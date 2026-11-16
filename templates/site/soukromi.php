<?php
/**
 * Soukromí a bezpečnost. Stránka nemá ověřené klíčové slovo; slouží hlavně uživatelům.
 * Tabulka oprávnění odpovídá tabulce 2 úvodní studie a skutečnému chování aplikace
 * (viz DayBoard\Core\Auth).
 *
 * @var array<string,string> $page
 */
?>
<section class="page-hero">
    <div class="container">
        <h1><?= e($page['h1']) ?></h1>
        <p class="lead">DayBoard je rodinný kalendář a nástěnka bez cloudu: běží na vašem domácím serveru a tam také zůstávají data vaší domácnosti. Tady je přehledně, co se kde ukládá, co odchází ven a kdo z rodiny co vidí.</p>
    </div>
</section>

<section class="section section--flush">
    <div class="container prose-layout">
        <article class="prose">
            <h2 id="ulozeni">Kde jsou data uložená</h2>
            <p>Účty členů, rozložení nástěnek, nákupní seznam i nastavení jsou v jediném souboru databáze SQLite ve složce <code>storage</code> na vašem serveru. Zálohovat DayBoard proto znamená zkopírovat tuto složku.</p>
            <ul>
                <li>Hesla se neukládají čitelně, jen jako jednosměrný otisk.</li>
                <li>Přístupové tokeny ke Google Kalendáři jsou v databázi zašifrované.</li>
                <li>Veřejné stránky tohoto webu nepoužívají cookies ani měřicí skripty. Cookie vzniká až při přihlášení a slouží jen k němu.</li>
            </ul>

            <h2 id="sluzby">Které služby DayBoard kontaktuje</h2>
            <p>Widgety potřebují data zvenčí. Dotazy posílá váš server a vždy jen to, co je pro daný widget nutné:</p>
            <div class="table-wrap">
                <table class="table">
                    <caption class="visually-hidden">Externí služby a údaje, které se jim posílají</caption>
                    <thead>
                        <tr>
                            <th scope="col">Widget</th>
                            <th scope="col">Služba</th>
                            <th scope="col">Co se odesílá</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><th scope="row">Počasí</th><td>Open-Meteo</td><td>Souřadnice obce domácnosti</td></tr>
                        <tr><th scope="row">Svátek</th><td>svatkyapi.cz</td><td>Dnešní datum</td></tr>
                        <tr><th scope="row">Dnešní program</th><td>Google Kalendář</td><td>Přihlášení člena; aplikace kalendář jen čte</td></tr>
                        <tr><th scope="row">Doprava autem</th><td>TomTom</td><td>Adresy a souřadnice domova a cílů cest</td></tr>
                        <tr><th scope="row">Odjezdy MHD</th><td>Golemio</td><td>Názvy oblíbených zastávek</td></tr>
                        <tr><th scope="row">Sportovní novinky</th><td>Nastavený RSS zdroj</td><td>Jen stažení veřejného zdroje</td></tr>
                        <tr><th scope="row">Rádio</th><td>Radio Browser</td><td>Hledaný název stanice (při přidávání)</td></tr>
                    </tbody>
                </table>
            </div>
            <p>Tři věci si stahuje přímo váš prohlížeč, takže jejich provozovatel uvidí vaši IP adresu: mapové podklady OpenStreetMap ve widgetu Poloha členů, náhledové obrázky u novinek a zvukový stream rádia, které si pustíte.</p>

            <h2 id="role">Role v domácnosti</h2>
            <p>Každý člen se přihlašuje vlastním účtem. Účty zakládá správce – veřejná registrace neexistuje. Role určuje, co člen smí:</p>
            <div class="table-wrap">
                <table class="table">
                    <caption class="visually-hidden">Oprávnění jednotlivých rolí</caption>
                    <thead>
                        <tr>
                            <th scope="col">Oblast</th>
                            <th scope="col">Správce domácnosti</th>
                            <th scope="col">Člen</th>
                            <th scope="col">Host / dítě</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><th scope="row">Zobrazení nástěnky</th><td>Vlastní nástěnka</td><td>Vlastní nástěnka</td><td>Nástěnka vybraná správcem</td></tr>
                        <tr><th scope="row">Výběr widgetů</th><td>Všechny dostupné</td><td>Z widgetů povolených správcem</td><td>Bez možnosti výběru</td></tr>
                        <tr><th scope="row">Úprava rozložení</th><td>Ano</td><td>Ano</td><td>Ne</td></tr>
                        <tr><th scope="row">Osobní nastavení</th><td>Ano</td><td>Ano</td><td>Omezené podle správce</td></tr>
                        <tr><th scope="row">Správa členů a rolí</th><td>Ano</td><td>Ne</td><td>Ne</td></tr>
                        <tr><th scope="row">Povolení widgetů a sdílená nastavení</th><td>Ano</td><td>Ne</td><td>Ne</td></tr>
                        <tr><th scope="row">Sdílený nákupní seznam</th><td>Přidávat a mazat položky</td><td>Přidávat a mazat položky</td><td>Pouze přidávat</td></tr>
                    </tbody>
                </table>
            </div>

            <h2 id="kdo-vidi">Kdo z rodiny vidí jaká data</h2>
            <ul>
                <li><strong>Osobní widgety</strong> – Dnešní program, Doprava autem a Odjezdy MHD – ukazují data jen přihlášenému členovi. Kalendář jednoho člena se ostatním nezobrazuje.</li>
                <li><strong>Sdílené widgety</strong> – Počasí, Svátek, Nákupní seznam, Rádio a Poloha členů – vidí všichni, komu je správce povolil.</li>
                <li><strong>Poloha</strong> – každý člen může sdílení své polohy vypnout v Osobním nastavení. V této verzi projektu je poloha simulovaná a z telefonů se nic nesbírá.</li>
            </ul>

            <h2 id="prihlaseni">Zabezpečení přihlášení</h2>
            <ul>
                <li>Po několika chybných pokusech se přihlášení k účtu na deset minut zablokuje.</li>
                <li>Formuláře jsou chráněné proti podvrženým požadavkům (CSRF) a přihlašovací cookie není dostupná skriptům.</li>
                <li>Oprávnění se ověřují na serveru při každé akci, ne jen skrytím tlačítka v prohlížeči.</li>
                <li>Po změně hesla se účet odhlásí na ostatních zařízeních.</li>
            </ul>
            <p>Bezpečnost celého řešení závisí i na tom, jak server provozujete. Doporučení najdete v návodu <a href="<?= e(url('/jak-to-funguje')) ?>#internet">jak DayBoard spustit na domácím serveru</a>.</p>
        </article>

        <aside class="aside-card" aria-labelledby="aside-title">
            <h2 id="aside-title">Shrnutí</h2>
            <ul class="check-list">
                <li><?= icon('check', 18) ?> Data domácnosti na vašem serveru</li>
                <li><?= icon('check', 18) ?> Bez veřejné registrace</li>
                <li><?= icon('check', 18) ?> Tři role s jasnými právy</li>
                <li><?= icon('check', 18) ?> Veřejný web bez cookies</li>
            </ul>
            <p><a href="<?= e(url('/caste-dotazy')) ?>">Časté dotazy k rodinné nástěnce</a></p>
        </aside>
    </div>
</section>
