<?php
// =====================================================================
//  Helpers: validators, formatters, MuOnline class lookup, file cache.
// =====================================================================
if (!defined("insite")) die("no access");
if (!defined("MU_EQUIPPED_SLOTS")) define("MU_EQUIPPED_SLOTS", 12);
// Per-slot record size for the Character.Inventory / warehouse.Items
// blobs on this server: 16 bytes per slot (32 hex chars), matching the
// reference parser in modules/webv.php — that is the canonical layout
// for our game DB (item code in byte 0 as a full 8-bit value, item
// group in byte 9's high nibble, 380 marker in byte 9's low nibble,
// excellent options in byte 7, sockets in bytes 11..15).
if (!defined("MU_ITEM_BYTES")) define("MU_ITEM_BYTES", 16);
if (!defined("MU_ITEM_GLOW_LEVEL_THRESHOLD")) define("MU_ITEM_GLOW_LEVEL_THRESHOLD", 10);
if (!defined("MU_HEX_FORMATTED_MIN_ITEM_CHARS")) define("MU_HEX_FORMATTED_MIN_ITEM_CHARS", MU_ITEM_BYTES * 2);
if (!defined("MU_EXCELLENT_OPTION_MASK")) define("MU_EXCELLENT_OPTION_MASK", 0x3F); // bits 0..5 = six excellent options
// Low nibble marker of byte 9 that the reference webv.php treats as the
// "Item Level 380" flag — group is still the high nibble of byte 9 in
// that case (because (b9 - 8) / 16 == b9 >> 4 when the low nibble is 8).
if (!defined("MU_ITEM_380_MARKER")) define("MU_ITEM_380_MARKER", 0x08);

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

    // Mirrors the server's item-name table; spellings are kept as provided.
    $catalog = [
        0 => [
            0 => "Kris", 1 => "Short Sword", 2 => "Rapier", 3 => "Katana",
            4 => "Sword of Assassin", 5 => "Blade", 6 => "Gladius",
            7 => "Falchion", 8 => "Serpent Sword", 9 => "Sword of Salamander",
            10 => "Light Saber", 11 => "Legendary Sword", 12 => "Heliacal Sword",
            13 => "Double Blade", 14 => "Lightning Sword", 15 => "Giant Sword",
            16 => "Sword of Destruction", 17 => "Dark Breaker", 18 => "Thunder Blade",
            19 => "Divine Sword of Archangel", 20 => "Knight Blade", 21 => "Dark Reign Blade",
            22 => "Bone Blade", 23 => "Explosion Blade", 24 => "Daybreak",
            25 => "Sword Dancer", 26 => "Archon Guardian Blade", 31 => "Rune Blade",
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
            13 => "Divine Scepter of Archangel", 14 => "Solay Scepter", 15 => "Shining Scepter",
        ],
        3 => [
            0 => "Light Spear", 1 => "Spear", 2 => "Dragon Lance", 3 => "Giant Trident",
            4 => "Serpent Spear", 5 => "Double Poleaxe", 6 => "Halberd", 7 => "Berdysh",
            8 => "Great Scythe", 9 => "Bill of Balrog", 10 => "Dragon Spear",
        ],
        4 => [
            0 => "Short Bow", 1 => "Bow", 2 => "Elven Bow", 3 => "Battle Bow",
            4 => "Tiger Bow", 5 => "Silver Bow", 6 => "Chaos Nature Bow",
            7 => "Bolts", 8 => "Crossbow", 9 => "Golden Crossbow", 10 => "Arquebus",
            11 => "Light Crossbow", 12 => "Serpent Crossbow", 13 => "Bluewing Crossbow",
            14 => "Aquagold Crossbow", 15 => "Arrows", 16 => "Saint Crossbow",
            17 => "Celestial Bow", 18 => "Divine Crossbow of Archangel",
            19 => "Great Reign Crossbow", 20 => "Arrow Viper Bow", 21 => "Sylph Wind Bow",
            22 => "Albatross Bow",
        ],
        5 => [
            0 => "Skull Staff", 1 => "Angelic Staff", 2 => "Serpent Staff",
            3 => "Thunder Staff", 4 => "Gorgon Staff", 5 => "Legendary Staff",
            6 => "Staff of Resurrection", 7 => "Chaos Lightning Staff",
            8 => "Staff of Destruction", 9 => "Dragon Soul Staff",
            10 => "Divine Staff of Archangel", 11 => "Staff of Kundun",
            12 => "Grand Viper Staff", 13 => "Platina Wing Staff",
        ],
        6 => [
            0 => "Small Shield", 1 => "Horn Shield", 2 => "Kite Shield",
            3 => "Elven Shield", 4 => "Buckler", 5 => "Dragon Slayer Shield",
            6 => "Skull Shield", 7 => "Spiked Shield", 8 => "Tower Shield",
            9 => "Plate Shield", 10 => "Large Round Shield", 11 => "Serpent Shield",
            12 => "Bronze Shield", 13 => "Chaos Dragon Shield", 14 => "Legendary Shield",
            15 => "Grand Soul Shield", 16 => "Elemental Shield",
        ],
        12 => [
            0 => "Wings of Elf", 1 => "Wings of Heaven", 2 => "Wings of Satan",
            3 => "Wings of Spirits", 4 => "Wings of Soul", 5 => "Wings of Dragon",
            6 => "Wings of Darkness", 7 => "Orb of Twisting Slash", 8 => "Healing Orb",
            9 => "Orb of Greater Defense", 10 => "Orb of Greater Damage",
            11 => "Orb of Summoning", 12 => "Orb of Rageful Blow", 13 => "Orb of Impale",
            14 => "Orb of Greater Fortitude", 15 => "Jewel of Chaos", 16 => "Orb of Fire Slash",
            17 => "Orb of Penetration", 18 => "Orb of Ice Arrow", 19 => "Orb of Death Stab",
            21 => "Scroll of FireBurst", 22 => "Scroll of Summon",
            23 => "Scroll of Critical Damage", 24 => "Scroll of Electric Spark",
            26 => "Gem of Secret", 30 => "Bundled Jewel of Bless",
            31 => "Bundled Jewel of Soul", 32 => "Chaos Castle Box", 33 => "Illusion Temple Box",
            34 => "Blood Castle Box", 35 => "Scroll of Fire Scream", 36 => "Wings of Storm",
            37 => "Wings of Space-Time", 38 => "Wings of Illusion", 39 => "Wings of Doom",
            40 => "Mantle of Monarch", 41 => "Wings of Angel", 42 => "Wings of Power",
            43 => "Wings of Butterfly", 44 => "Wings of Dream", 45 => "Mantle of Darkness",
        ],
        13 => [
            0 => "Guardian Angel", 1 => "Imp", 2 => "Horn of Uniria", 3 => "Horn of Dinorant",
            4 => "Dark Horse", 5 => "Dark Raven", 7 => "Contract (Summon)",
            8 => "Ring of Ice", 9 => "Ring of Poison", 10 => "Transformation Ring",
            11 => "Order (Guardian/Life Stone)", 12 => "Pendant of Lightning",
            13 => "Pendant of Fire", 14 => "Loch's Feather", 15 => "Fruit",
            16 => "Scroll of Archangel", 17 => "Blood Bone", 18 => "Cloak of Invisibility",
            19 => "Divine Weapon of Archangel", 20 => "Wizards Ring", 21 => "Ring of Fire",
            22 => "Ring of Earth", 23 => "Ring of Wind", 24 => "Ring of Magic",
            25 => "Pendant of Ice", 26 => "Pendant of Wind", 27 => "Pendant of Water",
            28 => "Pendant of Ability", 29 => "Armor of Guardsman", 30 => "Cape of Lord",
            31 => "Spirit", 32 => "Splinter of Armor", 33 => "Bless of Guardian",
            34 => "Claw of Beast", 35 => "Fragment of Horn", 36 => "Broken Horn",
            37 => "Horn of Fenrir", 38 => "Moonstone Ring", 39 => "Skeleton Warrior Ring",
            40 => "Jack O'Lantern Ring", 41 => "Santa Girl Ring", 42 => "GameMaster Ring",
            43 => "Seal of Ascension", 44 => "Seal of Wealth", 45 => "Seal of Sustenance",
            46 => "Blessed Devil Invasion", 47 => "Blessed invisibility Cloak",
            48 => "Blessed Lost Map", 49 => "Old Scroll", 50 => "Illusion Sorcerer Covenant",
            51 => "Scroll of Blood", 52 => "Flame of Condor", 53 => "Feather of Condor",
            54 => "Reset Fruit Strength", 55 => "Reset Fruit Agility", 56 => "Reset Fruit Vitality",
            57 => "Reset Fruit Energy", 58 => "Reset Fruit Command", 59 => "Seal of Mobility",
            61 => "illiusion temple ticket",
        ],
        14 => [
            0 => "Apple", 1 => "Small Healing Potion", 2 => "Medium Healing Potion",
            3 => "Large Healing Potion", 4 => "Small Mana Potion", 5 => "Medium Mana Potion",
            6 => "Large Mana Potion", 7 => "Siege Potion", 8 => "Antidote", 9 => "Ale",
            10 => "Town Portal Scroll", 11 => "Box of Luck", 12 => "Heart",
            13 => "Jewel of Bless", 14 => "Jewel of Soul", 15 => "Zen",
            16 => "Jewel of Life", 17 => "Devil's Eye", 18 => "Devil's Key",
            19 => "Devil's Invitation", 20 => "Remedy of Love", 21 => "Rena",
            22 => "Jewel of Creation", 23 => "Scroll of Emperor", 24 => "Broken Sword",
            25 => "Tear of Elf", 26 => "Soul Shard of Wizard", 28 => "Lost Map",
            29 => "Symbol of Kundun", 31 => "Jewel of Guardian", 32 => "Pink Chocolate Box",
            33 => "Red Chocolate Box", 34 => "Blue Chocolate Box", 35 => "Small SD Potion",
            36 => "Medium SD Potion", 37 => "Large SD Potion", 38 => "Small AG Potion",
            39 => "Medium AG Potion", 40 => "Large AG Potion", 41 => "Gemstone",
            42 => "Jewel of Harmony", 43 => "Lower Refining Stone", 44 => "Higher Refining Stone",
            45 => "Halloween Pumpkin", 46 => "Jack O'Lantern Bless Scroll",
            47 => "Jack O'Lantern Rage Scroll", 48 => "Jack O'Lantern Scream Scroll",
            49 => "Jack O'Lantern Food Scroll", 50 => "Jack O'Lantern Drink Scroll",
            51 => "Star", 52 => "GameMaster Box", 53 => "Symbol of luck",
            54 => "Chaos Card", 55 => "Green Chaos Box", 56 => "Pink Chaos Box",
            57 => "Purple Chaos Box", 58 => "Rare item Ticket 1", 59 => "Rare item Ticket 2",
            60 => "Rare item Ticket 3", 61 => "Rare item Ticket 4", 62 => "Rare item Ticket 5",
            63 => "Firecracker", 64 => "Cursed Castle Water", 65 => "Flame of Death Beam Knight",
            66 => "Horn of Hell Maine", 67 => "Feather of Phoenix of Darkness",
            70 => "Enhanced Healing Potion", 71 => "Enhanced Mana Potion", 72 => "Scroll of Quickness",
            73 => "Scroll of Shield", 74 => "Scroll of Might", 75 => "Scroll of Empower",
            76 => "Scroll of Bless the Body", 77 => "Scroll of Bless the Soul",
            78 => "Potion: increasing Strength", 79 => "Potion: increasing Agility",
            80 => "Potion: increasing Vitality", 81 => "Potion: increasing Energy",
            82 => "Potion: increasing Command", 83 => "Rare Item Ticket 6",
            84 => "Moss The Merchant Item 1", 85 => "Moss The Merchant Item 2",
            86 => "Moss The Merchant Item 3", 87 => "Moss The Merchant Item 4",
            88 => "Moss The Merchant Item 5", 89 => "Scorpion Mobility",
            90 => "Jewel Of Exellent", 91 => "Jewel Of Wings", 92 => "Jewel Of Luck",
            93 => "Jewel Of Skill", 95 => "Jewel Of Evalution", 96 => "Jewel Of Ancent",
            97 => "Jewel Of Option", 98 => "Jewel Of PvP", 99 => "Jewel Of Mistic",
            100 => "Jewel Of Level", 102 => "Coin 100kk", 103 => "Coin 500kk",
            104 => "Coin 1kkk", 105 => "BC Box", 106 => "CC Box", 107 => "DS Box",
            147 => "Test", 150 => "Amulet", 151 => "Amulet", 152 => "Amulet",
            153 => "Amulet", 154 => "Amulet", 155 => "Amulet", 156 => "Amulet",
        ],
        15 => [
            0 => "Scroll of Poison", 1 => "Scroll of Meteorite", 2 => "Scroll of Lighting",
            3 => "Scroll of Fire Ball", 4 => "Scroll of Flame", 5 => "Scroll of Teleport",
            6 => "Scroll of Ice", 7 => "Scroll of Twister", 8 => "Scroll of Evil Spirit",
            9 => "Scroll of Hellfire", 10 => "Scroll of Power Wave", 11 => "Scroll of Aqua Beam",
            12 => "Scroll of Cometfall", 13 => "Scroll of Inferno", 14 => "Scroll of Teleport",
            15 => "Scroll of Soul Barrier", 16 => "Scroll of Decay", 17 => "Scroll of Ice Storm",
            18 => "Scroll of Nova",
        ],
    ];

    $armor_parts = [
        7 => "Helm", 8 => "Armor", 9 => "Pants", 10 => "Gloves", 11 => "Boots",
    ];
    $armor_sets = [
        7 => [
            0 => "Bronze", 1 => "Dragon", 2 => "Pad", 3 => "Legendary", 4 => "Bone",
            5 => "Leather", 6 => "Scale", 7 => "Sphinx", 8 => "Brass", 9 => "Plate",
            10 => "Vine", 11 => "Silk", 12 => "Wind", 13 => "Spirit", 14 => "Guardian",
            16 => "Black Dragon", 17 => "Dark Phoenix", 18 => "Grand Soul", 19 => "Divine",
            21 => "Great Dragon", 22 => "Dark Soul", 24 => "Red Spirit", 25 => "Light Plate",
            26 => "Adamantine", 27 => "Dark Steel", 28 => "Dark Master", 29 => "Dragon Knight",
            30 => "Venom Mist", 31 => "Sylphid Ray", 33 => "Sunlight", 34 => "Ashcrow",
            35 => "Eclipse", 36 => "Iris", 38 => "Glorious", 39 => "Archon Guardian",
        ],
        8 => [
            0 => "Bronze", 1 => "Dragon", 2 => "Pad", 3 => "Legendary", 4 => "Bone",
            5 => "Leather", 6 => "Scale", 7 => "Sphinx", 8 => "Brass", 9 => "Plate",
            10 => "Vine", 11 => "Silk", 12 => "Wind", 13 => "Spirit", 14 => "Guardian",
            15 => "Storm Crow", 16 => "Black Dragon", 17 => "Dark Phoenix", 18 => "Grand Soul",
            19 => "Divine", 20 => "Thunder Hawk", 21 => "Great Dragon", 22 => "Dark Soul",
            23 => "Hurricane", 24 => "Red Spirit", 25 => "Light Plate", 26 => "Adamantine",
            27 => "Dark Steel", 28 => "Dark Master", 29 => "Dragon Knight", 30 => "Venom Mist",
            31 => "Sylphid Ray", 32 => "Volcano", 33 => "Sunlight", 34 => "Ashcrow",
            35 => "Eclipse", 36 => "Iris", 37 => "Valiant", 38 => "Glorious", 39 => "Archon Guardian",
        ],
    ];
    $armor_sets[9] = $armor_sets[8];
    $armor_sets[10] = $armor_sets[8];
    $armor_sets[11] = $armor_sets[8];
    foreach ($armor_sets as $group => $sets) {
        foreach ($sets as $code => $name) {
            $catalog[$group][$code] = $name . " " . $armor_parts[$group];
        }
    }

    return $catalog;
}

