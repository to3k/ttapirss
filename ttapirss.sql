SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `ttapirss` (
  `id` int(11) NOT NULL,
  `nazwa` text COLLATE utf8_polish_ci NOT NULL,
  `user_nick` text COLLATE utf8_polish_ci NOT NULL,
  `user_id` text COLLATE utf8_polish_ci NOT NULL,
  `last_tweet_id` text COLLATE utf8_polish_ci NOT NULL,
  `sprawdzenie` text COLLATE utf8_polish_ci NOT NULL,
  `aktualizacja` text COLLATE utf8_polish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

ALTER TABLE `ttapirss`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `ttapirss`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;