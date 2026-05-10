<?php
// =====================================================================
//  Single source of truth for the donate-shop catalog and vote sites.
//  Both are also used as a fallback when the corresponding DB tables
//  are not present.  modules/donate.php + modules/buy.php and
//  modules/vote.php + modules/vote_callback.php read from here so
//  prices and cooldowns can never drift between the two halves.
// =====================================================================
if (!defined("insite")) die("no access");

/** Static donate-shop catalog (used when WebDonateItems is empty). */
function donate_catalog_static() {
    return [
        1 => ["name"=>"500 WCoin Pack",  "image"=>"coins.svg",  "credits"=>5,  "wcoin"=>0,    "category"=>"wcoin"],
        2 => ["name"=>"1200 WCoin Pack", "image"=>"coins.svg",  "credits"=>10, "wcoin"=>0,    "category"=>"wcoin"],
        3 => ["name"=>"3000 WCoin Pack", "image"=>"coins.svg",  "credits"=>25, "wcoin"=>0,    "category"=>"wcoin"],
        4 => ["name"=>"VIP Bronze 30d",  "image"=>"shield.svg", "credits"=>8,  "wcoin"=>0,    "category"=>"vip"],
        5 => ["name"=>"VIP Silver 30d",  "image"=>"shield.svg", "credits"=>15, "wcoin"=>0,    "category"=>"vip"],
        6 => ["name"=>"VIP Gold 30d",    "image"=>"donate.svg", "credits"=>25, "wcoin"=>0,    "category"=>"vip"],
        7 => ["name"=>"Demon Pet",       "image"=>"skull.svg",  "credits"=>0,  "wcoin"=>800,  "category"=>"cosmetics"],
        8 => ["name"=>"Name Change",     "image"=>"scroll.svg", "credits"=>0,  "wcoin"=>600,  "category"=>"cosmetics"],
        9 => ["name"=>"Class Reset",     "image"=>"staff.svg",  "credits"=>0,  "wcoin"=>1200, "category"=>"cosmetics"],
    ];
}

/**
 * Resolve a single item by id, preferring DB row when present.
 * Returns ["name", "credits", "wcoin"] or null.
 */
function donate_item($id) {
    $id = (int)$id;
    $row = db_one(
        "SELECT name, price_credits, price_wcoin FROM WebDonateItems WHERE id = ?",
        [$id]
    );
    if ($row) {
        return [
            "name"    => (string)$row["name"],
            "credits" => (int)$row["price_credits"],
            "wcoin"   => (int)$row["price_wcoin"],
        ];
    }
    $static = donate_catalog_static();
    return $static[$id] ?? null;
}

/**
 * Vote partner sites — single source of truth.  Server admins must
 * replace YOUR_ID in each URL with the real partner-site ID.
 */
function vote_sites() {
    return [
        ["id"=>"topmu",  "name"=>"TopMu Online", "desc"=>"Largest MU top-list",
         "reward"=>50, "cooldown"=>12*3600,
         "url"=>"https://topmu.example/in?id=YOUR_ID"],
        ["id"=>"xtreme", "name"=>"XtremeTop100", "desc"=>"Global private servers",
         "reward"=>75, "cooldown"=>24*3600,
         "url"=>"https://xtremetop100.com/in.php?site=YOUR_ID"],
        ["id"=>"gtop",   "name"=>"GTop100 Mu",   "desc"=>"EU/NA traffic",
         "reward"=>50, "cooldown"=>12*3600,
         "url"=>"https://gtop100.com/topsites/MU-Online/in/YOUR_ID"],
        ["id"=>"mmotop", "name"=>"MMOTop",       "desc"=>"CIS/RU MMO ranking",
         "reward"=>60, "cooldown"=>24*3600,
         "url"=>"https://mmotop.example/in?id=YOUR_ID"],
    ];
}

/** Index vote_sites() by id for quick lookup. */
function vote_sites_by_id() {
    $out = [];
    foreach (vote_sites() as $s) $out[$s["id"]] = $s;
    return $out;
}
