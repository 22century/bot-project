<?php

use nise_bot\model\Model;

class NiseBot
{
    const REQUEST_FOLLOW_MAX = 10;

    private $T;
    private $Y;
    private $SETTING = [];
    private $SPECIAL_NAMES = [];
    private $LIMIT_DATETIME = '';

    public function __construct ($bot_name) {

        echo "--------------------------------------------------\n"
            . "| ↓↓↓ はじまるよ ↓↓↓\n"
            . "--------------------------------------------------\n\n";

        $path = __DIR__ . "/inc/{$bot_name}.json";

        if (!file_exists($path)) {
            exit("{$path} not found.\n");
        }

        $json = file_get_contents($path);
        $this->SETTING = json_decode($json);

        $this->LIMIT_DATETIME = F::mysql_datetime(time() - F::day_time(60));

        $this->Y = new YahooMa();

        $this->T = new Twitter(
            $this->SETTING->KEY->consumer_key,
            $this->SETTING->KEY->consumer_secret,
            $this->SETTING->KEY->access_token,
            $this->SETTING->KEY->access_token_secret
        );

        // 特殊名詞
        foreach (ORM::for_table('names')->find_array() as $row) {
            array_push($this->SPECIAL_NAMES, $row['text']);
        }

    }

    public function __destruct () {
        echo "\n--------------------------------------------------\n"
           . "| ↑↑↑ おわりだよ ↑↑↑\n"
           . "--------------------------------------------------\n";
    }

    /**
     * 巡回タスク
     */
    public function task_search () {
        echo "task_search.\n";
        // エゴサーチ
        $this->update_favorites();

        // 発言更新
        $tl = $this->get_timeline($this->SETTING->ORIGIN_NAME);
        $len = $this->update_statuses($tl);

        echo "refresh count: {$len}\n";

        if ($len <= 0) return;

        // 発言を結合
        $statuses = ORM::for_table('statuses')
            ->where([
                'bot_id' => $this->SETTING->ID,
                'lf' => false
            ])
            ->limit($len)
            ->order_by_desc('created_at')
            ->find_many();

        $sentence = '';
        $byte = 0;

        foreach ($statuses as $status) {
            $byte += mb_strlen($status['text'], 'ASCII');
            // API文字制限チェック
            if ($byte > 30000) break;
            $sentence .= $status['text'];
        }

        // 形態素解析APIに投げる
        $ma_map = $this->Y->getSurfaceMap($sentence);

        // 解析した単語をDB保存
        $this->update_poses($ma_map);
        $this->update_kaomoji($statuses);

    }

    /**
     * マイグレーションタスク
     */
    public function task_migration () {
        echo "task_migration.\n";
        // 発言を結合
        $statuses = ORM::for_table('statuses')
            ->where([
                'bot_id' => $this->SETTING->ID,
                'lf' => false
            ])
            ->order_by_desc('created_at')
            ->find_many();

        var_dump(count($statuses));

        $sentence = '';
        $byte = 0;
        $last_index = count($statuses) - 1;

        foreach ($statuses as $i => $status) {
            $byte += mb_strlen($status['text'], 'ASCII');
            $sentence .= $status['text'];
            // API文字制限チェック
            if ($byte > 30000 || $i >= $last_index) {
                echo "index: {$i}, byte: {$byte}\n";
                $ma_map = $this->Y->getSurfaceMap($sentence);
                $this->update_poses($ma_map);
                // リセット
                $byte = 0;
                $sentence = '';
            }
        }
    }

    /**
     * 発言タスク
     * @param string $reply_id
     */
    public function task_tweet ($reply_id = null) {
        echo "task_send.\n";

        $origin_status = Model::findRandStatus($this->SETTING->ID);
        $origin_text = trim($origin_status['text']);

        echo "befor: {$origin_text}\n";

        if ($this->SETTING->ORIGIN_NAME === 'lucidonn') {
            $my_text = $this->get_message_lucidonn($origin_text);
        }
        else {
            $my_text = $this->get_message($origin_text);
        }

        if ($origin_text === $my_text) {
            $my_text = $this->mode_random();
        }

        echo "after: {$my_text}\n";

        // 発言
        $my_status = $this->send_tweet($my_text, $reply_id);

        // ログ
        if ($my_status) {
            $this->update_log($my_status, $origin_status);
        }

    }

