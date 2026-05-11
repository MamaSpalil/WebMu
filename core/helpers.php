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
 * Names of the 12 equipped inventory slots in Season 3 storage order.
 * Index → slot label as stored in Character.Inventory.
 */
function mu_equip_slots()
{
    return [
        0  => "Right Hand",
        1  => "Left Hand",
        2  => "Helm",
        3  => "Armor",
        4  => "Pants",
        5  => "Gloves",
        6  => "Boots",
        7  => "Wings",
        8  => "Pet",
        9  => "Pendant",
        10 => "Ring 1",
        11 => "Ring 2",
    ];
}

/** Equipment slots in the visual order used by the in-game inventory. */
function mu_equipment_layout()
{
    return [
        ["slot" => 9,  "label" => "Pendant"],
        ["slot" => 2,  "label" => "Helm"],
        ["slot" => 7,  "label" => "Wings"],
        ["slot" => 0,  "label" => "Right Hand"],
        ["slot" => 3,  "label" => "Armor"],
        ["slot" => 1,  "label" => "Left Hand"],
        ["slot" => 5,  "label" => "Gloves"],
        ["slot" => 4,  "label" => "Pants"],
        ["slot" => 6,  "label" => "Boots"],
        ["slot" => 10, "label" => "Ring 1"],
        ["slot" => 8,  "label" => "Pet"],
        ["slot" => 11, "label" => "Ring 2"],
    ];
}

