<?php
// Top 5 guilds by total resets of members.
if (!defined("insite")) die("no access");

$cached = cache_get("widget.top5guild", 60);
if ($cached !== null) return $cached;

$rows = db_all(
    "SELECT TOP 5 g.G_Name, g.G_Master, COUNT(gm.Name) AS members, SUM(c.Resets) AS total_resets
     FROM Guild g
     JOIN GuildMember gm ON gm.G_Name = g.G_Name
     JOIN Character    c ON c.Name    = gm.Name
     GROUP BY g.G_Name, g.G_Master
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
