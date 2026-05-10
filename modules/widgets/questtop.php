<?php
// Top 5 quest-leaders.
if (!defined("insite")) die("no access");

$cached = cache_get("widget.questtop", 60);
if ($cached !== null) return $cached;

$rows = db_all(
    "SELECT TOP 5 Name, QuestNumber, Class
     FROM Character
     ORDER BY QuestNumber DESC, cLevel DESC"
);
$out = [];
foreach ($rows as $r) {
    $out[] = [
        "name"    => trim((string)$r["Name"]),
        "quests"  => (int)($r["QuestNumber"] ?? 0),
        "class"   => mu_class($r["Class"] ?? 0),
    ];
}
cache_set("widget.questtop", $out);
return $out;
