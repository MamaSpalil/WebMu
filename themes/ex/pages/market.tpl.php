<?php if (!defined("insite")) die("no access");
// Vars in scope: listings[], has_table, filter_cur, currencies[], me_id.
?>
<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/market.svg" alt="">
        <h1 class="page-title"><?= h(lang("market.title", "Market")) ?></h1>
        <p class="page-subtitle"><?= h(lang("market.subtitle", "Items players are selling.")) ?></p>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <section class="panel panel-corner">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;justify-content:space-between">
            <h2 class="panel-title left" style="margin:0">Items for sale</h2>
            <form method="get" action="index.php" style="display:flex;gap:8px;align-items:center;margin:0">
                <input type="hidden" name="m" value="market">
                <select name="currency" onchange="this.form.submit()">
                    <option value=""><?= h(lang("market.filter_all")) ?></option>
                    <?php foreach ($currencies as $c): ?>
                        <option value="<?= h($c["id"]) ?>"<?= $filter_cur === $c["id"] ? " selected" : "" ?>>
                            <?= h($c["label"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if (!$has_table): ?>
            <p class="text-mute mt-20">
                Market is not configured yet. Run <code>docs/schema_addons.sql</code>
                to create <code>WebMarketItems</code> (the built-in Web-Vault market),
                or set <code>market_items_table</code> in <code>opt.php</code> to
                point at your server's PersonalShop export.
            </p>
        <?php elseif (!$listings): ?>
            <p class="text-mute mt-20"><?= h(lang("market.empty")) ?></p>
        <?php else: ?>
            <div class="grid-4 mt-20">
                <?php foreach ($listings as $l):
                    $img = trim((string)$l["image"]);
                    if (!preg_match('~^[A-Za-z0-9_.-]+\.gif$~', $img)) $img = "";
                    $is_jewel = ($l["currency_kind"] ?? "balance") === "jewel";
                    $is_mine  = $me_id !== "" && $l["seller_account"] !== ""
                                && strcasecmp((string)$l["seller_account"], $me_id) === 0;
                ?>
                <article class="pack">
                    <?php if ($img !== ""): ?>
                        <img class="ico" src="assets/images/items/<?= h($img) ?>" alt="">
                    <?php else: ?>
                        <img class="ico" src="assets/icons/coins.svg" alt="">
                    <?php endif; ?>
                    <div class="name">
                        <?= h($l["item"] !== "" ? $l["item"] : "Unknown item") ?>
                        <?php if ($l["level"] > 0): ?>
                            <span class="text-mute">+<?= (int)$l["level"] ?></span>
                        <?php endif; ?>
                        <?php if ($l["qty"] > 1): ?>
                            <span class="text-mute">×<?= (int)$l["qty"] ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($l["exc"]) || !empty($l["luck"]) || !empty($l["skill"])): ?>
                        <div class="text-mute" style="font-size:11px;letter-spacing:1px;text-transform:uppercase">
                            <?php if (!empty($l["exc"])):   ?><span style="color:var(--grade-exc)">Exc</span> <?php endif; ?>
                            <?php if (!empty($l["luck"])):  ?>· Luck <?php endif; ?>
                            <?php if (!empty($l["skill"])): ?>· Skill<?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="text-mute">
                        <strong><?= h(rtrim(rtrim(number_format($l["price"], 4, ".", ""), "0"), ".")) ?></strong>
                        <?= h($l["currency_label"] !== "" ? $l["currency_label"] : "Zen") ?>
                    </div>
                    <div class="text-mute" style="font-size:12px">
                        Seller: <?= h($l["seller"] !== "" ? $l["seller"] : "—") ?>
                        <?php if ($l["source"] !== ""): ?>
                            · <span class="cls-tag"><?= h($l["source"]) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ((int)$l["id"] > 0): // Web-Vault listing — buyable ?>
                        <?php if ($is_mine): ?>
                            <form action="index.php?m=market_cancel" method="post" style="margin-top:8px">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$l["id"] ?>">
                                <button type="submit" class="btn small"><?= h(lang("market.cancel_btn")) ?></button>
                            </form>
                        <?php elseif ($me_id === ""): ?>
                            <a class="btn small" href="index.php?m=login"><?= h(lang("nav.login")) ?></a>
                        <?php elseif ($is_jewel): ?>
                            <button type="button" class="btn small ghost" disabled
                                    title="<?= h(lang("market.cant_buy_jewel")) ?>">
                                <?= h(lang("market.contact_btn")) ?>
                            </button>
                        <?php else: ?>
                            <form action="index.php?m=market_buy" method="post" style="margin-top:8px">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$l["id"] ?>">
                                <button type="submit" class="btn small"><?= h(lang("market.buy_btn")) ?></button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
            <?php if ((float)($config["market_fee_pct"] ?? 0) > 0): ?>
                <p class="text-mute mt-20" style="font-size:12px">
                    <?= h(lang("market.fee_note")) ?>: <?= (float)$config["market_fee_pct"] ?>%
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>
