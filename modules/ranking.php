<?php
// Ranking page — top players, top guilds, online now. 60s cache.
if (!defined("insite")) die("no access");

$tab = preg_replace('~[^a-z]~', '', strtolower((string)($_GET["tab"] ?? "players")));
if (!in_array($tab, ["players", "guilds", "kills", "online"], true)) $tab = "players";

$cache_key = "rank.all";
$data = cache_get($cache_key, 60);
if ($data === null) {
    $players = db_all(
        "SELECT TOP 100 c.Name, c.cLevel, c.Resets, c.MasterLevel, c.Class, gm.G_Name
         FROM Character c
         LEFT JOIN GuildMember gm ON gm.Name = c.Name
         ORDER BY c.Resets DESC, c.cLevel DESC, c.MasterLevel DESC"
    );
    foreach ($players as &$p) { $p["class_h"] = mu_class($p["Class"] ?? 0); }
    unset($p);

    $guilds = db_all(
        "SELECT TOP 50 g.G_Name, g.G_Master, COUNT(gm.Name) AS members,
                ISNULL(SUM(c.Resets),0) AS total_resets
         FROM Guild g
         LEFT JOIN GuildMember gm ON gm.G_Name = g.G_Name
         LEFT JOIN Character    c ON c.Name    = gm.Name
         GROUP BY g.G_Name, g.G_Master
         ORDER BY total_resets DESC"
    );

    $kills = db_all(
        "SELECT TOP 25 Name, cLevel, Class, PkCount, PkLevel
         FROM Character
         WHERE PkCount > 0
         ORDER BY PkCount DESC"
    );
    foreach ($kills as &$k) { $k["class_h"] = mu_class($k["Class"] ?? 0); }
    unset($k);

    $online = db_all(
        "SELECT TOP 100 ms.memb___id, ms.IP, c.Name, c.cLevel, c.Class
         FROM MEMB_STAT ms
         LEFT JOIN Character c ON c.AccountID = ms.memb___id
         WHERE ms.ConnectStat = 1
         ORDER BY c.Resets DESC, c.cLevel DESC"
    );
    foreach ($online as &$o) { $o["class_h"] = mu_class($o["Class"] ?? 0); }
    unset($o);

    // Stats strip
    $stats = [
        "accounts"   => (int)(db_one("SELECT COUNT(*) AS c FROM MEMB_INFO")["c"] ?? 0),
        "characters" => (int)(db_one("SELECT COUNT(*) AS c FROM Character")["c"] ?? 0),
        "online"     => (int)(db_one("SELECT COUNT(*) AS c FROM MEMB_STAT WHERE ConnectStat=1")["c"] ?? 0),
        "guilds"     => (int)(db_one("SELECT COUNT(*) AS c FROM Guild")["c"] ?? 0),
    ];
    $stats["online"] += (int)($config["onlineplus"] ?? 0);

    $data = compact("players", "guilds", "kills", "online", "stats");
    cache_set($cache_key, $data);
}

render_page("ranking", $data + [
    "title" => lang("rank.title"),
    "tab"   => $tab,
]);
