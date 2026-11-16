<?php

declare(strict_types=1);

namespace DayBoard\Support;

/** České formátování: data, pozdravy, oslovení (5. pád) a tvary slov podle počtu. */
final class Czech
{
    public const DAYS = ['neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'];
    public const DAYS_SHORT = ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So'];
    public const MONTHS_GENITIVE = [
        1 => 'ledna', 'února', 'března', 'dubna', 'května', 'června',
        'července', 'srpna', 'září', 'října', 'listopadu', 'prosince',
    ];

    /** Ženská jména končící souhláskou se v 5. pádě nemění. */
    private const UNCHANGED = ['dagmar', 'ester', 'miriam', 'ruth', 'karin', 'ingrid', 'carmen', 'nikol', 'elen', 'vivien', 'doris', 'edit', 'judit', 'lilian', 'ines', 'rút'];

    /** „Pondělí 16. listopadu 2026“ */
    public static function longDate(\DateTimeInterface $date): string
    {
        return self::ucfirst(self::DAYS[(int) $date->format('w')]) . ' ' . self::dayMonth($date) . ' ' . $date->format('Y');
    }

    /** „16. listopadu“ */
    public static function dayMonth(\DateTimeInterface $date): string
    {
        return $date->format('j') . '. ' . self::MONTHS_GENITIVE[(int) $date->format('n')];
    }

    public static function greeting(\DateTimeInterface $now): string
    {
        $hour = (int) $now->format('G');
        return match (true) {
            $hour >= 5 && $hour < 9 => 'Dobré ráno',
            $hour >= 9 && $hour < 12 => 'Dobré dopoledne',
            $hour >= 12 && $hour < 18 => 'Dobré odpoledne',
            default => 'Dobrý večer',
        };
    }

    /**
     * Oslovení křestním jménem v 5. pádě: Miroslav -> Miroslave, Jana -> Jano,
     * Tomáš -> Tomáši, Marek -> Marku, Petr -> Petře. Jde o pravidla pro běžná
     * česká jména; neznámý tvar zůstane beze změny.
     */
    public static function vocative(string $name): string
    {
        $first = explode(' ', trim($name))[0];
        $lower = mb_strtolower($first);
        $length = mb_strlen($first);
        if ($length < 3 || in_array($lower, self::UNCHANGED, true)) {
            return $first;
        }

        $last = mb_substr($lower, -1);
        $lastTwo = mb_substr($lower, -2);
        $lastThree = mb_substr($lower, -3);
        $cut = static fn (int $chars): string => mb_substr($first, 0, $length - $chars);

        if ($last === 'a') {
            return $cut(1) . 'o';                                   // Jana -> Jano, Honza -> Honzo
        }
        if (preg_match('~[eěiíyýoóuúůáé]$~u', $lower) === 1) {
            return $first;                                          // Marie, Jiří, Hugo
        }
        if ($lastTwo === 'ek' || $lastTwo === 'ěk') {
            return match ($lastThree) {
                'něk' => $cut(3) . 'ňku',                           // Zdeněk -> Zdeňku
                'děk' => $cut(3) . 'ďku',                           // Luděk -> Luďku
                'těk' => $cut(3) . 'ťku',
                default => $cut(2) . 'ku',                          // Marek -> Marku
            };
        }
        if ($lastTwo === 'el') {
            return in_array($lastThree, ['iel', 'ael', 'uel'], true)
                ? $first . 'i'                                      // Daniel -> Danieli
                : $cut(2) . 'le';                                   // Pavel -> Pavle
        }
        if ($lastTwo === 'ec') {
            return $cut(2) . 'če';                                  // Vavřinec -> Vavřinče
        }
        if ($last === 'r') {
            $beforeIsVowel = preg_match('~[aáeéěiíoóuúůyý]r$~u', $lower) === 1;
            return $beforeIsVowel ? $first . 'e' : $cut(1) . 'ře';  // Igor -> Igore, Petr -> Petře
        }
        if (in_array($last, ['k', 'h', 'g'], true)) {
            return $first . 'u';                                    // Dominik -> Dominiku, Vojtěch -> Vojtěchu
        }
        if (in_array($last, ['š', 'ž', 'č', 'ř', 'ď', 'ť', 'ň', 'j', 'c', 'x', 's', 'z'], true)) {
            return $first . 'i';                                    // Tomáš -> Tomáši, Matěj -> Matěji
        }
        return $first . 'e';                                        // Miroslav -> Miroslave, Antonín -> Antoníne
    }

    /** Tvar slova podle počtu: plural(5, 'položka', 'položky', 'položek'). */
    public static function plural(int $count, string $one, string $few, string $many): string
    {
        $count = abs($count);
        return $count === 1 ? $one : ($count >= 2 && $count <= 4 ? $few : $many);
    }

    /** Délka trvání: 15 min, 1 h, 1,5 h, 2 h 20 min. */
    public static function duration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        if ($minutes % 60 === 0) {
            return intdiv($minutes, 60) . ' h';
        }
        if ($minutes % 30 === 0) {
            return str_replace('.', ',', (string) ($minutes / 60)) . ' h';
        }
        return intdiv($minutes, 60) . ' h ' . ($minutes % 60) . ' min';
    }

    /** Číslo s desetinnou čárkou. */
    public static function number(float $value, int $decimals = 0): string
    {
        return number_format($value, $decimals, ',', "\u{00A0}");
    }

    public static function ucfirst(string $text): string
    {
        return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }
}
