FROM php:8.4-fpm

ARG UID=1000
ARG GID=1000

# poppler-utils (pdftotext/pdftoppm) is used in later phases for PDF page counts, thumbnails and search indexing
RUN apt-get update && apt-get install -y --no-install-recommends \
        git curl unzip zip libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev \
        libfreetype6-dev libonig-dev libxml2-dev poppler-utils \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd intl zip opcache \
    && pecl install redis && docker-php-ext-enable redis \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# Run as the host user so files created in the container are editable on the host
RUN groupmod -o -g ${GID} www-data && usermod -o -u ${UID} -g ${GID} www-data \
    && chown www-data:www-data /var/www

WORKDIR /var/www/html
USER www-data
