<?php
ini_set('memory_limit', '64M');

define('LIB_DIR' , __DIR__. '/../lib');
define('LOG_DIR' , __DIR__. '/../log');
define('LOG_FILE', LOG_DIR. '/friendship.txt');

$db = json_decode(file_get_contents(__DIR__.'/../config/db.json'), true);
require LIB_DIR . '/paris/idiorm.php';
require LIB_DIR . '/paris/paris.php';
require LIB_DIR . '/twitteroauth/twitteroauth.php';
require LIB_DIR . '/f.php';
require LIB_DIR . '/twitter.php';
require LIB_DIR . '/yahooma.php';
require __DIR__ . '/nise_bot.php';
require __DIR__ . '/model.php';

var_dump($db);

ORM::configure([
    'connection_string' => "mysql:host=${db['HOST']};port=${db['PORT']};dbname=${db['DBNAME']}",
    'username' => $db['USER'],
    'password' => $db['PASS'],
    'driver_options' =>  [ PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES ${db['CHARSET']}" ],
    'logging' => true
]);

$name = isset($argv[1]) ? $argv[1] : null;
$task = isset($argv[2]) ? $argv[2] : null;
$var  = isset($argv[3]) ? $argv[3] : null;
$var2 = isset($argv[4]) ? $argv[4] : null;

$niseBot = new NiseBot($name);

usleep(mt_rand(0, 1000));

// 巡回
if ($task === 'search') {
    $niseBot->task_search();
}
// 発言
else if ($task === 'tweet') {
    $niseBot->task_tweet();
}
// リプライ
else if ($task === 'reply') {
    if (!$var) exit ('undefined reply id.');
    $niseBot->task_tweet($var);
}
// 診断メーカー
else if ($task === 'shindan') {
    if (!$var) exit ('undefined shindan id.');
    $niseBot->task_shindan($var);
}
// Yo
else if ($task === 'yo') {
    if (!$var) exit ('undefined reply id.');
    $niseBot->task_yo($var);
}
// React
else if ($task === 'react') {
    if (!$var ) exit ('undefined reply id.');
    if (!$var2) exit ('undefined react word.');
    $niseBot->task_react($var, $var2);
}
// フォロー
else if ($task === 'friendship') {
    $niseBot->task_friendship();
}
// マイグレーション
else if ($task === 'migration') {
    $niseBot->task_migration();
}
// 遡り保存
else if ($task === 'upstream') {
    $niseBot->task_upstream();
}
// error
else {
    echo "undefined task.\n";
}

exit;