function mu_item_catalog()
{
    static $catalog = null;
    if ($catalog !== null) return $catalog;

    $sets = [
        7  => "Helm",
        8  => "Armor",
        9  => "Pants",
        10 => "Gloves",
        11 => "Boots",
    ];
    $set_names = [
        0 => "Leather", 1 => "Bronze", 2 => "Scale", 3 => "Brass",
        4 => "Plate", 5 => "Dragon", 6 => "Legendary", 7 => "Guardian",
        8 => "Adamantine", 9 => "Dark Phoenix", 10 => "Great Dragon",
        11 => "Dark Soul", 12 => "Hurricane", 13 => "Red Spirit",
        14 => "Light Plate", 15 => "Sacred Fire", 16 => "Storm Crow",
        17 => "Thunder Hawk", 18 => "Volcano", 19 => "Ashcrow",
        20 => "Eclipse", 21 => "Iris", 22 => "Valiant", 23 => "Black Dragon",
        24 => "Dark Steel", 25 => "Glorious", 26 => "Dark Master",
        27 => "Great Lord", 28 => "Divine", 29 => "Red Wing",
        30 => "Ancient", 31 => "Black Rose",
    ];

    $catalog = [
        0 => [
            0 => "Kris", 1 => "Short Sword", 2 => "Rapier", 3 => "Katache",
            4 => "Sword of Assassin", 5 => "Blade", 6 => "Gladius",
            7 => "Falchion", 8 => "Serpent Sword", 9 => "Sword of Salamander",
            10 => "Light Saber", 11 => "Legendary Sword", 12 => "Heliacal Sword",
            13 => "Double Blade", 14 => "Lightning Sword", 15 => "Giant Sword",
            16 => "Sword of Destruction", 17 => "Dark Breaker", 18 => "Thunder Blade",
            19 => "Dragon Slayer", 20 => "Sword of Archangel", 21 => "Knight Blade",
            22 => "Dark Reign Blade", 23 => "Flamberge", 24 => "Daybreak",
            25 => "Sword Dancer", 26 => "Bloodangel Blade",
        ],
        1 => [
            0 => "Small Axe", 1 => "Hand Axe", 2 => "Double Axe", 3 => "Tomahawk",
            4 => "Elven Axe", 5 => "Battle Axe", 6 => "Nikea Axe", 7 => "Larkan Axe",
            8 => "Crescent Axe",
        ],
        2 => [
            0 => "Mace", 1 => "Morning Star", 2 => "Flail", 3 => "Great Hammer",
            4 => "Crystal Morning Star", 5 => "Crystal Sword", 6 => "Chaos Dragon Axe",
            7 => "Elemental Mace", 8 => "Battle Scepter", 9 => "Master Scepter",
            10 => "Great Scepter", 11 => "Lord Scepter", 12 => "Great Lord Scepter",
            13 => "Soleil Scepter",
        ],
        3 => [
            0 => "Light Spear", 1 => "Spear", 2 => "Dragon Lance", 3 => "Giant Trident",
            4 => "Serpent Spear", 5 => "Double Poleaxe", 6 => "Halberd", 7 => "Berdysh",
            8 => "Great Scythe", 9 => "Bill of Balrog", 10 => "Dragon Spear",
        ],
        4 => [
            0 => "Short Bow", 1 => "Bow", 2 => "Elven Bow", 3 => "Battle Bow",
            4 => "Tiger Bow", 5 => "Silver Bow", 6 => "Chaos Nature Bow",
            7 => "Bolt", 8 => "Crossbow", 9 => "Golden Crossbow", 10 => "Arquebus",
            11 => "Light Crossbow", 12 => "Serpent Crossbow", 13 => "Bluewing Crossbow",
            14 => "Aquagold Crossbow", 15 => "Arrow", 16 => "Saint Crossbow",
            17 => "Celestial Bow", 18 => "Divine Crossbow of Archangel",
            19 => "Great Reign Crossbow", 20 => "Arrow Viper Bow", 21 => "Sylphid Ray Bow",
        ],
        5 => [
            0 => "Skull Staff", 1 => "Angelic Staff", 2 => "Serpent Staff",
            3 => "Thunder Staff", 4 => "Gorgon Staff", 5 => "Legendary Staff",
            6 => "Staff of Resurrection", 7 => "Chaos Lightning Staff",
            8 => "Staff of Destruction", 9 => "Dragon Soul Staff",
            10 => "Divine Staff of Archangel", 11 => "Kundun Staff",
            12 => "Grand Viper Staff", 13 => "Platina Staff", 14 => "Mystery Staff",
            15 => "Violent Wind Staff", 16 => "Red Wing Staff", 17 => "Ancient Staff",
        ],
        6 => [
            0 => "Small Shield", 1 => "Horn Shield", 2 => "Kite Shield",
            3 => "Elven Shield", 4 => "Buckler", 5 => "Dragon Slayer Shield",
            6 => "Skull Shield", 7 => "Spiked Shield", 8 => "Tower Shield",
            9 => "Plate Shield", 10 => "Large Round Shield", 11 => "Serpent Shield",
            12 => "Bronze Shield", 13 => "Dragon Shield", 14 => "Legendary Shield",
            15 => "Grand Soul Shield", 16 => "Elemental Shield", 17 => "Battle Shield",
            18 => "Spiked Shield of Honor", 19 => "Crimson Glory Shield",
            20 => "Salamander Shield", 21 => "Frost Barrier Shield",
            22 => "Guardian Shield", 23 => "Cross Shield",
        ],
        12 => [
            0 => "Wings of Elf", 1 => "Wings of Heaven", 2 => "Wings of Satan",
            3 => "Wings of Spirits", 4 => "Wings of Soul", 5 => "Wings of Dragon",
            6 => "Wings of Darkness", 7 => "Orb of Twisting Slash",
            8 => "Orb of Healing", 9 => "Orb of Greater Defense",
            10 => "Orb of Greater Damage", 11 => "Orb of Summoning",
            12 => "Orb of Rageful Blow", 13 => "Orb of Impale",
            14 => "Orb of Greater Fortitude", 15 => "Orb of Fire Slash",
            16 => "Scroll of Fire Burst", 17 => "Scroll of Summon",
            18 => "Scroll of Critical Damage", 19 => "Scroll of Electric Spark",
            20 => "Scroll of Force", 21 => "Scroll of Fire Scream",
            22 => "Scroll of Birds", 23 => "Scroll of Chain Lightning",
            24 => "Scroll of Sleep", 25 => "Scroll of Drain Life",
            26 => "Scroll of Lightning Shock", 30 => "Cape of Lord",
            31 => "Wings of Storm", 32 => "Wings of Eternal",
            33 => "Wings of Illusion", 34 => "Wings of Ruin",
            35 => "Cape of Emperor",
        ],
        13 => [
            0 => "Guardian Angel", 1 => "Satan", 2 => "Horn of Uniria",
            3 => "DinoRant", 4 => "Dark Horse", 5 => "Dark Raven",
            8 => "Ring of Ice", 9 => "Ring of Poison", 10 => "Ring of Transformation",
            12 => "Pendant of Lightning", 13 => "Pendant of Fire",
            14 => "Pendant of Ice", 16 => "Ring of Earth", 20 => "Pendant of Wind",
            21 => "Pendant of Water", 22 => "Ring of Magic", 23 => "Ring of Fire",
            24 => "Ring of Wind", 25 => "Ring of Magic", 26 => "Pendant of Ability",
            27 => "Pendant of Water", 28 => "Pendant of Earth",
            29 => "Pendant of Wind", 30 => "Pendant of Magic",
        ],
    ];

    foreach ($sets as $group => $part) {
        foreach ($set_names as $code => $name) {
            $catalog[$group][$code] = $name . " " . $part;
        }
    }
    return $catalog;
}

function mu_item_name($group, $code)
{
    $catalog = mu_item_catalog();
    return $catalog[(int)$group][(int)$code] ?? "Unknown";
}

