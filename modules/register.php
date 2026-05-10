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

// Insert account. Build the column list dynamically — stock Season 3 backups
// (MuOnline_Bak) do not always have AccountLevel/IsVaultPin, so we only add
// those when the columns actually exist. memb___id/memb_name/memb__pwd/
// sno__numb/mail_addr/bloc_code/ctl1_code/appl_days are present in every
// known MuOnline schema (verified against the MuOnline_Bak in this repo).
$cols   = ["memb___id", "memb_name", "memb__pwd", "sno__numb", "mail_addr",
           "bloc_code", "ctl1_code", "appl_days"];
$vals   = ["?", "?", "?", "'0000000000000'", "?", "0", "0",
           "CONVERT(varchar(10), GETDATE(), 120)"];
$params = [$login, $login, pwd_for_db($password), $email];

if (db_column_exists("MEMB_INFO", "AccountLevel")) {
    $cols[]   = "AccountLevel";
    $vals[]   = "0";
}
if (db_column_exists("MEMB_INFO", "IsVaultPin")) {
    $cols[]   = "IsVaultPin";
    $vals[]   = "?";
    $params[] = $pin;
}
$col_list = implode(", ", array_map(function ($c) { return db_ident($c); }, $cols));
$val_list = implode(", ", $vals);

$ok = db_exec("INSERT INTO MEMB_INFO ($col_list) VALUES ($val_list)", $params);
if (!$ok) {
    $err = db_last_odbc_error();
    db_log("registration insert failed for `$login`: " . $err);
    flash_set("error", "DB error during registration"
        . (!empty($config["debug"]) && $err !== "" ? ": " . $err : "") . ".");
    redirect("index.php?m=registration");
}

// Seed MEMB_STAT row so the player immediately appears as "offline" in the
// online-listings instead of being absent (stock MuOnline schema).
if (db_table_exists("MEMB_STAT")) {
    db_exec(
        "INSERT INTO MEMB_STAT (memb___id, ConnectStat, ServerName, IP)
         SELECT ?, 0, '', ''
         WHERE NOT EXISTS (SELECT 1 FROM MEMB_STAT WHERE memb___id = ?)",
        [$login, $login]
    );
}

// Starter pack: credits + WCoin (using configured currency tables).
$cr_t   = $config["cr_table"]    ?? "MEMB_INFO";
$cr_c   = $config["cr_column"]   ?? "credits";
$cr_a   = $config["cr_acc"]      ?? "memb___id";
$wc_t   = $config["wcoin_table"] ?? "GameShopPoint";
$wc_c   = $config["wcoin_column"]?? "WCoinP";
$wc_a   = $config["wcoin_acc"]   ?? "AccountID";
$cr_tq = db_ident($cr_t, "MEMB_INFO");
$cr_cq = db_ident($cr_c, "credits");
$cr_aq = db_ident($cr_a, "memb___id");
$wc_tq = db_ident($wc_t, "GameShopPoint");
$wc_cq = db_ident($wc_c, "WCoinP");
$wc_aq = db_ident($wc_a, "AccountID");
$starter_credits = (int)($config["starter_credits"] ?? 100);
$starter_wcoin   = (int)($config["starter_wcoin"] ?? 100);
$referral_credits = (int)($config["referral_credits"] ?? 50);

// Credits — only if the configured column actually exists on the table.
if ($starter_credits > 0 && db_column_exists($cr_t, $cr_c)) {
    db_exec("UPDATE $cr_tq SET $cr_cq = ISNULL($cr_cq,0) + ? WHERE $cr_aq = ?",
            [$starter_credits, $login]);
}
// WCoin — only if the optional GameShopPoint table exists.
if ($starter_wcoin > 0 && db_table_exists($wc_t)) {
    // MERGE inserts a new row when missing, otherwise increments existing balance.
    db_exec(
        "MERGE INTO $wc_tq AS T
         USING (SELECT ? AS acc, ? AS amt) AS S
           ON T.$wc_aq = S.acc
         WHEN MATCHED THEN UPDATE SET T.$wc_cq = ISNULL(T.$wc_cq,0) + S.amt
         WHEN NOT MATCHED THEN INSERT ($wc_aq, $wc_cq) VALUES (S.acc, S.amt);",
        [$login, $starter_wcoin]
    );
}

// Optional referral bonus
if ($referrer !== "" && valid_login($referrer) && $referrer !== $login
    && $referral_credits > 0 && db_column_exists($cr_t, $cr_c)) {
    if (db_one("SELECT 1 AS x FROM MEMB_INFO WHERE memb___id = ?", [$referrer])) {
        db_exec("UPDATE $cr_tq SET $cr_cq = ISNULL($cr_cq,0) + ? WHERE $cr_aq = ?",
                [$referral_credits, $referrer]);
    }
}

// Auto-login.
$row = db_one("SELECT memb___id, memb_name, mail_addr, memb__pwd FROM MEMB_INFO WHERE memb___id = ?",
              [$login]);
if ($row) login_user($row);

flash_set("success", lang("reg.success"));
redirect("index.php?m=account");
