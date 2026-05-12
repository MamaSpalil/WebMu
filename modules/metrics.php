<?php
// =====================================================================
//  ?m=metrics — operational counters for monitoring (§3.3.2).
//
//  Returns JSON with the rolling counters useful for dashboards and
//  alerting:
//
//    {
//      "status": "ok",
//      "ts": "2026-05-12T20:34:12+00:00",
//      "counters": {
//          "online":              123,   // MEMB_STAT.ConnectStat = 1
//          "registrations_1h":      4,   // webmu_log action='register' last hour
//          "logins_1h":            58,   // login_ok in last hour
//          "login_fails_1h":        7,   // login_fail in last hour
//          "votes_1h":             19,   // 'vote' in last hour
//          "buys_1h":               3,   // 'buy' in last hour
//          "errors_1h":             0    // lines in logs/errors.log in last hour
//      },
//      "sources": { ... per-counter source: "db"/"log"/"unavailable" ... }
//    }
//
//  Access control — this endpoint exposes operational data and MUST NOT
//  be public:
//    * The remote IP must be in $config["metrics_allow_ips"] (default
//      127.0.0.1 + ::1), OR
//    * The request must carry the matching token from
//      $config["metrics_token"] (header `X-Metrics-Token` or `?token=`).
//  When neither check passes the endpoint returns 403 with no detail.
//
//  Like ?m=health, this route bypasses the maintenance gate so external
//  monitors keep polling during scheduled downtime.
// =====================================================================
if (!defined("insite")) die("no access");

while (ob_get_level() > 0) { @ob_end_clean(); }

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");

// ---- Access control ---------------------------------------------------
$allow_ips = $config["metrics_allow_ips"] ?? ["127.0.0.1", "::1"];
if (!is_array($allow_ips)) $allow_ips = [];
$ip = function_exists("client_ip") ? client_ip() : ($_SERVER["REMOTE_ADDR"] ?? "");
$ip_ok = in_array($ip, $allow_ips, true);

$token_cfg = (string)($config["metrics_token"] ?? "");
$token_in  = "";
if (isset($_SERVER["HTTP_X_METRICS_TOKEN"])) {
    $token_in = (string)$_SERVER["HTTP_X_METRICS_TOKEN"];
} elseif (isset($_GET["token"])) {
    $token_in = (string)$_GET["token"];
}
$token_ok = ($token_cfg !== "" && $token_in !== "" && hash_equals($token_cfg, $token_in));

if (!$ip_ok && !$token_ok) {
    http_response_code(403);
    echo json_encode(["status" => "forbidden"], JSON_UNESCAPED_SLASHES);
    return;
}

// ---- Helpers ----------------------------------------------------------
$counters = [];
$sources  = [];

$metrics_set = function ($key, $value, $source) use (&$counters, &$sources) {
    $counters[$key] = $value;
    $sources[$key]  = $source;
};

$metrics_log_action_count_1h = function ($action) {
    if (!function_exists("db_table_exists") || !db_table_exists("webmu_log")) {
        return null;
    }
    $row = db_one(
        "SELECT COUNT(*) AS n FROM webmu_log
          WHERE action = ?
            AND ts >= DATEADD(hh, -1, GETDATE())",
        [$action]
    );
    if (!is_array($row)) return null;
    return (int)($row["n"] ?? 0);
};

// ---- Counters ---------------------------------------------------------
$db_ok = function_exists("db_ping") ? (bool)db_ping() : false;

// Online — MEMB_STAT.ConnectStat = 1
if ($db_ok) {
    $stat_table   = (string)($config["stat_table"]        ?? "MEMB_STAT");
    $stat_connect = (string)($config["stat_connect_col"]  ?? "ConnectStat");
    if (db_table_exists($stat_table)) {
        $row = db_one("SELECT COUNT(*) AS n FROM " . db_ident($stat_table)
                    . " WHERE " . db_ident($stat_connect) . " = 1");
        if (is_array($row)) {
            $metrics_set("online", (int)($row["n"] ?? 0), "db");
        } else {
            $metrics_set("online", 0, "unavailable");
        }
    } else {
        $metrics_set("online", 0, "unavailable");
    }
} else {
    $metrics_set("online", 0, "unavailable");
}

// webmu_log-derived counters (last hour)
$log_actions = [
    "registrations_1h" => "register",
    "logins_1h"        => "login_ok",
    "login_fails_1h"   => "login_fail",
    "votes_1h"         => "vote",
    "buys_1h"          => "buy",
];
foreach ($log_actions as $key => $action) {
    if (!$db_ok) {
        $metrics_set($key, 0, "unavailable");
        continue;
    }
    $n = $metrics_log_action_count_1h($action);
    if ($n === null) {
        $metrics_set($key, 0, "unavailable");
    } else {
        $metrics_set($key, $n, "db");
    }
}

// Errors in the last hour — counted from logs/errors.log (JSON-lines).
// We tail the file (read the last ~256 KB) so the cost stays bounded
// even on long-running deployments.
$errors_log = ($config["__logs"] ?? "") . "/errors.log";
$errors_1h  = 0;
$errors_src = "unavailable";
if (is_file($errors_log) && is_readable($errors_log)) {
    $size = @filesize($errors_log);
    $tail = "";
    if ($size !== false && $size > 0) {
        $fp = @fopen($errors_log, "rb");
        if ($fp) {
            $read_bytes = 256 * 1024;
            if ($size > $read_bytes) {
                @fseek($fp, -$read_bytes, SEEK_END);
                @fgets($fp); // discard partial line
            }
            while (($line = fgets($fp)) !== false) {
                $tail .= $line;
            }
            @fclose($fp);
        }
    }
    if ($tail !== "") {
        $cutoff = time() - 3600;
        foreach (explode("\n", $tail) as $line) {
            $line = trim($line);
            if ($line === "") continue;
            // Lines are JSON with a "ts" field formatted as Y-m-d H:i:s in
            // local time (see err_log() in core/errors.php).
            $obj = json_decode($line, true);
            if (!is_array($obj) || empty($obj["ts"])) continue;
            $t = strtotime((string)$obj["ts"]);
            if ($t !== false && $t >= $cutoff) {
                $errors_1h++;
            }
        }
        $errors_src = "log";
    }
}
$counters["errors_1h"] = $errors_1h;
$sources["errors_1h"]  = $errors_src;

// ---- Response ---------------------------------------------------------
$payload = [
    "status"   => $db_ok ? "ok" : "degraded",
    "ts"       => gmdate("c"),
    "counters" => $counters,
    "sources"  => $sources,
];

http_response_code(200);
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
