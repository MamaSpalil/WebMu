<?php if (!defined("insite")) die("no access"); ?>
<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/vote.svg" alt="">
        <h1 class="page-title"><?= h(lang("vote.title")) ?></h1>
        <p class="page-subtitle">Help us climb the top-lists — earn rewards every day</p>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <?php if (!$user): ?>
        <p class="note warn"><?= h(lang("vote.no_account")) ?>
            <a href="index.php?m=login&amp;next=<?= urlencode("index.php?m=vote") ?>">Log in</a>.</p>
    <?php endif; ?>

    <section class="panel panel-corner">
        <h2 class="panel-title left">Voting Sites</h2>

        <?php foreach ($sites as $s):
            $cd_left = $cooldowns[$s["id"]] ?? 0;
            $ready = ($cd_left <= 0);
        ?>
        <div class="vote-row">
            <img class="site-icon" src="assets/icons/castle.svg" alt="">
            <div>
                <div class="name"><?= h($s["name"]) ?></div>
                <div class="desc"><?= h($s["desc"]) ?> · Cooldown: <?= (int)($s["cooldown"]/3600) ?>h</div>
            </div>
            <div class="reward"><img src="assets/icons/coins.svg" alt=""> +<?= (int)$s["reward"] ?> Cash</div>

            <?php if ($user && $ready): ?>
                <form action="<?= h($s["url"]) ?>" method="get" target="_blank" rel="noopener" style="display:inline">
                    <button class="btn" type="submit">Vote →</button>
                </form>
                <!-- After voting on partner site, user comes back and confirms reward -->
                <form action="index.php?m=vote_callback" method="post" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="site" value="<?= h($s["id"]) ?>">
                    <button class="btn" type="submit">Confirm reward</button>
                </form>
            <?php elseif ($user): ?>
                <span class="status cd">In <?= gmdate("H:i", $cd_left) ?></span>
            <?php else: ?>
                <span class="status cd">Login</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </section>

    <section class="panel panel-corner">
        <h2 class="panel-title left">How it works</h2>
        <div class="grid-3">
            <div class="card"><img class="card-icon" src="assets/icons/registration.svg" alt="">
                <div class="card-title">1. Log in</div>
                <p>Sign in so we can credit rewards to your account.</p></div>
            <div class="card"><img class="card-icon" src="assets/icons/vote.svg" alt="">
                <div class="card-title">2. Vote</div>
                <p>Click "Vote →", solve the captcha on the partner site, then come back.</p></div>
            <div class="card"><img class="card-icon" src="assets/icons/coins.svg" alt="">
                <div class="card-title">3. Confirm</div>
                <p>Click "Confirm reward" to credit Vote points to your account.</p></div>
        </div>
    </section>
</main>
