<?php
// Personal account dashboard — shows balances built from $config keys.
if (!defined("insite")) die("no access");
require_login();

$user = current_user();
$account = $user["id"];

// Helper that reads "<col> from <table> where <acc>=?" using $config keys.
// Returns null when either the table or the column doesn't exist (so the
// caller can simply skip balances that aren't supported by the stock schema).
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
    if (!db_table_exists($tbl) || !db_column_exists($tbl, $col)) return null;
    $colq = db_ident($col);
    $tblq = db_ident($tbl);
    $accq = db_ident($acc, "memb___id");
    if (!$colq || !$tblq) return null;
    $row = db_one("SELECT $colq AS v FROM $tblq WHERE $accq = ?", [$account]);
    return $row ? (string)$row["v"] : "0";
}

// Vote points may live in MEMB_INFO.cash (stock) or in a fallback table
// WebVotePoints (created by vote_callback.php when MEMB_INFO.cash is absent).
function read_vote_balance($account) {
    global $config;
    $val = read_balance("gr", $account);
    if ($val !== null) return $val;
    $vote_tbl = (string)($config["web_vote_table"] ?? "WebVotePoints");
    if (!db_table_exists($vote_tbl)) return null;
    $vote_tblq = db_ident($vote_tbl, "WebVotePoints");
    $row = db_one("SELECT points AS v FROM $vote_tblq WHERE account = ?", [$account]);
    return $row ? (string)$row["v"] : "0";
}

$raw_balances = [
    ["label" => "Credits", "value" => read_balance("cr",    $account), "icon" => "coins.svg"],
    ["label" => "USD",     "value" => read_balance("usd",   $account), "icon" => "donate.svg"],
    ["label" => "WCoin",   "value" => read_balance("wcoin", $account), "icon" => "gem.svg"],
    ["label" => "Votes",   "value" => read_vote_balance($account),     "icon" => "vote.svg"],
];
// Hide currencies that aren't supported by this server's schema.
$balances = array_values(array_filter($raw_balances, function ($b) {
    return $b["value"] !== null;
}));

// Characters owned by this account
$char_t = db_ident($config["char_table"] ?? "Character", "Character");
$char_name = db_ident($config["char_name_col"] ?? "Name", "Name");
$char_account = db_ident($config["char_account_col"] ?? "AccountID", "AccountID");
$char_level = db_ident($config["char_level_col"] ?? "cLevel", "cLevel");
$char_resets = db_ident($config["char_resets_col"] ?? "Resets", "Resets");
$char_class = db_ident($config["char_class_col"] ?? "Class", "Class");
$char_t_raw = $config["char_table"] ?? "Character";
$char_master_cfg = trim((string)($config["char_master_col"] ?? ""));
$char_master = ($char_master_cfg !== "" && db_column_exists($char_t_raw, $char_master_cfg))
    ? db_ident($char_master_cfg, "MasterLevel") : null;
$master_select = $char_master ? "$char_master AS MasterLevel" : "0 AS MasterLevel";
$chars = db_all(
    "SELECT TOP 10 $char_name AS Name, $char_level AS cLevel,
            $char_resets AS Resets, $master_select, $char_class AS Class
     FROM $char_t WHERE $char_account = ?
     ORDER BY $char_resets DESC, $char_level DESC",
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
