FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    zip \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libxml2-dev \
    oniguruma-dev \
    icu-dev \
    libzip-dev \
    shadow \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp && \
    docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        xml \
        zip \
        bcmath \
        gd \
        intl \
        opcache \
        pcntl \
        exif

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Create non-root user matching typical host UID
RUN groupmod -g 1000 www-data && \
    usermod -u 1000 -g www-data www-data

WORKDIR /var/www/html

# Copy entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
