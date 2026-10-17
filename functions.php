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
#                                                                             #
# You should have received a copy of the GNU General Public License           #
# along with this program; if not, write to the Free Software                 #
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. #
###############################################################################

require_once("function.tab2space.php");

/**
 * disables magic_quotes from given variables of different types
 *
 * remove the slashes if magic_quotes_gpc is activated
 * see also: http://php.net/manual/en/security.magicquotes.disabling.html
 *
 * @param array [$value]
 * @return array [$value]
 * @since 1.7.8
 * @link http://php.net/manual/en/security.magicquotes.disabling.html
 */
function stripslashes_deep($value) {
	$value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
	return $value;
} # End: stripslashes_deep


function get_settings() {
	global $lang, $connid, $db_settings;
	$r = array();
	$result = mysqli_query($connid, "SELECT name, value FROM ".$db_settings['settings_table']);
	if (!$result) die($lang['db_error']);
	while ($line = mysqli_fetch_assoc($result)) {
		$r[$line['name']] = $line['value'];
	}
	mysqli_free_result($result);
	return $r;
}

function get_categories()
 {
  global $lang, $settings, $connid, $db_settings;

  $count_result = mysqli_query($connid, "SELECT COUNT(*) FROM ".$db_settings['category_table']);
  list($category_count) = mysqli_fetch_row($count_result);
  mysqli_free_result($count_result);

  if ($category_count > 0)
   {
    if (empty($_SESSION[$settings['session_prefix'].'user_id']))
     {
      $result = mysqli_query($connid, "SELECT id, category FROM ".$db_settings['category_table']." WHERE accession = 0 ORDER BY category_order ASC");
     }
    elseif (isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "user")
     {
      $result = mysqli_query($connid, "SELECT id, category FROM ".$db_settings['category_table']." WHERE accession = 0 OR accession = 1 ORDER BY category_order ASC");
     }
    elseif (isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_SESSION[$settings['session_prefix'].'user_type']) && ($_SESSION[$settings['session_prefix'].'user_type'] == "mod" || $_SESSION[$settings['session_prefix'].'user_type'] == "admin"))
     {
      $result = mysqli_query($connid, "SELECT id, category FROM ".$db_settings['category_table']." WHERE accession = 0 OR accession = 1 OR accession = 2 ORDER BY category_order ASC");
     }
    if(!$result) die($lang['db_error']);
    $categories[0]='';
    while ($line = mysqli_fetch_assoc($result))
     {
      $categories[$line['id']] = $line['category'];
     }
    mysqli_free_result($result);
    return $categories;
   }
  else return false;
 }

function get_category_ids($categories)
 {
  if($categories!=false)
   {
    foreach ($categories as $key => $_category_value)
     {
      $category_ids[] = $key;
     }
    return $category_ids;
   }
  else return false;
 }

function category_accession()
 {
  global $settings, $lang, $connid, $db_settings;
  $result = mysqli_query($connid, "SELECT id, accession FROM ".$db_settings['category_table']);
  while ($line = mysqli_fetch_assoc($result))
   {
    $category_accession[$line['id']] = $line['accession'];
   }
  mysqli_free_result($result);
  if (isset($category_accession)) return $category_accession; else return false;
 }

function nav($page, $entries_per_page, $entry_count, $order, $descasc, $category, $action="") // Seiten-Navigation für forum.php, board.php und mix.php
  {
   global $lang, $select_submit_button;
   $output = "";
   if ($entry_count > $entries_per_page)
    {
     $output .= "&nbsp; ";
     $new_index_before = $page - 1;
     $new_index_after = $page + 1;
     $site_count = ceil($entry_count / $entries_per_page);
     $ic = "";
     if (isset($category)) $ic .= "&amp;category=".urlencode($category);
     if (isset($action) && $action!="") $ic .= "&amp;action=".$action;
     if (isset($_GET['letter'])) $ic .= "&amp;letter=".urlencode($_GET['letter']);
     if ($new_index_before >= 0) $output .= "<a href=\"". basename($_SERVER["PHP_SELF"]) ."?page=".$new_index_before."&amp;order=".$order."&amp;descasc=".$descasc.$ic."\"><img src=\"img/prev.gif\" alt=\"&laquo;\" title=\"".$lang['previous_page_linktitle']."\" width=\"12\" height=\"9\" onmouseover=\"this.src='img/prev_mo.gif';\" onmouseout=\"this.src='img/prev.gif';\" /></a>&nbsp;";
     //if ($new_index_before >= 0 && $new_index_after < $site_count) $output .= " ";
     $page_count = ceil($entry_count/$entries_per_page);
     $output .= '<form action="'.basename($_SERVER["PHP_SELF"]).'" method="get" title="'.$lang['choose_page_formtitle'].'"><div style="display: inline;">';
     if (isset($order)) $output .= '<input type="hidden" name="order" value="'.$order.'" />';
     if (isset($descasc)) $output .= '<input type="hidden" name="descasc" value="'.$descasc.'" />';
     if (isset($category)) $output .= '<input type="hidden" name="category" value="'.$category.'" />';
     if (isset($action) && $action!="") $output .= '<input type="hidden" name="action" value="'.$action.'" />';
     if (isset($_GET['letter'])) $output .= '<input type="hidden" name="letter" value="'.$_GET['letter'].'" />';
     $output .= '<select class="kat" size="1" name="page" onchange="this.form.submit();">';
     if ($page == 0) $output .= '<option value="0" selected="selected">'.str_replace("[number]", "1", $lang['page_number']).'</option>';
     else $output .= '<option value="0">'.str_replace("[number]", "1", $lang['page_number']).'</option>';
     for($x=$page-9; $x<$page+10; $x++)
      {
       if ($x > 0 && $x < $page_count)
        {
         if ($page == $x) $output .= '<option value="'.$x.'" selected="selected">'.str_replace("[number]", $x+1, $lang['page_number']).'</option>';
         else $output .= '<option value="'.$x.'">'.str_replace("[number]", $x+1, $lang['page_number']).'</option>';
        }
      }
    $output .= '</select><noscript>&nbsp;<input type="image" name="" value="" src="img/submit.gif" alt="&raquo;" /></noscript></div></form>';
    if ($new_index_after < $site_count) $output .= "&nbsp;<a href=\"". basename($_SERVER["PHP_SELF"]) ."?page=" .$new_index_after ."&amp;order=".$order."&amp;descasc=".$descasc.$ic."\"><img src=\"img/next.gif\" alt=\"&raquo;\" title=\"".$lang['next_page_linktitle']."\" width=\"12\" height=\"9\" onmouseover=\"this.src='img/next_mo.gif';\" onmouseout=\"this.src='img/next.gif';\" /></a>";

    }
   return $output;
  }

