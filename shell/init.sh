#!/bin/sh

source `dirname $0`/define.sh

cd ${DIR_BOT}

php nise_bot/main.php ${BOT_NAME1} upstream
php nise_bot/main.php ${BOT_NAME1} migration
