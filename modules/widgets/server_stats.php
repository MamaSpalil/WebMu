<?php
// Server statistics widget — totals for the home-page strip.
// All counts are best-effort: any individual query failure is captured
// by the err_push_context wrapper in modules/home.php and the affected
// number falls back to 0 so the panel still renders.
if (!defined("insite")) die("no access");

$char_t       = db_ident($config["char_table"]  ?? "Character",  "Character");
$guild_t      = db_ident($config["guild_table"] ?? "Guild",      "Guild");
$stat_t       = db_ident($config["stat_table"]  ?? "MEMB_STAT",  "MEMB_STAT");
$stat_connect = db_ident($config["stat_connect_col"] ?? "ConnectStat", "ConnectStat");

$accounts   = (int)(db_one("SELECT COUNT(*) AS c FROM MEMB_INFO")["c"] ?? 0);
$characters = (int)(db_one("SELECT COUNT(*) AS c FROM $char_t")["c"] ?? 0);
$guilds     = (int)(db_one("SELECT COUNT(*) AS c FROM $guild_t")["c"] ?? 0);
$online     = (int)(db_one("SELECT COUNT(*) AS c FROM $stat_t WHERE $stat_connect = 1")["c"] ?? 0);
$online    += (int)($config["onlineplus"] ?? 0);

return [
    "accounts"   => $accounts,
    "characters" => $characters,
    "guilds"     => $guilds,
    "online"     => $online,
];
