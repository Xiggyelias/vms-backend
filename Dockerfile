FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers deflate expires

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html
COPY docker/apache-security.conf /etc/apache2/conf-available/security-hardening.conf
COPY docker/apache-performance.conf /etc/apache2/conf-available/performance-tuning.conf
COPY docker/php-security.ini /usr/local/etc/php/conf.d/security.ini
COPY docker/start.sh /usr/local/bin/start-app
RUN chmod +x /usr/local/bin/start-app \
    && a2enconf security-hardening \
    && a2enconf performance-tuning

RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader --no-interaction; fi \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
CMD ["start-app"]
