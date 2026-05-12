<?php
// =====================================================================
//  Web-Vault market — buy a listing.
//
//  POST: id=<listing id>, char=<seller-side: ignored | for zen: buyer's character>
//
//  Only "balance" currencies (wcoin / zen / usdt) can be bought through
//  the website. Jewel listings show "Contact seller" instead — actually
//  matching jewels between two warehouses safely requires a transactional
//  in-game escrow that is out of scope for this MVP.
//
//  Workflow:
//    1. Refuse listings whose currency is jewel-kind.
//    2. Refuse if the buyer is in-game (vault write race).
//    3. Lock listing row + buyer's warehouse + balance row.
//    4. Atomically debit the buyer using "WHERE balance >= ?" and credit
//       the seller (minus market_fee_pct).
//    5. Move the item_blob into the buyer's first empty vault slot.
//    6. Mark listing 'sold'. COMMIT — or ROLLBACK on any failure.
// =====================================================================
if (!defined("insite")) die("no access");
require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("index.php?m=market");
}

$me      = current_user();
$account = $me["id"];
$id      = (int)($_POST["id"] ?? 0);
$buyer_char_in = trim((string)($_POST["char"] ?? ""));

if ($id <= 0 || !db_table_exists("WebMarketItems")) {
    flash_set("error", lang("market.not_listed"));
    redirect("index.php?m=market");
}

if (rate_limit_hit("market_buy:acc:" . $account, 30, 3600)
    || rate_limit_hit("market_buy:ip:"  . client_ip(), 60, 3600)) {
    flash_set("error", lang("market.no_funds"));
    redirect("index.php?m=market");
}

$row = db_one(
    "SELECT id, seller_account, item_blob, item_name, qty, currency, price, state
       FROM WebMarketItems WHERE id = ?",
    [$id]
);
if (!$row || (string)$row["state"] !== "listed") {
    flash_set("error", lang("market.not_listed"));
    redirect("index.php?m=market");
}
if (strcasecmp((string)$row["seller_account"], $account) === 0) {
    // Refuse self-purchase.
    flash_set("error", lang("market.not_listed"));
    redirect("index.php?m=market");
}

$cur = market_currency((string)$row["currency"]);
if (!$cur || $cur["kind"] !== "balance") {
    flash_set("error", lang("market.cant_buy_jewel"));
    redirect("index.php?m=market");
}
if (!market_currency_available($cur["id"])) {
    flash_set("error", lang("market.no_funds"));
    redirect("index.php?m=market");
}

// Buyer must be offline (warehouse write race protection).
$stat_t = (string)($config["stat_table"]        ?? "MEMB_STAT");
$stat_a = (string)($config["stat_account_col"]  ?? "memb___id");
$stat_c = (string)($config["stat_connect_col"]  ?? "ConnectStat");
if (db_table_exists($stat_t) && db_column_exists($stat_t, $stat_c)) {
    $stq = db_ident($stat_t, "MEMB_STAT");
    $sac = db_ident($stat_a, "memb___id");
    $scc = db_ident($stat_c, "ConnectStat");
    $r = db_one("SELECT TOP 1 $scc AS s FROM $stq WHERE $sac = ?", [$account]);
    if ($r && (int)($r["s"] ?? 0) === 1) {
        flash_set("error", lang("wh.must_be_offline"));
        redirect("index.php?m=market");
    }
}

$wh_slots = max(1, (int)($config["wh_slots"] ?? 120));
$wh_t_raw = (string)($config["wh_table"]       ?? "warehouse");
$wh_i_raw = (string)($config["wh_items_col"]   ?? "Items");
$wh_a_raw = (string)($config["wh_account_col"] ?? "AccountID");
if (!db_table_exists($wh_t_raw)
    || !db_column_exists($wh_t_raw, $wh_i_raw)
    || !db_column_exists($wh_t_raw, $wh_a_raw)) {
    flash_set("error", lang("market.no_funds"));
    redirect("index.php?m=market");
}