    /**
     * Yo
     * @param null $reply_id
     */
    public function task_yo ($reply_id = null) {
        echo "Yo\n";
        $this->send_tweet('Yo', $reply_id);
    }

    /**
     * React
     * @param string $reply_id
     * @param string $word
     */
    public function task_react ($reply_id, $word) {
        echo "React {$reply_id} {$word}\n";

        $origin_status = Model::findRandStatus($this->SETTING->ID);
        $origin_text = trim($origin_status['text']);

        $my_text = $this->mode_jaga($origin_text, $word);

        if ($origin_text === $my_text) {
            $my_text = $word;
        }

        $my_status = $this->send_tweet($my_text, $reply_id);

        if ($my_status) {
            $this->update_log($my_status, $origin_status);
        }
    }

    /**
     * ぅしどん専用
     * @param string $origin_text
     * @return string
     */
    private function get_message_lucidonn ($origin_text) {

        switch ( mt_rand(0, 20) ) {
            case 0:
                echo "mode random nouns.\n";
                $my_text = $this->mode_random_nouns($origin_text,['ぜのぷ', 'がぉん']);
                break;
            case 1:
                echo "mode jaga.\n";
                $my_text = $this->mode_jaga($origin_text);
                break;
            case 2:
                echo "mode random.\n";
                $my_text = $this->mode_random();
                break;
            case 3:
                echo "mode bug.\n";
                $my_text = $this->create_message($origin_text);
                $my_text = $this->mode_bug($my_text);
                break;
            case 4:
                echo "mode kaomoji.\n";
                $my_text = $this->mode_kaomoji();
                break;
            default:
                $my_text = $this->create_message($origin_text);
                break;
        }

        if (mt_rand(1, 3) === 1) {
            echo "mode sonnani, fukuwarai.\n";
            $my_text = $this->mode_sonnani($my_text);
            $my_text = $this->mode_fukuwarai($my_text);
        }

        if (mt_rand(1, 50) === 1) {
            echo "mode yametokune.\n";
            $my_text = $this->mode_yametokune($my_text);
        }

        if ($origin_text === $my_text) {
            $my_text = $this->mode_random();
        }

        return $my_text;
    }

    /**
     * 文章構築
     * @param string $origin_text
     * @return string
     */
    private function get_message ($origin_text) {

        switch ( mt_rand(0, 20) ) {
            case 0:
                echo "mode random nouns.\n";
                $my_text = $this->mode_random_nouns($origin_text,['たまご']);
                break;
            case 1:
                echo "mode random.\n";
                $my_text = $this->mode_random();
                break;
            case 2:
                echo "mode bug.\n";
                $my_text = $this->create_message($origin_text);
                $my_text = $this->mode_bug($my_text);
                break;
            case 3:
                echo "mode kaomoji.\n";
                $my_text = $this->mode_kaomoji();
                break;
            default:
                $my_text = $this->create_message($origin_text);
                break;
        }

        if (mt_rand(1, 3) === 1) {
            echo "mode sonnani, fukuwarai.\n";
            $my_text = $this->mode_sonnani($my_text);
            $my_text = $this->mode_fukuwarai($my_text);
        }

        if ($origin_text === $my_text) {
            $my_text = $this->mode_random();
        }

        return $my_text;
    }

    /**
     * 診断メーカー
     * @param string $id
     */
    public function task_shindan ($id) {
        $paste_text = F::shindan_maker($id, $this->SETTING->NAME_JP);
        if (!$paste_text) return;
        // 発言
        $this->send_tweet($paste_text);
        echo $paste_text;
    }

    /**
     * 発言の取得
     * @param string $user_name
     * @return array
     */
    private function get_timeline($user_name) {

        $last_status = ORM::for_table('statuses')->where('bot_id', $this->SETTING->ID)->order_by_desc('created_at')->find_one();
        $since_id = (!$last_status) ? 0 : $last_status['id_str'];

        // 検索モード
        if ($this->SETTING->USE_SEARCH) {
            $timeline = $this->T->search([
                'q'           => 'from:' . $user_name,
                'count'       => 100,
                'since_id'    => $since_id,
                'result_type' => 'recent',
            ]);
            return $timeline['statuses'];
        } else {
            return $this->T->user_timeline([
                'screen_name' => $user_name,
                'count'       => 200,
                'since_id'    => $since_id,
            ]);
        }

    }

