#!/bin/sh

export XDEBUG_CONFIG="idekey=ANY_IDE remote_host=`route -A inet|grep default|tr -s ' '|cut -d' ' -f2`"
export PHP_IDE_CONFIG="serverName=<?= $basePath ?>"

exec php $@
