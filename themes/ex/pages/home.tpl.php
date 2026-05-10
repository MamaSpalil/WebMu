<?php if (!defined("insite")) die("no access");
$qinfo = $widget_data["qinfo"]   ?? null;
$strongest = $widget_data["strongest"] ?? null;
$lastinf = $widget_data["lastinf"] ?? [];
$top5g   = $widget_data["top5guild"] ?? [];
$qtop    = $widget_data["questtop"]  ?? [];
$top5i   = $widget_data["top5items"] ?? [];
$baners  = $widget_data["baners"]    ?? [];
?>
<style>
    .hero { position: relative; z-index: 5; max-width: 1280px; margin: 0 auto; padding: 60px 30px 40px; text-align: center; }
    .hero-title { font-family: 'Cinzel Decorative', serif; font-weight: 900;
        font-size: clamp(40px,7vw,84px); line-height: 1.05;
        background: linear-gradient(180deg,#fff5b8 0%,var(--gold) 35%,var(--gold-dark) 65%,var(--gold-deep) 100%);
        -webkit-background-clip: text; background-clip: text; color: transparent;
        letter-spacing: 6px; text-shadow: 0 0 24px rgba(202,160,64,0.45),0 4px 0 rgba(0,0,0,0.6);
        margin-bottom: 8px; }
    .hero-subtitle { font-weight:500; font-size:clamp(14px,1.6vw,18px); letter-spacing:8px;
        text-transform:uppercase; color:var(--gold); opacity:.9; margin-bottom:36px; }
    .actions { display:flex; justify-content:center; gap:40px; flex-wrap:wrap; margin:30px 0; }
    .featured { max-width:1280px; margin:0 auto 40px; padding:0 30px; }
    .gauge { background:rgba(0,0,0,.55); border:1px solid var(--border-gold); border-radius:3px;
        height:24px; overflow:hidden; margin-top:8px; }
    .gauge > span { display:block; height:100%; background:linear-gradient(90deg,#caa040,#fff5b8);
        box-shadow:0 0 12px rgba(230,195,74,.6); }
</style>

<section class="hero">
    <h1 class="hero-title"><?= h($config["server_team"] ?? "WebMu") ?></h1>
    <p class="hero-subtitle">— <?= h($config["server_name"] ?? "MuOnline") ?> —</p>

    <div class="actions">
        <a class="btn-major" href="index.php?m=registration"><span class="icon" aria-hidden="true">⚔</span>JOIN</a>
        <a class="btn-major" href="<?= h($config["forum"] ?? "#") ?>"><span class="icon" aria-hidden="true">📜</span>FORUM</a>
    </div>

    <?php if ($qinfo): ?>
        <div style="max-width:520px;margin:0 auto;color:var(--gold);font-size:13px;letter-spacing:3px;text-transform:uppercase">
            Online: <strong><?= fmt_int($qinfo["online"]) ?></strong> / <?= fmt_int($qinfo["max"]) ?>
            <div class="gauge"><span style="width:<?= (int)$qinfo["percent"] ?>%"></span></div>
        </div>
    <?php endif; ?>
</section>

<section class="featured">
    <div class="grid-3">
        <a class="card" href="index.php?m=download" style="text-decoration:none">
            <img class="card-icon" src="assets/icons/download.svg" alt="">
            <div class="card-title"><?= h(lang("nav.download")) ?></div>
            <p>Get the official client and step into the eternal continent.</p>
        </a>
        <a class="card" href="index.php?m=registration" style="text-decoration:none">
            <img class="card-icon" src="assets/icons/registration.svg" alt="">
            <div class="card-title"><?= h(lang("nav.registration")) ?></div>
            <p>Forge your account in minutes and join the server.</p>
        </a>
        <a class="card" href="index.php?m=ranking" style="text-decoration:none">
            <img class="card-icon" src="assets/icons/ranking.svg" alt="">
            <div class="card-title"><?= h(lang("nav.ranking")) ?></div>
            <p>Top Season 3 heroes, guilds and Castle Siege owners.</p>
        </a>
    </div>
</section>

<section class="featured">
    <div class="grid-3">
        <?php if ($strongest): ?>
        <article class="panel panel-corner">
            <h2 class="panel-title left">Strongest Hero</h2>
            <p style="text-align:center;font-family:'Cinzel Decorative',serif;font-size:24px;color:var(--gold-light)">
                <?= h($strongest["name"]) ?>
            </p>
            <p style="text-align:center;color:#c8b890;font-size:13px">
                <?= h($strongest["class"][0]) ?> · Lv <?= (int)$strongest["level"] ?>
                · Resets <strong><?= (int)$strongest["resets"] ?></strong>
                <?php if (trim((string)($config["char_master_col"] ?? "")) !== ""): ?>
                    · ML <?= (int)$strongest["master"] ?>
                <?php endif; ?>
            </p>
        </article>
        <?php endif; ?>

        <?php if ($qtop): ?>
        <article class="panel panel-corner">
            <h2 class="panel-title left">Quest Leaders</h2>
            <table class="rank">
                <?php foreach ($qtop as $i => $q): ?>
                <tr><td class="rank-pos"><?= $i+1 ?></td>
                    <td><?= h($q["name"]) ?></td>
                    <td><span class="cls-tag cls-<?= h($q["class"][1]) ?>"><?= h($q["class"][0]) ?></span></td>
                    <td><?= fmt_int($q["quests"]) ?></td></tr>
                <?php endforeach; ?>
            </table>
        </article>
        <?php endif; ?>

        <?php if ($top5g): ?>
        <article class="panel panel-corner">
            <h2 class="panel-title left">Top Guilds</h2>
            <table class="rank">
                <?php foreach ($top5g as $i => $g): ?>
                <tr><td class="rank-pos"><?= $i+1 ?></td>
                    <td><?= h($g["name"]) ?></td>
                    <td><?= h($g["master"]) ?></td>
                    <td><?= fmt_int($g["score"]) ?></td></tr>
                <?php endforeach; ?>
            </table>
        </article>
        <?php endif; ?>

        <?php if ($lastinf): ?>
        <article class="panel panel-corner">
            <h2 class="panel-title left">Latest Heroes</h2>
            <table class="rank">
                <?php foreach ($lastinf as $i => $a): ?>
                <tr><td class="rank-pos"><?= $i+1 ?></td>
                    <td><?= h($a["id"]) ?></td>
                    <td><?= h($a["date"]) ?></td></tr>
                <?php endforeach; ?>
            </table>
        </article>
        <?php endif; ?>

        <?php if ($top5i): ?>
        <article class="panel panel-corner">
            <h2 class="panel-title left">Featured Items</h2>
            <div class="loot-strip">
                <?php foreach ($top5i as $it): ?>
                    <span class="item-slot lg <?= h($it["rar"]) ?>" title="<?= h($it["name"]) ?>">
                        <img src="assets/images/items/<?= h($it["img"]) ?>" alt="">
                    </span>
                <?php endforeach; ?>
            </div>
        </article>
        <?php endif; ?>

        <?php if ($baners): ?>
        <article class="panel panel-corner">
            <h2 class="panel-title left">News</h2>
            <ul style="list-style:none;padding:0;margin:0">
                <?php foreach ($baners as $b): ?>
                <li style="padding:8px 0;border-bottom:1px solid var(--border-gold);display:flex;gap:12px;align-items:center">
                    <img src="assets/icons/<?= h($b["icon"]) ?>" alt="" style="width:28px;height:28px">
                    <div><strong style="color:var(--gold)"><?= h($b["title"]) ?></strong>
                    <div style="color:#c8b890;font-size:12.5px"><?= h($b["text"]) ?></div></div>
                </li>
                <?php endforeach; ?>
            </ul>
        </article>
        <?php endif; ?>
    </div>
</section>
