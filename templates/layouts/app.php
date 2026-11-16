<?php
/**
 * Layout aplikace po přihlášení: hlavní navigace, uživatel, odhlášení.
 *
 * @var array<string,mixed> $user
 * @var string $title
 * @var string $active   aktivní položka navigace: dashboard | settings | household | ''
 * @var string $content
 * @var bool $canEdit    (jen nástěnka) uživatel smí upravovat rozložení
 */

use DayBoard\Core\Auth;
use DayBoard\Models\Users;

$navigation = ['dashboard' => ['/nastenka', 'Nástěnka'], 'settings' => ['/nastaveni', 'Osobní nastavení']];
// Položku Správa domácnosti vidí pouze správce.
if (Auth::can('household.manage', $user)) {
    $navigation['household'] = ['/domacnost', 'Správa domácnosti'];
}
// Dokud si člen nezmění dočasné heslo, jinam se nedostane – navigaci proto skryjeme.
$showNavigation = (int) $user['must_change_password'] !== 1;

$scripts = ['app.js'];
if ($active === 'dashboard') {
    $scripts[] = 'dashboard.js';
    if (!empty($canEdit)) {
        $scripts[] = 'dashboard-edit.js';
    }
} elseif ($active === 'settings') {
    $scripts[] = 'settings.js';
} elseif ($active === 'household') {
    $scripts[] = 'household.js';
}
?>
<!DOCTYPE html>
<html lang="cs" data-theme="<?= e($user['theme']) ?>" data-density="<?= e($user['density']) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | DayBoard</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="base-path" content="<?= e(base_path()) ?>">
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <?php foreach ($scripts as $script): ?>
        <script src="<?= e(asset('js/' . $script)) ?>" defer></script>
    <?php endforeach; ?>
</head>
<body class="app">
<a class="skip-link" href="#obsah">Přeskočit na obsah</a>

<header class="app-header">
    <div class="app-header__inner">
        <a class="brand" href="<?= e(url('/nastenka')) ?>" aria-label="DayBoard – nástěnka">
            <?= partial('partials/logo') ?>
            <span class="brand__name">DayBoard</span>
        </a>

        <?php if ($showNavigation): ?>
            <nav class="app-nav" aria-label="Hlavní navigace">
                <ul>
                    <?php foreach ($navigation as $key => [$href, $label]): ?>
                        <li><a href="<?= e(url($href)) ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <div class="app-user">
            <span class="avatar" aria-hidden="true"><?= e(Users::initial($user)) ?></span>
            <span class="app-user__text">
                <span class="app-user__name"><?= e($user['display_name']) ?></span>
                <span class="app-user__role"><?= e(Users::ROLES[$user['role']]) ?></span>
            </span>
        </div>
        <form method="post" action="<?= e(url('/odhlaseni')) ?>" class="app-header__logout">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--small"><?= icon('logout', 16) ?><span>Odhlásit se</span></button>
        </form>
    </div>
</header>

<main id="obsah" class="app-main">
    <?= partial('partials/flashes') ?>
    <?= $content ?>
</main>

<!-- Společné potvrzovací okno (odebrání widgetu, člena apod.) – ovládá ho app.js -->
<dialog class="dialog dialog--confirm" id="confirm-dialog" aria-labelledby="confirm-dialog-title">
    <form method="dialog">
        <h2 id="confirm-dialog-title" class="dialog__title">Potvrzení</h2>
        <p class="dialog__text" data-confirm-text></p>
        <div class="dialog__actions">
            <button type="submit" value="cancel" class="btn">Zrušit</button>
            <button type="submit" value="ok" class="btn btn--danger" data-confirm-ok>Potvrdit</button>
        </div>
    </form>
</dialog>
</body>
</html>
