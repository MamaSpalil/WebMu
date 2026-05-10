<?php
// Top 5 quest-leaders. Auto-detects the quest column name (different
// MuOnline emulators use Quest, QuestNumber, or QuestInfo).
if (!defined("insite")) die("no access");

$cached = cache_get("widget.questtop", 60);
if ($cached !== null) return $cached;

$out = [];
$char_t = $config["char_table"] ?? "Character";
$quest_col = null;
foreach (["QuestNumber", "Quest", "QuestInfo"] as $candidate) {
    if (db_column_exists($char_t, $candidate)) { $quest_col = $candidate; break; }
}
if ($quest_col !== null) {
    $tq = db_ident($char_t, "Character");
    $qq = db_ident($quest_col);
    $rows = db_all(
        "SELECT TOP 5 Name, $qq AS QuestVal, Class
         FROM $tq
         ORDER BY $qq DESC, cLevel DESC"
    );
    foreach ($rows as $r) {
        $out[] = [
            "name"    => trim((string)$r["Name"]),
            "quests"  => (int)($r["QuestVal"] ?? 0),
            "class"   => mu_class($r["Class"] ?? 0),
        ];
    }
}
cache_set("widget.questtop", $out);
return $out;
