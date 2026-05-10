<?php
// Last 5 registered accounts.
if (!defined("insite")) die("no access");

$cached = cache_get("widget.lastinf", 60);
if ($cached !== null) return $cached;

// MEMB_INFO has appl_days (registration date string in stock schema)
$rows = db_all("SELECT TOP 5 memb___id, appl_days FROM MEMB_INFO ORDER BY appl_days DESC");
$out = [];
foreach ($rows as $r) {
    $out[] = [
        "id"   => trim((string)$r["memb___id"]),
        "date" => trim((string)($r["appl_days"] ?? "")),
    ];
}
cache_set("widget.lastinf", $out);
return $out;
