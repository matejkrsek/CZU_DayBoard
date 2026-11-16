<?php
/**
 * Widget Rádio. Zvuk se začne načítat až po kliknutí na Přehrát –
 * do té doby stránka žádný stream neotevírá.
 *
 * @var list<array<string,mixed>> $stations  oblíbené stanice domácnosti
 */
?>
<?php if ($stations === []): ?>
    <div class="widget-state">
        <?= icon('radio', 22) ?>
        <p>Správce domácnosti zatím nepřidal žádné rozhlasové stanice.</p>
    </div>
<?php else: ?>
    <div class="radio" data-radio>
        <div class="radio__player">
            <button type="button" class="radio__toggle" data-radio-toggle aria-label="Přehrát">
                <span class="radio__icon-play"><?= icon('play', 22) ?></span>
                <span class="radio__icon-pause"><?= icon('pause', 22) ?></span>
            </button>
            <div class="radio__now">
                <p class="radio__station" data-radio-name><?= e($stations[0]['name']) ?></p>
                <p class="radio__status" data-radio-status role="status">Připraveno k přehrání</p>
                <label class="radio__volume">
                    <?= icon('volume', 16) ?>
                    <span class="visually-hidden">Hlasitost</span>
                    <input type="range" min="0" max="100" step="5" value="70" data-radio-volume>
                </label>
            </div>
        </div>
        <ul class="radio__stations" aria-label="Oblíbené stanice">
            <?php foreach ($stations as $index => $station): ?>
                <li>
                    <button type="button" class="radio__pick" data-radio-station
                            data-url="<?= e($station['stream_url']) ?>" data-name="<?= e($station['name']) ?>"
                            aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"><?= e($station['name']) ?></button>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
