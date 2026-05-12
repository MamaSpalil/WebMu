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

// Rate-limit purchases: at most 20 / hour per IP and per account.
// Stops a runaway client (browser-replay or compromised JS) from
// hammering the debit endpoint while still allowing normal shopping.
if (rate_limit_hit("buy:ip:" . client_ip(), 20, 3600)
    || rate_limit_hit("buy:acc:" . $me["id"], 20, 3600)) {
    flash_set("error", lang("donate.rate_limit"));
    redirect("index.php?m=donate");
}

$item_id = (int)($_POST["item"] ?? 0);
if ($item_id <= 0) {
    flash_set("error", lang("donate.no_item"));
    redirect("index.php?m=donate");
}

// Resolve the item from the shared catalog (DB row or static fallback).
$it = donate_item($item_id);
if (!$it) {
    flash_set("error", lang("donate.no_item"));
    redirect("index.php?m=donate");
}

$cr_t = $config["cr_table"]    ?? "MEMB_INFO";
$cr_c = $config["cr_column"]   ?? "credits";
$cr_a = $config["cr_acc"]      ?? "memb___id";
$wc_t = $config["wcoin_table"] ?? "GameShopPoint";
$wc_c = $config["wcoin_column"]?? "WCoinP";
$wc_a = $config["wcoin_acc"]   ?? "AccountID";
$cr_tq = db_ident($cr_t, "MEMB_INFO");
$cr_cq = db_ident($cr_c, "credits");
$cr_aq = db_ident($cr_a, "memb___id");
$wc_tq = db_ident($wc_t, "GameShopPoint");
$wc_cq = db_ident($wc_c, "WCoinP");
$wc_aq = db_ident($wc_a, "AccountID");

// Atomically deduct using "WHERE balance >= ?" — prevents race & overdraft.
$debited = false;
if ($it["credits"] > 0) {
    if (!db_column_exists($cr_t, $cr_c)) {
        flash_set("error", "Credits column is not configured on this server.");
        redirect("index.php?m=donate");
    }
    $debited = db_exec(
        "UPDATE $cr_tq SET $cr_cq = $cr_cq - ? WHERE $cr_aq = ? AND ISNULL($cr_cq,0) >= ?",
        [$it["credits"], $me["id"], $it["credits"]]
    );
    if ($debited) {
        $r = db_one("SELECT @@ROWCOUNT AS r");
        $debited = $r && (int)$r["r"] > 0;
    }
} elseif ($it["wcoin"] > 0) {
    if (!db_table_exists($wc_t) || !db_column_exists($wc_t, $wc_c)) {
        flash_set("error", "WCoin storage is not available on this server.");
        redirect("index.php?m=donate");
    }
    $debited = db_exec(
        "UPDATE $wc_tq SET $wc_cq = $wc_cq - ? WHERE $wc_aq = ? AND ISNULL($wc_cq,0) >= ?",
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
$log_table = $config["donate_log_table"] ?? "WebDonateLog";
if (db_table_exists($log_table)) {
    $log_tableq = db_ident($log_table, "WebDonateLog");
    db_exec(
        "INSERT INTO $log_tableq (account, item_id, item_name, paid_credits, paid_wcoin, ip, ts)
         VALUES (?, ?, ?, ?, ?, ?, GETDATE())",
        [$me["id"], $item_id, $it["name"], $it["credits"], $it["wcoin"], client_ip()]
    );
}

flash_set("success", lang("donate.bought"));
redirect("index.php?m=donate");
