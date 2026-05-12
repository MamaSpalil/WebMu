<?php
// =====================================================================
//  ?m=health — lightweight health probe for external monitors (§3.3.1).
//
//  Returns JSON with HTTP 200 when the database is reachable and the
//  required MuOnline tables (MEMB_INFO, Character) are present, HTTP 503
//  otherwise. Designed to be cheap (one ping + two cached existence
//  checks) so it can be polled every few seconds.
//
//  No authentication is required and no PII is exposed — just status,
//  version, and per-check booleans.
// =====================================================================
if (!defined("insite")) die("no access");

// Disable any output buffering / template wrapping — we render JSON.
while (ob_get_level() > 0) { @ob_end_clean(); }

// Required core tables for the site to be useful at all.
$required = ["MEMB_INFO", "Character"];

// Optional WebMu add-on tables (idempotent, see docs/schema_addons.sql).
// They are reported in the JSON payload so admins can confirm their
// schema migration ran, but their absence does not flip overall status.
$optional = [
    "webmu_log",
    "WebDonateItems", "WebDonateLog",
    "WebMarketItems", "WebMarketLog",
    "WebOnlineHours",
    (string)($config["vip_table"] ?? "VipList"),
    (string)($config["wh_table"]  ?? "warehouse"),
];

$db_ok = false;
$db_error = "";
$tables = [];

if (function_exists("db_ping")) {
    $db_ok = (bool)db_ping();
    if (!$db_ok) {
        $db_error = (string)db_last_error();
    }
}

if ($db_ok) {
    foreach ($required as $t) {
        $tables[$t] = (bool)db_table_exists($t);
    }
    foreach ($optional as $t) {
        if ($t === "" || isset($tables[$t])) continue;
        $tables[$t] = (bool)db_table_exists($t);
    }
}

$tables_ok = $db_ok;
foreach ($required as $t) {
    if (empty($tables[$t])) { $tables_ok = false; break; }
}
$status_ok = $db_ok && $tables_ok;

http_response_code($status_ok ? 200 : 503);
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");

$payload = [
    "status"     => $status_ok ? "ok" : "fail",
    "ts"         => gmdate("c"),
    "checks"     => [
        "db"     => $db_ok,
        "tables" => $tables_ok,
    ],
    "tables"     => $tables,
    "maintenance"=> !empty($config["under_rec"]),
];

// Only expose the verbose connection error message when running in debug
// mode — it can leak ODBC driver / hostname details otherwise.
if (!$db_ok && !empty($config["debug"]) && $db_error !== "") {
    $payload["error"] = $db_error;
}

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
