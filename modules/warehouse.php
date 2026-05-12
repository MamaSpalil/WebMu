<?php
// =====================================================================
//  Web-Сундук — read-only view of the player's in-game warehouse.
//
//  Stock MuOnline Season 3 keeps the warehouse in `warehouse.Items`
//  (varbinary, 120 slots × 16 bytes) keyed by AccountID. Table/column
//  names come from $config["wh_*"] so custom emulators can remap them.
//
//  This module is read-only; the actual "put up for sale" action is
//  handled by modules/market_list.php so the state-changing endpoint
//  stays distinct (CSRF-checked in index.php).
// =====================================================================
if (!defined("insite")) die("no access");
require_login();

$me      = current_user();
$account = $me["id"];

$wh_t_raw  = (string)($config["wh_table"]       ?? "warehouse");
$wh_i_raw  = (string)($config["wh_items_col"]   ?? "Items");
$wh_a_raw  = (string)($config["wh_account_col"] ?? "AccountID");
$wh_m_raw  = (string)($config["wh_money_col"]   ?? "Money");
$wh_slots  = max(1, (int)($config["wh_slots"]   ?? 120));
$wh_cols   = max(1, (int)($config["wh_cols"]    ?? 8));

$has_table = ($wh_t_raw !== "" && $wh_i_raw !== "" && $wh_a_raw !== ""
              && db_table_exists($wh_t_raw)
              && db_column_exists($wh_t_raw, $wh_i_raw)
              && db_column_exists($wh_t_raw, $wh_a_raw));

$slots = array_fill(0, $wh_slots, ["empty" => true, "name" => "Empty", "image" => ""]);
$money = null;
$is_offline = true;

if ($has_table) {
    $cache_key = "warehouse." . strtolower($account);
    $cached = cache_get($cache_key, 30);
    if ($cached !== null) {
        $slots      = $cached["slots"]      ?? $slots;
        $money      = $cached["money"]      ?? null;
        $is_offline = $cached["is_offline"] ?? true;
    } else {
        $tq = db_ident($wh_t_raw, "warehouse");
        $iq = db_ident($wh_i_raw, "Items");
        $aq = db_ident($wh_a_raw, "AccountID");
        $select = "$iq AS Items";
        $has_money = ($wh_m_raw !== "" && db_column_exists($wh_t_raw, $wh_m_raw));
        if ($has_money) {
            $select .= ", " . db_ident($wh_m_raw, "Money") . " AS Money";
        }
        $row = db_one("SELECT TOP 1 $select FROM $tq WHERE $aq = ?", [$account]);
        if ($row) {
            $slots = mu_parse_warehouse_blob($row["Items"] ?? "", $wh_slots);
            if ($has_money && isset($row["Money"])) $money = (int)$row["Money"];
        }

        // Determine if the account is currently in-game (used by the
        // market_list endpoint to refuse modifications during a session).
        $stat_t = (string)($config["stat_table"]        ?? "MEMB_STAT");
        $stat_a = (string)($config["stat_account_col"]  ?? "memb___id");
        $stat_c = (string)($config["stat_connect_col"]  ?? "ConnectStat");
        if (db_table_exists($stat_t) && db_column_exists($stat_t, $stat_c)) {
            $stq = db_ident($stat_t, "MEMB_STAT");
            $sac = db_ident($stat_a, "memb___id");
            $scc = db_ident($stat_c, "ConnectStat");
            $r = db_one("SELECT TOP 1 $scc AS s FROM $stq WHERE $sac = ?", [$account]);
            if ($r) $is_offline = ((int)($r["s"] ?? 0) !== 1);
        }

        cache_set($cache_key, [
            "slots"      => $slots,
            "money"      => $money,
            "is_offline" => $is_offline,
        ]);
    }
}

audit_log("warehouse_view");

// Currencies — only those actually available on this install.
$currencies = [];
foreach (market_currencies() as $c) {
    if ($c["kind"] === "balance" && !market_currency_available($c["id"])) continue;
    $currencies[] = $c;
}

// Active listings owned by this user (so the warehouse view can show
// which slots are currently reserved, with a Cancel button).
$my_listings = [];
if (db_table_exists("WebMarketItems")) {
    $my_listings = db_all(
        "SELECT id, item_name, item_level, qty, currency, price, listed_at
         FROM WebMarketItems
         WHERE seller_account = ? AND state = 'listed'
         ORDER BY listed_at DESC",
        [$account]
    );
}

render_page("warehouse", [
    "title"        => lang("wh.title"),
    "has_table"    => $has_table,
    "slots"        => $slots,
    "wh_cols"      => $wh_cols,
    "wh_slots"     => $wh_slots,
    "money"        => $money,
    "is_offline"   => $is_offline,
    "currencies"   => $currencies,
    "my_listings"  => $my_listings,
]);
