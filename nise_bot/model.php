<?php

namespace nise_bot\model;

use ORM, F;

class Model {

    static function findRandNoun ($bot_id, $en = null) {
        $where = [
            'bot_id' => $bot_id,
            'type' => '名詞',
        ];
        if ($en !== null) {
            $where['en'] = $en;
        }
        return ORM::for_table('poses')
            ->where($where)
            ->where_raw('created_at > ?', [ F::mysql_datetime(time() - F::day_time(60)) ])
            ->order_by_expr('rand()')
            ->find_one();
    }

    static function findRandPos ($bot_id, $en = null) {
        $where = [
            'bot_id' => $bot_id,
        ];
        if ($en !== null) {
            $where['en'] = $en;
        }
        return ORM::for_table('poses')
            ->where($where)
            ->where_raw('created_at > ?', [ F::mysql_datetime(time() - F::day_time(60)) ])
            ->order_by_expr('rand()')
            ->find_one();
    }

    static function findRandPosByType ($bot_id, $en, $prefix = null, $suffix = null) {
        $where = [
            'bot_id' => $bot_id,
            'en' => $en
        ];

        if (!!$prefix) {
            $where['type'] = $prefix;
        }
        if (!!$suffix) {
            $where['suffix_type'] = $suffix;
        }

        return ORM::for_table('poses')
            ->where($where)
            ->where_raw('created_at > ?', [ F::mysql_datetime(time() - F::day_time(60)) ])
            ->order_by_expr('rand()')
            ->find_one();
    }

    static function findRandStatus ($bot_id) {
        return ORM::for_table('statuses')
            ->where('bot_id', $bot_id)
            ->where_raw('created_at > ?', [ F::mysql_datetime(time() - F::day_time(60)) ])
            ->order_by_expr('rand()')
            ->find_one();
    }

    static function findRandKaomoji ($bot_id, $face = false) {
        $where = ['bot_id' => $bot_id];
        if ($face) {
            $where['face'] = true;
        }
        return ORM::for_table('kaomoji')->where($where)->order_by_expr('rand()')->find_one();
    }

    static function findRandStatusMl ($bot_id) {
        return ORM::for_table('statuses')
            ->where([
                'bot_id' => $bot_id,
                'lf' => true
            ])
            ->where_raw('created_at > ?', [ F::mysql_datetime(time() - F::day_time(60)) ])
            ->order_by_expr('rand()')
            ->find_many();
    }
}