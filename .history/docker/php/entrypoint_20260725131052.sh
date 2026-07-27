#!/bin/sh
set -e

# Wait for MariaDB to be ready
echo "Waiting for MariaDB to be ready..."
until mysqladmin ping -h mariadb -u evounik_user -pevounik_password --silent; do
    sleep 2
done
echo "MariaDB is ready!"

# Install Composer dependencies if vendor directory doesn't exist
if [ ! -d "/var/www/html/vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-scripts --no-autoloader --prefer-dist
    composer dump-autoload --optimize
fi

# Clear cache if in dev environment
if [ "$APP_ENV" = "dev" ]; then
    echo "Clearing cache..."
    php bin/console cache:clear
fi

# Run database migrations if needed
# echo "Running database migrations..."
# php bin/console doctrine:migrations:migrate --no-interaction

# Start PHP-FPM
echo "Starting PHP-FPM..."
exec php-fpm