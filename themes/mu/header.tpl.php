<?php if (!defined("insite")) die("no access"); ?>
<!DOCTYPE html>
<html lang="<?= h($config["__lang_code"] ?? "en") ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title) ?> — <?= h($config["server_name"] ?? "WebMu") ?></title>
    <meta name="description" content="<?= h($config["description"] ?? "") ?>">
    <meta name="keywords"    content="<?= h($config["keywords"] ?? "") ?>">
    <link rel="icon" type="image/x-icon" href="themes/mu/assets/img/elements/logo.png">
    <!-- New Mu design (chrome) -->
    <link rel="stylesheet" type="text/css" href="themes/mu/assets/css/fonts.css">
    <link rel="stylesheet" type="text/css" href="themes/mu/assets/css/main.css">
    <!-- Existing component styles (panels, buttons, forms inside pages) -->
    <link rel="stylesheet" href="assets/css/site.css">
    <?php if (isset($extra_head)) echo $extra_head; ?>
</head>
<body class="mu-theme" id="body">

<header>
    <div class="wrapper">
        <a href="index.php">
            <img src="themes/mu/assets/img/elements/logo.png" alt="<?= h($config["server_name"] ?? "WebMu") ?>">
        </a>
    </div>
</header>

<div class="bg-content">
    <div class="wrapper">
        <?php
        // ----- LEFT SIDEBAR ------------------------------------------------
        // Navigation map: route key (m=...) => language key.
        // Each item is rendered only when the corresponding module exists.
        $__nav = [
            "home"         => "nav.news",
            "ranking"      => "nav.ranking",
            "registration" => "nav.registration",
            "download"     => "nav.download",
            "market"       => "nav.market",
            "vote"         => "nav.vote",
            "donate"       => "nav.donate",
            "about"        => "nav.about",
        ];
        // Optional fallback labels in case a lang() key is missing.
        $__nav_fallback = [
            "home" => "News", "ranking" => "Top",
            "registration" => "Register", "download" => "Download",
            "market" => "Market", "vote" => "Vote",
            "donate" => "Donate", "about" => "About",
        ];
        ?>
        <aside class="left-sitebar">
            <div class="nav-block">
                <ul>
                    <?php foreach ($__nav as $__k => $__lk):
                        $__label = lang($__lk);
                        if ($__label === $__lk || $__label === "") $__label = $__nav_fallback[$__k];
                    ?>
                        <li><a href="index.php?m=<?= h($__k) ?>"<?= ($page_id === $__k ? ' style="color:#fff"' : '') ?>><?= h($__label) ?></a></li>
                    <?php endforeach; ?>
                    <?php if (!empty($config["forum"])):
                        $__forum_lbl = lang("nav.forum");
                        if ($__forum_lbl === "nav.forum" || $__forum_lbl === "") $__forum_lbl = "Forum";
                    ?>
                        <li><a href="<?= h($config["forum"]) ?>" target="_blank" rel="noopener"><?= h($__forum_lbl) ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <?php
            // ----- TOP HEROES BLOCK ----------------------------------------
            // Renders a compact top-5 list of strongest characters using the
            // same rules as the ranking page. Falls back to a single link
            // when the DB is unreachable so the chrome never breaks.
            $__top5 = [];
            $__cached_top5 = function_exists("cache_get") ? cache_get("mu.header.top5", 60) : null;
            if ($__cached_top5 !== null) {
                $__top5 = $__cached_top5;
            } else if (empty($db_error) && empty($config["__using_example"]) && function_exists("db_one")) {
                try {
                    $__char_t      = db_ident($config["char_table"]      ?? "Character", "Character");
                    $__char_name   = db_ident($config["char_name_col"]   ?? "Name",      "Name");
                    $__char_level  = db_ident($config["char_level_col"]  ?? "cLevel",    "cLevel");
                    $__char_resets = db_ident($config["char_resets_col"] ?? "Resets",    "Resets");
                    $__char_ctl    = db_ident($config["char_ctl_col"]    ?? "CtlCode",   "CtlCode");
                    $__rows = db_all(
                        "SELECT TOP 5 $__char_name AS [CharName], $__char_level AS cLevel,
                                $__char_resets AS Resets
                         FROM $__char_t
                         WHERE $__char_ctl NOT IN (1, 17)
                         ORDER BY $__char_resets DESC, $__char_level DESC"
                    );
                    foreach (($__rows ?: []) as $__row) {
                        $__top5[] = [
                            "name"   => trim((string)($__row["CharName"] ?? "")),
                            "level"  => (int)($__row["cLevel"] ?? 0),
                            "resets" => (int)($__row["Resets"] ?? 0),
                        ];
                    }
                    if (function_exists("cache_set")) cache_set("mu.header.top5", $__top5);
                } catch (\Throwable $__e) {
                    $__top5 = [];
                }
            }
            ?>
            <div class="block-top">
                <?php if ($__top5): ?>
                    <?php $__i = 0; foreach ($__top5 as $__c): $__i++; ?>
                        <p>
                            <span class="nomber-top"><?= (int)$__i ?>.</span>
                            <a href="index.php?m=character&amp;name=<?= urlencode($__c["name"]) ?>"><?= h($__c["name"]) ?></a>
                            <span class="lvl-top">Lv <?= (int)$__c["level"] ?></span>
                        </p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><a href="index.php?m=ranking"><?= h(lang("nav.ranking", "Top players")) ?> &rarr;</a></p>
                <?php endif; ?>
            </div>
        </aside>

        <main class="main-content">
            <a href="index.php?m=download" class="btn-start-game" aria-label="<?= h(lang("nav.download", "Download")) ?>"></a>
            <div class="news-block-chrome">
                <?php
                // ----- INLINE FLASHES & DB ERRORS ------------------------------
                if (!empty($flashes)) {
                    foreach ($flashes as $__f) {
                        $__cls = "mu-flash" . (($__f["t"] ?? "") === "error" ? " warn" : "");
                        echo '<div class="' . $__cls . '">' . h($__f["m"] ?? "") . '</div>';
                    }
                }
                if (!empty($config["__using_example"])) {
                    echo '<div class="mu-flash warn">' . h(lang("db.config_example")) . '</div>';
                }
                if (!empty($db_error)) {
                    echo '<div class="mu-flash warn">' . h(lang("db.connection_error"));
                    if (!empty($config["debug"])) {
                        echo '<br><small>' . h($db_error) . '</small>';
                    }
                    echo '</div>';
                }
                ?>