function mu_item_name($item_type, $item_index)
{
    $catalog = mu_item_catalog();
    return $catalog[(int)$item_type][(int)$item_index] ?? "Unknown";
}

function mu_item_image($item_type, $item_index, $level = 0)
{
    $item_type  = (int)$item_type;
    $item_index = (int)$item_index;
    $level = max(0, (int)$level);
    // The bundled item sprites mostly use +0 sprites and a +10 glow bucket for levels 10-15.
    $fallback_level = $level >= MU_ITEM_GLOW_LEVEL_THRESHOLD ? MU_ITEM_GLOW_LEVEL_THRESHOLD : 0;
    $dir = dirname(__DIR__) . "/assets/images/items";
    // Keep the client filename convention: <ItemType><ItemIndex><level>.gif
    // (for example item 12/1 at +0 uses 1210.gif: Wings of Heaven).
    $candidates = array_unique([
        $item_type . $item_index . $level . ".gif",
        $item_type . $item_index . $fallback_level . ".gif",
        $item_type . $item_index . "0.gif",
    ]);
    foreach ($candidates as $file) {
        if (is_file($dir . "/" . $file)) return $file;
    }
    return "";
}

/**
 * Decode a single equipped/warehouse slot record from the Inventory blob,
 * matching the layout used by the reference parser in modules/webv.php
 * (16 bytes / 32 hex chars per slot):
 *
 *   byte 0:  ItemID — full 8-bit item code (0..255) within its group.
 *   byte 1:  bit 7 = Skill, bits 6..3 = Level (0..15),
 *            bit 2 = Luck, bits 1..0 = Option (0..3, ×4 = +N)
 *   byte 2:  Durability
 *   byte 3..6: Serial / extra flags (per-build, opaque to the website)
 *   byte 7:  Excellent options bitmask (bits 0..5; bits 6..7 reserved
 *            for set/ancient flags in some builds)
 *   byte 8:  Set / ancient option (low nibble = ancient marker)
 *   byte 9:  high nibble = ItemType / group (0..15);
 *            low nibble = 380-item marker (0x08 for "Item Level 380")
 *   byte 10: high nibble = harmony type, low nibble = harmony level
 *   byte 11..15: socket bytes (5 sockets, FF or 0x00 = empty)
 *
 * Empty slots are encoded as 16 × 0xFF and must be filtered by the caller.
 *
 * @param string $bytes Raw 16-byte slot record.
 * @return array{group:int,code:int}
 */
