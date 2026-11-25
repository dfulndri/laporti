FROM php:8.2-apache-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
      libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
      libc-client-dev libkrb5-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install -j"$(nproc)" gd mysqli pdo_mysql zip imap \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

RUN { \
      echo 'display_errors=On'; \
      echo 'error_reporting=E_ALL & ~E_DEPRECATED & ~E_NOTICE'; \
      echo 'upload_max_filesize=20M'; \
      echo 'post_max_size=25M'; \
    } > /usr/local/etc/php/conf.d/laporti.ini

WORKDIR /var/www/html
