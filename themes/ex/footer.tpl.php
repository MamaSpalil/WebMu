<?php if (!defined("insite")) die("no access"); ?>
<footer class="site-footer">
    © <?= h($config["server_team"] ?? "WebMu") ?> — <?= h(lang("footer.tagline")) ?>
    <div class="credits">
        Icons: <a href="https://game-icons.net/" rel="noopener">game-icons.net</a> — CC BY 3.0 (Lorc, Delapouite).
    </div>
</footer>
</body>
</html>
