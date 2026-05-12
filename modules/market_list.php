<?php
// =====================================================================
//  Web-Vault market — list an item from the player's warehouse.
//
//  POST: wh_slot=<int>, currency=<id>, price=<num>, qty=<int>
//
//  Workflow (best-effort transaction):
//    1. Validate inputs and currency.
//    2. Refuse if the account is currently in-game (race with GameServer).
//    3. Read warehouse blob WITH (UPDLOCK,ROWLOCK,HOLDLOCK).
//    4. Extract the 16 bytes for the slot — refuse if empty.
//    5. INSERT into WebMarketItems (item_blob = those 16 bytes + decoded
//       name/level/exc/luck/skill/opt for display).
//    6. UPDATE the warehouse blob with that slot zeroed (16 × 0xFF).
//    7. COMMIT — or ROLLBACK on any failure.
// =====================================================================
if (!defined("insite")) die("no access");
require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("index.php?m=warehouse");
}
$me      = current_user();
$account = $me["id"];

// ---- input validation -------------------------------------------------
$slot     = (int)($_POST["wh_slot"] ?? -1);
$currency = strtolower(preg_replace('~[^a-z]~', '', (string)($_POST["currency"] ?? "")));
$price    = (string)($_POST["price"] ?? "");
$qty      = max(1, (int)($_POST["qty"] ?? 1));
$qty      = min(255, $qty);

$wh_slots = max(1, (int)($config["wh_slots"] ?? 120));
if ($slot < 0 || $slot >= $wh_slots) {
    flash_set("error", lang("wh.invalid_slot"));
    redirect("index.php?m=warehouse");
}

$cur = market_currency($currency);
if (!$cur) {
    flash_set("error", lang("wh.list_fail"));
    redirect("index.php?m=warehouse");
}
if ($cur["kind"] === "balance" && !market_currency_available($cur["id"])) {
    flash_set("error", lang("wh.list_fail"));
    redirect("index.php?m=warehouse");
}

// Price: positive, within configured cap, integer for jewel/wcoin.
if (!is_numeric($price) || (float)$price <= 0) {
    flash_set("error", lang("wh.list_fail"));
    redirect("index.php?m=warehouse");
}
$price_f = (float)$price;
$max_price = (float)($config["market_max_price"] ?? 99999999);
if ($price_f > $max_price) {
    flash_set("error", lang("wh.list_fail"));
    redirect("index.php?m=warehouse");
}
if (!empty($cur["int_price"]) && $price_f != (int)$price_f) {
    $price_f = (float)(int)$price_f;
}

// ---- per-seller quota -------------------------------------------------
$max_listings = (int)($config["market_max_listings_per_seller"] ?? 50);
if ($max_listings > 0 && db_table_exists("WebMarketItems")) {
    $r = db_one(
        "SELECT COUNT(*) AS c FROM WebMarketItems WHERE seller_account = ? AND state = 'listed'",
        [$account]
    );
    if ($r && (int)$r["c"] >= $max_listings) {
        flash_set("error", lang("wh.list_fail"));
        redirect("index.php?m=warehouse");
    }
}

// ---- rate limit -------------------------------------------------------
if (rate_limit_hit("market_list:acc:" . $account, 30, 3600)
    || rate_limit_hit("market_list:ip:"  . client_ip(), 60, 3600)) {
    flash_set("error", lang("wh.list_fail"));
    redirect("index.php?m=warehouse");
}

// ---- gate: warehouse must be configured + WebMarketItems must exist --
$wh_t_raw = (string)($config["wh_table"]       ?? "warehouse");
$wh_i_raw = (string)($config["wh_items_col"]   ?? "Items");
$wh_a_raw = (string)($config["wh_account_col"] ?? "AccountID");
if ($wh_t_raw === "" || !db_table_exists($wh_t_raw)
    || !db_column_exists($wh_t_raw, $wh_i_raw)
    || !db_column_exists($wh_t_raw, $wh_a_raw)
    || !db_table_exists("WebMarketItems")) {
    flash_set("error", lang("wh.list_fail"));
    redirect("index.php?m=warehouse");
}