    /**
     * さかのぼって保存
     */
    public function task_upstream () {

        $statuses = [];
        $param = [
            'screen_name' => $this->SETTING->ORIGIN_NAME,
            'count'       => 100,
            'result_type' => 'recent',
        ];

        $max_id = null;
        $loop = 0;

        do {
            if ($max_id !== null) $param['max_id'] = $max_id;
            $timeline = $this->T->user_timeline($param);
            //var_dump($timeline);
            if ($timeline) {
                $statuses = array_merge($statuses, $timeline);
                $last_status = $timeline[ count($timeline) - 1 ];
                $max_id = $last_status['id_str'];
                var_dump($timeline[0]['id_str']);
                var_dump($max_id);
            }
            ++$loop;
            // 1000件
        } while ($loop < 10);

        $count = $this->update_statuses($statuses);

        echo 'updated:'.$count.'/'.count($statuses);
    }

    /**
     * エゴサーチふぁぼ
     */
    private function update_favorites() {

        if (!$this->SETTING->SEARCH_VAL) return;

        $tl = $this->T->search([
            'q' => $this->SETTING->SEARCH_VAL,
            'count' => 10,
            'result_type' => 'recent'
        ]);

        if (!isset($tl['statuses'])) {
            return;
        }

        foreach ($tl['statuses'] as $status) {
            if ($status['favorited'] === false) {
                if (!$this->SETTING->DEBUG) {
                    $this->T->favorites_create(['id'=>$status['id_str']]);
                }
                echo "Favorited: {$status['user']['screen_name']} {$status['id_str']} {$status['text']}\n";
            }
        }

    }

    /**
     * 発言をDB保存
     * @param array $tl
     * @return int 保存数
     */
    private function update_statuses($tl){

        $count = 0;

        foreach ($tl as $status) {
            $text = $status['text'];
            // フィルタリング対象
            if (F::is_filter($text)) continue;
            $text = htmlspecialchars_decode($text);

            $row = ORM::for_table('statuses')
                ->where([
                    'bot_id' => $this->SETTING->ID,
                    'text'   => $text
                ])->find_one() ?: false;

            if ($row === false) {
                ORM::for_table('statuses')
                    ->create()
                    ->set([
                        'id_str'     => $status['id_str'],
                        'bot_id'     => $this->SETTING->ID,
                        'text'       => $status['text'],
                        'lf'         => F::is_nl($text),
                        'created_at' => F::mysql_datetime($status['created_at']),
                    ])->save();
                ++ $count;
            }
        }

        return $count;
    }

    /**
     * 顔文字保存
     * @param array $statuses
     */
    private function update_kaomoji($statuses) {
        foreach ($statuses as $status) {
            $kao = F::kaomoji($status['text']);
            if (!$kao) continue;
            $found = ORM::for_table('kaomoji')->where([ 'text' => $kao, 'bot_id' => $this->SETTING->ID ])->find_one();
            if ($found) continue;
            echo "update_kaomoji: {$kao}\n";
            ORM::for_table('kaomoji')
                ->create()
                ->set([
                    'bot_id'     => $this->SETTING->ID,
                    'text'       => $kao,
                    'face'       => F::is_face($kao),
                    'created_at' => F::mysql_datetime(time()),
                ])->save();
        }
    }

	/**
	 * 品詞をキーとした連想配列から品詞テーブルに格納
     * @param array $maMap
     */
    private function update_poses($maMap) {

        $now = F::mysql_datetime(time());
        $data = [];

        foreach ($maMap as $ma) {
            if (F::is_special($ma)) continue;
            if (F::is_splitpos($ma)) {
                array_push($data, [
                    'prefix' => $ma,
                    'suffix_type' => '',
                    'suffix_text' => '',
                ]);
            } else {
                if (count($data) <= 0) continue;
                $idx = count($data) - 1;
                $data[$idx]['suffix_type'] .= $ma['pos'];
                $data[$idx]['suffix_text'] .= $ma['surface'];
            }
        }

        foreach ($data as $datam) {

            if (F::is_not_word($datam['prefix']['surface'], $datam['prefix']['pos'] === '名詞')) {
                continue;
            }

            $found = ORM::for_table('poses')
                ->where([
                    'bot_id' => $this->SETTING->ID,
                    'type'   => $datam['prefix']['pos'],
                    'text'   => $datam['prefix']['surface'],
                    'suffix_type' => $datam['suffix_type'],
                    'suffix_text' => $datam['suffix_text'],
                ])->find_one() ?: false;

            if ($found === false) {
                ORM::for_table('poses')
                    ->create()
                    ->set([
                        'bot_id'  => $this->SETTING->ID,
                        'en'      => F::is_en($datam['prefix']['surface']),
                        'type'    => $datam['prefix']['pos'],
                        'text'    => $datam['prefix']['surface'],
                        'reading' => $datam['prefix']['reading'],
                        'suffix_text' => $datam['suffix_text'],
                        'suffix_type' => $datam['suffix_type'],
                        'updated_at'  => $now,
                        'created_at'  => $now,
                    ])->save();
            }
        }

	}

