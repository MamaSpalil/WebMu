<?php
// =====================================================================
//  VIP exchange — POST endpoint, CSRF-protected via $state_changing.
//
//  POST: pkg=<package id from $config["vip_packages"]>
//
//  Atomically:
//    1. Verifies WebOnlineHours has enough hours (seconds_total -
//       seconds_spent >= pkg.hours * 3600).
//    2. Increments seconds_spent.
//    3. MERGEs into VipList, extending ExpireDate when already active
//       (DATEADD(day, pkg.duration_days, max(GETDATE(), ExpireDate))).
// =====================================================================
if (!defined("insite")) die("no access");
require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("index.php?m=vip");
}
$me      = current_user();
$account = $me["id"];

if (rate_limit_hit("vip_exchange:acc:" . $account, 10, 3600)) {
    flash_set("error", lang("vip.exchange_fail"));
    redirect("index.php?m=vip");
}

$pkg_id = preg_replace('~[^A-Za-z0-9_]~', '', (string)($_POST["pkg"] ?? ""));
$packages = is_array($config["vip_packages"] ?? null) ? $config["vip_packages"] : [];
$pkg = null;
foreach ($packages as $p) {
    if (($p["id"] ?? "") === $pkg_id) { $pkg = $p; break; }
}
if (!$pkg) {
    flash_set("error", lang("vip.exchange_fail"));
    redirect("index.php?m=vip");
}

if (!db_table_exists("WebOnlineHours")
    || !db_table_exists((string)($config["vip_table"] ?? "VipList"))) {
    flash_set("error", lang("vip.not_configured"));
    redirect("index.php?m=vip");
}

$cost_seconds  = max(1, (int)$pkg["hours"]) * 3600;
$duration_days = max(1, (int)$pkg["duration_days"]);
$vip_type      = max(1, min(255, (int)$pkg["vip_type"]));

$ok = false;
db_exec("BEGIN TRANSACTION");
try {
    // Ensure a row exists.
    db_exec(
        "MERGE WebOnlineHours AS t
         USING (SELECT ? AS account) AS s ON t.account = s.account
         WHEN NOT MATCHED THEN
            INSERT (account, seconds_total, seconds_spent, updated_at)
            VALUES (?, 0, 0, GETDATE());",
        [$account, $account]
    );
    // Atomic debit: increment seconds_spent only if balance covers it.
    $debited = db_exec(
        "UPDATE WebOnlineHours WITH (UPDLOCK, ROWLOCK)
            SET seconds_spent = seconds_spent + ?, updated_at = GETDATE()
          WHERE account = ?
            AND (seconds_total - seconds_spent) >= ?",
        [$cost_seconds, $account, $cost_seconds]
    );
    if (!$debited) throw new RuntimeException("debit_prepare_failed");
    $rc = db_one("SELECT @@ROWCOUNT AS r");
    if (!$rc || (int)$rc["r"] === 0) throw new RuntimeException("not_enough_hours");

    // Extend / create the VIP entry.
    $vt = db_ident((string)($config["vip_table"]        ?? "VipList"), "VipList");
    $va = db_ident((string)($config["vip_account_col"]  ?? "AccountID"), "AccountID");
    $vy = db_ident((string)($config["vip_type_col"]     ?? "VipType"),   "VipType");
    $ve = db_ident((string)($config["vip_expire_col"]   ?? "ExpireDate"),"ExpireDate");

    $merged = db_exec(
        "MERGE $vt AS t
         USING (SELECT ? AS acc) AS s ON t.$va = s.acc
         WHEN MATCHED THEN UPDATE
            SET $vy = ?,
                $ve = DATEADD(day, ?,
                              CASE WHEN t.$ve > GETDATE() THEN t.$ve ELSE GETDATE() END),
                GrantedBy = 'vip_exchange'
         WHEN NOT MATCHED THEN
            INSERT ($va, $vy, $ve, GrantedBy)
            VALUES (?, ?, DATEADD(day, ?, GETDATE()), 'vip_exchange');",
        [$account, $vip_type, $duration_days, $account, $vip_type, $duration_days]
    );
    if (!$merged) throw new RuntimeException("vip_merge_failed");

    db_exec("COMMIT TRANSACTION");
    $ok = true;
    audit_log("vip_exchange", [
        "pkg"      => $pkg_id,
        "vip_type" => $vip_type,
        "days"     => $duration_days,
    ]);
} catch (\Throwable $e) {
    db_exec("ROLLBACK TRANSACTION");
    err_log("vip_exchange", "failed: " . $e->getMessage(), ["pkg" => $pkg_id]);
}

flash_set($ok ? "success" : "error", lang($ok ? "vip.exchange_ok" : "vip.exchange_fail"));
redirect("index.php?m=vip");
