<?php
/**
 * Layout přihlašovací obrazovky (bez navigace aplikace).
 *
 * @var string $title
 * @var string $content
 */
?>
<!DOCTYPE html>
<html lang="cs" data-theme="system">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | DayBoard</title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="auth">
<a class="skip-link" href="#obsah">Přeskočit na obsah</a>

<header class="auth-header">
    <a class="brand" href="<?= e(url('/')) ?>" aria-label="DayBoard – úvodní stránka">
        <?= partial('partials/logo') ?>
        <span class="brand__name">DayBoard</span>
    </a>
    <a class="auth-header__back" href="<?= e(url('/')) ?>"><?= icon('arrow-left', 16) ?> Veřejné stránky</a>
</header>

<main id="obsah" class="auth-main">
    <?= $content ?>
</main>
</body>
</html>