// ---- account must be offline -----------------------------------------
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
        redirect("index.php?m=warehouse");
    }
}

// ---- transactional move: warehouse → WebMarketItems ------------------
$tq = db_ident($wh_t_raw, "warehouse");
$iq = db_ident($wh_i_raw, "Items");
$aq = db_ident($wh_a_raw, "AccountID");

$ok = false;
db_exec("BEGIN TRANSACTION");
try {
    // Lock the warehouse row for the duration of the transaction.
    $row = db_one(
        "SELECT $iq AS Items FROM $tq WITH (UPDLOCK, ROWLOCK, HOLDLOCK) WHERE $aq = ?",
        [$account]
    );
    if (!$row) {
        throw new RuntimeException("no_warehouse_row");
    }

    $bytes = mu_warehouse_get_slot($row["Items"] ?? "", $slot, $wh_slots);
    if ($bytes === "") {
        throw new RuntimeException("slot_empty");
    }

    // Decode for display columns.
    $b1 = ord($bytes[1]);
    $level = ($b1 >> 3) & 0x0F;
    $skill = (bool)($b1 & 0x80);
    $luck  = (bool)($b1 & 0x04);
    $opt   = $b1 & 0x03;
    $exc   = ord($bytes[7]) & MU_EXCELLENT_OPTION_MASK;
    $id    = mu_decode_item_identity($bytes);
    $name  = mu_item_name($id["group"], $id["code"]);
    $img   = function_exists("mu_item_image")
        ? mu_item_image($id["group"], $id["code"], $level)
        : "";

    // Insert listing first (so failure leaves the warehouse intact).
    $ins = db_exec(
        "INSERT INTO WebMarketItems
            (seller_account, seller_char, wh_slot, item_blob, item_name, item_image,
             item_level, item_exc, item_luck, item_skill, item_opt, qty,
             currency, price, state, listed_at)
         VALUES (?, NULL, ?, CONVERT(varbinary(" . MU_ITEM_BYTES . "), ?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'listed', GETDATE())",
        [
            $account, $slot, "0x" . bin2hex($bytes), $name, $img,
            $level, $exc, $luck ? 1 : 0, $skill ? 1 : 0, $opt, $qty,
            $cur["id"], $price_f,
        ]
    );
    if (!$ins) throw new RuntimeException("insert_failed");

    // Wipe the slot in the warehouse blob.
    $new_blob = mu_warehouse_set_slot($row["Items"] ?? "", $slot, "", $wh_slots);
    $upd = db_exec(
        "UPDATE $tq SET $iq = CONVERT(varbinary(MAX), ?) WHERE $aq = ?",
        ["0x" . bin2hex($new_blob), $account]
    );
    if (!$upd) throw new RuntimeException("update_failed");

    db_exec("COMMIT TRANSACTION");
    $ok = true;

    if (db_table_exists("WebMarketLog")) {
        db_exec(
            "INSERT INTO WebMarketLog (action, account, details)
             VALUES ('list', ?, ?)",
            [$account, mb_substr($name . " ×" . $qty . " for " . $price_f . " " . $cur["id"], 0, 400, "UTF-8")]
        );
    }
    audit_log("market_list", [
        "name"     => $name,
        "qty"      => $qty,
        "currency" => $cur["id"],
    ]);
} catch (\Throwable $e) {
    db_exec("ROLLBACK TRANSACTION");
    err_log("market_list", "failed: " . $e->getMessage(), ["account" => $account, "slot" => $slot]);
}

cache_del("warehouse." . strtolower($account));
market_invalidate_listings_cache();

flash_set($ok ? "success" : "error", lang($ok ? "wh.list_ok" : "wh.list_fail"));
redirect("index.php?m=warehouse");
