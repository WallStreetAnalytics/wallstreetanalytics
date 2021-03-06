FROM python:3.8-slim-buster

LABEL maintainer Nick <nick@night.com>

# TODO: might not need waiforit with newer versions of compose
RUN apt-get update &&\
    apt-get install --yes --no-install-recommends \
    bash \
    curl \
    gcc \
    make &&\
    apt-get clean &&\
    rm -rf /var/lib/apt/lists/* &&\
    curl -o /usr/local/bin/waitforit -sSL https://github.com/maxcnunes/waitforit/releases/download/v2.4.1/waitforit-linux_amd64 && \
    chmod +x /usr/local/bin/waitforit

RUN pip3 install --no-cache-dir --upgrade pip

ARG env
ARG requirements_file
ENV ENV ${env:-dev}
ENV REQUIREMENTS_FILE ${requirements_file:-dev.txt}
ENV PYTHONPATH "${PYTHONPATH}:/code/edgar/src"

ADD requirements/base.txt requirements/${REQUIREMENTS_FILE} /tmp/
RUN pip3 install --no-cache-dir -r /tmp/${REQUIREMENTS_FILE}

ADD . /code/

RUN mkdir -p /data/logs

VOLUME /data

EXPOSE 5000

WORKDIR /code/
