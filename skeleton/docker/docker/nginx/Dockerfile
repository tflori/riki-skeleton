FROM iras/nginx:1

COPY public /<?= $basePath ?>/public
COPY docker/nginx/server.conf /etc/nginx/conf.d/server/<?= $basePath ?>.conf

ENV DOCUMENT_ROOT /<?= $basePath ?>/public
