<?php
/**
 * Chybový stav widgetu (např. výpadek externího API).
 *
 * @var string $message
 * @var string|null $detail  technický detail, jen mimo produkci
 */
?>
<div class="widget-state widget-state--error">
    <?= icon('alert', 22) ?>
    <p><?= e($message) ?></p>
    <?php if (!empty($detail)): ?>
        <p class="widget-state__detail"><?= e(str_limit($detail, 140)) ?></p>
    <?php endif; ?>
    <button type="button" class="btn btn--small" data-widget-retry><?= icon('refresh', 14) ?><span>Zkusit znovu</span></button>
</div>
