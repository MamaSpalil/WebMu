<?php
// =====================================================================
//  Helpers: validators, formatters, MuOnline class lookup, file cache.
// =====================================================================
if (!defined("insite")) die("no access");
if (!defined("MU_EQUIPPED_SLOTS")) define("MU_EQUIPPED_SLOTS", 12);
if (!defined("MU_ITEM_BYTES")) define("MU_ITEM_BYTES", 12);
if (!defined("MU_ITEM_TYPE_HIGH_BIT_OFFSET")) define("MU_ITEM_TYPE_HIGH_BIT_OFFSET", 8);
if (!defined("MU_EXTENDED_ITEM_INDEX_OFFSET")) define("MU_EXTENDED_ITEM_INDEX_OFFSET", 32);
if (!defined("MU_ITEM_TYPE_HIGH_BIT_FLAG")) define("MU_ITEM_TYPE_HIGH_BIT_FLAG", 0x80);
if (!defined("MU_EXTENDED_ITEM_INDEX_FLAG")) define("MU_EXTENDED_ITEM_INDEX_FLAG", 0x40);
if (!defined("MU_ITEM_GLOW_LEVEL_THRESHOLD")) define("MU_ITEM_GLOW_LEVEL_THRESHOLD", 10);
if (!defined("MU_HEX_FORMATTED_MIN_ITEM_CHARS")) define("MU_HEX_FORMATTED_MIN_ITEM_CHARS", MU_ITEM_BYTES * 2);
if (!defined("MU_ITEM_SCORE_EXPECTED_SLOT")) define("MU_ITEM_SCORE_EXPECTED_SLOT", 4);
if (!defined("MU_ITEM_SCORE_KNOWN_NAME")) define("MU_ITEM_SCORE_KNOWN_NAME", 2);
if (!defined("MU_ITEM_SCORE_HAS_IMAGE")) define("MU_ITEM_SCORE_HAS_IMAGE", 1);

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

function mu_slot_expected_groups($slot)
{
    $map = [
        0 => [0, 1, 2, 3, 4, 5],    // right hand: weapons
        1 => [0, 1, 2, 3, 4, 5, 6], // left hand: weapon or shield
        2 => [7],                    // helm
        3 => [8],                    // armor
        4 => [9],                    // pants
        5 => [10],                   // gloves
        6 => [11],                   // boots
        7 => [12],                   // wings/cape
        8 => [13],                   // pet
        9 => [13],                   // pendant
        10 => [13],                  // ring
        11 => [13],                  // ring
    ];
    return $map[(int)$slot] ?? [];
}

function mu_slot_allowed_codes($slot, $group)
{
    $slot = (int)$slot;
    $group = (int)$group;
    if ($slot === 7 && $group === 12) {
        // Wings slot: 1st/2nd/3rd wings, cloaks and mantles from group 12.
        return [0, 1, 2, 3, 4, 5, 6, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45];
    }
    if ($group !== 13) {
        return null;
    }
    if ($slot === 8) {
        // Pet slot: 0 Angel, 1 Imp, 2 Uniria, 3 Dinorant, 4 Horse, 5 Raven, 37 Fenrir.
        return [0, 1, 2, 3, 4, 5, 37];
    }
    if ($slot === 9) {
        // Pendant slot: lightning/fire/ice/wind/water/ability pendants.
        return [12, 13, 25, 26, 27, 28];
    }
    if ($slot === 10 || $slot === 11) {
        // Ring slots: ice/poison and elemental/transformation event rings.
        return [8, 9, 10, 20, 21, 22, 23, 24, 38, 39, 40, 41, 42];
    }
    // Null means this slot/group has no item-code restriction: every code
    // within the expected item group is valid.
    return null;
}

/**
 * Return whether a decoded item identity (item group + item code) can appear
 * in the given equipped slot. Pass $expected when it is already known to avoid
 * recomputing the slot's allowed item groups while filtering many candidates.
 */
function mu_slot_allows_identity($slot, $group, $code, $expected = null)
{
    if ($expected === null) {
        $expected = mu_slot_expected_groups($slot);
    }
    if ($expected && !in_array((int)$group, $expected, true)) {
        return false;
    }
    $allowed_codes = mu_slot_allowed_codes($slot, $group);
    return $allowed_codes === null || in_array((int)$code, $allowed_codes, true);
}

