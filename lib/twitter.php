<?php

/**
 * Class Twitter
 * use abraham-twitteroauth.php
 */
class Twitter
{
    const API_HOST = 'https://api.twitter.com/1.1/';
    const SEARCH_HOST = 'https://api.twitter.com/1.1/search/tweets.json?';

    protected $oauth;

    function __construct ($key, $skey, $token, $stoken) {
        $this->oauth = new TwitterOAuth(
            $key,   // consumer_key
            $skey,  // consumer_secret
            $token, // access_token
            $stoken // access_token_secret
        );
    }

    private function oauth_json ($url, $params, $method = 'GET') {
        $json = $this->oauth->OAuthRequest($url, $method, $params);
        if (!$json) return null;
        $statuses = json_decode($json, true);
        return $statuses;
    }

    public function statuses_update($text, $in_reply_to_status_id = null) {
        $param = ['status'  =>  $text];
        if ($in_reply_to_status_id !== null) {
            $param['in_reply_to_status_id'] = $in_reply_to_status_id;
        }
        return $this->oauth_json(self::API_HOST.'statuses/update.json', $param, 'POST');
    }

    public function users_show ($name) {
        return $this->oauth_json(self::API_HOST.'users/show.json', ['screen_name' => $name]);
    }

    public function user_timeline ($param) {
        return $this->oauth_json(self::API_HOST.'statuses/user_timeline.json', $param);
    }

    public function favorites_create ($param) {
        return $this->oauth_json(self::API_HOST.'favorites/create.json', $param, 'POST');
    }

    public function statuses_show ($param) {
        return $this->oauth_json(self::API_HOST.'statuses/show.json', $param);
    }

    public function favorites_destroy ($statusId) {
        return $this->oauth_json(self::API_HOST.'favorites/destroy.json', ['id' => $statusId], 'POST');
    }

    public function friends_timeline ($count = 100) {
        return $this->oauth_json(self::API_HOST.'statuses/home_timeline.json', ['count' => $count]);
    }

    public function mentions_timeline ($count = 20) {
        return $this->oauth_json(self::API_HOST.'statuses/mentions_timeline.json', ['count' => $count]);
    }

    public function followers_ids ($param) {
        return $this->ids('followers', $param);
    }

    public function friends_ids ($param) {
        return $this->ids('friends', $param);
    }

    private function ids ($api_name, $param) {
        $ids = [];
        $cursor = -1;

        do {
            $data = $this->oauth_json(self::API_HOST . "{$api_name}/ids.json", [
                'screen_name' => $param['screen_name'],
                'cursor' => $cursor
            ]);
            if (isset($data['errors'])) {
                print_r($data);
                return [];
            }
            $ids = array_merge($ids, $data['ids']);
            $cursor = $data['next_cursor_str'];
        } while ($cursor > 0);

        echo "{$api_name} ". count($ids). "\n";

        return $ids;
    }

    public function friendships_show ($params){
        return $this->oauth_json(self::API_HOST.'friendships/show.json', $params);
    }

    public function friendships_create ($params) {
        return $this->oauth_json(self::API_HOST.'friendships/create.json', $params, 'POST');
    }

    public function friendships_destroy ($params) {
        return $this->oauth_json(self::API_HOST.'friendships/destroy.json', $params, 'POST');
    }

    public function search ($query_data) {
        return $this->oauth_json(self::SEARCH_HOST, $query_data);
    }

}