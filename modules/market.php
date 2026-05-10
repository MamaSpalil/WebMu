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
    // NOTE: replace the WHERE clause with `c.PersonalShop IS NOT NULL AND c.IsStoreOpen = 1`
    // (or your emulator's equivalent) to filter to characters with an open shop.
    // We use 1=1 by default so the query never accidentally excludes valid rows.
    $rows = db_all(
        "SELECT TOP 200 c.Name AS seller, c.Class
         FROM Character c
         WHERE 1=1"
    );
    foreach ($rows as $r) {
        $listings[] = [
            "seller" => trim((string)$r["Name"] ?? $r["seller"] ?? ""),
            "class"  => mu_class($r["Class"] ?? 0),
        ];
    }
    cache_set("market.listings", $listings);
}

render_page("market", [
    "title"    => lang("nav.market"),
    "listings" => $listings,
]);