    /**
     * 文章作成
     * @param string $sentence
     * @return string
     */
    private function create_message ($sentence) {

        // スペース区切り
        $spaced = F::is_spaced($sentence);

        $origin_text = ($spaced)
            ? str_replace([' ', '　'], '', $sentence)
            : $sentence;

        $ma_map = $this->Y->getSurfaceMap($origin_text, $this->SPECIAL_NAMES);

        if (count($ma_map) <= 0) return '';

        // 全名詞置換モード（or）
        $do_replace_all_nouns =
            // 名詞のみ
            ( count($ma_map) <= 1 && F::is_noun($ma_map[0]) )
            // 改行
            || F::is_nl($sentence)
            // 同名詞複数
            || call_user_func(function($ma_map){
                $found = [];
                foreach ($ma_map as $ma) {
                    if (F::is_noun($ma) && !F::is_not_word($ma['surface'], true)) {
                        if (in_array($ma['surface'], $found)) return true;
                    }
                    array_push($found, $ma['surface']);
                }
                return false;
            }, $ma_map);

        $my_text = ($do_replace_all_nouns)
            ? $this->mode_replace_all_nouns($ma_map)
            : $this->mode_replace_sequence_poses($ma_map);

        if ($origin_text === $my_text) {
            echo "! befor_text eq after_text\n";
            $my_text = $this->mode_replace_all_nouns($ma_map);
        }

        if ($spaced) {
            if (preg_match_all('/./u', $my_text, $m)) {
                $my_text = implode('　', $m[0]);
            }
        }

        return $my_text;
    }

    /**
     * 全名詞置換モード
     * @param array $ma_map
     * @return string
     */
    private function mode_replace_all_nouns ($ma_map) {

        echo "do_full_replace_nouns\n";

        $replaced = [];
        $text = '';

        foreach ($ma_map as $ma) {
            $surface = $ma['surface'];
            if (!F::is_noun($ma) || F::is_not_word($surface, $ma['pos'] === '名詞') || F::is_special($ma)) {
                $text .= $surface;
            } else if (isset($replaced[ $surface ])) {
                $text .= $replaced[ $surface ];
            } else {
                $row = Model::findRandNoun($this->SETTING->ID, F::is_en($surface));
                $after = (!$row) ? $surface : $row['text'];
                $text .= $after;
                $replaced[ $surface ] = $after;
            }
        }

        return $text;
    }

    /**
     * 品詞出現順置換モード
     * @param array $ma_map
     * @return string
     */
    private function mode_replace_sequence_poses ($ma_map) {

        echo "do_replace_sequence_poses\n";

        $ma_map_map = [];

        foreach ($ma_map as $ma) {
            if (F::is_special($ma)) {
                array_push($ma_map_map, [ $ma ]);
                continue;
            }
            if (F::is_splitpos($ma)) {
                array_push($ma_map_map, [ $ma ]);
            } else {
                $index = count($ma_map_map) - 1;
                if (isset($ma_map_map[$index])) {
                    $befor = $ma_map_map[$index][0];
                    if (!F::is_special($befor)) {
                        array_push($ma_map_map[$index], $ma);
                        continue;
                    }
                }
                array_push($ma_map_map, [$ma]);
            }
        }

        $text = '';
        $replaced = [];
        $last_index = count($ma_map_map) - 1;

        foreach ($ma_map_map as $i => $ma_map) {

            if (count($ma_map) <= 1) {
                if (F::is_special($ma_map[0]) || F::is_not_word($ma_map[0]['surface'], $ma_map[0]['pos'] === '名詞')) {
                    $text .= $ma_map[0]['surface'];
                    continue;
                }
            }

            $befor  = '';
            $after  = null;
            $prefix = '';
            $suffix = '';
            $en = false;

            foreach ($ma_map as $ma) {
                $befor .= $ma['surface'];
                if (F::is_splitpos($ma)) {
                    $prefix = $ma['pos'];
                    $en = F::is_en($ma['surface']);
                } else {
                    $suffix .= $ma['pos'];
                }
            }

            if (isset($replaced[ $befor ])) {
                $text .= $replaced[ $befor ];
                continue;
            }

            // last
            if ($last_index === $i) {
                $text .= $befor;
                continue;
            }

            //echo "{$prefix}, {$suffix}\n";
            $row = Model::findRandPosByType($this->SETTING->ID, $en, $prefix, $suffix);
            if (!$row) {
                $after = $befor;
            } else {
                $after = ($prefix ? $row['text'] : '') . ($suffix ? $row['suffix_text'] : '');
            }

            $text .= $after;
            $replaced[ $befor ] = $after;
        }

        return $text;
    }

