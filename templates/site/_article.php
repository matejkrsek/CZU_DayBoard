<?php
/**
 * Společný rámec článku v sekci Návody: drobečková navigace, nadpis, úvodní
 * obrázek, text článku a odkazy na další návody.
 *
 * @var array<string,string> $page
 * @var string $path
 * @var string $lead   první odstavec (obsahuje hlavní klíčové slovo článku)
 * @var string $body   HTML těla článku
 */

use DayBoard\Site\Pages;
use DayBoard\Support\Czech;

$published = new DateTimeImmutable($page['published']);
?>
<article>
    <header class="page-hero page-hero--article">
        <div class="container container--narrow">
            <nav class="breadcrumbs" aria-label="Drobečková navigace">
                <ol>
                    <li><a href="<?= e(url('/')) ?>">Domů</a></li>
                    <li><a href="<?= e(url('/navody')) ?>">Návody</a></li>
                    <li aria-current="page"><?= e($page['label']) ?></li>
                </ol>
            </nav>
            <h1><?= e($page['h1']) ?></h1>
            <p class="lead"><?= $lead ?></p>
            <p class="article-meta">Publikováno <time datetime="<?= e($page['published']) ?>"><?= e(Czech::dayMonth($published) . ' ' . $published->format('Y')) ?></time></p>
        </div>
    </header>

    <div class="section section--flush">
        <div class="container container--narrow prose">
            <?= image_tag($page['image'], $page['imageAlt'], ['width' => 800, 'height' => 400, 'class' => 'prose__cover', 'loading' => 'eager']) ?>
            <?= $body ?>
        </div>
    </div>
</article>

<section class="section section--tinted" aria-labelledby="dalsi-navody">
    <div class="container">
        <h2 id="dalsi-navody">Další návody</h2>
        <ul class="cards cards--3">
            <?php foreach (Pages::guides() as $guidePath => $guide): ?>
                <?php if ($guidePath !== $path): ?>
                    <li><?= partial('site/_guide-card', ['guidePath' => $guidePath, 'guide' => $guide, 'heading' => 'h3']) ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
