<?php
// =====================================================================
//  Centralized per-request error subsystem.
//
//  All site code should report failures through err_log() / err_db() so
//  that:
//    * every error lands in logs/errors.log as a structured line,
//    * the active module/widget is recorded next to each error,
//    * the header template can display a single user-visible notice
//      summarising which modules failed to export data from the DB
//      (with full details when $config["debug"] = 1).
//
//  Conventions:
//    * Bound query parameters are NEVER logged — they may contain
//      passwords / PINs from form submissions.
//    * Errors are kept in $config["__errors"] for the current request and
//      are read back by err_collected() / err_summary() in templates.
// =====================================================================
if (!defined("insite")) die("no access");

/**
 * Initialise per-request state and register PHP error / exception
 * handlers. Safe to call once from core/init.php.
 */
function err_init()
{
    global $config;

    if (!isset($config["__errors"]) || !is_array($config["__errors"])) {
        $config["__errors"] = [];
    }
    if (!isset($config["__err_ctx"]) || !is_array($config["__err_ctx"])) {
        $config["__err_ctx"] = [];
    }

    // Make sure the log directory exists; fall back to the system temp dir
    // if logs/ is not writable so logging still works.
    $logs = $config["__logs"] ?? "";
    if ($logs !== "" && !is_dir($logs)) {
        @mkdir($logs, 0775, true);
    }
    if ($logs === "" || !is_writable($logs)) {
        $config["__logs"] = sys_get_temp_dir();
    }

    set_error_handler("err_php_handler");
    set_exception_handler("err_exception_handler");
    register_shutdown_function("err_fatal_handler");
}

/**
 * Push a context entry (e.g. the active module or widget name) so that
 * any error logged while it is on top of the stack is attributed to it.
 *   $type — short kind: "module", "widget", "callback", ...
 *   $name — short identifier (a-z0-9_).
 */
function err_push_context($name, $type = "module")
{
    global $config;
    $config["__err_ctx"][] = [
        "type" => preg_replace('~[^a-z0-9_]~i', '', (string)$type) ?: "module",
        "name" => preg_replace('~[^a-z0-9_./-]~i', '', (string)$name) ?: "(unknown)",
    ];
}

/** Pop the most recently pushed context. */
function err_pop_context()
{
    global $config;
    if (!empty($config["__err_ctx"])) {
        array_pop($config["__err_ctx"]);
    }
}

/** Return the current top-of-stack context or a "global" fallback. */
function err_current_context()
{
    global $config;
    $stack = $config["__err_ctx"] ?? [];
    if (!$stack) {
        return ["type" => "global", "name" => "global"];
    }
    return end($stack);
}

/**
 * Record an error.
 *   $kind    — short category: "db", "php", "exception", "module", ...
 *   $message — human-readable message.
 *   $extra   — optional associative array of extra fields (e.g. ["sql"=>...]).
 *              MUST NOT contain bound query parameter values.
 */
