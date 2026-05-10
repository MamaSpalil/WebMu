<?php if (!defined("insite")) die("no access"); ?>
<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/download.svg" alt="">
        <h1 class="page-title"><?= h(lang("nav.download")) ?></h1>
        <p class="page-subtitle">Season 3 Episode 1 — get the client</p>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <section class="panel panel-corner">
        <h2 class="panel-title left">Game Client</h2>
        <div class="dl-row">
            <img src="assets/icons/chest.svg" alt="">
            <div class="meta">
                <h3><?= h($downloads["client"]["name"]) ?></h3>
                <p>Complete installation for the configured Season 3 Episode 1 server.</p>
                <div class="tags"><span class="badge new">latest</span><span class="badge"><?= h($downloads["client"]["size"]) ?></span></div>
            </div>
            <a class="btn" href="<?= h($downloads["client"]["url"]) ?>">
                <img src="assets/icons/download.svg" alt="" style="width:18px;height:18px"> Mirror
            </a>
        </div>
        <div class="dl-row">
            <img src="assets/icons/scroll.svg" alt="">
            <div class="meta">
                <h3><?= h($downloads["patch"]["name"]) ?></h3>
                <p>Incremental update if you already have the client installed.</p>
                <div class="tags"><span class="badge"><?= h($downloads["patch"]["size"]) ?></span><span class="badge">required</span></div>
            </div>
            <a class="btn" href="<?= h($downloads["patch"]["url"]) ?>">
                <img src="assets/icons/download.svg" alt="" style="width:18px;height:18px"> Patch
            </a>
        </div>
        <p class="note mt-20">Change client, patch and launcher links in <code>opt.php</code>; no template edits are required.</p>
    </section>

    <section class="panel panel-corner">
        <h2 class="panel-title left">System Requirements</h2>
        <div class="req-grid">
            <div><h4>Minimum</h4>
                <table>
                    <tr><th>OS</th><td>Windows 7 SP1 (64-bit)</td></tr>
                    <tr><th>CPU</th><td>Dual-core 2.0 GHz</td></tr>
                    <tr><th>RAM</th><td>2 GB</td></tr>
                    <tr><th>Storage</th><td>5 GB free</td></tr>
                </table>
            </div>
            <div><h4>Recommended</h4>
                <table>
                    <tr><th>OS</th><td>Windows 10 / 11 (64-bit)</td></tr>
                    <tr><th>CPU</th><td>Quad-core 3.0 GHz</td></tr>
                    <tr><th>RAM</th><td>8 GB</td></tr>
                    <tr><th>Storage</th><td>10 GB SSD</td></tr>
                </table>
            </div>
        </div>
    </section>
</main>
