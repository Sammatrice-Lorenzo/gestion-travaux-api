FROM php:8.3-apache

RUN apt-get update && apt-get upgrade -y \
    && apt-get install -y \
        wget \
        libldap2-dev \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libsqlite3-dev \
        libsodium-dev \
        libssl-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        curl \
        fileinfo \
        gd \
        intl \
        ldap \
        mbstring \
        mysqli \
        pdo_mysql \
        pdo_sqlite \
        sodium \
        zip

RUN a2enmod rewrite

COPY ./docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/

EXPOSE 80

CMD [ "apache2-foreground" ]
