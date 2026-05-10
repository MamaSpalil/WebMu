<?php if (!defined("insite")) die("no access"); ?>
<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/images/crest-season3.svg" alt="">
        <h1 class="page-title"><?= h(lang("nav.about")) ?></h1>
        <p class="page-subtitle">Season 3 Episode 1 — classic continent reborn</p>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <div class="season-banner panel-corner">
        <img src="assets/images/season3-banner.svg" alt="">
        <div>
            <h2><?= h($config["server_name"] ?? "MuOnline") ?></h2>
            <p>Dark fantasy progression, Castle Siege rivalry, Blood Castle, Devil Square and guild warfare tuned for a classic Season 3 Episode 1 experience.</p>
        </div>
    </div>

    <div class="stats-bar">
        <div class="stat"><span class="num"><?= fmt_int($stats["accounts"]) ?></span><span class="lbl">Accounts</span></div>
        <div class="stat"><span class="num"><?= fmt_int($stats["characters"]) ?></span><span class="lbl">Characters</span></div>
        <div class="stat"><span class="num"><?= fmt_int($stats["online"]) ?></span><span class="lbl">Online now</span></div>
        <div class="stat"><span class="num"><?= fmt_int($stats["guilds"]) ?></span><span class="lbl">Guilds</span></div>
    </div>

    <section class="grid-3">
        <article class="card">
            <img class="card-icon" src="assets/icons/sword.svg" alt="">
            <div class="card-title">Classic classes</div>
            <p>Dark Knight, Dark Wizard, Fairy Elf, Magic Gladiator and Dark Lord progression using your live MuOnline database.</p>
        </article>
        <article class="card">
            <img class="card-icon" src="assets/icons/castle.svg" alt="">
            <div class="card-title">Guild warfare</div>
            <p>Guild, GuildMember and character reset data power rankings and server widgets.</p>
        </article>
        <article class="card">
            <img class="card-icon" src="assets/icons/gem.svg" alt="">
            <div class="card-title">Configurable economy</div>
            <p>Credits, WCoin and vote points are mapped in <code>opt.php</code>, so admins can match their own schema.</p>
        </article>
    </section>
</main>
