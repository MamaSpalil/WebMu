<?php
// =====================================================================
//  WebMu bootstrap. Included by index.php (the single front controller).
//  Loads opt.php, opens the DB, starts session, prepares globals.
// =====================================================================
if (!defined("insite")) define("insite", 1);

// ---- error handling ----------------------------------------------------
error_reporting(E_ALL);

// ---- locate config -----------------------------------------------------
$ROOT = dirname(__DIR__);
if (is_file($ROOT . "/opt.php")) {
    require $ROOT . "/opt.php";
} elseif (is_file($ROOT . "/opt.example.php")) {
    // Last-ditch fallback so the site can boot for setup screens.
    require $ROOT . "/opt.example.php";
    $config["__using_example"] = true;
} else {
    http_response_code(500);
    die("Configuration file opt.php is missing. Copy opt.example.php to opt.php.");
}

if (!isset($config) || !is_array($config)) {
    http_response_code(500);
    die("Invalid configuration in opt.php.");
}

// expose paths to the rest of the application
$config["__root"]   = $ROOT;
$config["__core"]   = $ROOT . "/core";
$config["__themes"] = $ROOT . "/themes";
$config["__theme_dir"] = $ROOT . "/themes/" . preg_replace('~[^a-z0-9_]~i', '', $config["theme"] ?? "ex");
$config["__cache"]  = $ROOT . "/cache";
$config["__logs"]   = $ROOT . "/logs";

ini_set("display_errors", !empty($config["debug"]) ? "1" : "0");
ini_set("log_errors", "1");
ini_set("error_log", $config["__logs"] . "/php-error.log");

// ---- session -----------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        "lifetime" => 0,
        "path"     => "/",
        "secure"   => !empty($_SERVER["HTTPS"]),
        "httponly" => true,
        "samesite" => "Lax",
    ]);
    session_name("webmu_sid");
    session_start();
}

// ---- core modules ------------------------------------------------------
require $config["__core"] . "/helpers.php";
require $config["__core"] . "/errors.php";
err_init();
require $config["__core"] . "/lang.php";
require $config["__core"] . "/db.php";
require $config["__core"] . "/auth.php";
require $config["__core"] . "/render.php";
require $config["__core"] . "/catalog.php";

// load language strings
lang_load($config["def_lang"] ?? "rus");

// maintenance gate (skipped for the explicit "maintenance" route handler)
if (!empty($config["under_rec"]) && (($_GET["m"] ?? "") !== "maintenance")) {
    render_page("maintenance", [
        "title" => lang("maintenance.title", "Server is under maintenance"),
    ]);
    exit;
}
