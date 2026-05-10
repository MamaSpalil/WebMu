<?php if (!defined("insite")) die("no access"); ?>
<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/ranking.svg" alt="">
        <h1 class="page-title"><?= h(lang("rank.title")) ?></h1>
        <p class="page-subtitle">Heroes — Guilds — Castle owners</p>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <div class="stats-bar">
        <div class="stat"><span class="num"><?= fmt_int($stats["accounts"]) ?></span><span class="lbl">Accounts</span></div>
        <div class="stat"><span class="num"><?= fmt_int($stats["characters"]) ?></span><span class="lbl">Characters</span></div>
        <div class="stat"><span class="num"><?= fmt_int($stats["online"]) ?></span><span class="lbl">Online now</span></div>
        <div class="stat"><span class="num"><?= fmt_int($stats["guilds"]) ?></span><span class="lbl">Guilds</span></div>
    </div>

    <div class="tabs" role="tablist">
        <a class="tab<?= $tab==="players"?" active":"" ?>" href="index.php?m=ranking&amp;tab=players"><?= h(lang("rank.players")) ?></a>
        <a class="tab<?= $tab==="guilds" ?" active":"" ?>" href="index.php?m=ranking&amp;tab=guilds"><?= h(lang("rank.guilds")) ?></a>
        <a class="tab<?= $tab==="kills"  ?" active":"" ?>" href="index.php?m=ranking&amp;tab=kills"><?= h(lang("rank.kills")) ?></a>
        <a class="tab<?= $tab==="online" ?" active":"" ?>" href="index.php?m=ranking&amp;tab=online"><?= h(lang("rank.online")) ?></a>
    </div>

    <?php if ($tab === "players"): ?>
    <section class="panel panel-corner">
        <h2 class="panel-title left">Top 100 — by Resets</h2>
        <div class="table-wrap">
            <table class="rank">
                <thead><tr><th>#</th><th>Character</th><th>Class</th><th>Level</th><th>Resets</th><th>Master Lv</th><th>Guild</th></tr></thead>
                <tbody>
                <?php foreach ($players as $i => $p): ?>
                <tr<?= $i<3 ? ' class="top-'.($i+1).'"' : '' ?>>
                    <td class="rank-pos"><?= $i+1 ?></td>
                    <td><?= h($p["Name"]) ?></td>
                    <td><span class="cls-tag cls-<?= h($p["class_h"][1]) ?>"><?= h($p["class_h"][0]) ?></span></td>
                    <td><?= (int)$p["cLevel"] ?></td>
                    <td><?= (int)$p["Resets"] ?></td>
                    <td><?= (int)$p["MasterLevel"] ?></td>
                    <td><?= h($p["G_Name"] ?? "—") ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$players): ?><tr><td colspan="7" class="text-mute" style="text-align:center">No data</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="text-mute mt-20">Refreshes every 60 seconds from the live game database.</p>
    </section>
    <?php elseif ($tab === "guilds"): ?>
    <section class="panel panel-corner">
        <h2 class="panel-title left">Top 50 Guilds</h2>
        <div class="table-wrap">
            <table class="rank">
                <thead><tr><th>#</th><th>Guild</th><th>Master</th><th>Members</th><th>Score</th></tr></thead>
                <tbody>
                <?php foreach ($guilds as $i => $g): ?>
                <tr<?= $i<3 ? ' class="top-'.($i+1).'"' : '' ?>>
                    <td class="rank-pos"><?= $i+1 ?></td>
                    <td><?= h($g["G_Name"]) ?></td>
                    <td><?= h($g["G_Master"]) ?></td>
                    <td><?= (int)$g["members"] ?></td>
                    <td><?= fmt_int($g["total_resets"]) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$guilds): ?><tr><td colspan="5" class="text-mute" style="text-align:center">No data</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php elseif ($tab === "kills"): ?>
    <section class="panel panel-corner">
        <h2 class="panel-title left">PVP Killers</h2>
        <div class="table-wrap">
            <table class="rank">
                <thead><tr><th>#</th><th>Character</th><th>Class</th><th>Level</th><th>Kills</th><th>PK Level</th></tr></thead>
                <tbody>
                <?php foreach ($kills as $i => $k): ?>
                <tr<?= $i<3 ? ' class="top-'.($i+1).'"' : '' ?>>
                    <td class="rank-pos"><?= $i+1 ?></td>
                    <td><?= h($k["Name"]) ?></td>
                    <td><span class="cls-tag cls-<?= h($k["class_h"][1]) ?>"><?= h($k["class_h"][0]) ?></span></td>
                    <td><?= (int)$k["cLevel"] ?></td>
                    <td><?= (int)$k["PkCount"] ?></td>
                    <td><?= (int)$k["PkLevel"] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$kills): ?><tr><td colspan="6" class="text-mute" style="text-align:center">No data</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php else: /* online */ ?>
    <section class="panel panel-corner">
        <h2 class="panel-title left"><?= h(lang("rank.online")) ?></h2>
        <div class="table-wrap">
            <table class="rank">
                <thead><tr><th>#</th><th>Account</th><th>Character</th><th>Class</th><th>Level</th></tr></thead>
                <tbody>
                <?php foreach ($online as $i => $o): ?>
                <tr><td class="rank-pos"><?= $i+1 ?></td>
                    <td><?= h($o["memb___id"]) ?></td>
                    <td><?= h($o["Name"] ?? "—") ?></td>
                    <td><?php if (isset($o["Name"])): ?><span class="cls-tag cls-<?= h($o["class_h"][1]) ?>"><?= h($o["class_h"][0]) ?></span><?php endif; ?></td>
                    <td><?= (int)($o["cLevel"] ?? 0) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$online): ?><tr><td colspan="5" class="text-mute" style="text-align:center">Nobody online right now</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>
</main>
