#!/bin/sh
set -ex

# setup psysh config
mkdir -p /root/.config/psysh
mkdir -p /home/www-user/.config/psysh
{ \
  echo '<?php'; \
  echo ''; \
  echo 'return ['; \
  echo '  "pager" => "more",'; \
  echo '  "historySize" => 0,'; \
  echo '  "eraseDuplicates" => true,'; \
  echo '];'; \
  echo ''; \
} | tee /root/.config/psysh/config.php > /home/www-user/.config/psysh/config.php

# download manual for psysh
apk add --no-cache ca-certificates openssl wget
mkdir -p /usr/local/share/psysh
wget https://psysh.org/manual/en/php_manual.sqlite -O /usr/local/share/psysh/php_manual.sqlite

# enable environment variables in php
echo 'env[APP_ENV] = $APP_ENV' >> /etc/php7/php-fpm.d/www.conf
echo 'env[PATH] = $PATH' >> /etc/php7/php-fpm.d/www.conf
