<?php
	// MySQL ustawienia i połączenie
	require('mysql_config.php');
	$mysqli = mysqli_connect($host, $user, $pass, $nazwa_bazy) or die('Błąd 1: '.mysqli_error($mysqli));
	mysqli_set_charset($mysqli, 'utf8');
	
	// ustawienie strefy czasowej na +00 (UTC)
	date_default_timezone_set('UTC'); // standardowa strefa czasowa dla plików RSS
	
	// wskazanie plików z kluczami do Twitter API oraz frameworkiem
	require_once( 'api_config.php' );
	require_once( 'TwitterAPIExchange.php' );

	// ustawienia kluczy Twitter API, pobrane z pliku config.php
	$settings = array(
		'oauth_access_token' => TWITTER_ACCESS_TOKEN, 
		'oauth_access_token_secret' => TWITTER_ACCESS_TOKEN_SECRET, 
		'consumer_key' => TWITTER_CONSUMER_KEY, 
		'consumer_secret' => TWITTER_CONSUMER_SECRET
	);
	
	$zapytanie = 'SELECT * FROM ttapirss ORDER BY id ASC'; // [0] id, [1] nazwa, [2] user_nick, [3] user_id, [4] last_tweet_id, [5] sprawdzenie, [6] aktualizacja
	$wynik = mysqli_query($mysqli, $zapytanie);
	
	while($wiersz = mysqli_fetch_row($wynik))
	{
		$id = $wiersz[0];
		$nazwa = $wiersz[1];
		$user_nick = $wiersz[2];
		$user_id = $wiersz[3];
		$last_tweet_id = $wiersz[4];
		$lp = 0;
		$title = '';
		$link = '';
		$date = '';
		$description = '';
		$nowe_tweety = 'NIE';
		$plik_rss = '';
		
		// dla nowo dodanych rekordów - jeżeli w bazie nie ma jeszcze user_id
		if(empty($user_id))
		{
			// ustalenie user_id po nicku użytkownika
			$url = 'https://api.twitter.com/2/users/by/username/'.$user_nick;
			$requestMethod = 'GET';
			$getfield = '?';
			
			$twitter = new TwitterAPIExchange( $settings );
			$twitter->setGetfield( $getfield );
			$twitter->buildOauth( $url, $requestMethod );
			$response = $twitter->performRequest( true, array( CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0 ) );
			$nick_to_id = json_decode( $response, true );
			
			$user_id = $nick_to_id['data']['id'];
			
			$zmien = 'UPDATE ttapirss SET user_id=\''.$user_id.'\' WHERE id=\''.$id.'\'';
			mysqli_query($mysqli, $zmien);
		}
		
		// pobiera tweety z danego konta
		$url = 'https://api.twitter.com/2/users/'.$user_id.'/tweets';
		$requestMethod = 'GET';
		$getfield = '?max_results=10&exclude=replies&since_id=1&tweet.fields=entities,created_at&expansions=referenced_tweets.id';
		
		$twitter = new TwitterAPIExchange( $settings );
		$twitter->setGetfield( $getfield );
		$twitter->buildOauth( $url, $requestMethod );
		$response = $twitter->performRequest( true, array( CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0 ) );
		$tweets = json_decode( $response, true );
		
		if($tweets['meta']['newest_id'] > $last_tweet_id)
		{
			$nowe_tweety = 'TAK';
			
			$plik_rss = '
					<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"
					xmlns:content="http://purl.org/rss/1.0/modules/content/"
					xmlns:wfw="http://wellformedweb.org/CommentAPI/"
					xmlns:dc="http://purl.org/dc/elements/1.1/"
					xmlns:atom="http://www.w3.org/2005/Atom"
					xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
					xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
					>
					<channel>
					<title>Twitter - '.$nazwa.' (@'.$user_nick.')</title>
					<atom:link href="https://twitter.to3k.pl/twitter_'.$user_nick.'.rss" rel="self" type="application/rss+xml" />
					<link>https://twitter.to3k.pl/twitter_'.$user_nick.'.rss</link>
					<description>Kanał RSS Twitter - '.$nazwa.' (@'.$user_nick.')</description>
					<lastBuildDate>'.date('r').'</lastBuildDate>
					<language>pl-PL</language>
					<sy:updatePeriod>hourly</sy:updatePeriod>
					<sy:updateFrequency>1</sy:updateFrequency>
					<image>
					<url>favicon.ico</url>
					<width>32</width>
					<height>32</height>
					</image>
			';
		}
		
		echo '----------'.$nazwa.' (@'.$user_nick.')----------<br>';
		foreach ( $tweets['data'] as $tweet ) :
			$lp++;
			//TITLE
			$title = $tweet['text'].' ';
			foreach ( $tweet['entities']['urls'] as $urls ) :
				$title = str_replace($urls['url'], $urls['expanded_url'], $title);
			endforeach;
			$title = preg_replace('/(https:\/\/twitter.com\/.+?\/status\/.+?\/photo\/1 )/i', '[FOTO]', $title);
			$title = preg_replace('/(https:\/\/twitter.com\/.+?\/status\/.+?\/video\/1 )/i', '[WIDEO]', $title);
			$title = preg_replace('/(https:\/\/twitter.com\/.+?\/status\/.+? )/i', '[STATUS]', $title);
			echo $lp.'. Title: '.$title.'<br>';
					
			//LINK
			$link = 'https://twitter.com/'.$user_nick.'/status/'.$tweet['id'];
			echo 'Link: '.$link.'<br>';
					
			//DATE
			$date = date('r', strtotime($tweet['created_at']));
			echo 'pubDate: '.$date.'<br>';
					
			//DESCRIPTION
			$tresc = '';
			$image = '';
			if($tweet['referenced_tweets'][0]['type'] == 'quoted')
			{
				$url = 'https://api.twitter.com/2/tweets/'.$tweet['referenced_tweets'][0]['id'];
				$requestMethod = 'GET';
				$getfield = '?tweet.fields=entities';
							
				$twitter = new TwitterAPIExchange( $settings );
				$twitter->setGetfield( $getfield );
				$twitter->buildOauth( $url, $requestMethod );
				$response = $twitter->performRequest( true, array( CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0 ) );
				$retweet = json_decode( $response, true );
						
				$tresc = $retweet['data']['text'];
				foreach ( $retweet['data']['entities']['urls'] as $urls ) :
					$tresc = str_replace($urls['url'], $urls['expanded_url'], $tresc);
				endforeach;
				
				$url = 'https://api.twitter.com/1.1/statuses/show.json';
				$requestMethod = 'GET';
				$getfield = '?id='.$retweet['data']['id'];
				$twitter = new TwitterAPIExchange( $settings );
				$twitter->setGetfield( $getfield );
				$twitter->buildOauth( $url, $requestMethod );
				$response = $twitter->performRequest( true, array( CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0 ) );
				$onetweet = json_decode( $response, true );
				
				if(!empty($onetweet['entities']['media'][0]['media_url_https']))
				{
					$image = $onetweet['entities']['media'][0]['media_url_https'];
				}
				elseif(!empty($onetweet['quoted_status']['entities']['media'][0]['media_url_https']))
				{
					$image = $onetweet['quoted_status']['entities']['media'][0]['media_url_https'];
				}
			}
			$url = 'https://api.twitter.com/1.1/statuses/show.json';
			$requestMethod = 'GET';
			$getfield = '?id='.$tweet['id'];
			$twitter = new TwitterAPIExchange( $settings );
			$twitter->setGetfield( $getfield );
			$twitter->buildOauth( $url, $requestMethod );
			$response = $twitter->performRequest( true, array( CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0 ) );
			$onetweet = json_decode( $response, true );
			if(!empty($onetweet['retweeted_status']['quoted_status']['text']))
			{
				$tresc = $onetweet['retweeted_status']['quoted_status']['text'];
				foreach ( $onetweet['retweeted_status']['quoted_status']['entities']['media'] as $urls ) :
					$tresc = str_replace($urls['url'], $urls['expanded_url'], $tresc);
				endforeach;
			}
			if(!empty($onetweet['entities']['media'][0]['media_url_https']))
			{
				$image = $onetweet['entities']['media'][0]['media_url_https'];
			}
			elseif(!empty($onetweet['retweeted_status']['quoted_status']['entities']['media'][0]['media_url_https']))
			{
				$image = $onetweet['retweeted_status']['quoted_status']['entities']['media'][0]['media_url_https'];
			}
			$tresc = $tresc.' ';
			$tresc = preg_replace('/(https:\/\/twitter.com\/.+?\/status\/.+?\/photo\/1 )/i', '[FOTO]', $tresc);
			$tresc = preg_replace('/(https:\/\/twitter.com\/.+?\/status\/.+?\/video\/1 )/i', '[WIDEO]', $tresc);
			$tresc = preg_replace('/(https:\/\/twitter.com\/.+?\/status\/.+? )/i', '[STATUS]', $tresc);
			if(!empty($image))
			{
				$description = '<img src="'.$image.'" />'.$tresc;
				$description2 = '<img height="100px" width="100px" src="'.$image.'" />'.$tresc;
				echo 'Description: '.$description2.'<br>';
			}
			else
			{
				$description = $tresc;
				echo 'Description: '.$description.'<br>';
			}
			
			echo '<br>';
			
			if($nowe_tweety == 'TAK')
			{
				$plik_rss .= '
					<item>
					<title>'.$title.'</title>
					<link>'.$link.'</link>
					<pubDate>'.$date.'</pubDate>
					<description><![CDATA['.$description.']]></description>
					</item>
				';
			}
		endforeach;
		
		if($nowe_tweety == 'TAK')
		{
			$plik_rss .= '
					</channel>
					</rss>
			';
			
			$zmien = 'UPDATE ttapirss SET last_tweet_id=\''.$tweets['meta']['newest_id'].'\', aktualizacja=\''.date('r').'\' WHERE id=\''.$id.'\'';
			mysqli_query($mysqli, $zmien);
			
			$fp = fopen('twitter_'.$user_nick.'.rss', 'w');
			fputs($fp, $plik_rss);
			fclose($fp);
		}
		
		$zmien = 'UPDATE ttapirss SET sprawdzenie=\''.date('r').'\' WHERE id=\''.$id.'\'';
		mysqli_query($mysqli, $zmien);
	}
	mysqli_close($mysqli);
?>