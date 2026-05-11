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
 * Codes follow the common MuOnline layout (base / quest evolutions):
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

/**
 * Map MuOnline Character.MapNumber to a human-readable map name.
 * Covers the standard Season 3 Episode 1 map list. Unknown ids fall back
 * to "Map #<n>" so the rank/online tables always show something useful.
 */
function mu_map($id)
{
    $id = (int)$id;
    static $map = [
        0  => "Lorencia",
        1  => "Dungeon",
        2  => "Devias",
        3  => "Noria",
        4  => "Lost Tower",
        5  => "Exile",
        6  => "Arena",
        7  => "Atlans",
        8  => "Tarkan",
        9  => "Devil Square",
        10 => "Icarus",
        11 => "Blood Castle 1",
        12 => "Blood Castle 2",
        13 => "Blood Castle 3",
        14 => "Blood Castle 4",
        15 => "Blood Castle 5",
        16 => "Blood Castle 6",
        17 => "Blood Castle 7",
        18 => "Chaos Castle 1",
        19 => "Chaos Castle 2",
        20 => "Chaos Castle 3",
        21 => "Chaos Castle 4",
        22 => "Chaos Castle 5",
        23 => "Chaos Castle 6",
        24 => "Kalima 1",
        25 => "Kalima 2",
        26 => "Kalima 3",
        27 => "Kalima 4",
        28 => "Kalima 5",
        29 => "Kalima 6",
        30 => "Valley of Loren",
        31 => "Land of Trial",
        32 => "Devil Square 2",
        33 => "Aida",
        34 => "Crywolf",
        37 => "Kalima 7",
        38 => "Kanturu 1",
        39 => "Kanturu 2",
        40 => "Kanturu 3 (Boss)",
        41 => "Silent Map",
        42 => "Barracks of Balgass",
        43 => "Refuge of Balgass",
        45 => "Illusion Temple 1",
        46 => "Illusion Temple 2",
        47 => "Illusion Temple 3",
        48 => "Illusion Temple 4",
        49 => "Illusion Temple 5",
        50 => "Illusion Temple 6",
        51 => "Elveland",
        52 => "Blood Castle 8",
        53 => "Chaos Castle 7",
    ];
    return $map[$id] ?? ("Map #" . $id);
}

/**
 * Names of the 12 equipped inventory slots in Season 3 (in display order).
 * Index → slot label. Matches the in-game layout.
 */
function mu_equip_slots()
{
    return [
        0  => "Helm",
        1  => "Armor",
        2  => "Pants",
        3  => "Gloves",
        4  => "Boots",
        5  => "Right Hand",
        6  => "Left Hand",
        7  => "Wings",
        8  => "Pet",
        9  => "Ring 1",
        10 => "Ring 2",
        11 => "Pendant",
    ];
}

/**
 * Decode the 12 equipped slots from a MuOnline Character.Inventory blob.
 *
 * In Season 3 the inventory is a packed varbinary; slots 0–11 are equipped,
 * each record is 12 bytes long, and an empty slot is filled with 0xFF.
 * We return an array of 12 entries with:
 *   - empty:   true/false
 *   - group:   item-group id (0-15)  — high nibble of byte 9
 *   - code:    item code     (0-511) — byte 0 + bit 7 of byte 9
 *   - level:   item +N level (0-15)
 *   - skill:   bool — skill option
 *   - luck:    bool — luck option
 *   - opt:     additional option amount (0-7, ×4 = +N)
 *   - exc:     excellent option bitmask
 *   - raw:     12-byte hex (for debugging)
 *
 * The exact bit layout is emulator-specific, but the format below is the
 * most common Season 3 layout. Callers should treat the decoded fields as
 * best-effort and fall back to "—" when group/code can't be looked up.
 */
function mu_parse_equipped_inventory($blob)
{
    $slots = array_fill(0, 12, [
        "empty" => true, "group" => 0, "code" => 0, "level" => 0,
        "skill" => false, "luck" => false, "opt" => 0, "exc" => 0,
        "raw" => "",
    ]);
    if ($blob === null || $blob === "" || !is_string($blob)) {
        return $slots;
    }
    $len = strlen($blob);
    // Each item slot occupies 12 bytes; equipped block = 144 bytes minimum.
    $slot_size = 12;
    for ($i = 0; $i < 12; $i++) {
        $off = $i * $slot_size;
        if ($off + $slot_size > $len) break;
        $bytes = substr($blob, $off, $slot_size);
        // Empty slot = all 0xFF.
        $all_ff = true;
        for ($b = 0; $b < $slot_size; $b++) {
            if (ord($bytes[$b]) !== 0xFF) { $all_ff = false; break; }
        }
        if ($all_ff) continue;

        $b0 = ord($bytes[0]);             // item code (low byte)
        $b1 = ord($bytes[1]);             // level / luck / skill / option
        $b3 = ord($bytes[3]);             // excellent bitmask (low bits)
        $b9 = ord($bytes[9]);             // group (high nibble) + code high bit (bit 7)

        $group = ($b9 >> 4) & 0x0F;
        $code  = $b0 | ((($b9 >> 7) & 0x01) << 8); // 9-bit item code
        $level = ($b1 >> 3) & 0x0F;
        $skill = (bool)($b1 & 0x80);
        $luck  = (bool)($b1 & 0x04);
        $opt   = $b1 & 0x03;              // ×4 = visible +N option

        $slots[$i] = [
            "empty" => false,
            "group" => $group,
            "code"  => $code,
            "level" => $level,
            "skill" => $skill,
            "luck"  => $luck,
            "opt"   => $opt,
            "exc"   => $b3,
            "raw"   => strtoupper(bin2hex($bytes)),
        ];
    }
    return $slots;
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

/**
 * Probe a TCP host:port to see if the game server is reachable.
 *
 * Cached per request and per (ip,port,timeout) tuple so calling it from
 * multiple widgets does not multiply the latency. Returns true on a
 * successful connection, false on failure or invalid input.
 */
function server_status_check($ip, $port, $timeout = 2)
{
    static $memo = [];
    $ip      = trim((string)$ip);
    $port    = (int)$port;
    $timeout = max(1, (int)$timeout);
    if ($ip === "" || $port < 1 || $port > 65535) return false;

    $key = $ip . ":" . $port . ":" . $timeout;
    if (isset($memo[$key])) return $memo[$key];

    $errno = 0; $errstr = "";
    $sock = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    if ($sock) {
        @fclose($sock);
        return $memo[$key] = true;
    }
    return $memo[$key] = false;
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
