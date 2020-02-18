<?php
/**
 * @file
 * Get the latest release of a project from github.
 */

$baseUrl = 'https://api.github.com';
$token = getenv('GITHUBAPI_TOKEN');
$authorization = 'Authorization: token ' . $token;

// @todo: for now update this date weekly.
// Last week: 2020-02-13T20:08:20Z
$date = isset($argv[1]) ? $argv[1] : '';
$test_run = isset($argv[2]) ? TRUE : FALSE;

if ($date == '') {
  print "\n\tA date is required to return releases since \$date.\n\n";
  return;
}

if ($test_run) {
  print "\n\tThis is a test run and only the first 30 results will be used.\n\n";
}

$new_releases = get_new_rleases_since_date(
  $baseUrl,
  $authorization,
  $date,
  'backdrop-contrib',
  $test_run
);
$count = count($new_releases);
$text = ($count > 1 || $count == 0)
  ? "There have been $count new releases this week!"
  : "There has been $count new release this week.";

$html = '<div>';
$html .= "<div>
  $text
</div>";
foreach ($new_releases as $machine_name => $release) {
  $html .= <<< HTML
    <div>
			<a href="{$release['url']}">$machine_name</a> {$release['version']} {$release['author']}
		</div>  
HTML;
}
$html .= '</div>';
print_r($html);

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
function get_latest_release($baseUrl, $authorization, $owner = 'backdrop-contrib', $repo) {
  $url = "$baseUrl/repos/$owner/$repo/releases/latest";

  $ch = curl_init();
  curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    array('Content-Type: application/json', $authorization)
  );
  curl_setopt (
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
  $myHeader = explode("\n", $header);
  $body = json_decode($body);
  $body->project = $repo;

  return $body;
}

/**
 * Query GitHub API for all backdrop-contrib projects.
 *
 * @param string $org
 * The org to get the project from.
 *
 * @return Object
 *   Data from the GitHub API.
 *
 * @see _next_url()
 */
function get_all_contrib_projects($baseUrl, $authorization, $org = 'backdrop-contrib', $test_run = FALSE) {
  $url = "$baseUrl/orgs/$org/repos";
  $projects = [];

  do {
    $ch = curl_init();
    curl_setopt(
      $ch,
      CURLOPT_HTTPHEADER,
      array('Content-Type: application/json', $authorization)
    );
    curl_setopt (
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
    $myHeader = explode("\n", $header);
    $body = json_decode($body);
		if (!empty($body)) {
      foreach ($body as $project) {
        $projects[] = $project->name;
      }
    }
    $url = _next_url($myHeader, $test_run);
  } 
  while ($url);

  return $projects;
}

/**
 * Check for new releases of a project.
 *
 * @param string $baseUrl
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
function get_new_rleases_since_date($baseUrl, $authorization, $date, $org, $test_run = FALSE) {
  $new_releases = [];
  $projects = get_all_contrib_projects($baseUrl, $authorization, $org, $test_run);
  foreach ($projects as $project) {
		// Get the latest release of the $project.
    $release = get_latest_release($baseUrl, $authorization, $org, $project);
		$release_name = $release->project;
		$release_date = (isset($release->created_at)) ? $release->created_at : NULL;
print_r(['rd' => $release_date]);
    // Check if release is later than $date.
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
 * @param array $myHeader
 *   The header from the GitHub API request.
 *
 * @return string nextUrl | NULL
 *   The next url from paginated request or NULL.
 */
function _next_url($myHeader, $test_run = FALSE) {
  if ($test_run) {
    return NULL;
  }
  if (isset($myHeader[15])
    && strpos($myHeader[15], 'rel="next"') == TRUE
    && strpos($myHeader[15], 'rel="prev"') == FALSE) {

    $nextUrl = explode('rel="next"', $myHeader[15]);
    $nextUrl = $nextUrl[0];
    $nextUrl = explode('<', $nextUrl);
    $nextUrl = $nextUrl[1];
    $nextUrl = rtrim($nextUrl, '>; ');
  }
  elseif (isset($myHeader[15])
    && strpos($myHeader[15], 'rel="next"') == TRUE
    && strpos($myHeader[15], 'rel="prev"') == TRUE) {

    $nextUrl = explode('rel="next"', $myHeader[15]);
    $nextUrl = $nextUrl[0];
    $nextUrl = explode('rel="prev"', $nextUrl);
    $nextUrl = $nextUrl[1];
    $nextUrl = explode('<', $nextUrl);
    $nextUrl = $nextUrl[1];
    $nextUrl = rtrim($nextUrl, '>; ');
  }
  else {
    $nextUrl = NULL;
  }
  print_r(['nu' => $nextUrl]);

  return $nextUrl;
}
