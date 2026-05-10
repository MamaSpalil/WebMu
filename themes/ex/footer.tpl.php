<?php if (!defined("insite")) die("no access");
// Social channels — only rendered when an URL is provided in opt.php.
$__socials = [];
foreach ([
    "Discord"  => $config["social_discord"]  ?? "",
    "Telegram" => $config["social_telegram"] ?? "",
    "VK"       => $config["social_vk"]       ?? "",
    "YouTube"  => $config["social_youtube"]  ?? "",
] as $__title => $__url) {
    if ($__url !== "") $__socials[$__title] = $__url;
}
if (!empty($config["forum"])) {
    // Always include the forum at the front when configured.
    $__socials = [lang("footer.forum") => $config["forum"]] + $__socials;
}
?>
<footer class="site-footer footer-3d">
    <div class="footer-grid">
        <section class="footer-card">
            <h3 class="footer-card-title"><?= h(lang("footer.col.server")) ?></h3>
            <ul class="footer-links">
                <li><a href="index.php?m=download"><?= h(lang("nav.download")) ?></a></li>
                <li><a href="index.php?m=ranking"><?= h(lang("nav.ranking")) ?></a></li>
                <li><a href="index.php?m=market"><?= h(lang("nav.market")) ?></a></li>
                <li><a href="index.php?m=about"><?= h(lang("nav.about")) ?></a></li>
            </ul>
        </section>
        <section class="footer-card">
            <h3 class="footer-card-title"><?= h(lang("footer.col.community")) ?></h3>
            <ul class="footer-links">
                <?php if (!empty($config["forum"])): ?><li><a href="<?= h($config["forum"]) ?>" rel="noopener"><?= h(lang("footer.forum")) ?></a></li><?php endif; ?>
                <li><a href="index.php?m=vote"><?= h(lang("nav.vote")) ?></a></li>
                <li><a href="index.php?m=registration"><?= h(lang("nav.registration")) ?></a></li>
                <li><a href="index.php?m=donate"><?= h(lang("nav.donate")) ?></a></li>
            </ul>
        </section>
        <section class="footer-card">
            <h3 class="footer-card-title"><?= h(lang("footer.col.support")) ?></h3>
            <ul class="footer-links">
                <li><a href="index.php?m=about"><?= h(lang("footer.faq")) ?></a></li>
                <li><a href="index.php?m=login"><?= h(lang("nav.login")) ?></a></li>
                <?php if (!empty($config["siteaddress"])): ?>
                    <li><span class="text-mute"><?= h(parse_url($config["siteaddress"], PHP_URL_HOST) ?: $config["siteaddress"]) ?></span></li>
                <?php endif; ?>
            </ul>
        </section>
    </div>

    <div class="footer-bottom">
        <div class="copy">
            © <?= h($config["server_team"] ?? "WebMu") ?> — <?= h(lang("footer.tagline")) ?>
        </div>
        <?php if ($__socials): ?>
        <div class="socials" aria-label="Social links">
            <?php foreach ($__socials as $__title => $__url): ?>
                <a class="social-btn" href="<?= h($__url) ?>" rel="noopener" title="<?= h($__title) ?>"><?= h(mb_substr($__title, 0, 1, "UTF-8")) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="credits">
        Icons: <a href="https://game-icons.net/" rel="noopener">game-icons.net</a> — CC BY 3.0 (Lorc, Delapouite).
    </div>
</footer>
</body>
</html>
