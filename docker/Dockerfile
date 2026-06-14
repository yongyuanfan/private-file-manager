FROM alpine:3.24.0

RUN apk update && apk upgrade --no-cache

COPY ./php /bin/php

COPY ./composer /bin/composer

COPY ./entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

WORKDIR /app

EXPOSE 8787

ENTRYPOINT ["/entrypoint.sh"]