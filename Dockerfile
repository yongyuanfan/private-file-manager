FROM alpine:3.22.4

COPY ./privite-file-manager /bin/private-file-manager

RUN chmod +x /bin/private-file-manager

WORKDIR /app

ENTRYPOINT ["/bin/private-file-manager"]