/** Prevent legacy variable-variable request imports from overwriting application/authentication state. */
function forum_request_variable_allowed($key, $value) {
    if (!is_string($key) || !preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/', $key) || !is_scalar($value)) return false;
    // Validate parameters whose legacy handlers perform arithmetic, sort SQL, or echo them into links.
    if (in_array($key, array('page', 'be_page', 'p'), true) && !preg_match('/\A[0-9]{1,7}\z/', (string)$value)) return false;
    if (in_array($key, array('id', 'ul', 'sam', 'user_lock', 'mark', 'move_up_category', 'move_down_category', 'delete_smiley', 'move_up_smiley', 'move_down_smiley', 'lock'), true) && !preg_match('/\A[0-9]{1,10}\z/', (string)$value)) return false;
    if ($key === 'category' && !preg_match('/\A(?:[0-9]{1,10}|all)\z/', (string)$value)) return false;
    if ($key === 'order' && !in_array($value, array('time','last_answer','subject','category','name','user_name','user_type'), true)) return false;
    if (in_array($key, array('descasc', 'da'), true) && !in_array($value, array('ASC','DESC'), true)) return false;
    if ($key === 'view' && !in_array($value, array('board','mix'), true)) return false;
    $reserved = array(
        'GLOBALS','_GET','_POST','_COOKIE','_REQUEST','_SERVER','_SESSION','_FILES','_ENV',
        'settings','db_settings','connid','lang','lang_add','categories','category_accession',
        'category_ids','category_ids_query','time_difference','counter','template','header','footer','errors',
        'user_id','user_type','user_name','user_view','user_time_difference','last_visit',
        'key','value','request_method','current_page','csrf_required_get','protected_action'
    );
    return !in_array($key, $reserved, true);
}

/** Return a safely escaped href, or false for unsupported schemes. Input may already be HTML-escaped. */
function forum_safe_sort_column($candidate, $allowed, $default) {
    return (is_string($candidate) && in_array($candidate, $allowed, true)) ? $candidate : $default;
}

function forum_safe_sort_direction($candidate, $default = 'ASC') {
    if (!is_string($candidate)) return $default;
    $candidate = strtoupper($candidate);
    return ($candidate === 'ASC' || $candidate === 'DESC') ? $candidate : $default;
}

function forum_safe_link_target($raw_url) {
    global $lang;
    $charset = isset($lang['charset']) ? $lang['charset'] : 'UTF-8';
    $url = trim(html_entity_decode((string)$raw_url, ENT_QUOTES | ENT_HTML5, $charset));
    if ($url === '' || preg_match('/[\x00-\x20\x7f]/', $url)) return false;
    $scheme = parse_url($url, PHP_URL_SCHEME);
    if ($scheme !== null && !in_array(strtolower($scheme), array('http', 'https', 'ftp', 'mailto'), true)) return false;
    return htmlsc($url);
}

/** Allow image URLs only from HTTP(S) or local relative paths. */
function forum_safe_image_target($raw_url) {
    global $lang;
    $charset = isset($lang['charset']) ? $lang['charset'] : 'UTF-8';
    $url = trim(html_entity_decode((string)$raw_url, ENT_QUOTES | ENT_HTML5, $charset));
    if ($url === '' || preg_match('/[\x00-\x20\x7f]/', $url) || strpos($url, '//') === 0) return false;
    $scheme = parse_url($url, PHP_URL_SCHEME);
    if ($scheme !== null && !in_array(strtolower($scheme), array('http', 'https'), true)) return false;
    return htmlsc($url);
}

function forum_bbcode_named_link($matches) {
    $href = forum_safe_link_target($matches[1]);
    if ($href === false) return $matches[2];
    return '<a href="'.$href.'">'.$matches[2].'</a>';
}

function forum_bbcode_image($matches, $style = 'margin: 5px 0px 5px 0px') {
    $src = forum_safe_image_target($matches[1]);
    if ($src === false) return '[image]';
    return '<img src="'.$src.'" alt="[image]" style="'.$style.'" />';
}

// makes URLs clickable:
function make_link($string)
 {
  $string = ' ' . $string;
  $string = preg_replace_callback("#(^|[\n ])([\w]+?://.*?[^ \"\n\r\t<]*)#is", "shorten_link", $string);
  $string = preg_replace("#(^|[\n ])((www|ftp)\.[\w\-]+\.[\w\-.\~]+(?:/[^ \"\t\n\r<]*)?)#is", "\\1<a href=\"http://\\2\">\\2</a>", $string);
  $string = preg_replace("#(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $string);
  $string = substr($string, 1);

  return $string;
 }

// function to hide the links from the checkings in posting.php
function text_check_link($string)
 {
  $string = ' ' . $string;
  $string = preg_replace("#(^|[\n ])([\w]+?://.*?[^ \"\n\r\t<]*)#is", "", $string);
  $string = substr($string, 1);
  return $string;
 }

function bbcode($string)
 {
  global $settings;
  $string = preg_replace("#\[b\](.+?)\[/b\]#is", "<b>\\1</b>", $string);
  $string = preg_replace("#\[i\](.+?)\[/i\]#is", "<i>\\1</i>", $string);
  $string = preg_replace("#\[u\](.+?)\[/u\]#is", "<u>\\1</u>", $string);
  $string = preg_replace("#\[s\](.+?)\[/s\]#is", "<del>\\1</del>", $string);
  $string = preg_replace("#\[d\](.+?)\[/d\]#is", "<del>\\1</del>", $string);
  $string = preg_replace("#\[sub\](.+?)\[/sub\]#is", "<sub>\\1</sub>", $string);
  $string = preg_replace("#\[sup\](.+?)\[/sup\]#is", "<sup>\\1</sup>", $string);
  $string = preg_replace("#\[link\]www\.(.+?)\[/link\]#is", "<a href=\"http://www.\\1\">www.\\1</a>", $string);
  $string = preg_replace_callback("#\[link\](.+?)\[/link\]#is", "shorten_link", $string);
  $string = preg_replace_callback("#\[link=(.+?)\](.+?)\[/link\]#is", "forum_bbcode_named_link", $string);
  $string = preg_replace("#\[url\]www\.(.+?)\[/url\]#is", "<a href=\"http://www.\\1\">www.\\1</a>", $string);
  $string = preg_replace_callback("#\[url\](.+?)\[/url\]#is", "shorten_link", $string);
  $string = preg_replace_callback("#\[url=(.+?)\](.+?)\[/url\]#is", "forum_bbcode_named_link", $string);
  $string = preg_replace_callback("#\[code\](.+?)\[/code\]#is", "parse_code", $string);
  $string = preg_replace("#\[msg\](.+?)\[/msg\]#is", "<a href=\"".basename($_SERVER['PHP_SELF'])."?id=\\1\">\\1</a>", $string);
  $string = preg_replace("#\[msg=(.+?)\](.+?)\[/msg\]#is", "<a href=\"".basename($_SERVER['PHP_SELF'])."?id=\\1\">\\2</a>", $string);
  if ($settings['bbcode_img'] == 1)
   {
    $string = preg_replace_callback("#\[img\](.+?)\[/img\]#is", "forum_bbcode_image", $string);
    $string = preg_replace_callback("#\[img\|left\](.+?)\[/img\]#is", function ($m) { return forum_bbcode_image($m, "float: left; margin: 0px 5px 5px 0px"); }, $string);
    $string = preg_replace_callback("#\[img\|right\](.+?)\[/img\]#is", function ($m) { return forum_bbcode_image($m, "float: right; margin: 0px 0px 5px 5px"); }, $string);
   }
  return $string;
 }

function shorten_link($string)
 {
  global $settings;
  if(count($string) == 2) { $pre = ""; $url = $string[1]; }
  else { $pre = $string[1]; $url = $string[2]; }
  $safe_url = forum_safe_link_target($url);
  if ($safe_url === false) return $pre.$url;
  $display_url = $url;
  if (strlen($display_url) > $settings['text_word_maxlength']) $display_url = substr($display_url, 0, ($settings['text_word_maxlength']/2)) . "..." . substr($display_url, - ($settings['text_word_maxlength']-3-$settings['text_word_maxlength']/2));
  return $pre."<a href=\"".$safe_url."\">".$display_url."</a>";
 }

function parse_code($string)
{
    // Replace tabs with correct number of spaces.
    $string = tab2space($string[1], 8);

    // Replace two spaces with '&nbsp; ' so non-tabbed code indents without making huge long lines.
    $string = str_replace('  ', '&nbsp; ', $string);

    // Replace two spaces with ' &nbsp;' to catch odd numbers of spaces.
    $string = str_replace('  ', ' &nbsp;', $string);

    // Replace space occurring at the beginning of a line.
    $string = preg_replace('/^ {1}/m', '&nbsp;', $string);

    return '<code>'.$string.'</code>';
}

// strips BBCodes for e-mail texts:
function unbbcode($string)
 {
  global $settings;
  $string = preg_replace("#\[b\](.+?)\[/b\]#is", "*\\1*", $string);
  $string = preg_replace("#\[i\](.+?)\[/i\]#is", "/\\1/", $string);
  $string = preg_replace("#\[u\](.+?)\[/u\]#is", "_\\1_", $string);
  $string = preg_replace("#\[s\](.+?)\[/s\]#is", "-\\1-", $string);
  $string = preg_replace("#\[d\](.+?)\[/d\]#is", "-\\1-", $string);
  $string = preg_replace("#\[sub\](.+?)\[/sub\]#is", "~\\1~", $string);
  $string = preg_replace("#\[sup\](.+?)\[/sup\]#is", "^\\1^", $string);
  $string = preg_replace("#\[link\]www\.(.+?)\[/link\]#is", "http://www.\\1", $string);
  $string = preg_replace("#\[link\](.+?)\[/link\]#is", "\\1", $string);
  $string = preg_replace("#\[link=(.+?)\](.+?)\[/link\]#is", "\\2 --> \\1", $string);
  $string = preg_replace("#\[url\]www\.(.+?)\[/url\]#is", "http://www.\\1", $string);
  $string = preg_replace("#\[url\](.+?)\[/url\]#is", "\\1", $string);
  $string = preg_replace("#\[url=(.+?)\](.+?)\[/url\]#is", "\\2 --> \\1", $string);
  $string = preg_replace("#\[code\](.+?)\[/code\]#is", "\\1", $string);
  $string = preg_replace("#\[msg\](.+?)\[/msg\]#is", "\\1", $string);
  $string = preg_replace("#\[msg=(.+?)\](.+?)\[/msg\]#is", "\\2 --> \\1", $string);
  if ($settings['bbcode_img'] == 1)
   {
    $string = preg_replace("#\[img\](.+?)\[/img\]#is", "\\1", $string);
    $string = preg_replace("#\[img\|left\](.+?)\[/img\]#is", "\\1", $string);
    $string = preg_replace("#\[img\|right\](.+?)\[/img\]#is", "\\1", $string);
   }
  return $string;
 }

function smilies($string)
 {
  global $connid, $db_settings;
  $result = mysqli_query($connid, "SELECT file, code_1, code_2, code_3, code_4, code_5, title FROM ".$db_settings['smilies_table']);
  while($data = mysqli_fetch_assoc($result))
   {
    if($data['title']!='') $title = ' title="'. htmlsc($data['title']) .'"'; else $title='';
    if($data['code_1']!='') $string = str_replace($data['code_1'], "<img src=\"img/smilies/".$data['file']."\" alt=\"".$data['code_1']."\"".$title." />", $string);
    if($data['code_2']!='') $string = str_replace($data['code_2'], "<img src=\"img/smilies/".$data['file']."\" alt=\"".$data['code_2']."\"".$title." />", $string);
    if($data['code_3']!='') $string = str_replace($data['code_3'], "<img src=\"img/smilies/".$data['file']."\" alt=\"".$data['code_3']."\"".$title." />", $string);
    if($data['code_4']!='') $string = str_replace($data['code_4'], "<img src=\"img/smilies/".$data['file']."\" alt=\"".$data['code_4']."\"".$title." />", $string);
    if($data['code_5']!='') $string = str_replace($data['code_5'], "<img src=\"img/smilies/".$data['file']."\" alt=\"".$data['code_5']."\"".$title." />", $string);
   }
  mysqli_free_result($result);
  return($string);
 }

function zitat($string)
 {
  global $settings;
  $string = preg_replace("/^".htmlsc($settings['quote_symbol'])."\\s+(.*)/", "<span class=\"citation\">".htmlsc($settings['quote_symbol'])." \\1</span>", $string);
  $string = preg_replace("/\\n".htmlsc($settings['quote_symbol'])."\\s+(.*)/", "<span class=\"citation\">".htmlsc($settings['quote_symbol'])." \\1</span>", $string);
  $string = preg_replace("/\\n ".htmlsc($settings['quote_symbol'])."\\s+(.*)/", "<span class=\"citation\">".htmlsc($settings['quote_symbol'])." \\1</span>", $string);
  return $string;
 }

 function rss_quote($string)
 {
  global $settings;
  $string = preg_replace("/^".htmlsc($settings['quote_symbol'])."\\s+(.*)/", "<i>".htmlsc($settings['quote_symbol'])." \\1</i>", $string);
  $string = preg_replace("/\\n".htmlsc($settings['quote_symbol'])."\\s+(.*)/", "<i>".htmlsc($settings['quote_symbol'])." \\1</i>", $string);
  $string = preg_replace("/\\n ".htmlsc($settings['quote_symbol'])."\\s+(.*)/", "<i>".htmlsc($settings['quote_symbol'])." \\1</i>", $string);
  return $string;
 }

// connects to the database:
function connect_db($host,$user,$pw,$db) {
	global $lang;
	$connid = @mysqli_connect($host, $user, $pw, $db);  // open database connection
	if (!$connid) die($lang['db_error']);
	mysqli_set_charset($connid, "utf8mb4");
	return $connid;
}

// counts the users which are online:
function user_online()
 {
  $user_online_period = 10;
  global $connid, $db_settings, $settings;
  if (isset($_SESSION[$settings['session_prefix'].'user_id'])) $user_id = $_SESSION[$settings['session_prefix'].'user_id']; else $user_id = 0;
  $diff = time()-($user_online_period*60);
  if (isset($_SESSION[$settings['session_prefix'].'user_id'])) $ip = "uid_".$_SESSION[$settings['session_prefix'].'user_id'];
  else $ip = $_SERVER['REMOTE_ADDR'];
  @mysqli_query($connid, "DELETE FROM ". $db_settings['useronline_table'] ." WHERE time < ". intval($diff));
  list($is_online) = @mysqli_fetch_row(@mysqli_query($connid, "SELECT COUNT(*) FROM ". $db_settings['useronline_table'] ." WHERE ip= '". mysqli_real_escape_string($connid, $ip) ."'"));
  if ($is_online > 0) @mysqli_query($connid, "UPDATE ". $db_settings['useronline_table'] ." SET time='".time()."', user_id='". intval($user_id) ."' WHERE ip='". mysqli_real_escape_string($connid, $ip) ."'");
  else @mysqli_query($connid, "INSERT INTO ". $db_settings['useronline_table'] ." SET time='". time() ."', ip='". mysqli_real_escape_string($connid, $ip) ."', user_id=". intval($user_id));
  #return $user_online;
 }

 // displays the thread tree:
 function thread_tree($id, $aktuellerEintrag = 0, $tiefe = 0)
 {
  global $settings, $lang, $parent_array, $child_array, $page, $category, $order, $db_settings, $connid, $last_visit, $categories, $category_accession;

  // highlighting of admins and mods:
  $mark_admin = false; $mark_mod = false;
  if ($settings['admin_mod_highlight'] == 1 && $parent_array[$id]["user_id"] > 0)
  {
   $userdata_result=mysqli_query($connid, "SELECT user_type FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($parent_array[$id]["user_id"]));
   if (!$userdata_result) die($lang['db_error']);
   $userdata = mysqli_fetch_assoc($userdata_result);
   mysqli_free_result($userdata_result);
   if ($userdata['user_type'] == "admin") $mark_admin = true;
   elseif ($userdata['user_type'] == "mod") $mark_mod = true;
  }

  if ($mark_admin==true) $name = "<span class=\"admin-highlight\">".htmlsc($parent_array[$id]["name"])."</span>";
  elseif ($mark_mod==true) $name = "<span class=\"mod-highlight\">".htmlsc($parent_array[$id]["name"])."</span>";
  else $name=htmlsc($parent_array[$id]["name"]);
  if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $parent_array[$id]["user_id"] > 0 && $settings['show_registered']==1)
   {
    $sult = str_replace("[name]", htmlsc($parent_array[$id]["name"]), $lang['show_userdata_linktitle']);
    $thread_info_a = str_replace("[name]", $name."<a href=\"user.php?id=".$parent_array[$id]["user_id"]."\" title=\"".$sult."\"><img src=\"img/registered.gif\" alt=\"(R)\" width=\"10\" height=\"10\" /></a>", $lang['thread_info']);
   }
  elseif (!isset($_SESSION[$settings['session_prefix'].'user_id']) && $parent_array[$id]["user_id"] > 0 && $settings['show_registered']==1) $thread_info_a = str_replace("[name]", $name."<img src=\"img/registered.gif\" alt=\"(R)\" width=\"10\" height=\"10\" title=\"".$lang['registered_user_title']."\" />", $lang['thread_info']);
  else $thread_info_a = str_replace("[name]", $name, $lang['thread_info']);
  $thread_info_b = str_replace("[time]", forum_strftime($lang['time_format'],$parent_array[$id]["tp_time"]), $thread_info_a);

  ?><li><?php
  if ($id == $aktuellerEintrag && $parent_array[$id]["pid"]==0)
   {
    ?><span class="actthread"><?php echo htmlsc($parent_array[$id]["subject"]); ?></span><?php
   }
  elseif ($id == $aktuellerEintrag && $parent_array[$id]["pid"]!=0)
   {
    ?><span class="actreply"><?php echo htmlsc($parent_array[$id]["subject"]); ?></span><?php
   }
  else
   {
    ?><a class="<?php
    if ((($parent_array[$id]["pid"]==0) && isset($_SESSION[$settings['session_prefix'].'newtime']) && $_SESSION[$settings['session_prefix'].'newtime'] < $parent_array[$id]["last_answer"]) || (($parent_array[$id]["pid"]==0) && empty($_SESSION[$settings['session_prefix'].'newtime']) && $parent_array[$id]["last_answer"] > $last_visit)) echo "threadnew";
    elseif ($parent_array[$id]["pid"]==0) echo "thread";
    elseif ((($parent_array[$id]["pid"]!=0) && isset($_SESSION[$settings['session_prefix'].'newtime']) && $_SESSION[$settings['session_prefix'].'newtime'] < $parent_array[$id]["time"]) || (($parent_array[$id]["pid"]!=0) && empty($_SESSION[$settings['session_prefix'].'newtime']) && $parent_array[$id]["time"] > $last_visit)) echo "replynew";
    else echo "reply"; ?>" href="forum_entry.php?id=<?php echo $parent_array[$id]["id"]; if ($page != 0 || $category != 0 || $order != "time") echo '&amp;page='.$page.'&amp;category='.urlencode($category).'&amp;order='.$order; ?>"><?php echo htmlsc($parent_array[$id]["subject"]); ?></a><?php
   }
  echo " " . $thread_info_b;
  if ($parent_array[$id]["pid"]==0 && $category==0 && isset($categories[$parent_array[$id]["category"]]) && $categories[$parent_array[$id]["category"]]!='') { ?> <a title="<?php echo str_replace("[category]", $categories[$parent_array[$id]["category"]], $lang['choose_category_linktitle']); if (isset($category_accession[$parent_array[$id]["category"]]) && $category_accession[$parent_array[$id]["category"]] == 2) echo " ".$lang['admin_mod_category']; elseif (isset($category_accession[$parent_array[$id]["category"]]) && $category_accession[$parent_array[$id]["category"]] == 1) echo " ".$lang['registered_users_category']; ?>" href="forum.php?category=<?php echo $parent_array[$id]["category"]; ?>"><span class="<?php if (isset($category_accession[$parent_array[$id]["category"]]) && $category_accession[$parent_array[$id]["category"]] == 2) echo "category-adminmod"; elseif (isset($category_accession[$parent_array[$id]["category"]]) && $category_accession[$parent_array[$id]["category"]] == 1) echo "category-regusers"; else echo "category"; ?>">(<?php echo $categories[$parent_array[$id]["category"]]; ?>)</span></a><?php }
  if ($aktuellerEintrag == 0 && $parent_array[$id]["pid"]==0 && $parent_array[$id]["fixed"] == 1) { ?> <img src="img/fixed.gif" width="9" height="9" title="<?php echo $lang['fixed']; ?>" alt="*" /><?php }
  if ($parent_array[$id]["pid"]==0 && $settings['all_views_direct'] == 1) { echo " <span class=\"small\">"; if ($settings['board_view']==1) { ?><a href="board_entry.php?id=<?php echo $parent_array[$id]["tid"]; ?>"><img src="img/board_d.gif" alt="[Board]" title="<?php echo $lang['open_in_board_linktitle']; ?>" width="12" height="9" onmouseover="this.src='img/board_mo.gif';" onmouseout="this.src='img/board_d.gif';" /></a><?php } if ($settings['mix_view'] == 1) { ?><a href="mix_entry.php?id=<?php echo $parent_array[$id]["tid"]; ?>"><img src="img/mix_d.gif" alt="[Mix]" title="<?php echo $lang['open_in_mix_linktitle']; ?>" width="12" height="9" onmouseover="this.src='img/mix_mo.gif';" onmouseout="this.src='img/mix_d.gif';" /></a><?php } echo "</span>"; }

      if ($parent_array[$id]["pid"]==0 && isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin")
     {
      ?> <a href="admin.php?mark=<?php echo $parent_array[$id]["tid"]; ?>&amp;refer=<?php echo basename($_SERVER["PHP_SELF"]); ?>&amp;page=<?php echo $page; ?>&amp;category=<?php echo urlencode($category); ?>&amp;order=<?php echo $order; ?>"><?php
      if ($parent_array[$id]['marked']==1) { ?><img src="img/marked.gif" alt="[x]" width="9" height="9" title="<?php echo $lang['demark_linktitle']; ?>" /><?php }
      else { echo "<img src=\"img/mark.gif\" alt=\"[-]\" title=\"".$lang['mark_linktitle']."\" width=\"9\" height=\"9\" />"; }
      ?></a><?php
     }

  // display all branches of the thread tree:
  if(isset($child_array[$id]) && is_array($child_array[$id]))
   {
    ?><ul class="<?php if ($tiefe >= $settings['thread_depth_indent']) echo 'deep-reply'; else echo 'reply'; ?>"><?php
    foreach($child_array[$id] as $kind)
     {
      thread_tree($kind, $aktuellerEintrag, $tiefe+1);
     }
    ?></ul><?php
   }
  ?></li><?php
 }

function parse_template()
 {
  global $settings, $lang, $header, $footer, $wo, $ao, $topnav, $subnav_1, $subnav_2, $footer_info_dump, $search, $show_postings, $counter;
  $template = implode("",file($settings['template']));

  if ($settings['home_linkaddress'] != "" && $settings['home_linkname'] != "") $template = preg_replace("#\{IF:HOME-LINK\}(.+?)\{ENDIF:HOME-LINK\}#is", "\\1", $template);
  else $template = preg_replace("#\{IF:HOME-LINK\}(.+?)\{ENDIF:HOME-LINK\}#is", "", $template);

  $template = str_replace("{LANGUAGE}",$lang['language'],$template);
  $template = str_replace("{CHARSET}",$lang['charset'],$template);
  $title = $settings['forum_name']; if (isset($wo)) $title .= " - ". htmlsc($wo);
  $template = str_replace("{TITLE}",$title,$template);
  $template = str_replace("{FORUM-NAME}", $settings['forum_name'],$template);
  $template = str_replace('{HOME-ADDRESS}',$settings['home_linkaddress'],$template);
  $template = str_replace('{HOME-LINK}',$settings['home_linkname'],$template);
  $template = str_replace('{FORUM-INDEX-LINK}',$lang['forum_home_linkname'],$template);
  $template = str_replace('{FORUM-INDEX-LINKTITLE}',$lang['forum_home_linktitle'],$template);
  $template = str_replace('{FORUM-ADDRESS}',$settings['forum_address'],$template);
  $template = str_replace('{CONTACT}',$lang['contact_linkname'],$template);

  // User menu:
  if (isset($_SESSION[$settings['session_prefix']."user_name"])) { if (isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type']=="admin") $user_menu_admin = ' | <a href="admin.php" title="'.$lang['admin_area_linktitle'].'">'.$lang['admin_area_linkname'].'</a>'; else $user_menu_admin = ""; $user_menu = '<a href="user.php?id='.$_SESSION[$settings['session_prefix'].'user_id'].'" title="'.$lang['own_userdata_linktitle'].'"><b>'.htmlsc($_SESSION[$settings['session_prefix'].'user_name']).'</b></a> | <a href="user.php" title="'.$lang['user_area_linktitle'].'">'.$lang['user_area_linkname'].'</a>'.$user_menu_admin.' | <a href="login.php" title="'.$lang['logout_linktitle'].'">'.$lang['logout_linkname'].'</a>'; }
  else $user_menu = '<a href="login.php" title="'.$lang['login_linktitle'].'">'.$lang['login_linkname'].'</a> | <a href="register.php" title="'.$lang['register_linktitle'].'">'.$lang['register_linkname'].'</a>';
  $template = str_replace("{USER-MENU}",$user_menu,$template);

  // Search:
  $search_dump = '<form action="search.php" method="get" title="'.$lang['search_formtitle'].'"><div class="search">';
  $search_dump .= $lang['search_marking'];
  # if (isset($search)) $search_match = htmlsc($search); else $search_match = "";
  $search_dump .= '<span class="normal">&nbsp;</span><input class="searchfield" type="text" name="search" value="" size="20" /><span class="normal">&nbsp;</span><input type="image" name="" src="img/submit.gif" alt="&raquo;" /></div></form>';
  $template = str_replace("{SEARCH}",$search_dump,$template);

  // Sub navigation:
  if (isset($topnav)) $tnd = $topnav; else $tnd = ""; $template = str_replace("{LOCATION}",$tnd,$template);
  #if (isset($topnav)) $subnav_1_dump = $topnav; elseif (isset($subnav_1)) $subnav_1_dump = $subnav_1; else $subnav_1_dump = "&nbsp;";
  if (isset($subnav_1)) $subnav_1_dump = $subnav_1; else $subnav_1_dump = "&nbsp;";
  $template = str_replace("{SUB-NAVIGATION-1}",$subnav_1_dump,$template);
  if (isset($subnav_2)) $subnav_2_dump = $subnav_2; else $subnav_2_dump = "&nbsp;";
  $template = str_replace("{SUB-NAVIGATION-2}",$subnav_2_dump,$template);

  // Footer:
  $template = str_replace("{COUNTER}",$counter,$template);
  if ($settings['provide_rssfeed'] == 1 && $settings['access_for_users_only'] == 0) 
   { 
    $rss_feed_link = '<link rel="alternate" type="application/rss+xml" title="RSS Feed" href="rss.php" />';
    $rss_feed_button = '<a href="rss.php"><img src="img/rss.png" width="14" height="14" alt="RSS Feed" /></a><br />'; 
   }
  else 
   {
    $rss_feed_link = '';
    $rss_feed_button = '';
   }
  $template = str_replace("{RSS-FEED-LINK}",$rss_feed_link,$template);
  $template = str_replace("{RSS-FEED-BUTTON}",$rss_feed_button,$template);

  $template_parts = explode("{CONTENT}",$template);
  if (isset($template_parts[0])) $header = $template_parts[0]; else $header = "";
  if (isset($template_parts[1])) $footer = $template_parts[1]; else $footer = "";
 }

/**
 * encapsulated htmlspecialchars with the correct charset
 *
 * @param string @string
 * @return string @string
 */
function htmlsc($string) {
	global $lang;
	return htmlspecialchars($string, ENT_QUOTES, $lang['charset'], false);
}


/** Format the strftime-style date formats used by legacy language files without deprecated strftime(). */
function forum_strftime($format, $timestamp = null) {
    $timestamp = ($timestamp === null) ? time() : (int)$timestamp;
    $map = array('a'=>'D','A'=>'l','b'=>'M','B'=>'F','c'=>'D M d H:i:s Y','d'=>'d','D'=>'m/d/y','e'=>'j','F'=>'Y-m-d','g'=>'o','G'=>'o','h'=>'M','H'=>'H','I'=>'h','j'=>'z','m'=>'m','M'=>'i','n'=>"\n",'p'=>'A','r'=>'h:i:s A','R'=>'H:i','S'=>'s','t'=>"\t",'T'=>'H:i:s','u'=>'N','U'=>'W','V'=>'W','w'=>'w','W'=>'W','x'=>'m/d/y','X'=>'H:i:s','s'=>'U','y'=>'y','Y'=>'Y','z'=>'O','Z'=>'T','%'=>'%');
    return preg_replace_callback('/%([aAbBcCdDeFgGhHIjmMnprRStTuUVwWxXyYZzs%])/', function ($m) use ($map, $timestamp) {
        $directive = $m[1];
        if ($directive === 'C') return sprintf('%02d', (int)floor((int)date('Y', $timestamp) / 100));
        if (!array_key_exists($directive, $map)) return $m[0];
        $replacement = $map[$directive];
        if ($replacement === "\n" || $replacement === "\t" || $replacement === '%') return $replacement;
        return date($replacement, $timestamp);
    }, (string)$format);
}

/** Hash new passwords with PHP's supported password hashing algorithm. */
function forum_password_hash($password) { return password_hash((string)$password, PASSWORD_DEFAULT); }

/** Verify legacy MD5 password hashes and current password_hash() hashes. */
function forum_password_verify($password, $stored_hash) {
    if (!is_string($stored_hash) || $stored_hash === '') return false;
    if (preg_match('/\A[a-f0-9]{32}\z/i', $stored_hash)) return hash_equals(strtolower($stored_hash), md5((string)$password));
    return password_verify((string)$password, $stored_hash);
}

/** Upgrade weak or obsolete hashes after successful authentication. */
function forum_password_needs_rehash($stored_hash) {
    if (!is_string($stored_hash) || preg_match('/\A[a-f0-9]{32}\z/i', $stored_hash)) return true;
    return password_needs_rehash($stored_hash, PASSWORD_DEFAULT);
}

/** Write application cookies with safer default attributes. */
function forum_set_cookie($name, $value, $expires = 0) {
    $secure = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    $path = str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/'));
    if ($path === '.' || $path === '/') $path = '/'; else $path = rtrim($path, '/') . '/';
    if ((int)$expires <= 0) $expires = time() - 3600;
    return setcookie((string)$name, (string)$value, array('expires'=>(int)$expires, 'path'=>$path, 'secure'=>$secure, 'httponly'=>true, 'samesite'=>'Lax'));
}

/** Restrict post-login redirects to known local forum endpoints. */
function forum_safe_return_page($candidate) {
    $allowed = array('index.php','board.php','forum.php','mix.php','board_entry.php','forum_entry.php','mix_entry.php','user.php','search.php');
    if (!is_string($candidate) || $candidate === '') return 'index.php';
    if (strpos($candidate, '/') !== false || strpos($candidate, '\\') !== false || strpos($candidate, ':') !== false || strpos($candidate, '?') !== false || strpos($candidate, '#') !== false) return 'index.php';
    return in_array($candidate, $allowed, true) ? $candidate : 'index.php';
}

/** Validate a submitted per-session CSRF token using a constant-time comparison. */
function forum_csrf_token_valid($candidate) {
    return isset($_SESSION['_mlf_csrf_token']) && is_string($_SESSION['_mlf_csrf_token']) &&
        is_string($candidate) && $candidate !== '' && hash_equals($_SESSION['_mlf_csrf_token'], $candidate);
}

/** Query-string actions in this legacy version that change data and need CSRF protection. */
function forum_csrf_protected_get_actions($page) {
    $actions = array(
        'admin.php' => array('mark','move_up_category','move_down_category','user_lock','delete_smiley','enable_smilies','disable_smilies','move_up_smiley','move_down_smiley'),
        'user.php' => array('user_lock'),
        'posting.php' => array('lock'),
        'login.php' => array('action:logout')
    );
    return isset($actions[$page]) ? $actions[$page] : array();
}

/** Check both legacy action parameters and action=... routes for CSRF-sensitive GETs. */
function forum_csrf_request_requires_token($page, $query) {
    if (!is_array($query)) return false;
    foreach (forum_csrf_protected_get_actions($page) as $protected_action) {
        if (strpos($protected_action, 'action:') === 0) {
            $value = substr($protected_action, 7);
            if (isset($query['action']) && is_string($query['action']) && $query['action'] === $value) return true;
        } elseif (array_key_exists($protected_action, $query)) {
            return true;
        }
    }
    return false;
}

/** Add a hidden token to POST forms and a query token to legacy state-changing links. */
function forum_inject_csrf_fields($html) {
    if (!isset($_SESSION['_mlf_csrf_token']) || !is_string($_SESSION['_mlf_csrf_token'])) return $html;
    $token = $_SESSION['_mlf_csrf_token'];
    $escaped_token = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
    $html = preg_replace_callback('~<form\b[^>]*>~i', function ($match) use ($escaped_token) {
        $tag = $match[0];
        if (!preg_match('/\bmethod\s*=\s*([\'\"]?)post\1/i', $tag) || preg_match('/\bname\s*=\s*([\'\"]?)_mlf_csrf_token\1/i', $tag)) return $tag;
        return $tag . '<input type="hidden" name="_mlf_csrf_token" value="' . $escaped_token . '" />';
    }, $html);

    $html = preg_replace_callback('~<a\b[^>]*>~i', function ($match) use ($token) {
        $tag = $match[0];
        if (!preg_match('/\bhref\s*=\s*([\'\"])(.*?)\1/is', $tag, $href, PREG_OFFSET_CAPTURE)) return $tag;
        $attribute = $href[0][0];
        $quote = $href[1][0];
        $url = html_entity_decode($href[2][0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = @parse_url($url);
        if (!is_array($parts) || isset($parts['scheme']) || isset($parts['host']) || !isset($parts['path'])) return $tag;
        $page = basename($parts['path']);
        $protected_actions = forum_csrf_protected_get_actions($page);
        if (!$protected_actions) return $tag;
        $query = array();
        if (isset($parts['query'])) parse_str($parts['query'], $query);
        $must_protect = forum_csrf_request_requires_token($page, $query);
        // In this legacy app, opening login.php with an authenticated session logs the user out.
        global $settings;
        if ($page === 'login.php' && isset($settings['session_prefix']) && isset($_SESSION[$settings['session_prefix'].'user_id']) && !array_key_exists('action', $query)) $must_protect = true;
        if (!$must_protect || array_key_exists('_mlf_csrf_token', $query)) return $tag;

        $fragment = '';
        if (isset($parts['fragment'])) {
            $fragment = '#' . $parts['fragment'];
            $url = substr($url, 0, strrpos($url, '#'));
        }
        $separator = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $separator . '_mlf_csrf_token=' . rawurlencode($token) . $fragment;
        $replacement = substr($attribute, 0, strpos($attribute, '=') + 1) . $quote . htmlspecialchars($url, ENT_QUOTES, 'UTF-8', false) . $quote;
        $offset = $href[0][1];
        return substr($tag, 0, $offset) . $replacement . substr($tag, $offset + strlen($attribute));
    }, $html);
    return $html;
}

?>
