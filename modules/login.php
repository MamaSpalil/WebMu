<?php
// GET → form. POST → authenticate.
if (!defined("insite")) die("no access");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    render_page("login", ["title" => lang("login.title")]);
    return;
}

if (rate_limit_hit("login:" . client_ip(), 10, 600)) {
    flash_set("error", lang("login.rate_limit"));
    redirect("index.php?m=login");
}

$login = trim((string)($_POST["login"] ?? ""));
$pwd   = (string)($_POST["password"] ?? "");

if (!valid_login($login) || !valid_password($pwd)) {
    flash_set("error", lang("login.bad"));
    redirect("index.php?m=login");
}

$row = db_one(
    "SELECT memb___id, memb_name, mail_addr, memb__pwd, bloc_code
     FROM MEMB_INFO WHERE memb___id = ?", [$login]
);
if (!$row || (int)($row["bloc_code"] ?? 0) !== 0 || !verify_password($pwd, $row)) {
    flash_set("error", lang("login.bad"));
    redirect("index.php?m=login");
}

login_user($row);
$next = $_GET["next"] ?? "index.php?m=account";
redirect($next);
