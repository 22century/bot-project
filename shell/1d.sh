#!/bin/sh

DIR_BOT="/任意のディレクトリ/bot-project"

cd ${DIR_BOT}

nohup php nise_bot/main.php mybot1 friendship
nohup php nise_bot/main.php mybot2 friendship
