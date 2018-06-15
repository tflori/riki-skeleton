FROM iras/php7-fpm:1

COPY public /<?= $basePath ?>/public
COPY src /<?= $basePath ?>/src
COPY app /<?= $basePath ?>/app
COPY bin /<?= $basePath ?>/bin
COPY vendor /<?= $basePath ?>/vendor
COPY ["composer.json", "composer.lock", "/<?= $basePath ?>/"]

WORKDIR /app

COPY docker/php/install.sh /tmp/install.sh
RUN /bin/sh /tmp/install.sh && rm /tmp/install.sh
ENV PATH /<?= $basePath ?>/bin:$PATH
ENV APP_ENV testing
