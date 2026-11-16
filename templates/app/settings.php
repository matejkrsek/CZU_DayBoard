<?php
/**
 * Osobní nastavení (wireframe 4).
 *
 * @var array<string,mixed> $user
 * @var array<string,mixed> $form          hodnoty formuláře
 * @var array<string,string> $errors
 * @var bool $canAppearance                smí měnit vzhled (host/dítě jen se svolením správce)
 * @var bool $canPersonal                  smí měnit účty, cíle cest a zastávky
 * @var array<string,string> $homePages
 * @var bool $googleConfigured
 * @var string $defaultRss
 * @var string $homeName
 */

use DayBoard\Controllers\SettingsController;
use DayBoard\Models\Users;

$error = static fn (string $key): string => isset($errors[$key])
    ? '<p class="field__error" id="' . e($key) . '-error">' . e($errors[$key]) . '</p>'
    : '';
$invalid = static fn (string $key): string => isset($errors[$key])
    ? ' aria-invalid="true" aria-describedby="' . e($key) . '-error"'
    : '';

// Vždy tři řádky pro cíle cest; nevyplněné JavaScript skryje a nabídne tlačítko „Přidat“.
$destinations = array_pad($form['destinations'], Users::MAX_DESTINATIONS, ['id' => 0, 'label' => '', 'address' => '']);
$popularStops = ['Anděl', 'Budějovická', 'Černý Most', 'Dejvická', 'Florenc', 'Hlavní nádraží', 'Hradčanská', 'I. P. Pavlova',
    'Karlovo náměstí', 'Muzeum', 'Můstek', 'Nádraží Holešovice', 'Náměstí Míru', 'Národní třída', 'Palmovka', 'Smíchovské nádraží', 'Zličín'];
