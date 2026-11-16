<?php
/**
 * Přehled návodů. Jednotlivé články cílí na příbuzná témata s vyšší hledaností
 * (viz tabulka 5 úvodní studie).
 *
 * @var array<string,string> $page
 */

use DayBoard\Site\Pages;
?>
<section class="page-hero">
    <div class="container">
        <h1><?= e($page['h1']) ?></h1>
        <p class="lead">Praktické články k tématům, která s rodinnou nástěnkou souvisejí: chytrá domácnost, otevřená data o pražské dopravě a klidnější rána s dětmi.</p>
    </div>
</section>

<section class="section section--flush">
    <div class="container">
        <ul class="cards cards--3">
            <?php foreach (Pages::guides() as $guidePath => $guide): ?>
                <li><?= partial('site/_guide-card', ['guidePath' => $guidePath, 'guide' => $guide, 'heading' => 'h2']) ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="section__more">Hledáte návod k samotné instalaci? Přečtěte si, <a href="<?= e(url('/jak-to-funguje')) ?>">jak DayBoard spustit na domácím serveru</a>.</p>
    </div>
</section>