function mu_decode_item_identity($bytes)
{
    // Match webv.php exactly: code is the full byte 0 (no high-bit
    // extension — the +32 ItemIndex extension flag from S3 Ep.1 12-byte
    // blobs does not apply to this server's 16-byte slot layout) and
    // group is the high nibble of byte 9 (the low nibble carries the
    // 380-item marker, which we deliberately ignore for identity).
    $b0 = ord($bytes[0]);
    $b9 = ord($bytes[9]);
    $group = ($b9 >> 4) & 0x0F;
    $code  = $b0;
    return ["group" => $group, "code" => $code];
}

/**
 * Strict slot-type whitelist for the 12 equipped slots of a MuOnline
 * Season 2 / Season 3 Episode 1 (1.02.19) Character.Inventory blob.
 *
 * Returns true only if the (group, code) decoded from a slot is a legal
 * item for that slot under stock S2/S3 Ep.1 rules:
 *
 *   slot 0  Right Hand → weapons only           (groups 0..5)
 *   slot 1  Left Hand  → weapons or shield      (groups 0..5 or 6)
 *   slot 2  Helm       → group 7
 *   slot 3  Armor      → group 8
 *   slot 4  Pants      → group 9
 *   slot 5  Gloves     → group 10
 *   slot 6  Boots      → group 11
 *   slot 7  Wings      → group 12, wing/cape codes only
 *   slot 8  Pet        → group 13, pet codes only
 *   slot 9  Pendant    → group 13, pendant codes only
 *   slot 10 Ring 1     → group 13, ring codes only
 *   slot 11 Ring 2     → group 13, ring codes only
 *
 * Group 13 mixes pets, pendants and rings, so the slot has to be matched
 * by item code as well — otherwise (e.g.) a Pendant of Fire would be
 * accepted in the Pet slot.
 */
