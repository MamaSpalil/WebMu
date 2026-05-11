<?php if (!defined("insite")) die("no access");
$show_master = isset($players[0]["MasterLevel"]) && trim((string)($config["char_master_col"] ?? "")) !== "";
$has_greset  = isset($players[0]["GReset"]);
$has_stats   = isset($players[0]["Strength"]) || isset($players[0]["Energy"]);
$has_zen     = isset($players[0]["Money"]);
$has_quest   = isset($players[0]["Quest"]);
$has_account = isset($players[0]["AccountID"]);
$has_map     = isset($players[0]["MapNumber"]);
$has_pos     = isset($players[0]["MapPosX"]) && isset($players[0]["MapPosY"]);
$has_exquest = isset($players[0]["ExQuestNum"]);
$player_cols = 5 + ($has_greset ? 1 : 0) + ($show_master ? 1 : 0) + 1 /*Guild*/ + ($has_stats ? 1 : 0) + ($has_zen ? 1 : 0) + ($has_quest ? 1 : 0) + ($has_account ? 1 : 0) + ($has_map ? 1 : 0) + ($has_exquest ? 1 : 0);
$has_guild_mark   = isset($guilds[0]["G_Mark"]);
$has_guild_notice = isset($guilds[0]["G_Notice"]);
$guild_cols = 5 + ($has_guild_mark ? 1 : 0) + ($has_guild_notice ? 1 : 0);
$has_online_map = isset($online[0]["map_h"]);
?>
<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/ranking.svg" alt="">
        <h1 class="page-title"><?= h(lang("rank.title")) ?></h1>
        <p class="page-subtitle">Season 3 rankings from the live game database</p>
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
                <thead><tr>
                    <th>#</th><th>Character</th>
                    <?php if ($has_account): ?><th>Account</th><?php endif; ?>
                    <th>Class</th><th>Level</th><th>Resets</th>
                    <?php if ($has_greset): ?><th title="Grand Reset">GR</th><?php endif; ?>
                    <?php if ($show_master): ?><th>Master Lv</th><?php endif; ?>
                    <?php if ($has_stats): ?><th title="Strength / Agility / Vitality / Energy / Command">Stats</th><?php endif; ?>
                    <?php if ($has_zen): ?><th>Zen</th><?php endif; ?>
                    <?php if ($has_quest): ?><th>Quest</th><?php endif; ?>
                    <?php if ($has_exquest): ?><th title="Ex Quest number">Ex&nbsp;Quest</th><?php endif; ?>
                    <?php if ($has_map): ?><th>Location</th><?php endif; ?>
                    <th>Guild</th>
                </tr></thead>
                <tbody>
                <?php foreach ($players as $i => $p): ?>
                <tr<?= $i<3 ? ' class="top-'.($i+1).'"' : '' ?>>
                    <td class="rank-pos"><?= $i+1 ?></td>
                    <td><a class="char-link" href="index.php?m=character&amp;name=<?= h(urlencode($p["Name"])) ?>"><?= h($p["Name"]) ?></a></td>
                    <?php if ($has_account): ?><td class="text-mute"><?= h($p["AccountID"] ?? "—") ?></td><?php endif; ?>
                    <td><span class="cls-tag cls-<?= h($p["class_h"][1]) ?>"><?= h($p["class_h"][0]) ?></span></td>
                    <td><?= (int)$p["cLevel"] ?></td>
                    <td><?= (int)$p["Resets"] ?></td>
                    <?php if ($has_greset): ?><td><?= (int)($p["GReset"] ?? 0) ?></td><?php endif; ?>
                    <?php if ($show_master): ?><td><?= (int)$p["MasterLevel"] ?></td><?php endif; ?>
                    <?php if ($has_stats): ?>
                        <td class="text-mute" title="STR / AGI / VIT / ENE / CMD">
                            <?= fmt_int($p["Strength"]   ?? 0) ?>/<?= fmt_int($p["Dexterity"]  ?? 0) ?>/<?= fmt_int($p["Vitality"]   ?? 0) ?>/<?= fmt_int($p["Energy"]     ?? 0) ?><?php if (isset($p["Leadership"])): ?>/<?= fmt_int($p["Leadership"]) ?><?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <?php if ($has_zen): ?><td><?= fmt_zen($p["Money"] ?? 0) ?></td><?php endif; ?>
                    <?php if ($has_quest): ?><td><?= (int)($p["Quest"] ?? 0) ?></td><?php endif; ?>
                    <?php if ($has_exquest): ?><td><?= (int)($p["ExQuestNum"] ?? 0) ?></td><?php endif; ?>
                    <?php if ($has_map): ?>
                        <td class="text-mute"><?= h(mu_map((int)($p["MapNumber"] ?? 0))) ?><?php if ($has_pos): ?> <span class="text-mute">(<?= (int)($p["MapPosX"] ?? 0) ?>,<?= (int)($p["MapPosY"] ?? 0) ?><?php if (isset($p["MapDir"])): ?>·<?= (int)$p["MapDir"] ?><?php endif; ?>)</span><?php endif; ?></td>
                    <?php endif; ?>
                    <td><?= h($p["G_Name"] ?? "—") ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$players): ?><tr><td colspan="<?= $player_cols ?>" class="text-mute" style="text-align:center">No data</td></tr><?php endif; ?>
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
                <thead><tr>
                    <th>#</th>
                    <?php if ($has_guild_mark): ?><th>Mark</th><?php endif; ?>
                    <th>Guild</th><th>Master</th><th>Members</th><th>Score</th>
                    <?php if ($has_guild_notice): ?><th>Notice</th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($guilds as $i => $g): ?>
                <tr<?= $i<3 ? ' class="top-'.($i+1).'"' : '' ?>>
                    <td class="rank-pos"><?= $i+1 ?></td>
                    <?php if ($has_guild_mark): ?>
                        <td><?php if (!empty($g["G_Mark"])): ?><span class="guild-mark" title="Guild mark"></span><?php else: ?>—<?php endif; ?></td>
                    <?php endif; ?>
                    <td><?= h($g["G_Name"]) ?></td>
                    <td><?= h($g["G_Master"]) ?></td>
                    <td><?= (int)$g["members"] ?></td>
                    <td><?= fmt_int($g["total_resets"]) ?></td>
                    <?php if ($has_guild_notice): ?>
                        <td class="text-mute"><?= h(mb_strimwidth((string)($g["G_Notice"] ?? ""), 0, 60, "…", "UTF-8")) ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (!$guilds): ?><tr><td colspan="<?= $guild_cols ?>" class="text-mute" style="text-align:center">No data</td></tr><?php endif; ?>
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
                    <td><a class="char-link" href="index.php?m=character&amp;name=<?= h(urlencode($k["Name"])) ?>"><?= h($k["Name"]) ?></a></td>
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
                <thead><tr>
                    <th>#</th><th>Account</th><th>Character</th><th>Class</th><th>Level</th><th>Resets</th>
                    <?php if ($has_online_map): ?><th>Location</th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($online as $i => $o): ?>
                <tr><td class="rank-pos"><?= $i+1 ?></td>
                    <td><?= h($o["memb___id"]) ?></td>
                    <td><?php if (isset($o["Name"])): ?>
                        <a class="char-link" href="index.php?m=character&amp;name=<?= h(urlencode($o["Name"])) ?>"><?= h($o["Name"]) ?></a>
                    <?php else: ?>—<?php endif; ?></td>
                    <td><?php if (isset($o["Name"])): ?><span class="cls-tag cls-<?= h($o["class_h"][1]) ?>"><?= h($o["class_h"][0]) ?></span><?php endif; ?></td>
                    <td><?= (int)($o["cLevel"] ?? 0) ?></td>
                    <td><?= (int)($o["Resets"] ?? 0) ?></td>
                    <?php if ($has_online_map): ?>
                        <td class="text-mute"><?= h($o["map_h"] ?? "—") ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (!$online): ?><tr><td colspan="<?= 6 + ($has_online_map ? 1 : 0) ?>" class="text-mute" style="text-align:center">Nobody online right now</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>
</main>
