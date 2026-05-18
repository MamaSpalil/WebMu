<?php if (!defined("insite")) die("no access");
$qinfo     = $widget_data["qinfo"]   ?? null;
$srv       = $widget_data["server_status"] ?? null;
$srv_stats = $widget_data["server_stats"]  ?? null;
$strongest = $widget_data["strongest"] ?? null;
$lastinf = $widget_data["lastinf"] ?? [];
$top5g   = $widget_data["top5guild"] ?? [];
$qtop    = $widget_data["questtop"]  ?? [];
$top5i   = $widget_data["top5items"] ?? [];
$baners  = $widget_data["baners"]    ?? [];
$home_news       = $home_news       ?? [];
$home_news_total = $home_news_total ?? 0;
$home_news_pages = $home_news_pages ?? 1;
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
    .srv-status { max-width: 560px; margin: 0 auto;
        display:flex; align-items:center; justify-content:center; gap:14px;
        padding:10px 18px;
        background: rgba(14,9,3,0.7); border:1px solid var(--border-gold); border-radius:3px;
        color:var(--gold); font-size:13px; letter-spacing:3px; text-transform:uppercase;}
    .srv-status .pill { display:inline-flex; align-items:center; gap:8px;
        padding:4px 12px; border:1px solid var(--gold); border-radius:999px; font-weight:700;}
    .srv-status .pill.up   { color:#9be39b; border-color:#3e8f3e;
        box-shadow:0 0 12px rgba(80,200,80,.35); }
    .srv-status .pill.down { color:#e89b9b; border-color:#8f3e3e;
        box-shadow:0 0 12px rgba(220,80,80,.35); }
    .srv-status .pill .dot { width:8px; height:8px; border-radius:50%; background:currentColor;
        box-shadow:0 0 8px currentColor; }
    .stats-strip { max-width:1280px; margin:0 auto 30px; padding:0 30px;
        display:grid; gap:18px; grid-template-columns:repeat(4,minmax(0,1fr)); }
    @media (max-width:720px) { .stats-strip { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    .stats-strip .stat {
        text-align:center; padding:18px 14px;
        background:linear-gradient(180deg, rgba(28,18,6,0.85) 0%, rgba(10,6,2,0.92) 100%);
        border:1px solid var(--border-gold); border-radius:4px;
        box-shadow:inset 0 1px 0 rgba(255,240,168,0.10), 0 6px 14px rgba(0,0,0,0.55);}
    .stats-strip .stat .num {
        font-family:'Cinzel Decorative',serif; font-size:28px; color:var(--gold-light);
        text-shadow:0 0 12px rgba(230,195,74,.4); }
    .stats-strip .stat .lbl {
        margin-top:4px; font-size:12px; letter-spacing:3px; text-transform:uppercase; color:#c8b890; }
</style>

<section class="hero">
    <h1 class="hero-title"><?= h($config["server_team"] ?? "WebMu") ?></h1>
    <p class="hero-subtitle">— <?= h($config["server_name"] ?? "MuOnline") ?> —</p>

    <div class="actions">
        <a class="btn-major" href="index.php?m=registration"><span class="icon" aria-hidden="true">⚔</span>JOIN</a>
        <a class="btn-major" href="<?= h($config["forum"] ?? "#") ?>"><span class="icon" aria-hidden="true">📜</span>FORUM</a>
    </div>

    <?php
    // Prefer the dedicated server_status widget (does a TCP probe of the
    // configured game-server IP:Port). Fall back to qinfo when the new
    // widget is not enabled, so existing installs keep showing the bar.
    $online = null; $max = null; $percent = null;
    if ($srv) {
        $online  = $srv["online"];
        $max     = $srv["max"];
        $percent = $srv["percent"];
    } elseif ($qinfo) {
        $online  = $qinfo["online"];
        $max     = $qinfo["max"];
        $percent = $qinfo["percent"];
    }
    ?>

    <?php if ($srv): ?>
        <div class="srv-status">
            <?php if ($srv["probed"]): ?>
                <span class="pill <?= $srv["is_up"] ? "up" : "down" ?>">
                    <span class="dot" aria-hidden="true"></span>
                    <?= $srv["is_up"] ? "Server Online" : "Server Offline" ?>
                </span>
                <span class="text-mute" style="letter-spacing:2px">
                    <?= h($srv["ip"]) ?>:<?= (int)$srv["port"] ?>
                </span>
            <?php else: ?>
                <span class="text-mute">
                    Set <code>server_ip</code> &amp; <code>server_port</code> in <code>opt.php</code>
                    to enable the live server probe.
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($online !== null): ?>
        <div style="max-width:520px;margin:18px auto 0;color:var(--gold);font-size:13px;letter-spacing:3px;text-transform:uppercase">
            Online: <strong><?= fmt_int($online) ?></strong> / <?= fmt_int($max) ?>
            <div class="gauge"><span style="width:<?= (int)$percent ?>%"></span></div>
        </div>
    <?php endif; ?>
</section>

<?php if ($srv_stats): ?>
<section class="stats-strip" aria-label="Server statistics">
    <div class="stat">
        <div class="num"><?= fmt_int($srv_stats["accounts"]) ?></div>
        <div class="lbl">Accounts</div>
    </div>
    <div class="stat">
        <div class="num"><?= fmt_int($srv_stats["characters"]) ?></div>
        <div class="lbl">Characters</div>
    </div>
    <div class="stat">
        <div class="num"><?= fmt_int($srv_stats["guilds"]) ?></div>
        <div class="lbl">Guilds</div>
    </div>
    <div class="stat">
        <div class="num"><?= fmt_int($srv_stats["online"]) ?></div>
        <div class="lbl">Online now</div>
    </div>
</section>
<?php endif; ?>

<section class="news-on-home" aria-label="Latest news">
    <div class="news-on-home-inner">
        <header class="news-on-home-head">
            <h2 class="news-on-home-title"><?= h(lang("news.title", "News")) ?></h2>
            <p class="news-on-home-sub"><?= h(lang("news.subtitle", "Announcements")) ?></p>
        </header>
        <?php if (!$home_news): ?>
            <p class="text-mute" style="text-align:center"><?= h(lang("news.empty", "No news posts yet.")) ?></p>
        <?php else: ?>
            <div class="news-on-home-list">
                <?php foreach ($home_news as $n):
                    $title  = trim((string)($n["title"]  ?? ""));
                    $body   = trim((string)($n["body"]   ?? ""));
                    $author = trim((string)($n["author"] ?? ""));
                    $ts     = (string)($n["posted_at"] ?? "");
                    $ts_iso = $ts !== "" ? date("Y-m-d\TH:i", strtotime($ts) ?: time()) : "";
                    $ts_h   = $ts !== "" ? date("d.m.Y H:i", strtotime($ts) ?: time()) : "";
                ?>
                    <article class="news-post panel panel-corner">
                        <header class="news-post-head">
                            <h3 class="news-post-title"><?= h($title) ?></h3>
                            <div class="news-post-meta">
                                <span class="news-post-author"><?= h(lang("news.posted_by", "Posted by")) ?>
                                    <strong><?= h($author !== "" ? $author : lang("news.author", "Administrator")) ?></strong></span>
                                <time class="news-post-date" datetime="<?= h($ts_iso) ?>"><?= h($ts_h) ?></time>
                            </div>
                        </header>
                        <div class="news-post-body"><?= nl2br(h($body)) ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($home_news_pages > 1): ?>
            <?= pager_html(1, $home_news_pages, "index.php?m=news&page=%d") ?>
        <?php endif; ?>
    </div>
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
                <a class="char-link" href="index.php?m=character&amp;name=<?= h(urlencode($strongest["name"])) ?>"><?= h($strongest["name"]) ?></a>
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
                    <td><a class="char-link" href="index.php?m=character&amp;name=<?= h(urlencode($q["name"])) ?>"><?= h($q["name"]) ?></a></td>
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
