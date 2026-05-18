<?php
// =====================================================================
//  Admin panel — single-controller dispatcher (?m=admin&sub=...).
//
//  Sub-pages:
//    dashboard (default) — counters
//    users     — search, view balances, adjust
//    warehouse — view any account's vault (read-only)
//    market    — moderate active listings
//    vip       — grant/revoke VIP manually
//    log       — paginated webmu_log
//
//  Authorization: $config["admin_accounts"] whitelist OR
//                 MEMB_INFO.is_admin = 1 (when the column exists).
//
//  All POST actions are dispatched here (CSRF + state_changing in
//  index.php still apply because index.php registers ?m=admin in
//  $state_changing, see below).
// =====================================================================
if (!defined("insite")) die("no access");
require_admin();

$sub = preg_replace('~[^a-z_]~', '', strtolower((string)($_GET["sub"] ?? "dashboard")));
if ($sub === "") $sub = "dashboard";

$me = current_user();

// ----------------------------------------------------------------------
// POST actions — keep them small and inline so ?m=admin owns its CSRF.
// ----------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = preg_replace('~[^a-z_]~', '', strtolower((string)($_POST["action"] ?? "")));

    if ($action === "adjust_balance") {
        $target  = trim((string)($_POST["account"] ?? ""));
        $kind    = preg_replace('~[^a-z]~', '', strtolower((string)($_POST["kind"] ?? "")));
        $delta_s = trim((string)($_POST["delta"] ?? "0"));
        if (!preg_match('~^-?\d+(\.\d+)?$~', $delta_s)) $delta_s = "0";
        $delta   = (float)$delta_s;
        $map = [
            "credits" => [$config["cr_table"]    ?? "MEMB_INFO",     $config["cr_column"]    ?? "credits", $config["cr_acc"]    ?? "memb___id"],
            "wcoin"   => [$config["wcoin_table"] ?? "GameShopPoint", $config["wcoin_column"] ?? "WCoinP",  $config["wcoin_acc"] ?? "AccountID"],
            "usdt"    => [$config["usdt_table"]  ?? "MEMB_INFO",     $config["usdt_column"]  ?? "usdt",    $config["usdt_acc"]  ?? "memb___id"],
        ];
        if ($target !== "" && isset($map[$kind]) && abs($delta) > 0) {
            [$t, $c, $a] = $map[$kind];
            if (db_table_exists($t) && db_column_exists($t, $c)) {
                $tq = db_ident($t); $cq = db_ident($c); $aq = db_ident($a);
                db_exec("UPDATE $tq SET $cq = ISNULL($cq,0) + ? WHERE $aq = ?", [$delta, $target]);
                audit_log("admin_adjust", [
                    "target" => $target, "kind" => $kind, "delta" => $delta,
                ]);
                flash_set("success", lang("admin.adjust_ok"));
            }
        }
        redirect("index.php?m=admin&sub=users&q=" . urlencode($target));
    }

    if ($action === "grant_vip") {
        $target = trim((string)($_POST["account"] ?? ""));
        $type   = max(1, min(255, (int)($_POST["vip_type"] ?? 1)));
        $days   = max(1, (int)($_POST["days"] ?? 1));
        if ($target !== "" && db_table_exists((string)($config["vip_table"] ?? "VipList"))) {
            $vt = db_ident((string)($config["vip_table"] ?? "VipList"), "VipList");
            $va = db_ident((string)($config["vip_account_col"] ?? "AccountID"), "AccountID");
            $vy = db_ident((string)($config["vip_type_col"] ?? "VipType"), "VipType");
            $ve = db_ident((string)($config["vip_expire_col"] ?? "ExpireDate"), "ExpireDate");
            db_exec(
                "MERGE $vt AS t
                 USING (SELECT ? AS acc) AS s ON t.$va = s.acc
                 WHEN MATCHED THEN UPDATE
                    SET $vy = ?,
                        $ve = DATEADD(day, ?,
                                      CASE WHEN t.$ve > GETDATE() THEN t.$ve ELSE GETDATE() END),
                        GrantedBy = 'admin'
                 WHEN NOT MATCHED THEN
                    INSERT ($va, $vy, $ve, GrantedBy)
                    VALUES (?, ?, DATEADD(day, ?, GETDATE()), 'admin');",
                [$target, $type, $days, $target, $type, $days]
            );
            audit_log("admin_grant_vip", [
                "target" => $target, "vip_type" => $type, "days" => $days,
            ]);
            flash_set("success", lang("admin.adjust_ok"));
        }
        redirect("index.php?m=admin&sub=vip");
    }

    if ($action === "revoke_vip") {
        $target = trim((string)($_POST["account"] ?? ""));
        if ($target !== "" && db_table_exists((string)($config["vip_table"] ?? "VipList"))) {
            $vt = db_ident((string)($config["vip_table"] ?? "VipList"), "VipList");
            $va = db_ident((string)($config["vip_account_col"] ?? "AccountID"), "AccountID");
            $ve = db_ident((string)($config["vip_expire_col"] ?? "ExpireDate"), "ExpireDate");
            db_exec("UPDATE $vt SET $ve = GETDATE() WHERE $va = ?", [$target]);
            audit_log("admin_revoke_vip", ["target" => $target]);
            flash_set("success", lang("admin.adjust_ok"));
        }
        redirect("index.php?m=admin&sub=vip");
    }

    if ($action === "news_save") {
        $id    = (int)($_POST["id"] ?? 0);
        $title = trim((string)($_POST["title"] ?? ""));
        $body  = trim((string)($_POST["body"]  ?? ""));
        // Hard caps mirror the column widths in docs/schema_addons.sql.
        if (function_exists("mb_substr")) {
            $title = mb_substr($title, 0, 160, "UTF-8");
            $body  = mb_substr($body,  0, 8000, "UTF-8");
        } else {
            $title = substr($title, 0, 160);
            $body  = substr($body,  0, 8000);
        }
        if ($title !== "" && $body !== "" && db_table_exists("webmu_news")) {
            $author = (string)($me["id"] ?? "admin");
            if ($id > 0) {
                db_exec(
                    "UPDATE webmu_news SET title = ?, body = ?, updated_at = GETDATE() WHERE id = ?",
                    [$title, $body, $id]
                );
                audit_log("admin_news_update", ["id" => $id, "title" => $title]);
            } else {
                db_exec(
                    "INSERT INTO webmu_news (title, body, author, posted_at) VALUES (?, ?, ?, GETDATE())",
                    [$title, $body, $author]
                );
                audit_log("admin_news_create", ["title" => $title]);
            }
            flash_set("success", lang("admin.news.saved"));
        }
        redirect("index.php?m=admin&sub=news");
    }

    if ($action === "news_delete") {
        $id = (int)($_POST["id"] ?? 0);
        if ($id > 0 && db_table_exists("webmu_news")) {
            db_exec("DELETE FROM webmu_news WHERE id = ?", [$id]);
            audit_log("admin_news_delete", ["id" => $id]);
            flash_set("success", lang("admin.news.deleted"));
        }
        redirect("index.php?m=admin&sub=news");
    }

    redirect("index.php?m=admin&sub=" . $sub);
}

