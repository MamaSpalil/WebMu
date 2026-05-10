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

// Credit the reward to the gr_* column (cash by default).
$tbl = $config["gr_table"]         ?? "MEMB_INFO";
$col = $config["gr_points_column"] ?? "cash";
$acc = $config["gr_points_acc"]    ?? "memb___id";
$ok = db_exec(
    "UPDATE [$tbl] SET [$col] = ISNULL([$col],0) + ? WHERE [$acc] = ?",
    [(int)$site["reward"], $me["id"]]
);
if (!$ok) {
    flash_set("error", "Could not credit reward (DB error).");
    redirect("index.php?m=vote");
}

@file_put_contents($f, (string)time(), LOCK_EX);
flash_set("success", lang("vote.thanks"));
redirect("index.php?m=vote");