// Compute fee-adjusted credit to the seller. Buyer always pays full price.
$fee_pct = (float)($config["market_fee_pct"] ?? 0);
if ($fee_pct < 0) $fee_pct = 0;
if ($fee_pct > 50) $fee_pct = 50;
$price        = (float)$row["price"];
$seller_take  = $price * (1.0 - ($fee_pct / 100.0));

$buy_tq = db_ident($wh_t_raw, "warehouse");
$buy_iq = db_ident($wh_i_raw, "Items");
$buy_aq = db_ident($wh_a_raw, "AccountID");

$bal_t = db_ident((string)$cur["table"], "MEMB_INFO");
$bal_c = db_ident((string)$cur["column"], "credits");
$bal_a = db_ident((string)$cur["acc"], "memb___id");

// For the "zen" currency, the balance lives on Character (per-character),
// so we need the buyer to specify which character to debit. Default to
// the buyer's highest-level character if none provided.
$buyer_char = "";
$seller_char = "";
if (!empty($cur["is_character_balance"])) {
    $char_t  = $config["char_table"]       ?? "Character";
    $char_n  = $config["char_name_col"]    ?? "Name";
    $char_aa = $config["char_account_col"] ?? "AccountID";
    $char_l  = $config["char_level_col"]   ?? "cLevel";
    $char_tq  = db_ident($char_t, "Character");
    $char_nq  = db_ident($char_n, "Name");
    $char_aaq = db_ident($char_aa, "AccountID");
    $char_lq  = db_ident($char_l, "cLevel");

    if ($buyer_char_in !== "" && preg_match('~^[A-Za-z0-9]{1,10}$~', $buyer_char_in)) {
        $r = db_one(
            "SELECT TOP 1 $char_nq AS n FROM $char_tq
              WHERE $char_aaq = ? AND $char_nq = ?",
            [$account, $buyer_char_in]
        );
        if ($r) $buyer_char = (string)$r["n"];
    }
    if ($buyer_char === "") {
        $r = db_one(
            "SELECT TOP 1 $char_nq AS n FROM $char_tq
              WHERE $char_aaq = ? ORDER BY $char_lq DESC",
            [$account]
        );
        if ($r) $buyer_char = (string)$r["n"];
    }
    if ($buyer_char === "") {
        flash_set("error", lang("market.no_funds"));
        redirect("index.php?m=market");
    }
    // Credit the seller's highest-level character.
    $r = db_one(
        "SELECT TOP 1 $char_nq AS n FROM $char_tq
          WHERE $char_aaq = ? ORDER BY $char_lq DESC",
        [(string)$row["seller_account"]]
    );
    if ($r) $seller_char = (string)$r["n"];
}

