<?php
// Personal-shop market listing.  Reads characters that have opened
// PersonalShop and shows the price of slot 1 (best-effort).  Schemas
// vary widely; we degrade gracefully if columns are missing.
if (!defined("insite")) die("no access");

$listings = [];
$cached = cache_get("market.listings", 60);
if ($cached !== null) {
    $listings = $cached;
} else {
    $char_t = db_ident($config["char_table"] ?? "Character", "Character");
    $char_name = db_ident($config["char_name_col"] ?? "Name", "Name");
    $char_level = db_ident($config["char_level_col"] ?? "cLevel", "cLevel");
    $char_resets = db_ident($config["char_resets_col"] ?? "Resets", "Resets");
    $char_class = db_ident($config["char_class_col"] ?? "Class", "Class");
    $open_cfg = trim((string)($config["market_open_col"] ?? ""));
    $where = "1=1";
    if ($open_cfg !== "") {
        $open_col = db_ident($open_cfg);
        if ($open_col) $where = "c.$open_col = 1";
    }
    $rows = db_all(
        "SELECT TOP 200 c.$char_name AS seller, c.$char_level AS cLevel,
                c.$char_resets AS Resets, c.$char_class AS Class
         FROM $char_t c
         WHERE $where
         ORDER BY c.$char_resets DESC, c.$char_level DESC"
    );
    foreach ($rows as $r) {
        $listings[] = [
            "seller" => trim((string)($r["seller"] ?? "")),
            "class"  => mu_class($r["Class"] ?? 0),
            "level"  => (int)($r["cLevel"] ?? 0),
            "resets" => (int)($r["Resets"] ?? 0),
        ];
    }
    cache_set("market.listings", $listings);
}

render_page("market", [
    "title"    => lang("nav.market"),
    "listings" => $listings,
]);
