FROM alpine:3.22.4

COPY . /app

WORKDIR /app

RUN mv ./bin/* /usr/local/bin/

# RUN composer config -g repo.packagist composer https://packagist.phpcomposer.com

RUN composer install --no-dev --optimize-autoloader

ENTRYPOINT ["php", "start.php", "start"]
