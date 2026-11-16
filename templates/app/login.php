<?php
/**
 * Přihlašovací obrazovka (wireframe 1).
 *
 * @var string $login       naposledy zadané přihlašovací jméno
 * @var string|null $error
 * @var list<array{0:string,1:string,2:string}> $demoAccounts  ukázkové účty (jen v ukázkovém režimu)
 */

use DayBoard\Database\Seeder;
use DayBoard\Models\Users;
?>
<div class="auth-layout">
    <section class="auth-card" aria-labelledby="login-title">
        <div class="auth-card__logo"><?= partial('partials/logo', ['size' => 56]) ?></div>
        <h1 id="login-title" class="auth-card__title">Přihlášení</h1>
        <p class="auth-card__lead">Přihlaste se do rodinné nástěnky.</p>

        <?php foreach (flashes() as $flash): ?>
            <p class="form-alert form-alert--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
        <?php endforeach; ?>
        <?php if ($error !== null): ?>
            <p class="form-alert form-alert--error" role="alert" id="login-error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/prihlaseni')) ?>" class="form">
            <?= csrf_field() ?>
            <div class="field">
                <label for="login">Uživatelské jméno nebo e-mail</label>
                <input type="text" id="login" name="login" value="<?= e($login) ?>" autocomplete="username"
                       autocapitalize="none" spellcheck="false" placeholder="jmeno@domacnost.cz" required
                       <?= $error !== null ? 'aria-describedby="login-error"' : '' ?> <?= $login === '' ? 'autofocus' : '' ?>>
            </div>
            <div class="field">
                <label for="password">Heslo</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required
                       <?= $login !== '' ? 'autofocus' : '' ?>>
            </div>
            <label class="check">
                <input type="checkbox" name="remember" value="1">
                <span>Zůstat přihlášen</span>
            </label>
            <button type="submit" class="btn btn--primary btn--block">Přihlásit se</button>
        </form>

        <p class="auth-card__hint">Zapomenuté heslo? Obnovení provede správce domácnosti.</p>
    </section>

    <aside class="auth-notes" aria-label="Informace k přihlášení">
        <p class="note">Účty zakládá správce domácnosti – registrace není veřejná.</p>

        <?php if (DayBoard\Core\Config::isEphemeral()): ?>
            <p class="note">Toto je online ukázka. Po čtvrthodině bez návštěv se server uspí a všechna data se vrátí do výchozího stavu. Probuzení trvá zhruba minutu.</p>
        <?php endif; ?>

        <?php if ($demoAccounts !== []): ?>
            <div class="note note--demo">
                <h2 class="note__title">Ukázkový režim – přihlásit jako:</h2>
                <?php
                // Tlačítka jen odešlou výchozí ukázkové heslo. Po jeho změně přestanou fungovat,
                // nejde tedy o obcházení přihlášení.
                $shown = [];
                foreach ($demoAccounts as [$demoLogin, $demoName, $demoRole]):
                    if (isset($shown[$demoRole])) {
                        continue;
                    }
                    $shown[$demoRole] = true;
                    ?>
                    <form method="post" action="<?= e(url('/prihlaseni')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="login" value="<?= e($demoLogin) ?>">
                        <input type="hidden" name="password" value="<?= e(Seeder::DEMO_PASSWORD) ?>">
                        <button type="submit" class="btn btn--block">
                            <?= e(Users::ROLES[$demoRole]) ?> <span class="muted">(<?= e($demoName) ?>)</span>
                        </button>
                    </form>
                <?php endforeach; ?>
                <p class="note__small">Heslo ukázkových účtů je „<?= e(Seeder::DEMO_PASSWORD) ?>“. V ostrém provozu hesla změňte a v souboru <code>.env</code> nastavte <code>DEMO_MODE=false</code>.</p>
            </div>
        <?php endif; ?>
    </aside>
</div>
