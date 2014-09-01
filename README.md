bot-project
===========

**にせBOT**

PHP 5.4以上 node 0.10以上 MySQL または MariaDB それなりに新しいの

nodemodule入れてね。

```
$ npm install  
```

schemeのsql流してね。  



既存の発言拾ってね。
```
$ cd bot-project  
$ php nise_bot/main.php botname upstream  
```

**config/**  
DBの接続情報入れる

**nise_bot/inc/**  
jsonの説明を見て設定する、ファイル名とシェルの引数をあわせる。

**log/**  
書き込み権限つけてね。

**shell/**  
引数は設定のjsonと合わせる  
20m - 20分毎のcron  
60m - 毎時のcron  
1d - 毎日のcron  
async_stream - nodeのユーザーストリーム監視起動

**使うのに登録が必要なもの**  
TwitterAPI - https://dev.twitter.com  
Yahoo!日本語形態素解析API - http://developer.yahoo.co.jp/webapi/jlp/ma/v1/parse.html
