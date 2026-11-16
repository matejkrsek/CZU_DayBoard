<?php
/**
 * Návod: Ranní rutina s dětmi. Hlavní klíčové slovo: „ranní rutina“.
 *
 * @var array<string,string> $page
 * @var string $path
 */
ob_start();
?>
<h2 id="vecer">1. Začněte už večer</h2>
<p>Klidné ráno se připravuje den předem. Večer stačí deset minut:</p>
<ul>
    <li>nachystat oblečení podle zítřejší předpovědi počasí,</li>
    <li>sbalit aktovky a připravit, co jde, na svačiny,</li>
    <li>podívat se společně, co koho zítra čeká – kroužek, písemka, návštěva lékaře.</li>
</ul>
<p>Čím méně rozhodnutí zbude na ráno, tím méně prostoru pro dohady.</p>

<h2 id="poradi">2. Držte každý den stejné pořadí</h2>
<p>Dětem pomáhá, když ráno probíhá pořád stejně: vstát, obléknout se, nasnídat, vyčistit zuby, obout se. Menším dětem poslouží obrázkový seznam na lednici, větším stačí krátký soupis. Rodič pak nemusí každý krok připomínat – stačí se zeptat, co je další na řadě.</p>

<h2 id="rezerva">3. Počítejte s rezervou</h2>
<p>Většina ranního stresu vzniká z pocitu, že nestíháme. Deset až patnáct minut rezervy pokryje hledání čepice i rozlitý čaj. Vyplatí se také vědět předem, jak to vypadá s dopravou: zácpa na obvyklé trase nebo zpoždění tramvaje jsou důvod vyrazit o pár minut dřív, ne až důvod k běhu na zastávku.</p>

<h2 id="ukoly">4. Rozdělte si úkoly</h2>
<p>Kdo dnes vede děti do školy? Kdo je vyzvedává a kdo nakoupí? Když to rodina řeší až mezi dveřmi, něco se snadno zapomene. Pomáhá sdílený kalendář, kam si rodiče píšou odvozy a kroužky, a jeden společný nákupní seznam, kam může připsat kdokoli.</p>

<h2 id="jedno-misto">5. Mějte informace na jednom místě</h2>
<p>Ráno není čas otevírat pět aplikací. Počasí, program dne, odjezdy a nákupní seznam mají být vidět na první pohled – třeba na tabletu v kuchyni nebo v předsíni, kolem kterého všichni projdou.</p>

<h2 id="dayboard">Jak s ranní rutinou pomáhá DayBoard</h2>
<p>DayBoard je rodinná nástěnka určená právě pro začátek dne. Na jedné obrazovce ukáže:</p>
<ul>
    <li><strong>počasí</strong> – aby bylo jasné, jestli vzít bundu a deštník,</li>
    <li><strong>dnešní program</strong> z Google Kalendáře každého člena rodiny,</li>
    <li><strong>dopravu</strong> – dobu jízdy autem do školy a práce a nejbližší odjezdy MHD,</li>
    <li><strong>sdílený nákupní seznam</strong>, do kterého mohou připisovat i děti.</li>
</ul>
<p>Děti mají vlastní zjednodušený účet: vidí jen widgety, které jim rodiče vyberou, a nic si omylem nepřenastaví. Prohlédněte si <a href="<?= e(url('/funkce')) ?>">všechny widgety rodinné nástěnky</a> nebo si přečtěte, <a href="<?= e(url('/jak-to-funguje')) ?>">jak DayBoard doma zprovoznit</a>.</p>
<?php
echo partial('site/_article', [
    'page' => $page,
    'path' => $path,
    'lead' => 'Ranní rutina s dětmi nemusí být každodenní závod s časem. Pomáhá pár jednoduchých zvyků: připravit co nejvíc večer, držet ráno stejné pořadí kroků a nehledat informace na pěti místech. Sepsali jsme postup, který si snadno přizpůsobíte vlastní rodině.',
    'body' => ob_get_clean(),
]);
