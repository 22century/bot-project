<?php

/**
 * 汎用
 */
class F {

    const MYSQL_DATETIME = 'Y-m-d H:i:s';
    const PATTERN_DESU = '/^.*?(?:@[a-z0-9_]+ ?)*.*?([^ 　、。．，､｡]{4,})(?:で(?:す|した|ある|あり|しょう?)|だ(?:[おねぜわなし]|ろう?|のう|よね?|から))/iu';

    /**
     * 英数記号判定
     * @param string $thing
     * @return bool
     */
    static function is_en($thing){
        return preg_match('/^[a-z0-9\'%&!?_-]+$/iu', $thing) > 0;
    }

    /**
     * 改行チェック
     * @param string $thing
     * @return bool
     */
    static function is_nl($thing){
        return (preg_match('/[\r\n]/s', $thing) > 0);
    }

    /**
     * DB保存しない発言の判定
     * @param string $thing
     * @return bool
     */
    static function is_filter($thing){
        $thing = trim($thing);
        return
            // RT
            (preg_match('/^RT .*$/s', $thing) > 0)
            // Reply
            || (preg_match('/^[.]?@.*$/us', $thing) > 0)
            // リンク含んでる
            || (preg_match('/^.*https?:\/\/.*$/s', $thing) > 0)
            // なうぷれ・なうぶらとか
            || (preg_match('/^Now [a-z0-9]+ ?:.*$/is', $thing) > 0)
            // 少ない
            || (mb_strlen($thing, 'UTF-8') <= 2)
            // 日本人のはずなので英語だけとか認めない
            || (preg_match('/^[a-z0-9!?& ]+$/is', $thing) > 0);
    }

    /**
     * 単語として扱わないものを判定
     * @param string $thing
     * @param bool $noun
     * @return bool
     */
    static function is_not_word($thing, $noun = false){
        $thing = trim($thing);
        return
            // 半角2文字以下
            (strlen($thing) <= 2)
            // 数字から始まる
            || (preg_match('/^[0-9０-９]+.*$/u', $thing) > 0)
            // 英数字との混合
            || (!ctype_alnum($thing) && preg_match('/^[a-z0-9]$/iu', $thing) > 0)
            // 1文字
            || ( mb_strlen($thing, 'UTF-8') <= 1)
            // ひらがなカタカナ2文字（名詞の場合）
            || ($noun && preg_match('/^[ぁ-んァ-ヶー]{2}$/u', $thing) > 0 && preg_match('/^わし|ワシ|おれ|オレ|ぼく|ボク|きみ|キミ|あれ|アレ$/u', $thing) <= 0)
            // 記号から始まる
            || (preg_match('/^[ﾟﾞﾟ、。，…‥”＃＄％＆’（）＝〜｜｛｝＊｀＋＿？！＞＜\'"()\[\]=~¥#&+{}<>,.;:%^$!?*@_\/“‘`ー―-].*$/su', $thing) > 0)
            // 「人YV」だけ（突然の死対策）「ｗ」だけ
            || (preg_match('/^[人VYwｗ]+$/iu', $thing) > 0);
    }

    /**
     * テキストを逆順に
     * @param string $subject
     * @return string
     */
    static function reverseText($subject) {
        $maches = [];
        if (empty($subject)) return '';
        preg_match_all('/./u', $subject, $maches);
        $wordary = array_reverse($maches[0]);
        return join('', $wordary);
    }

    /**
     * リプライの除去
     * @param string $subject
     * @return mixed
     */
    static function strip_reply($subject) {
        return preg_replace('/@[a-z0-9_]+/iu', '', $subject);
    }

    /**
     * ハッシュタグの除去
     */
    static function strip_hashtag($subject) {
        return preg_replace('/#[\S]+/iu', '', $subject);
    }

    /**
     * 顔文字検出
     * @param string $subject
     * @return string
     */
    static function kaomoji($subject){
        $sym = '|｜\r\n><＞＜;:；：【】{}\[\]「」｢｣….,！？!?()（）ー〜ー−―一-龠々ぁ-んァ-ヶa-z0-9ｱ-ﾝﾞﾟｧ-ｫｬ-ｮｯｰ';
        $not = ' 　#＃_＿-';
        $pattern = "/^.*?([^{$sym}{$not}]*[(（][^{$sym}\\/]{3,}[）)][^{$sym}{$not}]*).*$/isu";
        $kao = '';
        if (preg_match($pattern, $subject, $matches) > 0) {
            $kao = trim($matches[1]);
        }
        return $kao;
    }

