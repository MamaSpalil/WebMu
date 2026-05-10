<?php
// Strongest character widget — top 1 by Resets, Level, optional Master level.
if (!defined("insite")) die("no access");

$cached = cache_get("widget.strongest", 60);
if ($cached !== null) return $cached;

$char_t = db_ident($config["char_table"] ?? "Character", "Character");
$char_name = db_ident($config["char_name_col"] ?? "Name", "Name");
$char_level = db_ident($config["char_level_col"] ?? "cLevel", "cLevel");
$char_resets = db_ident($config["char_resets_col"] ?? "Resets", "Resets");
$char_class = db_ident($config["char_class_col"] ?? "Class", "Class");
$char_master_cfg = trim((string)($config["char_master_col"] ?? ""));
$char_master = $char_master_cfg !== "" ? "c." . db_ident($char_master_cfg, "MasterLevel") : "0";

$row = db_one(
    "SELECT TOP 1 c.$char_name AS Name, c.$char_level AS cLevel,
            c.$char_resets AS Resets, $char_master AS MasterLevel, c.$char_class AS Class
     FROM $char_t c
     ORDER BY c.$char_resets DESC, c.$char_level DESC, MasterLevel DESC"
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
