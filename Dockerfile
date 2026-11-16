# DayBoard – obraz s Apache a PHP. Použití: docker compose up -d
FROM php:8.3-apache

# Čisté URL (mod_rewrite), cache statických souborů a komprese
RUN a2enmod rewrite expires deflate \
    # webový kořen je složka public/, zdrojové kódy a data jsou mimo něj
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html
COPY . .

# Apache běží pod uživatelem www-data a potřebuje zapisovat databázi, cache a logy
RUN chown -R www-data:www-data storage

EXPOSE 80
