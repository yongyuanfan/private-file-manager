FROM alpine:3.22.4

ADD . /app

WORKDIR /app

EXPOSE 8787

ENTRYPOINT ["./bin/php", "start.php", "start"]
