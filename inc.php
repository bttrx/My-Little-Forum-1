<?php
###############################################################################
# my little forum                                                             #
# Copyright (C) 2004-2008 Alex                                                #
# http://www.mylittlehomepage.net/                                            #
# Copyright (C) 2009-2019 H. August                                           #
# https://www.projekt-mlf.de/                                                 #
#                                                                             #
# This program is free software; you can redistribute it and/or               #
# modify it under the terms of the GNU General Public License                 #
# as published by the Free Software Foundation; either version 2              #
# of the License, or (at your option) any later version.                      #
#                                                                             #
# This program is distributed in the hope that it will be useful,             #
# but WITHOUT ANY WARRANTY; without even the implied warranty of              #
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the                #
# GNU General Public License for more details.                                #
                                                                              #
# You should have received a copy of the GNU General Public License           #
# along with this program; if not, write to the Free Software                 #
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. #
###############################################################################

#ini_set("session.use_trans_sid","0");
// Harden session handling before starting the session (PHP 7.3+ / PHP 8+).
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
$session_cookie_secure = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
$session_cookie_path = str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/'));
if ($session_cookie_path === '.' || $session_cookie_path === '/') $session_cookie_path = '/';
else $session_cookie_path = rtrim($session_cookie_path, '/') . '/';
session_set_cookie_params(array(
    'lifetime' => 0,
    'path' => $session_cookie_path,
    'secure' => $session_cookie_secure,
    'httponly' => true,
    'samesite' => 'Lax'
));
session_start();

include("db_settings.php");
include("functions.php");

// Per-session CSRF protection for POST forms and old state-changing GET links.
if (!isset($_SESSION['_mlf_csrf_token']) || !is_string($_SESSION['_mlf_csrf_token'])) {
    $_SESSION['_mlf_csrf_token'] = bin2hex(random_bytes(32));
}
$request_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
if ($request_method === 'POST' && !forum_csrf_token_valid(isset($_POST['_mlf_csrf_token']) ? $_POST['_mlf_csrf_token'] : null)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('This form expired or its security token is invalid. Please reload the page and try again.');
}
ob_start('forum_inject_csrf_fields');

# Magic quotes were removed in PHP 5.4; request values must not be unescaped globally.

$connid = connect_db($db_settings['host'], $db_settings['user'], $db_settings['pw'], $db_settings['db']);
$settings = get_settings();

$current_page = basename(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '');
$csrf_required_get = forum_csrf_request_requires_token($current_page, $_GET);
// login.php treats a plain visit by an authenticated user as logout for legacy reasons.
if ($current_page === 'login.php' && isset($_SESSION[$settings['session_prefix'].'user_id']) && !isset($_GET['action'])) $csrf_required_get = true;
if ($csrf_required_get && !forum_csrf_token_valid(isset($_GET['_mlf_csrf_token']) ? $_GET['_mlf_csrf_token'] : null)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('This action expired or its security token is invalid. Reload the forum page and try again.');
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}
include("lang/".$settings['language_file']);
setlocale(LC_ALL, $lang['locale']);

