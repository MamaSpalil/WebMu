<?php
// =====================================================================
//  Market — items players have put up for sale.
//
//  Two data sources, in this order of preference:
//    1) WebMarketItems   — built-in Web-Vault listings created via the
//       on-site "Put up for sale" form (modules/market_list.php).
//    2) The legacy table named in $config["market_items_table"] — for
//       installations that import PersonalShop slots from the game
//       server (Seller, ItemName, Price, ...).
//
//  Both are merged in a uniform $listings array for the template.
// =====================================================================
if (!defined("insite")) die("no access");

$listings = [];
$has_table = false;

$me = current_user();
$me_id = $me ? (string)$me["id"] : "";

// Filter: ?currency=wcoin etc.
$filter_cur = strtolower(preg_replace('~[^a-z]~', '', (string)($_GET["currency"] ?? "")));

$cache_key = "market.listings." . $filter_cur;
$cached = cache_get($cache_key, 30);
if ($cached !== null) {
    $listings  = $cached["listings"]  ?? [];
    $has_table = !empty($cached["has_table"]);
} else {

    // ---- 1) Built-in WebMarketItems --------------------------------------
    if (db_table_exists("WebMarketItems")) {
        $has_table = true;
        $sql = "SELECT id, seller_account, seller_char, item_name, item_image,
                       item_level, item_exc, item_luck, item_skill, item_opt,
                       qty, currency, price, listed_at
                FROM WebMarketItems
                WHERE state = 'listed'";
        $args = [];
        if ($filter_cur !== "") {
            $sql .= " AND currency = ?";
            $args[] = $filter_cur;
        }
        $sql .= " ORDER BY listed_at DESC";
        $rows = db_all($sql, $args);
        foreach ($rows as $r) {
            $cur = market_currency((string)$r["currency"]);
            $listings[] = [
                "id"        => (int)$r["id"],
                "source"    => "WebVault",
                "seller"    => trim((string)($r["seller_char"] ?? $r["seller_account"])),
                "seller_account" => trim((string)$r["seller_account"]),
                "item"      => trim((string)$r["item_name"]),
                "image"     => trim((string)$r["item_image"]),
                "level"     => (int)$r["item_level"],
                "exc"       => (int)$r["item_exc"],
                "luck"      => (int)$r["item_luck"] === 1,
                "skill"     => (int)$r["item_skill"] === 1,
                "qty"       => max(1, (int)$r["qty"]),
                "currency"  => (string)$r["currency"],
                "currency_label" => $cur ? (string)$cur["label"] : (string)$r["currency"],
                "currency_kind"  => $cur ? (string)$cur["kind"]  : "balance",
                "price"     => (float)$r["price"],
                "listed_at" => (string)$r["listed_at"],
            ];
        }
    }

    // ---- 2) Legacy market_items_table ------------------------------------
    $market_table = trim((string)($config["market_items_table"] ?? ""));
    if ($market_table !== "" && $market_table !== "WebMarketItems"
        && db_table_exists($market_table)) {
        $has_table = true;
        $mt        = db_ident($market_table, "WebMarketItems");
        $col_seller = db_ident($config["market_seller_col"]   ?? "Seller",   "Seller");
        $col_item   = db_ident($config["market_item_col"]     ?? "ItemName", "ItemName");
        $col_price  = db_ident($config["market_price_col"]    ?? "Price",    "Price");
        $extra_select = [];
        $opt = [
            "Currency"  => "Currency",
            "ItemImage" => "ItemImage",
            "ItemLevel" => "ItemLevel",
            "Quantity"  => "Quantity",
            "ListedAt"  => "ListedAt",
            "Source"    => "Source",
        ];
        foreach ($opt as $alias => $col) {
            if (db_column_exists($market_table, $col)) {
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
                "id"        => 0,
                "source"    => trim((string)($r["Source"] ?? "PShop")),
                "seller"    => trim((string)($r["Seller"] ?? "")),
                "seller_account" => "",
                "item"      => trim((string)($r["ItemName"] ?? "")),
                "image"     => trim((string)($r["ItemImage"] ?? "")),
                "level"     => isset($r["ItemLevel"]) ? (int)$r["ItemLevel"] : 0,
                "exc"       => 0,
                "luck"      => false,
                "skill"     => false,
                "qty"       => isset($r["Quantity"]) ? max(1, (int)$r["Quantity"]) : 1,
                "currency"  => trim((string)($r["Currency"] ?? "Zen")),
                "currency_label" => trim((string)($r["Currency"] ?? "Zen")),
                "currency_kind"  => "balance",
                "price"     => (float)($r["Price"] ?? 0),
                "listed_at" => trim((string)($r["ListedAt"] ?? "")),
            ];
        }
    }

    cache_set($cache_key, [
        "listings"  => $listings,
        "has_table" => $has_table,
    ]);
}

render_page("market", [
    "title"        => lang("market.title", "Market"),
    "listings"     => $listings,
    "has_table"    => $has_table,
    "filter_cur"   => $filter_cur,
    "currencies"   => market_currencies(),
    "me_id"        => $me_id,
]);
