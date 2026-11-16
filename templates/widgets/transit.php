<?php
/**
 * Widget Odjezdy MHD.
 *
 * @var array{state:string,demo:bool,departures:list<array<string,mixed>>} $transit
 */
$vehicles = ['metro' => 'metro', 'tram' => 'tramvaj', 'bus' => 'autobus', 'train' => 'vlak', 'ferry' => 'přívoz'];
?>
<?php if ($transit['state'] === 'empty'): ?>
    <div class="widget-state">
        <?= icon('tram', 22) ?>
        <p>Zatím nemáte žádné oblíbené zastávky. Přidejte si je v <a href="<?= e(url('/nastaveni')) ?>">Osobním nastavení</a>.</p>
    </div>
<?php elseif ($transit['state'] === 'unconfigured'): ?>
    <div class="widget-state">
        <?= icon('tram', 22) ?>
        <p>Odjezdy nejsou na tomto serveru nastaveny (chybí GOLEMIO_API_KEY v souboru .env).</p>
    </div>
<?php elseif ($transit['departures'] === []): ?>
    <div class="widget-state">
        <?= icon('clock', 22) ?>
        <p>V nejbližší hodině a půl z vašich zastávek nic nejede. Zkontrolujte přesné názvy zastávek v nastavení.</p>
    </div>
<?php else: ?>
    <?php if ($transit['demo']): ?>
        <p class="demo-note demo-note--short" title="Skutečné odjezdy vyžadují klíč Golemio API v souboru .env.">Ukázková data</p>
    <?php endif; ?>
    <ul class="departures">
        <?php foreach ($transit['departures'] as $departure): ?>
            <li class="departure">
                <span class="line line--<?= e($departure['type']) ?>">
                    <span class="visually-hidden"><?= e($vehicles[$departure['type']] ?? 'linka') ?></span>
                    <?= e($departure['line']) ?>
                </span>
                <span class="departure__text">
                    <span class="departure__headsign"><?= e($departure['headsign']) ?></span>
                    <span class="departure__stop"><?= e($departure['stop']) ?><?= $departure['delay'] > 0 ? ' · zpoždění ' . (int) $departure['delay'] . ' min' : '' ?></span>
                </span>
                <span class="departure__time">
                    <?php if ((int) $departure['minutes'] === 0): ?>
                        <strong>teď</strong>
                    <?php else: ?>
                        <strong><?= (int) $departure['minutes'] ?></strong>&nbsp;min
                    <?php endif; ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
