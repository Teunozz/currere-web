# Stage 1: Build frontend assets
FROM node:24-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.ts tsconfig.json eslint.config.js ./
COPY resources/ resources/
COPY public/ public/

ENV SKIP_WAYFINDER=1
RUN npm run build

# Stage 2: Install PHP dependencies
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

# Stage 3: Production image
FROM ubuntu:24.04

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV DB_DATABASE=/data/database.sqlite

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update && apt-get upgrade -y \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg curl ca-certificates zip unzip supervisor sqlite3 libcap2-bin libpng-dev nano \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y \
       php8.4-cli \
       php8.4-sqlite3 \
       php8.4-gd \
       php8.4-curl \
       php8.4-mbstring \
       php8.4-xml \
       php8.4-zip \
       php8.4-bcmath \
       php8.4-intl \
       php8.4-readline \
    && setcap "cap_net_bind_service=+ep" /usr/bin/php8.4 \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Copy application code
COPY --from=composer /app/vendor vendor/
COPY . .

# Copy built frontend assets (overwrite the public/build directory)
COPY --from=frontend /app/public/build public/build/

# Remove dev/build files not needed at runtime
RUN rm -rf node_modules tests resources/js resources/css \
    .dockerignore \
    vite.config.ts tsconfig.json eslint.config.js \
    package.json package-lock.json

# Setup supervisor and entrypoint
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start-container /usr/local/bin/start-container
COPY docker/php.ini /etc/php/8.4/cli/conf.d/99-production.ini
RUN chmod +x /usr/local/bin/start-container

# Ensure storage and cache directories exist
RUN mkdir -p storage/logs \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    database

EXPOSE 80

ENTRYPOINT ["start-container"]
