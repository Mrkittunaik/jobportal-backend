# Job Portal Backend

Laravel 12 REST API: Sanctum auth, MySQL, Cloudflare R2 file storage.

    composer install --no-dev --optimize-autoloader
    cp .env.example .env        # fill DB + R2 values
    php artisan key:generate
    php artisan migrate --force
    php artisan db:seed --force
