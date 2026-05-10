<?php if (!defined("insite")) die("no access");
$show_master = trim((string)($config["char_master_col"] ?? "")) !== "";
?>
<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/registration.svg" alt="">
        <h1 class="page-title"><?= h(lang("acc.title")) ?></h1>
        <p class="page-subtitle"><?= h($user["id"]) ?> · <?= h($user["mail"]) ?></p>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <!-- Balances -->
    <div class="balance-bar">
        <?php foreach ($balances as $b): ?>
            <div class="bal">
                <img src="assets/icons/<?= h($b["icon"]) ?>" alt="">
                <div><div class="v"><?= h($b["value"] ?? "—") ?></div>
                <div class="l"><?= h($b["label"]) ?></div></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="reg-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:start">
        <section class="panel panel-corner">
            <h2 class="panel-title left">My characters</h2>
            <?php if (!$chars): ?>
                <p class="text-mute">No characters yet. Create one in-game.</p>
            <?php else: ?>
                <div class="table-wrap">
                <table class="rank">
                    <thead><tr><th>Name</th><th>Class</th><th>Level</th><th>Resets</th><?php if ($show_master): ?><th>ML</th><?php endif; ?></tr></thead>
                    <tbody>
                    <?php foreach ($chars as $c): ?>
                        <tr><td><?= h($c["Name"]) ?></td>
                            <td><span class="cls-tag cls-<?= h($c["class_h"][1]) ?>"><?= h($c["class_h"][0]) ?></span></td>
                            <td><?= (int)$c["cLevel"] ?></td>
                            <td><?= (int)$c["Resets"] ?></td>
                            <?php if ($show_master): ?><td><?= (int)$c["MasterLevel"] ?></td><?php endif; ?></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel panel-corner">
            <h2 class="panel-title left"><?= h(lang("acc.password")) ?></h2>
            <form action="index.php?m=change_password" method="post" autocomplete="off">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="field full">
                        <label for="current">Current password</label>
                        <input id="current" name="current" type="password" required minlength="4" maxlength="10">
                    </div>
                    <div class="field">
                        <label for="new">New password</label>
                        <input id="new" name="new" type="password" required minlength="4" maxlength="10">
                    </div>
                    <div class="field">
                        <label for="new2">Confirm new password</label>
                        <input id="new2" name="new2" type="password" required minlength="4" maxlength="10">
                    </div>
                </div>
                <div style="text-align:right;margin-top:14px"><button type="submit" class="btn">Update</button></div>
            </form>
        </section>
    </div>
</main>
