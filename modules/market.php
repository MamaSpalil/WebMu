<?php
// =====================================================================
//  Market — items that characters have put up for sale, NOT a copy of
//  the player ranking.  Ranking belongs in the Ranking module; Market
//  shows the things that players sell:
//    1) personal-shop items the game writes to a table on the SQL side
//       (e.g. via a server-side export job for PersonalShop slots), or
//    2) Web-Vault listings written by the on-site "Web-Сундук" feature.
//
//  Stock MuOnline Season 3 keeps PersonalShop slots inside the
//  Character.Inventory blob, which is impractical to parse from PHP, so
//  this module degrades gracefully when no items table is configured —
//  it just shows an empty state with a hint for the server admin.
// =====================================================================
if (!defined("insite")) die("no access");

$listings = [];
$market_table = trim((string)($config["market_items_table"] ?? ""));
$has_table = false;

$cached = cache_get("market.listings", 60);
if ($cached !== null) {
    $listings = $cached["listings"] ?? [];
    $has_table = !empty($cached["has_table"]);
} else {
    if ($market_table !== "" && db_table_exists($market_table)) {
        $has_table = true;
        $mt        = db_ident($market_table, "WebMarketItems");
        $col_seller = db_ident($config["market_seller_col"]   ?? "Seller",   "Seller");
        $col_item   = db_ident($config["market_item_col"]     ?? "ItemName", "ItemName");
        $col_price  = db_ident($config["market_price_col"]    ?? "Price",    "Price");
        // Optional columns — only used if they actually exist on the table.
        $market_table_raw = $market_table;
        $extra_select = [];
        $opt = [
            "Currency"  => "Currency",
            "ItemImage" => "ItemImage",
            "ItemLevel" => "ItemLevel",
            "Quantity"  => "Quantity",
            "ListedAt"  => "ListedAt",
            "Source"    => "Source",   // "PShop" | "WebVault"
        ];
        foreach ($opt as $alias => $col) {
            if (db_column_exists($market_table_raw, $col)) {
                $extra_select[$alias] = db_ident($col) . " AS " . db_ident($alias);
            }
        }
        $extra_sql = $extra_select ? ", " . implode(", ", $extra_select) : "";

        $rows = db_all(
            "SELECT TOP 200 $col_seller AS Seller, $col_item AS ItemName,
                    ISNULL($col_price,0) AS Price $extra_sql
             FROM $mt
             ORDER BY "
            . (isset($extra_select["ListedAt"]) ? db_ident("ListedAt") . " DESC, " : "")
            . "$col_price DESC"
        );

        foreach ($rows as $r) {
            $listings[] = [
                "seller"   => trim((string)($r["Seller"] ?? "")),
                "item"     => trim((string)($r["ItemName"] ?? "")),
                "price"    => (int)($r["Price"] ?? 0),
                "currency" => trim((string)($r["Currency"] ?? "Zen")),
                "image"    => trim((string)($r["ItemImage"] ?? "")),
                "level"    => isset($r["ItemLevel"]) ? (int)$r["ItemLevel"] : null,
                "qty"      => isset($r["Quantity"])  ? max(1, (int)$r["Quantity"]) : 1,
                "source"   => trim((string)($r["Source"] ?? "")),
            ];
        }
    }

    cache_set("market.listings", [
        "listings"  => $listings,
        "has_table" => $has_table,
    ]);
}

render_page("market", [
    "title"        => lang("nav.market"),
    "listings"     => $listings,
    "has_table"    => $has_table,
    "market_table" => $market_table,
]);
