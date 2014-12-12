#!/bin/sh

source `dirname $0`/define.sh

cd ${DIR_BOT}

# mybot1
nohup node nise_bot/main.js ${BOT_NAME1} > log/stdout.txt 2> log/stderr.txt < /dev/null &

# mybot2
#nohup node nise_bot/main.js ${BOT_NAME2} > log/stdout.txt 2> log/stderr.txt < /dev/null &
