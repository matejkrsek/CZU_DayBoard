<?php

declare(strict_types=1);

namespace DayBoard\Site;

/**
 * Veřejné (prezentační) stránky a jejich SEO metadata.
 * Title, meta description a H1 odpovídají tabulce 6 úvodní studie,
 * URL a klíčová slova tabulce 5. Title držíme do 60 znaků, description do 155.
 */
final class Pages
{
    /**
     * nav      – která položka hlavní navigace je aktivní
     * keyword  – hlavní klíčové slovo stránky (jen pro orientaci autorů obsahu)
     * type     – 'article' u návodů (strukturovaná data a drobečková navigace)
     *
     * @return array<string,array<string,string>>
     */
    public static function all(): array
    {
        return [
            '/' => [
                'template' => 'site/home', 'nav' => 'home', 'label' => 'Domů',
                'title' => 'DayBoard – rodinná nástěnka pro začátek dne',
                'description' => 'Počasí, dnešní program, odjezdy MHD a sdílený nákupní seznam na jedné obrazovce. Rodinná nástěnka, kterou provozujete na vlastním domácím serveru.',
                'h1' => 'Rodinná nástěnka pro začátek dne',
                'keyword' => 'rodinná nástěnka',
            ],
            '/funkce' => [
                'template' => 'site/funkce', 'nav' => 'funkce', 'label' => 'Funkce a widgety',
                'title' => 'Sdílený rodinný kalendář a další widgety | DayBoard',
                'description' => 'Google Kalendář, počasí, doba jízdy autem, odjezdy MHD, svátek, sportovní zprávy, nákupní seznam a rádio. Widgety si každý člen rodiny poskládá sám.',
                'h1' => 'Widgety rodinné nástěnky: kalendář, doprava, nákupy',
                'keyword' => 'sdílený rodinný kalendář',
            ],
            '/jak-to-funguje' => [
                'template' => 'site/jak-to-funguje', 'nav' => 'jak', 'label' => 'Jak to funguje',
                'title' => 'Dashboard na domácí server krok za krokem | DayBoard',
                'description' => 'Jak spustit rodinnou nástěnku DayBoard na domácím serveru nebo Raspberry Pi, přidat členy domácnosti, nastavit role a připojit Google Kalendář.',
                'h1' => 'Jak spustit DayBoard na domácím serveru',
                'keyword' => '',
            ],
            '/soukromi' => [
                'template' => 'site/soukromi', 'nav' => 'soukromi', 'label' => 'Soukromí a bezpečnost',
                'title' => 'Rodinný kalendář bez cloudu: soukromí dat | DayBoard',
                'description' => 'Data DayBoardu zůstávají na vašem domácím serveru. Jak fungují role správce, člena a dítěte a kdo z rodiny vidí jaká data.',
                'h1' => 'Soukromí a bezpečnost: data zůstávají doma',
                'keyword' => '',
            ],
            '/caste-dotazy' => [
                'template' => 'site/caste-dotazy', 'nav' => 'faq', 'label' => 'Časté dotazy',
                'title' => 'Časté dotazy k rodinné nástěnce DayBoard',
                'description' => 'Co DayBoard potřebuje ke spuštění, jak dostat Google Kalendář na tablet na zdi, jak funguje dětský účet a čím se liší od DAKboardu.',
                'h1' => 'Časté dotazy k rodinné nástěnce',
                'keyword' => 'dakboard alternative',
            ],
            '/navody' => [
                'template' => 'site/navody', 'nav' => 'navody', 'label' => 'Návody',
                'title' => 'Návody k rodinné nástěnce a domácímu serveru | DayBoard',
                'description' => 'Návody k příbuzným tématům: Home Assistant dashboard, odjezdy MHD z Golemio API a ranní rutina s dětmi. Prakticky a krok za krokem.',
                'h1' => 'Návody',
                'keyword' => '',
            ],
            '/navody/home-assistant-dashboard' => [
                'template' => 'site/navod-home-assistant', 'nav' => 'navody', 'type' => 'article',
                'label' => 'Home Assistant dashboard, nebo DayBoard?',
                'title' => 'Home Assistant dashboard, nebo DayBoard? | DayBoard',
                'description' => 'Kdy se pro rodinný přehled dne hodí Home Assistant dashboard a kdy jednodušší DayBoard. Srovnání, příklady karet a možnost používat obojí.',
                'h1' => 'Home Assistant dashboard, nebo DayBoard?',
                'keyword' => 'home assistant dashboard',
                'teaser' => 'Kdy stačí dashboard v Home Assistantu a kdy se vyplatí samostatná rodinná nástěnka.',
                'image' => 'navod-home-assistant.svg',
                'imageAlt' => 'Schéma: panel chytré domácnosti s vypínači vedle rodinné nástěnky s kalendářem a nákupním seznamem',
                'published' => '2026-11-10',
            ],
            '/navody/golemio-odjezdy' => [
                'template' => 'site/navod-golemio', 'nav' => 'navody', 'type' => 'article',
                'label' => 'Odjezdy MHD z Golemio API',
                'title' => 'Golemio API: odjezdy MHD na vlastní nástěnce | DayBoard',
                'description' => 'Jak z Golemio API získat nejbližší odjezdy pražské MHD: registrace klíče, dotaz na odjezdovou tabuli zastávky a zobrazení na domácí nástěnce.',
                'h1' => 'Odjezdy MHD z Golemio API',
                'keyword' => 'golemio',
                'teaser' => 'Od registrace API klíče po odjezdovou tabuli oblíbené zastávky na tabletu v předsíni.',
                'image' => 'navod-golemio.svg',
                'imageAlt' => 'Odjezdová tabule se třemi nejbližšími spoji: linka, směr a počet minut do odjezdu',
                'published' => '2026-11-10',
            ],
            '/navody/ranni-rutina' => [
                'template' => 'site/navod-ranni-rutina', 'nav' => 'navody', 'type' => 'article',
                'label' => 'Ranní rutina s dětmi',
                'title' => 'Ranní rutina s dětmi: jak na klidnější rána | DayBoard',
                'description' => 'Ranní rutina s dětmi bez shonu: co připravit večer, jak rozdělit úkoly a proč pomáhá mít počasí, program a odjezdy na jedné obrazovce.',
                'h1' => 'Ranní rutina s dětmi',
                'keyword' => 'ranní rutina',
                'teaser' => 'Pět kroků ke klidnějším ránům – a co z toho může ohlídat nástěnka v kuchyni.',
                'image' => 'navod-ranni-rutina.svg',
                'imageAlt' => 'Ilustrace rána: vycházející slunce, hodiny a seznam úkolů před odchodem z domu',
                'published' => '2026-11-10',
            ],
        ];
    }

    public static function get(string $path): ?array
    {
        return self::all()[$path] ?? null;
    }

    /** @return array<string,array<string,string>> Návody (články) v pořadí, v jakém se vypisují. */
    public static function guides(): array
    {
        return array_filter(self::all(), static fn (array $page): bool => ($page['type'] ?? '') === 'article');
    }

    /** Položky hlavní navigace veřejné části. @return array<string,array<string,string>> */
    public static function navigation(): array
    {
        return array_filter(self::all(), static fn (array $page): bool => !isset($page['type']));
    }
}
