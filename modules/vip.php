<?php
// =====================================================================
//  VIP for online time — Stage 3 of the User Panel rework.
//
//  Reads the player's accumulated online seconds from WebOnlineHours
//  (idempotent table; see docs/schema_addons.sql), and shows the VIP
//  packages declared in $config["vip_packages"]. Each package has:
//    id, name, hours (cost), vip_type, duration_days, perks{}.
//
//  Throttled accumulation: each request bumps WebOnlineHours.seconds_total
//  for every account that is currently online — at most once per
//  $config["vip_hours_throttle_sec"] seconds, capped per tick by
//  $config["vip_hours_max_step_sec"] to avoid huge jumps after PHP
//  downtime.
// =====================================================================
if (!defined("insite")) die("no access");
require_login();

$me      = current_user();
$account = $me["id"];

$packages    = is_array($config["vip_packages"] ?? null) ? $config["vip_packages"] : [];
$has_hours   = db_table_exists("WebOnlineHours");
$has_viplist = db_table_exists((string)($config["vip_table"] ?? "VipList"));

// ---- Throttled online-hours accumulation -----------------------------
if ($has_hours) {
    $throttle = max(0, (int)($config["vip_hours_throttle_sec"] ?? 300));
    $cap_step = max(60, (int)($config["vip_hours_max_step_sec"] ?? 1800));
    if ($throttle > 0) {
        $marker = ($config["__cache"] ?? sys_get_temp_dir()) . "/vip_hours.tick";
        $now    = time();
        $last   = is_file($marker) ? (int)@filemtime($marker) : 0;
        if (($now - $last) >= $throttle) {
            @file_put_contents($marker, "x", LOCK_EX);
            $stat_t = (string)($config["stat_table"]        ?? "MEMB_STAT");
            $stat_a = (string)($config["stat_account_col"]  ?? "memb___id");
            $stat_c = (string)($config["stat_connect_col"]  ?? "ConnectStat");
            if (db_table_exists($stat_t) && db_column_exists($stat_t, $stat_c)) {
                $stq = db_ident($stat_t, "MEMB_STAT");
                $sac = db_ident($stat_a, "memb___id");
                $scc = db_ident($stat_c, "ConnectStat");
                // For every currently-online account, bump seconds_total by
                // min(elapsed_since_last_seen_online, cap_step). MERGE
                // ensures a row exists per account.
                $online = db_all("SELECT $sac AS a FROM $stq WHERE $scc = 1");
                foreach ($online as $row) {
                    $acc = trim((string)$row["a"]);
                    if ($acc === "") continue;
                    db_exec(
                        "MERGE WebOnlineHours AS t
                         USING (SELECT ? AS account) AS s ON t.account = s.account
                         WHEN MATCHED THEN UPDATE SET
                            seconds_total = seconds_total +
                                CASE
                                  WHEN t.last_seen_online IS NULL THEN ?
                                  WHEN DATEDIFF(SECOND, t.last_seen_online, GETDATE()) > ? THEN ?
                                  ELSE DATEDIFF(SECOND, t.last_seen_online, GETDATE())
                                END,
                            last_seen_online = GETDATE(),
                            updated_at = GETDATE()
                         WHEN NOT MATCHED THEN
                            INSERT (account, seconds_total, seconds_spent, last_seen_online, updated_at)
                            VALUES (?, ?, 0, GETDATE(), GETDATE());",
                        [$acc, $cap_step, $cap_step, $cap_step, $acc, $cap_step]
                    );
                }
            }
        }
    }
}

// ---- Read this user's hour bank --------------------------------------
$seconds_total = 0;
$seconds_spent = 0;
if ($has_hours) {
    $r = db_one(
        "SELECT seconds_total, seconds_spent FROM WebOnlineHours WHERE account = ?",
        [$account]
    );
    if ($r) {
        $seconds_total = (int)$r["seconds_total"];
        $seconds_spent = (int)$r["seconds_spent"];
    }
}
$hours_available = (int)floor(max(0, $seconds_total - $seconds_spent) / 3600);
$hours_spent     = (int)floor($seconds_spent / 3600);

// ---- Read current VIP -----------------------------------------------
$vip_now = null;
if ($has_viplist) {
    $vt = db_ident((string)($config["vip_table"]        ?? "VipList"), "VipList");
    $va = db_ident((string)($config["vip_account_col"]  ?? "AccountID"), "AccountID");
    $vy = db_ident((string)($config["vip_type_col"]     ?? "VipType"),   "VipType");
    $ve = db_ident((string)($config["vip_expire_col"]   ?? "ExpireDate"),"ExpireDate");
    $r = db_one(
        "SELECT TOP 1 $vy AS vt, $ve AS ed FROM $vt WHERE $va = ? AND $ve > GETDATE()",
        [$account]
    );
    if ($r) {
        $vip_now = [
            "type"   => (int)$r["vt"],
            "expire" => (string)$r["ed"],
        ];
    }
}

render_page("vip", [
    "title"            => lang("vip.title"),
    "hours_available"  => $hours_available,
    "hours_spent"      => $hours_spent,
    "packages"         => $packages,
    "vip_now"          => $vip_now,
    "has_hours"        => $has_hours,
    "has_viplist"      => $has_viplist,
]);
