<?php if (!defined("insite")) die("no access"); ?>

<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/donate.svg" alt="">
        <h1 class="page-title"><?= h(lang("donate.title")) ?></h1>
        <p class="page-subtitle">Support the realm — claim legendary rewards</p>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <?php if ($user): ?>
    <div class="balance-bar">
        <div class="bal"><img src="assets/icons/coins.svg" alt="">
            <div><div class="v"><?= fmt_int($balances["credits"]) ?></div><div class="l">Credits</div></div></div>
        <div class="bal"><img src="assets/icons/gem.svg" alt="">
            <div><div class="v"><?= fmt_int($balances["wcoin"]) ?></div><div class="l">WCoin</div></div></div>
        <div class="bal"><img src="assets/icons/info.svg" alt="">
            <div><div class="v"><?= h($user["id"]) ?></div><div class="l">Account</div></div></div>
    </div>
    <?php else: ?>
        <p class="note warn">Please <a href="index.php?m=login&amp;next=<?= urlencode("index.php?m=donate") ?>">log in</a> to make purchases.</p>
    <?php endif; ?>

    <?php
    $cats = [];
    foreach ($items as $it) { $cats[$it["category"]][] = $it; }
    $cat_titles = ["wcoin"=>"WCoin Packages", "vip"=>"VIP Membership", "cosmetics"=>"Cosmetics", "bundles"=>"Bundles"];
    foreach ($cats as $cat => $list):
    ?>
    <section class="panel panel-corner">
        <h2 class="panel-title left"><?= h($cat_titles[$cat] ?? ucfirst($cat)) ?></h2>
        <div class="grid-4">
            <?php foreach ($list as $it): ?>
            <article class="pack">
                <img class="ico" src="assets/icons/<?= h($it["image"]) ?>" alt="">
                <div class="name"><?= h($it["name"]) ?></div>
                <div class="price">
                    <?php if ($it["credits"] > 0): ?>
                        <?= fmt_int($it["credits"]) ?><small>Credits</small>
                    <?php else: ?>
                        <?= fmt_int($it["wcoin"]) ?><small>WCoin</small>
                    <?php endif; ?>
                </div>
                <?php if ($user): ?>
                <form action="index.php?m=buy" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="item" value="<?= (int)$it["id"] ?>">
                    <button class="btn" type="submit">Purchase</button>
                </form>
                <?php else: ?>
                <a class="btn" href="index.php?m=login&amp;next=<?= urlencode("index.php?m=donate") ?>">Login to buy</a>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>

    <section class="panel panel-corner">
        <h2 class="panel-title left">Stay safe</h2>
        <p class="note warn">Staff <em>never</em> ask for your password or card details in private chat.
            Buy only through this official Donate Shop.</p>
    </section>
</main>
