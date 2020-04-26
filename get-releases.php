<?php
/**
 * @file
 * Get the latest release of a project from github.
 */

$base_url = 'https://api.github.com';
$token = getenv('GITHUBAPI_TOKEN');
$authorization = 'Authorization: token ' . $token;

// @todo: for now update this date weekly.
// Last week: 2020-02-13T20:08:20Z
$date = isset($argv[1]) ? $argv[1] : '';
$org = isset($argv[2]) ? $argv[2] : 'backdrop-contrib';
$test_run = isset($argv[3]) ? TRUE : FALSE;

if ($date == '') {
  print "\n\tA date is required to return releases since \$date.\n\n";
  return;
}

if ($test_run) {
  print "\n\tThis is a test run and only the first 30 results will be used.\n\n";
}

print "\n\tGetting releases for the $org org.\n\n";

$new_releases = get_new_rleases_since_date(
  $base_url,
  $authorization,
  $date,
  $org,
  $test_run
);
$count = count($new_releases);
$text = ($count > 1 || $count == 0) ?
  "There have been $count new releases since $date!" :
  "There has been $count new release since $date.";

$html = '<div>';
$html .= "<div>
  $text
</div>";
$txt = $text;
foreach ($new_releases as $machine_name => $release) {
  $html .= <<< HTML
    <div>
			<a href="{$release['url']}">$machine_name</a> {$release['version']} {$release['author']}
		</div>  
HTML;
  $txt .= "\n* $machine_name {$release['version']} \n\t * {$release['author']}";
}
$html .= '</div>';
print_r([
  'html' => $html,
  'txt' => $txt,
]);

/**
 * Query github api for the latest release of a project.
 *
 * @param string $base_url
 *   The base url for the API call. 
 * @param string $authorization
 *   The authorization string for the header API call.
 * @param string $repo
 *   The repo to get the latest release for.
 * @param string $owner
 *   The owner or org of the repo. 
 *
 * @return array
 *   The response from GitHub API.
 */
function get_latest_release($base_url, $authorization, $repo, $owner = 'backdrop-contrib') {
  $url = "$base_url/repos/$owner/$repo/releases/latest";
  $ch = curl_init();
  curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    array('Content-Type: application/json', $authorization)
  );
  curl_setopt(
    $ch,
    CURLOPT_USERAGENT,
    'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)'
  );
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  $content = curl_exec($ch);
  $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $header = substr($content, 0, $header_size);
  $body = substr($content, $header_size);
  curl_close($ch);
  $my_header = explode("\n", $header);
  $body = json_decode($body);
  $body->project = $repo;

  return $body;
}

/**
 * Query GitHub API for all backdrop-contrib projects.
 *
 * @param string $base_url
 *   The base url for API query.
 * @param string $authorization
 *   The authorization string for the header of API request.
 * @param string $org
 *   The org to get the project from.
 * @param bool $test_run
 *   Pass in a TRUE if you want to limit to 30 queries for testing. 
 *
 * @return Object
 *   Data from the GitHub API.
 *
 * @see _next_url()
 */
function get_all_contrib_projects($base_url, $authorization, $org = 'backdrop-contrib', $test_run = FALSE) {
  $url = "$base_url/users/$org/repos";
  $projects = [];

  print "\n\t\tGathering backdrop-contrib repos .";

  do {
    $ch = curl_init();
    curl_setopt(
      $ch,
      CURLOPT_HTTPHEADER,
      array('Content-Type: application/json', $authorization)
    );
    curl_setopt(
      $ch,
      CURLOPT_USERAGENT,
      'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)'
    );
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    $content = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($content, 0, $header_size);
    $body = substr($content, $header_size);
    curl_close($ch);
    $my_header = explode("\n", $header);
    $body = json_decode($body);

    if (!empty($body)) {
      foreach ($body as $project) {
        $projects[] = $project->name;
      }
    }
    $url = _next_url($my_header, $test_run);
    print " .";
  } while ($url);

  return $projects;
}

/**
 * Check for new releases of a project.
 *
 * @param string $base_url
 *   The base url of the API.
 * @param string $authorization
 *   The authorization string for the header of the query.
 * @param string $date
 *   The date to check against; 2020-02-13T20:08:20Z.
 * @param string $org
 *   The repo to check i.e. drush or webform. 
 *
 * @see get_all_contrib_projects()
 * @see get_latest_release()
 */
function get_new_rleases_since_date($base_url, $authorization, $date, $org, $test_run = FALSE) {
  $new_releases = [];
  $projects = get_all_contrib_projects($base_url, $authorization, $org, $test_run);
  foreach ($projects as $project) {
    // Get the latest release of the $project.
    $release = get_latest_release($base_url, $authorization, $project, $org);
    $release_name = $release->project;
    $release_date = (isset($release->created_at)) ? $release->created_at : NULL;

    print "\t\t\033[1m$release->project\033[0m\n";

    // eheck if release is later than $date.
    if (!empty($release_date) && strtotime($release_date) > strtotime($date)) {
      $new_releases[$release_name] = [
        'version' => $release->tag_name,
        'author' => '@' . $release->author->login,
        'url' => $release->html_url,
      ];
    }
  }

  return $new_releases;
}

/**
 * Check the header to see if there is a nextUrl.
 *
 * @param array $my_header
 *   The header from the GitHub API request.
 *
 * @return string|NULL
 *   $next_url The next url from paginated request or NULL.
 */
function _next_url($my_header, $test_run = FALSE) {
  if ($test_run) {
    return NULL;
  }
  if (isset($my_header[15])
    && strpos($my_header[15], 'rel="next"') == TRUE
    && strpos($my_header[15], 'rel="prev"') == FALSE) {

    $next_url = explode('rel="next"', $my_header[15]);
    $next_url = $next_url[0];
    $next_url = explode('<', $next_url);
    $next_url = $next_url[1];
    $next_url = rtrim($next_url, '>; ');
  }
  elseif (isset($my_header[15])
    && strpos($my_header[15], 'rel="next"') == TRUE
    && strpos($my_header[15], 'rel="prev"') == TRUE) {

    $next_url = explode('rel="next"', $my_header[15]);
    $next_url = $next_url[0];
    $next_url = explode('rel="prev"', $next_url);
    $next_url = $next_url[1];
    $next_url = explode('<', $next_url);
    $next_url = $next_url[1];
    $next_url = rtrim($next_url, '>; ');
  }
  else {
    $next_url = NULL;
  }

  return $next_url;
}
