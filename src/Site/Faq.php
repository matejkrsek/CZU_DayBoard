<?php

declare(strict_types=1);

namespace DayBoard\Site;

/**
 * Časté dotazy. Jeden zdroj pro stránku /caste-dotazy i pro strukturovaná
 * data FAQPage (JSON-LD), aby se obě místa nerozcházela.
 */
final class Faq
{
    /** @return list<array{question:string,answer:string}> Odpověď je HTML (odstavce, odkazy). */
    public static function items(): array
    {
        $link = static fn (string $path, string $text): string => '<a href="' . e(url($path)) . '">' . e($text) . '</a>';

        return [
            [
                'question' => 'Co DayBoard potřebuje ke spuštění?',
                'answer' => '<p>Stačí počítač, který doma běží nepřetržitě – domácí server, NAS, Raspberry Pi nebo starší notebook – a na něm PHP 8.1 či novější. Data se ukládají do souboru SQLite, takže žádný databázový server instalovat nemusíte. Kdo má raději kontejnery, spustí DayBoard jedním příkazem v Dockeru.</p>'
                    . '<p>Celý postup popisuje návod ' . $link('/jak-to-funguje', 'jak DayBoard spustit na domácím serveru') . '.</p>',
            ],
            [
                'question' => 'Jak dostat Google Kalendář na tablet na zdi?',
                'answer' => '<p>Na tabletu otevřete DayBoard v prohlížeči a přihlaste se. V Osobním nastavení klikněte na „Připojit účet Google“ a povolte čtení kalendáře. Widget Dnešní program pak ukazuje dnešní události a průběžně se obnovuje.</p>'
                    . '<p>Aby nástěnka vypadala jako aplikace, přidejte si stránku na plochu tabletu a zapněte zobrazení na celou obrazovku. Každý člen rodiny vidí po přihlášení svůj vlastní kalendář.</p>',
            ],
            [
                'question' => 'Jak funguje dětský účet?',
                'answer' => '<p>Účet s rolí Host / dítě vidí jen ty widgety, které mu vybere správce domácnosti – typicky počasí, svátek, nákupní seznam a rádio. Rozložení nástěnky měnit nemůže a do sdíleného nákupního seznamu smí položky přidávat, ale ne mazat.</p>'
                    . '<p>Podrobnosti o rolích najdete na stránce ' . $link('/soukromi', 'soukromí a bezpečnost dat') . '.</p>',
            ],
            [
                'question' => 'Čím se DayBoard liší od DAKboardu?',
                'answer' => '<p>DAKboard je hotová služba pro nástěnné displeje provozovaná v cloudu výrobce. DayBoard je jeho menší alternativa (DAKboard alternative) pro ty, kdo chtějí mít rodinnou nástěnku na vlastním serveru: data zůstávají doma, každý člen má vlastní nástěnku a přihlášení s rolí a widgety počítají s českým prostředím – odjezdy Pražské integrované dopravy, české jmeniny a státní svátky.</p>'
                    . '<p>DayBoard je naopak studentský projekt bez placené podpory a s menším výběrem widgetů. Fotoalbum je zatím jen plánované rozšíření.</p>',
            ],
            [
                'question' => 'Kde jsou uložená data naší domácnosti?',
                'answer' => '<p>V jediném souboru databáze SQLite na serveru, na kterém DayBoard provozujete. Nákupní seznam, nastavení ani účty členů se neposílají do žádného cloudu. Ven odcházejí jen dotazy nutné pro widgety, například souřadnice obce pro předpověď počasí.</p>'
                    . '<p>Přesný seznam najdete v přehledu, ' . $link('/soukromi', 'které služby DayBoard kontaktuje') . '.</p>',
            ],
            [
                'question' => 'Funguje DayBoard i mimo Prahu?',
                'answer' => '<p>Ano. Počasí, kalendář, doba jízdy autem, svátek, sportovní novinky, nákupní seznam i rádio fungují kdekoli. Jen widget Odjezdy MHD čerpá z datové platformy Golemio, která pokrývá Pražskou integrovanou dopravu, tedy Prahu a Středočeský kraj.</p>',
            ],
            [
                'question' => 'Kolik DayBoard stojí?',
                'answer' => '<p>Za DayBoard se neplatí – je to studentský projekt, který provozujete na vlastním hardwaru. Některé widgety používají služby třetích stran s vlastními podmínkami a limity: pro Google Kalendář, TomTom a Golemio potřebujete bezplatnou registraci a vlastní klíč. Bez klíčů tyto widgety zobrazují ukázková data.</p>',
            ],
            [
                'question' => 'Dá se DayBoard používat na telefonu?',
                'answer' => '<p>Ano, nástěnka se přizpůsobí šířce displeje. Na počítači a tabletu na zdi jsou widgety v mřížce se čtyřmi sloupci, na telefonu se seřadí pod sebe ve stejném pořadí. Přehled ' . $link('/funkce', 'všech widgetů rodinné nástěnky') . ' najdete na stránce Funkce.</p>',
            ],
            [
                'question' => 'Co když člen rodiny zapomene heslo?',
                'answer' => '<p>Účty zakládá a spravuje správce domácnosti. Ve Správě domácnosti u člena zvolí „Upravit“, nastaví dočasné heslo a může vyžádat jeho změnu při příštím přihlášení. Veřejná registrace ani obnova hesla e-mailem v DayBoardu záměrně nejsou.</p>',
            ],
        ];
    }
}
