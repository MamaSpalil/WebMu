<?php
if (!defined("insite")) die("no access");

$stats = cache_get("about.stats", 60);
if ($stats === null) {
    $char_t = db_ident($config["char_table"] ?? "Character", "Character");
    $guild_t = db_ident($config["guild_table"] ?? "Guild", "Guild");
    $stat_t = db_ident($config["stat_table"] ?? "MEMB_STAT", "MEMB_STAT");
    $stat_connect = db_ident($config["stat_connect_col"] ?? "ConnectStat", "ConnectStat");
    $stats = [
        "accounts"   => (int)(db_one("SELECT COUNT(*) AS c FROM MEMB_INFO")["c"] ?? 0),
        "characters" => (int)(db_one("SELECT COUNT(*) AS c FROM $char_t")["c"] ?? 0),
        "guilds"     => (int)(db_one("SELECT COUNT(*) AS c FROM $guild_t")["c"] ?? 0),
        "online"     => (int)(db_one("SELECT COUNT(*) AS c FROM $stat_t WHERE $stat_connect = 1")["c"] ?? 0),
    ];
    $stats["online"] += (int)($config["onlineplus"] ?? 0);
    cache_set("about.stats", $stats);
}

render_page("about", [
    "title" => lang("nav.about"),
    "stats" => $stats,
]);