function mu_slot_allows($slot, $group, $code)
{
    $slot  = (int)$slot;
    $group = (int)$group;
    $code  = (int)$code;

    // Group 13 sub-categories per stock S2/S3 Ep.1 item lists.
    static $g13_pets = [
        0,  // Guardian Angel
        1,  // Imp
        2,  // Horn of Uniria
        3,  // Horn of Dinorant
        4,  // Dark Horse
        5,  // Dark Raven
    ];
    static $g13_pendants = [
        12, // Pendant of Lightning
        13, // Pendant of Fire
        25, // Pendant of Ice
        26, // Pendant of Wind
        27, // Pendant of Water
        28, // Pendant of Ability
    ];
    static $g13_rings = [
        8,  // Ring of Ice
        9,  // Ring of Poison
        10, // Transformation Ring
        20, // Wizards Ring
        21, // Ring of Fire
        22, // Ring of Earth
        23, // Ring of Wind
        24, // Ring of Magic
        38, // Moonstone Ring
        39, // Skeleton Warrior Ring
        40, // Jack O'Lantern Ring
        41, // Santa Girl Ring
        42, // GameMaster Ring
    ];
    // Wings/Capes/Mantles legal in the Wings slot. The reference parser
    // (modules/webv.php) treats codes 0..6 as the classic 1st/2nd-class
    // wings and 36..45 as the 3rd-class wings/capes (Storm, Space-Time,
    // Illusion, Doom, Mantle of Monarch, Wings of Angel/Power/Butterfly/
    // Dream, Mantle of Darkness). Anything else in group 12 is an
    // orb / scroll / box that must never appear in the Wings slot.
    static $g12_wings = [
        0, // Wings of Elf
        1, // Wings of Heaven
        2, // Wings of Satan
        3, // Wings of Spirits
        4, // Wings of Soul
        5, // Wings of Dragon
        6, // Wings of Darkness
        36, // Wings of Storm
        37, // Wings of Space-Time
        38, // Wings of Illusion
        39, // Wings of Doom
        40, // Mantle of Monarch
        41, // Wings of Angel
        42, // Wings of Power
        43, // Wings of Butterfly
        44, // Wings of Dream
        45, // Mantle of Darkness
    ];

    switch ($slot) {
        case 0: // Right Hand: weapons only
            return $group >= 0 && $group <= 5;
        case 1: // Left Hand: weapons or shield
            return ($group >= 0 && $group <= 5) || $group === 6;
        case 2: return $group === 7;   // Helm
        case 3: return $group === 8;   // Armor
        case 4: return $group === 9;   // Pants
        case 5: return $group === 10;  // Gloves
        case 6: return $group === 11;  // Boots
        case 7: return $group === 12 && in_array($code, $g12_wings, true);
        case 8: return $group === 13 && in_array($code, $g13_pets, true);
        case 9: return $group === 13 && in_array($code, $g13_pendants, true);
        case 10:
        case 11:
            return $group === 13 && in_array($code, $g13_rings, true);
    }
    return false;
}

