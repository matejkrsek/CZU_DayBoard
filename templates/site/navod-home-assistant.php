<?php
/**
 * Návod: Home Assistant dashboard, nebo DayBoard?
 * Hlavní klíčové slovo: „home assistant dashboard“; vedlejší: „dashboard home assistant“,
 * „home assistant dashboard examples“.
 *
 * @var array<string,string> $page
 * @var string $path
 */
ob_start();
?>
<h2 id="co-umi">Co umí dashboard v Home Assistantu</h2>
<p>Home Assistant je otevřená platforma pro chytrou domácnost. Jeho dashboard se skládá z karet: jedna zapíná světla, další ukazuje teplotu v obýváku, obraz z kamery nebo spotřebu elektřiny. Karty lze poskládat v editoru přímo v prohlížeči, pokročilejší úpravy se píšou v konfiguračním jazyce YAML.</p>
<p>Dashboard Home Assistant umí zobrazit i věci, které s ovládáním domu nesouvisejí – počasí, kalendář nebo nákupní seznam. Podmínkou ale je běžící Home Assistant a nastavené integrace.</p>

<h2 id="priklady">Příklady: co lidé na dashboard dávají</h2>
<p>Když hledáte inspiraci („home assistant dashboard examples“), nejčastěji narazíte na tyto sestavy:</p>
<ul>
    <li><strong>Ovládací panel místností</strong> – světla, žaluzie, topení a zásuvky po jednotlivých pokojích.</li>
    <li><strong>Přehled domu</strong> – teploty, vlhkost, otevřená okna, stav alarmu a kamer.</li>
    <li><strong>Energie</strong> – spotřeba, výroba fotovoltaiky a nabíjení auta.</li>
    <li><strong>Panel na zeď</strong> – velké hodiny, počasí, kalendář a pár nejpoužívanějších tlačítek pro tablet v předsíni.</li>
</ul>
<p>Poslední příklad se rodinné nástěnce blíží nejvíc. Právě u něj se vyplatí zvážit, jestli je Home Assistant ten správný nástroj.</p>

<h2 id="kdy-dayboard">Kdy je lepší volbou DayBoard</h2>
<p>DayBoard neřídí žádná zařízení. Dělá jedinou věc: ráno ukáže každému členu rodiny, co ho ten den čeká. Hodí se, když:</p>
<ul>
    <li><strong>nemáte chytrou domácnost</strong> a nechcete ji kvůli kalendáři na zdi budovat,</li>
    <li><strong>nástěnku budou používat i děti a prarodiče</strong> – přihlásí se a vidí svůj den, nic se nedá „rozbít“,</li>
    <li><strong>potřebujete české zdroje</strong> – odjezdy Pražské integrované dopravy, jmeniny a státní svátky jsou připravené jako hotové widgety,</li>
    <li><strong>chcete jasné role</strong> – správce, člen a host nebo dítě s předem danými právy.</li>
</ul>

<h2 id="srovnani">Srovnání v kostce</h2>
<div class="table-wrap">
    <table class="table">
        <caption class="visually-hidden">Srovnání dashboardu Home Assistant a DayBoardu</caption>
        <thead>
            <tr>
                <th scope="col">Hledisko</th>
                <th scope="col">Home Assistant dashboard</th>
                <th scope="col">DayBoard</th>
            </tr>
        </thead>
        <tbody>
            <tr><th scope="row">Hlavní účel</th><td>Ovládání a přehled chytré domácnosti</td><td>Ranní přehled dne pro rodinu</td></tr>
            <tr><th scope="row">Co potřebujete</th><td>Běžící Home Assistant a integrace</td><td>PHP a soubor SQLite</td></tr>
            <tr><th scope="row">Nastavení</th><td>Velmi pružné, pro pokročilé YAML</td><td>Hotové widgety, přetažení myší</td></tr>
            <tr><th scope="row">Ovládání zařízení</th><td>Ano, to je jeho hlavní síla</td><td>Ne</td></tr>
            <tr><th scope="row">České zdroje</th><td>Přes doplňky a vlastní nastavení</td><td>Odjezdy PID, jmeniny a svátky v základu</td></tr>
        </tbody>
    </table>
</div>

<h2 id="dohromady">Jde to i dohromady</h2>
<p>Volba nemusí být buď, anebo. Na tabletu na zdi klidně používejte obojí: Home Assistant pro ovládání domu a DayBoard jako ranní přehled. Stačí mít obě stránky otevřené v prohlížeči a přepínat mezi nimi.</p>
<p>Chcete DayBoard vyzkoušet? Podívejte se na <a href="<?= e(url('/funkce')) ?>">widgety rodinné nástěnky</a> nebo rovnou na návod, <a href="<?= e(url('/jak-to-funguje')) ?>">jak DayBoard spustit na domácím serveru</a>.</p>
<?php
echo partial('site/_article', [
    'page' => $page,
    'path' => $path,
    'lead' => 'Home Assistant dashboard je skvělý nástroj pro ovládání chytré domácnosti. Pokud ale hledáte hlavně ranní přehled pro celou rodinu – kdo kam jede, co koupit a jak bude venku –, může být jednodušší cestou samostatná rodinná nástěnka. Porovnali jsme oba přístupy.',
    'body' => ob_get_clean(),
]);
