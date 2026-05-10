<?php
// Buy a donate-shop item.
//   POST: item=<id>
// Debits credits or WCoin (whichever the item priced in) and logs the
// purchase to WebDonateLog (if the table exists).  Item delivery to the
// game must be done by a server-side script that watches WebDonateLog;
// emulator-specific details (Items BLOB / T_InGameShop_StorageItem)
// must be configured per-server, so we keep this generic.
if (!defined("insite")) die("no access");
require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("index.php?m=donate");
}
$me = current_user();
$item_id = (int)($_POST["item"] ?? 0);
if ($item_id <= 0) {
    flash_set("error", lang("donate.no_item"));
    redirect("index.php?m=donate");
}

// Reload catalog (mirror modules/donate.php so prices match).
$items = [];
$rows  = db_all("SELECT id, name, price_credits, price_wcoin FROM WebDonateItems WHERE id = ?", [$item_id]);
if ($rows) {
    $r = $rows[0];
    $items[$item_id] = ["name" => $r["name"], "credits" => (int)$r["price_credits"], "wcoin" => (int)$r["price_wcoin"]];
} else {
    // mirror the static fallback in modules/donate.php
    $static = [
        1=>["name"=>"500 WCoin Pack",  "credits"=>5,  "wcoin"=>0],
        2=>["name"=>"1200 WCoin Pack", "credits"=>10, "wcoin"=>0],
        3=>["name"=>"3000 WCoin Pack", "credits"=>25, "wcoin"=>0],
        4=>["name"=>"VIP Bronze 30d",  "credits"=>8,  "wcoin"=>0],
        5=>["name"=>"VIP Silver 30d",  "credits"=>15, "wcoin"=>0],
        6=>["name"=>"VIP Gold 30d",    "credits"=>25, "wcoin"=>0],
        7=>["name"=>"Demon Pet",       "credits"=>0,  "wcoin"=>800],
        8=>["name"=>"Name Change",     "credits"=>0,  "wcoin"=>600],
        9=>["name"=>"Class Reset",     "credits"=>0,  "wcoin"=>1200],
    ];
    if (isset($static[$item_id])) $items[$item_id] = $static[$item_id];
}

if (!isset($items[$item_id])) {
    flash_set("error", lang("donate.no_item"));
    redirect("index.php?m=donate");
}
$it = $items[$item_id];

$cr_t = $config["cr_table"]    ?? "MEMB_INFO";
$cr_c = $config["cr_column"]   ?? "credits";
$cr_a = $config["cr_acc"]      ?? "memb___id";
$wc_t = $config["wcoin_table"] ?? "GameShopPoint";
$wc_c = $config["wcoin_column"]?? "WCoinP";
$wc_a = $config["wcoin_acc"]   ?? "AccountID";

// Atomically deduct using "WHERE balance >= ?" — prevents race & overdraft.
$debited = false;
if ($it["credits"] > 0) {
    $debited = db_exec(
        "UPDATE [$cr_t] SET [$cr_c] = [$cr_c] - ? WHERE [$cr_a] = ? AND ISNULL([$cr_c],0) >= ?",
        [$it["credits"], $me["id"], $it["credits"]]
    );
    if ($debited) {
        $r = db_one("SELECT @@ROWCOUNT AS r");
        $debited = $r && (int)$r["r"] > 0;
    }
} elseif ($it["wcoin"] > 0) {
    $debited = db_exec(
        "UPDATE [$wc_t] SET [$wc_c] = [$wc_c] - ? WHERE [$wc_a] = ? AND ISNULL([$wc_c],0) >= ?",
        [$it["wcoin"], $me["id"], $it["wcoin"]]
    );
    if ($debited) {
        $r = db_one("SELECT @@ROWCOUNT AS r");
        $debited = $r && (int)$r["r"] > 0;
    }
}

if (!$debited) {
    flash_set("error", lang("donate.no_funds"));
    redirect("index.php?m=donate");
}

// Log the order so an in-game delivery service can fulfill it.
db_exec(
    "INSERT INTO WebDonateLog (account, item_id, item_name, paid_credits, paid_wcoin, ip, ts)
     VALUES (?, ?, ?, ?, ?, ?, GETDATE())",
    [$me["id"], $item_id, $it["name"], $it["credits"], $it["wcoin"], client_ip()]
);

flash_set("success", lang("donate.bought"));
redirect("index.php?m=donate");