/**
 * Per-attribute capability table for a decoded item under stock MuOnline
 * Season 2 / Season 3 Episode 1 (1.02.19) rules. Used to suppress badges
 * that decoded bytes can never legally carry for that item class:
 *
 *   level  — only weapons (groups 0..5), shields (group 6), armor parts
 *            (groups 7..11) and wings/capes (group 12, codes 0..6) can
 *            be upgraded with Jewel of Bless / Soul.
 *   skill  — only weapons and shields can hold a Skill option.
 *   luck   — only weapons, shields, armor parts and wings/capes accept
 *            a Luck option (Jewel of Luck).
 *   exc    — Excellent options exist on weapons, shields, armor parts
 *            and wings/capes.
 *
 * Pets, pendants, rings and consumables (groups 13..15, plus the
 * orb / scroll / box codes of group 12) do not support any of these.
 */
function mu_item_supports($attr, $group, $code)
{
    $g = (int)$group;
    $c = (int)$code;

    $is_weapon  = ($g >= 0 && $g <= 5);
    $is_shield  = ($g === 6);
    $is_armor   = ($g >= 7 && $g <= 11);
    // Wings/Capes that can carry +N / Luck / Excellent badges: classic
    // wings (codes 0..6) and the 3rd-class wings/capes (codes 36..45).
    // Other group-12 codes are orbs/scrolls/boxes and must not show
    // weapon-style badges (see modules/webv.php for the full list).
    $is_wing    = ($g === 12 && (($c >= 0 && $c <= 6) || ($c >= 36 && $c <= 45)));

    switch ($attr) {
        case "level":
            return $is_weapon || $is_shield || $is_armor || $is_wing;
        case "skill":
            return $is_weapon || $is_shield;
        case "luck":
        case "exc":
            return $is_weapon || $is_shield || $is_armor || $is_wing;
    }
    return false;
}

/**
 * True iff (group, code) is present in the S2/S3 Ep.1 item catalog.
 * Used to drop bogus warehouse slots whose decoded identity points at
 * items that don't exist on this server version.
 */
function mu_item_exists($group, $code)
{
    $catalog = mu_item_catalog();
    return isset($catalog[(int)$group][(int)$code]);
}

/**
 * Return the inventory dimensions [width, height] of an item in cells,
 * matching the Item.bmd table the MuOnline Season 3 Ep.1 client uses
 * to lay items out in the warehouse / inventory grid.
 *
 * The data is sourced from the legacy `imgs/items.php` reference
 * (`$itembd[group][code][1]` was a 2-char "WH" string) plus the public
 * Item.bmd shipped with the S3 Ep.1 client. Items that don't appear in
 * the per-item override map fall back to a per-group default; truly
 * unknown items default to 1×1.
 *
 * @param int $group ItemType (0..15)
 * @param int $code  ItemIndex
 * @return array{0:int,1:int} [width, height]
 */
function mu_item_size($group, $code)
{
    static $table = null;
    if ($table === null) {
        // Per-group defaults (used when the (group, code) pair is not in
        // the override map below).
        $defaults = [
            0  => [1, 3], // Swords
            1  => [2, 3], // Axes
            2  => [1, 3], // Maces / Scepters
            3  => [1, 3], // Spears
            4  => [1, 4], // Bows / Crossbows
            5  => [1, 3], // Staves
            6  => [2, 2], // Shields
            7  => [2, 2], // Helms
            8  => [2, 3], // Armor
            9  => [2, 2], // Pants
            10 => [2, 2], // Gloves
            11 => [2, 2], // Boots
            12 => [2, 3], // Wings (defaults to wing size; orbs/scrolls overridden)
            13 => [1, 1], // Pets / pendants / rings / misc (overridden case-by-case)
            14 => [1, 1], // Potions / jewels
            15 => [1, 2], // Magic scrolls
        ];
        // Per-item overrides (only entries that differ from the default).
        $overrides = [
            // ----- Group 0: Swords ---------------------------------------
            0 => [
                0 => [1, 2],   // Kris
                1 => [1, 2],   // Short Sword
                2 => [1, 2],   // Rapier
                15 => [1, 4],  // Giant Sword
                16 => [1, 4],  // Sword of Destruction
                17 => [1, 4],  // Dark Breaker
                18 => [1, 4],  // Thunder Blade
                19 => [1, 4],  // Divine Sword of Archangel
                21 => [1, 4],  // Dark Reign Blade
                22 => [1, 4],  // Bone Blade
                23 => [1, 4],  // Explosion Blade
                24 => [1, 4],  // Daybreak
                26 => [1, 4],  // Archon Guardian Blade
            ],
            // ----- Group 1: Axes ----------------------------------------
            1 => [
                0 => [1, 2],   // Small Axe
                1 => [1, 2],   // Hand Axe
                8 => [2, 4],   // Crescent Axe
            ],
            // ----- Group 2: Maces / Scepters ----------------------------
            2 => [
                6 => [2, 3],   // Chaos Dragon Axe
                13 => [1, 4],  // Divine Scepter of Archangel
                15 => [1, 4],  // Shining Scepter
            ],
            // ----- Group 3: Spears --------------------------------------
            3 => [
                2 => [1, 4],   // Dragon Lance
                5 => [1, 4],   // Double Poleaxe
                6 => [1, 4],   // Halberd
                7 => [1, 4],   // Berdysh
                8 => [1, 4],   // Great Scythe
                9 => [1, 4],   // Bill of Balrog
                10 => [1, 4],  // Dragon Spear
            ],
            // ----- Group 4: Bows / Crossbows ----------------------------
            4 => [
                0 => [1, 3],   // Short Bow
                1 => [1, 3],   // Bow
                7 => [1, 2],   // Bolts
                8 => [1, 3],   // Crossbow
                9 => [1, 3],   // Golden Crossbow
                15 => [1, 2],  // Arrows
            ],
            // ----- Group 5: Staves --------------------------------------
            5 => [
                7 => [1, 4],   // Chaos Lightning Staff
                8 => [1, 4],   // Staff of Destruction
                10 => [1, 4],  // Divine Staff of Archangel
                12 => [1, 4],  // Grand Viper Staff
                13 => [1, 4],  // Platina Wing Staff
            ],
            // ----- Group 6: Shields -------------------------------------
            6 => [
                5 => [2, 3],   // Dragon Slayer Shield
                8 => [2, 3],   // Tower Shield
            ],
            // ----- Group 12: Wings / Orbs / Scrolls ---------------------
            12 => [
                7  => [1, 1],  // Orb of Twisting Slash
                8  => [1, 1],  // Healing Orb
                9  => [1, 1],  // Orb of Greater Defense
                10 => [1, 1],  // Orb of Greater Damage
                11 => [1, 1],  // Orb of Summoning
                12 => [1, 1],  // Orb of Rageful Blow
                13 => [1, 1],  // Orb of Impale
                14 => [1, 1],  // Orb of Greater Fortitude
                15 => [1, 1],  // Jewel of Chaos
                16 => [1, 1],  // Orb of Fire Slash
                17 => [1, 1],  // Orb of Penetration
                18 => [1, 1],  // Orb of Ice Arrow
                19 => [1, 1],  // Orb of Death Stab
                21 => [1, 2],  // Scroll of FireBurst
                22 => [1, 2],  // Scroll of Summon
                23 => [1, 2],  // Scroll of Critical Damage
                24 => [1, 2],  // Scroll of Electric Spark
                26 => [1, 1],  // Gem of Secret
                30 => [1, 1],  // Bundled Jewel of Bless
                31 => [1, 1],  // Bundled Jewel of Soul
                32 => [1, 1],  // Chaos Castle Box
                33 => [1, 1],  // Illusion Temple Box
                34 => [1, 1],  // Blood Castle Box
                35 => [1, 2],  // Scroll of Fire Scream
            ],
            // ----- Group 13: Pets / Pendants / Rings / Misc -------------
            13 => [
                0  => [1, 2],  // Guardian Angel
                1  => [1, 1],  // Imp
                2  => [1, 2],  // Horn of Uniria
                3  => [1, 2],  // Horn of Dinorant
                4  => [2, 2],  // Dark Horse
                5  => [1, 2],  // Dark Raven
                12 => [1, 2],  // Pendant of Lightning
                13 => [1, 2],  // Pendant of Fire
                25 => [1, 2],  // Pendant of Ice
                26 => [1, 2],  // Pendant of Wind
                27 => [1, 2],  // Pendant of Water
                28 => [1, 2],  // Pendant of Ability
                30 => [2, 3],  // Cape of Lord
                37 => [2, 2],  // Horn of Fenrir
            ],
        ];
        $table = ["__defaults" => $defaults, "__overrides" => $overrides];
    }

    $g = (int)$group;
    $c = (int)$code;
    if (isset($table["__overrides"][$g][$c])) {
        return $table["__overrides"][$g][$c];
    }
    if (isset($table["__defaults"][$g])) {
        return $table["__defaults"][$g];
    }
    return [1, 1];
}

