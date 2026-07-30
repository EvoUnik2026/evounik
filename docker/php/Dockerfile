FROM php:8.4.1-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    nodejs \
    npm \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libmcrypt-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libpng \
    libjpeg \
    libwebp \
    freetype \
    libmcrypt \
    libzip \
    oniguruma-dev \
    libxml2-dev \
    mysql-client \
    mariadb-connector-c-dev \
    pcre-dev \
    icu-dev \
    lexbor-dev \
    ${PHPIZE_DEPS}

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        bcmath \
        gd \
        zip \
        intl \
        xml

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files so dependency installation uses the lock file
COPY composer.json composer.lock ./

# Install composer dependencies from lock file, including dev packages
RUN composer install --no-scripts --no-plugins --no-autoloader --prefer-dist --no-interaction

# Copy application files
COPY . .

# Generate autoloader
RUN composer dump-autoload --optimize

# Create and set permissions
RUN mkdir -p /var/www/html/var /var/www/html/public \
    && chown -R www-data:www-data /var/www/html/var /var/www/html/public

# Expose port 9000 for PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]