function mu_item_image($group, $code, $level = 0)
{
    $group = (int)$group;
    $code  = (int)$code;
    $level = max(0, (int)$level);
    // The bundled item sprites mostly use level bucket 0 or 10 for glowing gear.
    $fallback_level = $level >= 10 ? 10 : 0;
    $dir = dirname(__DIR__) . "/assets/images/items";
    // Keep the client filename convention: <ItemType><ItemIndex><level>.gif
    // (for example item 12/1 at +0 uses 1210.gif: Wings of Heaven).
    $candidates = array_unique([
        $group . $code . $level . ".gif",
        $group . $code . $fallback_level . ".gif",
        $group . $code . "0.gif",
    ]);
    foreach ($candidates as $file) {
        if (is_file($dir . "/" . $file)) return $file;
    }
    return "";
}

function mu_slot_expected_groups($slot)
{
    $map = [
        0 => [0, 1, 2, 3, 4, 5],
        1 => [0, 1, 2, 3, 4, 5, 6],
        2 => [7],
        3 => [8],
        4 => [9],
        5 => [10],
        6 => [11],
        7 => [12],
        8 => [13],
        9 => [13],
        10 => [13],
        11 => [13],
    ];
    return $map[(int)$slot] ?? [];
}

function mu_decode_item_candidates($bytes)
{
    $b0 = ord($bytes[0]);
    $b9 = ord($bytes[9]);
    $old_group = ($b0 >> 5) + (($b9 & 0x80) ? 8 : 0);
    $old_code  = $b0 & 0x1F;
    return [
        ["group" => $old_group, "code" => $old_code],
        // Alternate emulator layout: byte 9 stores the group nibble and code high bit.
        ["group" => ($b9 >> 4) & 0x0F, "code" => $b0 | ((($b9 >> 7) & 0x01) << 8)],
        ["group" => ($b9 >> 4) & 0x0F, "code" => $b0 & 0x1F],
    ];
}

function mu_choose_item_identity($bytes, $slot, $level)
{
    $expected = mu_slot_expected_groups($slot);
    $best = null;
    $best_score = -1;
    foreach (mu_decode_item_candidates($bytes) as $candidate) {
        $group = (int)$candidate["group"];
        $code  = (int)$candidate["code"];
        $name  = mu_item_name($group, $code);
        $image = mu_item_image($group, $code, $level);
        $score = 0;
        if ($expected && in_array($group, $expected, true)) $score += 4;
        if ($name !== "Unknown") $score += 2;
        if ($image !== "") $score += 1;
        if ($score > $best_score) {
            $best_score = $score;
            $best = ["group" => $group, "code" => $code, "name" => $name, "image" => $image];
        }
    }
    return $best ?: ["group" => 0, "code" => 0, "name" => "Unknown", "image" => ""];
}

function mu_inventory_bytes($blob)
{
    if ($blob === null || $blob === "" || !is_string($blob)) return "";
    $trimmed = trim($blob);
    if (stripos($trimmed, "0x") === 0) $trimmed = substr($trimmed, 2);
    $compact = preg_replace('/\s+/', '', $trimmed);
    $looks_formatted_hex = strlen($compact) !== strlen($blob);
    if ($compact !== "" && (strlen($compact) % 2) === 0 && ctype_xdigit($compact)
        && strlen($compact) >= 24 && $looks_formatted_hex) {
        $packed = @hex2bin($compact);
        if ($packed !== false) return $packed;
    }
    // 288 = 12 equipped slots × 12 bytes per item × 2 hex characters per byte.
    if ($compact !== "" && (strlen($compact) % 2) === 0 && ctype_xdigit($compact)
        && strlen($compact) >= 288) {
        $packed = @hex2bin($compact);
        if ($packed !== false) return $packed;
    }
    return $blob;
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
 *   - opt:     additional option amount (0-3, ×4 = +N)
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
        "raw" => "", "name" => "Empty", "image" => "",
    ]);
    $blob = mu_inventory_bytes($blob);
    if ($blob === "") {
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

        $b1 = ord($bytes[1]);             // level / luck / skill / option
        $b7 = ord($bytes[7]);             // excellent option bitmask in common MU item codes

        $level = ($b1 >> 3) & 0x0F;
        $skill = (bool)($b1 & 0x80);
        $luck  = (bool)($b1 & 0x04);
        $opt   = $b1 & 0x03;              // ×4 = visible +N option
        $identity = mu_choose_item_identity($bytes, $i, $level);

        $slots[$i] = [
            "empty" => false,
            "group" => $identity["group"],
            "code"  => $identity["code"],
            "name"  => $identity["name"],
            "image" => $identity["image"],
            "level" => $level,
            "skill" => $skill,
            "luck"  => $luck,
            "opt"   => $opt,
            "exc"   => $b7,
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
