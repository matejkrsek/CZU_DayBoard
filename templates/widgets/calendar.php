<?php
/**
 * Widget Dnešní program (Google Kalendář).
 *
 * @var array{state:string,demo:bool,events:list<array<string,mixed>>} $calendar
 */
$settingsLink = '<a href="' . e(url('/nastaveni')) . '">Osobním nastavení</a>';
?>
<?php if ($calendar['state'] === 'disconnected'): ?>
    <div class="widget-state">
        <?= icon('calendar', 22) ?>
        <p>Google Kalendář zatím není připojen. Připojíte ho v <?= $settingsLink ?>.</p>
    </div>
<?php elseif ($calendar['state'] === 'expired'): ?>
    <div class="widget-state">
        <?= icon('alert', 22) ?>
        <p>Připojení ke Google Kalendáři vypršelo. Připojte účet znovu v <?= $settingsLink ?>.</p>
    </div>
<?php elseif ($calendar['state'] === 'unconfigured'): ?>
    <div class="widget-state">
        <?= icon('calendar', 22) ?>
        <p>Google Kalendář není na tomto serveru nastaven (chybí údaje OAuth klienta v souboru .env).</p>
    </div>
<?php elseif ($calendar['events'] === []): ?>
    <div class="widget-state">
        <?= icon('check', 22) ?>
        <p>Dnes nemáte v kalendáři žádné události.</p>
    </div>
<?php else: ?>
    <?php if ($calendar['demo']): ?>
        <p class="demo-note">Ukázková data – vlastní události uvidíte po připojení Google Kalendáře v <?= $settingsLink ?>.</p>
    <?php endif; ?>
    <ol class="events">
        <?php foreach ($calendar['events'] as $event): ?>
            <li class="event<?= $event['past'] ? ' is-past' : '' ?><?= $event['current'] ? ' is-current' : '' ?>">
                <time class="event__time"<?= $event['allDay'] ? '' : ' datetime="' . e(date('c', $event['start'])) . '"' ?>><?= e($event['time']) ?></time>
                <span class="event__text">
                    <span class="event__title"><?= e($event['title']) ?></span>
                    <?php if ($event['location'] !== ''): ?>
                        <span class="event__place"><?= e(str_limit($event['location'], 60)) ?></span>
                    <?php endif; ?>
                </span>
                <?php if ($event['current']): ?><span class="event__badge">právě teď</span><?php endif; ?>
                <?php if ($event['duration'] !== ''): ?><span class="event__duration"><?= e($event['duration']) ?></span><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>
