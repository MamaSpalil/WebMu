<?php
// Server status widget: live TCP probe of the game server's IP:Port plus
// the real online-player count from MEMB_STAT (with the optional
// onlineplus offset added on top).  Uses $config["server_ip"],
// $config["server_port"] and $config["onlineplus"] from opt.php.
if (!defined("insite")) die("no access");

$ip      = trim((string)($config["server_ip"]   ?? ""));
$port    = (int)($config["server_port"]         ?? 0);
$timeout = max(1, (int)($config["server_timeout"] ?? 2));

// Probe the game server. When IP/Port are not configured yet we cannot
// claim the server is up, so report unknown/false.
$probed  = ($ip !== "" && $port > 0);
$is_up   = $probed ? server_status_check($ip, $port, $timeout) : false;

// Real online count from MEMB_STAT.
$stat_t       = db_ident($config["stat_table"] ?? "MEMB_STAT", "MEMB_STAT");
$stat_connect = db_ident($config["stat_connect_col"] ?? "ConnectStat", "ConnectStat");
$row     = db_one("SELECT COUNT(*) AS c FROM $stat_t WHERE $stat_connect = 1");
$online  = (int)($row["c"] ?? 0) + (int)($config["onlineplus"] ?? 0);
$max     = max(1, (int)($config["maxconnect"] ?? 250));

return [
    "ip"      => $ip,
    "port"    => $port,
    "probed"  => $probed,
    "is_up"   => $is_up,
    "online"  => $online,
    "max"     => $max,
    "percent" => min(100, (int)round($online * 100 / $max)),
];
