<?php

/**
 * Class YahooMa
 * Yahoo! 日本語形態素解析API
 * @see http://developer.yahoo.co.jp/webapi/jlp/ma/v1/parse.html
 */

class YahooMa
{
    const YAHOOMA_URL  = 'http://jlp.yahooapis.jp/MAService/V1/parse';

    protected $apiKey;

    function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    /**
     * 形態素解析APIの解析結果を返す
     * @param string $sentence
     * @return array XMLを変換した連想配列
     */
    private function request($sentence) {

        $data  = [
            'appid'    => $this->apiKey,
            'results'  => 'ma',//'uniq',
            'sentence' => $sentence
        ];

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
            $contents = file_get_contents(self::YAHOOMA_URL, false, stream_context_create($options));
        } catch (Exception $e) {
        	echo "YahooMA API通信失敗\n";
            exit($e->getMessage());
        }

        $contents = str_replace( ["\r\n", "\r", "\n"], "\\n", $contents );
        $xml = simplexml_load_string($contents);
        $json = json_encode($xml);
        $maHash = json_decode($json, true);

        if ($maHash === null
        || !isset($maHash['ma_result']['word_list']['word'])
        ) { return []; }

        $words = $maHash['ma_result']['word_list']['word'];

        // 1件のときは配列にならない
        if (!isset($words[0])) {
            $words = [$words];
            $maHash['ma_result']['word_list']['word'] = $words;
        }

        // 改行戻し
        foreach ($words as $i => $word) {
            if ($word['surface'] === "\\n") {
                $maHash['ma_result']['word_list']['word'][$i]['surface'] = "\n";
            }
        }

        return $maHash;
    }

    /**
     * 出現順を保つ品詞情報を返す
     * @param $sentence
     * @param array $dics
     * @return array
     */
    public function getSurfaceMap($sentence, $dics = []){

        if (!$sentence) return [];

        // 特殊名詞
        $sym = '<>';
        $dic_map = [];
        foreach ($dics as $dic) {
            if (mb_strpos($sentence, $dic) !== false) {
                $sentence = str_replace($dic, $sym, $sentence);
                $dic_map[$sym] = $dic;
                $sym .= '<>';
            }
        }

        $buf_map  = [];
        $dic_map  = array_reverse($dic_map);
        $xml_map  = $this->request($sentence);
        $word_map = $xml_map['ma_result']['word_list']['word'];
        $beforPos = '';

        foreach ($word_map as $word) {

            $surface = self::replaceSpace($word['surface']);
            $reading = self::replaceSpace($word['reading']);

            // 連続した品詞を一単語として扱う
            if ($beforPos === $word['pos']) {
                $idx = count($buf_map) - 1;
                $buf_map[$idx]['surface'] .= $surface;
                $buf_map[$idx]['reading'] .= $reading;
            } else {
                array_push($buf_map, [
                    'surface' => $surface,
                    'reading' => $reading,
                    'pos'     => $word['pos']
                ]);
            }

            $beforPos = $word['pos'];
        }

        // 特殊名詞戻し
        if (count($dic_map) > 0) {
            foreach ($buf_map as &$ma) {
                foreach ($dic_map as $key => $dic) {
                    $surface = $ma['surface'];
                    // echo  $key, ",", $dic, "," , $surface,  "\n";
                    if (mb_strpos($surface, $key) !== false) {
                        $surface = str_replace($key, $dic, $surface);
                        $ma['surface'] = $surface;
                    }
                }
            }
        }

        return $buf_map;
    }

    /**
     * 品詞をキーとした連想配列を返す。
     * @param $sentence
     * @return array
     */
    public function getPosMap($sentence){

        $xmlObj = $this->request($sentence);
        $wordAry = $xmlObj['ma_result']['word_list']['word'];
        $beforPos = '';
        $results = [];

        // 品詞をキーとした連想配列に加工
        foreach ($wordAry as $cur)
        {
            $pos = $cur['pos'];
            $surface = trim(self::replaceSpace($cur['surface']));
            $reading = trim(self::replaceSpace($cur['reading']));
            
            // 品詞名
            if (!isset($results[$pos])) {
                $results[$pos] = [];
            }

            // 品詞が連続していたら一つの単語として扱う
            if ($pos === $beforPos && !is_numeric($surface)) {
                $idx = count($results[ $pos ]) - 1;
                $results[$pos][$idx]['surface'] .= $surface;
                $results[$pos][$idx]['reading'] .= $reading;
            } else {
                if (!in_array($surface, $results[$pos])) {
                    array_push($results[$pos], [
                        'surface' => $surface,
                        'reading' => $reading
                    ]);
                }
            }

            $beforPos = $pos;
        }

        return $results;
    }

    /**
     * 文字列以外をスペースに変換する
     * @param $surface
     * @return string
     */
    private function replaceSpace ($surface) {
        // 空白文字は配列に変換されるので0番目を使う
        if (is_array($surface)) {
            return ' ';
        } else {
            return $surface;
        }
    }

    /**
     * maMapに指定した品詞が含まれるか検索する
     * @param $surface
     * @param $maMap
     * @return int|string
     */
    public function indexOf ($surface, $maMap) {
        foreach ($maMap as $index => $ma) {
            if ($surface === $ma['surface']) {
                return $index;
            }
        }
        return -1;
    }

    /**
     * 品詞Arrayを文書変換
     * @param array $maMap
     * @return string
     */
    public function getText ($maMap) {
        $text = '';
        foreach ($maMap as $ma) {
            $text .= $ma['surface'];
        }
        return $text;
    }

}
