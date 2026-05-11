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

<header class="site-header header-3d">
    <div class="header-glow" aria-hidden="true"></div>
    <div class="nav-container">
        <a class="brand brand-3d" href="index.php">
            <span class="brand-logo-wrap" aria-hidden="true">
                <img src="assets/images/logo.svg" alt="<?= h($config["server_name"] ?? "WebMu") ?>">
            </span>
            <span class="brand-text" data-text="<?= h($config["server_team"] ?? "WebMu") ?>"><?= h($config["server_team"] ?? "WebMu") ?></span>
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
        <?php
        // Live server-status pill: green when the game server's TCP
        // port answers (and DB is reachable), red otherwise. Falls back
        // to a DB-only check when server_ip / server_port are unset.
        $__db_ok = empty($db_error) && empty($config["__using_example"]);
        $__srv_ip   = trim((string)($config["server_ip"]   ?? ""));
        $__srv_port = (int)($config["server_port"]         ?? 0);
        if ($__srv_ip !== "" && $__srv_port > 0) {
            $__server_ok = $__db_ok && server_status_check(
                $__srv_ip, $__srv_port,
                (int)($config["server_timeout"] ?? 2)
            );
        } else {
            $__server_ok = $__db_ok;
        }
        ?>
        <div class="server-status-pill <?= $__server_ok ? "online" : "offline" ?>" aria-live="polite">
            <span class="dot" aria-hidden="true"></span>
            <span class="lbl"><?= $__server_ok ? "Online" : "Offline" ?></span>
        </div>
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

<?php
// Per-module error summary — surfaces failures that happened while a
// module/widget was trying to read data from the database. Always shows
// a short notice; full SQL + ODBC text only when $config["debug"] = 1.
$__err_summary = function_exists("err_summary") ? err_summary("db") : [];
// Filter out the global connection error already shown above.
$__err_module_groups = [];
foreach ($__err_summary as $__k => $__g) {
    if (($__g["type"] ?? "") === "global") continue;
    $__err_module_groups[$__k] = $__g;
}
if (!empty($__err_module_groups)):
    $__err_all = function_exists("err_collected") ? err_collected("db") : [];
?>
    <div class="flash-stack">
        <p class="note warn">
            <?= h(lang("errors.db_failed")) ?>
            <?php
            $__labels = [];
            foreach ($__err_module_groups as $__g) {
                $__labels[] = h($__g["type"] . " " . $__g["name"]) . " (" . (int)$__g["count"] . ")";
            }
            ?>
            <br><small><?= implode(", ", $__labels) ?></small>
            <?php if (!empty($config["debug"]) && !empty($__err_all)): ?>
                <details style="margin-top:8px">
                    <summary><?= h(lang("errors.details")) ?></summary>
                    <ol style="margin:6px 0 0 18px;padding:0">
                        <?php foreach ($__err_all as $__e):
                            if (($__e["context"]["type"] ?? "") === "global") continue; ?>
                            <li style="margin-bottom:6px">
                                <code><?= h(($__e["context"]["type"] ?? "") . ":" . ($__e["context"]["name"] ?? "")) ?></code>
                                — <?= h($__e["message"] ?? "") ?>
                                <?php if (!empty($__e["extra"]["sql"])): ?>
                                    <br><code style="display:block;white-space:pre-wrap;word-break:break-all"><?= h($__e["extra"]["sql"]) ?></code>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </details>
            <?php endif; ?>
        </p>
    </div>
<?php endif; ?>