/**
 * Clamp a decoded slot's optional badges (`level`, `skill`, `luck`, `exc`)
 * to what the S2/S3 Ep.1 item class can legally carry. Mutates the slot
 * array in place. Used by both the equipped and warehouse decoders to
 * avoid surfacing bogus byte-1 / byte-7 flags on jewels, potions,
 * scrolls, pets, pendants and rings.
 */
function mu_clamp_item_badges(array &$slot)
{
    $g = (int)($slot["group"] ?? 0);
    $c = (int)($slot["code"]  ?? 0);
    if (!mu_item_supports("level", $g, $c)) $slot["level"] = 0;
    if (!mu_item_supports("skill", $g, $c)) $slot["skill"] = false;
    if (!mu_item_supports("luck",  $g, $c)) $slot["luck"]  = false;
    if (!mu_item_supports("exc",   $g, $c)) $slot["exc"]   = 0;
}

function mu_is_hex_inventory($value, $min_chars)
{
    return $value !== "" && (strlen($value) % 2) === 0
        && strlen($value) >= $min_chars && ctype_xdigit($value);
}

/**
 * Resolve the displayable identity (group, code, catalog name, sprite filename)
 * for a 12-byte equipped slot. The slot/level arguments are kept for backwards
 * compatibility with mu_parse_equipped_inventory(); only $level affects the
 * sprite chosen by mu_item_image() (e.g. +10 glow variant).
 *
 * @param string $bytes 12-byte slot record.
 * @param int $slot Equipped slot index 0..11 (currently informational only).
 * @param int $level Visible item upgrade level (0..15).
 */
function mu_choose_item_identity($bytes, $slot, $level)
{
    $id = mu_decode_item_identity($bytes);
    $group = (int)$id["group"];
    $code  = (int)$id["code"];
    return [
        "group" => $group,
        "code"  => $code,
        "name"  => mu_item_name($group, $code),
        "image" => mu_item_image($group, $code, $level),
    ];
}

function mu_inventory_bytes($blob)
{
    if ($blob === null || $blob === "" || !is_string($blob)) return "";
    $trimmed = trim($blob);
    $has_hex_prefix = stripos($trimmed, "0x") === 0;
    if ($has_hex_prefix) $trimmed = substr($trimmed, 2);
    $has_hex_formatting = $has_hex_prefix || preg_match('/\s/', $trimmed) > 0;
    $compact = preg_replace('/\s+/', '', $trimmed);
    if ($has_hex_formatting && mu_is_hex_inventory($compact, MU_HEX_FORMATTED_MIN_ITEM_CHARS)) {
        $packed = hex2bin($compact);
        if ($packed !== false) return $packed;
    }
    if (mu_is_hex_inventory($compact, MU_EQUIPPED_SLOTS * MU_ITEM_BYTES * 2)) {
        $packed = hex2bin($compact);
        if ($packed !== false) return $packed;
    }
    return $blob;
}

