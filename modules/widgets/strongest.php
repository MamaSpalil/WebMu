<?php
// Strongest character widget — top 1 by Resets, Level, Master.
if (!defined("insite")) die("no access");

$cached = cache_get("widget.strongest", 60);
if ($cached !== null) return $cached;

$row = db_one(
    "SELECT TOP 1 c.Name, c.cLevel, c.Resets, c.MasterLevel, c.Class
     FROM Character c
     ORDER BY c.Resets DESC, c.cLevel DESC, c.MasterLevel DESC"
);
$out = $row ? [
    "name"    => trim((string)$row["Name"]),
    "level"   => (int)($row["cLevel"] ?? 0),
    "resets"  => (int)($row["Resets"] ?? 0),
    "master"  => (int)($row["MasterLevel"] ?? 0),
    "class"   => mu_class($row["Class"] ?? 0),
] : null;
cache_set("widget.strongest", $out);
return $out;
