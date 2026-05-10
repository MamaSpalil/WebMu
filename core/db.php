<?php
// =====================================================================
//  Database wrapper (ODBC → MS SQL Server, MuOnline schema).
//
//  All queries go through db_query() / db_one() / db_all() and use
//  parameterized statements (odbc_prepare + odbc_execute) — never
//  concatenate values into SQL.
// =====================================================================
if (!defined("insite")) die("no access");

/**
 * Lazily open and cache the ODBC connection.
 * Returns the connection resource, or null if the driver/host is unavailable.
 */
function db()
{
    static $conn = null;
    static $tried = false;
    global $config;

    if ($conn !== null || $tried) {
        return $conn;
    }
    $tried = true;

    if (!function_exists("odbc_connect")) {
        db_set_error("ODBC extension is not installed — database features disabled.");
        return null;
    }
    $driver = $config["odbc_driver"] ?? "SQL Server";
    $host   = $config["db_host"]     ?? "127.0.0.1";
    $name   = $config["db_name"]     ?? "MuOnline";
    $user   = $config["db_user"]     ?? "";
    $pwd    = $config["db_upwd"]     ?? "";

    $dsn = "Driver={" . $driver . "};Server=" . $host . ";Database=" . $name . ";";
    $c = @odbc_connect($dsn, $user, $pwd);
    if (!$c) {
        db_set_error("ODBC connect failed: " . odbc_errormsg());
        return null;
    }
    $conn = $c;
    db_set_error("");
    return $conn;
}

/** Store the last connection error for templates and logs. */
function db_set_error($msg)
{
    global $config;
    $config["__db_connection_error"] = (string)$msg;
    if ($config["__db_connection_error"] !== "") {
        db_log($config["__db_connection_error"]);
    }
}

/** Return the last connection error without opening a new connection. */
function db_last_error()
{
    global $config;
    return $config["__db_connection_error"] ?? "";
}

/** Check the connection once and return the current connection error. */
function db_check_connection_error()
{
    db();
    return db_last_error();
}

/**
 * Execute a parameterized query.
 *  $sql  — SQL with ? placeholders.
 *  $args — values to bind, in order.
 * Returns the result resource on success, or false on error.
 */
function db_query($sql, array $args = [])
{
    $c = db();
    if (!$c) {
        return false;
    }
    $stmt = @odbc_prepare($c, $sql);
    if (!$stmt) {
        db_log("prepare failed: " . odbc_errormsg($c) . " | SQL: " . $sql);
        return false;
    }
    // odbc_execute requires every bound parameter to be a string —
    // it does not infer types from PHP scalars, so we coerce here.
    $bound = [];
    foreach ($args as $v) {
        $bound[] = ($v === null) ? null : (string)$v;
    }
    $ok = @odbc_execute($stmt, $bound);
    if (!$ok) {
        db_log("execute failed: " . odbc_errormsg($c) . " | SQL: " . $sql);
        return false;
    }
    return $stmt;
}

/** Fetch a single row (assoc) or null. */
function db_one($sql, array $args = [])
{
    $r = db_query($sql, $args);
    if (!$r) return null;
    $row = odbc_fetch_array($r);
    return $row ?: null;
}

/** Fetch all rows (array of assoc). */
function db_all($sql, array $args = [])
{
    $r = db_query($sql, $args);
    if (!$r) return [];
    $rows = [];
    while ($row = odbc_fetch_array($r)) {
        $rows[] = $row;
    }
    return $rows;
}

/** Execute a non-SELECT (INSERT/UPDATE/DELETE). Returns true/false. */
function db_exec($sql, array $args = [])
{
    return (bool)db_query($sql, $args);
}

/** Escape a LIKE-pattern user input (escapes % _ [ ]). */
function db_escape_like($s)
{
    return strtr((string)$s, [
        "[" => "[[]", "%" => "[%]", "_" => "[_]",
    ]);
}

/** Validate and quote a configured SQL Server identifier. */
function db_ident($name, $fallback = null)
{
    $name = trim((string)$name);
    if (preg_match('~^[A-Za-z_][A-Za-z0-9_]*$~', $name)) {
        return "[" . $name . "]";
    }
    if ($fallback !== null) {
        db_log("Invalid SQL identifier `" . $name . "`, using fallback `" . $fallback . "`.");
        return db_ident($fallback);
    }
    db_log("Invalid SQL identifier `" . $name . "`.");
    return null;
}

/** Best-effort table existence check used for optional WebMu tables. */
function db_table_exists($table)
{
    $table = trim((string)$table);
    if (!preg_match('~^[A-Za-z_][A-Za-z0-9_]*$~', $table)) {
        return false;
    }
    $row = db_one(
        "SELECT 1 AS x FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?",
        [$table]
    );
    return (bool)$row;
}

/** Append to the error log; only echoed when debug=1. */
function db_log($msg)
{
    global $config;
    $line = "[" . date("Y-m-d H:i:s") . "] " . $msg . "\n";
    @file_put_contents(($config["__logs"] ?? sys_get_temp_dir()) . "/db.log", $line, FILE_APPEND);
    if (!empty($config["debug"])) {
        echo "<pre style=\"color:#f88;background:#200;padding:8px;border:1px solid #800\">" .
             htmlspecialchars($msg) . "</pre>";
    }
}
