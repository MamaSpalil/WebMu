<?php
// Voting page — list partner top-lists with cooldown indicator.
if (!defined("insite")) die("no access");

// Static partner list. id is what comes back as ?site=...
$sites = [
    ["id" => "topmu",   "name" => "TopMu Online",   "desc" => "Largest MU top-list",       "reward" => 50, "cooldown" => 12 * 3600, "url" => "https://topmu.example/in?id=YOUR_ID"],
    ["id" => "xtreme",  "name" => "XtremeTop100",   "desc" => "Global private servers",    "reward" => 75, "cooldown" => 24 * 3600, "url" => "https://xtremetop100.com/in.php?site=YOUR_ID"],
    ["id" => "gtop",    "name" => "GTop100 Mu",     "desc" => "EU/NA traffic",             "reward" => 50, "cooldown" => 12 * 3600, "url" => "https://gtop100.com/topsites/MU-Online/in/YOUR_ID"],
    ["id" => "mmotop",  "name" => "MMOTop",         "desc" => "CIS/RU MMO ranking",        "reward" => 60, "cooldown" => 24 * 3600, "url" => "https://mmotop.example/in?id=YOUR_ID"],
];

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
