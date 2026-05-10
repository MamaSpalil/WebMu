<?php if (!defined("insite")) die("no access"); ?>
<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/market.svg" alt="">
        <h1 class="page-title">Market</h1>
        <p class="page-subtitle">Trade with heroes from across the continent</p>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <section class="panel panel-corner">
        <h2 class="panel-title left">Open personal shops</h2>
        <?php if (!$listings): ?>
            <p class="text-mute">No open shops right now. Try again later.</p>
        <?php else: ?>
            <div class="grid-4">
                <?php foreach ($listings as $l): ?>
                <article class="pack">
                    <img class="ico" src="assets/icons/coins.svg" alt="">
                    <div class="name"><?= h($l["seller"]) ?></div>
                    <div class="text-mute"><span class="cls-tag cls-<?= h($l["class"][1]) ?>"><?= h($l["class"][0]) ?></span></div>
                    <div class="text-mute">Lv <?= (int)$l["level"] ?> · Resets <?= (int)$l["resets"] ?></div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <p class="note mt-20">
            Set <code>market_open_col</code> in <code>opt.php</code> if your emulator stores an open-shop flag.
        </p>
    </section>
</main>
