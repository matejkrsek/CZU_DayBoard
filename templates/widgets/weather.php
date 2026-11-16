<?php
/**
 * Widget Počasí.
 *
 * @var array<string,mixed> $weather  výstup WeatherService::forecast()
 */
?>
<div class="weather">
    <div class="weather__now">
        <span class="weather__icon"><?= icon($weather['icon'], 46) ?></span>
        <div>
            <p class="weather__temp"><?= (int) $weather['temp'] ?>&nbsp;°C</p>
            <p class="weather__desc"><?= e($weather['desc']) ?> · <?= e($weather['location']) ?></p>
        </div>
    </div>
    <p class="weather__meta">Pocitově <?= (int) $weather['feels'] ?>&nbsp;°C · vítr <?= (int) $weather['wind'] ?>&nbsp;km/h</p>
    <ul class="weather__days" aria-label="Předpověď na další dny">
        <?php foreach ($weather['days'] as $day): ?>
            <li class="weather__day">
                <span class="weather__day-name"><?= e($day['label']) ?></span>
                <?= icon($day['icon'], 20) ?>
                <span class="visually-hidden"><?= e($day['desc']) ?>,</span>
                <span class="weather__day-max"><?= (int) $day['max'] ?>°</span>
                <span class="weather__day-min"><span class="visually-hidden">v noci</span> <?= (int) $day['min'] ?>°</span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
