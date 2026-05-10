<?php
// Donate-shop catalog. Items can come from a WebDonateItems table,
// otherwise fall back to a built-in static list.
if (!defined("insite")) die("no access");

$items = [];
$rows  = db_all("SELECT id, name, image, price_credits, price_wcoin, category FROM WebDonateItems ORDER BY category, id");
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
    // built-in fallback
    $items = [
        ["id"=>1, "name"=>"500 WCoin Pack",  "image"=>"coins.svg",  "credits"=>5,  "wcoin"=>0, "category"=>"wcoin"],
        ["id"=>2, "name"=>"1200 WCoin Pack", "image"=>"coins.svg",  "credits"=>10, "wcoin"=>0, "category"=>"wcoin"],
        ["id"=>3, "name"=>"3000 WCoin Pack", "image"=>"coins.svg",  "credits"=>25, "wcoin"=>0, "category"=>"wcoin"],
        ["id"=>4, "name"=>"VIP Bronze 30d",  "image"=>"shield.svg", "credits"=>8,  "wcoin"=>0, "category"=>"vip"],
        ["id"=>5, "name"=>"VIP Silver 30d",  "image"=>"shield.svg", "credits"=>15, "wcoin"=>0, "category"=>"vip"],
        ["id"=>6, "name"=>"VIP Gold 30d",    "image"=>"donate.svg", "credits"=>25, "wcoin"=>0, "category"=>"vip"],
        ["id"=>7, "name"=>"Demon Pet",       "image"=>"skull.svg",  "credits"=>0,  "wcoin"=>800, "category"=>"cosmetics"],
        ["id"=>8, "name"=>"Name Change",     "image"=>"scroll.svg", "credits"=>0,  "wcoin"=>600, "category"=>"cosmetics"],
        ["id"=>9, "name"=>"Class Reset",     "image"=>"staff.svg",  "credits"=>0,  "wcoin"=>1200,"category"=>"cosmetics"],
    ];
}

// Show user's current balances at the top.
$me = current_user();
$balances = ["credits" => 0, "wcoin" => 0];
if ($me) {
    $cr = db_one("SELECT credits FROM MEMB_INFO WHERE memb___id = ?", [$me["id"]]);
    if ($cr) $balances["credits"] = (int)$cr["credits"];
    $wc_t = $config["wcoin_table"]  ?? "GameShopPoint";
    $wc_c = $config["wcoin_column"] ?? "WCoinP";
    $wc_a = $config["wcoin_acc"]    ?? "AccountID";
    $wc = db_one("SELECT [$wc_c] AS v FROM [$wc_t] WHERE [$wc_a] = ?", [$me["id"]]);
    if ($wc) $balances["wcoin"] = (int)$wc["v"];
}

render_page("donate", [
    "title"    => lang("donate.title"),
    "items"    => $items,
    "balances" => $balances,
]);
