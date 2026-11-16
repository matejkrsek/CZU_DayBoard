#!/bin/sh
# Spuštění Apache v kontejneru. Hostingy jako Render předávají port v proměnné
# PORT – Apache se na něj přepne; bez ní (lokální Docker) poslouchá na portu 80.
set -e

PORT="${PORT:-80}"
sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
