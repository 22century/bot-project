#!/bin/sh

DIR_BOT="/任意のディレクトリ/bot-project"

cd ${DIR_BOT}

# mybot1
nohup node nise_bot/main.js mybot1 > log/stdout.txt 2> log/stderr.txt < /dev/null &

# mybot2
nohup node nise_bot/main.js mybot2 > log/stdout.txt 2> log/stderr.txt < /dev/null &