if(basename($_SERVER['PHP_SELF'])!='login.php' && basename($_SERVER['PHP_SELF'])!='info.php' && (!(isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type']=='admin')) && $settings['forum_disabled']==1)
 {
  if(isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] != 'admin')
   {
    session_destroy();
    forum_set_cookie("auto_login", "", 0);
   }
  header("location: info.php?info=1");
  die("<a href=\"info.php?info=1\">further...</a>");
 }

// look if IP is banned:
$ip_result=mysqli_query($connid, "SELECT list FROM ". $db_settings['banlists_table'] ." WHERE name = 'ips' LIMIT 1");
if(!$ip_result) die($lang['db_error']);
$data = mysqli_fetch_assoc($ip_result);
mysqli_free_result($ip_result);
if(trim($data['list']) != '')
 {
  $banned_ips_array = explode(',',trim($data['list']));
  if(in_array($_SERVER["REMOTE_ADDR"],$banned_ips_array))
   {
    die($lang['ip_no_access']);
   }
 }

// look if user is banned:
if(isset($_SESSION[$settings['session_prefix'].'user_name']))
 {
  $ban_result=mysqli_query($connid, "SELECT list FROM ". $db_settings['banlists_table'] ." WHERE name = 'users' LIMIT 1");
  if(!$ban_result) die($lang['db_error']);
  $data = mysqli_fetch_assoc($ban_result);
  mysqli_free_result($ban_result);
  if(trim($data['list']) != '')
   {
    $banned_users_array = explode(',',strtolower(trim($data['list'])));
    if(in_array(strtolower($_SESSION[$settings['session_prefix'].'user_name']),$banned_users_array) && $_SESSION[$settings['session_prefix'].'user_type']!='admin')
     {
      session_destroy();
      forum_set_cookie("auto_login", "", 0);
      header("location: login.php?msg=user_banned");
      die($lang['user_banned']);
     }
   }
 }

// determine last visit:
if (empty($_SESSION[$settings['session_prefix']."user_id"]) && $settings['remember_last_visit'] == 1)
 {
  if (isset($_COOKIE['last_visit']))
   {
    $c_last_visit = explode(".", $_COOKIE['last_visit']);
    if (isset($c_last_visit[0])) $c_last_visit[0] = trim($c_last_visit[0]); else $c_last_visit[0] = time();
    if (isset($c_last_visit[1])) $c_last_visit[1] = trim($c_last_visit[1]); else $c_last_visit[1] = time();
    if ($c_last_visit[1] < (time() - 600))
     {
      $c_last_visit[0] = $c_last_visit[1];
      $c_last_visit[1] = time();
      forum_set_cookie("last_visit", $c_last_visit[0].".".$c_last_visit[1], time()+(3600*24*30));
     }
   }
  else forum_set_cookie("last_visit", time().".".time(), time()+(3600*24*30));
 }
if (isset($c_last_visit)) $last_visit = $c_last_visit[0]; else $last_visit = time();

if(isset($_GET['category'])) $category = intval($_GET['category']);
$categories = get_categories();
$category_ids = get_category_ids($categories);
if($category_ids!=false) $category_ids_query = implode(", ", $category_ids);
if(empty($category)) $category=0;

#$blocked_categories_request = get_blocked_categories();

if (isset($_SESSION[$settings['session_prefix'].'user_id'])) $category_accession = category_accession();

// Count postings and threads in one aggregate query; category IDs were normalized
// by get_category_ids() before being added to the query.
if ($categories === false || is_array($categories)) {
    $category_filter = '';
    if (is_array($categories)) {
        $category_filter = " WHERE category IN (".$category_ids_query.")";
    }
    $count_result = mysqli_query($connid, "SELECT COUNT(*) AS posting_count, COALESCE(SUM(CASE WHEN pid = 0 THEN 1 ELSE 0 END), 0) AS thread_count FROM ".$db_settings['forum_table'].$category_filter);
    if (!$count_result) die($lang['db_error']);
    list($posting_count, $thread_count) = mysqli_fetch_row($count_result);
    mysqli_free_result($count_result);
    $posting_count = (int)$posting_count;
    $thread_count = (int)$thread_count;
} else {
    $thread_count = 0;
    $posting_count = 0;
}

$count_result = mysqli_query($connid, "SELECT COUNT(*) FROM ".$db_settings['userdata_table']);
if (!$count_result) die($lang['db_error']);
list($user_count) = mysqli_fetch_row($count_result);
mysqli_free_result($count_result);
$user_count = (int)$user_count;

if ($settings['count_users_online'] > 0)
 {
  user_online();
  $count_result = mysqli_query($connid, "SELECT COALESCE(SUM(CASE WHEN user_id > 0 THEN 1 ELSE 0 END), 0) AS registered_online, COALESCE(SUM(CASE WHEN user_id = 0 THEN 1 ELSE 0 END), 0) AS guests_online FROM ".$db_settings['useronline_table']);
  if (!$count_result) die($lang['db_error']);
  list($useronline_count, $guestsonline_count) = mysqli_fetch_row($count_result);
  mysqli_free_result($count_result);
  $useronline_count = (int)$useronline_count;
  $guestsonline_count = (int)$guestsonline_count;
  if ($settings['count_users_online'] != 2) $counter = $lang['counter_uo'];
  else $counter = $lang['counter_uo_ng'];
  $counter = str_replace("[postings]", $posting_count, $counter);
  $counter = str_replace("[threads]", $thread_count, $counter);
  $counter = str_replace("[users]", $user_count, $counter);
  if ($settings['count_users_online'] != 2) $counter = str_replace("[total_online]", $useronline_count+$guestsonline_count, $counter);
  $counter = str_replace("[user_online]", $useronline_count, $counter);
  if ($settings['count_users_online'] != 2) $counter = str_replace("[guests_online]", $guestsonline_count, $counter);
 }
else
 {
  $counter = str_replace("[forum_name]", "<a href=\"".$settings['forum_address']."\">". htmlsc($settings['forum_name']) ."</a>", $lang['counter']);
  $counter = str_replace("[contact]", "<a href=\"contact.php?forum_contact=true\">". htmlsc($lang['contact_linkname']) ."</a>", $counter);
  $counter = str_replace("[postings]", $posting_count, $counter);
  $counter = str_replace("[threads]", $thread_count, $counter);
  $counter = str_replace("[users]", $user_count, $counter);
}

// Cast all timezone offsets before they are interpolated into SQL INTERVAL expressions.
// The cookie is user-controlled and must never be concatenated into a query as-is.
$base_time_difference = (isset($settings['time_difference']) && is_numeric($settings['time_difference'])) ? (int)$settings['time_difference'] : 0;
$user_time_difference_offset = 0;
if (isset($_SESSION[$settings['session_prefix'].'user_time_difference']) && is_scalar($_SESSION[$settings['session_prefix'].'user_time_difference']) && is_numeric($_SESSION[$settings['session_prefix'].'user_time_difference'])) {
    $user_time_difference_offset = (int)$_SESSION[$settings['session_prefix'].'user_time_difference'];
} elseif (isset($_COOKIE['user_time_difference']) && is_scalar($_COOKIE['user_time_difference']) && is_numeric($_COOKIE['user_time_difference'])) {
    $user_time_difference_offset = (int)$_COOKIE['user_time_difference'];
}
$user_time_difference_offset = max(-24, min(24, $user_time_difference_offset));
$time_difference = $base_time_difference + $user_time_difference_offset;
?>