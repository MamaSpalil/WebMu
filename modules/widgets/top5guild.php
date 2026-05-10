<?php
// Top 5 guilds by total resets of members.
if (!defined("insite")) die("no access");

$cached = cache_get("widget.top5guild", 60);
if ($cached !== null) return $cached;

$guild_t       = db_ident($config["guild_table"] ?? "Guild", "Guild");
$guild_member_t= db_ident($config["guild_member_table"] ?? "GuildMember", "GuildMember");
$char_t        = db_ident($config["char_table"] ?? "Character", "Character");
$guild_name    = db_ident($config["guild_name_col"]  ?? "G_Name",   "G_Name");
$guild_master  = db_ident($config["guild_master_col"]?? "G_Master", "G_Master");
$char_name     = db_ident($config["char_name_col"]   ?? "Name",     "Name");
$char_resets   = db_ident($config["char_resets_col"] ?? "Resets",   "Resets");
$gm_char_name  = db_ident($config["guild_member_name_col"]  ?? "Name",   "Name");
$gm_guild_name = db_ident($config["guild_member_guild_col"] ?? "G_Name", "G_Name");

$rows = db_all(
    "SELECT TOP 5 g.$guild_name AS G_Name, g.$guild_master AS G_Master,
            COUNT(gm.$gm_char_name) AS members, SUM(c.$char_resets) AS total_resets
     FROM $guild_t g
     JOIN $guild_member_t gm
          ON gm.$gm_guild_name COLLATE DATABASE_DEFAULT
           = g.$guild_name      COLLATE DATABASE_DEFAULT
     JOIN $char_t c
          ON c.$char_name COLLATE DATABASE_DEFAULT
           = gm.$gm_char_name COLLATE DATABASE_DEFAULT
     GROUP BY g.$guild_name, g.$guild_master
     ORDER BY total_resets DESC"
);
$out = [];
foreach ($rows as $r) {
    $out[] = [
        "name"    => trim((string)$r["G_Name"]),
        "master"  => trim((string)$r["G_Master"]),
        "members" => (int)($r["members"] ?? 0),
        "score"   => (int)($r["total_resets"] ?? 0),
    ];
}
cache_set("widget.top5guild", $out);
return $out;
