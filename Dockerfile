FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    libsqlite3-dev unzip curl && \
    docker-php-ext-install pdo pdo_sqlite bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader

RUN cp .env.example .env && php artisan key:generate

EXPOSE 8000

CMD ["sh", "-c", "php artisan schedule:work & php artisan serve --host=0.0.0.0 --port=8000"]
