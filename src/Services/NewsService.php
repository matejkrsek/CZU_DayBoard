<?php

declare(strict_types=1);

namespace DayBoard\Services;

use DayBoard\Core\Cache;
use DayBoard\Core\Http;
use DayBoard\Models\Household;

/** Sportovní novinky z RSS nebo Atom zdroje (osobního, jinak výchozího pro domácnost). */
final class NewsService
{
    /** @return array{source:string,items:list<array<string,mixed>>} */
    public static function forUser(array $user): array
    {
        $url = trim((string) ($user['rss_url'] ?? '')) ?: Household::get('default_rss_url');
        if ($url === '') {
            throw new \RuntimeException('Není nastaven žádný RSS zdroj.');
        }
        return Cache::remember('rss:' . $url, 600, static fn (): array => self::fetch($url));
    }

    /** Ověří, že adresa je použitelný RSS zdroj. Vrací text chyby, nebo null. */
    public static function validate(string $url): ?string
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false || preg_match('~^https?://~i', $url) !== 1) {
            return 'Adresa RSS zdroje musí začínat http:// nebo https://.';
        }
        try {
            $feed = self::fetch($url);
            return $feed['items'] === [] ? 'Na zadané adrese není RSS zdroj s články.' : null;
        } catch (\Throwable $e) {
            return 'RSS zdroj se nepodařilo načíst: ' . $e->getMessage();
        }
    }

    /** @return array{source:string,items:list<array<string,mixed>>} */
    private static function fetch(string $url): array
    {
        // Adresu zadává uživatel, proto povolujeme jen veřejné servery (ne domácí síť).
        $xml = Http::get($url, ['Accept' => 'application/rss+xml, application/atom+xml, application/xml, text/xml'], 6, true);

        $previous = libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($feed === false) {
            throw new \RuntimeException('Zdroj není platné XML.');
        }

        $items = [];
        if (isset($feed->channel->item)) {                       // RSS 2.0
            $source = (string) $feed->channel->title;
            foreach ($feed->channel->item as $item) {
                $items[] = self::item((string) $item->title, (string) $item->link, (string) $item->pubDate, self::image($item));
            }
        } elseif (isset($feed->entry)) {                         // Atom
            $source = (string) $feed->title;
            foreach ($feed->entry as $entry) {
                $link = '';
                foreach ($entry->link as $candidate) {
                    if ((string) ($candidate['rel'] ?? 'alternate') === 'alternate') {
                        $link = (string) $candidate['href'];
                        break;
                    }
                }
                $items[] = self::item((string) $entry->title, $link, (string) ($entry->updated ?: $entry->published), self::image($entry));
            }
        } else {
            throw new \RuntimeException('Zdroj není ve formátu RSS ani Atom.');
        }

        $items = array_values(array_filter($items, static fn (array $item): bool => $item['title'] !== '' && $item['link'] !== ''));
        return ['source' => self::clean($source), 'items' => array_slice($items, 0, 12)];
    }

    /** @return array{title:string,link:string,time:?int,image:?string} */
    private static function item(string $title, string $link, string $date, ?string $image): array
    {
        $link = trim($link);
        $timestamp = $date !== '' ? strtotime($date) : false;
        return [
            'title' => self::clean($title),
            // Odkaz musí být běžná webová adresa – nikdy např. javascript:
            'link' => preg_match('~^https?://~i', $link) === 1 ? $link : '',
            'time' => $timestamp === false ? null : $timestamp,
            'image' => $image,
        ];
    }

    /** Náhledový obrázek z <enclosure> nebo <media:thumbnail> / <media:content>. */
    private static function image(\SimpleXMLElement $node): ?string
    {
        $candidates = [];
        if (isset($node->enclosure)) {
            $type = (string) $node->enclosure['type'];
            $enclosure = (string) $node->enclosure['url'];
            $looksLikeImage = preg_match('~\.(jpe?g|png|webp|gif)(\?|$)~i', $enclosure) === 1;
            if (str_starts_with($type, 'image/') || ($type === '' && $looksLikeImage)) {
                $candidates[] = $enclosure;
            }
        }
        $media = $node->children('http://search.yahoo.com/mrss/');
        foreach (['thumbnail', 'content'] as $tag) {
            if (isset($media->{$tag})) {
                $candidates[] = (string) $media->{$tag}->attributes()['url'];
            }
        }
        foreach ($candidates as $candidate) {
            if (str_starts_with($candidate, 'https://')) {
                return $candidate;
            }
        }
        return null;
    }

    private static function clean(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string) preg_replace('~\s+~u', ' ', $text));
    }

    /** „před 25 min“, „před 3 h“, „včera“, „14. 11.“ */
    public static function ago(?int $timestamp): string
    {
        if ($timestamp === null) {
            return '';
        }
        $minutes = intdiv(max(0, time() - $timestamp), 60);
        return match (true) {
            $minutes < 1 => 'právě teď',
            $minutes < 60 => 'před ' . $minutes . ' min',
            $minutes < 24 * 60 => 'před ' . intdiv($minutes, 60) . ' h',
            $minutes < 48 * 60 => 'včera',
            default => date('j. n.', $timestamp),
        };
    }
}
