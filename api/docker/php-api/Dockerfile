FROM php:8.4.1-fpm-alpine

ARG XDEBUG_VERSION=3.4.0

RUN apk add --no-cache --virtual=build $PHPIZE_DEPS icu-dev \
  && apk add --no-cache postgresql-dev icu-libs zip libzip-dev linux-headers git \
  && pecl install xdebug-$XDEBUG_VERSION \
  && docker-php-ext-configure opcache --enable-opcache \
  && docker-php-ext-install -j$(nproc) intl opcache pdo_pgsql zip \
  && docker-php-ext-enable xdebug \
  && apk del build \
  && mv /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini.backup

COPY ./docker/php-api/php-fpm.conf /usr/local/etc/php-fpm.d/zz.conf
COPY ./docker/php-api/php-prod.ini /usr/local/etc/php/conf.d/php.ini

WORKDIR /usr/src/app

# Create a docker user
RUN addgroup -g 1000 docker \
  && adduser -D -G docker -u 1000 docker \
  && chown -R 1000:1000 . \
  && chmod -R 777 $PHP_INI_DIR

USER docker

# Install composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Prevent the re-installation of vendors at every changes in the source code
COPY --chown=docker:docker ./php-api/composer.* ./
RUN composer install --optimize-autoloader --prefer-dist --no-scripts --no-progress \
  && composer clear-cache

# Install sources
COPY --chown=docker:docker ./php-api/ ./
RUN bin/console cache:warmup --no-debug --env=prod

CMD ["php-fpm"]
