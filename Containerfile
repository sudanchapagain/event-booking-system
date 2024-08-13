FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    postgresql-client \
    && docker-php-ext-install pgsql pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . .

RUN chown -R 1000:1000 /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
