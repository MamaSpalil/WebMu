<?php
// =====================================================================
//  Lightweight per-account action log (§3.2 of docs/IMPROVEMENT_PROMPT.md).
//
//  audit_log("login_ok", ["user" => "..."])
//
//  Backed by the optional `webmu_log` table (see docs/schema_addons.sql).
//  Every call site is gated by db_table_exists() so the site keeps working
//  on installations where the table was never created — in that case the
//  event still lands in logs/errors.log via err_log() so admins are not
//  blind during initial setup.
//
//  Conventions:
//    * `action` is a short snake_case verb: login_ok, login_fail, register,
//      change_password, vote, buy, market_list, market_buy, ...
//    * `account` defaults to the currently logged-in user (if any) and can
//      be overridden via the $details["account"] key for events that fire
//      before login (e.g. login_fail with the attempted login).
//    * `details` is JSON-encoded; never include passwords, PINs, tokens
//      or full credit-card numbers — same rule as core/errors.php.
// =====================================================================
if (!defined("insite")) die("no access");

/**
 * Record an audit event. Returns true on success (DB row written),
 * false otherwise. Always safe to call — never throws, never echoes.
 *
 * @param string $action  Short verb identifying the event.
 * @param array  $details Optional context. Reserved keys: "account" (override).
 */
function audit_log($action, array $details = [])
{
    $action = preg_replace('~[^a-z0-9_]~', '', strtolower((string)$action));
    if ($action === "" || strlen($action) > 32) {
        return false;
    }

    // Resolve account: explicit override, then session, else NULL.
    $account = null;
    if (isset($details["account"])) {
        $account = (string)$details["account"];
        unset($details["account"]);
    } elseif (function_exists("current_user")) {
        $u = current_user();
        if ($u && !empty($u["id"])) {
            $account = (string)$u["id"];
        }
    }
    if ($account !== null) {
        // memb___id is varchar(10) on stock MuOnline; truncate defensively.
        $account = substr($account, 0, 10);
    }

    $ip = function_exists("client_ip") ? client_ip() : ($_SERVER["REMOTE_ADDR"] ?? null);

    $details_json = null;
    if ($details) {
        $encoded = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_string($encoded)) {
            // webmu_log.details is nvarchar(400) — truncate to fit.
            $details_json = mb_substr($encoded, 0, 400, "UTF-8");
        }
    }

    // Fall back to the structured error log when the table is missing so
    // setup-time admins still see what happened.
    if (!function_exists("db_table_exists") || !db_table_exists("webmu_log")) {
        if (function_exists("err_log")) {
            err_log("audit", $action, [
                "account" => $account,
                "ip"      => $ip,
                "details" => $details,
            ]);
        }
        return false;
    }

    return (bool)db_exec(
        "INSERT INTO webmu_log (ts, ip, account, action, details)
         VALUES (GETDATE(), ?, ?, ?, ?)",
        [$ip, $account, $action, $details_json]
    );
}
