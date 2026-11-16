<?php
/** Jednorázové zprávy po uložení formuláře (úspěch, chyba, informace). */
$messages = flashes();
if ($messages === []) {
    return;
}
?>
<div class="flashes" id="flashes">
    <?php foreach ($messages as $flash): ?>
        <div class="flash flash--<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
            <?= icon($flash['type'] === 'success' ? 'check' : ($flash['type'] === 'error' ? 'alert' : 'info'), 18) ?>
            <p><?= e($flash['message']) ?></p>
            <button type="button" class="flash__close" data-flash-close aria-label="Zavřít zprávu"><?= icon('x', 16) ?></button>
        </div>
    <?php endforeach; ?>
</div>
