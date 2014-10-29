/**
 * @file stream.js
 */

// local vars
var SETTING, twit,
    PATTERNS = {
        YO: null,
        REPLY: null
    },
    NAME = '';

var streamer, Streamer = function () {
    this.initialize.apply(this, arguments);
    streamer = this;
};

Streamer.prototype = {

    initialize: function (name) {
        NAME = name;
        SETTING = require('./inc/' + NAME + '.json');
        PATTERNS.REPLY = new RegExp('^@' + SETTING['SCREEN_NAME'] + '[\\s\\S]*$', 'mi');
        PATTERNS.YO = new RegExp('^@' + SETTING['SCREEN_NAME'] + '[\\s]+yo(?:[\\s]|$)', 'i');

        if (!(SETTING['ORIGIN_NAME'] instanceof Array)) {
            SETTING['ORIGIN_NAME'] = [ SETTING['ORIGIN_NAME'] ];
        }

        twit = new Twit({
            'consumer_key'        : SETTING['KEY']['consumer_key'],
            'consumer_secret'     : SETTING['KEY']['consumer_secret'],
            'access_token'        : SETTING['KEY']['access_token'],
            'access_token_secret' : SETTING['KEY']['access_token_secret']
        });
    },

    /**
     * UserStreamの作成
     */
    open: function () {

        twit.stream('user')
            .on('tweet', function (tweet) {

                if (!tweet) return;

                var text = (tweet['text']||'').trim();

                // たまに壊れたデータが入ってくる
                if (typeof tweet.user === 'undefined' || typeof tweet.user.screen_name === 'undefined') {
                    streamer.log('user: undefined.');
                    console.error(typeof tweet);
                    console.error(tweet);
                    return;
                }

                // RT
                if (streamer.isRT(tweet)) {
                    // console.log('RT');
                    return;
                }
                // リプライ
                if (streamer.isReply(text)) {
                    streamer.log('reply');
                    var react;
                    // YO
                    if (streamer.isYo(text)) {
                        streamer.execPhp('yo', tweet['id_str']);
                    } else if (react = streamer.getReact(text)) {
                        streamer.execPhp('react', tweet['id_str'], react)
                    } else {
                        streamer.execPhp('reply', tweet['id_str']);
                    }
                    return;
                }
                // 本人
                if (SETTING['ORIGIN_NAME'].indexOf(tweet['user']['screen_name']) !== -1) {
                    // 診断
                    if (streamer.hasShindanUrl(tweet)) {
                        streamer.execPhp('shindan', streamer.getShindanId(tweet));
                    }
                }
            });
    },

    /**
     * Reply判定
     * @param {string} text
     * @returns {boolean}
     */
    isReply: function(text){
        return PATTERNS.REPLY.test(text);
    },

    /**
     * Yo
     * @param {string} text
     * @returns {boolean}
     */
    isYo: function(text){
        return PATTERNS.YO.test(text);
    },

    /**
     * 外部PHPタスク実行
     * @param {*} var_args
     */
    execPhp: function (var_args) {
        var args = Array.prototype.slice.call(arguments),
            command = 'php nise_bot/main.php ' + NAME + ' ' + args.join(' ');
        streamer.log('command:', command);

        setTimeout(function(){
            exec(command, function (err, stdout, stderr) {
                console.log('error:', err);
                console.log('stderr:', stderr);
                console.log('stdout:\n', stdout.replace('\\n', '\n'));
            });
        }, 10 * 1000);
    },

    /**
     * 診断メーカー
     * @param {object} tweet
     * @returns {boolean}
     */
    hasShindanUrl: function (tweet) {
        if (typeof tweet.entities.urls === 'undefined' || tweet.entities.urls.length <= 0) return false;

        return tweet.entities.urls.some(function(obj){
            return obj.expanded_url.indexOf('shindanmaker.com') !== -1
        });
    },

    /**
     * 診断メーカーID
     * @param {object} tweet
     * @returns {string}
     */
    getShindanId: function (tweet) {
        var id = null;
        tweet.entities.urls.some(function(obj){
            if (obj.expanded_url.indexOf('shindanmaker.com') !== -1) {
                var m = obj.expanded_url.match(/shindanmaker\.com\/([\d]+)/)||[];
                if (m.length > 0) {
                    id = m[1];
                    return true;
                } else {
                    return false;
                }
            }
        });
        return id;
    },

    /**
     * RT
     * @param {object} tweet
     * @returns {boolean}
     */
    isRT: function (tweet) {
        return typeof tweet['retweeted_status'] !== 'undefined';
    },

    /**
     * 反応
     * @param text
     * @returns {string}
     */
    getReact: function (text) {
        var word = null;

        SETTING['REACTS'].some(function(react){
            if (text.indexOf(react) !== -1) {
                word = react;
                return true;
            }
            return false;
        });

        return word;
    },

    /**
     * logger
     */
    log: function (str) {
        console.log(SETTING['SCREEN_NAME'], str);
    }

};

module.exports = Streamer;
