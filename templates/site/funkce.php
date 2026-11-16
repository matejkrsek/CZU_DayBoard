<?php
/**
 * Funkce a widgety. Hlavní klíčové slovo: „sdílený rodinný kalendář“,
 * vedlejší: „sdílený nákupní seznam“.
 *
 * @var array<string,string> $page
 */

use DayBoard\Widgets\WidgetRegistry;
?>
<section class="page-hero">
    <div class="container">
        <h1><?= e($page['h1']) ?></h1>
        <p class="lead">DayBoard spojuje sdílený rodinný kalendář, dopravu, sdílený nákupní seznam a další denní informace do jedné nástěnky. Tvoří ji widgety – každý ukazuje jednu věc a každý člen rodiny si z nich poskládá vlastní přehled dne.</p>
    </div>
</section>

<section class="section section--flush">
    <div class="container prose-layout">
        <article class="prose">
            <h2 id="kalendar">Dnešní program: sdílený rodinný kalendář</h2>
            <?= image_tag('widget-kalendar.webp', 'Widget Dnešní program se šesti událostmi dne od ranního odvozu dětí po rodinnou večeři', ['class' => 'prose__figure']) ?>
            <p>Widget Dnešní program čte události z Google Kalendáře, který si člen sám připojí. Ráno tak všichni vidí, co je čeká: poradu, kroužek, zubaře i společnou večeři. Proběhlé události zešednou a ta, která právě běží, je zvýrazněná.</p>
            <p>Každý člen rodiny má po přihlášení svůj kalendář. Kalendáře tak zůstávají u Googlu, kde je rodina už používá a sdílí – DayBoard z nich jen čte dnešní den a nic do nich nezapisuje.</p>

            <h2 id="doprava">Doprava autem a odjezdy MHD</h2>
            <?= image_tag('widget-odjezdy.webp', 'Widget Odjezdy MHD s nejbližšími spoji z oblíbených zastávek: linka, směr a minuty do odjezdu', ['class' => 'prose__figure']) ?>
            <p>Widget Doprava autem ukazuje aktuální dobu jízdy až do tří přednastavených míst, například do školy, do práce a k babičce. Počítá s aktuálním provozem, takže hned vidíte, jestli je potřeba vyjet dřív.</p>
            <p>Widget Odjezdy MHD vypisuje nejbližší spoje z oblíbených zastávek Pražské integrované dopravy včetně zpoždění. Obnovuje se každou půlminutu, aby se na něj dalo spolehnout i při odchodu z domu.</p>

            <h2 id="nakupni-seznam">Sdílený nákupní seznam</h2>
            <?= image_tag('widget-nakupni-seznam.webp', 'Widget Nákupní seznam s položkami Mléko, Vejce a Jablka a polem pro přidání další položky', ['class' => 'prose__figure']) ?>
            <p>Jeden seznam pro celou domácnost. Kdo zjistí, že dochází mléko, připíše ho – a ostatní to vidí na své nástěnce během chvíle. Koupené položky stačí odškrtnout.</p>
            <p>Děti a hosté mohou do seznamu přidávat, ale mazat položky smí jen dospělí členové. Nikdo tak omylem nesmaže nákup na celý týden.</p>

            <h2 id="pocasi">Počasí, svátek a denní informace</h2>
            <p>Počasí pro obec, kterou nastaví správce domácnosti: aktuální teplota, pocitová teplota, vítr a předpověď na čtyři dny. Widget Svátek doplní datum, jmeniny na dnešek i zítřek, státní svátky, číslo týdne a východ a západ slunce.</p>

            <h2 id="dalsi">Sportovní novinky, rádio a poloha členů</h2>
            <p>Sportovní novinky zobrazují titulky z RSS zdroje – výchozí vybírá správce, každý člen si může nastavit vlastní. Rádio přehraje oblíbené internetové stanice domácnosti jedním kliknutím. Mapa ukazuje sdílenou polohu členů, kteří sdílení povolili; v tomto studentském projektu je poloha simulovaná.</p>

            <h2 id="rozlozeni">Nástěnka podle vás</h2>
            <?= image_tag('uprava-nastenky.webp', 'Režim úprav nástěnky: widgety v mřížce s úchyty pro změnu velikosti a nabídka dostupných widgetů', ['class' => 'prose__figure']) ?>
            <p>Widgety leží v mřížce a mají různou velikost. V režimu úprav je přetáhnete jinam, tahem za roh zvětšíte nebo zmenšíte a křížkem odeberete – podobně jako na domovské obrazovce telefonu. Vše jde ovládat i klávesnicí.</p>
            <p>Které widgety jsou v domácnosti k dispozici, určuje správce. Kdo co smí, popisuje stránka <a href="<?= e(url('/soukromi')) ?>">soukromí a role v domácnosti</a>.</p>

            <h2 id="prehled">Přehled widgetů</h2>
            <div class="table-wrap">
                <table class="table">
                    <caption class="visually-hidden">Widgety DayBoardu, jejich obsah a typ údajů</caption>
                    <thead>
                        <tr>
                            <th scope="col">Widget</th>
                            <th scope="col">Zobrazovaný obsah</th>
                            <th scope="col">Typ údajů</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (WidgetRegistry::all() as $widget): ?>
                            <tr>
                                <th scope="row"><?= e($widget['title']) ?></th>
                                <td><?= e($widget['summary']) ?></td>
                                <td><?= e(WidgetRegistry::scopeLabel((string) $widget['scope'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="prose__note">Sdílené údaje vidí celá domácnost stejně, osobní patří jen přihlášenému členovi.</p>
        </article>

        <aside class="aside-card" aria-labelledby="aside-title">
            <h2 id="aside-title">Chcete to vyzkoušet?</h2>
            <p>DayBoard běží na vašem vlastním serveru. Instalace zabere pár minut.</p>
            <p><a class="btn btn--primary btn--block" href="<?= e(url('/jak-to-funguje')) ?>">Jak DayBoard nainstalovat</a></p>
            <p><a href="<?= e(url('/caste-dotazy')) ?>">Časté dotazy k rodinné nástěnce</a></p>
        </aside>
    </div>
</section>