    /**
     * 顔文字
     * @return string
     */
    private function mode_kaomoji () {
        $row = Model::findRandKaomoji($this->SETTING->ID);
        return $row['text'];
    }

    /**
     * ふくわらい
     * @param $text
     * @return mixed
     */
    private function mode_fukuwarai ($text) {
        $kaomoji = F::kaomoji($text);
        if ($kaomoji !== '') {
            $row = Model::findRandKaomoji($this->SETTING->ID, true);
            if (!!($row)) {
                $text = preg_replace("/\\(.*?\\)/iu", $row['text'], $text);
            }
        }
        return $text;
    }

    /**
     * じゃが
     * @param string $sentence
     * @param string $word
     * @return string
     */
    private function mode_jaga ($sentence, $word = 'じゃが') {
        $ma_map = $this->Y->getSurfaceMap($sentence, $this->SPECIAL_NAMES);
        $text = '';
        foreach ($ma_map as $ma) {
            if (!F::is_noun($ma) && !F::is_special($ma)) {
                $text .= $word;
            } else {
                $text .= $ma['surface'];
            }
        }
        return $text;
    }

    /**
     * 名詞定形置換
     * @param string $sentence
     * @param array $nouns
     * @return string
     */
    private function mode_random_nouns ($sentence, $nouns) {
        $ma_map = $this->Y->getSurfaceMap($sentence, $this->SPECIAL_NAMES);
        $text = '';
        foreach ($ma_map as $ma) {
            if (F::is_noun($ma) && !F::is_special($ma) && F::is_not_word($ma['surface'])) {
                $text .= F::array_sample($nouns);
            } else {
                $text .= $ma['surface'];
            }
        }
        return $text;
    }

    /**
     * なそ
     * にん
     * @param string $sentence
     * @returns string
     */
    private function mode_sonnani ($sentence) {
        // 「そんなに！」
        if (preg_match('/^[あ-ん]{4}[^ぁ-ん]?$/su', $sentence) > 0) {
            preg_match_all('/[あ-ん]/su', $sentence, $matches);
            echo "mode そんなに！\n";
            $chars = $matches[0];
            return "{$chars[2]}{$chars[0]}\n{$chars[3]}{$chars[1]}\n！";
        } else {
            return $sentence;
        }
    }

    /**
     * バグる
     * @param string $sentence
     * @return string
     */
    private function mode_bug ($sentence) {
        $ma_map = $this->Y->getSurfaceMap($sentence, $this->SPECIAL_NAMES);
        $index = mt_rand(0, count($ma_map) - 1);
        $text = '';
        foreach ($ma_map as $i => $ma) {
            $text .= ($index > $i)
                ? $ma['surface'] : $ma_map[$index]['surface'];
        }
        return $text;
    }

    /**
     * 台なしにする何か
     * @return string
     */
    private function mode_random () {
        // 定型文の取得
        $text = F::array_sample($this->SETTING->ASSETS);
        if (strpos($text, '{0}') !== false && preg_match_all('/(\{\d\})/u', $text, $matches)) {
            $keys = array_unique($matches[1]);
            foreach ($keys as $k) {
                $row = Model::findRandNoun($this->SETTING->ID);
                $text = str_replace($k, $row['text'], $text);
            }
        }
        return $text;
    }

    /**
     * やめとくねモード
     * @param string $sentence
     * @return string
     */
    private function mode_yametokune ($sentence) {
        $len = mb_strlen($sentence);
        return mb_substr($sentence, 0, $len-1) . '…やめとくね。';
    }

