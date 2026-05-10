<?php if (!defined("insite")) die("no access"); ?>
<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/registration.svg" alt="">
        <h1 class="page-title"><?= h(lang("login.title")) ?></h1>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <section class="panel panel-corner" style="max-width:520px;margin:0 auto">
        <h2 class="panel-title left">Log in</h2>
        <form action="index.php?m=login<?= !empty($_GET["next"]) ? "&amp;next=".urlencode($_GET["next"]) : "" ?>"
              method="post" autocomplete="off">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field full">
                    <label for="login">Account name</label>
                    <input id="login" name="login" type="text" required minlength="4" maxlength="10" pattern="[A-Za-z0-9]+">
                </div>
                <div class="field full">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required minlength="4" maxlength="10">
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:18px">
                <span class="text-mute">No account? <a href="index.php?m=registration">Register</a>.</span>
                <button type="submit" class="btn"><?= h(lang("nav.login")) ?></button>
            </div>
        </form>
    </section>
</main>
