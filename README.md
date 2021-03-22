ttapirss
===============
Twitter API RSS Feed
------------
Od zawsze najwygodniejszą formą konsumowania treści były dla mnie kanały RSS, a Twitter to medium społecznościowe, którego najczęściej i najaktywniej używam. Postanowiłem połączyć te dwie rzeczy. Niestety większość narzędzi, tworzących kanały RSS dla kont na Twitterze, jest płatnych. Oczywiście jest darmowy nitter.net, którego używałem do tej pory, jednak ostatnio zauważyłem jego problemy ze stabilnością, które są zapewne spowodowane sporą popularnością serwisu oraz ograniczeniami API Twittera dla deweloperów. W takiej sytuacji najlepiej siąść i stworzyć swoje własne narzędzie, które będzie szyte na miarę. Zrobiłem pod siebie, ale wrzucam tutaj, bo może komuś się przyda :)

Instalacja
------------
1. Uzyskać dostęp do API Twittera: https://developer.twitter.com/en/apply-for-access
2. Wrzucić na FTP:
+ TwitterAPIExchange.php - framework dla API Twittera zapożyczony z: https://github.com/J7mbo/twitter-api-php (działa z każdą wersją API - v1.1/v2)
+ api_config.php - wypełnić klucze i tokeny uzykasne z pkt. 1
+ (opcjonalne) favicon.ico - favikonka twittera, która będzie się wyświetlać w czytniku RSS
+ get_tweets.php - główny plik (pobiera dane z Twittera, formatuje je i tworzy pliki RSS kanałów)
+ mysql_config.php - wypełnić dane dostępowe do bazy MySQL
3. Utworzyć tabelę w bazie MySQL zgodnie z konfiguracją w pliku ttapirss.sql
4. Ustawić zadanie crona dla pliku get_tweets.php (najlepiej w interwale 5 minut, ale to też zależy od aktywności użytkownika, dla którego tworzymy kanał RSS)
```*/5 * * * *```

Jak używać?
------------
1. Aby utworzyć kanał RSS należy oddać rekord do bazy MySQL. Konieczne jest wypełnienie jedynie wartości nazwa (dowolna nazwa pod jaką będzie widniał rekord) i user_nick (nick użytkownika na Twitterze @xxx).
```INSERT INTO `ttapirss` (`nazwa`, `user_nick`) VALUES ('<nazwa_użytkownika>', '<nick_użytkownika>');```
2. Po uruchomieniu pliku get_tweets.php (ręcznie lub przez cron) utworzy się na FTP plik twitter_<nick_użytkownika>.rss, który wystarczy podlinkować w czytniku RSS (np. InoReader).