/**
 * Decode the 12 equipped slots from a MuOnline Character.Inventory blob,
 * targeting the 16-byte-per-slot layout used by this server (the same
 * layout the reference parser modules/webv.php walks):
 *
 *   - The Inventory column is a packed varbinary of 16-byte slot records;
 *     slots 0–11 are equipped (right hand, left hand, helm, armor, pants,
 *     gloves, boots, wings, pet, pendant, ring 1, ring 2 in that storage
 *     order). An empty slot is filled with 16 × 0xFF.
 *   - The per-slot byte layout is documented on mu_decode_item_identity().
 *
 * We return an array of 12 entries with:
 *   - empty:   true/false
 *   - group:   item-group id (0-15)
 *   - code:    item code     (0-63)
 *   - name:    catalog name (or "Unknown")
 *   - image:   sprite filename relative to assets/images/items
 *   - level:   item +N level (0-15)
 *   - skill:   bool — skill option
 *   - luck:    bool — luck option
 *   - opt:     additional option amount (0-3, ×4 = +N)
 *   - exc:     excellent option bitmask (bits 0-5)
 *   - raw:     12-byte hex (for debugging)
 */
function mu_parse_equipped_inventory($blob)
{
    $slots = array_fill(0, MU_EQUIPPED_SLOTS, [
        "empty" => true, "group" => 0, "code" => 0, "level" => 0,
        "skill" => false, "luck" => false, "opt" => 0, "exc" => 0,
        "raw" => "", "name" => "Empty", "image" => "",
    ]);
    $blob = mu_inventory_bytes($blob);
    if ($blob === "") {
        return $slots;
    }
    $len = strlen($blob);
    $slot_size = MU_ITEM_BYTES;
    for ($i = 0; $i < MU_EQUIPPED_SLOTS; $i++) {
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
        // Excellent option bitmask is the low 6 bits of byte 7; the top two
        // bits of byte 7 are reserved for set/ancient flags in some builds
        // and must not leak into the "Exc" badge on the character page.
        $excellent_mask = ord($bytes[7]) & MU_EXCELLENT_OPTION_MASK;

        $level = ($b1 >> 3) & 0x0F;
        $skill = (bool)($b1 & 0x80);
        $luck  = (bool)($b1 & 0x04);
        $opt   = $b1 & 0x03;              // ×4 = visible +N option
        $identity = mu_choose_item_identity($bytes, $i, $level);

        // Strict S2/S3 Ep.1 slot-type validation: a Helm slot must hold a
        // Helm, a Pet slot must hold a pet, etc. Anything else is bogus
        // data (corrupt blob, wrong-season build, manual DB edit) and is
        // surfaced as an empty slot rather than as a mis-rendered item.
        if (!mu_slot_allows($i, $identity["group"], $identity["code"])) {
            continue;
        }

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
            "exc"   => $excellent_mask,
            "raw"   => strtoupper(bin2hex($bytes)),
        ];
        // Clamp +N / Skill / Luck / Exc badges to what this item class
        // can legally carry under S2/S3 Ep.1.
        mu_clamp_item_badges($slots[$i]);
    }
    return $slots;
}

/**
 * Decode N slots from a packed MuOnline warehouse / inventory blob using
 * the same per-slot byte layout as mu_parse_equipped_inventory(): each
 * slot is MU_ITEM_BYTES (16) bytes, empty slot = 16 × 0xFF.
 *
 * Used by the Web-Сундук page and the admin "view warehouse" tool.
 *
 * @param string $blob Raw varbinary from `warehouse.Items` (or hex string).
 * @param int    $slots Maximum slot count to decode (default 120 = 8×15).
 * @return array<int,array> Same per-slot shape as mu_parse_equipped_inventory().
 */
function mu_parse_warehouse_blob($blob, $slots = 120)
{
    $slots = max(1, (int)$slots);
    $out = array_fill(0, $slots, [
        "empty" => true, "group" => 0, "code" => 0, "level" => 0,
        "skill" => false, "luck" => false, "opt" => 0, "exc" => 0,
        "raw" => "", "name" => "Empty", "image" => "",
        "w" => 1, "h" => 1,
    ]);
    $blob = mu_inventory_bytes($blob);
    if ($blob === "") return $out;
    $len = strlen($blob);
    $slot_size = MU_ITEM_BYTES;
    for ($i = 0; $i < $slots; $i++) {
        $off = $i * $slot_size;
        if ($off + $slot_size > $len) break;
        $bytes = substr($blob, $off, $slot_size);
        $all_ff = true;
        for ($b = 0; $b < $slot_size; $b++) {
            if (ord($bytes[$b]) !== 0xFF) { $all_ff = false; break; }
        }
        if ($all_ff) continue;

        $b1 = ord($bytes[1]);
        $excellent_mask = ord($bytes[7]) & MU_EXCELLENT_OPTION_MASK;
        $level = ($b1 >> 3) & 0x0F;
        $skill = (bool)($b1 & 0x80);
        $luck  = (bool)($b1 & 0x04);
        $opt   = $b1 & 0x03;
        $identity = mu_choose_item_identity($bytes, $i, $level);

        // Strict S2/S3 Ep.1 catalog filter: anything that decodes to an
        // item this server version doesn't know about (e.g. a stray
        // group 16+ byte from a corrupt or wrong-season blob) is dropped
        // to Empty rather than rendered as a nameless tile.
        if (!mu_item_exists($identity["group"], $identity["code"])) {
            continue;
        }

        $out[$i] = [
            "empty" => false,
            "group" => $identity["group"],
            "code"  => $identity["code"],
            "name"  => $identity["name"],
            "image" => $identity["image"],
            "level" => $level,
            "skill" => $skill,
            "luck"  => $luck,
            "opt"   => $opt,
            "exc"   => $excellent_mask,
            "raw"   => strtoupper(bin2hex($bytes)),
        ];
        // Per-item inventory dimensions in cells (Item.bmd / S3 Ep.1).
        $size = mu_item_size($identity["group"], $identity["code"]);
        $out[$i]["w"] = (int)$size[0];
        $out[$i]["h"] = (int)$size[1];
        // Clamp +N / Skill / Luck / Exc badges to what this item class
        // can legally carry under S2/S3 Ep.1 — jewels / potions /
        // scrolls / pets / pendants / rings cannot carry any of them.
        mu_clamp_item_badges($out[$i]);
    }
    return $out;
}

