<?php if (!defined("insite")) die("no access");
// Character profile template (?m=character&name=…).
// Variables in scope: name, account, level, resets, greset, master,
// strength, dexterity, vitality, energy, leadership, money,
// map_number, map_x, map_y, pk_count, pk_level, class (label/slug),
// class_code, guild, online, equipped[12], has_inventory.
$is_dl = (($class_code >> 4) & 0x0F) === 0x4;
$layout = mu_equipment_layout();
$map_name = $map_number !== null ? mu_map($map_number) : null;
?>
<style>
    .char-grid { display:grid; grid-template-columns: 1.1fr 1fr; gap: 28px; align-items:start; }
    @media (max-width: 1000px) { .char-grid { grid-template-columns: 1fr; } }
    .char-head { display:flex; align-items:center; gap:18px; flex-wrap:wrap; margin-bottom:14px; }
    .char-head .nm { font-family:'Cinzel Decorative',serif; font-size:clamp(22px,3vw,32px);
        color:var(--gold-light); letter-spacing:3px; text-shadow:0 0 12px rgba(230,195,74,.35); }
    .pill { display:inline-flex; align-items:center; gap:8px;
        padding:3px 12px; border:1px solid var(--gold); border-radius:999px;
        font-size:11.5px; letter-spacing:2px; text-transform:uppercase; color:var(--gold); }
    .pill .dot { width:8px; height:8px; border-radius:50%; background:currentColor;
        box-shadow:0 0 8px currentColor; }
    .pill.up   { color:#9be39b; border-color:#3e8f3e; }
    .pill.down { color:#888; border-color:#444; }

    .stat-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; margin-top:6px; }
    .stat-grid .kv { display:flex; justify-content:space-between; padding:9px 12px;
        background:rgba(14,9,3,0.55); border:1px solid var(--border-gold); border-radius:3px;
        font-size:13.5px; }
    .stat-grid .kv .k { color:#c8b890; letter-spacing:1.5px; text-transform:uppercase; font-size:11.5px; }
    .stat-grid .kv .v { color:var(--gold-light); font-weight:700; }

    .equip-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr));
        gap:16px 18px; margin-top:10px; }
    .equip-cell { min-height:116px; display:flex; flex-direction:column; align-items:center; gap:7px;
        text-align:center; position:relative; }
    .equip-cell.empty { opacity:.62; }
    .equip-cell .slot-box { width:78px; height:78px; border-radius:5px;
        background:rgba(34,48,65,.78); border:1px solid rgba(140,160,184,.28);
        display:flex; align-items:center; justify-content:center; position:relative;
        overflow:visible; box-shadow:inset 0 0 0 1px rgba(255,255,255,.03); }
    .equip-cell .slot-box img { max-width:70px; max-height:70px; object-fit:contain; }
    .equip-cell .slot-box .lvl { position:absolute; top:-10px; right:-10px;
        padding:2px 6px; border-radius:4px; background:#ff4d55; color:#fff;
        font-size:13px; line-height:1.2; font-weight:800; letter-spacing:.4px; }
    .equip-cell .item { font-size:13.5px; color:#d8deeb; font-weight:500; word-break:break-word; }
    .equip-cell .item small { display:block; color:#8e98aa; font-weight:400; font-size:11px; margin-top:2px; }
    .equip-cell .badges { display:flex; flex-wrap:wrap; justify-content:center; gap:4px; }
    .equip-cell .badges span { font-size:9.5px; letter-spacing:1.2px; padding:1px 5px;
        border:1px solid var(--gold-dark); border-radius:2px; color:var(--gold); }
</style>

<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/ranking.svg" alt="">
        <h1 class="page-title"><?= h($name) ?></h1>
        <p class="page-subtitle">
            <span class="cls-tag cls-<?= h($class[1]) ?>"><?= h($class[0]) ?></span>
            <?php if ($guild !== ""): ?>
                · Guild <strong style="color:var(--gold-light)"><?= h($guild) ?></strong>
            <?php endif; ?>
        </p>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <div class="char-head">
        <span class="pill <?= $online ? "up" : "down" ?>">
            <span class="dot" aria-hidden="true"></span>
            <?= $online ? "Online" : "Offline" ?>
        </span>
        <?php if ($account !== ""): ?>
            <span class="text-mute" style="letter-spacing:2px">Account: <strong><?= h($account) ?></strong></span>
        <?php endif; ?>
    </div>

    <div class="char-grid">
        <!-- Left column: progress + stats + location -->
        <section class="panel panel-corner">
            <h2 class="panel-title left">Progress</h2>
            <div class="stat-grid">
                <div class="kv"><span class="k">Level</span><span class="v"><?= fmt_int($level) ?></span></div>
                <div class="kv"><span class="k">Resets</span><span class="v"><?= fmt_int($resets) ?></span></div>
                <?php if ($greset !== null): ?>
                    <div class="kv"><span class="k">Grand Reset</span><span class="v"><?= fmt_int($greset) ?></span></div>
                <?php endif; ?>
                <?php if ($master !== null): ?>
                    <div class="kv"><span class="k">Master Lv</span><span class="v"><?= fmt_int($master) ?></span></div>
                <?php endif; ?>
                <div class="kv"><span class="k">PK Count</span><span class="v"><?= fmt_int($pk_count) ?></span></div>
                <div class="kv"><span class="k">PK Level</span><span class="v"><?= fmt_int($pk_level) ?></span></div>
            </div>

            <h2 class="panel-title left" style="margin-top:22px">Stats</h2>
            <div class="stat-grid">
                <?php if ($strength  !== null): ?><div class="kv"><span class="k">Strength</span><span class="v"><?= fmt_int($strength) ?></span></div><?php endif; ?>
                <?php if ($dexterity !== null): ?><div class="kv"><span class="k">Agility</span><span class="v"><?= fmt_int($dexterity) ?></span></div><?php endif; ?>
                <?php if ($vitality  !== null): ?><div class="kv"><span class="k">Vitality</span><span class="v"><?= fmt_int($vitality) ?></span></div><?php endif; ?>
                <?php if ($energy    !== null): ?><div class="kv"><span class="k">Energy</span><span class="v"><?= fmt_int($energy) ?></span></div><?php endif; ?>
                <?php if ($is_dl && $leadership !== null): ?>
                    <div class="kv"><span class="k">Command</span><span class="v"><?= fmt_int($leadership) ?></span></div>
                <?php endif; ?>
                <?php if ($money !== null): ?>
                    <div class="kv"><span class="k">Zen</span><span class="v"><?= fmt_zen($money) ?></span></div>
                <?php endif; ?>
            </div>

            <?php if ($map_name !== null): ?>
            <h2 class="panel-title left" style="margin-top:22px">Location</h2>
            <div class="stat-grid">
                <div class="kv"><span class="k">Map</span><span class="v"><?= h($map_name) ?> <small style="color:#a0916a">#<?= (int)$map_number ?></small></span></div>
                <?php if ($map_x !== null): ?><div class="kv"><span class="k">Coord X</span><span class="v"><?= (int)$map_x ?></span></div><?php endif; ?>
                <?php if ($map_y !== null): ?><div class="kv"><span class="k">Coord Y</span><span class="v"><?= (int)$map_y ?></span></div><?php endif; ?>
            </div>
            <?php endif; ?>
        </section>

        <!-- Right column: equipped inventory -->
        <section class="panel panel-corner">
            <h2 class="panel-title left">Equipped</h2>
            <?php if (!$has_inventory): ?>
                <p class="text-mute">Inventory column is not available in this database.</p>
            <?php else: ?>
            <div class="equip-grid">
                <?php foreach ($layout as $cell):
                    $idx = (int)$cell["slot"];
                    $s = $equipped[$idx] ?? ["empty" => true, "name" => "Empty", "image" => ""];
                    $image = (string)($s["image"] ?? "");
                    if (!preg_match('~^[A-Za-z0-9_.-]+\.gif$~', $image)) $image = "";
                    ?>
                    <div class="equip-cell<?= empty($s["empty"]) ? "" : " empty" ?>">
                        <div class="slot-box" title="<?= h($cell["label"]) ?>">
                        <?php if (empty($s["empty"])): ?>
                            <?php if ($image !== ""): ?>
                                <img src="assets/images/items/<?= h($image) ?>" alt="<?= h($s["name"] ?? "Unknown") ?>">
                            <?php endif; ?>
                            <?php if (!empty($s["level"])): ?>
                                <span class="lvl">+<?= (int)$s["level"] ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                        </div>
                        <?php if (empty($s["empty"])): ?>
                            <span class="item"><?= h($s["name"] ?? "Unknown") ?></span>
                            <?php if (!empty($s["skill"]) || !empty($s["luck"]) || !empty($s["exc"])): ?>
                            <div class="badges">
                                <?php if (!empty($s["skill"])): ?><span>Skill</span><?php endif; ?>
                                <?php if (!empty($s["luck"]))  : ?><span>Luck</span><?php endif; ?>
                                <?php if (!empty($s["exc"]))   : ?><span>Exc</span><?php endif; ?>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="item text-mute">Empty</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="text-mute mt-20" style="font-size:12px">
                Live snapshot from the game database (cached 30 s).
            </p>
            <?php endif; ?>
        </section>
    </div>

    <p class="text-mute mt-20"><a href="index.php?m=ranking">← Back to rankings</a></p>
</main>
