<?php
/**
 * Widget Poloha členů. Mapa (Leaflet + OpenStreetMap) se načte až ve chvíli,
 * kdy je widget vidět; seznam pod ní je zároveň textovou alternativou mapy.
 *
 * @var array{home:array<string,mixed>,members:list<array<string,mixed>>} $locations
 */
?>
<div class="members" data-members>
    <div class="members__map" data-map tabindex="-1" aria-label="Mapa se sdílenou polohou členů domácnosti">
        <p class="members__map-placeholder">Mapa členů</p>
    </div>
    <ul class="members__list" data-members-list>
        <?php foreach ($locations['members'] as $member): ?>
            <li class="members__item">
                <span class="members__dot" style="background: <?= e($member['color']) ?>" aria-hidden="true"><?= e($member['initial']) ?></span>
                <span class="members__name"><?= e($member['name']) ?></span>
                <span class="members__place"><?= e($member['place']) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if ($locations['members'] === []): ?>
        <p class="members__empty">Polohu teď nikdo nesdílí.</p>
    <?php endif; ?>
    <p class="members__note">Poloha je pro účely projektu simulovaná.</p>
    <script type="application/json" data-members-data><?= json_encode($locations, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
</div>
