<?php
/**
 * @file
 * Get the latest release of a project from github.
 */

$x = get_releases('backdrop-contrib', 'drush');
print_r($x);

/**
 * Query github api for the latest release of a project.
 *
 * @param strong $owner
 *   The owner of the repo the i.e. the org or username.
 * @param string $repo
 *   The project that you wish to get the latest release for.
 *
 * @return array
 *   The return from GitHub API.
 */
function get_latest_release($owner = 'backdrop-contrib', $repo) {
  $url = "https://api.github.com/repos/$owner/$repo/releases/latest";
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization));
  curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
  curl_setopt($ch, CURLOPT_URL, $nextUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  $content = curl_exec($ch);
  $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $header = substr($content, 0, $header_size);
  $body = substr($content, $header_size);
  curl_close($ch);
  $myHeader = explode("\n", $header);

  return $body;
}
