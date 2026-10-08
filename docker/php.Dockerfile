FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*
COPY docker/apache.conf /etc/apache2/conf-enabled/pathyatra.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/pathyatra.ini
COPY api /var/www/html/api
COPY config /var/www/html/config
COPY lib /var/www/html/lib
COPY database /var/www/html/database
COPY tests /var/www/html/tests
WORKDIR /var/www/html
