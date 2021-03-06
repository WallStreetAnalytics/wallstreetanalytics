FROM mongo:latest

LABEL maintainer Nick <nick@night.com>

# TODO: make read only
COPY ./scripts/mongo-init.sh /docker-entrypoint-initdb.d/

EXPOSE 27017