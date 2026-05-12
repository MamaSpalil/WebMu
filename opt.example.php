<?php
// =====================================================================
//  WebMu — site configuration TEMPLATE
//
//  HOW TO USE
//  ----------
//  1. Copy this file to opt.php in the same directory:
//         cp opt.example.php opt.php
//  2. Fill in the real database credentials and server settings below.
//  3. The real opt.php is git-ignored so the secrets never reach the
//     repository.  This template is the only file that is committed.
//
//  The first line below is a guard: opt.php must only be loaded from
//  the application bootstrap (core/init.php), which defines `insite`.
// =====================================================================
if (!defined("insite")) die("no access");

// ---- Database (MS SQL Server, MuOnline schema) ----
// All site modules read these values from opt.php — never hardcode
// credentials anywhere else. core/db.php builds the ODBC DSN from them.
$config["db_host"]      = "127.0.0.1";        // remote SQL Server host (IP or hostname). Can also be "host,port".
$config["db_port"]      = "";                 // optional explicit TCP port, e.g. "1433". Leave empty if baked into db_host.
$config["db_user"]      = "web_app";          // dedicated SQL login (NOT sa)
$config["db_upwd"]      = "CHANGE_ME";        // password for the SQL login (must be changed before going live)
$config["ctype"]        = "ODBC";             // currently only ODBC is supported
$config["db_name"]      = "MuOnline";         // main game database
$config["odbc_driver"]  = "SQL Server";       // ODBC driver name, e.g. "SQL Server", "ODBC Driver 17 for SQL Server"

// ---- Optional remote-connection tuning -----------------------------------
// Leave these empty/0 unless you need them. They are forwarded to ODBC.
$config["db_dsn"]        = "";                // full DSN override; if set, overrides db_host/db_port/db_name/odbc_driver
$config["db_appname"]    = "WebMu";           // application name reported to SQL Server (visible in sp_who2)
$config["db_charset"]    = "";                // e.g. "UTF-8" with MSODBCSQL; leave empty for driver default
$config["db_timeout"]    = 5;                 // connection / login timeout in seconds (0 = driver default)
$config["db_persistent"] = 0;                 // 1 = use odbc_pconnect (pooled), 0 = fresh connection per request
$config["odbc_cursor_type"] = 2;              // 2 = SQL_CUR_USE_DRIVER; avoids SQLGetData errors on SELECT aliases

// ---- Site addresses ----
$config["siteaddress"]  = "http://localhost/";
$config["forum"]        = "http://localhost/forum";
// Optional social channel URLs — footer hides each button when empty.
$config["social_discord"]  = "";
$config["social_telegram"] = "";
$config["social_vk"]       = "";
$config["social_youtube"]  = "";

// ---- Game server (used by the server-status widget on the home page) ----
// Public IP / TCP port that the MU game client connects to. The home
// page does a fast TCP probe (server_timeout seconds) to show whether
// the server is reachable, in addition to the live online count.
$config["server_ip"]    = "127.0.0.1";          // game server (ConnectServer / GameServer) IP
$config["server_port"]  = 44405;                // ConnectServer port (default for Season 3)
$config["server_timeout"] = 2;                  // TCP probe timeout in seconds

// ---- Localization & theme ----
$config["def_lang"]     = "rus";              // rus | eng
$config["theme"]        = "mu";               // themes/<theme>/  (mu = new design from templates.zip; ex = legacy)

// ---- Runtime ----
$config["debug"]        = 0;                  // 1 = print SQL/PHP errors
$config["maxconnect"]   = 250;                // capacity for the load bar
$config["onlineplus"]   = 0;                  // optional offset for "online" counter
$config["md5use"]       = "off";              // off = plain (MuOnline default), on = md5
$config["under_rec"]    = 0;                  // 1 = maintenance mode (site closed)

// ---- Operational metrics endpoint (?m=metrics) ----
// Exposes operational counters (online, registrations/hour, votes/hour,
// errors/hour, ...) for dashboards. MUST NOT be public.
// Access is granted when the remote IP is in metrics_allow_ips OR when
// the request carries a matching token via the X-Metrics-Token header
// or ?token=... query parameter. Set metrics_token to a long random
// string when scraping from a non-loopback host (e.g. Prometheus).
$config["metrics_allow_ips"] = ["127.0.0.1", "::1"];
$config["metrics_token"]     = "";            // optional shared secret; empty = IP allowlist only

// ---- SEO / branding ----
$config["description"]  = "WebMu MuOnline Season 3 Episode 1 server";
$config["keywords"]     = "MuOnline Season 3 episode 1";
$config["server_name"]  = "MuOnline Season 3 Episode 1";
$config["server_team"]  = "WebMu";

// ---- Home-page widgets (rendered, in order) ----
$config["mainmod"]      = "qinfo,server_status,server_stats,strongest,questtop,top5items,lastinf,lastinforum";
$config["mainmod_def"]  = "qinfo,server_status,server_stats,strongest,questtop,top5guild,baners";

