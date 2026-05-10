<?php
// =====================================================================
//  Authentication: sessions, login, logout, CSRF, IP rate-limit.
//  Passwords obey $config["md5use"]: "off" = plain (MuOnline default),
//  "on"  = md5() — to match how the game client stores them.
// =====================================================================
if (!defined("insite")) die("no access");

/** Hash an incoming password according to opt.php. */
function pwd_for_db($plain)
{
    global $config;
    if (($config["md5use"] ?? "off") === "on") {
        return md5((string)$plain);
    }
    return (string)$plain;
}

/** Verify a plaintext attempt against the MEMB_INFO row. */
function verify_password($plain, $row)
{
    if (!$row || !isset($row["memb__pwd"])) return false;
    $stored = trim((string)$row["memb__pwd"]);
    return hash_equals($stored, pwd_for_db($plain));
}

/** ---------- session helpers ---------- */
function current_user()
{
    return $_SESSION["user"] ?? null;
}

function require_login()
{
    if (!current_user()) {
        header("Location: index.php?m=login&next=" . urlencode($_SERVER["REQUEST_URI"] ?? ""));
        exit;
    }
}

function login_user(array $row)
{
    // Regenerate session id to prevent fixation.
    session_regenerate_id(true);
    $_SESSION["user"] = [
        "id"   => trim((string)($row["memb___id"]   ?? "")),
        "mail" => trim((string)($row["mail_addr"]   ?? "")),
        "name" => trim((string)($row["memb_name"]   ?? "")),
        "ts"   => time(),
    ];
}

function logout_user()
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), "", time() - 42000,
            $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
}

/** ---------- CSRF ---------- */
function csrf_token()
{
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(16));
    }
    return $_SESSION["csrf"];
}

function csrf_check()
{
    $sent = $_POST["_csrf"] ?? "";
    return !empty($_SESSION["csrf"]) && hash_equals($_SESSION["csrf"], (string)$sent);
}

function csrf_field()
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">' .
           '<input type="text" name="hp_field" value="" tabindex="-1" autocomplete="off" ' .
           'style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden">';
}

/** Returns true if honeypot was filled (spam). */
function honeypot_tripped()
{
    return !empty($_POST["hp_field"]);
}

/** ---------- IP rate-limit (file-based, no DB needed) ---------- */
function rate_limit_hit($key, $max, $window_seconds)
{
    global $config;
    $dir = ($config["__cache"] ?? sys_get_temp_dir()) . "/rl";
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $file = $dir . "/" . md5($key);
    $now = time();
    $entries = [];
    if (is_file($file)) {
        $entries = @json_decode((string)file_get_contents($file), true) ?: [];
    }
    // drop expired
    $entries = array_values(array_filter($entries, function ($t) use ($now, $window_seconds) {
        return ($now - (int)$t) < $window_seconds;
    }));
    if (count($entries) >= $max) {
        return true;
    }
    $entries[] = $now;
    @file_put_contents($file, json_encode($entries), LOCK_EX);
    return false;
}

function client_ip()
{
    return $_SERVER["REMOTE_ADDR"] ?? "0.0.0.0";
}
