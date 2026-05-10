<?php
// =====================================================================
//  Helpers: validators, formatters, MuOnline class lookup, file cache.
// =====================================================================
if (!defined("insite")) die("no access");

/** ---- escaping ---- */
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

/** ---- validators ---- */
function valid_login($s)
{
    return is_string($s) && preg_match('~^[A-Za-z0-9]{4,10}$~', $s);
}
function valid_password($s)
{
    // memb__pwd is varchar(10) in stock MuOnline schema.
    return is_string($s) && strlen($s) >= 4 && strlen($s) <= 10;
}
function valid_pin($s)
{
    return is_string($s) && preg_match('~^[0-9]{4}$~', $s);
}
function valid_email($s)
{
    return is_string($s) && filter_var($s, FILTER_VALIDATE_EMAIL) !== false && strlen($s) <= 50;
}

/** ---- formatters ---- */
function fmt_zen($n)
{
    return number_format((float)$n, 0, ".", " ");
}
function fmt_int($n)
{
    return number_format((int)$n, 0, ".", " ");
}

/**
 * Map MuOnline Character.Class numeric code to a short name + slug.
 * Codes follow the standard Season 6 layout (base / 1st-quest / 2nd-quest):
 *   0,1,2,3 = DW;  16,17,18,19 = DK;  32,33,34,35 = ELF;  48,49,50 = MG;
 *   64,65,66 = DL; 80,81,82,83 = SM;  96,97,98 = RF.
 */
function mu_class($code)
{
    $code = (int)$code;
    // The high nibble of Character.Class identifies the base class:
    //   0x0=DW, 0x1=DK, 0x2=ELF, 0x3=MG, 0x4=DL, 0x5=SM, 0x6=RF.
    // The low nibble distinguishes 1st/2nd/3rd quest variants of the
    // same class; we collapse them to the base class for display.
    $base = ($code >> 4) & 0x0F;
    $map = [
        0x0 => ["Dark Wizard",    "dw"],
        0x1 => ["Dark Knight",    "dk"],
        0x2 => ["Elf",            "elf"],
        0x3 => ["Magic Gladiator","mg"],
        0x4 => ["Dark Lord",      "dl"],
        0x5 => ["Summoner",       "sm"],
        0x6 => ["Rage Fighter",   "rf"],
    ];
    return $map[$base] ?? ["Unknown", "dw"];
}

/** ---- file cache (used by ranking/widgets) ---- */
function cache_get($key, $ttl)
{
    global $config;
    $f = ($config["__cache"] ?? sys_get_temp_dir()) . "/" . md5($key) . ".cache";
    if (!is_file($f)) return null;
    if ((time() - filemtime($f)) > $ttl) return null;
    $raw = @file_get_contents($f);
    if ($raw === false) return null;
    $v = @unserialize($raw);
    return $v === false ? null : $v;
}
function cache_set($key, $value)
{
    global $config;
    $dir = $config["__cache"] ?? sys_get_temp_dir();
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    @file_put_contents($dir . "/" . md5($key) . ".cache", serialize($value), LOCK_EX);
}

/** ---- redirect helper ---- */
function redirect($url)
{
    header("Location: " . $url);
    exit;
}

/** Build a flash message for the next request. */
function flash_set($type, $text)
{
    $_SESSION["flash"][] = ["t" => $type, "m" => $text];
}
function flash_pop()
{
    $msgs = $_SESSION["flash"] ?? [];
    unset($_SESSION["flash"]);
    return $msgs;
}
