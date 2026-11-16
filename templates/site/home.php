<?php
/**
 * Veřejná úvodní stránka (wireframe 6).
 * Hlavní klíčové slovo „rodinná nástěnka“ je v title, H1 i prvním odstavci.
 * Z úvodní stránky vede odkaz s popisným textem na každou podstránku.
 *
 * @var array<string,string> $page
 */

use DayBoard\Site\Pages;
?>
<section class="hero">
    <div class="container hero__inner">
        <div class="hero__text">
            <h1><?= e($page['h1']) ?></h1>
            <p class="lead">DayBoard je rodinná nástěnka, která celé domácnosti hned ráno ukáže to podstatné: počasí, dnešní program, dopravu i sdílený nákupní seznam na jedné obrazovce. Provozujete ji na vlastním domácím serveru.</p>
            <p class="button-row">
                <a class="btn btn--primary btn--large" href="<?= e(url('/prihlaseni')) ?>">Přihlásit se do aplikace</a>
                <a class="btn btn--large" href="<?= e(url('/jak-to-funguje')) ?>">Jak to funguje</a>
            </p>
        </div>
        <div class="hero__media">
            <?= image_tag(
                'nastenka.webp',
                'Nástěnka DayBoard s widgety Počasí, Dnešní program, Doprava autem, Odjezdy MHD, Nákupní seznam a Poloha členů',
                ['loading' => 'eager', 'fetchpriority' => 'high', 'class' => 'hero__image']
            ) ?>
        </div>
    </div>
</section>

<section class="section" aria-labelledby="co-zobrazuje">
    <div class="container">
        <h2 id="co-zobrazuje">Co DayBoard zobrazuje</h2>
        <p class="section__lead">Nástěnka se skládá z widgetů. Každý člen rodiny si z nich poskládá vlastní přehled dne.</p>
        <ul class="cards cards--4">
            <li class="feature-card">
                <span class="feature-card__icon"><?= icon('calendar', 24) ?></span>
                <h3>Dnešní program</h3>
                <p>Události z Google Kalendáře každého člena rodiny – přehledně pod sebou a bez přepínání aplikací.</p>
                <a href="<?= e(url('/funkce')) ?>#kalendar">Sdílený rodinný kalendář <?= icon('arrow-right', 16) ?></a>
            </li>
            <li class="feature-card">
                <span class="feature-card__icon"><?= icon('car', 24) ?></span>
                <h3>Doprava</h3>
                <p>Aktuální doba jízdy autem do školy či práce a nejbližší odjezdy MHD z oblíbených zastávek.</p>
                <a href="<?= e(url('/funkce')) ?>#doprava">Doprava autem a odjezdy MHD <?= icon('arrow-right', 16) ?></a>
            </li>
            <li class="feature-card">
                <span class="feature-card__icon"><?= icon('cart', 24) ?></span>
                <h3>Nákupní seznam</h3>
                <p>Jeden seznam pro celou domácnost. Kdo si vzpomene, připíše – a všichni to hned vidí.</p>
                <a href="<?= e(url('/funkce')) ?>#nakupni-seznam">Sdílený nákupní seznam <?= icon('arrow-right', 16) ?></a>
            </li>
            <li class="feature-card">
                <span class="feature-card__icon"><?= icon('shield', 24) ?></span>
                <h3>Role a soukromí</h3>
                <p>Správce, člen a host nebo dítě – každý vidí jen to své. Data zůstávají na vašem serveru.</p>
                <a href="<?= e(url('/soukromi')) ?>">Soukromí a bezpečnost dat <?= icon('arrow-right', 16) ?></a>
            </li>
        </ul>
        <p class="section__more"><a href="<?= e(url('/funkce')) ?>">Přehled všech widgetů rodinné nástěnky</a></p>
    </div>
</section>

<section class="section section--tinted" aria-labelledby="jak-to-funguje">
    <div class="container">
        <h2 id="jak-to-funguje">Jak to funguje</h2>
        <ol class="steps">
            <li class="step">
                <h3>Spusťte na domácím serveru</h3>
                <p>DayBoard nainstalujete na domácí server, NAS nebo Raspberry Pi. Stačí PHP, databázi si vytvoří sám.</p>
            </li>
            <li class="step">
                <h3>Přidejte členy domácnosti</h3>
                <p>Správce založí účty, určí role a vybere, které widgety smí kdo používat.</p>
            </li>
            <li class="step">
                <h3>Poskládejte si nástěnku</h3>
                <p>Každý si widgety vybere, přetáhne na místo a nastaví jim velikost – jako na domovské obrazovce telefonu.</p>
            </li>
        </ol>
        <p class="section__more"><a href="<?= e(url('/jak-to-funguje')) ?>">Podrobný návod, jak DayBoard nainstalovat</a></p>
    </div>
</section>

<section class="section" aria-labelledby="navody">
    <div class="container">
        <h2 id="navody">Návody</h2>
        <p class="section__lead">Praktické články k tématům, která s rodinnou nástěnkou souvisejí.</p>
        <ul class="cards cards--3">
            <?php foreach (Pages::guides() as $guidePath => $guide): ?>
                <li><?= partial('site/_guide-card', ['guidePath' => $guidePath, 'guide' => $guide, 'heading' => 'h3']) ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="section__more"><a href="<?= e(url('/navody')) ?>">Všechny návody</a></p>
    </div>
</section>

<section class="section section--tinted cta" aria-labelledby="cta-title">
    <div class="container cta__inner">
        <div>
            <h2 id="cta-title">Máte DayBoard doma?</h2>
            <p>Přihlaste se ke své nástěnce. Pokud si nevíte rady, projděte si <a href="<?= e(url('/caste-dotazy')) ?>">časté dotazy k rodinné nástěnce</a>.</p>
        </div>
        <a class="btn btn--primary btn--large" href="<?= e(url('/prihlaseni')) ?>">Přihlásit se do aplikace</a>
    </div>
</section>