?>
<div class="page page--settings">
    <header class="page-head">
        <h1>Osobní nastavení</h1>
        <p class="page-head__lead">Nastavení platí jen pro váš účet. Nastavení sdílená celou domácností spravuje správce.</p>
    </header>

    <?php if ($errors !== []): ?>
        <div class="flash flash--error" role="alert">
            <?= icon('alert', 18) ?>
            <p>Nastavení se neuložilo – opravte prosím označená pole.</p>
        </div>
    <?php endif; ?>

    <?php if (!$canAppearance && !$canPersonal): ?>
        <div class="card">
            <p>Osobní nastavení pro váš účet spravuje správce domácnosti. Pokud chcete něco změnit, požádejte ho.</p>
        </div>
    <?php else: ?>
        <div class="settings-layout">
            <nav class="section-nav" aria-label="Sekce nastavení">
                <ul>
                    <?php if ($canAppearance): ?><li><a href="#vzhled">Vzhled a preference</a></li><?php endif; ?>
                    <?php if ($canPersonal): ?>
                        <li><a href="#ucty">Připojené účty a osobní údaje</a></li>
                        <li><a href="#cesty">Cíle cest a oblíbené zastávky</a></li>
                    <?php endif; ?>
                </ul>
                <?php if (!$canPersonal): ?>
                    <p class="note">Jako host / dítě máte nastavení omezené – připojené účty a cíle cest spravuje správce.</p>
                <?php endif; ?>
            </nav>

            <form method="post" action="<?= e(url('/nastaveni')) ?>" class="settings-form" id="settings-form" novalidate>
                <?= csrf_field() ?>

                <?php if ($canAppearance): ?>
                    <section class="card" id="vzhled" aria-labelledby="vzhled-title">
                        <header class="card__head">
                            <div>
                                <h2 id="vzhled-title">Vzhled nástěnky a preference</h2>
                                <p class="card__lead">Jak vypadá vaše nástěnka</p>
                            </div>
                        </header>
                        <div class="settings-grid">
                            <div class="settings-grid__col">
                                <fieldset class="segmented">
                                    <legend>Motiv</legend>
                                    <div class="segmented__options">
                                        <?php foreach (SettingsController::THEMES as $value => $label): ?>
                                            <label><input type="radio" name="theme" value="<?= e($value) ?>"<?= $form['theme'] === $value ? ' checked' : '' ?>><span><?= e($label) ?></span></label>
                                        <?php endforeach; ?>
                                    </div>
                                    <?= $error('theme') ?>
                                </fieldset>
                                <fieldset class="segmented">
                                    <legend>Hustota mřížky</legend>
                                    <div class="segmented__options">
                                        <?php foreach (SettingsController::DENSITIES as $value => $label): ?>
                                            <label><input type="radio" name="density" value="<?= e($value) ?>"<?= $form['density'] === $value ? ' checked' : '' ?>><span><?= e($label) ?></span></label>
                                        <?php endforeach; ?>
                                    </div>
                                    <?= $error('density') ?>
                                </fieldset>
                            </div>
                            <div class="settings-grid__col">
                                <div class="field">
                                    <label for="language">Jazyk rozhraní</label>
                                    <select id="language" aria-describedby="language-hint">
                                        <option selected>Čeština</option>
                                    </select>
                                    <p class="field__hint" id="language-hint">Další jazyky zatím nejsou k dispozici.</p>
                                </div>
                                <div class="field">
                                    <label for="home_page">Domovská stránka po přihlášení</label>
                                    <select id="home_page" name="home_page"<?= $invalid('home_page') ?>>
                                        <?php foreach ($homePages as $value => $label): ?>
                                            <option value="<?= e($value) ?>"<?= $form['home_page'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?= $error('home_page') ?>
                                </div>
                            </div>
                            <div class="settings-grid__col settings-grid__col--switches">
                                <label class="switch">
                                    <input type="checkbox" role="switch" name="show_greeting" value="1"<?= $form['show_greeting'] ? ' checked' : '' ?>>
                                    <span class="switch__track" aria-hidden="true"></span>
                                    <span>Uvítání a datum</span>
                                </label>
                                <label class="switch">
                                    <input type="checkbox" role="switch" name="show_scope_labels" value="1"<?= $form['show_scope_labels'] ? ' checked' : '' ?>>
                                    <span class="switch__track" aria-hidden="true"></span>
                                    <span>Názvy sdílení u widgetů</span>
                                </label>
                                <label class="switch">
                                    <input type="checkbox" role="switch" name="confirm_remove" value="1"<?= $form['confirm_remove'] ? ' checked' : '' ?>>
                                    <span class="switch__track" aria-hidden="true"></span>
                                    <span>Potvrdit odebrání widgetu</span>
                                </label>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($canPersonal): ?>
                    <section class="card" id="ucty" aria-labelledby="ucty-title">
                        <header class="card__head">
                            <div>
                                <h2 id="ucty-title">Připojené účty a osobní údaje</h2>
                                <p class="card__lead">Účty, ze kterých widgety čerpají vaše data</p>
                            </div>
                        </header>

                        <div class="account">
                            <span class="account__icon"><?= icon('calendar', 20) ?></span>
                            <div class="account__text">
                                <h3>Google Kalendář</h3>
                                <?php if (!empty($user['google_refresh_token'])): ?>
                                    <p>Připojeno<?= !empty($user['google_email']) ? ': ' . e($user['google_email']) : '' ?> · widget Dnešní program</p>
                                <?php elseif ($googleConfigured): ?>
                                    <p>Stav: nepřipojeno. Aplikace žádá pouze o čtení kalendáře.</p>
                                <?php else: ?>
                                    <p>Na tomto serveru zatím není nastaven OAuth klient Google (soubor <code>.env</code>). Widget ukazuje ukázková data.</p>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($user['google_refresh_token'])): ?>
                                <button type="submit" class="btn" form="google-disconnect-form">Odpojit</button>
                            <?php else: ?>
                                <button type="submit" class="btn" form="google-connect-form"<?= $googleConfigured ? '' : ' disabled' ?>>Připojit účet Google</button>
                            <?php endif; ?>
                        </div>

                        <div class="account account--field">
                            <span class="account__icon"><?= icon('news', 20) ?></span>
                            <div class="account__text">
                                <h3><label for="rss_url">Sportovní novinky – RSS zdroj</label></h3>
                                <input type="url" id="rss_url" name="rss_url" value="<?= e($form['rss_url']) ?>" maxlength="300"
                                       placeholder="<?= e($defaultRss) ?>" inputmode="url" aria-describedby="rss_url-hint<?= isset($errors['rss_url']) ? ' rss_url-error' : '' ?>"<?= isset($errors['rss_url']) ? ' aria-invalid="true"' : '' ?>>
                                <p class="field__hint" id="rss_url-hint">Prázdné pole = výchozí zdroj domácnosti.</p>
                                <?= $error('rss_url') ?>
                            </div>
                        </div>

                        <div class="settings-grid settings-grid--personal">
                            <div class="field">
                                <label for="display_name">Zobrazované jméno</label>
                                <input type="text" id="display_name" name="display_name" value="<?= e($form['display_name']) ?>" maxlength="40" autocomplete="given-name" required<?= $invalid('display_name') ?>>
                                <?= $error('display_name') ?>
                            </div>
                            <div class="field">
                                <label for="origin">Doprava autem vychází z místa</label>
                                <select id="origin" name="origin">
                                    <option value="home"<?= $form['origin'] === 'home' ? ' selected' : '' ?>>Domov (<?= e($homeName) ?>)</option>
                                    <?php foreach ($form['destinations'] as $destination): ?>
                                        <?php if ($destination['id'] > 0): ?>
                                            <option value="<?= (int) $destination['id'] ?>"<?= $form['origin'] === (string) $destination['id'] ? ' selected' : '' ?>><?= e($destination['label']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="settings-grid__col--switches">
                                <label class="switch">
                                    <input type="checkbox" role="switch" name="share_location" value="1"<?= $form['share_location'] ? ' checked' : '' ?>>
                                    <span class="switch__track" aria-hidden="true"></span>
                                    <span>Sdílet mou polohu s domácností</span>
                                </label>
                            </div>
                        </div>
                        <p class="card__footnote"><?= icon('lock', 15) ?> Heslo: <a href="<?= e(url('/zmena-hesla')) ?>">změnit heslo k účtu</a></p>
                    </section>

                    <section class="card" id="cesty" aria-labelledby="cesty-title">
                        <header class="card__head">
                            <div>
                                <h2 id="cesty-title">Cíle cest a oblíbené zastávky</h2>
                                <p class="card__lead">Widgety Doprava autem a Odjezdy MHD</p>
                            </div>
                        </header>
                        <div class="routes-settings">
                            <fieldset class="plain-fieldset">
                                <legend>Cíle cest autem (max. <?= Users::MAX_DESTINATIONS ?>)</legend>
                                <div id="dest-rows">
                                    <?php foreach ($destinations as $index => $destination): ?>
                                        <div class="dest-row" data-dest-row>
                                            <input type="hidden" name="dest[<?= $index ?>][id]" value="<?= (int) $destination['id'] ?>">
                                            <label class="visually-hidden" for="dest-label-<?= $index ?>">Název <?= $index + 1 ?>. cíle</label>
                                            <input type="text" id="dest-label-<?= $index ?>" name="dest[<?= $index ?>][label]" value="<?= e($destination['label']) ?>" maxlength="30" placeholder="Název, např. Škola">
                                            <label class="visually-hidden" for="dest-address-<?= $index ?>">Adresa <?= $index + 1 ?>. cíle</label>
                                            <input type="text" id="dest-address-<?= $index ?>" name="dest[<?= $index ?>][address]" value="<?= e($destination['address']) ?>" maxlength="120" placeholder="Ulice 123, Praha" autocomplete="off">
                                            <button type="button" class="btn btn--small" data-dest-remove hidden>Odebrat</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?= $error('destinations') ?>
                                <button type="button" class="btn btn--small" id="dest-add" hidden><?= icon('plus', 14) ?><span>Přidat cíl cesty</span></button>
                            </fieldset>

                            <fieldset class="plain-fieldset">
                                <legend>Oblíbené zastávky MHD (max. <?= Users::MAX_STOPS ?>)</legend>
                                <div class="stop-add">
                                    <label class="visually-hidden" for="stop-input">Název zastávky</label>
                                    <input type="text" id="stop-input" name="stops[]" list="stop-suggestions" maxlength="60" placeholder="Název zastávky…" autocomplete="off" aria-describedby="stop-hint">
                                    <button type="button" class="btn btn--small" id="stop-add" hidden>Přidat</button>
                                </div>
                                <datalist id="stop-suggestions">
                                    <?php foreach ($popularStops as $stop): ?><option value="<?= e($stop) ?>"></option><?php endforeach; ?>
                                </datalist>
                                <p class="field__hint" id="stop-hint">Zadejte přesný název zastávky PID včetně diakritiky.</p>
                                <ul class="chips" id="stop-list" aria-label="Vybrané zastávky">
                                    <?php foreach ($form['stops'] as $stop): ?>
                                        <li class="chip">
                                            <input type="hidden" name="stops[]" value="<?= e($stop) ?>">
                                            <span><?= e($stop) ?></span>
                                            <button type="button" class="chip__remove" data-chip-remove aria-label="Odebrat zastávku <?= e($stop) ?>" hidden><?= icon('x', 13) ?></button>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?= $error('stops') ?>
                            </fieldset>
                        </div>
                    </section>
                <?php endif; ?>

                <div class="form__actions form__actions--sticky">
                    <a class="btn" href="<?= e(url('/nastaveni')) ?>">Zrušit</a>
                    <button type="submit" class="btn btn--primary">Uložit změny</button>
                </div>
            </form>

            <?php if ($canPersonal): ?>
                <?php // Samostatné formuláře pro Google – tlačítka na ně odkazují atributem form="…". ?>
                <form method="post" action="<?= e(url('/nastaveni/google/pripojit')) ?>" id="google-connect-form" hidden><?= csrf_field() ?></form>
                <form method="post" action="<?= e(url('/nastaveni/google/odpojit')) ?>" id="google-disconnect-form" hidden><?= csrf_field() ?></form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