// ----------------------------------------------------------------------
// GET — gather data per sub-page.
// ----------------------------------------------------------------------
$data = ["sub" => $sub];

if ($sub === "dashboard") {
    $r = db_one("SELECT COUNT(*) AS c FROM MEMB_INFO");
    $data["accounts"] = $r ? (int)$r["c"] : 0;
    $stat_t = (string)($config["stat_table"] ?? "MEMB_STAT");
    $stat_c = (string)($config["stat_connect_col"] ?? "ConnectStat");
    $data["online"] = 0;
    if (db_table_exists($stat_t) && db_column_exists($stat_t, $stat_c)) {
        $tq = db_ident($stat_t, "MEMB_STAT");
        $cq = db_ident($stat_c, "ConnectStat");
        $r = db_one("SELECT COUNT(*) AS c FROM $tq WHERE $cq = 1");
        $data["online"] = $r ? (int)$r["c"] : 0;
    }
    $data["listed"] = 0;
    if (db_table_exists("WebMarketItems")) {
        $r = db_one("SELECT COUNT(*) AS c FROM WebMarketItems WHERE state = 'listed'");
        $data["listed"] = $r ? (int)$r["c"] : 0;
    }
    $data["vip_active"] = 0;
    if (db_table_exists((string)($config["vip_table"] ?? "VipList"))) {
        $vt = db_ident((string)($config["vip_table"] ?? "VipList"), "VipList");
        $ve = db_ident((string)($config["vip_expire_col"] ?? "ExpireDate"), "ExpireDate");
        $r = db_one("SELECT COUNT(*) AS c FROM $vt WHERE $ve > GETDATE()");
        $data["vip_active"] = $r ? (int)$r["c"] : 0;
    }
    $data["log_24h"] = 0;
    if (db_table_exists("webmu_log")) {
        $r = db_one("SELECT COUNT(*) AS c FROM webmu_log WHERE ts > DATEADD(hour, -24, GETDATE())");
        $data["log_24h"] = $r ? (int)$r["c"] : 0;
    }
}

