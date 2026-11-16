<?php
/**
 * Chybová stránka (404, 403, 419, 500).
 *
 * @var array<string,string> $page
 * @var int $status
 * @var string $message
 */
?>
<section class="section section--narrow error-page">
    <div class="container">
        <p class="error-page__code">Chyba <?= (int) $status ?></p>
        <h1><?= e($page['h1']) ?></h1>
        <?php if ($message !== ''): ?>
            <p class="lead"><?= e($message) ?></p>
        <?php endif; ?>
        <p class="button-row">
            <a class="btn btn--primary" href="<?= e(url('/')) ?>">Zpět na úvodní stránku</a>
            <a class="btn" href="<?= e(url('/nastenka')) ?>">Otevřít nástěnku</a>
        </p>
    </div>
</section>
