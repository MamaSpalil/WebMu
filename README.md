# WebMu

A PHP web front-end for a MuOnline (Season 3 Ep.1) game server.
Reads the live game database (MS SQL Server) through `opt.php` and
exposes registration, ranking, online status, vote rewards, donate
shop, market and a personal account dashboard.

## Quick start

1. Copy the config template and fill in real remote DB credentials:
   ```sh
   cp opt.example.php opt.php
   ```
   The real `opt.php` is git-ignored — secrets never reach the repo.
2. Make sure PHP has the `odbc` extension and a working
   `Driver={SQL Server}` ODBC driver to your MuOnline database.
3. Keep every connection setting (`db_host`, `db_port`, `db_user`,
   `db_upwd`, `db_name`, `odbc_driver`, and the optional `db_dsn`,
   `db_appname`, `db_charset`, `db_timeout`, `db_persistent`) in
   `opt.php`; `opt.example.php` is only a template. If the connection
   fails, the site shows a warning and logs the ODBC error to
   `logs/db.log`. For a step-by-step walkthrough open
   [`docs/setup.html`](docs/setup.html) — a self-contained HTML
   presentation covering install, `opt.php` configuration and
   troubleshooting.
4. Point a webroot at this directory and open `index.php`.

## Layout

```
index.php              single front controller (defines `insite` and routes)
opt.example.php        config template — copy to opt.php
core/                  bootstrap, DB wrapper, auth, render, helpers
lang/                  translation dictionaries (rus, eng)
modules/               one PHP file per route + widgets/ for home page
themes/ex/             page templates (header, footer, pages/*)
assets/                shared CSS / icons / images (unchanged)
cache/, logs/          runtime data (git-ignored)
```

## Security notes

* All SQL goes through `db_query()` which uses `odbc_prepare` +
  `odbc_execute` — never concatenate user input into SQL.
* Every form carries a CSRF token + a honeypot field (`csrf_field()`).
* Registration and login are IP-rate-limited via `core/auth.php`.
* `opt.php` is git-ignored.  If a real password ever lands in git
  history, rotate it on the SQL Server immediately.
* The web app should run with a dedicated SQL login that has
  `SELECT/INSERT/UPDATE` only on the tables it touches — never `sa`.

## Configuration keys (see `opt.example.php`)

`db_*` / `odbc_driver` describe the ODBC connection.
`siteaddress`, `forum`, `def_lang`, `theme` control the site.
`debug`, `maxconnect`, `onlineplus`, `under_rec` control runtime behavior.
`char_*`, `guild_*`, `stat_*` map ranking/account queries to your
MuOnline schema. `cr_*`, `usd_*`, `wcoin_*`, `gr_*` map each balance to its
`<table>.<column>` and per-account key — change them and the dashboard,
donate shop and vote callback follow automatically.
Download links, starter rewards, vote sites and optional WebMu donate
tables are configured in `opt.php`.
`mainmod` lists the home-page widgets to render in order.
`admin_accounts` is a whitelist of memb___id account names that may
access `?m=admin`. The optional `MEMB_INFO.is_admin` column (added by
`docs/schema_addons.sql`) provides the same effect when set to 1.

## User pages added by the User-Panel rework

| Route                  | Module                          | Purpose                                                |
|------------------------|---------------------------------|--------------------------------------------------------|
| `?m=warehouse`         | `modules/warehouse.php`         | Read-only Web-Vault grid; "Put up for sale" button.    |
| `?m=market`            | `modules/market.php`            | Browse listings (filter by currency).                  |
| `?m=market_list` POST  | `modules/market_list.php`       | List one Web-Vault item on the market.                 |
| `?m=market_cancel` POST| `modules/market_cancel.php`     | Cancel a listing, return item to seller's vault.       |
| `?m=market_buy` POST   | `modules/market_buy.php`        | Buy a wcoin/zen/usdt listing (jewels are list-only).   |
| `?m=vip`               | `modules/vip.php`               | Online-hours bank + VIP packages.                      |
| `?m=vip_buy` POST      | `modules/vip_buy.php`           | Exchange accumulated hours for in-game VIP perks.      |
| `?m=admin`             | `modules/admin.php`             | Admin panel (dashboard, users, vaults, market, VIP, log). |

Run `docs/schema_addons.sql` after upgrading — it idempotently creates
`WebMarketItems`, `WebMarketLog`, `WebOnlineHours`, `VipList` and adds
`MEMB_INFO.usdt` / `MEMB_INFO.is_admin` columns when missing.
