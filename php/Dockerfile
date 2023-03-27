FROM php:8.2-apache

RUN apt-get update && apt-get upgrade -y \
    && apt-get install -y wget \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql \
    && wget https://get.symfony.com/cli/installer -O - | bash \
    && docker-php-ext-install mysqli && docker-php-ext-enable mysqli \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin \
    && export PATH="$HOME/.symfony5/bin:$PATH"

RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev \
    unzip
RUN docker-php-ext-install zip
# RUN pecl install apcu && docker-php-ext-enable apcu

RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
   mv composer.phar /usr/local/bin/composer

RUN apt update && apt install -yqq nodejs npm

COPY ./php/apache.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/

EXPOSE 80