<?php
// Ranking page — top players, top guilds, online now. 60s cache.
if (!defined("insite")) die("no access");

$tab = preg_replace('~[^a-z]~', '', strtolower((string)($_GET["tab"] ?? "players")));
if (!in_array($tab, ["players", "guilds", "kills", "online"], true)) $tab = "players";

$cache_key = "rank.all";
$data = cache_get($cache_key, 60);
if ($data === null) {
    $char_t       = db_ident($config["char_table"] ?? "Character", "Character");
    $char_t_raw   = $config["char_table"] ?? "Character";
    $char_name    = db_ident($config["char_name_col"] ?? "Name", "Name");
    $char_account = db_ident($config["char_account_col"] ?? "AccountID", "AccountID");
    $char_level   = db_ident($config["char_level_col"] ?? "cLevel", "cLevel");
    $char_resets  = db_ident($config["char_resets_col"] ?? "Resets", "Resets");
    $char_class   = db_ident($config["char_class_col"] ?? "Class", "Class");
    $char_pk_count= db_ident($config["char_pk_count_col"] ?? "PkCount", "PkCount");
    $char_pk_level= db_ident($config["char_pk_level_col"] ?? "PkLevel", "PkLevel");
    $char_master_cfg = trim((string)($config["char_master_col"] ?? ""));
    $char_master = ($char_master_cfg !== "" && db_column_exists($char_t_raw, $char_master_cfg))
        ? "c." . db_ident($char_master_cfg, "MasterLevel")
        : "0";

    // Optional Character columns — only select what actually exists so we
    // never crash on stock Season 3 backups that lack some of them.
    $opt_cols = [
        "AccountID"  => "AccountID",
        "Strength"   => "Strength",
        "Dexterity"  => "Dexterity",
        "Vitality"   => "Vitality",
        "Energy"     => "Energy",
        "Leadership" => "Leadership",
        "Money"      => "Money",
        "MapNumber"  => "MapNumber",
        "MapPosX"    => "MapPosX",
        "MapPosY"    => "MapPosY",
        "MapDir"     => "MapDir",
        "Quest"      => "Quest",
        "ExQuestNum" => "ExQuestNum",
        "GReset"     => "GReset",
    ];
    $extra_select = [];
    foreach ($opt_cols as $alias => $col) {
        if (db_column_exists($char_t_raw, $col)) {
            $extra_select[$alias] = "c." . db_ident($col) . " AS " . db_ident($alias);
        }
    }
    $extra_sql = $extra_select ? ", " . implode(", ", $extra_select) : "";

    $guild_t      = db_ident($config["guild_table"] ?? "Guild", "Guild");
    $guild_t_raw  = $config["guild_table"] ?? "Guild";
    $guild_member_t   = db_ident($config["guild_member_table"] ?? "GuildMember", "GuildMember");
    $guild_member_t_raw = $config["guild_member_table"] ?? "GuildMember";
    $guild_name   = db_ident($config["guild_name_col"] ?? "G_Name", "G_Name");
    $guild_master = db_ident($config["guild_master_col"] ?? "G_Master", "G_Master");
    $guild_score  = db_ident($config["guild_score_col"] ?? "G_Score", "G_Score");
    // GuildMember stores both the character Name and the guild name (G_Name).
    // Both column names default to the same as in the parent tables.
    $gm_char_name  = db_ident($config["guild_member_name_col"]  ?? "Name",   "Name");
    $gm_guild_name = db_ident($config["guild_member_guild_col"] ?? "G_Name", "G_Name");
    // Optional Guild columns
    $guild_extra = [];
    foreach (["G_Mark" => "G_Mark", "G_Notice" => "G_Notice"] as $alias => $col) {
        if (db_column_exists($guild_t_raw, $col)) {
            $guild_extra[$alias] = "g." . db_ident($col) . " AS " . db_ident($alias);
        }
    }
    $guild_extra_sql = $guild_extra ? ", " . implode(", ", $guild_extra) : "";

    $stat_t       = db_ident($config["stat_table"] ?? "MEMB_STAT", "MEMB_STAT");
    $stat_account = db_ident($config["stat_account_col"] ?? "memb___id", "memb___id");
    $stat_connect = db_ident($config["stat_connect_col"] ?? "ConnectStat", "ConnectStat");

    // COLLATE DATABASE_DEFAULT eliminates collation conflicts between
    // Character / GuildMember / MEMB_INFO (often "SQL_Latin1_General_CP1_CI_AS"
    // vs "Latin1_General_CI_AS") when joining on varchar keys.
    $has_greset = isset($extra_select["GReset"]);
    $players_order = ($has_greset ? "GReset DESC, " : "")
                   . "c.$char_resets DESC, c.$char_level DESC, MasterLevel DESC";
    // NOTE: alias `c.Name` as `CharName` (not `Name`) — see character.php for
    // the full rationale. With a `LEFT JOIN GuildMember gm` (which also has a
    // `Name` column), aliasing as `Name` makes the ODBC SQL Server driver
    // raise "Ambiguous column name 'Name'" during SQLGetData under
    // SQL_CUR_USE_ODBC. We remap CharName → Name in PHP after fetch so the
    // template/templating code is unaffected.
    $players = db_all(
        "SELECT TOP 100 c.$char_name AS [CharName], c.$char_level AS cLevel,
                c.$char_resets AS Resets, $char_master AS MasterLevel,
                c.$char_class AS Class, gm.$gm_guild_name AS G_Name $extra_sql
         FROM $char_t c
         LEFT JOIN $guild_member_t gm
              ON gm.$gm_char_name COLLATE DATABASE_DEFAULT
               = c.$char_name      COLLATE DATABASE_DEFAULT
         ORDER BY $players_order"
    );
    foreach ($players as &$p) {
        // CharName is always selected, but use the same guarded pattern as
        // the `online` loop below for consistency. NULLs are preserved so
        // the template's isset()/empty() checks behave like before.
        if (array_key_exists("CharName", $p)) {
            $p["Name"] = $p["CharName"];
        }
        $p["class_h"] = mu_class($p["Class"] ?? 0);
    }
    unset($p);

    // Guilds top: we use a derived subquery for the member count instead of
    // GROUP BY on the outer Guild row. The reason is that the Guild table on
    // stock MuOnline schemas carries `G_Mark` (image) and `G_Notice`
    // (text/ntext/varbinary) — and image/text/ntext types cannot appear in
    // GROUP BY, which makes `GROUP BY g.G_Name, g.G_Master, g.G_Score,
    // g.G_Mark, g.G_Notice` fail with a SELECT/GROUP BY error. Pre-aggregating
    // GuildMember by G_Name in a derived table sidesteps the limitation.
    $guilds = db_all(
        "SELECT TOP 50 g.$guild_name AS G_Name, g.$guild_master AS G_Master,
                ISNULL(gm_count.members, 0) AS members,
                ISNULL(g.$guild_score, 0) AS total_resets $guild_extra_sql
         FROM $guild_t g
         LEFT JOIN (
             SELECT $gm_guild_name AS G_Name, COUNT(*) AS members
             FROM $guild_member_t
             GROUP BY $gm_guild_name
         ) gm_count
              ON gm_count.G_Name COLLATE DATABASE_DEFAULT
               = g.$guild_name   COLLATE DATABASE_DEFAULT
         ORDER BY total_resets DESC, members DESC"
    );

    $kills = db_all(
        "SELECT TOP 25 $char_name AS Name, $char_level AS cLevel, $char_class AS Class,
                $char_pk_count AS PkCount, $char_pk_level AS PkLevel
         FROM $char_t
         WHERE $char_pk_count > 0
         ORDER BY $char_pk_count DESC"
    );
    foreach ($kills as &$k) { $k["class_h"] = mu_class($k["Class"] ?? 0); }
    unset($k);

    // Online list — also COLLATE-safe and pulls MapNumber if available.
    // Same `Name` → `CharName` aliasing as the players query above.
    $online_map_sql = isset($extra_select["MapNumber"]) ? ", " . $extra_select["MapNumber"] : "";
    $online = db_all(
        "SELECT TOP 100 ms.$stat_account AS memb___id, c.$char_name AS [CharName],
                c.$char_level AS cLevel, c.$char_resets AS Resets,
                c.$char_class AS Class $online_map_sql
         FROM $stat_t ms
         LEFT JOIN $char_t c
              ON c.$char_account COLLATE DATABASE_DEFAULT
               = ms.$stat_account COLLATE DATABASE_DEFAULT
         WHERE ms.$stat_connect = 1
         ORDER BY c.$char_resets DESC, c.$char_level DESC"
    );
    foreach ($online as &$o) {
        if (array_key_exists("CharName", $o)) {
            $o["Name"] = $o["CharName"];
        }
        $o["class_h"] = mu_class($o["Class"] ?? 0);
        $o["map_h"]   = isset($o["MapNumber"]) ? mu_map($o["MapNumber"]) : null;
    }
    unset($o);

    // Stats strip
    $stats = [
        "accounts"   => (int)(db_one("SELECT COUNT(*) AS c FROM MEMB_INFO")["c"] ?? 0),
        "characters" => (int)(db_one("SELECT COUNT(*) AS c FROM $char_t")["c"] ?? 0),
        "online"     => (int)(db_one("SELECT COUNT(*) AS c FROM $stat_t WHERE $stat_connect=1")["c"] ?? 0),
        "guilds"     => (int)(db_one("SELECT COUNT(*) AS c FROM $guild_t")["c"] ?? 0),
    ];
    $stats["online"] += (int)($config["onlineplus"] ?? 0);

    $data = compact("players", "guilds", "kills", "online", "stats");
    cache_set($cache_key, $data);
}

render_page("ranking", $data + [
    "title" => lang("rank.title"),
    "tab"   => $tab,
]);
