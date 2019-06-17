FROM ivankristianto/php7apache

RUN php -r "readfile('https://getcomposer.org/installer');" | php -- --install-dir=/usr/local/bin --filename=composer \
        && chmod +x /usr/local/bin/composer

WORKDIR /var/www/html

COPY ./ /var/www/html

RUN cd /var/www/html && composer install --no-interaction