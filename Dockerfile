FROM php:8.4-apache AS base

RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
		git \
		curl \
		unzip \
		zip \
		libpng-dev \
		libjpeg62-turbo-dev \
		libfreetype6-dev \
		libonig-dev \
		libzip-dev \
		libicu-dev \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j$(nproc) \
		pdo_mysql \
		mbstring \
		exif \
		pcntl \
		bcmath \
		intl \
		zip \
		gd \
		opcache \
	&& pecl install redis \
	&& docker-php-ext-enable redis \
	&& a2enmod rewrite headers \
	&& rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS php-deps

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts

FROM node:20-bullseye-slim AS node-build

WORKDIR /var/www/html
COPY package.json ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM base AS app

WORKDIR /var/www/html
COPY . .
COPY --from=php-deps /var/www/html/vendor /var/www/html/vendor
COPY --from=node-build /var/www/html/public/build /var/www/html/public/build

RUN printf '%s\n' \
		'<VirtualHost *:80>' \
		'  DocumentRoot /var/www/html/public' \
		'  <Directory /var/www/html/public>' \
		'    AllowOverride All' \
		'    Require all granted' \
		'  </Directory>' \
		'</VirtualHost>' \
		> /etc/apache2/sites-available/000-default.conf \
	&& chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

