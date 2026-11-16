<?php
/**
 * Správa domácnosti (wireframe 5) a okno Přidat člena (wireframe 5a). Jen pro správce.
 *
 * @var array<string,mixed> $user
 * @var list<array<string,mixed>> $members
 * @var array<int|string,string> $postedRoles   role odeslané formulářem (po chybě)
 * @var array<string,string> $settings
 * @var array<string,array{members:bool,guest:bool}> $access
 * @var array<string,array<string,mixed>> $widgets
 * @var list<array{name:string,stream_url:string,station_uuid:string}> $stations
 * @var list<string> $timezones
 * @var array<string,string> $errors
 */

use DayBoard\Models\Household;
use DayBoard\Models\Users;
use DayBoard\Support\Czech;

$error = static fn (string $key): string => isset($errors[$key])
    ? '<p class="field__error" id="' . e($key) . '-error">' . e($errors[$key]) . '</p>'
    : '';
$invalid = static fn (string $key): string => isset($errors[$key])
    ? ' aria-invalid="true" aria-describedby="' . e($key) . '-error"'
    : '';
$memberCount = count($members);
?>
<div class="page page--household">
    <header class="page-head">
        <h1>Správa domácnosti</h1>
        <p class="page-head__lead">Tuto obrazovku vidí pouze správce domácnosti.</p>
    </header>

    <?php if ($errors !== []): ?>
        <div class="flash flash--error" role="alert">
            <?= icon('alert', 18) ?>
            <p>Změny se neuložily – opravte prosím označená pole.</p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/domacnost')) ?>" id="household-form" class="household-layout" novalidate>
        <?= csrf_field() ?>

        <div class="household-layout__main">
            <section class="card" id="clenove" aria-labelledby="clenove-title">
                <header class="card__head">
                    <div>
                        <h2 id="clenove-title">Členové domácnosti a jejich role</h2>
                        <p class="card__lead"><?= $memberCount ?> <?= Czech::plural($memberCount, 'člen', 'členové', 'členů') ?></p>
                    </div>
                    <button type="button" class="btn btn--primary" data-member-add hidden><?= icon('plus', 16) ?><span>Přidat člena</span></button>
                </header>
                <?= $error('roles') ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Jméno</th>
                                <th scope="col">E-mail / uživatelské jméno</th>
                                <th scope="col">Role</th>
                                <th scope="col">Akce</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <?php $role = (string) ($postedRoles[$member['id']] ?? $member['role']); ?>
                                <tr>
                                    <th scope="row">
                                        <span class="member-name">
                                            <span class="avatar avatar--small" aria-hidden="true"><?= e(Users::initial($member)) ?></span>
                                            <?= e($member['display_name']) ?>
                                            <?php if ((int) $member['id'] === (int) $user['id']): ?><span class="tag">vy</span><?php endif; ?>
                                        </span>
                                    </th>
                                    <td class="table__muted"><?= e($member['login']) ?></td>
                                    <td>
                                        <label class="visually-hidden" for="role-<?= (int) $member['id'] ?>">Role člena <?= e($member['display_name']) ?></label>
                                        <select id="role-<?= (int) $member['id'] ?>" name="roles[<?= (int) $member['id'] ?>]">
                                            <?php foreach (Users::ROLES as $value => $label): ?>
                                                <option value="<?= e($value) ?>"<?= $role === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="table__actions">
                                        <button type="button" class="link-button" data-member-edit hidden
                                                data-id="<?= (int) $member['id'] ?>" data-name="<?= e($member['display_name']) ?>"
                                                data-login="<?= e($member['login']) ?>" data-role="<?= e($member['role']) ?>">Upravit<span class="visually-hidden"> člena <?= e($member['display_name']) ?></span></button>
                                        <?php if ((int) $member['id'] !== (int) $user['id']): ?>
                                            <button type="submit" class="link-button link-button--muted" form="member-delete-<?= (int) $member['id'] ?>"
                                                    data-confirm="Opravdu odebrat člena <?= e($member['display_name']) ?>? Smaže se i jeho nástěnka a osobní nastavení."
                                                    data-confirm-label="Odebrat">Odebrat<span class="visually-hidden"> člena <?= e($member['display_name']) ?></span></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card" id="sdilena" aria-labelledby="sdilena-title">
                <header class="card__head">
                    <div>
                        <h2 id="sdilena-title">Sdílená nastavení domácnosti</h2>
                        <p class="card__lead">Platí pro všechny členy</p>
                    </div>
                </header>

                <div class="location">
                    <div class="location__fields">
                        <div class="field">
                            <label for="location_name">Lokalita domácnosti (Počasí, Poloha členů, Doprava)</label>
                            <div class="input-group">
                                <input type="text" id="location_name" name="location_name" value="<?= e($settings['location_name']) ?>" maxlength="80" autocomplete="off" required<?= $invalid('location_name') ?>>
                                <button type="button" class="btn" id="location-search" hidden>Najít</button>
                            </div>
                            <?= $error('location_name') ?>
                            <ul class="picker" id="location-results" hidden></ul>
                            <p class="field__hint" id="location-status" role="status"></p>
                            <input type="hidden" name="location_lat" id="location_lat" value="<?= e($settings['location_lat']) ?>">
                            <input type="hidden" name="location_lon" id="location_lon" value="<?= e($settings['location_lon']) ?>">
                            <input type="hidden" name="location_resolved" id="location_resolved" value="<?= e($settings['location_name']) ?>">
                        </div>
                        <div class="field">
                            <label for="timezone">Časové pásmo</label>
                            <select id="timezone" name="timezone"<?= $invalid('timezone') ?>>
                                <?php foreach ($timezones as $timezone): ?>
                                    <option value="<?= e($timezone) ?>"<?= $settings['timezone'] === $timezone ? ' selected' : '' ?>><?= e($timezone) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $error('timezone') ?>
                        </div>
                    </div>
                    <div class="location__preview" id="location-preview" aria-label="Náhled lokality na mapě"
                         data-lat="<?= e($settings['location_lat']) ?>" data-lon="<?= e($settings['location_lon']) ?>">
                        <p>Náhled lokality</p>
                    </div>
                </div>

                <div class="field">
                    <label for="default_rss_url">Výchozí RSS zdroj sportovních novinek</label>
                    <input type="url" id="default_rss_url" name="default_rss_url" value="<?= e($settings['default_rss_url']) ?>" maxlength="300" inputmode="url" required<?= $invalid('default_rss_url') ?>>
                    <?= $error('default_rss_url') ?>
                </div>

                <fieldset class="plain-fieldset">
                    <legend>Oblíbené rozhlasové stanice domácnosti (Radio Browser)</legend>
                    <ul class="chips chips--inline" id="station-list" aria-label="Vybrané stanice">
                        <?php foreach ($stations as $index => $station): ?>
                            <li class="chip">
                                <input type="hidden" name="stations[<?= $index ?>][name]" value="<?= e($station['name']) ?>">
                                <input type="hidden" name="stations[<?= $index ?>][stream_url]" value="<?= e($station['stream_url']) ?>">
                                <input type="hidden" name="stations[<?= $index ?>][station_uuid]" value="<?= e($station['station_uuid']) ?>">
                                <span><?= e($station['name']) ?></span>
                                <button type="button" class="chip__remove" data-chip-remove aria-label="Odebrat stanici <?= e($station['name']) ?>" hidden><?= icon('x', 13) ?></button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn btn--small" id="station-add" data-max="<?= Household::MAX_STATIONS ?>" hidden><?= icon('plus', 14) ?><span>Přidat stanici</span></button>
                    <?= $error('stations') ?>
                </fieldset>

                <div class="switch-list">
                    <label class="switch">
                        <input type="checkbox" role="switch" name="guest_shopping_add_only" value="1"<?= $settings['guest_shopping_add_only'] === '1' ? ' checked' : '' ?>>
                        <span class="switch__track" aria-hidden="true"></span>
                        <span>Host / dítě smí v nákupním seznamu pouze přidávat položky</span>
                    </label>
                    <label class="switch">
                        <input type="checkbox" role="switch" name="guest_can_customize" value="1"<?= $settings['guest_can_customize'] === '1' ? ' checked' : '' ?>>
                        <span class="switch__track" aria-hidden="true"></span>
                        <span>Host / dítě smí měnit vzhled své nástěnky</span>
                    </label>
                </div>
                <p class="card__footnote"><?= icon('info', 15) ?> Sdílená poloha členů je simulovaná vlastní službou.</p>
            </section>
        </div>

        <div class="household-layout__side">
            <section class="card" id="widgety" aria-labelledby="widgety-title">
                <header class="card__head">
                    <div>
                        <h2 id="widgety-title">Dostupné widgety</h2>
                        <p class="card__lead">Co smí členové a hosté použít. Správce má k dispozici všechny.</p>
                    </div>
                </header>
                <div class="table-wrap">
                    <table class="table table--widgets">
                        <thead>
                            <tr>
                                <th scope="col">Widget</th>
                                <th scope="col">Členové</th>
                                <th scope="col">Host / dítě</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($widgets as $key => $widget): ?>
                                <?php $available = ($widget['available'] ?? true) === true; ?>
                                <tr<?= $available ? '' : ' class="is-disabled"' ?>>
                                    <th scope="row">
                                        <span class="widget-name"><?= icon((string) $widget['icon'], 17) ?><?= e($widget['title']) ?><?= $available ? '' : ' <span class="tag">rozšíření</span>' ?></span>
                                    </th>
                                    <td>
                                        <label class="switch switch--solo">
                                            <input type="checkbox" role="switch" name="widgets[<?= e($key) ?>][members]" value="1"
                                                   aria-label="<?= e($widget['title']) ?> – povolit členům"
                                                   <?= $available && $access[$key]['members'] ? 'checked' : '' ?><?= $available ? '' : ' disabled' ?>>
                                            <span class="switch__track" aria-hidden="true"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <input type="checkbox" class="checkbox" name="widgets[<?= e($key) ?>][guest]" value="1"
                                               aria-label="<?= e($widget['title']) ?> – zobrazit hostovi / dítěti"
                                               <?= $available && $access[$key]['guest'] ? 'checked' : '' ?><?= $available ? '' : ' disabled' ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="form__actions form__actions--sticky household-layout__actions">
            <a class="btn" href="<?= e(url('/domacnost')) ?>">Zrušit</a>
            <button type="submit" class="btn btn--primary">Uložit změny</button>
        </div>
    </form>

    <?php // Formuláře pro odebrání člena stojí mimo hlavní formulář; tlačítka na ně odkazují atributem form. ?>
    <?php foreach ($members as $member): ?>
        <?php if ((int) $member['id'] !== (int) $user['id']): ?>
            <form method="post" action="<?= e(url('/domacnost/clenove/' . (int) $member['id'] . '/odebrat')) ?>" id="member-delete-<?= (int) $member['id'] ?>" hidden><?= csrf_field() ?></form>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Překryvné okno: přidat / upravit člena (wireframe 5a) -->
    <dialog class="dialog" id="member-dialog" aria-labelledby="member-dialog-title">
        <form method="post" action="<?= e(url('/domacnost/clenove')) ?>" id="member-form" data-base-action="<?= e(url('/domacnost/clenove')) ?>">
            <?= csrf_field() ?>
            <header class="dialog__head">
                <h2 class="dialog__title" id="member-dialog-title">Přidat člena domácnosti</h2>
                <button type="button" class="dialog__close" data-dialog-close aria-label="Zavřít okno"><?= icon('x', 16) ?></button>
            </header>
            <div class="dialog__body form">
                <div class="field">
                    <label for="member-name">Jméno</label>
                    <input type="text" id="member-name" name="display_name" maxlength="40" placeholder="Jméno člena" required>
                </div>
                <div class="field">
                    <label for="member-login">E-mail / uživatelské jméno</label>
                    <input type="text" id="member-login" name="login" maxlength="190" placeholder="clen@domacnost.cz" autocapitalize="none" spellcheck="false" autocomplete="off" required>
                </div>
                <fieldset class="segmented">
                    <legend>Role</legend>
                    <div class="segmented__options">
                        <?php foreach (Users::ROLES as $value => $label): ?>
                            <label><input type="radio" name="role" value="<?= e($value) ?>"<?= $value === 'member' ? ' checked' : '' ?>><span><?= e($value === 'admin' ? 'Správce' : $label) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <div class="field">
                    <label for="member-password" id="member-password-label">Dočasné heslo</label>
                    <input type="password" id="member-password" name="password" minlength="<?= Users::MIN_PASSWORD_LENGTH ?>" autocomplete="new-password" aria-describedby="member-password-hint" required>
                    <p class="field__hint" id="member-password-hint">Alespoň <?= Users::MIN_PASSWORD_LENGTH ?> znaků.</p>
                </div>
                <label class="check">
                    <input type="checkbox" name="must_change" value="1" checked>
                    <span>Vyžadovat změnu hesla při prvním přihlášení</span>
                </label>
            </div>
            <footer class="dialog__actions">
                <button type="button" class="btn" data-dialog-close>Zrušit</button>
                <button type="submit" class="btn btn--primary" id="member-submit">Přidat</button>
            </footer>
        </form>
    </dialog>

    <!-- Překryvné okno: vyhledání rozhlasové stanice -->
    <dialog class="dialog" id="station-dialog" aria-labelledby="station-dialog-title">
        <header class="dialog__head">
            <h2 class="dialog__title" id="station-dialog-title">Přidat rozhlasovou stanici</h2>
            <button type="button" class="dialog__close" data-dialog-close aria-label="Zavřít okno"><?= icon('x', 16) ?></button>
        </header>
        <div class="dialog__body">
            <form id="station-search-form" class="input-group" role="search">
                <label class="visually-hidden" for="station-query">Název stanice</label>
                <input type="search" id="station-query" placeholder="Název stanice, např. Radiožurnál" autocomplete="off" required minlength="2">
                <button type="submit" class="btn btn--primary">Hledat</button>
            </form>
            <p class="field__hint" id="station-status" role="status">Stanice se hledají v otevřeném katalogu Radio Browser.</p>
            <ul class="picker picker--stations" id="station-results"></ul>
        </div>
        <footer class="dialog__actions">
            <button type="button" class="btn" data-dialog-close>Hotovo</button>
        </footer>
    </dialog>
</div>
