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
    global $config;
    $id = (int)$id;
    $table = $config["donate_items_table"] ?? "WebDonateItems";
    if (db_table_exists($table)) {
        $tableq = db_ident($table, "WebDonateItems");
        $row = db_one(
            "SELECT name, price_credits, price_wcoin FROM $tableq WHERE id = ?",
            [$id]
        );
        if ($row) {
            return [
                "name"    => (string)$row["name"],
                "credits" => (int)$row["price_credits"],
                "wcoin"   => (int)$row["price_wcoin"],
            ];
        }
    }
    $static = donate_catalog_static();
    return $static[$id] ?? null;
}

/**
 * Vote partner sites — single source of truth.  Server admins must
 * replace YOUR_ID in each URL with the real partner-site ID.
 */
function vote_sites() {
    global $config;
    if (!empty($config["vote_sites"]) && is_array($config["vote_sites"])) {
        return $config["vote_sites"];
    }
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

/* =====================================================================
 *  Market currencies — single source of truth.
 *
 *  WebMu's Web-Vault market lets sellers price an item in one of the
 *  currencies declared here. Each entry describes:
 *    id          — short token stored in WebMarketItems.currency
 *    label       — displayable name for templates
 *    kind        — "balance" | "jewel"
 *      "balance" currencies have a numeric account balance the website
 *        can debit/credit directly (wcoin, zen, usdt). For these we
 *        offer a "Buy" button that performs the trade fully on-site.
 *      "jewel" currencies are per-piece game items (Jewel of Soul,
 *        etc.). The site only allows LISTING for jewel prices; the
 *        actual trade requires a safe in-game escrow that is out of
 *        scope for the MVP. Buyers see "Contact seller" instead.
 *    table/column/acc — for "balance" currencies, the SQL location of
 *        the balance value (mapped from $config[*] keys so admins can
 *        remap to custom emulator schemas).
 *    int_price   — true = enforce integer price (jewels & wcoin).
 * ===================================================================== */
function market_currencies()
{
    global $config;
    $list = [
        [
            "id" => "wcoin", "label" => "WCoin", "kind" => "balance",
            "table" => $config["wcoin_table"]  ?? "GameShopPoint",
            "column"=> $config["wcoin_column"] ?? "WCoinP",
            "acc"   => $config["wcoin_acc"]    ?? "AccountID",
            "int_price" => true,
        ],
        [
            "id" => "zen",   "label" => "Zen", "kind" => "balance",
            // Zen lives on Character.Money — paid PER CHARACTER, so the
            // buyer must pick a character to debit when paying in Zen.
            "table" => $config["char_table"]    ?? "Character",
            "column"=> "Money",
            "acc"   => $config["char_name_col"] ?? "Name",
            "is_character_balance" => true,
            "int_price" => true,
        ],
        [
            "id" => "usdt",  "label" => "USDT", "kind" => "balance",
            "table" => $config["usdt_table"]  ?? "MEMB_INFO",
            "column"=> $config["usdt_column"] ?? "usdt",
            "acc"   => $config["usdt_acc"]    ?? "memb___id",
            "int_price" => false,
        ],
    ];
    // Jewels — ordered by familiar in-game frequency.
    foreach ([
        "bless"     => "Jewel of Bless",
        "soul"      => "Jewel of Soul",
        "chaos"     => "Jewel of Chaos",
        "life"      => "Jewel of Life",
        "creation"  => "Jewel of Creation",
        "harmony"   => "Jewel of Harmony",
        "level"     => "Jewel of Level",
        "luck"      => "Jewel of Luck",
        "excellent" => "Jewel of Excellent",
    ] as $id => $label) {
        $list[] = ["id" => $id, "label" => $label, "kind" => "jewel", "int_price" => true];
    }
    return $list;
}

/**
 * Invalidate every cached market listing view (the unfiltered key plus
 * one per known currency filter — see modules/market.php which builds
 * the cache key as "market.listings." . $filter_cur).
 *
 * Call this from any state-changing market endpoint (list / buy /
 * cancel) so per-currency tabs refresh immediately instead of waiting
 * out the 30-second TTL.
 */
function market_invalidate_listings_cache()
{
    if (!function_exists("cache_del")) return;
    cache_del("market.listings.");          // unfiltered view
    foreach (market_currencies() as $c) {
        cache_del("market.listings." . (string)$c["id"]);
    }
}

/** Look up a market currency descriptor by id. Returns null if unknown. */
function market_currency($id)
{
    $id = (string)$id;
    foreach (market_currencies() as $c) {
        if ($c["id"] === $id) return $c;
    }
    return null;
}

/**
 * Returns true if the currency is supported on the current installation:
 *   - balance currencies need their table+column to actually exist;
 *   - jewel currencies are always allowed for listing.
 */
function market_currency_available($id)
{
    $c = market_currency($id);
    if (!$c) return false;
    if ($c["kind"] !== "balance") return true;
    if (!function_exists("db_table_exists")) return false;
    $tbl = (string)($c["table"] ?? "");
    $col = (string)($c["column"] ?? "");
    if ($tbl === "" || $col === "") return false;
    return db_table_exists($tbl) && db_column_exists($tbl, $col);
}