    /**
     * 単独の顔文字か判定（しかし括弧で括ってるかだけ）kaomojiの後に使う前提
     * @param string $subject
     * @return bool
     */
    static function is_face($subject){
        return preg_match("/^[(（].*[）)]$/isu", $subject) > 0;
    }

    /**
     * 名詞
     * @param array $ma
     * @return bool
     */
    static function is_noun ($ma) {
        return $ma['pos'] === '名詞';
    }

    /**
     * 分割対象か判定
     * @param array $ma 形容詞, 形容動詞, 名詞, 動詞, 感動詞
     * @return bool
     */
    static function is_splitpos ($ma) {
        return in_array($ma['pos'], ['形容詞', '形容動詞', '名詞', '動詞', '感動詞']);
    }

    /**
     * 特殊
     * @param array $ma 形容詞, 形容動詞, 名詞, 動詞, 感動詞
     * @return bool
     */
    static function is_special ($ma) {
        return $ma['pos'] === '特殊';
    }

    /**
     * MySQL格納日付
     * @param int|string $time
     * @return bool|string
     */
    static function mysql_datetime ($time) {
        return (is_numeric($time))
            ? date(self::MYSQL_DATETIME, $time)
            : date(self::MYSQL_DATETIME, strtotime($time));
    }

    /**
     * 日を秒換算する
     * @param int $day
     * @return int
     */
    static function day_time ($day) {
        return 60 * 60 * 24 * $day;
    }

    /**
     * Reply判定
     * @param string $text
     * @param string $name
     * @return bool
     */
    static function is_reply($text, $name){
        return strpos($text, "@{$name}") === 1;
    }

    /**
     * 配列からランダムに1件返す
     * @param array $arr
     * @return mixed
     */
    static function array_sample ($arr) {
        return $arr[ array_rand($arr) ];
    }

    /**
     * HTTP POST
     * @param string $url
     * @param array $data
     * @param bool $normalize
     * @return string
     */
    static function post ($url, $data, $normalize = false) {

        $contents = '';
        $query = http_build_query($data);
        $options = [
            'http' => [
                'method'  => 'POST',
                'content' => $query,
                'header'  => [
                    'Content-type: application/x-www-form-urlencoded',
                    'Content-Length:'.strlen($query),
                    'User-Agent: Mozilla'
                ]]];
        try {
            $contents = file_get_contents($url, false, stream_context_create($options));
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        if ($normalize) {
            $contents = preg_replace('/>[\t ]+</', '><', $contents);
            $contents = preg_replace('/^[\t ]+/m', ''  , $contents);
            $contents = preg_replace('/[\r\n]+/' , "\n", $contents);
        }

        return $contents;
    }

    /**
     * 診断メーカー
     * @param string|int $id
     * @param string $name
     * @return string
     */
    static function shindan_maker ($id, $name) {
        $html = self::post("http://shindanmaker.com/{$id}", [
            'u' => $name,
            'from' => ''
        ], true);

        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        if (!$dom) {
            return '';
        } else {
            $formElement = $dom->getElementById('forcopy');
            return trim($formElement->textContent);
        }
    }

    /**
     * ス　ペ　ー　ス　区　切　り
     * @param string $text
     * @return bool
     */
    static function is_spaced ($text) {
        $match_count = preg_match_all('/　| /u', $text);
        return ( $match_count > 2 && ((mb_strlen($text)/2)|0) === $match_count );
    }

    /**
     * 断定系の取得
     * @param $str
     * @return string|null
     */
    static function get_desu ($str) {
        return preg_match(self::PATTERN_DESU, $str, $matches)
            ? $matches[1] : null;
    }

    /**
     * ログ出力
     * @param string $fpath
     * @param string $data
     */
    static function log ($fpath, $data) {
        try {
            $buf = self::mysql_datetime(time()) . ' ' . trim($data)."\n";
            echo $buf;
            file_put_contents($fpath, utf8_encode($buf), FILE_APPEND);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

}
