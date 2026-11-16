<?php
/**
 * Karta návodu (úvodní stránka, přehled návodů, „další návody“ pod článkem).
 *
 * @var string $guidePath
 * @var array<string,string> $guide
 * @var string $heading  úroveň nadpisu podle místa použití: h2 | h3
 */
$heading = in_array($heading ?? 'h3', ['h2', 'h3'], true) ? $heading : 'h3';
?>
<article class="guide-card">
    <?= image_tag($guide['image'], $guide['imageAlt'], ['width' => 800, 'height' => 400, 'class' => 'guide-card__image']) ?>
    <div class="guide-card__body">
        <<?= $heading ?> class="guide-card__title"><a href="<?= e(url($guidePath)) ?>"><?= e($guide['label']) ?></a></<?= $heading ?>>
        <p><?= e($guide['teaser']) ?></p>
        <span class="guide-card__more" aria-hidden="true">Přečíst návod <?= icon('arrow-right', 16) ?></span>
    </div>
</article>
