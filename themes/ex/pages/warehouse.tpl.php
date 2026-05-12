<?php if (!defined("insite")) die("no access");
// Web-Сундук — read-only grid + "list on market" form.
// Vars in scope: has_table, slots[wh_slots], wh_cols, money, is_offline,
//                currencies[], my_listings[].
?>
<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/market.svg" alt="">
        <h1 class="page-title"><?= h(lang("wh.title")) ?></h1>
        <p class="page-subtitle"><?= h(lang("wh.subtitle")) ?></p>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <?php if (!$has_table): ?>
        <section class="panel panel-corner">
            <p class="text-mute"><?= h(lang("wh.not_configured")) ?></p>
        </section>
    <?php else: ?>

    <?php if ($money !== null): ?>
        <div class="balance-bar">
            <div class="bal">
                <img src="assets/icons/coins.svg" alt="">
                <div><div class="v"><?= fmt_zen($money) ?></div>
                <div class="l"><?= h(lang("wh.zen_balance")) ?></div></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$is_offline): ?>
        <p class="note warn"><?= h(lang("wh.must_be_offline")) ?></p>
    <?php endif; ?>

    <section class="panel panel-corner">
        <h2 class="panel-title left"><?= h(lang("wh.title")) ?></h2>

        <?php
        $non_empty = 0;
        foreach ($slots as $s) if (empty($s["empty"])) $non_empty++;
        if ($non_empty === 0): ?>
            <p class="text-mute"><?= h(lang("wh.empty")) ?></p>
        <?php else:
            // Pre-compute (col, row, w, h) for every visible cell. Items
            // spanning (w × h) cells are clamped against the grid bounds,
            // and cells covered by another item's footprint are dropped
            // so the placeholder doesn't show through.
            $wh_rows = (int)ceil($wh_slots / max(1, $wh_cols));
            $cells = [];
            $covered = [];
            foreach ($slots as $idx => $s) {
                $col0 = $idx % $wh_cols;
                $row0 = intdiv($idx, $wh_cols);
                $empty = !empty($s["empty"]);
                if ($empty) {
                    $w = 1; $h = 1;
                } else {
                    $w = max(1, (int)($s["w"] ?? 1));
                    $h = max(1, (int)($s["h"] ?? 1));
                    if ($col0 + $w > $wh_cols) $w = max(1, $wh_cols - $col0);
                    if ($row0 + $h > $wh_rows) $h = max(1, $wh_rows - $row0);
                }
                $cells[$idx] = ["col" => $col0, "row" => $row0, "w" => $w, "h" => $h, "empty" => $empty];
                if (!$empty && ($w > 1 || $h > 1)) {
                    for ($dy = 0; $dy < $h; $dy++) {
                        for ($dx = 0; $dx < $w; $dx++) {
                            if ($dx === 0 && $dy === 0) continue;
                            $covered[($row0 + $dy) * $wh_cols + ($col0 + $dx)] = true;
                        }
                    }
                }
            }
        ?>
            <div class="wh-grid" style="--wh-cols:<?= (int)$wh_cols ?>;--wh-rows:<?= (int)$wh_rows ?>">
                <?php foreach ($slots as $idx => $s):
                    if (!empty($covered[$idx])) continue;
                    $c = $cells[$idx];
                    $empty = $c["empty"];
                    $image = (string)($s["image"] ?? "");
                    if (!preg_match('~^[A-Za-z0-9_.-]+\.gif$~', $image)) $image = "";
                    $cell_style = "grid-column: " . ($c["col"] + 1) . " / span " . $c["w"]
                                . "; grid-row: " . ($c["row"] + 1) . " / span " . $c["h"] . ";";
                    $cell_class = "wh-cell" . ($empty ? " empty" : "")
                                . ($c["w"] > 1 ? " w" . $c["w"] : "")
                                . ($c["h"] > 1 ? " h" . $c["h"] : "");
                ?>
                <div class="<?= h($cell_class) ?>" style="<?= h($cell_style) ?>"
                     title="<?= h(lang("wh.slot")) ?> #<?= (int)$idx ?><?= $empty ? "" : " · " . h($s["name"] ?? "") ?>">
                    <?php if (!$empty): ?>
                        <?php if ($image !== ""): ?>
                            <img src="assets/images/items/<?= h($image) ?>" alt="<?= h($s["name"] ?? "") ?>">
                        <?php endif; ?>
                        <?php if (!empty($s["level"])): ?>
                            <span class="lvl">+<?= (int)$s["level"] ?></span>
                        <?php endif; ?>
                        <?php if (!empty($s["exc"])): ?><span class="badge exc">Exc</span><?php endif; ?>
                        <?php if (!empty($s["luck"])): ?><span class="badge luck">L</span><?php endif; ?>
                        <?php if (!empty($s["skill"])): ?><span class="badge skill">S</span><?php endif; ?>
                        <?php if ($is_offline && !empty($currencies)): ?>
                            <button type="button" class="wh-list-btn"
                                    data-slot="<?= (int)$idx ?>"
                                    data-name="<?= h($s["name"] ?? "") ?>"
                                    title="<?= h(lang("wh.list_btn")) ?>">＋</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if (!empty($my_listings)): ?>
        <section class="panel panel-corner">
            <h2 class="panel-title left"><?= h(lang("market.my_listings")) ?></h2>
            <div class="table-wrap">
            <table class="rank">
                <thead><tr><th>#</th><th>Item</th><th>Price</th><th>Listed</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($my_listings as $l): ?>
                    <tr>
                        <td><?= (int)$l["id"] ?></td>
                        <td><?= h($l["item_name"]) ?>
                            <?php if ((int)$l["item_level"] > 0): ?> +<?= (int)$l["item_level"] ?><?php endif; ?>
                            <?php if ((int)$l["qty"] > 1): ?> ×<?= (int)$l["qty"] ?><?php endif; ?>
                        </td>
                        <td><?= h(rtrim(rtrim((string)$l["price"], "0"), ".")) ?>
                            <span class="text-mute"><?= h($l["currency"]) ?></span></td>
                        <td><?= h((string)$l["listed_at"]) ?></td>
                        <td>
                            <form action="index.php?m=market_cancel" method="post" style="margin:0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$l["id"] ?>">
                                <button type="submit" class="btn small"><?= h(lang("market.cancel_btn")) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($currencies) && $is_offline): ?>
    <!-- Listing modal (lightweight, no JS framework) -->
    <div id="wh-list-modal" class="wh-modal" hidden>
        <form action="index.php?m=market_list" method="post" class="panel panel-corner wh-modal-body">
            <?= csrf_field() ?>
            <h2 class="panel-title left"><?= h(lang("wh.list_title")) ?></h2>
            <p id="wh-list-item" class="text-mute"></p>
            <input type="hidden" name="wh_slot" id="wh-list-slot" value="">
            <div class="form-grid">
                <div class="field">
                    <label for="wh-list-currency"><?= h(lang("wh.list_currency")) ?></label>
                    <select id="wh-list-currency" name="currency" required>
                        <?php foreach ($currencies as $c): ?>
                            <option value="<?= h($c["id"]) ?>"><?= h($c["label"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="wh-list-price"><?= h(lang("wh.list_price")) ?></label>
                    <input id="wh-list-price" name="price" type="number" min="1" step="1"
                           max="<?= (int)($config["market_max_price"] ?? 99999999) ?>" required>
                </div>
                <div class="field">
                    <label for="wh-list-qty"><?= h(lang("wh.list_qty")) ?></label>
                    <input id="wh-list-qty" name="qty" type="number" min="1" max="255" value="1">
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px">
                <button type="button" class="btn ghost" onclick="document.getElementById('wh-list-modal').hidden=true">×</button>
                <button type="submit" class="btn"><?= h(lang("wh.list_submit")) ?></button>
            </div>
        </form>
    </div>
    <script>
    (function(){
        var modal = document.getElementById('wh-list-modal');
        var slotInp = document.getElementById('wh-list-slot');
        var itemLbl = document.getElementById('wh-list-item');
        document.querySelectorAll('.wh-list-btn').forEach(function(btn){
            btn.addEventListener('click', function(){
                slotInp.value = btn.getAttribute('data-slot');
                itemLbl.textContent = (btn.getAttribute('data-name') || '') +
                    ' (' + <?= json_encode(lang("wh.slot")) ?> + ' #' + btn.getAttribute('data-slot') + ')';
                modal.hidden = false;
            });
        });
        modal.addEventListener('click', function(e){ if (e.target === modal) modal.hidden = true; });
    })();
    </script>
    <?php endif; ?>

    <?php endif; ?>
</main>
