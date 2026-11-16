<?php
/**
 * Návod: Odjezdy MHD z Golemio API. Hlavní klíčové slovo: „golemio“.
 * Technické údaje odpovídají dokumentaci https://api.golemio.cz/pid/docs/openapi/
 * a tomu, jak API volá DayBoard\Services\TransitService.
 *
 * @var array<string,string> $page
 * @var string $path
 */
ob_start();
?>
<h2 id="co-je-golemio">Co je Golemio</h2>
<p>Golemio shromažďuje městská data – dopravu, parkování, odpady nebo kvalitu ovzduší – a zpřístupňuje je vývojářům přes veřejné rozhraní (API). Pro rodinnou nástěnku je nejzajímavější část o Pražské integrované dopravě: odjezdové tabule všech zastávek v Praze a Středočeském kraji včetně aktuálního zpoždění.</p>

<h2 id="klic">1. Získejte API klíč</h2>
<p>Golemio API je zdarma, ale vyžaduje registraci. Na adrese <a href="https://api.golemio.cz/api-keys/" rel="noopener">api.golemio.cz/api-keys</a> si založte účet a vygenerujte klíč (token). Posílá se s každým dotazem v hlavičce <code>X-Access-Token</code>.</p>

<h2 id="dotaz">2. Zeptejte se na odjezdy ze zastávky</h2>
<p>Odjezdové tabule vrací adresa <code>/v2/pid/departureboards</code>. Zastávku určíte přesným názvem v parametru <code>names[]</code> – záleží na velkých písmenech i diakritice. Parametr můžete zopakovat a zeptat se na více zastávek najednou.</p>
<pre><code>curl -G "https://api.golemio.cz/v2/pid/departureboards" \
  -H "X-Access-Token: VÁŠ_KLÍČ" \
  --data-urlencode "names[]=Anděl" \
  --data-urlencode "minutesAfter=60" \
  --data-urlencode "limit=5"</code></pre>
<ul>
    <li><code>names[]</code> – název zastávky; místo něj lze použít i identifikátory <code>ids[]</code>.</li>
    <li><code>minutesAfter</code> – jak daleko dopředu se dívat (v minutách).</li>
    <li><code>limit</code> – nejvyšší počet vrácených spojů.</li>
</ul>

<h2 id="odpoved">3. Přečtěte odpověď</h2>
<p>Odpověď je ve formátu JSON. V poli <code>departures</code> najdete jednotlivé spoje, v poli <code>stops</code> zastávky, kterých se týkají. Pro odjezdovou tabuli stačí čtyři údaje:</p>
<div class="table-wrap">
    <table class="table">
        <caption class="visually-hidden">Důležité položky odpovědi Golemio API</caption>
        <thead>
            <tr><th scope="col">Položka</th><th scope="col">Význam</th></tr>
        </thead>
        <tbody>
            <tr><th scope="row"><code>route.short_name</code></th><td>Číslo nebo písmeno linky</td></tr>
            <tr><th scope="row"><code>trip.headsign</code></th><td>Cílová zastávka (směr)</td></tr>
            <tr><th scope="row"><code>departure_timestamp.predicted</code></th><td>Očekávaný čas odjezdu včetně zpoždění</td></tr>
            <tr><th scope="row"><code>delay.minutes</code></th><td>Zpoždění v minutách, pokud je známé</td></tr>
        </tbody>
    </table>
</div>
<p>Počet minut do odjezdu spočítáte jako rozdíl mezi očekávaným časem odjezdu a aktuálním časem.</p>

<h2 id="cache">4. Šetřete limity a klíč nechte na serveru</h2>
<p>Odjezdy se mění každou chvíli, přesto není nutné ptát se při každém načtení stránky. Stačí odpověď na půl minuty uložit a zobrazovat ji všem. A hlavně: klíč nikdy nevkládejte do JavaScriptu v prohlížeči, kde by si ho mohl kdokoli přečíst. Dotaz má posílat server.</p>

<h2 id="dayboard">Odjezdy v DayBoardu bez programování</h2>
<p>Přesně takhle pracuje widget Odjezdy MHD v DayBoardu. Klíč zadáte jednou do souboru <code>.env</code> jako <code>GOLEMIO_API_KEY</code>, každý člen domácnosti si v Osobním nastavení vybere oblíbené zastávky a nástěnka pak ukazuje nejbližší spoje s průběžnou obnovou.</p>
<p>Jak nástěnku zprovoznit, popisuje návod <a href="<?= e(url('/jak-to-funguje')) ?>">jak DayBoard spustit na domácím serveru</a>. Další widgety pro cestu z domu – například dobu jízdy autem – najdete v <a href="<?= e(url('/funkce')) ?>#doprava">přehledu funkcí</a>.</p>
<?php
echo partial('site/_article', [
    'page' => $page,
    'path' => $path,
    'lead' => 'Golemio je datová platforma hlavního města Prahy. Přes její veřejné API získáte mimo jiné aktuální odjezdy pražské MHD – stejná data, jaká svítí na zastávkových tabulích. Ukážeme, jak z Golemio API dostat nejbližší spoje ze své zastávky a zobrazit je na domácí nástěnce.',
    'body' => ob_get_clean(),
]);
