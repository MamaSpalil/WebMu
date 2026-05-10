<?php
// Vote callback — credits reward to the configured `gr_*` column.
// Endpoint:  index.php?m=vote_callback   (POST with site=<id>)
if (!defined("insite")) die("no access");
require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("index.php?m=vote");
}

$me = current_user();
$site_id = preg_replace('~[^a-z0-9]~', '', strtolower((string)($_POST["site"] ?? "")));

// Look up the configured site (single source of truth in core/catalog.php).
$sites = vote_sites_by_id();
if (!isset($sites[$site_id])) {
    flash_set("error", "Unknown vote site.");
    redirect("index.php?m=vote");
}
$site = $sites[$site_id];

// Check cooldown
$cache_dir = $config["__cache"] ?? sys_get_temp_dir();
$f = $cache_dir . "/vote_" . md5($me["id"] . ":" . $site_id);
if (is_file($f)) {
    $last = (int)file_get_contents($f);
    if ((time() - $last) < (int)$site["cooldown"]) {
        flash_set("error", lang("vote.cooldown"));
        redirect("index.php?m=vote");
    }
}

// Credit the reward. Preferred path: configured gr_* column on MEMB_INFO.
// Fallback path: dedicated WebVotePoints table (auto-created on demand) —
// used when the column doesn't exist on the stock Season 3 schema.
$tbl = $config["gr_table"]         ?? "MEMB_INFO";
$col = $config["gr_points_column"] ?? "cash";
$acc = $config["gr_points_acc"]    ?? "memb___id";
$reward = (int)$site["reward"];
$ok = false;

if (db_column_exists($tbl, $col)) {
    $tblq = db_ident($tbl, "MEMB_INFO");
    $colq = db_ident($col, "cash");
    $accq = db_ident($acc, "memb___id");
    $ok = db_exec(
        "UPDATE $tblq SET $colq = ISNULL($colq,0) + ? WHERE $accq = ?",
        [$reward, $me["id"]]
    );
} else {
    // Fallback: WebVotePoints (account varchar(10), points int).
    $vote_tbl = (string)($config["web_vote_table"] ?? "WebVotePoints");
    $vote_tblq = db_ident($vote_tbl, "WebVotePoints");
    if (!db_table_exists($vote_tbl)) {
        db_exec(
            "CREATE TABLE $vote_tblq (
                account   varchar(10) NOT NULL PRIMARY KEY,
                points    int          NOT NULL DEFAULT 0,
                updated_at datetime    NOT NULL DEFAULT GETDATE()
            )"
        );
    }
    if (db_table_exists($vote_tbl)) {
        $ok = db_exec(
            "MERGE INTO $vote_tblq AS T
             USING (SELECT ? AS acc, ? AS amt) AS S
               ON T.account = S.acc
             WHEN MATCHED THEN UPDATE SET T.points = ISNULL(T.points,0) + S.amt, T.updated_at = GETDATE()
             WHEN NOT MATCHED THEN INSERT (account, points, updated_at) VALUES (S.acc, S.amt, GETDATE());",
            [$me["id"], $reward]
        );
    }
}
if (!$ok) {
    flash_set("error", "Could not credit reward (DB error).");
    redirect("index.php?m=vote");
}

@file_put_contents($f, (string)time(), LOCK_EX);
flash_set("success", lang("vote.thanks"));
redirect("index.php?m=vote");