// ---- Currency mapping (table / column / account-key for each balance) ----
// Credits — main donate currency
$config["cr_table"]     = "MEMB_INFO";
$config["cr_column"]    = "credits";
$config["cr_acc"]       = "memb___id";
// USD — secondary donate currency
$config["usd_table"]    = "MEMB_INFO";
$config["usd_column"]   = "usd";
$config["usd_acc"]      = "memb___id";
// WCoin — in-game shop currency
$config["wcoin_table"]  = "GameShopPoint";
$config["wcoin_column"] = "WCoinP";
$config["wcoin_acc"]    = "AccountID";
// Vote points (cash)
$config["gr_table"]         = "MEMB_INFO";
$config["gr_points_column"] = "cash";
$config["gr_points_acc"]    = "memb___id";

// ---- Donate-shop pricing ----
$config["vip_icon_cost"] = 1;                 // credits per VIP icon

// ---- Registration rewards ----
$config["starter_credits"] = 100;
$config["starter_wcoin"]   = 100;
$config["referral_credits"] = 50;

// ---- Downloads page ----
$config["client_name"]        = "MU Full Client (Season 3 Ep.1)";
$config["client_size"]        = "1.2 GB";
$config["client_url"]         = "#mirror1";    // replace with real client URL
$config["patch_name"]         = "Season 3 Episode 1 Patch";
$config["patch_size"]         = "85 MB";
$config["patch_url"]          = "#patch";      // replace with real patch URL
$config["launcher_url"]       = "#launcher";   // optional launcher URL

// ---- Ranking / MuOnline_Bak schema mapping ----
$config["char_table"]         = "Character";
$config["char_name_col"]      = "Name";
$config["char_account_col"]   = "AccountID";
$config["char_level_col"]     = "cLevel";
$config["char_resets_col"]    = "Resets";
$config["char_master_col"]    = "";           // Season 3 backups often do not have MasterLevel
$config["char_class_col"]     = "Class";
$config["char_pk_count_col"]  = "PkCount";
$config["char_pk_level_col"]  = "PkLevel";
// Grand-reset column on Character. Many custom Season 3 schemas use
// `gr_res`; stock MuOnline calls it `GReset`. Auto-detected at runtime
// (db_column_exists), so this is just the preferred name to look up first.
$config["char_greset_col"]    = "gr_res";
// Account access-control flag on Character. On stock MuOnline, CtlCode = 1
// means a banned/blocked character and CtlCode = 17 marks a hidden GM —
// neither should appear in public rankings. Leave empty to disable filter.
$config["char_ctl_col"]       = "CtlCode";
// Optional AccountCharacter table — used to identify the *currently
// connected* character on an account for the Online tab. When the table
// exists, `AccountCharacter.GameIDC` holds the in-game character name
// the account is logged in as, which is the only reliable way to pick a
// single character out of the (up to 5) characters owned by an account.
$config["account_char_table"] = "AccountCharacter";
$config["account_char_acc_col"]  = "Id";
$config["account_char_name_col"] = "GameIDC";
$config["guild_table"]        = "Guild";
$config["guild_member_table"] = "GuildMember";
$config["guild_name_col"]     = "G_Name";
$config["guild_master_col"]   = "G_Master";
$config["guild_score_col"]    = "G_Score";
// GuildMember has both a character-name column and a guild-name column.
// Defaults match the stock MuOnline schema.
$config["guild_member_name_col"]  = "Name";
$config["guild_member_guild_col"] = "G_Name";
$config["stat_table"]         = "MEMB_STAT";
$config["stat_account_col"]   = "memb___id";
$config["stat_connect_col"]   = "ConnectStat";

// ---- Market ----
// Optional table where the server (or a server-side export job) writes
// items currently listed for sale via PersonalShop and/or the Web-Vault
// "Sundook Reitingi" feature. Leave empty to hide market listings.
// Expected columns: Seller, ItemName, Price (required) plus optional
// Currency, ItemImage, ItemLevel, Quantity, ListedAt, Source.
//
// NOTE: This is the legacy "external" market table. WebMu also ships its
// own Web-Vault market (table `WebMarketItems` — see docs/schema_addons.sql)
// which is used automatically when the table exists; it stores listings
// created by the Web-Сундук "Put up for sale" form. The legacy table
// (this one) is still read for backwards compatibility.
$config["market_items_table"] = "";            // e.g. "WebMarketItems"
$config["market_seller_col"]  = "Seller";
$config["market_item_col"]    = "ItemName";
$config["market_price_col"]   = "Price";
// Web-Vault market — built-in. Listing fees / commission percentages are
// applied when a buyer purchases a listing via ?m=market_buy. Set to 0 to
// disable. Range: 0..50.
$config["market_fee_pct"]     = 0;
// Maximum number of active listings per seller. 0 = unlimited.
$config["market_max_listings_per_seller"] = 50;
// Hard caps on listing price (anti-typo / anti-overflow). decimal(20,4).
$config["market_max_price"]   = 99999999;

