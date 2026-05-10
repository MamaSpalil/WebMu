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

// ---- Site addresses ----
$config["siteaddress"]  = "http://localhost/";
$config["forum"]        = "http://localhost/forum";
// Optional social channel URLs — footer hides each button when empty.
$config["social_discord"]  = "";
$config["social_telegram"] = "";
$config["social_vk"]       = "";
$config["social_youtube"]  = "";

// ---- Localization & theme ----
$config["def_lang"]     = "rus";              // rus | eng
$config["theme"]        = "ex";               // themes/<theme>/

// ---- Runtime ----
$config["debug"]        = 0;                  // 1 = print SQL/PHP errors
$config["maxconnect"]   = 250;                // capacity for the load bar
$config["onlineplus"]   = 0;                  // optional offset for "online" counter
$config["md5use"]       = "off";              // off = plain (MuOnline default), on = md5
$config["under_rec"]    = 0;                  // 1 = maintenance mode (site closed)

// ---- SEO / branding ----
$config["description"]  = "WebMu MuOnline Season 3 Episode 1 server";
$config["keywords"]     = "MuOnline Season 3 episode 1";
$config["server_name"]  = "MuOnline Season 3 Episode 1";
$config["server_team"]  = "WebMu";

// ---- Home-page widgets (rendered, in order) ----
$config["mainmod"]      = "qinfo,strongest,questtop,top5items,lastinf,lastinforum";
$config["mainmod_def"]  = "qinfo,strongest,questtop,top5guild,baners";

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
$config["market_open_col"]    = "";            // e.g. "IsStoreOpen"; empty lists recent characters

// ---- Optional WebMu tables ----
$config["donate_items_table"] = "WebDonateItems";
$config["donate_log_table"]   = "WebDonateLog";
// Fallback storage for vote points when $config["gr_*"] columns are not
// present on MEMB_INFO (stock Season 3 backups). Auto-created on demand.
$config["web_vote_table"]     = "WebVotePoints";

// ---- Vote sites (replace URLs/ids with real top-list links) ----
$config["vote_sites"] = [
    ["id"=>"topmu", "name"=>"TopMu Online", "desc"=>"MU top-list", "reward"=>50, "cooldown"=>12*3600, "url"=>"https://topmu.example/in?id=YOUR_ID"],
    ["id"=>"mmotop", "name"=>"MMOTop", "desc"=>"CIS/RU MMO ranking", "reward"=>60, "cooldown"=>24*3600, "url"=>"https://mmotop.example/in?id=YOUR_ID"],
];
