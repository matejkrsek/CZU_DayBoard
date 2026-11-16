<?php
/**
 * Změna hesla (povinná při prvním přihlášení s dočasným heslem od správce).
 *
 * @var array<string,mixed> $user
 * @var array<string,string> $errors
 */

use DayBoard\Models\Users;

$forced = (int) $user['must_change_password'] === 1;
?>
<div class="page page--narrow">
    <header class="page-head">
        <h1>Změna hesla</h1>
        <p class="page-head__lead">
            <?= $forced
                ? 'Správce vám nastavil dočasné heslo. Než budete pokračovat, zvolte si prosím vlastní.'
                : 'Nové heslo začne platit hned; na ostatních zařízeních budete odhlášeni.' ?>
        </p>
    </header>

    <form method="post" action="<?= e(url('/zmena-hesla')) ?>" class="card form" novalidate>
        <?= csrf_field() ?>
        <?php
        $fields = [
            'current_password' => [$forced ? 'Dočasné heslo' : 'Současné heslo', 'current-password', ''],
            'new_password' => ['Nové heslo', 'new-password', 'Alespoň ' . Users::MIN_PASSWORD_LENGTH . ' znaků.'],
            'new_password_repeat' => ['Nové heslo znovu', 'new-password', ''],
        ];
        foreach ($fields as $name => [$label, $autocomplete, $hint]):
            $describedBy = trim(($hint !== '' ? $name . '-hint ' : '') . (isset($errors[$name]) ? $name . '-error' : ''));
            ?>
            <div class="field">
                <label for="<?= e($name) ?>"><?= e($label) ?></label>
                <input type="password" id="<?= e($name) ?>" name="<?= e($name) ?>" autocomplete="<?= e($autocomplete) ?>" required
                       <?= $describedBy !== '' ? 'aria-describedby="' . e($describedBy) . '"' : '' ?>
                       <?= isset($errors[$name]) ? 'aria-invalid="true"' : '' ?>>
                <?php if ($hint !== ''): ?><p class="field__hint" id="<?= e($name) ?>-hint"><?= e($hint) ?></p><?php endif; ?>
                <?php if (isset($errors[$name])): ?><p class="field__error" id="<?= e($name) ?>-error"><?= e($errors[$name]) ?></p><?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="form__actions">
            <?php if (!$forced): ?><a class="btn" href="<?= e(url('/nastaveni')) ?>">Zrušit</a><?php endif; ?>
            <button type="submit" class="btn btn--primary">Změnit heslo</button>
        </div>
    </form>
</div>
