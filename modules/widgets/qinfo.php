<?php
// Server quick-info widget: real online count + capacity bar.
if (!defined("insite")) die("no access");

$online = 0;
$row = db_one("SELECT COUNT(*) AS c FROM MEMB_STAT WHERE ConnectStat = 1");
if ($row && isset($row["c"])) {
    $online = (int)$row["c"];
}
$online += (int)($config["onlineplus"] ?? 0);
$max = max(1, (int)($config["maxconnect"] ?? 250));

return [
    "online"  => $online,
    "max"     => $max,
    "percent" => min(100, (int)round($online * 100 / $max)),
    "name"    => $config["server_name"] ?? "WebMu",
];
