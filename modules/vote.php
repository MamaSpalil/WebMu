<?php
// Voting page — list partner top-lists with cooldown indicator.
if (!defined("insite")) die("no access");

// Single source of truth for the partner list lives in core/catalog.php.
$sites = vote_sites();

$me = current_user();
$cooldowns = [];
if ($me) {
    // Per-account cooldown, stored in cache (avoids requiring a DB table to exist).
    foreach ($sites as $s) {
        $f = ($config["__cache"] ?? sys_get_temp_dir()) . "/vote_" . md5($me["id"] . ":" . $s["id"]);
        $left = 0;
        if (is_file($f)) {
            $last = (int)file_get_contents($f);
            $left = max(0, $s["cooldown"] - (time() - $last));
        }
        $cooldowns[$s["id"]] = $left;
    }
}

render_page("vote", [
    "title"     => lang("vote.title"),
    "sites"     => $sites,
    "cooldowns" => $cooldowns,
]);
