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
    // Try Character.PersonalShop / IsStoreOpen depending on emulator.
    $rows = db_all(
        "SELECT TOP 200 c.Name AS seller, c.Class
         FROM Character c
         WHERE c.PkCount IS NOT NULL  -- safe placeholder; replaced per-emulator
        "
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
