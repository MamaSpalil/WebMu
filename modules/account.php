<?php
// Personal account dashboard — shows balances built from $config keys.
if (!defined("insite")) die("no access");
require_login();

$user = current_user();
$account = $user["id"];

// Helper that reads "<col> from <table> where <acc>=?" using $config keys.
function read_balance($prefix, $account) {
    global $config;
    $col = $config[$prefix . "_column"]
        ?? $config[$prefix . "_points_column"]
        ?? null;
    $tbl = $config[$prefix . "_table"]
        ?? null;
    $acc = $config[$prefix . "_acc"]
        ?? $config[$prefix . "_points_acc"]
        ?? "memb___id";
    if (!$col || !$tbl) return null;
    $row = db_one("SELECT [$col] AS v FROM [$tbl] WHERE [$acc] = ?", [$account]);
    return $row ? (string)$row["v"] : "0";
}

$balances = [
    ["label" => "Credits", "value" => read_balance("cr",    $account), "icon" => "coins.svg"],
    ["label" => "USD",     "value" => read_balance("usd",   $account), "icon" => "donate.svg"],
    ["label" => "WCoin",   "value" => read_balance("wcoin", $account), "icon" => "gem.svg"],
    ["label" => "Votes",   "value" => read_balance("gr",    $account), "icon" => "vote.svg"],
];

// Characters owned by this account
$chars = db_all(
    "SELECT TOP 10 Name, cLevel, Resets, MasterLevel, Class
     FROM Character WHERE AccountID = ?
     ORDER BY Resets DESC, cLevel DESC",
    [$account]
);
foreach ($chars as &$c) {
    $c["class_h"] = mu_class($c["Class"] ?? 0);
}
unset($c);

render_page("account", [
    "title"    => lang("acc.title"),
    "balances" => $balances,
    "chars"    => $chars,
]);
