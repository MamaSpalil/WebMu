<?php if (!defined("insite")) die("no access"); ?>
<style>
    .reg-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 28px; align-items: start; }
    @media (max-width: 900px) { .reg-grid { grid-template-columns: 1fr; } }
    .perks { list-style: none; padding: 0; display: flex; flex-direction: column; gap: 14px; }
    .perks li { display: flex; gap: 14px; align-items: flex-start; padding: 12px;
        background: rgba(14,9,3,0.7); border: 1px solid var(--border-gold); border-radius: 3px; }
    .perks li img { width: 38px; height: 38px; flex-shrink: 0; filter: drop-shadow(0 0 8px rgba(230,195,74,.4)); }
    .perks li strong { color: var(--gold); display: block; font-family: 'Cinzel Decorative',serif; letter-spacing: 1.5px; margin-bottom: 2px; }
    .perks li span { color: #c8b890; font-size: 13.5px; line-height: 1.5; }
    .submit-row { display: flex; justify-content: space-between; align-items: center; margin-top: 22px; gap: 18px; flex-wrap: wrap; }
</style>

<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/registration.svg" alt="">
        <h1 class="page-title"><?= h(lang("reg.title")) ?></h1>
        <p class="page-subtitle">Sign the scroll — claim your destiny</p>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <div class="reg-grid">
        <section class="panel panel-corner">
            <h2 class="panel-title left">Account Details</h2>

            <form action="index.php?m=register" method="post" autocomplete="off" novalidate>
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="field full">
                        <label for="login">Account name</label>
                        <input id="login" name="login" type="text" required minlength="4" maxlength="10"
                               pattern="[A-Za-z0-9]+" placeholder="warrior42">
                        <span class="help">4–10 chars, letters &amp; digits only.</span>
                    </div>
                    <div class="field">
                        <label for="email">E-mail</label>
                        <input id="email" name="email" type="email" required placeholder="hero@continent.mu">
                    </div>
                    <div class="field">
                        <label for="email2">Confirm e-mail</label>
                        <input id="email2" name="email2" type="email" required placeholder="hero@continent.mu">
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" required minlength="4" maxlength="10" placeholder="••••••••">
                        <span class="help">4–10 chars (game client limit).</span>
                    </div>
                    <div class="field">
                        <label for="password2">Confirm password</label>
                        <input id="password2" name="password2" type="password" required minlength="4" maxlength="10" placeholder="••••••••">
                    </div>
                    <div class="field">
                        <label for="pin">Vault PIN (4 digits)</label>
                        <input id="pin" name="pin" type="password" required minlength="4" maxlength="4"
                               inputmode="numeric" pattern="[0-9]{4}" placeholder="••••">
                    </div>
                    <div class="field">
                        <label for="country">Country</label>
                        <select id="country" name="country" required>
                            <option value="">— select —</option>
                            <option>Ukraine</option><option>Russia</option><option>Poland</option>
                            <option>Germany</option><option>USA</option><option>Brazil</option>
                            <option>Philippines</option><option>Other</option>
                        </select>
                    </div>
                    <div class="field full">
                        <label for="referrer">Referrer (optional)</label>
                        <input id="referrer" name="referrer" type="text" maxlength="10" placeholder="friend's account name">
                    </div>
                    <div class="field full">
                        <label class="checkbox">
                            <input type="checkbox" name="rules" required>
                            I have read and accept the Server Rules &amp; Terms of Service.
                        </label>
                    </div>
                </div>

                <div class="submit-row">
                    <span class="text-mute">Already have an account? <a href="index.php?m=login">Log in</a>.</span>
                    <button type="submit" class="btn">
                        <img src="assets/icons/sword.svg" alt="" style="width:18px;height:18px"> Forge Account
                    </button>
                </div>
            </form>
        </section>

        <aside>
            <section class="panel panel-corner">
                <h2 class="panel-title left">Starter Pack</h2>
                <ul class="perks">
                    <li><img src="assets/icons/sword.svg" alt=""><div><strong>Lvl 1 Boost</strong>
                        <span>Free +Luck weapon and 7-day buff scroll.</span></div></li>
                    <li><img src="assets/icons/coins.svg" alt=""><div><strong>Starting Bonus</strong>
                        <span>+100 Credits and +100 WCoin to begin your journey.</span></div></li>
                    <li><img src="assets/icons/gem.svg" alt=""><div><strong>Newbie Buff</strong>
                        <span>+30% EXP for the first 3 days.</span></div></li>
                </ul>
            </section>

            <section class="panel panel-corner">
                <h2 class="panel-title left">Server Rules</h2>
                <ul style="padding-left:18px; color:#ddc89a; font-size:13.5px; line-height:1.7;">
                    <li>One account per person — multi-boxing limited to 3 windows.</li>
                    <li>No bots, hacks, exploits, or 3rd-party clients.</li>
                    <li>Do not share credentials — staff will <em>never</em> ask.</li>
                    <li>Real-money trading outside the official Donate Shop is prohibited.</li>
                </ul>
            </section>
        </aside>
    </div>
</main>
