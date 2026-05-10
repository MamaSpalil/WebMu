<?php
// Donate-shop catalog. Items can come from a WebDonateItems table,
// otherwise fall back to a built-in static list.
if (!defined("insite")) die("no access");

$items = [];
$items_table = $config["donate_items_table"] ?? "WebDonateItems";
$rows = [];
if (db_table_exists($items_table)) {
    $items_tableq = db_ident($items_table, "WebDonateItems");
    $rows = db_all("SELECT id, name, image, price_credits, price_wcoin, category FROM $items_tableq ORDER BY category, id");
}
if ($rows) {
    foreach ($rows as $r) {
        $items[] = [
            "id"        => (int)$r["id"],
            "name"      => (string)$r["name"],
            "image"     => (string)($r["image"] ?? "donate.svg"),
            "credits"   => (int)$r["price_credits"],
            "wcoin"     => (int)$r["price_wcoin"],
            "category"  => (string)($r["category"] ?? "wcoin"),
        ];
    }
}
if (!$items) {
    // Built-in fallback (single source of truth in core/catalog.php).
    foreach (donate_catalog_static() as $id => $it) {
        $items[] = ["id" => $id] + $it;
    }
}

// Show user's current balances at the top.
$me = current_user();
$balances = ["credits" => 0, "wcoin" => 0];
if ($me) {
    $cr_t = db_ident($config["cr_table"] ?? "MEMB_INFO", "MEMB_INFO");
    $cr_c = db_ident($config["cr_column"] ?? "credits", "credits");
    $cr_a = db_ident($config["cr_acc"] ?? "memb___id", "memb___id");
    $cr = db_one("SELECT $cr_c AS v FROM $cr_t WHERE $cr_a = ?", [$me["id"]]);
    if ($cr) $balances["credits"] = (int)$cr["v"];
    $wc_t = $config["wcoin_table"]  ?? "GameShopPoint";
    $wc_c = $config["wcoin_column"] ?? "WCoinP";
    $wc_a = $config["wcoin_acc"]    ?? "AccountID";
    $wc_tq = db_ident($wc_t, "GameShopPoint");
    $wc_cq = db_ident($wc_c, "WCoinP");
    $wc_aq = db_ident($wc_a, "AccountID");
    $wc = db_one("SELECT $wc_cq AS v FROM $wc_tq WHERE $wc_aq = ?", [$me["id"]]);
    if ($wc) $balances["wcoin"] = (int)$wc["v"];
}

render_page("donate", [
    "title"    => lang("donate.title"),
    "items"    => $items,
    "balances" => $balances,
]);
