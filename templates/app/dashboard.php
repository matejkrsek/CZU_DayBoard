<?php
/**
 * Nástěnka (wireframe 2) včetně režimu úprav (wireframe 3).
 *
 * @var array<string,mixed> $user
 * @var string $greeting
 * @var string $today
 * @var list<array{key:string,x:int,y:int,w:int,h:int}> $layout
 * @var array<string,array<string,mixed>> $registry
 * @var array<string,string> $bodies        předem vykreslený obsah widgetů bez AJAXu
 * @var bool $canEdit
 * @var array<string,mixed> $editorConfig
 */

$showGreeting = (int) $user['show_greeting'] === 1;
$onBoard = array_column($layout, 'key');
?>
<div class="page page--dashboard">
    <div class="dash-head" id="dash-head">
        <div>
            <?php if ($showGreeting): ?>
                <h1 class="dash-head__title"><?= e($greeting) ?></h1>
                <p class="dash-head__date"><?= e($today) ?></p>
            <?php else: ?>
                <h1 class="visually-hidden">Nástěnka</h1>
            <?php endif; ?>
        </div>
        <?php if ($canEdit): ?>
            <button type="button" class="btn btn--primary" id="edit-start" hidden><?= icon('edit', 16) ?><span>Upravit nástěnku</span></button>
        <?php endif; ?>
    </div>

    <noscript><p class="flash flash--info">Nástěnka potřebuje zapnutý JavaScript – bez něj se widgety nenačtou.</p></noscript>

    <?php if ($canEdit): ?>
        <div class="editbar" id="editbar" hidden>
            <div>
                <h2 class="editbar__title">Režim úprav nástěnky</h2>
                <p class="editbar__hint">Widgety přetahujte, tahem za roh měňte velikost, křížkem je odeberete.</p>
            </div>
            <div class="editbar__actions">
                <button type="button" class="btn" id="edit-reset">Obnovit výchozí rozložení</button>
                <button type="button" class="btn" id="edit-cancel">Zrušit</button>
                <button type="button" class="btn btn--primary" id="edit-save">Uložit rozložení</button>
            </div>
        </div>
    <?php endif; ?>

    <div class="board" id="board">
        <div class="grid" id="grid">
            <?php foreach ($layout as $item): ?>
                <?= partial('app/_widget', [
                    'item' => $item,
                    'widget' => $registry[$item['key']],
                    'body' => $bodies[$item['key']] ?? null,
                    'showScope' => (int) $user['show_scope_labels'] === 1,
                ]) ?>
            <?php endforeach; ?>
        </div>

        <p class="board__empty" id="board-empty" <?= $layout !== [] ? 'hidden' : '' ?>>
            <?= $canEdit
                ? 'Nástěnka je zatím prázdná. Klikněte na „Upravit nástěnku“ a přidejte si widgety.'
                : 'Správce domácnosti vám zatím nevybral žádné widgety.' ?>
        </p>

        <?php if ($canEdit): ?>
            <aside class="palette" id="palette" aria-labelledby="palette-title" hidden>
                <h2 class="palette__title" id="palette-title">Dostupné widgety</h2>
                <p class="palette__hint">Nabídka povolená správcem domácnosti</p>
                <label class="visually-hidden" for="palette-search">Hledat widget</label>
                <input type="search" id="palette-search" class="palette__search" placeholder="Hledat widget…" autocomplete="off">
                <ul class="palette__list" id="palette-list"></ul>
                <div class="legend">
                    <h3 class="legend__title">Jak upravovat</h3>
                    <ol class="legend__list">
                        <li>Přetažením widget přesunete.</li>
                        <li>Tažením za pravý dolní roh změníte velikost.</li>
                        <li>Křížkem widget odeberete.</li>
                    </ol>
                    <p class="legend__keys"><strong>Klávesnice:</strong> šipky widget přesouvají, Shift + šipky mění velikost, Delete ho odebere.</p>
                </div>
            </aside>
        <?php endif; ?>
    </div>

    <?php if ($canEdit): ?>
        <?php // Šablony widgetů, které na nástěnce zatím nejsou – JavaScript je při přidání naklonuje. ?>
        <?php foreach ($registry as $key => $widget): ?>
            <?php if (!in_array($key, $onBoard, true)): ?>
                <template id="widget-template-<?= e($key) ?>">
                    <?= partial('app/_widget', [
                        'item' => ['key' => $key, 'x' => 0, 'y' => 0, 'w' => $widget['w'], 'h' => $widget['h']],
                        'widget' => $widget,
                        'body' => '',
                        'showScope' => (int) $user['show_scope_labels'] === 1,
                    ]) ?>
                </template>
            <?php endif; ?>
        <?php endforeach; ?>
        <script type="application/json" id="editor-config"><?= json_encode($editorConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <?php endif; ?>

    <p class="visually-hidden" id="live-region" role="status" aria-live="polite"></p>
</div>