    /**
     * 正規化
     * @param string $sentence
     * @return string
     */
    private function filter_before($sentence){
        $sentence = F::strip_reply($sentence);
        $sentence = F::strip_hashtag($sentence);
        $sentence = str_replace(['@','＠'], '©', $sentence);
        return trim($sentence);
    }

    /**
     * 無害化
     * @param string $sentence
     * @param string $end_char
     * @return string
     */
    private function filter_after($sentence, $end_char){
        // 140文字超えてたら削って適当な文字をつける
        if (mb_strlen($sentence) > 140) {
            $sentence = mb_substr($sentence, 0, 140 - mb_strlen($end_char)) . $end_char;
        }
        return $sentence;
    }

    /**
     * 発言する
     * @param string $message
     * @param string $reply_id
     * @return mixed|null
     */
    protected function send_tweet($message, $reply_id = null) {

        if (!$message) {
            echo "empty text.\n";
            return null;
        }

        $message = $this->filter_before($message);

        if ($reply_id !== null) {
            $reply = $this->T->statuses_show(['id' => $reply_id]);
            if (!$reply || isset($reply['errors'])) {
                echo "reply error.\n";
                return null;
            }
            $message = "@{$reply['user']['screen_name']} {$message}";
        }

        // 防壁
        $message = $this->filter_after($message, $this->SETTING->SUFFIX);

        try {
            // 発言
            if (!$this->SETTING->DEBUG) {
                $status = $this->T->statuses_update($message, $reply_id);
                if (!$status) {
                    return null;
                } else {
                    return $status;
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        return null;
    }

    /**
     * ログ保存
     * @param array $status
     * @param $origin_status
     */
    private function update_log ($status, $origin_status) {
        ORM::for_table('logs')
            ->create()
            ->set([
                'id_str' => $status['id_str'],
                'bot_id' => $this->SETTING->ID,
                'text'   => $status['text'],
                'origin_id_str' => $origin_status['id_str'],
                'origin_text'   => $origin_status['text'],
                'created_at'    => F::mysql_datetime(time()),
            ])->save();
    }

    /**
     * フォロー更新
     */
    public function task_friendship(){

        $followers_ids = $this->T->followers_ids([
            'screen_name' => $this->SETTING->SCREEN_NAME
        ]);

        $friends_ids = $this->T->friends_ids([
            'screen_name' => $this->SETTING->SCREEN_NAME
        ]);

        if (count($followers_ids) <= 0 || count($friends_ids) <= 0) {
            return;
        }

        $this->follow($followers_ids, $friends_ids);
        $this->remove($followers_ids, $friends_ids);

    }

    /**
     * フォロー
     * @param array $followers_ids
     * @param array $friends_ids
     */
    private function follow ($followers_ids, $friends_ids) {

        $diff = array_diff($followers_ids, $friends_ids);
        shuffle($diff);

        foreach ($diff as $i => $user_id) {
            if ($i >= self::REQUEST_FOLLOW_MAX) break;

            $data = $this->T->friendships_show([
                'source_screen_name' => $this->SETTING->SCREEN_NAME,
                'target_id' => $user_id
            ]);

            if (!isset($data['relationship'])) {
                continue;
            }

            $bot  = $data['relationship']['source'];
            $user = $data['relationship']['target'];

            if ($bot['following'] === false && $user['following'] === true) {
                F::log(LOG_FILE, "{$this->SETTING->SCREEN_NAME} friendships_create: {$user_id} {$user['screen_name']}");
                $this->T->friendships_create(['user_id' => $user_id]);
            }
        }

    }

    /**
     * リムーブ
     * @param array $followers_ids
     * @param array $friends_ids
     */
    private function remove ($followers_ids, $friends_ids){

        $diff = array_diff($friends_ids, $followers_ids);
        shuffle($diff);

        foreach ($diff as $i => $user_id) {
            if ($i >= self::REQUEST_FOLLOW_MAX) break;

            $data = $this->T->friendships_show([
                'source_screen_name' => $this->SETTING->SCREEN_NAME,
                'target_id' => $user_id
            ]);

            if (!isset($data['relationship'])) {
                continue;
            }

            $bot  = $data['relationship']['source'];
            $user = $data['relationship']['target'];

            if ($bot['following'] === true && $user['following'] === false) {
                F::log(LOG_FILE, "{$this->SETTING->SCREEN_NAME} friendships_destroy: {$user_id} {$user['screen_name']}");
                $this->T->friendships_destroy(['user_id' => $user_id]);
            }
        }

    }

}
