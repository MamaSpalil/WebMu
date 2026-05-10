<?php if (!defined("insite")) die("no access"); ?>
<!DOCTYPE html>
<html lang="<?= h($config["__lang_code"] ?? "en") ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title) ?> — <?= h($config["server_name"] ?? "WebMu") ?></title>
    <meta name="description" content="<?= h($config["description"] ?? "") ?>">
    <meta name="keywords"    content="<?= h($config["keywords"] ?? "") ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;900&family=Cinzel+Decorative:wght@700;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="assets/images/logo.svg">
    <link rel="stylesheet" href="assets/css/site.css">
    <?php if (isset($extra_head)) echo $extra_head; ?>
</head>
<body>

<header class="site-header">
    <div class="nav-container">
        <a class="brand" href="index.php">
            <img src="assets/images/logo.svg" alt="<?= h($config["server_name"] ?? "WebMu") ?>">
            <span class="brand-text"><?= h($config["server_team"] ?? "WebMu") ?></span>
        </a>
        <nav class="main-nav" aria-label="Primary">
            <?php
            $nav = [
                "download"     => "nav.download",
                "ranking"      => "nav.ranking",
                "registration" => "nav.registration",
                "market"       => "nav.market",
                "vote"         => "nav.vote",
                "donate"       => "nav.donate",
                "about"        => "nav.about",
            ];
            foreach ($nav as $k => $lk):
                $cls = "nav-btn" . (($page_id === $k) ? " active" : ""); ?>
                <a class="<?= $cls ?>" href="index.php?m=<?= $k ?>"><?= h(lang($lk)) ?></a>
            <?php endforeach; ?>
            <?php if ($user): ?>
                <a class="nav-btn<?= $page_id === "account" ? " active" : "" ?>"
                   href="index.php?m=account"><?= h(lang("nav.account")) ?> (<?= h($user["id"]) ?>)</a>
                <a class="nav-btn" href="index.php?m=logout"><?= h(lang("nav.logout")) ?></a>
            <?php else: ?>
                <a class="nav-btn<?= $page_id === "login" ? " active" : "" ?>"
                   href="index.php?m=login"><?= h(lang("nav.login")) ?></a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php if (!empty($flashes)): ?>
    <div class="flash-stack">
        <?php foreach ($flashes as $f): ?>
            <p class="note <?= $f["t"] === "error" ? "warn" : "" ?>"><?= h($f["m"]) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($config["__using_example"])): ?>
    <div class="flash-stack">
        <p class="note warn"><?= h(lang("db.config_example")) ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($db_error)): ?>
    <div class="flash-stack">
        <p class="note warn">
            <?= h(lang("db.connection_error")) ?>
            <?php if (!empty($config["debug"])): ?>
                <br><small><?= h($db_error) ?></small>
            <?php endif; ?>
        </p>
    </div>
<?php endif; ?>
