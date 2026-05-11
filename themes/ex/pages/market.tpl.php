<?php if (!defined("insite")) die("no access"); ?>
<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/market.svg" alt="">
        <h1 class="page-title">Market</h1>
        <p class="page-subtitle">Items players are selling — personal shops &amp; Web-Vault listings</p>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <section class="panel panel-corner">
        <h2 class="panel-title left">Items for sale</h2>

        <?php if (!$has_table): ?>
            <p class="text-mute">
                Market is not configured yet. Set <code>market_items_table</code>
                in <code>opt.php</code> to the SQL table where your server
                exports PersonalShop slots and/or where the on-site Web-Vault
                stores listings.
            </p>
            <p class="note mt-20">
                Looking for the player ranking? It moved to
                <a href="index.php?m=ranking">Ranking</a>.
            </p>
        <?php elseif (!$listings): ?>
            <p class="text-mute">No items are listed for sale right now. Try again later.</p>
        <?php else: ?>
            <div class="grid-4">
                <?php foreach ($listings as $l): ?>
                <article class="pack">
                    <?php if ($l["image"] !== ""): ?>
                        <img class="ico" src="assets/images/items/<?= h($l["image"]) ?>" alt="">
                    <?php else: ?>
                        <img class="ico" src="assets/icons/coins.svg" alt="">
                    <?php endif; ?>
                    <div class="name">
                        <?= h($l["item"] !== "" ? $l["item"] : "Unknown item") ?>
                        <?php if ($l["level"] !== null && $l["level"] > 0): ?>
                            <span class="text-mute">+<?= (int)$l["level"] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="text-mute">
                        <?= fmt_int($l["price"]) ?> <?= h($l["currency"] !== "" ? $l["currency"] : "Zen") ?>
                        <?php if ($l["qty"] > 1): ?>
                            · ×<?= (int)$l["qty"] ?>
                        <?php endif; ?>
                    </div>
                    <div class="text-mute">
                        Seller: <?= h($l["seller"] !== "" ? $l["seller"] : "—") ?>
                        <?php if ($l["source"] !== ""): ?>
                            · <span class="cls-tag"><?= h($l["source"]) ?></span>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
