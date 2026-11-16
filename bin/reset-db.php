<?php

declare(strict_types=1);

// Smaže databázi a vytvoří ji znovu s ukázkovou domácností.
// Použití: php bin/reset-db.php          (zeptá se na potvrzení)
//          php bin/reset-db.php --yes    (bez dotazu)

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Tento skript lze spustit jen z příkazové řádky.');
}

require dirname(__DIR__) . '/src/bootstrap.php';

use DayBoard\Core\Config;
use DayBoard\Core\Database;
use DayBoard\Database\Seeder;

$path = Config::path('DB_PATH', 'storage/dayboard.sqlite');

if (is_file($path) && !in_array('--yes', $argv, true)) {
    echo "Databáze {$path} bude SMAZÁNA včetně všech účtů a nastavení.\n";
    echo 'Pokračovat? Napište "ano": ';
    if (trim((string) fgets(STDIN)) !== 'ano') {
        exit("Zrušeno, databáze zůstala beze změny.\n");
    }
}

foreach ([$path, $path . '-journal', $path . '-wal', $path . '-shm'] as $file) {
    if (is_file($file)) {
        unlink($file);
    }
}
array_map('unlink', glob(DAYBOARD_ROOT . '/storage/cache/*.json') ?: []);

Database::pdo(); // vytvoří schéma a ukázková data

echo "Hotovo. Ukázkové účty (heslo „" . Seeder::DEMO_PASSWORD . "“):\n";
foreach (Seeder::DEMO_ACCOUNTS as [$login, $name, $role]) {
    echo sprintf("  %-26s %-10s %s\n", $login, $name, DayBoard\Models\Users::ROLES[$role]);
}