/**
 * Return item-code variants used by common Season 3 inventory encodings:
 * full = byte 0 as a complete code, extended = low 5 bits plus extension flag,
 * base = low 5-bit item code without the extension flag.
 */
function mu_item_code_variants($bytes)
{
    $b0 = ord($bytes[0]);
    $b9 = ord($bytes[9]);
    $item_index = $b0 & 0x1F;
    return [
        "full" => $b0,
        "extended" => $item_index + (($b9 & MU_EXTENDED_ITEM_INDEX_FLAG) ? MU_EXTENDED_ITEM_INDEX_OFFSET : 0),
        "base" => $item_index,
    ];
}

function mu_decode_item_candidates($bytes)
{
    if (!is_string($bytes) || strlen($bytes) < 10) {
        return [];
    }
    $b0 = ord($bytes[0]);
    $b9 = ord($bytes[9]);
    $code_variants = mu_item_code_variants($bytes);
    $item_type  = ($b0 >> 5) + (($b9 & MU_ITEM_TYPE_HIGH_BIT_FLAG) ? MU_ITEM_TYPE_HIGH_BIT_OFFSET : 0);
    return [
        // Common Season 3 layout: ItemType comes from byte 0 high bits + byte 9 high flag;
        // bit 6 of byte 9 extends ItemIndex by +32 on newer item lists.
        ["group" => $item_type, "code" => $code_variants["extended"]],
        // Legacy Season 3 layout without the extended ItemIndex bit.
        ["group" => $item_type, "code" => $code_variants["base"]],
        // Alternate emulator layout: byte 9 stores ItemType in its high nibble and byte 0 stores the full 8-bit ItemIndex.
        // Do not add byte 9 bit 7 to ItemIndex here: in this format it is part of ItemType.
        ["group" => ($b9 >> 4) & 0x0F, "code" => $code_variants["full"]],
        // Same alternate layout, but capped to 5-bit ItemIndex used by older item lists.
        ["group" => ($b9 >> 4) & 0x0F, "code" => $code_variants["base"]],
    ];
}

function mu_decode_slot_item_candidates($bytes, $slot)
{
    if (!is_string($bytes) || strlen($bytes) < 10) {
        return [];
    }
    $candidates = mu_decode_item_candidates($bytes);
    $expected = mu_slot_expected_groups($slot);
    // Some emulators store the full item code in byte 0, while the common
    // Season 3 layout stores the low 5 bits there plus optional extension.
    $code_variants = mu_item_code_variants($bytes);
    $code_candidates = array_unique([
        $code_variants["full"],
        $code_variants["extended"],
        $code_variants["base"],
    ]);
    foreach ($expected as $group) {
        foreach ($code_candidates as $code) {
            $candidates[] = ["group" => $group, "code" => $code];
        }
    }
    $filtered = [];
    $seen = [];
    foreach ($candidates as $candidate) {
        $group = (int)$candidate["group"];
        $code = (int)$candidate["code"];
        if (!mu_slot_allows_identity($slot, $group, $code, $expected)) {
            continue;
        }
        $key = $group . "_" . $code;
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $filtered[] = ["group" => $group, "code" => $code];
    }
    return $filtered;
}

function mu_is_hex_inventory($value, $min_chars)
{
    return $value !== "" && (strlen($value) % 2) === 0
        && strlen($value) >= $min_chars && ctype_xdigit($value);
}

function mu_choose_item_identity($bytes, $slot, $level)
{
    $expected = mu_slot_expected_groups($slot);
    $best = null;
    $best_score = -1;
    foreach (mu_decode_slot_item_candidates($bytes, $slot) as $candidate) {
        $group = (int)$candidate["group"];
        $code  = (int)$candidate["code"];
        $name  = mu_item_name($group, $code);
        $image = mu_item_image($group, $code, $level);
        $score = 0;
        if ($expected && in_array($group, $expected, true)) $score += MU_ITEM_SCORE_EXPECTED_SLOT;
        if ($name !== "Unknown") $score += MU_ITEM_SCORE_KNOWN_NAME;
        if ($image !== "") $score += MU_ITEM_SCORE_HAS_IMAGE;
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
        $excellent_mask = ord($bytes[7]); // excellent option bitmask in common MU item codes

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
            "exc"   => $excellent_mask,
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
