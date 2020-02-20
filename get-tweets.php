<?php
/**
 * @file
 * Aggregeate tweets from @serundeputy for display on serundeputy.io.
 */

require_once __DIR__ . "/vendor/autoload.php";

use Abraham\TwitterOAuth\TwitterOAuth;


// Get the tweets.
$tweets = get_tweets();
print_r($tweets['simplified_statuses']);
/**
 * Get tweets.
 */
function get_tweets() {
  $consumer_key = getenv('TWITTER_CONSUMER_KEY');
  $consumer_secret = getenv('TWITTER_CONSUMER_SECRET');
  $access_token = getenv('TWITTER_ACCESS_TOKEN');
  $access_token_secret = getenv('TWITTER_ACCESS_TOKEN_SECRET');

  $connection = new TwitterOAuth(
		$consumer_key,
		$consumer_secret,
    $access_token,
    $access_token_secret
  );
  $statuses = $connection->get(
    "search/tweets", ["q" => "backdropcms"]
  );
  
  $simplified_statuses = [];
  foreach ($statuses as $status) {
    foreach ($status as $data) {
      if (isset($data->user->screen_name)) {
        if (isset($data->retweeted_status)) {
			    $url = $data->retweeted_status->entities->urls[0]->expanded_url;	
				}
				else {
          $url = $data->entities->urls[0]->expanded_url;
        }
        $simplified_statuses[] = [
          'author' => '@' . $data->user->screen_name,
          'url' => $url,
          'text' => $data->text,
        ];
      }
    }
  }

  return [
    'statuses' => $statuses,
    'simplified_statuses' => $simplified_statuses,
  ];
}