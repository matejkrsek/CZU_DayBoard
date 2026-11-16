<?php
/**
 * Widget Nákupní seznam (sdílený pro celou domácnost).
 *
 * @var list<array<string,mixed>> $items
 * @var bool $canDelete  host/dítě standardně položky jen přidává
 */

use DayBoard\Models\Shopping;

$hasDone = array_filter($items, static fn (array $item): bool => (int) $item['done'] === 1) !== [];
?>
<div class="shopping">
    <form class="shopping__add" data-shopping-add>
        <label class="visually-hidden" for="shopping-new">Nová položka nákupního seznamu</label>
        <input type="text" id="shopping-new" name="name" maxlength="<?= Shopping::MAX_NAME_LENGTH ?>"
               placeholder="Přidat položku…" autocomplete="off" enterkeyhint="done" required>
        <button type="submit" class="btn btn--primary btn--icon" aria-label="Přidat položku"><?= icon('plus', 18) ?></button>
    </form>

    <?php if ($items === []): ?>
        <p class="shopping__empty">Seznam je prázdný. Co je potřeba koupit?</p>
    <?php else: ?>
        <ul class="shopping__list">
            <?php foreach ($items as $item): ?>
                <li class="shopping__item<?= (int) $item['done'] === 1 ? ' is-done' : '' ?>">
                    <label class="shopping__label">
                        <input type="checkbox" data-shopping-toggle="<?= (int) $item['id'] ?>"<?= (int) $item['done'] === 1 ? ' checked' : '' ?>>
                        <span><?= e($item['name']) ?></span>
                    </label>
                    <?php if ($canDelete): ?>
                        <button type="button" class="shopping__delete" data-shopping-delete="<?= (int) $item['id'] ?>"
                                aria-label="Smazat položku <?= e($item['name']) ?>"><?= icon('x', 14) ?></button>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($hasDone && $canDelete): ?>
            <button type="button" class="link-button shopping__clear" data-shopping-clear>Smazat koupené</button>
        <?php endif; ?>
    <?php endif; ?>
</div>
