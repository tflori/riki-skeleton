FROM iras/php7-fpm:1

WORKDIR /riki

COPY docker.dev/php/install.sh /tmp/install.sh
RUN /bin/sh /tmp/install.sh && rm /tmp/install.sh
ENV PATH /riki/vendor/bin:/riki/bin:$PATH
ENV APP_ENV testing
