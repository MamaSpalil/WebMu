<?php
// POST — create a new MEMB_INFO account.
if (!defined("insite")) die("no access");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("index.php?m=registration");
}

// IP rate-limit: 5 registrations / 24h per IP.
if (rate_limit_hit("reg:" . client_ip(), 5, 86400)) {
    flash_set("error", lang("reg.rate_limit"));
    redirect("index.php?m=registration");
}

$login    = trim((string)($_POST["login"]    ?? ""));
$email    = trim((string)($_POST["email"]    ?? ""));
$email2   = trim((string)($_POST["email2"]   ?? ""));
$password = (string)($_POST["password"]      ?? "");
$password2= (string)($_POST["password2"]     ?? "");
$pin      = (string)($_POST["pin"]           ?? "");
$country  = trim((string)($_POST["country"]  ?? ""));
$referrer = trim((string)($_POST["referrer"] ?? ""));
$rules    = !empty($_POST["rules"]);

$errors = [];
if (!valid_login($login))             $errors[] = lang("reg.invalid.login");
if (!valid_password($password))       $errors[] = lang("reg.invalid.pwd");
if ($password !== $password2)         $errors[] = lang("reg.invalid.pwd2");
if (!valid_email($email))             $errors[] = lang("reg.invalid.mail");
if ($email !== $email2)               $errors[] = lang("reg.invalid.mail2");
if (!valid_pin($pin))                 $errors[] = lang("reg.invalid.pin");
if (!$rules)                          $errors[] = lang("reg.invalid.rules");

if (!$errors) {
    // Uniqueness checks (parameterized).
    if (db_one("SELECT 1 AS x FROM MEMB_INFO WHERE memb___id = ?", [$login])) {
        $errors[] = lang("reg.exists.login");
    } elseif (db_one("SELECT 1 AS x FROM MEMB_INFO WHERE mail_addr = ?", [$email])) {
        $errors[] = lang("reg.exists.mail");
    }
}

if ($errors) {
    foreach ($errors as $e) flash_set("error", $e);
    redirect("index.php?m=registration");
}

// Insert account. Stock MuOnline schema column list.
$ok = db_exec(
    "INSERT INTO MEMB_INFO
       (memb___id, memb_name, memb__pwd, sno__numb, mail_addr,
        bloc_code, ctl1_code, AccountLevel, IsVaultPin, appl_days)
     VALUES (?, ?, ?, '0000000000000', ?, 0, 0, 0, ?, CONVERT(varchar(10), GETDATE(), 120))",
    [$login, $login, pwd_for_db($password), $email, $pin]
);
if (!$ok) {
    flash_set("error", "DB error during registration. Please contact support.");
    redirect("index.php?m=registration");
}

// Starter pack: credits + WCoin (using configured currency tables).
$cr_t   = $config["cr_table"]    ?? "MEMB_INFO";
$cr_c   = $config["cr_column"]   ?? "credits";
$cr_a   = $config["cr_acc"]      ?? "memb___id";
$wc_t   = $config["wcoin_table"] ?? "GameShopPoint";
$wc_c   = $config["wcoin_column"]?? "WCoinP";
$wc_a   = $config["wcoin_acc"]   ?? "AccountID";
$starter_credits = 100;
$starter_wcoin   = 100;

if ($cr_t === "MEMB_INFO") {
    db_exec("UPDATE [$cr_t] SET [$cr_c] = ISNULL([$cr_c],0) + ? WHERE [$cr_a] = ?",
            [$starter_credits, $login]);
}
// MERGE for GameShopPoint (insert if missing, otherwise add).
db_exec(
    "MERGE INTO [$wc_t] AS T
     USING (SELECT ? AS acc, ? AS amt) AS S
       ON T.[$wc_a] = S.acc
     WHEN MATCHED THEN UPDATE SET T.[$wc_c] = ISNULL(T.[$wc_c],0) + S.amt
     WHEN NOT MATCHED THEN INSERT ([$wc_a], [$wc_c]) VALUES (S.acc, S.amt);",
    [$login, $starter_wcoin]
);

// Optional referral bonus
if ($referrer !== "" && valid_login($referrer) && $referrer !== $login) {
    if (db_one("SELECT 1 AS x FROM MEMB_INFO WHERE memb___id = ?", [$referrer])) {
        db_exec("UPDATE MEMB_INFO SET credits = ISNULL(credits,0) + 50 WHERE memb___id = ?",
                [$referrer]);
    }
}

// Auto-login.
$row = db_one("SELECT memb___id, memb_name, mail_addr, memb__pwd FROM MEMB_INFO WHERE memb___id = ?",
              [$login]);
if ($row) login_user($row);

flash_set("success", lang("reg.success"));
redirect("index.php?m=account");
