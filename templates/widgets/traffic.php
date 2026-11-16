<?php
/**
 * Widget Doprava autem.
 *
 * @var array{state:string,demo:bool,origin:string,routes:list<array<string,mixed>>} $traffic
 */

use DayBoard\Support\Czech;

$levels = ['ok' => 'plynulý provoz', 'slow' => 'zhuštěný provoz', 'jam' => 'kolony'];
?>
<?php if ($traffic['state'] === 'empty'): ?>
    <div class="widget-state">
        <?= icon('car', 22) ?>
        <p>Zatím nemáte žádné cíle cest. Přidejte si je v <a href="<?= e(url('/nastaveni')) ?>">Osobním nastavení</a>.</p>
    </div>
<?php elseif ($traffic['state'] === 'unconfigured'): ?>
    <div class="widget-state">
        <?= icon('car', 22) ?>
        <p>Doprava není na tomto serveru nastavena (chybí TOMTOM_API_KEY v souboru .env).</p>
    </div>
<?php else: ?>
    <?php if ($traffic['demo']): ?>
        <p class="demo-note demo-note--short" title="Skutečné časy vyžadují klíč TomTom API v souboru .env.">Ukázková data</p>
    <?php endif; ?>
    <?php if ($traffic['origin'] !== 'Domov'): ?>
        <p class="routes__origin">Z místa: <?= e($traffic['origin']) ?></p>
    <?php endif; ?>
    <ul class="routes">
        <?php foreach ($traffic['routes'] as $route): ?>
            <?php if (isset($route['error'])): ?>
                <li class="route">
                    <div class="route__row">
                        <span class="route__label"><?= e($route['label']) ?></span>
                        <span class="route__error"><?= e($route['error']) ?></span>
                    </div>
                </li>
            <?php else: ?>
                <li class="route route--<?= e($route['level']) ?>">
                    <div class="route__row">
                        <span class="route__label"><?= e($route['label']) ?></span>
                        <strong class="route__time"><?= (int) $route['minutes'] ?>&nbsp;min</strong>
                    </div>
                    <div class="route__bar" aria-hidden="true"><span style="width: <?= max(6, (int) $route['share']) ?>%"></span></div>
                    <p class="route__meta">
                        <?= e($levels[$route['level']]) ?><?= $route['delay'] > 0 ? ' (+' . (int) $route['delay'] . '&nbsp;min)' : '' ?>
                        · <?= e(Czech::number((float) $route['km'], 1)) ?>&nbsp;km
                    </p>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
