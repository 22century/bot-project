#!/bin/sh

source `dirname $0`/define.sh

cd ${DIR_BOT}

nohup php nise_bot/main.php ${BOT_NAME1} search &> ${NOHUP_OUT} &
#nohup php nise_bot/main.php ${BOT_NAME2} search &> ${NOHUP_OUT} &
