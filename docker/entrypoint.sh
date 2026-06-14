#!/bin/sh
set -e

cd /app

if [ ! -d "vendor" ] && [ -f "composer.json" ]; then
    composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
    composer install --no-dev --optimize-autoloader
fi

if [ ! -f ".env" ]; then
    cp .env.example .env
fi

php start.php start -d

cleanup() {
    php start.php stop
}
trap cleanup TERM INT

wait