if ($sub === "users") {
    $q = trim((string)($_GET["q"] ?? ""));
    $data["q"] = $q;
    $data["users"] = [];
    if ($q !== "" && preg_match('~^[\w@.-]{1,80}$~', $q)) {
        $cr_t  = $config["cr_table"]    ?? "MEMB_INFO";
        $cr_c  = $config["cr_column"]   ?? "credits";
        $usdt_t = $config["usdt_table"] ?? "MEMB_INFO";
        $usdt_c = $config["usdt_column"]?? "usdt";
        $select_credits = (db_table_exists($cr_t) && db_column_exists($cr_t, $cr_c))
            ? db_ident($cr_c) : "NULL";
        $select_usdt = (db_table_exists($usdt_t) && db_column_exists($usdt_t, $usdt_c))
            ? db_ident($usdt_c) : "NULL";
        $like = "%" . db_escape_like($q) . "%";
        $data["users"] = db_all(
            "SELECT TOP 50 memb___id AS id, mail_addr AS mail,
                    $select_credits AS credits, $select_usdt AS usdt
               FROM MEMB_INFO
              WHERE memb___id LIKE ? OR mail_addr LIKE ?",
            [$like, $like]
        );
        if (db_table_exists("GameShopPoint") && db_column_exists("GameShopPoint", "WCoinP")) {
            foreach ($data["users"] as &$u) {
                $r = db_one("SELECT WCoinP FROM GameShopPoint WHERE AccountID = ?", [$u["id"]]);
                $u["wcoin"] = $r ? (int)$r["WCoinP"] : 0;
            }
            unset($u);
        }
    }
}

if ($sub === "warehouse") {
    $acc = trim((string)($_GET["acc"] ?? ""));
    $data["acc"] = $acc;
    $data["slots"] = null;
    $wh_slots = max(1, (int)($config["wh_slots"] ?? 120));
    $data["wh_cols"] = max(1, (int)($config["wh_cols"] ?? 8));
    $data["wh_slots"] = $wh_slots;
    if ($acc !== "" && preg_match('~^[A-Za-z0-9]{1,10}$~', $acc)) {
        $wh_t = (string)($config["wh_table"] ?? "warehouse");
        $wh_i = (string)($config["wh_items_col"] ?? "Items");
        $wh_a = (string)($config["wh_account_col"] ?? "AccountID");
        if (db_table_exists($wh_t) && db_column_exists($wh_t, $wh_i)) {
            $tq = db_ident($wh_t, "warehouse");
            $iq = db_ident($wh_i, "Items");
            $aq = db_ident($wh_a, "AccountID");
            $r = db_one("SELECT TOP 1 $iq AS Items FROM $tq WHERE $aq = ?", [$acc]);
            $data["slots"] = $r ? mu_parse_warehouse_blob($r["Items"] ?? "", $wh_slots) : null;
        }
    }
}

if ($sub === "market") {
    $data["listings"] = [];
    if (db_table_exists("WebMarketItems")) {
        $data["listings"] = db_all(
            "SELECT TOP 200 id, seller_account, item_name, item_level, qty,
                    currency, price, listed_at
               FROM WebMarketItems WHERE state = 'listed'
              ORDER BY listed_at DESC"
        );
    }
}

if ($sub === "vip") {
    $data["active"] = [];
    $vip_t = (string)($config["vip_table"] ?? "VipList");
    if (db_table_exists($vip_t)) {
        $vt = db_ident($vip_t, "VipList");
        $va = db_ident((string)($config["vip_account_col"] ?? "AccountID"), "AccountID");
        $vy = db_ident((string)($config["vip_type_col"] ?? "VipType"), "VipType");
        $ve = db_ident((string)($config["vip_expire_col"] ?? "ExpireDate"), "ExpireDate");
        $data["active"] = db_all(
            "SELECT TOP 200 $va AS account, $vy AS vip_type, $ve AS expire
               FROM $vt WHERE $ve > GETDATE() ORDER BY $ve DESC"
        );
    }
}

if ($sub === "log") {
    $data["entries"] = [];
    $data["filter_account"] = trim((string)($_GET["acc"] ?? ""));
    $data["filter_action"]  = preg_replace('~[^a-z0-9_]~', '', strtolower((string)($_GET["action"] ?? "")));
    if (db_table_exists("webmu_log")) {
        $sql = "SELECT TOP 200 ts, ip, account, action, details FROM webmu_log";
        $args = []; $where = [];
        if ($data["filter_account"] !== ""
            && preg_match('~^[A-Za-z0-9]{1,10}$~', $data["filter_account"])) {
            $where[] = "account = ?"; $args[] = $data["filter_account"];
        }
        if ($data["filter_action"] !== "") {
            $where[] = "action = ?"; $args[] = $data["filter_action"];
        }
        if ($where) $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY ts DESC";
        $data["entries"] = db_all($sql, $args);
    }
}

if ($sub === "news") {
    $data["news"]      = [];
    $data["edit_post"] = null;
    $data["no_table"]  = !db_table_exists("webmu_news");
    if (!$data["no_table"]) {
        $data["news"] = db_all(
            "SELECT TOP 200 id, title, body, author, posted_at, updated_at
               FROM webmu_news
              ORDER BY posted_at DESC, id DESC"
        );
        $edit_id = (int)($_GET["edit"] ?? 0);
        if ($edit_id > 0) {
            $data["edit_post"] = db_one(
                "SELECT id, title, body FROM webmu_news WHERE id = ?",
                [$edit_id]
            );
        }
    }
}

render_page("admin", $data + [
    "title" => lang("admin.title"),
]);
