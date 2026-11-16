<?php
/**
 * Rámeček widgetu v mřížce nástěnky.
 *
 * Pozice a velikost jsou v CSS proměnných (--c = sloupec, --r = řádek, --w, --h),
 * podle kterých app.css widget umístí do mřížky; --w2 je šířka pro dvousloupcové
 * zobrazení na tabletu. Stejné hodnoty čte JavaScript z data-atributů (x, y od nuly).
 *
 * @var array{key:string,x:int,y:int,w:int,h:int} $item
 * @var array<string,mixed> $widget   záznam z WidgetRegistry
 * @var string|null $body             hotový obsah, null = načíst AJAXem
 * @var bool $showScope               zobrazit štítek Sdílené / Osobní
 */

use DayBoard\Widgets\WidgetRegistry;

$key = $item['key'];
$isAsync = (bool) $widget['async'];
$style = sprintf('--c:%d;--r:%d;--w:%d;--h:%d;--w2:%d', $item['x'] + 1, $item['y'] + 1, $item['w'], $item['h'], min(2, $item['w']));
?>
<section class="widget widget--<?= e($key) ?>" id="widget-<?= e($key) ?>" style="<?= e($style) ?>"
         aria-labelledby="widget-<?= e($key) ?>-title"
         data-widget="<?= e($key) ?>" data-title="<?= e($widget['title']) ?>"
         data-x="<?= (int) $item['x'] ?>" data-y="<?= (int) $item['y'] ?>" data-w="<?= (int) $item['w'] ?>" data-h="<?= (int) $item['h'] ?>"
         data-min-w="<?= (int) $widget['minW'] ?>" data-min-h="<?= (int) $widget['minH'] ?>"
         data-refresh="<?= (int) $widget['refresh'] ?>"
         <?= $isAsync ? 'data-src="' . e(url('/api/widgets/' . $key)) . '"' : '' ?>>
    <header class="widget__head">
        <span class="widget__icon"><?= icon((string) $widget['icon'], 18) ?></span>
        <h2 class="widget__title" id="widget-<?= e($key) ?>-title"><?= e($widget['title']) ?></h2>
        <?php if ($showScope): ?>
            <span class="widget__scope"><?= e(WidgetRegistry::scopeLabel((string) $widget['scope'])) ?></span>
        <?php endif; ?>
        <button type="button" class="widget__remove" data-widget-remove aria-label="Odebrat widget <?= e($widget['title']) ?>" hidden><?= icon('x', 14) ?></button>
    </header>

    <div class="widget__body" data-widget-body>
        <?php if ($body !== null): ?>
            <?= $body ?>
        <?php else: ?>
            <?php // Kostra drží místo, dokud se obsah nenačte – stránka pak neposkakuje (CLS). ?>
            <div class="skeleton" aria-hidden="true"><span></span><span></span><span></span></div>
            <p class="visually-hidden">Načítá se…</p>
        <?php endif; ?>
    </div>

    <?php // Zástupný obsah a úchyt pro změnu velikosti – viditelné jen v režimu úprav. ?>
    <div class="widget__ghost" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
    <span class="widget__size" aria-hidden="true"></span>
    <span class="widget__resize" data-widget-resize aria-hidden="true"></span>
</section>
