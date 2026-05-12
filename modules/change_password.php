<?php
// Change account password. POST only.
if (!defined("insite")) die("no access");
require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("index.php?m=account");
}

// Rate-limit password changes: at most 5 attempts / hour per IP and per
// account — enough for the legitimate "I mistyped my current password" loop,
// strict enough to block credential-stuffing once a session is hijacked.
$me_id = current_user()["id"] ?? "";
if (rate_limit_hit("chpwd:ip:" . client_ip(), 5, 3600)
    || rate_limit_hit("chpwd:acc:" . $me_id, 5, 3600)) {
    flash_set("error", lang("acc.rate_limit"));
    redirect("index.php?m=account");
}

$cur  = (string)($_POST["current"] ?? "");
$new  = (string)($_POST["new"]     ?? "");
$new2 = (string)($_POST["new2"]    ?? "");

if (!valid_password($new) || $new !== $new2) {
    flash_set("error", lang("reg.invalid.pwd2"));
    redirect("index.php?m=account");
}

$me = current_user();
$row = db_one("SELECT memb__pwd FROM MEMB_INFO WHERE memb___id = ?", [$me["id"]]);
if (!verify_password($cur, $row)) {
    flash_set("error", lang("acc.password_bad"));
    redirect("index.php?m=account");
}

$ok = db_exec("UPDATE MEMB_INFO SET memb__pwd = ? WHERE memb___id = ?",
              [pwd_for_db($new), $me["id"]]);
if ($ok) {
    audit_log("change_password");
    flash_set("success", lang("acc.password_ok"));
}
redirect("index.php?m=account");