$ok      = false;
$err_key = "market.no_funds";
db_exec("BEGIN TRANSACTION");
try {
    // Re-lock listing (HOLDLOCK ⇒ KEY-RANGE serializable on this PK).
    $lr = db_one(
        "SELECT id, state FROM WebMarketItems WITH (UPDLOCK, ROWLOCK, HOLDLOCK) WHERE id = ?",
        [$id]
    );
    if (!$lr || (string)$lr["state"] !== "listed") {
        $err_key = "market.not_listed";
        throw new RuntimeException("listing_gone");
    }

    // Lock buyer's warehouse + ensure free slot.
    $wrow = db_one(
        "SELECT $buy_iq AS Items FROM $buy_tq WITH (UPDLOCK, ROWLOCK, HOLDLOCK) WHERE $buy_aq = ?",
        [$account]
    );
    if (!$wrow) {
        // Auto-create an empty warehouse row if the player has none —
        // some emulators only create it on first in-game vault use.
        db_exec(
            "INSERT INTO $buy_tq ($buy_aq, $buy_iq) VALUES (?, CONVERT(varbinary(MAX), ?))",
            [$account, "0x" . str_repeat("FF", $wh_slots * MU_ITEM_BYTES)]
        );
        $wrow = db_one(
            "SELECT $buy_iq AS Items FROM $buy_tq WITH (UPDLOCK, ROWLOCK, HOLDLOCK) WHERE $buy_aq = ?",
            [$account]
        );
        if (!$wrow) throw new RuntimeException("no_buyer_warehouse");
    }
    $buy_blob = $wrow["Items"] ?? "";
    $free = mu_warehouse_first_empty_slot($buy_blob, $wh_slots);
    if ($free < 0) {
        $err_key = "market.no_room";
        throw new RuntimeException("no_room");
    }

    // Debit buyer atomically.
    if (!empty($cur["is_character_balance"])) {
        $debit_ok = db_exec(
            "UPDATE $bal_t SET $bal_c = $bal_c - ? WHERE $bal_a = ? AND ISNULL($bal_c,0) >= ?",
            [$price, $buyer_char, $price]
        );
    } else {
        $debit_ok = db_exec(
            "UPDATE $bal_t SET $bal_c = $bal_c - ? WHERE $bal_a = ? AND ISNULL($bal_c,0) >= ?",
            [$price, $account, $price]
        );
    }
    if (!$debit_ok) throw new RuntimeException("debit_prepare_failed");
    $rc = db_one("SELECT @@ROWCOUNT AS r");
    if (!$rc || (int)$rc["r"] === 0) {
        throw new RuntimeException("insufficient_funds");
    }

    // Credit seller (skip when seller_take rounds to 0 to avoid a no-op write).
    if ($seller_take > 0) {
        if (!empty($cur["is_character_balance"])) {
            if ($seller_char !== "") {
                db_exec(
                    "UPDATE $bal_t SET $bal_c = ISNULL($bal_c,0) + ? WHERE $bal_a = ?",
                    [$seller_take, $seller_char]
                );
            }
        } else {
            db_exec(
                "UPDATE $bal_t SET $bal_c = ISNULL($bal_c,0) + ? WHERE $bal_a = ?",
                [$seller_take, (string)$row["seller_account"]]
            );
        }
    }

    // Move the item into the buyer's vault.
    $bytes = (string)$row["item_blob"];
    if (strlen($bytes) !== MU_ITEM_BYTES) {
        if (strlen($bytes) === MU_ITEM_BYTES * 2 && ctype_xdigit($bytes)) {
            $bytes = hex2bin($bytes);
        } else {
            throw new RuntimeException("bad_blob");
        }
    }
    $new_blob = mu_warehouse_set_slot($buy_blob, $free, $bytes, $wh_slots);
    $upd = db_exec(
        "UPDATE $buy_tq SET $buy_iq = CONVERT(varbinary(MAX), ?) WHERE $buy_aq = ?",
        ["0x" . bin2hex($new_blob), $account]
    );
    if (!$upd) throw new RuntimeException("buyer_warehouse_update_failed");

    // Mark listing sold.
    $sold = db_exec(
        "UPDATE WebMarketItems
            SET state = 'sold', sold_at = GETDATE(), buyer_account = ?
          WHERE id = ? AND state = 'listed'",
        [$account, $id]
    );
    if (!$sold) throw new RuntimeException("sold_update_failed");

    db_exec("COMMIT TRANSACTION");
    $ok = true;

    if (db_table_exists("WebMarketLog")) {
        db_exec(
            "INSERT INTO WebMarketLog (action, listing_id, account, details)
             VALUES ('buy', ?, ?, ?)",
            [$id, $account,
             mb_substr((string)$row["item_name"] . " for " . $price . " " . $cur["id"], 0, 400, "UTF-8")]
        );
    }
    audit_log("market_buy", [
        "id"       => $id,
        "name"     => (string)$row["item_name"],
        "currency" => $cur["id"],
    ]);
} catch (\Throwable $e) {
    db_exec("ROLLBACK TRANSACTION");
    err_log("market_buy", "failed: " . $e->getMessage(), ["id" => $id, "buyer" => $account]);
}

cache_del("warehouse." . strtolower($account));
market_invalidate_listings_cache();

flash_set($ok ? "success" : "error", lang($ok ? "market.bought" : $err_key));
redirect("index.php?m=" . ($ok ? "warehouse" : "market"));
