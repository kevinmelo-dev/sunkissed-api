FROM php:8.3-fpm-alpine

# System dependencies
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    $PHPIZE_DEPS

# PHP extensions required by Laravel + this project
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        pcntl

# Redis extension (queues + cache)
RUN pecl install redis \
    && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Non-root user matching typical WSL/Linux UID for clean file permissions
ARG UID=1000
ARG GID=1000
RUN addgroup -g ${GID} app \
    && adduser -D -u ${UID} -G app app

WORKDIR /var/www/html

USER app

EXPOSE 9000

CMD ["php-fpm"]
