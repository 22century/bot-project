#!/bin/sh

source `dirname $0`/define.sh

cd ${DIR_BOT}

nohup php nise_bot/main.php ${BOT_NAME1} tweet &> ${NOHUP_OUT} &
#nohup php nise_bot/main.php ${BOT_NAME2} tweet &> ${NOHUP_OUT} &
