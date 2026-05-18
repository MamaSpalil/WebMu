<?php if (!defined("insite")) die("no access");
// Admin panel — single template, branches on $sub.
$tabs = [
    "dashboard" => "admin.tab.dashboard",
    "news"      => "admin.tab.news",
    "users"     => "admin.tab.users",
    "warehouse" => "admin.tab.warehouse",
    "market"    => "admin.tab.market",
    "vip"       => "admin.tab.vip",
    "log"       => "admin.tab.log",
];
$wh_cols = $wh_cols ?? 8;
?>
<main class="page admin-page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/shield.svg" alt="">
        <h1 class="page-title"><?= h(lang("admin.title")) ?></h1>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <nav class="admin-tabs">
        <?php foreach ($tabs as $k => $lk): ?>
            <a class="nav-btn<?= $sub === $k ? " active" : "" ?>"
               href="index.php?m=admin&sub=<?= h($k) ?>"><?= h(lang($lk)) ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($sub === "dashboard"): ?>
        <section class="panel panel-corner">
            <h2 class="panel-title left"><?= h(lang("admin.tab.dashboard")) ?></h2>
            <div class="balance-bar">
                <div class="bal"><img src="assets/icons/registration.svg" alt="">
                    <div><div class="v"><?= fmt_int($accounts) ?></div><div class="l">Accounts</div></div></div>
                <div class="bal"><img src="assets/icons/ranking.svg" alt="">
                    <div><div class="v"><?= fmt_int($online) ?></div><div class="l">Online now</div></div></div>
                <div class="bal"><img src="assets/icons/market.svg" alt="">
                    <div><div class="v"><?= fmt_int($listed) ?></div><div class="l">Listed</div></div></div>
                <div class="bal"><img src="assets/icons/shield.svg" alt="">
                    <div><div class="v"><?= fmt_int($vip_active) ?></div><div class="l">Active VIP</div></div></div>
                <div class="bal"><img src="assets/icons/scroll.svg" alt="">
                    <div><div class="v"><?= fmt_int($log_24h) ?></div><div class="l">Log (24h)</div></div></div>
            </div>
        </section>

    <?php elseif ($sub === "users"): ?>
        <section class="panel panel-corner">
            <h2 class="panel-title left"><?= h(lang("admin.tab.users")) ?></h2>
            <form method="get" action="index.php" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap">
                <input type="hidden" name="m" value="admin">
                <input type="hidden" name="sub" value="users">
                <div class="field" style="flex:1;min-width:240px">
                    <label for="q"><?= h(lang("admin.search")) ?></label>
                    <input id="q" name="q" type="text" value="<?= h($q) ?>" maxlength="80">
                </div>
                <button class="btn small" type="submit">→</button>
            </form>
            <?php if ($q !== "" && !$users): ?>
                <p class="text-mute mt-20"><?= h(lang("admin.no_results")) ?></p>
            <?php elseif ($users): ?>
                <div class="table-wrap mt-20">
                <table class="rank">
                    <thead><tr><th>Account</th><th>E-mail</th><th>Credits</th><th>WCoin</th><th>USDT</th><th>Adjust</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><a href="index.php?m=admin&sub=warehouse&acc=<?= h(urlencode($u["id"])) ?>"><?= h($u["id"]) ?></a></td>
                            <td><?= h($u["mail"]) ?></td>
                            <td><?= isset($u["credits"]) && $u["credits"] !== null ? fmt_int($u["credits"]) : "—" ?></td>
                            <td><?= isset($u["wcoin"]) ? fmt_int($u["wcoin"]) : "—" ?></td>
                            <td><?= isset($u["usdt"])  && $u["usdt"]  !== null ? h(rtrim(rtrim((string)$u["usdt"], "0"), ".")) : "—" ?></td>
                            <td>
                                <form method="post" action="index.php?m=admin" style="display:flex;gap:4px;align-items:center">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="adjust_balance">
                                    <input type="hidden" name="account" value="<?= h($u["id"]) ?>">
                                    <select name="kind">
                                        <option value="credits">Credits</option>
                                        <option value="wcoin">WCoin</option>
                                        <option value="usdt">USDT</option>
                                    </select>
                                    <input type="text" name="delta" placeholder="±N" size="6" required>
                                    <button class="btn small" type="submit"><?= h(lang("admin.adjust")) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </section>

    <?php elseif ($sub === "warehouse"): ?>
        <section class="panel panel-corner">
            <h2 class="panel-title left"><?= h(lang("admin.tab.warehouse")) ?></h2>
            <form method="get" action="index.php" style="display:flex;gap:8px;align-items:end">
                <input type="hidden" name="m" value="admin">
                <input type="hidden" name="sub" value="warehouse">
                <div class="field" style="flex:1;min-width:200px">
                    <label for="acc">Account</label>
                    <input id="acc" name="acc" type="text" value="<?= h($acc) ?>" maxlength="10" pattern="[A-Za-z0-9]+">
                </div>
                <button class="btn small" type="submit">→</button>
            </form>
            <?php if ($slots !== null): ?>
                <div class="wh-grid mt-20" style="--wh-cols:<?= (int)$wh_cols ?>">
                    <?php foreach ($slots as $idx => $s):
                        $empty = !empty($s["empty"]);
                        $img = (string)($s["image"] ?? "");
                        if (!preg_match('~^[A-Za-z0-9_.-]+\.gif$~', $img)) $img = "";
                    ?>
                        <div class="wh-cell<?= $empty ? " empty" : "" ?>"
                             title="#<?= (int)$idx ?><?= $empty ? "" : " · " . h($s["name"]) ?>">
                            <?php if (!$empty && $img !== ""): ?>
                                <img src="assets/images/items/<?= h($img) ?>" alt="">
                            <?php endif; ?>
                            <?php if (!$empty && !empty($s["level"])): ?><span class="lvl">+<?= (int)$s["level"] ?></span><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($acc !== ""): ?>
                <p class="text-mute mt-20"><?= h(lang("admin.no_results")) ?></p>
            <?php endif; ?>
        </section>

    <?php elseif ($sub === "market"): ?>
        <section class="panel panel-corner">
            <h2 class="panel-title left"><?= h(lang("admin.tab.market")) ?></h2>
            <?php if (!$listings): ?>
                <p class="text-mute"><?= h(lang("market.empty")) ?></p>
            <?php else: ?>
                <div class="table-wrap">
                <table class="rank">
                    <thead><tr><th>#</th><th>Seller</th><th>Item</th><th>Price</th><th>Listed</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($listings as $l): ?>
                        <tr>
                            <td><?= (int)$l["id"] ?></td>
                            <td><?= h($l["seller_account"]) ?></td>
                            <td><?= h($l["item_name"]) ?>
                                <?php if ((int)$l["item_level"] > 0): ?> +<?= (int)$l["item_level"] ?><?php endif; ?>
                                <?php if ((int)$l["qty"] > 1):       ?> ×<?= (int)$l["qty"] ?><?php endif; ?>
                            </td>
                            <td><?= h(rtrim(rtrim((string)$l["price"], "0"), ".")) ?> <span class="text-mute"><?= h($l["currency"]) ?></span></td>
                            <td><?= h((string)$l["listed_at"]) ?></td>
                            <td>
                                <form method="post" action="index.php?m=market_cancel" style="margin:0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$l["id"] ?>">
                                    <button class="btn small" type="submit"><?= h(lang("admin.cancel_listing")) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </section>

    <?php elseif ($sub === "vip"): ?>
        <section class="panel panel-corner">
            <h2 class="panel-title left"><?= h(lang("admin.grant_vip")) ?></h2>
            <form method="post" action="index.php?m=admin" class="form-grid" style="align-items:end">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="grant_vip">
                <div class="field">
                    <label for="g_account">Account</label>
                    <input id="g_account" name="account" type="text" maxlength="10" pattern="[A-Za-z0-9]+" required>
                </div>
                <div class="field">
                    <label for="g_type">VIP type</label>
                    <input id="g_type" name="vip_type" type="number" min="1" max="255" value="1" required>
                </div>
                <div class="field">
                    <label for="g_days">Days</label>
                    <input id="g_days" name="days" type="number" min="1" max="365" value="30" required>
                </div>
                <div class="field"><button class="btn small" type="submit"><?= h(lang("admin.grant_vip")) ?></button></div>
            </form>
        </section>

        <section class="panel panel-corner">
            <h2 class="panel-title left">Active VIP</h2>
            <?php if (!$active): ?>
                <p class="text-mute"><?= h(lang("admin.no_results")) ?></p>
            <?php else: ?>
                <div class="table-wrap">
                <table class="rank">
                    <thead><tr><th>Account</th><th>Type</th><th>Expires</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($active as $v): ?>
                        <tr>
                            <td><?= h((string)$v["account"]) ?></td>
                            <td>VIP <?= (int)$v["vip_type"] ?></td>
                            <td><?= h((string)$v["expire"]) ?></td>
                            <td>
                                <form method="post" action="index.php?m=admin" style="margin:0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="revoke_vip">
                                    <input type="hidden" name="account" value="<?= h((string)$v["account"]) ?>">
                                    <button class="btn small" type="submit"><?= h(lang("admin.revoke_vip")) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </section>

    <?php elseif ($sub === "news"): ?>
        <?php
        $edit = $edit_post ?? null;
        $edit_id    = (int)($edit["id"] ?? 0);
        $edit_title = (string)($edit["title"] ?? "");
        $edit_body  = (string)($edit["body"]  ?? "");
        ?>
        <section class="panel panel-corner">
            <h2 class="panel-title left">
                <?= $edit_id > 0 ? h(lang("admin.news.edit")) : h(lang("admin.news.create")) ?>
            </h2>
            <?php if (!empty($no_table)): ?>
                <p class="note warn"><?= h(lang("admin.news.no_table")) ?></p>
            <?php else: ?>
                <form method="post" action="index.php?m=admin" class="form-grid" style="display:block">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="news_save">
                    <input type="hidden" name="id" value="<?= (int)$edit_id ?>">
                    <div class="field">
                        <label for="n_title"><?= h(lang("admin.news.title")) ?></label>
                        <input id="n_title" name="title" type="text" maxlength="160"
                               value="<?= h($edit_title) ?>" required>
                    </div>
                    <div class="field">
                        <label for="n_body"><?= h(lang("admin.news.body")) ?></label>
                        <textarea id="n_body" name="body" rows="6" maxlength="8000" required><?= h($edit_body) ?></textarea>
                    </div>
                    <div class="field" style="display:flex;gap:8px">
                        <button class="btn small" type="submit">
                            <?= $edit_id > 0 ? h(lang("admin.news.update")) : h(lang("admin.news.create")) ?>
                        </button>
                        <?php if ($edit_id > 0): ?>
                            <a class="btn small" href="index.php?m=admin&amp;sub=news"><?= h(lang("admin.news.cancel")) ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <?php if (empty($no_table)): ?>
        <section class="panel panel-corner">
            <h2 class="panel-title left"><?= h(lang("admin.news.list")) ?></h2>
            <?php if (!$news): ?>
                <p class="text-mute"><?= h(lang("admin.news.empty")) ?></p>
            <?php else: ?>
                <div class="table-wrap">
                <table class="rank">
                    <thead><tr><th>#</th><th><?= h(lang("admin.news.title")) ?></th><th><?= h(lang("news.author")) ?></th><th><?= h(lang("news.posted_by")) ?></th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($news as $n): ?>
                        <tr>
                            <td><?= (int)$n["id"] ?></td>
                            <td><?= h((string)$n["title"]) ?></td>
                            <td><?= h((string)($n["author"] ?? "")) ?></td>
                            <td><?= h((string)($n["posted_at"] ?? "")) ?></td>
                            <td style="white-space:nowrap">
                                <a class="btn small" href="index.php?m=admin&amp;sub=news&amp;edit=<?= (int)$n["id"] ?>"><?= h(lang("admin.news.edit")) ?></a>
                                <form method="post" action="index.php?m=admin" style="display:inline;margin:0"
                                      onsubmit="return confirm('<?= h(lang("admin.news.confirm_delete")) ?>');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="news_delete">
                                    <input type="hidden" name="id" value="<?= (int)$n["id"] ?>">
                                    <button class="btn small" type="submit"><?= h(lang("admin.news.delete")) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

    <?php elseif ($sub === "log"): ?>
        <section class="panel panel-corner">
            <h2 class="panel-title left"><?= h(lang("admin.tab.log")) ?></h2>
            <form method="get" action="index.php" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap">
                <input type="hidden" name="m" value="admin">
                <input type="hidden" name="sub" value="log">
                <div class="field"><label for="acc">Account</label>
                    <input id="acc" name="acc" type="text" value="<?= h($filter_account) ?>" maxlength="10"></div>
                <div class="field"><label for="action">Action</label>
                    <input id="action" name="action" type="text" value="<?= h($filter_action) ?>" maxlength="32"></div>
                <button class="btn small" type="submit">→</button>
            </form>
            <?php if (!$entries): ?>
                <p class="text-mute mt-20"><?= h(lang("admin.no_results")) ?></p>
            <?php else: ?>
                <div class="table-wrap mt-20">
                <table class="rank">
                    <thead><tr><th>Time</th><th>IP</th><th>Account</th><th>Action</th><th>Details</th></tr></thead>
                    <tbody>
                    <?php foreach ($entries as $e): ?>
                        <tr>
                            <td><?= h((string)$e["ts"]) ?></td>
                            <td><?= h((string)$e["ip"]) ?></td>
                            <td><?= h((string)($e["account"] ?? "")) ?></td>
                            <td><code><?= h((string)$e["action"]) ?></code></td>
                            <td><span class="text-mute" style="font-size:12px"><?= h((string)($e["details"] ?? "")) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </section>

    <?php endif; ?>
</main>
