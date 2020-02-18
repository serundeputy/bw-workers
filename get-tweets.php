<?php
/**
 * @file
 * Aggregeate tweets from @serundeputy for display on serundeputy.io.
 */

require_once "/vendor/autoload.php";

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Implements hook_block_info().
 */
function serundeputy_tweets_block_info() {
  $blocks['serundeputy_tweets_block'] = array(
    'info' => t('Serundeputy Tweets'),
    'description' => t('Get tweets from twitter API.'),
  );

  return $blocks;
}

/**
 * Implements hook_block_view().
 *
 * @see _serundeputy_tweets_get_tweets()
 */
function serundeputy_tweets_block_view($delta = '', $settings = array()) {
  $block = array();

  // Block content.
  $content = _serundeputy_tweets_get_tweets();

  switch ($delta) {
    case 'serundeputy_tweets_block':
      $block['subject'] = NULL;
      $block['content'] = $content;
      return $block;
  }
}

/**
 * Get tweets.
 */
function _serundeputy_tweets_get_tweets() {
  // @codingStandardsIgnoreStart
  global $settings;
  // @codingStandardsIgnoreEnd

  $consumer_key = getenv('TWITTER_CONSUMER_KEY');
  $consumer_secret = getenv('TWITTER_CONSUMER_SECRET');
  $access_token = getenv('TWITTER_ACCESS_TOKEN');
  $access_token_secret = getenv('TWITTER_ACCESS_TOKEN_SECRET');

  $connection = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);
  $statuses = $connection->get(
    "search/tweets", ["q" => "serundeputy","count" => 4]
  );
