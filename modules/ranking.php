<?php
// Ranking page — top players, top guilds, online now. 60s cache.
if (!defined("insite")) die("no access");

$tab = preg_replace('~[^a-z]~', '', strtolower((string)($_GET["tab"] ?? "players")));
if (!in_array($tab, ["players", "guilds", "kills", "online"], true)) $tab = "players";

$cache_key = "rank.all";
$data = cache_get($cache_key, 60);
if ($data === null) {
    $char_t = db_ident($config["char_table"] ?? "Character", "Character");
    $char_name = db_ident($config["char_name_col"] ?? "Name", "Name");
    $char_account = db_ident($config["char_account_col"] ?? "AccountID", "AccountID");
    $char_level = db_ident($config["char_level_col"] ?? "cLevel", "cLevel");
    $char_resets = db_ident($config["char_resets_col"] ?? "Resets", "Resets");
    $char_class = db_ident($config["char_class_col"] ?? "Class", "Class");
    $char_pk_count = db_ident($config["char_pk_count_col"] ?? "PkCount", "PkCount");
    $char_pk_level = db_ident($config["char_pk_level_col"] ?? "PkLevel", "PkLevel");
    $char_master_cfg = trim((string)($config["char_master_col"] ?? ""));
    $char_master = $char_master_cfg !== "" ? "c." . db_ident($char_master_cfg, "MasterLevel") : "0";

    $guild_t = db_ident($config["guild_table"] ?? "Guild", "Guild");
    $guild_member_t = db_ident($config["guild_member_table"] ?? "GuildMember", "GuildMember");
    $guild_name = db_ident($config["guild_name_col"] ?? "G_Name", "G_Name");
    $guild_master = db_ident($config["guild_master_col"] ?? "G_Master", "G_Master");
    $guild_score = db_ident($config["guild_score_col"] ?? "G_Score", "G_Score");
    $stat_t = db_ident($config["stat_table"] ?? "MEMB_STAT", "MEMB_STAT");
    $stat_account = db_ident($config["stat_account_col"] ?? "memb___id", "memb___id");
    $stat_connect = db_ident($config["stat_connect_col"] ?? "ConnectStat", "ConnectStat");

    $players = db_all(
        "SELECT TOP 100 c.$char_name AS Name, c.$char_level AS cLevel,
                c.$char_resets AS Resets, $char_master AS MasterLevel,
                c.$char_class AS Class, gm.$guild_name AS G_Name
         FROM $char_t c
         LEFT JOIN $guild_member_t gm ON gm.$char_name = c.$char_name
         ORDER BY c.$char_resets DESC, c.$char_level DESC, MasterLevel DESC"
    );
    foreach ($players as &$p) { $p["class_h"] = mu_class($p["Class"] ?? 0); }
    unset($p);

    $guilds = db_all(
        "SELECT TOP 50 g.$guild_name AS G_Name, g.$guild_master AS G_Master,
                COUNT(gm.$char_name) AS members, ISNULL(g.$guild_score,0) AS total_resets
         FROM $guild_t g
         LEFT JOIN $guild_member_t gm ON gm.$guild_name = g.$guild_name
         GROUP BY g.$guild_name, g.$guild_master, g.$guild_score
         ORDER BY total_resets DESC, members DESC"
    );

    $kills = db_all(
        "SELECT TOP 25 $char_name AS Name, $char_level AS cLevel, $char_class AS Class,
                $char_pk_count AS PkCount, $char_pk_level AS PkLevel
         FROM $char_t
         WHERE $char_pk_count > 0
         ORDER BY $char_pk_count DESC"
    );
    foreach ($kills as &$k) { $k["class_h"] = mu_class($k["Class"] ?? 0); }
    unset($k);

    $online = db_all(
        "SELECT TOP 100 ms.$stat_account AS memb___id, c.$char_name AS Name,
                c.$char_level AS cLevel, c.$char_class AS Class
         FROM $stat_t ms
         LEFT JOIN $char_t c ON c.$char_account = ms.$stat_account
         WHERE ms.$stat_connect = 1
         ORDER BY c.$char_resets DESC, c.$char_level DESC"
    );
    foreach ($online as &$o) { $o["class_h"] = mu_class($o["Class"] ?? 0); }
    unset($o);

    // Stats strip
    $stats = [
        "accounts"   => (int)(db_one("SELECT COUNT(*) AS c FROM MEMB_INFO")["c"] ?? 0),
        "characters" => (int)(db_one("SELECT COUNT(*) AS c FROM $char_t")["c"] ?? 0),
        "online"     => (int)(db_one("SELECT COUNT(*) AS c FROM $stat_t WHERE $stat_connect=1")["c"] ?? 0),
        "guilds"     => (int)(db_one("SELECT COUNT(*) AS c FROM $guild_t")["c"] ?? 0),
    ];
    $stats["online"] += (int)($config["onlineplus"] ?? 0);

    $data = compact("players", "guilds", "kills", "online", "stats");
    cache_set($cache_key, $data);
}

render_page("ranking", $data + [
    "title" => lang("rank.title"),
    "tab"   => $tab,
]);
