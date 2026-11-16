<?php
/**
 * Widget Svátek a denní informace.
 *
 * @var array<string,mixed> $day  výstup NamedayService::today()
 */
?>
<div class="day">
    <p class="day__weekday"><?= e($day['weekday']) ?></p>
    <p class="day__date"><?= e($day['date']) ?></p>
    <?php if ($day['holiday']): ?>
        <p class="day__holiday"><?= e($day['holiday']) ?></p>
    <?php endif; ?>
    <?php if ($day['name']): ?>
        <p class="day__name">Svátek má: <strong><?= e($day['name']) ?></strong></p>
    <?php else: ?>
        <p class="day__name muted">Jmeniny se teď nepodařilo načíst.</p>
    <?php endif; ?>
    <ul class="day__meta">
        <?php if ($day['tomorrow']): ?><li>Zítra: <?= e($day['tomorrow']) ?></li><?php endif; ?>
        <li>Týden <?= (int) $day['week'] ?> · den <?= (int) $day['dayOfYear'] ?></li>
        <?php if ($day['sunrise'] && $day['sunset']): ?>
            <li class="day__sun"><?= icon('sunrise', 15) ?> Slunce <?= e(ltrim($day['sunrise'], '0')) ?>–<?= e($day['sunset']) ?></li>
        <?php endif; ?>
    </ul>
</div>
