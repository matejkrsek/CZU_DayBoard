<?php

declare(strict_types=1);

namespace DayBoard\Services;

use DayBoard\Models\Household;
use DayBoard\Models\Users;

/**
 * Sdílená poloha členů domácnosti. Pro účely projektu jde o SIMULACI: polohy se
 * nepřebírají z telefonů, ale počítají se z denní doby – ve všední den dopoledne
 * je člen „v práci“ nebo „ve škole“, jinak „doma“. Skutečný zdroj polohy
 * (např. mobilní aplikace) by nahradil metodu simulate().
 */
final class LocationService
{
    private const COLORS = ['#c2410c', '#1d4ed8', '#15803d', '#7e22ce', '#b45309', '#0f766e', '#be185d', '#4338ca'];

    /** @return array{home:array{lat:float,lon:float,name:string},members:list<array<string,mixed>>} */
    public static function members(): array
    {
        $home = Household::location();
        $members = [];
        foreach (Users::all() as $index => $user) {
            if ((int) $user['share_location'] !== 1) {
                continue; // člen sdílení polohy vypnul v osobním nastavení
            }
            $members[] = [
                'id' => (int) $user['id'],
                'name' => (string) $user['display_name'],
                'initial' => Users::initial($user),
                'color' => self::COLORS[$index % count(self::COLORS)],
            ] + self::simulate($user, $home);
        }
        return ['home' => ['lat' => $home['lat'], 'lon' => $home['lon'], 'name' => $home['name']], 'members' => $members];
    }

    /**
     * @param array{lat:float,lon:float,name:string} $home
     * @return array{lat:float,lon:float,place:string}
     */
    private static function simulate(array $user, array $home): array
    {
        $now = new \DateTimeImmutable();
        $weekday = (int) $now->format('N') <= 5;
        $minuteOfDay = (int) $now->format('G') * 60 + (int) $now->format('i');
        $away = $weekday && $minuteOfDay >= 8 * 60 && $minuteOfDay < 16 * 60;

        // Každý člen má své stálé „pracoviště“ odvozené z ID: 1,5–4 km od domova.
        $seed = (int) $user['id'] * 2654435761 % 1000;
        $angle = ($seed / 1000) * 2 * M_PI;
        $distanceKm = $user['role'] === 'guest' ? 0.8 + ($seed % 7) / 10 : 1.5 + ($seed % 25) / 10;

        $north = $away ? cos($angle) * $distanceKm : 0.0;
        $east = $away ? sin($angle) * $distanceKm : 0.0;

        // Drobný pohyb kolem místa (desítky metrů), aby mapa působila živě.
        $phase = time() / 90 + $seed;
        $north += sin($phase) * 0.03;
        $east += cos($phase * 0.7) * 0.03;

        return [
            'lat' => round($home['lat'] + $north / 111.32, 5),
            'lon' => round($home['lon'] + $east / (111.32 * cos(deg2rad($home['lat']))), 5),
            'place' => !$away ? 'doma' : ($user['role'] === 'guest' ? 've škole' : 'v práci'),
        ];
    }
}
