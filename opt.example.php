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
$config["db_host"]      = "127.0.0.1";        // SQL Server host (1433)
$config["db_user"]      = "web_app";          // dedicated SQL login (NOT sa)
$config["db_upwd"]      = "CHANGE_ME";        // password for the SQL login
$config["ctype"]        = "ODBC";             // currently only ODBC is supported
$config["db_name"]      = "MuOnline";         // main game database
$config["odbc_driver"]  = "SQL Server";       // ODBC driver name

// ---- Site addresses ----
$config["siteaddress"]  = "http://localhost/";
$config["forum"]        = "http://localhost/forum";

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
$config["description"]  = "WebMu MuOnline server";
$config["keywords"]     = "MuOnline Season 6 episode 3";
$config["server_name"]  = "Welcome to WebMu";
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
