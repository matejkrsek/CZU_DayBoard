<?php
/**
 * Layout veřejné prezentační části (společná hlavička a patička).
 * Stránky jsou čisté HTML a CSS bez JavaScriptu – kvůli rychlosti (Core Web Vitals).
 *
 * @var array<string,string> $page  metadata stránky (viz DayBoard\Site\Pages)
 * @var string|null $path           cesta stránky, null u chybových stránek
 * @var string $content
 */

use DayBoard\Site\Faq;
use DayBoard\Site\Pages;

$canonical = $path !== null ? absolute_url($path) : null;
$robots = $page['robots'] ?? 'index, follow';
$isArticle = ($page['type'] ?? '') === 'article';
// Náhled pro sdílení na sociálních sítích (ty neumějí SVG, proto vždy snímek nástěnky).
$ogImage = absolute_url('/assets/img/nastenka.webp');

// Strukturovaná data (JSON-LD) pomáhají vyhledávačům pochopit obsah stránky.
$jsonLd = [];
if ($path === '/') {
    $jsonLd[] = [
        '@context' => 'https://schema.org', '@type' => 'SoftwareApplication',
        'name' => 'DayBoard', 'applicationCategory' => 'LifestyleApplication', 'operatingSystem' => 'Web',
        'description' => $page['description'], 'url' => $canonical, 'inLanguage' => 'cs',
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'CZK'],
    ];
} elseif ($path === '/caste-dotazy') {
    $jsonLd[] = [
        '@context' => 'https://schema.org', '@type' => 'FAQPage',
        'mainEntity' => array_map(static fn (array $item): array => [
            '@type' => 'Question', 'name' => $item['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags($item['answer']))],
        ], Faq::items()),
    ];
} elseif ($isArticle) {
    $jsonLd[] = [
        '@context' => 'https://schema.org', '@type' => 'Article',
        'headline' => $page['h1'], 'description' => $page['description'], 'inLanguage' => 'cs',
        'datePublished' => $page['published'], 'image' => $ogImage, 'mainEntityOfPage' => $canonical,
        'author' => ['@type' => 'Organization', 'name' => 'DayBoard'],
    ];
    $jsonLd[] = [
        '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Domů', 'item' => absolute_url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Návody', 'item' => absolute_url('/navody')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $page['h1'], 'item' => $canonical],
        ],
    ];
}
?>
<!DOCTYPE html>
<html lang="cs" data-theme="system">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page['title']) ?></title>
    <?php if (($page['description'] ?? '') !== ''): ?>
        <meta name="description" content="<?= e($page['description']) ?>">
    <?php endif; ?>
    <meta name="robots" content="<?= e($robots) ?>">
    <?php if ($canonical !== null): ?>
        <link rel="canonical" href="<?= e($canonical) ?>">
        <meta property="og:url" content="<?= e($canonical) ?>">
    <?php endif; ?>
    <meta property="og:type" content="<?= $isArticle ? 'article' : 'website' ?>">
    <meta property="og:site_name" content="DayBoard">
    <meta property="og:locale" content="cs_CZ">
    <meta property="og:title" content="<?= e($page['title']) ?>">
    <meta property="og:description" content="<?= e($page['description'] ?? '') ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta name="theme-color" content="#f6f3ec" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#12161d" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
    <?php foreach ($jsonLd as $block): ?>
        <script type="application/ld+json"><?= json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
    <?php endforeach; ?>
</head>
<body class="site">
<a class="skip-link" href="#obsah">Přeskočit na obsah</a>

<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="DayBoard – úvodní stránka">
            <?= partial('partials/logo') ?>
            <span class="brand__name">DayBoard</span>
        </a>
        <nav class="site-nav" aria-label="Hlavní navigace">
            <ul>
                <?php foreach (Pages::navigation() as $navPath => $navPage): ?>
                    <li>
                        <a href="<?= e(url($navPath)) ?>"<?= $navPage['nav'] === ($page['nav'] ?? '') ? ' aria-current="' . ($navPath === $path ? 'page' : 'true') . '"' : '' ?>>
                            <?= e($navPage['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <a class="btn btn--primary site-header__cta" href="<?= e(url('/prihlaseni')) ?>"><span>Přihlásit se<span class="site-header__cta-more"> do aplikace</span></span></a>
    </div>
</header>

<main id="obsah">
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container site-footer__inner">
        <div>
            <p class="site-footer__brand">DayBoard</p>
            <p class="site-footer__claim">Rodinná nástěnka pro začátek dne</p>
        </div>
        <nav aria-label="Odkazy v patičce">
            <ul class="site-footer__links">
                <li><a href="<?= e(url('/funkce')) ?>">Funkce a widgety</a></li>
                <li><a href="<?= e(url('/jak-to-funguje')) ?>">Jak to funguje</a></li>
                <li><a href="<?= e(url('/soukromi')) ?>">Soukromí a bezpečnost</a></li>
                <li><a href="<?= e(url('/caste-dotazy')) ?>">Časté dotazy</a></li>
                <li><a href="<?= e(url('/navody')) ?>">Návody</a></li>
                <li><a href="<?= e(url('/prihlaseni')) ?>">Přihlásit se</a></li>
            </ul>
        </nav>
    </div>
    <div class="container">
        <p class="site-footer__note">Studentský projekt předmětu Internetové technologie – client side, PEF ČZU v Praze.</p>
    </div>
</footer>
</body>
</html>
