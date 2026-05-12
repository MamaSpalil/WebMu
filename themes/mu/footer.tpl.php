<?php if (!defined("insite")) die("no access");
// Online count + server-status pill (uses existing helpers from core).
$__db_ok    = empty($db_error) && empty($config["__using_example"]);
$__srv_ip   = trim((string)($config["server_ip"]   ?? ""));
$__srv_port = (int)($config["server_port"]         ?? 0);
$__server_ok = $__db_ok;
if (!empty($__srv_ip) && $__srv_port > 0 && function_exists("server_status_check")) {
    $__server_ok = $__db_ok && server_status_check(
        $__srv_ip, $__srv_port, (int)($config["server_timeout"] ?? 2)
    );
}

// Try to read live online count from MEMB_STAT — same logic as the
// existing widgets/server_stats.php, kept inline + try/catch so the
// chrome never breaks the page when the column shape differs.
$__online_count = null;
if ($__db_ok && function_exists("db_one")) {
    try {
        $__stat_t   = db_ident($config["stat_table"]   ?? "MEMB_STAT", "MEMB_STAT");
        $__stat_col = db_ident($config["stat_conn_col"] ?? "ConnectStat", "ConnectStat");
        $__row = db_one("SELECT COUNT(*) AS cnt FROM $__stat_t WHERE $__stat_col = 1");
        if ($__row && isset($__row["cnt"])) {
            $__online_count = (int)$__row["cnt"] + (int)($config["onlineplus"] ?? 0);
        }
    } catch (\Throwable $__e) {
        $__online_count = null;
    }
}

// Build socials list once (same convention as themes/ex/footer.tpl.php).
$__socials = [];
foreach ([
    "Discord"  => $config["social_discord"]  ?? "",
    "Telegram" => $config["social_telegram"] ?? "",
    "VK"       => $config["social_vk"]       ?? "",
    "YouTube"  => $config["social_youtube"]  ?? "",
] as $__title => $__url) {
    if ($__url !== "") $__socials[$__title] = $__url;
}
?>
            </div><!-- /.news-block-chrome -->
        </main>

        <aside class="right-sitebar">
            <div class="block-online">
                <div class="segment-server">
                    <p>
                        <span class="<?= $__server_ok ? "online" : "off" ?>"></span>
                        <?= h($config["server_name"] ?? "Server") ?>
                        <span class="coll-players"><?= $__online_count !== null ? (int)$__online_count : ($__server_ok ? "Online" : "Offline") ?></span>
                    </p>
                </div>
            </div>

            <div class="block-login">
                <?php if (!empty($user)): ?>
                    <div class="account_menu">
                        <a href="index.php?m=account"><b><?= h(lang("nav.account", "Account")) ?> (<?= h($user["id"] ?? "") ?>)</b></a>
                        <a href="index.php?m=warehouse"><b><?= h(lang("nav.warehouse", "Warehouse")) ?></b></a>
                        <a href="index.php?m=market"><b><?= h(lang("nav.market", "Market")) ?></b></a>
                        <a href="index.php?m=vip"><b style="color:#e7b267"><?= h(lang("nav.vip", "VIP")) ?></b></a>
                        <a href="index.php?m=donate"><b style="color:#e7b267"><?= h(lang("nav.donate", "Donate")) ?></b></a>
                        <a href="index.php?m=ranking"><b><?= h(lang("nav.ranking", "Ranking")) ?></b></a>
                        <?php if (function_exists("is_admin") && is_admin()): ?>
                            <a href="index.php?m=admin"><b style="color:#ff6a6a"><?= h(lang("nav.admin", "Admin")) ?></b></a>
                        <?php endif; ?>
                        <form action="index.php?m=logout" method="post" name="log_out" style="display:inline">
                            <?php if (function_exists("csrf_field")) echo csrf_field(); ?>
                            <a href="javascript://" onclick="document.log_out.submit();" class="submit"><b><?= h(lang("nav.logout", "Logout")) ?></b></a>
                        </form>
                    </div>
                <?php else: ?>
                    <form action="index.php?m=login" method="post" name="login" autocomplete="off">
                        <?php if (function_exists("csrf_field")) echo csrf_field(); ?>
                        <input type="text"     placeholder="<?= h(lang("nav.login", "Login")) ?>"    class="inp-login" name="login"    id="login_input" maxlength="10" required>
                        <input type="password" placeholder="<?= h(lang("login.password", "Password")) ?>" class="inp-pass"  name="password" id="pass_input"  maxlength="32" required>
                        <a href="index.php?m=registration"><?= h(lang("nav.registration", "Register")) ?></a>
                        <input type="submit" name="login_in" class="btn-vhod" value="">
                        <div class="clear"></div>
                    </form>
                <?php endif; ?>
            </div>

            <div class="block-topic-forum">
                <?php
                // Latest news / forum topics — pulls from the existing
                // lastinforum widget if present, otherwise hides quietly.
                $__forum_topics = [];
                $__widget_path = __DIR__ . "/../../modules/widgets/lastinforum.php";
                if (is_file($__widget_path)) {
                    try {
                        $__forum_topics = (function () use ($__widget_path) {
                            $config = $GLOBALS["config"] ?? [];
                            $r = include $__widget_path;
                            return is_array($r) ? $r : [];
                        })();
                    } catch (\Throwable $__e) {
                        $__forum_topics = [];
                    }
                }
                ?>
                <?php if ($__forum_topics): ?>
                    <?php foreach (array_slice($__forum_topics, 0, 5) as $__t): ?>
                        <div class="topic-forum">
                            <?php if (!empty($__t["url"])): ?>
                                <a href="<?= h($__t["url"]) ?>" target="_blank" rel="noopener"><?= h($__t["title"] ?? "") ?></a>
                            <?php else: ?>
                                <a><?= h($__t["title"] ?? "") ?></a>
                            <?php endif; ?>
                            <?php if (!empty($__t["author"])): ?>
                                <p><span class="autor-top"><?= h($__t["author"]) ?></span></p>
                            <?php endif; ?>
                            <div class="clear"></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php if (!empty($config["forum"])): ?>
                        <div class="topic-forum">
                            <a href="<?= h($config["forum"]) ?>" target="_blank" rel="noopener">
                                <?= h(lang("nav.forum", "Forum")) ?>
                            </a>
                            <div class="clear"></div>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($__socials as $__title => $__url): ?>
                        <div class="topic-forum">
                            <a href="<?= h($__url) ?>" target="_blank" rel="noopener"><?= h($__title) ?></a>
                            <div class="clear"></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <div class="clear"></div>
    </div><!-- /.wrapper -->
</div><!-- /.bg-content -->

<footer style="background:#0e0606;color:#705656;padding:14px 0;text-align:center;font:11px PSType,Trebuchet MS,sans-serif">
    <div class="wrapper">
        © <?= date("Y") ?> <?= h($config["server_team"] ?? $config["server_name"] ?? "WebMu") ?>
        <?php if (lang("footer.tagline") && lang("footer.tagline") !== "footer.tagline"): ?>
            — <?= h(lang("footer.tagline")) ?>
        <?php endif; ?>
    </div>
</footer>

</body>
</html>
