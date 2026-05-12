<?php if (!defined("insite")) die("no access");
// Vars in scope: hours_available, hours_spent, packages[], vip_now, has_hours, has_viplist.
?>
<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/shield.svg" alt="">
        <h1 class="page-title"><?= h(lang("vip.title")) ?></h1>
        <p class="page-subtitle"><?= h(lang("vip.subtitle")) ?></p>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <?php if (!$has_hours || !$has_viplist): ?>
        <section class="panel panel-corner">
            <p class="text-mute"><?= h(lang("vip.not_configured")) ?></p>
            <p class="text-mute" style="font-size:12px;margin-top:8px">
                Run <code>docs/schema_addons.sql</code> to create
                <code>WebOnlineHours</code> and <code>VipList</code>.
            </p>
        </section>
    <?php else: ?>

    <div class="balance-bar">
        <div class="bal">
            <img src="assets/icons/donate.svg" alt="">
            <div><div class="v"><?= fmt_int($hours_available) ?></div>
            <div class="l"><?= h(lang("vip.your_hours")) ?></div></div>
        </div>
        <div class="bal">
            <img src="assets/icons/coins.svg" alt="">
            <div><div class="v"><?= fmt_int($hours_spent) ?></div>
            <div class="l"><?= h(lang("vip.spent_hours")) ?></div></div>
        </div>
        <div class="bal">
            <img src="assets/icons/shield.svg" alt="">
            <div>
                <?php if ($vip_now): ?>
                    <div class="v">VIP <?= (int)$vip_now["type"] ?></div>
                    <div class="l"><?= h(lang("vip.expires")) ?>: <?= h($vip_now["expire"]) ?></div>
                <?php else: ?>
                    <div class="v"><?= h(lang("vip.none")) ?></div>
                    <div class="l"><?= h(lang("vip.current")) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <section class="panel panel-corner">
        <h2 class="panel-title left"><?= h(lang("vip.title")) ?></h2>
        <?php if (!$packages): ?>
            <p class="text-mute"><?= h(lang("vip.not_configured")) ?></p>
        <?php else: ?>
        <div class="grid-4">
            <?php foreach ($packages as $pkg):
                $afford = $hours_available >= (int)$pkg["hours"]; ?>
            <article class="pack">
                <img class="ico" src="assets/icons/shield.svg" alt="">
                <div class="name"><?= h($pkg["name"]) ?></div>
                <div class="text-mute">
                    <strong><?= (int)$pkg["hours"] ?></strong> <?= h(lang("vip.cost_hours")) ?>
                    · <?= (int)$pkg["duration_days"] ?> <?= h(lang("vip.duration")) ?>
                </div>
                <?php if (!empty($pkg["perks"]) && is_array($pkg["perks"])): ?>
                    <ul class="text-mute" style="font-size:12px;list-style:none;padding:0;margin:6px 0;text-align:left">
                        <?php foreach (["exp","drop","chaos","jos"] as $k):
                            if (empty($pkg["perks"][$k])) continue; ?>
                            <li><?= h(lang("vip.perks." . $k)) ?>: <strong><?= h($pkg["perks"][$k]) ?></strong></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <form action="index.php?m=vip_buy" method="post" style="position:relative;z-index:2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="pkg" value="<?= h($pkg["id"]) ?>">
                    <button type="submit" class="btn small" <?= $afford ? "" : "disabled" ?>>
                        <?= h(lang("vip.exchange")) ?>
                    </button>
                </form>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <?php endif; ?>
</main>
