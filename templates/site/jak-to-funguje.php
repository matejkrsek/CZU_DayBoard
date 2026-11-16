<?php
/**
 * Jak to funguje – návod ke spuštění na domácím serveru.
 * Stránka nemá ověřené klíčové slovo; slouží hlavně uživatelům.
 *
 * @var array<string,string> $page
 */

use DayBoard\Database\Seeder;
?>
<section class="page-hero">
    <div class="container">
        <h1><?= e($page['h1']) ?></h1>
        <p class="lead">DayBoard je dashboard na domácí server: běží u vás doma a celá rodina ho otevírá v prohlížeči. Tento návod vás krok za krokem provede od stažení po první poskládanou nástěnku.</p>
    </div>
</section>

<section class="section section--flush">
    <div class="container prose-layout">
        <article class="prose">
            <h2 id="pozadavky">1. Co budete potřebovat</h2>
            <ul>
                <li>Zařízení, které doma běží nepřetržitě: domácí server, NAS, Raspberry Pi nebo starší počítač.</li>
                <li>PHP 8.1 nebo novější s rozšířeními <code>pdo_sqlite</code>, <code>mbstring</code>, <code>openssl</code>, <code>simplexml</code> a <code>curl</code>. Alternativně stačí Docker.</li>
                <li>Připojení k internetu – widgety si stahují počasí, odjezdy a další data.</li>
            </ul>
            <p>Databázový server instalovat nemusíte. DayBoard si při prvním spuštění sám vytvoří soubor s databází SQLite.</p>

            <h2 id="stazeni">2. Stáhněte DayBoard</h2>
            <pre><code>git clone https://github.com/matejkrsek/CZU_DayBoard.git
cd CZU_DayBoard</code></pre>

            <h2 id="spusteni">3. Spusťte ho</h2>
            <h3>Varianta A: vestavěný server PHP</h3>
            <p>Nejrychlejší cesta pro vyzkoušení i pro malou domácí síť:</p>
            <pre><code>php -S 0.0.0.0:8000 -t public public/index.php</code></pre>
            <p>Nástěnku pak otevřete na adrese <code>http://ADRESA-SERVERU:8000</code> z kteréhokoli zařízení v domácí síti.</p>

            <h3>Varianta B: Docker</h3>
            <pre><code>docker compose up -d</code></pre>
            <p>DayBoard poběží na portu 8080 a data zůstanou ve složce <code>storage</code>.</p>

            <h3>Varianta C: Apache</h3>
            <p>Složku projektu zkopírujte do adresáře webového serveru (například <code>htdocs</code> v XAMPPu) a zapněte modul <code>mod_rewrite</code>. Přiložené soubory <code>.htaccess</code> se postarají o hezké adresy i o to, aby z webu nebyla dostupná databáze ani konfigurace.</p>

            <h2 id="prihlaseni">4. Přihlaste se a změňte hesla</h2>
            <p>Nová instalace obsahuje ukázkovou domácnost se čtyřmi účty. Jako správce se přihlásíte jménem <code><?= e(Seeder::DEMO_ACCOUNTS[0][0]) ?></code> a heslem <code><?= e(Seeder::DEMO_PASSWORD) ?></code>.</p>
            <p>Než nástěnku začnete doopravdy používat, změňte hesla všech účtů a v souboru <code>.env</code> nastavte <code>DEMO_MODE=false</code>. Zmizí tím ukázková tlačítka na přihlašovací stránce a widgety přestanou zobrazovat ukázková data.</p>

            <h2 id="clenove">5. Přidejte členy domácnosti a nastavte role</h2>
            <p>Ve Správě domácnosti založte účet každému členovi rodiny a zvolte mu roli: správce, člen, nebo host / dítě. Členovi nastavíte dočasné heslo, které si při prvním přihlášení změní. Co která role smí, shrnuje stránka <a href="<?= e(url('/soukromi')) ?>">soukromí a bezpečnost</a>.</p>

            <h2 id="widgety">6. Vyberte widgety a lokalitu</h2>
            <p>Správce určí, které widgety smí používat členové a které uvidí děti. Nastaví také obec domácnosti – podle ní se řídí předpověď počasí a výchozí bod cest.</p>

            <h2 id="ucty">7. Připojte Google Kalendář a doplňte API klíče</h2>
            <p>Počasí, svátek, novinky a rádio fungují hned. Tři widgety potřebují vlastní klíč, který zadáte do souboru <code>.env</code> (vzor je v souboru <code>.env.example</code>):</p>
            <ul>
                <li><strong>Dnešní program</strong> – OAuth klient v Google Cloud Console (<code>GOOGLE_CLIENT_ID</code>, <code>GOOGLE_CLIENT_SECRET</code>). Každý člen pak účet Google připojí v Osobním nastavení.</li>
                <li><strong>Doprava autem</strong> – klíč TomTom (<code>TOMTOM_API_KEY</code>).</li>
                <li><strong>Odjezdy MHD</strong> – klíč Golemio (<code>GOLEMIO_API_KEY</code>). Postup popisuje návod <a href="<?= e(url('/navody/golemio-odjezdy')) ?>">odjezdy MHD z Golemio API</a>.</li>
            </ul>

            <h2 id="nastenka">8. Poskládejte si nástěnku</h2>
            <p>Na nástěnce klikněte na „Upravit nástěnku“. Widgety přetáhněte na místo, tahem za roh upravte velikost a změny uložte. Co všechno lze na nástěnku dát, ukazuje <a href="<?= e(url('/funkce')) ?>">přehled widgetů</a>.</p>

            <h2 id="internet">Přístup z internetu</h2>
            <p>DayBoard je navržený pro domácí síť. Pokud ho chcete otevírat i odjinud, dejte před něj reverzní proxy s HTTPS nebo použijte VPN – přihlašovací údaje by nikdy neměly putovat nešifrovaně.</p>
        </article>

        <aside class="aside-card" aria-labelledby="aside-title">
            <h2 id="aside-title">Něco nefunguje?</h2>
            <p>Odpovědi na nejběžnější potíže najdete v častých dotazech.</p>
            <p><a class="btn btn--block" href="<?= e(url('/caste-dotazy')) ?>">Časté dotazy k rodinné nástěnce</a></p>
            <p><a class="btn btn--primary btn--block" href="<?= e(url('/prihlaseni')) ?>">Přihlásit se do aplikace</a></p>
        </aside>
    </div>
</section>
