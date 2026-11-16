<?php
/**
 * Widget Sportovní novinky (RSS).
 *
 * @var array{source:string,items:list<array<string,mixed>>} $news
 */

use DayBoard\Services\NewsService;
?>
<?php if ($news['items'] === []): ?>
    <div class="widget-state">
        <?= icon('news', 22) ?>
        <p>RSS zdroj teď neobsahuje žádné články.</p>
    </div>
<?php else: ?>
    <ul class="news">
        <?php foreach ($news['items'] as $item): ?>
            <li>
                <a class="news__item" href="<?= e($item['link']) ?>" target="_blank" rel="noopener noreferrer">
                    <?php if ($item['image'] !== null): ?>
                        <?php // Náhled je jen ozdoba vedle titulku, proto prázdný alt. ?>
                        <img class="news__thumb" src="<?= e($item['image']) ?>" alt="" width="72" height="48" loading="lazy" decoding="async" referrerpolicy="no-referrer">
                    <?php else: ?>
                        <span class="news__thumb news__thumb--empty" aria-hidden="true"><?= icon('news', 18) ?></span>
                    <?php endif; ?>
                    <span class="news__text">
                        <span class="news__title"><?= e($item['title']) ?></span>
                        <span class="news__meta">
                            <?= e(implode(' · ', array_filter([$news['source'], NewsService::ago($item['time'])]))) ?>
                            <span class="visually-hidden">(otevře se v novém okně)</span>
                        </span>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