/**
 * Pack a single decoded slot back into the warehouse blob, replacing the
 * 16 bytes at `slot` with `bytes` (or 16 × 0xFF when $bytes is "").
 * Returns the new full blob (same length as input or extended with 0xFF
 * up to ($slot+1)*16 bytes).
 */
function mu_warehouse_set_slot($blob, $slot, $bytes, $total_slots = 120)
{
    $slot = (int)$slot;
    $total_slots = max(1, (int)$total_slots);
    if ($slot < 0 || $slot >= $total_slots) return $blob;
    $packed = mu_inventory_bytes($blob);
    $expected = $total_slots * MU_ITEM_BYTES;
    if (strlen($packed) < $expected) {
        $packed .= str_repeat("\xFF", $expected - strlen($packed));
    }
    if ($bytes === "" || $bytes === null) {
        $bytes = str_repeat("\xFF", MU_ITEM_BYTES);
    } elseif (strlen($bytes) !== MU_ITEM_BYTES) {
        return $blob; // refuse malformed input
    }
    return substr($packed, 0, $slot * MU_ITEM_BYTES)
         . $bytes
         . substr($packed, ($slot + 1) * MU_ITEM_BYTES);
}

/**
 * Find the index of the first empty (16 × 0xFF) slot in a warehouse blob.
 * Returns -1 if all $total_slots are occupied.
 */
function mu_warehouse_first_empty_slot($blob, $total_slots = 120)
{
    $total_slots = max(1, (int)$total_slots);
    $packed = mu_inventory_bytes($blob);
    $expected = $total_slots * MU_ITEM_BYTES;
    if (strlen($packed) < $expected) {
        // Whatever's missing at the end is implicitly empty.
        return (int)floor(strlen($packed) / MU_ITEM_BYTES);
    }
    for ($i = 0; $i < $total_slots; $i++) {
        $off = $i * MU_ITEM_BYTES;
        $all_ff = true;
        for ($b = 0; $b < MU_ITEM_BYTES; $b++) {
            if (ord($packed[$off + $b]) !== 0xFF) { $all_ff = false; break; }
        }
        if ($all_ff) return $i;
    }
    return -1;
}

/**
 * Get the raw 16 bytes of a warehouse slot; returns "" if slot is empty
 * (all 0xFF) or out of range.
 */
function mu_warehouse_get_slot($blob, $slot, $total_slots = 120)
{
    $slot = (int)$slot;
    $total_slots = max(1, (int)$total_slots);
    if ($slot < 0 || $slot >= $total_slots) return "";
    $packed = mu_inventory_bytes($blob);
    $off = $slot * MU_ITEM_BYTES;
    if ($off + MU_ITEM_BYTES > strlen($packed)) return "";
    $bytes = substr($packed, $off, MU_ITEM_BYTES);
    $all_ff = true;
    for ($b = 0; $b < MU_ITEM_BYTES; $b++) {
        if (ord($bytes[$b]) !== 0xFF) { $all_ff = false; break; }
    }
    return $all_ff ? "" : $bytes;
}

/** Delete a cached entry created with cache_set(). */
function cache_del($key)
{
    global $config;
    $f = ($config["__cache"] ?? sys_get_temp_dir()) . "/" . md5($key) . ".cache";
    if (is_file($f)) @unlink($f);
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

/**
 * Render a numeric pager: "First [1][2]..[N] Last".
 *
 *   $current      — 1-based current page
 *   $total_pages  — total number of pages (>=1)
 *   $url_template — printf-style URL with one %d for the page number
 *                   (e.g. "index.php?m=news&page=%d")
 *   $window       — how many numbered neighbours to render around current
 *
 * Returns HTML; empty string when there is only one page.
 */
function pager_html($current, $total_pages, $url_template, $window = 2)
{
    $current     = max(1, (int)$current);
    $total_pages = max(1, (int)$total_pages);
    if ($total_pages <= 1) return "";
    if ($current > $total_pages) $current = $total_pages;

    $u = function ($p) use ($url_template) {
        return htmlspecialchars(sprintf($url_template, (int)$p), ENT_QUOTES, "UTF-8");
    };

    $lbl_first = htmlspecialchars(function_exists("lang") ? lang("news.pager.first", "First") : "First", ENT_QUOTES, "UTF-8");
    $lbl_last  = htmlspecialchars(function_exists("lang") ? lang("news.pager.last",  "Last")  : "Last",  ENT_QUOTES, "UTF-8");

    // Build the visible-number window: always include 1 and last, plus
    // a [current-window .. current+window] band, with "…" placeholders
    // around gaps. This produces things like 1 … 4 5 6 … 12.
    $nums = [1];
    for ($i = $current - $window; $i <= $current + $window; $i++) {
        if ($i > 1 && $i < $total_pages) $nums[] = $i;
    }
    if ($total_pages > 1) $nums[] = $total_pages;
    $nums = array_values(array_unique($nums));
    sort($nums);

    $out = '<nav class="pager" aria-label="pagination">';
    if ($current > 1) {
        $out .= '<a class="pager-edge" href="' . $u(1) . '">' . $lbl_first . '</a>';
    } else {
        $out .= '<span class="pager-edge disabled">' . $lbl_first . '</span>';
    }
    $prev = 0;
    foreach ($nums as $n) {
        if ($prev && $n > $prev + 1) {
            $out .= '<span class="pager-gap">…</span>';
        }
        if ($n === $current) {
            $out .= '<span class="pager-num current">' . (int)$n . '</span>';
        } else {
            $out .= '<a class="pager-num" href="' . $u($n) . '">' . (int)$n . '</a>';
        }
        $prev = $n;
    }
    if ($current < $total_pages) {
        $out .= '<a class="pager-edge" href="' . $u($total_pages) . '">' . $lbl_last . '</a>';
    } else {
        $out .= '<span class="pager-edge disabled">' . $lbl_last . '</span>';
    }
    $out .= '</nav>';
    return $out;
}
