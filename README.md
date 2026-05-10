# WebMu

A PHP web front-end for a MuOnline (Season 6 Ep.3) game server.
Reads the live game database (MS SQL Server) through `opt.php` and
exposes registration, ranking, online status, vote rewards, donate
shop, market and a personal account dashboard.

## Quick start

1. Copy the config template and fill in real DB credentials:
   ```sh
   cp opt.example.php opt.php
   ```
   The real `opt.php` is git-ignored — secrets never reach the repo.
2. Make sure PHP has the `odbc` extension and a working
   `Driver={SQL Server}` ODBC driver to your MuOnline database.
3. Point a webroot at this directory and open `index.php`.

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
`cr_*`, `usd_*`, `wcoin_*`, `gr_*` map each balance to its
`<table>.<column>` and per-account key — change them and the dashboard,
donate shop and vote callback follow automatically.
`mainmod` lists the home-page widgets to render in order.

