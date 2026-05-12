<?php
// =====================================================================
//  Web-Vault market — cancel an active listing.
//
//  POST: id=<listing id>
//
//  Returns the reserved item back to the seller's warehouse (first
//  empty slot found, NOT necessarily the original wh_slot — the player
//  may have re-arranged the vault while offline). Marks the row
//  state='cancelled'. Idempotent: a re-submit of an already-cancelled
//  listing is a no-op.
// =====================================================================
if (!defined("insite")) die("no access");
require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("index.php?m=warehouse");
}

$me      = current_user();
$account = $me["id"];
$id      = (int)($_POST["id"] ?? 0);
if ($id <= 0 || !db_table_exists("WebMarketItems")) {
    flash_set("error", lang("market.not_listed"));
    redirect("index.php?m=warehouse");
}

// Allow either the seller OR an admin to cancel.
$is_admin = function_exists("is_admin") && is_admin();

$row = db_one(
    "SELECT id, seller_account, wh_slot, item_blob, item_name, state
       FROM WebMarketItems WHERE id = ?",
    [$id]
);
if (!$row) {
    flash_set("error", lang("market.not_listed"));
    redirect("index.php?m=warehouse");
}
if (!$is_admin && strcasecmp((string)$row["seller_account"], $account) !== 0) {
    http_response_code(403);
    flash_set("error", lang("market.not_listed"));
    redirect("index.php?m=warehouse");
}
if ((string)$row["state"] !== "listed") {
    flash_set("error", lang("market.not_listed"));
    redirect("index.php?m=warehouse");
}

$seller   = (string)$row["seller_account"];
$wh_slots = max(1, (int)($config["wh_slots"] ?? 120));
$wh_t_raw = (string)($config["wh_table"]       ?? "warehouse");
$wh_i_raw = (string)($config["wh_items_col"]   ?? "Items");
$wh_a_raw = (string)($config["wh_account_col"] ?? "AccountID");

$ok = false;
db_exec("BEGIN TRANSACTION");
try {
    if (!db_table_exists($wh_t_raw)
        || !db_column_exists($wh_t_raw, $wh_i_raw)
        || !db_column_exists($wh_t_raw, $wh_a_raw)) {
        throw new RuntimeException("warehouse_unconfigured");
    }
    $tq = db_ident($wh_t_raw, "warehouse");
    $iq = db_ident($wh_i_raw, "Items");
    $aq = db_ident($wh_a_raw, "AccountID");

    // Lock seller's warehouse.
    $wrow = db_one(
        "SELECT $iq AS Items FROM $tq WITH (UPDLOCK, ROWLOCK, HOLDLOCK) WHERE $aq = ?",
        [$seller]
    );
    if (!$wrow) {
        throw new RuntimeException("no_warehouse_row");
    }
    $blob = $wrow["Items"] ?? "";
    $free = mu_warehouse_first_empty_slot($blob, $wh_slots);
    if ($free < 0) {
        throw new RuntimeException("no_room");
    }
    $bytes = (string)$row["item_blob"]; // varbinary(MU_ITEM_BYTES) — driver returns binary
    if (strlen($bytes) !== MU_ITEM_BYTES) {
        // Some ODBC drivers return hex-string for varbinary.
        if (strlen($bytes) === MU_ITEM_BYTES * 2 && ctype_xdigit($bytes)) {
            $bytes = hex2bin($bytes);
        } else {
            throw new RuntimeException("bad_blob");
        }
    }
    $new_blob = mu_warehouse_set_slot($blob, $free, $bytes, $wh_slots);
    $upd = db_exec(
        "UPDATE $tq SET $iq = CONVERT(varbinary(MAX), ?) WHERE $aq = ?",
        ["0x" . bin2hex($new_blob), $seller]
    );
    if (!$upd) throw new RuntimeException("update_failed");

    $upd2 = db_exec(
        "UPDATE WebMarketItems SET state = 'cancelled' WHERE id = ? AND state = 'listed'",
        [$id]
    );
    if (!$upd2) throw new RuntimeException("update_listing_failed");

    db_exec("COMMIT TRANSACTION");
    $ok = true;

    if (db_table_exists("WebMarketLog")) {
        db_exec(
            "INSERT INTO WebMarketLog (action, listing_id, account, details)
             VALUES (?, ?, ?, ?)",
            [$is_admin ? "admin_cancel" : "cancel", $id, $account,
             mb_substr((string)$row["item_name"], 0, 400, "UTF-8")]
        );
    }
    audit_log($is_admin ? "market_admin_cancel" : "market_cancel", [
        "id"   => $id,
        "name" => (string)$row["item_name"],
    ]);
} catch (\Throwable $e) {
    db_exec("ROLLBACK TRANSACTION");
    err_log("market_cancel", "failed: " . $e->getMessage(), ["id" => $id]);
    if ($e->getMessage() === "no_room") {
        flash_set("error", lang("wh.cancel_no_room"));
    }
}

cache_del("warehouse." . strtolower($seller));
market_invalidate_listings_cache();

if ($ok) flash_set("success", lang("wh.cancel_ok"));
redirect("index.php?m=" . ($is_admin ? "admin&sub=market" : "warehouse"));
