<?php
/**
 * Logo DayBoardu – vycházející slunce. Dekorativní, název stojí vedle jako text.
 *
 * @var int $size
 */
$size ??= 30;
?>
<svg class="brand__mark" width="<?= (int) $size ?>" height="<?= (int) $size ?>" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
    <rect width="32" height="32" rx="8" fill="var(--accent)"/>
    <path d="M10.5 22a5.5 5.5 0 0 1 11 0z" fill="#fff"/>
    <path d="M6.5 22h19M16 9.5v3M9.3 12.8l2 2M22.7 12.8l-2 2" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
</svg>
