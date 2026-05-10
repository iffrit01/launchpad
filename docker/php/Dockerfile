FROM php:8.3-fpm-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        bash \
        ca-certificates \
        curl \
        git \
        libicu-dev \
        libonig-dev \
        libpng-dev \
        libzip-dev \
        openssh-client \
        unzip \
        zip \
    && docker-php-ext-install \
        bcmath \
        intl \
        mbstring \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupadd --gid ${GID} launchpad \
    && useradd --uid ${UID} --gid ${GID} --create-home --shell /bin/bash launchpad

WORKDIR /app

COPY docker/php/php.ini /usr/local/etc/php/conf.d/launchpad.ini
COPY docker/php/entrypoint.sh /usr/local/bin/launchpad-entrypoint
RUN chmod +x /usr/local/bin/launchpad-entrypoint

USER launchpad

ENTRYPOINT ["launchpad-entrypoint"]
CMD ["php-fpm"]
