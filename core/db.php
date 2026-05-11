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
 * Validate the database section of $config loaded from opt.php.
 * Returns an empty string on success, or a human-readable error message
 * naming the missing/placeholder keys. All site modules read DB settings
 * exclusively from opt.php — never hardcode credentials elsewhere.
 */
function db_validate_config()
{
    global $config;

    if (!empty($config["__using_example"])) {
        return "Loaded opt.example.php — copy it to opt.php and fill in real database credentials.";
    }

    // If a full DSN override is supplied, only credentials are required.
    $hasDsn = !empty($config["db_dsn"]);
    $required = $hasDsn
        ? ["db_user", "db_upwd"]
        : ["db_host", "db_user", "db_upwd", "db_name", "odbc_driver"];

    $missing = [];
    foreach ($required as $key) {
        $val = $config[$key] ?? "";
        if ($val === "" || $val === null) {
            $missing[] = $key;
        }
    }
    // Reject obvious template placeholder so the site fails loudly instead
    // of silently trying to connect with the example password.
    if (isset($config["db_upwd"]) && $config["db_upwd"] === "CHANGE_ME") {
        $missing[] = "db_upwd (still set to CHANGE_ME)";
    }
    if ($missing) {
        return "opt.php is missing or has placeholder values for: " . implode(", ", $missing) . ".";
    }
    return "";
}

/**
 * Build the ODBC DSN string from $config (opt.php). All connection
 * settings — host, port, database, driver, timeout, charset, app name —
 * come from opt.php so that every module/file shares one source of truth.
 *
 * If $config["db_dsn"] is set, it is returned verbatim so advanced setups
 * can supply a complete DSN (e.g. SQL Native Client with TrustServerCertificate).
 */
function db_build_dsn()
{
    global $config;

    if (!empty($config["db_dsn"])) {
        return (string)$config["db_dsn"];
    }

    $driver = $config["odbc_driver"] ?? "SQL Server";
    $host   = $config["db_host"]     ?? "127.0.0.1";
    $port   = $config["db_port"]     ?? "";
    $name   = $config["db_name"]     ?? "MuOnline";

    // Allow either explicit db_port or "host,port" baked into db_host.
    $server = $host;
    if ($port !== "" && strpos($host, ",") === false) {
        $server = $host . "," . $port;
    }

    $parts = [
        "Driver={" . $driver . "}",
        "Server="  . $server,
        "Database=" . $name,
    ];
    if (!empty($config["db_appname"])) {
        $parts[] = "APP=" . $config["db_appname"];
    }
    if (!empty($config["db_charset"])) {
        // Honored by SQL Server Native Client / MSODBCSQL.
        $parts[] = "CharacterSet=" . $config["db_charset"];
    }
    return implode(";", $parts) . ";";
}

/**
 * Lazily open and cache the ODBC connection.
 * Returns the connection resource, or null if the driver/host is unavailable.
 * All settings are read from opt.php via $config — see db_build_dsn().
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

    $cfgError = db_validate_config();
    if ($cfgError !== "") {
        db_set_error($cfgError);
        return null;
    }

    $user = $config["db_user"] ?? "";
    $pwd  = $config["db_upwd"] ?? "";
    $dsn  = db_build_dsn();

    // Optional connection / login timeouts (seconds).
    $timeout = isset($config["db_timeout"]) ? (int)$config["db_timeout"] : 0;
    if ($timeout > 0 && function_exists("ini_set")) {
        @ini_set("odbc.defaultlrl", (string)max(8192, $timeout * 1024));
    }

    $persistent = !empty($config["db_persistent"]);
    // Prefer driver cursors: the ODBC cursor library can re-query result
    // columns by alias during SQLGetData, which breaks SELECT aliases such as
    // CharName/total_resets on SQL Server.
    $cursorType = isset($config["odbc_cursor_type"])
        ? (int)$config["odbc_cursor_type"]
        : (defined("SQL_CUR_USE_DRIVER") ? SQL_CUR_USE_DRIVER : 2);
    $c = $persistent
        ? @odbc_pconnect($dsn, $user, $pwd, $cursorType)
        : @odbc_connect($dsn, $user, $pwd, $cursorType);
    if (!$c) {
        db_set_error("ODBC connect failed: " . odbc_errormsg());
        return null;
    }
    $conn = $c;
    db_set_error(null);
    return $conn;
}

/**
 * Lightweight connectivity probe — opens (or reuses) the connection and
 * runs a trivial round-trip. Returns true if the remote DB is reachable
 * with the credentials currently in opt.php.
 */
function db_ping()
{
    $c = db();
    if (!$c) return false;
    $r = @odbc_exec($c, "SELECT 1");
    if (!$r) {
        db_set_error("ODBC ping failed: " . odbc_errormsg($c));
        return false;
    }
    return true;
}

/** Store the last connection error for templates and logs. */
function db_set_error($msg)
{
    global $config;
    if ($msg === null) {
        unset($config["__db_connection_error"]);
        return;
    }
    $config["__db_connection_error"] = (string)$msg;
    db_log($config["__db_connection_error"]);
    if (function_exists("err_db")) {
        err_db("connection: " . $config["__db_connection_error"]);
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
    db(); // Initializes the lazy connection and populates any connection error.
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
        $msg = odbc_errormsg($c);
        db_log("prepare failed: " . $msg . " | SQL: " . $sql);
        if (function_exists("err_db")) err_db("prepare failed: " . $msg, $sql, $args);
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
        $msg = odbc_errormsg($c);
        db_log("execute failed: " . $msg . " | SQL: " . $sql);
        if (function_exists("err_db")) err_db("execute failed: " . $msg, $sql, $args);
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
    static $cache = [];
    $key = strtolower($table);
    if (isset($cache[$key])) return $cache[$key];
    $row = db_one(
        "SELECT 1 AS x FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?",
        [$table]
    );
    return $cache[$key] = (bool)$row;
}

/**
 * Best-effort column existence check (cached per request).
 * Used by modules that read optional columns (e.g. MEMB_INFO.cash, .usd,
 * Character.MasterLevel) so the site degrades gracefully on stock backups.
 */
function db_column_exists($table, $column)
{
    $table  = trim((string)$table);
    $column = trim((string)$column);
    if (!preg_match('~^[A-Za-z_][A-Za-z0-9_]*$~', $table))  return false;
    if (!preg_match('~^[A-Za-z_][A-Za-z0-9_]*$~', $column)) return false;
    static $cache = [];
    $key = strtolower($table . "." . $column);
    if (isset($cache[$key])) return $cache[$key];
    $row = db_one(
        "SELECT 1 AS x FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$table, $column]
    );
    return $cache[$key] = (bool)$row;
}

/** Return the last ODBC error message (empty string if none). */
function db_last_odbc_error()
{
    $c = db();
    if (!$c) return db_last_error();
    return (string)@odbc_errormsg($c);
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
