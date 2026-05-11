<?php
// =====================================================================
//  Character profile page  —  ?m=character&name=<charname>
//
//  Renders full info pulled from the MuOnline game DB for one character:
//    Level, Resets, Grand Reset, Strength, Agility, Vitality, Energy,
//    Command (Leadership — shown only for Dark Lord), MapNumber + coords,
//    guild membership, online state, and the 12 equipped inventory slots.
//
//  Every optional column is gated through db_column_exists() so the page
//  also works on stock Season 3 backups (MuOnline_Bak) that don't have
//  MasterLevel / GReset / Leadership.
// =====================================================================
if (!defined("insite")) die("no access");

// Validate the requested character name: 1–10 chars, letters & digits only
// (MuOnline allows short in-game names; we deliberately mirror the game's
// own constraints rather than the stricter account-login validator).
$name = trim((string)($_GET["name"] ?? ""));
if (!preg_match('~^[A-Za-z0-9]{1,10}$~', $name)) {
    http_response_code(404);
    render_page("notfound", ["title" => "404"]);
    return;
}

$cache_key = "char.profile." . strtolower($name);
$data = cache_get($cache_key, 30);

if ($data === null) {
    $char_t       = db_ident($config["char_table"] ?? "Character", "Character");
    $char_t_raw   = $config["char_table"] ?? "Character";
    $char_name    = db_ident($config["char_name_col"] ?? "Name", "Name");
    $char_account = db_ident($config["char_account_col"] ?? "AccountID", "AccountID");
    $char_level   = db_ident($config["char_level_col"] ?? "cLevel", "cLevel");
    $char_resets  = db_ident($config["char_resets_col"] ?? "Resets", "Resets");
    $char_class   = db_ident($config["char_class_col"] ?? "Class", "Class");
    $char_pk_count = db_ident($config["char_pk_count_col"] ?? "PkCount", "PkCount");
    $char_pk_level = db_ident($config["char_pk_level_col"] ?? "PkLevel", "PkLevel");

    // Optional columns — only added to SELECT when they actually exist.
    $opt_cols = [
        "Strength"   => "Strength",
        "Dexterity"  => "Dexterity",
        "Vitality"   => "Vitality",
        "Energy"     => "Energy",
        "Leadership" => "Leadership",
        "Money"      => "Money",
        "MapNumber"  => "MapNumber",
        "MapPosX"    => "MapPosX",
        "MapPosY"    => "MapPosY",
        "Quest"      => "Quest",
        "Inventory"  => "Inventory",
        "GReset"     => "GReset",
    ];
    $char_master_cfg = trim((string)($config["char_master_col"] ?? ""));
    if ($char_master_cfg !== "") {
        $opt_cols["MasterLevel"] = $char_master_cfg;
    }

    $select_parts = [
        "c.$char_name      AS Name",
        "c.$char_account   AS AccountID",
        "c.$char_level     AS cLevel",
        "c.$char_resets    AS Resets",
        "c.$char_class     AS Class",
        "c.$char_pk_count  AS PkCount",
        "c.$char_pk_level  AS PkLevel",
    ];
    foreach ($opt_cols as $alias => $col) {
        if (db_column_exists($char_t_raw, $col)) {
            $select_parts[] = "c." . db_ident($col) . " AS " . db_ident($alias);
        }
    }

    // Guild membership (LEFT JOIN; COLLATE-safe — see ranking.php).
    $guild_member_t   = db_ident($config["guild_member_table"] ?? "GuildMember", "GuildMember");
    $gm_char_name     = db_ident($config["guild_member_name_col"]  ?? "Name",   "Name");
    $gm_guild_name    = db_ident($config["guild_member_guild_col"] ?? "G_Name", "G_Name");
    $select_parts[]   = "gm.$gm_guild_name AS G_Name";

    // Online state (LEFT JOIN MEMB_STAT on AccountID = memb___id).
    $stat_t       = db_ident($config["stat_table"] ?? "MEMB_STAT", "MEMB_STAT");
    $stat_account = db_ident($config["stat_account_col"] ?? "memb___id", "memb___id");
    $stat_connect = db_ident($config["stat_connect_col"] ?? "ConnectStat", "ConnectStat");
    $select_parts[] = "ms.$stat_connect AS ConnectStat";

    $sql = "SELECT TOP 1 " . implode(", ", $select_parts) . "
            FROM $char_t c
            LEFT JOIN $guild_member_t gm
                 ON gm.$gm_char_name COLLATE DATABASE_DEFAULT
                  = c.$char_name      COLLATE DATABASE_DEFAULT
            LEFT JOIN $stat_t ms
                 ON ms.$stat_account COLLATE DATABASE_DEFAULT
                  = c.$char_account  COLLATE DATABASE_DEFAULT
            WHERE c.$char_name COLLATE DATABASE_DEFAULT
                = ? COLLATE DATABASE_DEFAULT";
    $row = db_one($sql, [$name]);

    if (!$row) {
        // Fall back through cache: cache "not found" briefly so we don't
        // hammer the DB if someone scans names.
        cache_set($cache_key, ["__missing" => true]);
        http_response_code(404);
        render_page("notfound", ["title" => "404"]);
        return;
    }

    $class_h = mu_class($row["Class"] ?? 0);

    // Decode the 12 equipped slots from the Inventory blob (if available).
    $equipped = isset($row["Inventory"])
        ? mu_parse_equipped_inventory($row["Inventory"])
        : array_fill(0, 12, ["empty" => true]);

    $data = [
        "name"         => trim((string)$row["Name"]),
        "account"      => trim((string)($row["AccountID"] ?? "")),
        "level"        => (int)($row["cLevel"] ?? 0),
        "resets"       => (int)($row["Resets"] ?? 0),
        "greset"       => isset($row["GReset"])      ? (int)$row["GReset"]      : null,
        "master"       => isset($row["MasterLevel"]) ? (int)$row["MasterLevel"] : null,
        "strength"     => isset($row["Strength"])    ? (int)$row["Strength"]    : null,
        "dexterity"    => isset($row["Dexterity"])   ? (int)$row["Dexterity"]   : null,
        "vitality"     => isset($row["Vitality"])    ? (int)$row["Vitality"]    : null,
        "energy"       => isset($row["Energy"])      ? (int)$row["Energy"]      : null,
        "leadership"   => isset($row["Leadership"])  ? (int)$row["Leadership"]  : null,
        "money"        => isset($row["Money"])       ? (int)$row["Money"]       : null,
        "map_number"   => isset($row["MapNumber"])   ? (int)$row["MapNumber"]   : null,
        "map_x"        => isset($row["MapPosX"])     ? (int)$row["MapPosX"]     : null,
        "map_y"        => isset($row["MapPosY"])     ? (int)$row["MapPosY"]     : null,
        "pk_count"     => (int)($row["PkCount"] ?? 0),
        "pk_level"     => (int)($row["PkLevel"] ?? 0),
        "class"        => $class_h,
        "class_code"   => (int)($row["Class"] ?? 0),
        "guild"        => isset($row["G_Name"]) ? trim((string)$row["G_Name"]) : "",
        "online"       => (int)($row["ConnectStat"] ?? 0) === 1,
        "equipped"     => $equipped,
        "has_inventory"=> isset($row["Inventory"]),
    ];
    cache_set($cache_key, $data);
}

if (!empty($data["__missing"])) {
    http_response_code(404);
    render_page("notfound", ["title" => "404"]);
    return;
}

render_page("character", $data + [
    "title" => $data["name"],
]);
