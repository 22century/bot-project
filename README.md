にせBOT
===========


PHP 5.4以上 node 0.10以上 MySQL または MariaDB それなりに新しいの

nodemodule入れてね。

```
$ cd bot-project  
$ npm install  
```

schemeのsql流してね。  

```
$ mysql -u ***** -p ***** nise_bot < scheme/nise_bot.sql
```

既存の発言拾ってね。
```
$ php nise_bot/main.php botname upstream  
$ php nise_bot/main.php botname migration  
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

**事例**  
https://twitter.com/nise_ajipo  
https://twitter.com/nise_xenop  
https://twitter.com/nise_22century  
https://twitter.com/iucidonn  
https://twitter.com/nise_moroya  