// ---- Web-Сундук (warehouse) ----
// Stock MuOnline Season 3 stores per-account warehouse items in
// `warehouse.Items` (varbinary, 120 slots × 16 bytes = 1920 bytes) keyed
// by AccountID. Override these names for custom emulators.
$config["wh_table"]       = "warehouse";
$config["wh_items_col"]   = "Items";
$config["wh_account_col"] = "AccountID";
$config["wh_money_col"]   = "Money";          // optional — Zen stored in vault
$config["wh_slots"]       = 120;              // 8×15 grid in stock
$config["wh_cols"]        = 8;                // grid width in cells

// ---- VIP (online-hours → in-game perks) ----
// VipList table read by GameServer to apply VIP perks. Many Season 3
// emulators use a table similar to this — adjust column names below if
// your build differs. WebMu writes (AccountID, VipType, ExpireDate)
// when a player exchanges accumulated online hours for a VIP package.
$config["vip_table"]            = "VipList";
$config["vip_account_col"]      = "AccountID";
$config["vip_type_col"]         = "VipType";
$config["vip_expire_col"]       = "ExpireDate";
// VIP packages — the player chooses one and pays `hours` from their
// online-hours balance. Perks are display-only; the GameServer must
// implement the actual exp/drop/chaos/JoS bonuses keyed by VipType.
$config["vip_packages"] = [
    [
        "id" => "vip1", "name" => "VIP Bronze",
        "hours" => 24,  "vip_type" => 1, "duration_days" => 1,
        "perks" => ["exp" => "+30%", "drop" => "+20%", "chaos" => "+5%",  "jos" => "+5%"],
    ],
    [
        "id" => "vip2", "name" => "VIP Silver",
        "hours" => 120, "vip_type" => 2, "duration_days" => 7,
        "perks" => ["exp" => "+50%", "drop" => "+30%", "chaos" => "+10%", "jos" => "+10%"],
    ],
    [
        "id" => "vip3", "name" => "VIP Gold",
        "hours" => 400, "vip_type" => 3, "duration_days" => 30,
        "perks" => ["exp" => "+80%", "drop" => "+50%", "chaos" => "+15%", "jos" => "+15%"],
    ],
];
// Online-hours accumulator throttle. WebMu pulses MEMB_STAT.ConnectStat
// once per `vip_hours_throttle_sec` seconds and adds the elapsed delta
// (capped at `vip_hours_max_step_sec`) to WebOnlineHours.hours_total
// for every account that is currently in-game. Set to 0 to disable the
// in-PHP accumulator (use a SQL Agent job instead).
$config["vip_hours_throttle_sec"] = 300;      // poll at most every 5 min
$config["vip_hours_max_step_sec"] = 1800;     // cap a single tick at 30 min

// ---- USDT — secondary internal balance for the market ----
// Used as the `usdt` market currency. Stored in MEMB_INFO.usdt when the
// column exists (added idempotently by docs/schema_addons.sql). Disable
// by leaving the column missing — the currency simply hides from the
// market UI when not configured.
$config["usdt_table"]   = "MEMB_INFO";
$config["usdt_column"]  = "usdt";
$config["usdt_acc"]     = "memb___id";

// ---- Admin panel ----
// Whitelist of memb___id account names that may access ?m=admin and the
// admin actions. Empty array disables the admin panel entirely (unless
// MEMB_INFO.is_admin = 1 for the current user). Case-insensitive match.
$config["admin_accounts"] = [];

// ---- Optional WebMu tables ----
$config["donate_items_table"] = "WebDonateItems";
$config["donate_log_table"]   = "WebDonateLog";
// Fallback storage for vote points when $config["gr_*"] columns are not
// present on MEMB_INFO (stock Season 3 backups). Auto-created on demand.
$config["web_vote_table"]     = "WebVotePoints";

// Antifraud caps on the vote_callback endpoint (per 24h sliding window).
// Per-(account,site) cooldown is the primary anti-replay; these protect
// against multi-site farming from a single IP / account.
$config["vote_max_per_ip_day"]  = 30;
$config["vote_max_per_acc_day"] = 20;

// ---- Vote sites (replace URLs/ids with real top-list links) ----
$config["vote_sites"] = [
    ["id"=>"topmu", "name"=>"TopMu Online", "desc"=>"MU top-list", "reward"=>50, "cooldown"=>12*3600, "url"=>"https://topmu.example/in?id=YOUR_ID"],
    ["id"=>"mmotop", "name"=>"MMOTop", "desc"=>"CIS/RU MMO ranking", "reward"=>60, "cooldown"=>24*3600, "url"=>"https://mmotop.example/in?id=YOUR_ID"],
];