function err_log($kind, $message, array $extra = [])
{
    global $config;

    $ctx = err_current_context();
    $entry = [
        "ts"      => date("Y-m-d H:i:s"),
        "kind"    => (string)$kind,
        "context" => $ctx,
        "message" => (string)$message,
        "extra"   => $extra,
    ];

    // In-memory copy for templates to read back during this request.
    $config["__errors"][] = $entry;

    // Append a single JSON line for offline analysis.
    $logs = $config["__logs"] ?? sys_get_temp_dir();
    $line = json_encode([
        "ts"      => $entry["ts"],
        "kind"    => $entry["kind"],
        "ctx"     => $ctx["type"] . ":" . $ctx["name"],
        "uri"     => $_SERVER["REQUEST_URI"] ?? "",
        "ip"      => $_SERVER["REMOTE_ADDR"] ?? "",
        "message" => $entry["message"],
        "extra"   => $entry["extra"],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($line === false) {
        $line = "[" . $entry["ts"] . "] " . $entry["kind"] . " " . $entry["message"];
    }
    @file_put_contents($logs . "/errors.log", $line . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Convenience wrapper for errors that occurred while talking to the
 * database. SQL is recorded; bound parameter values are deliberately
 * not, since they can contain user secrets (passwords, PINs, e-mails).
 */
function err_db($message, $sql = null, array $bound = [])
{
    $extra = [];
    if ($sql !== null && $sql !== "") {
        // Collapse whitespace so the log line stays readable.
        $extra["sql"] = preg_replace('~\s+~', ' ', trim((string)$sql));
    }
    if (!empty($bound)) {
        // Record only the number of parameters, never their values.
        $extra["param_count"] = count($bound);
    }
    err_log("db", $message, $extra);
}

/** Return all errors collected during the current request, optionally filtered. */
function err_collected($kind = null)
{
    global $config;
    $all = $config["__errors"] ?? [];
    if ($kind === null) return $all;
    $kind = (string)$kind;
    return array_values(array_filter($all, function ($e) use ($kind) {
        return ($e["kind"] ?? "") === $kind;
    }));
}

/**
 * Group collected errors by context for compact display in templates.
 * Returns: [ "module:ranking" => ["count"=>N, "type"=>..., "name"=>..., "kinds"=>[...]], ... ]
 */
function err_summary($kind = null)
{
    $out = [];
    foreach (err_collected($kind) as $e) {
        $ctx = $e["context"] ?? ["type" => "global", "name" => "global"];
        $key = $ctx["type"] . ":" . $ctx["name"];
        if (!isset($out[$key])) {
            $out[$key] = [
                "type"  => $ctx["type"],
                "name"  => $ctx["name"],
                "count" => 0,
                "kinds" => [],
            ];
        }
        $out[$key]["count"]++;
        $k = $e["kind"] ?? "?";
        $out[$key]["kinds"][$k] = ($out[$key]["kinds"][$k] ?? 0) + 1;
    }
    return $out;
}

/** True if any error of the given kind (or any kind) was logged this request. */
function err_has($kind = null)
{
    return !empty(err_collected($kind));
}

// ---- PHP-level handlers --------------------------------------------------

/**
 * Convert non-fatal PHP errors / warnings / notices into err_log() entries.
 * Returning false lets PHP run its normal error handler too (so display_errors
 * still works under debug). Errors silenced with @ are skipped.
 */
function err_php_handler($severity, $message, $file = "", $line = 0)
{
    // Respect @-suppression and the configured error_reporting() mask.
    if (!(error_reporting() & $severity)) return false;

    $map = [
        E_ERROR             => "error",
        E_WARNING           => "warning",
        E_PARSE             => "parse",
        E_NOTICE            => "notice",
        E_CORE_ERROR        => "error",
        E_CORE_WARNING      => "warning",
        E_COMPILE_ERROR     => "error",
        E_COMPILE_WARNING   => "warning",
        E_USER_ERROR        => "error",
        E_USER_WARNING      => "warning",
        E_USER_NOTICE       => "notice",
        E_RECOVERABLE_ERROR => "error",
        E_DEPRECATED        => "deprecated",
        E_USER_DEPRECATED   => "deprecated",
    ];
    $level = $map[$severity] ?? "error";
    err_log("php", "PHP " . $level . ": " . $message, [
        "file" => $file,
        "line" => (int)$line,
    ]);
    return false;
}

/** Last-resort handler for uncaught exceptions. */
function err_exception_handler($e)
{
    err_log("exception", get_class($e) . ": " . $e->getMessage(), [
        "file" => $e->getFile(),
        "line" => $e->getLine(),
    ]);
    err_render_fatal_page();
}

/** Capture fatal errors that bypass set_error_handler(). */
function err_fatal_handler()
{
    $e = error_get_last();
    if (!$e) return;
    $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($e["type"], $fatal, true)) return;
    err_log("php", "PHP fatal: " . ($e["message"] ?? ""), [
        "file" => $e["file"] ?? "",
        "line" => (int)($e["line"] ?? 0),
    ]);
    err_render_fatal_page();
}

/**
 * Render a neutral, self-contained "internal error" page after an
 * uncaught exception or fatal so the user never sees a blank screen
 * or a half-rendered template (§3.1.1).
 *
 * Intentionally inline HTML — the regular template renderer cannot be
 * trusted at this point (the failure may have happened mid-render, or
 * inside the template engine itself). Stays silent if:
 *   * we're running on the CLI,
 *   * headers have already been sent (response is already on the wire),
 *   * the active route is one of the JSON endpoints (health/metrics) —
 *     those handle their own error formatting.
 */
function err_render_fatal_page()
{
    if (PHP_SAPI === "cli") return;
    if (headers_sent()) return;

    $route = isset($_GET["m"]) ? preg_replace('~[^a-z_]~', '', strtolower((string)$_GET["m"])) : "";
    if ($route === "health" || $route === "metrics") return;

    // Drop any partially-buffered output so we render a clean page.
    while (ob_get_level() > 0) { @ob_end_clean(); }

    http_response_code(500);
    header("Content-Type: text/html; charset=utf-8");
    header("Cache-Control: no-store, no-cache, must-revalidate");

    $title = function_exists("lang") ? lang("errors.unhandled", "Internal server error") : "Internal server error";
    $body  = function_exists("lang") ? lang("errors.unhandled_text",
        "Something went wrong while handling your request. The incident has been logged. Please try again in a moment.")
        : "Something went wrong while handling your request. The incident has been logged. Please try again in a moment.";
    $home  = function_exists("lang") ? lang("errors.return_home", "Return home") : "Return home";

    $h = function_exists("h")
        ? "h"
        : function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); };

    echo "<!doctype html>\n<html lang=\"en\"><head><meta charset=\"utf-8\">"
       . "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">"
       . "<title>500 — " . $h($title) . "</title>"
       . "<style>"
       . "body{margin:0;padding:0;background:#0e0e12;color:#e6e2d5;"
       . "font:16px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;}"
       . "main{max-width:560px;margin:8vh auto;padding:32px 28px;"
       . "background:#15151c;border:1px solid #2a2a36;border-radius:8px;}"
       . "h1{margin:0 0 12px;font-size:22px;color:#f3d27a;}"
       . "p{margin:0 0 16px;color:#bdb7a3;}"
       . "a{color:#f3d27a;text-decoration:none;border-bottom:1px solid #5a4d20;}"
       . "a:hover{border-bottom-color:#f3d27a;}"
       . "</style></head><body><main>"
       . "<h1>500 — " . $h($title) . "</h1>"
       . "<p>" . $h($body) . "</p>"
       . "<p><a href=\"index.php\">" . $h($home) . "</a></p>"
       . "</main></body></html>";
}
