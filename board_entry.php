<?php
###############################################################################
# my little forum                                                             #
# Copyright (C) 2005 Alex                                                     #
# http://www.mylittlehomepage.net/                                            #
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

include("inc.php");

if(count($_GET) > 0)
foreach($_GET as $key => $value)
$$key = $value;

function nav_b($be_page, $entries_per_page, $entry_count, $id, $da, $nr, $page, $category, $order, $descasc) {
global $lang, $page_navigation_pd_menu, $page_navigation_arrows, $select_submit_button;

$output = '';

if ($entry_count > $entries_per_page)
	{
	$output .= '&nbsp;&nbsp;';
	$new_index_before = $be_page - 1;
	$new_index_after = $be_page + 1;
	$site_count = ceil($entry_count / $entries_per_page);
	if ($new_index_before >= 0)
		{
		$output .= '<a href="'.basename($_SERVER["SCRIPT_NAME"]).'?&amp;id='.$id.'&amp;be_page=';
		$output .= $new_index_before.'&amp;da='.$da.'&amp;page='.$page.'&amp;category=';
		$output .= $category.'&amp;order='.$order.'&amp;descasc='.$descasc.'" title="';
		$output .= $lang['previous_page_linktitle'].'"><img src="img/prev.gif" alt="&laquo;"';
		$output .= 'width="12" height="9" onmouseover="this.src=\'img/prev_mo.gif\';"';
		$output .= ' onmouseout="this.src=\'img/prev.gif\';" /></a>';
		}
	if ($new_index_before >= 0 && $new_index_after < $site_count)
		{
		$output .= '&nbsp;';
		}
	if ($new_index_after < $site_count)
		{
		$output .= '<a href="'.basename($_SERVER["SCRIPT_NAME"]).'?&amp;id='.$id.'&amp;be_page=';
		$output .= $new_index_after.'&amp;da='.$da.'&amp;page='.$page.'&amp;category='.$category;
		$output .= '&amp;order='.$order.'&amp;descasc='.$descasc.'" title="';
		$output .= $lang['next_page_linktitle'].'"><img src="img/next.gif" alt="&laquo;"';
		$output .= 'width="12" height="9" onmouseover="this.src=\'img/next_mo.gif\';"';
		$output .= ' onmouseout="this.src=\'img/next.gif\';" /></a>';
		}
	$page_count = ceil($entry_count/$entries_per_page);
	$output .= '&nbsp;&nbsp;<form method="get" action="'.basename($_SERVER["SCRIPT_NAME"]).'"';
	$output .= ' title="'.$lang['choose_page_formtitle'].'">'."\n".'<div style="display: inline;">'."\n";
	if (isset($id))
		{
		$output .= '<input type="hidden" name="id" value="'.$id.'">'."\n";
		}
	if (isset($da))
		{
		$output .= '<input type="hidden" name="da" value="'.$da.'">'."\n";
		}
	$output .= '<input type="hidden" name="page" value="'.$page.'">'."\n";
	$output .= '<input type="hidden" name="category" value="'.$category.'">'."\n";
	$output .= '<input type="hidden" name="order" value="'.$order.'">'."\n";
	$output .= '<input type="hidden" name="descasc" value ="'.$descasc.'">'."\n";
	$output .= '<select class="kat" size="1" name="be_page" onchange="this.form.submit();">'."\n";
	$output .= '<option value="0"';
	$output .= ($be_page == 0) ? ' selected="selected"' : '';
	$output .= '>1</option>'."\n";
	for ($x=$be_page-9; $x<$be_page+9; $x++)
		{
		$output .= '<option value="'.$x.'"';
		$output .= ($x > 0 && $x < $page_count-1) ? ' selected="selected"' : '';
		$output .= '>'.($x+1).'</option>'."\n";
		}
	$output .= '<option value="'.($page_count-1).'"';
	$output .= ($be_page+1 == $page_count) ? ' selected="selected"' : '';
	$output .= '>'.$page_count.'</option>'."\n";
	$output .= '</select>'."\n".'&nbsp;<input type="image" name="" value="" src="img/submit.gif" alt="&raquo;" />'."\n".'</div>'."\n".'</form>'."\n";
	}
return $output;
}

if (!isset($_SESSION[$settings['session_prefix'].'user_id'])
&& isset($_COOKIE['auto_login'])
&& isset($settings['autologin'])
&& $settings['autologin'] == 1)
	{
	$id = (isset($_GET['id'])) ? $_GET['id'] : '';
	header("location: ".$settings['forum_address']."login.php?referer=board_entry.php&id=".$id);
	die("<a href=\"login.php?referer=board_entry.php&id=".$id."\">further...</a>");
	}

if ($settings['access_for_users_only'] == 1
&& isset($_SESSION[$settings['session_prefix'].'user_name'])
|| $settings['access_for_users_only'] != 1)
	{
	if (empty($page)) $page = 0;
	if (empty($order)) $order="last_answer";
	if (empty($descasc)) $descasc="DESC";
	if (empty($category)) $category="all";
	if (empty($be_page)) $be_page = 0;
	if (empty($da)) $da="ASC";
	$ul = $be_page * $settings['answers_per_topic'];

	unset($entrydata);
	unset($thread);

	if(isset($id))
		{
		$id = (int) $id;
		if($id > 0)
			{
			$firstPostingQuery = "SELECT
			id,
			tid,
			pid,
			user_id,
			UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS p_time,
			UNIX_TIMESTAMP(time) AS time,
			UNIX_TIMESTAMP(edited + INTERVAL ".$time_difference." HOUR) AS e_time,
			UNIX_TIMESTAMP(edited - INTERVAL ".$settings['edit_delay']." MINUTE) AS edited_diff,
			edited_by,
			user_id,
			name,
			email,
			subject,
			hp,
			place,
			text,
			show_signature,
			category,
			locked,
			ip
			FROM ".$db_settings['forum_table']."
			WHERE id = ".$id." LIMIT 1";
			$result_t = mysql_query($firstPostingQuery, $connid);
			$thread = mysql_fetch_assoc($result_t);
			mysql_free_result($result_t);

			# Look if id correct:
			if ($thread['pid'] != 0)
				{
				header("location: ".$settings['forum_address'].basename($_SERVER['SCRIPT_NAME'])."?id=".$thread['tid']."&page=".$page."&category=".$category."&order=".$order."&descasc=".$descasc."#p".$id);
				}

			# category of this posting accessible by user?
			if (!(isset($_SESSION[$settings['session_prefix'].'user_type'])
			&& $_SESSION[$settings['session_prefix'].'user_type'] == "admin"))
				{
				if (is_array($category_ids) && !in_array($thread['category'], $category_ids))
					{
					header("location: ".$settings['forum_address']."board.php");
					die();
					}
				}

			// count views:
			if (isset($settings['count_views']) && $settings['count_views'] == 1)
				{
				mysql_query("UPDATE ".$db_settings['forum_table']." SET time=time, last_answer=last_answer, edited=edited, views=views+1 WHERE tid=".$id, $connid);
				}

			$mark['admin'] = false;
			$mark['mod'] = false;
			$mark['user'] = false;
			if ($thread["user_id"] > 0)
				{
				$userdataByIdQuery = "SELECT
				user_name,
				user_type,
				user_email,
				hide_email,
				user_hp,
				user_place,
				signature
				FROM ".$db_settings['userdata_table']."
				WHERE user_id = ".intval($thread["user_id"]);
				$userdata_result_t = mysql_query($userdataByIdQuery, $connid);
				if (!$userdata_result_t) die($lang['db_error']);
				$userdata = mysql_fetch_assoc($userdata_result_t);
				mysql_free_result($userdata_result_t);
				$thread["email"] = $userdata["user_email"];
				$thread["hide_email"] = $userdata["hide_email"];
				$thread["place"] = $userdata["user_place"];
				$thread["hp"] = $userdata["user_hp"];
				if ($userdata["user_type"] == "admin" && $settings['admin_mod_highlight'] == 1)
					{
					$mark['admin'] = true;
					}
				else if ($userdata["user_type"] == "mod" && $settings['admin_mod_highlight'] == 1)
					{
					$mark['mod'] = true;
					}
				else if ($userdata["user_type"] == "user" && $settings['user_highlight'] == 1)
					{
					$mark['user'] = true;
					}
				if ($thread["show_signature"]==1)
					{
					$signature = $userdata["signature"];
					}
				} # End: if ($thread["user_id"] > 0)
			$allPostingsQuery = "SELECT
			id,
			tid,
			pid,
			user_id,
			UNIX_TIMESTAMP(time + INTERVAL ".$time_difference." HOUR) AS p_time,
			UNIX_TIMESTAMP(time) AS time,
			UNIX_TIMESTAMP(edited + INTERVAL ".$time_difference." HOUR) AS e_time,
			UNIX_TIMESTAMP(edited - INTERVAL ".$settings['edit_delay']." MINUTE) AS edited_diff,
			edited_by,
			name,
			email,
			subject,
			hp,
			place,
			text,
			show_signature,
			category,
			locked,
			ip
			FROM ".$db_settings['forum_table']."
			WHERE tid = ".$id." AND id != ".$id."
			ORDER BY time ".$da."
			LIMIT ".$ul.", ".$settings['answers_per_topic'];
			$result = mysql_query($allPostingsQuery, $connid);
			$result_c = mysql_query("SELECT tid FROM ".$db_settings['forum_table']." WHERE tid = ".$id." AND id != ".$id, $connid);
			if(!$result or !$result_t) die($lang['db_error']);
			$thread_count = mysql_num_rows($result_c);
			mysql_free_result($result_c);
			}
		} # End: if ($thread['pid'] != 0)
	else
		{
		header("location: ".$settings['forum_address']."board.php");
		}

	if (empty($thread))
		{
		header("location: ".$settings['forum_address']."board.php");
		}

	$wo = $thread["subject"];
	$subnav_1  = '<a class="textlink" href="board.php?page='.$page.'&amp;category='.$category;
	$subnav_1 .= '&amp;order='.$order.'&amp;descasc='.$descasc.'" title="';
	$subnav_1 .= $lang['back_to_board_linktitle'].'">'.$lang['back_to_board_linkname'].'</a>';
	$subnav_2 = '';
	if ($da=="DESC")
		{
		$order_order = 'ASC';
		$order_title = $lang['order_linktitle_3'];
		$order_text  = $lang['order_linkname'];
		}
	else
		{
		$order_order = 'DESC';
		$order_title = $lang['order_linktitle_4'];
		$order_text  = $lang['order_linkname'];
		}
	$subnav_2 .= '&nbsp;<a href="board_entry.php?id='.$thread["tid"].'&amp;da=';
	$subnav_2 .= $order_order.'&amp;page='.$page.'&amp;order='.$order.'&amp;descasc=';
	$subnav_2 .= $descasc.'&amp;category='.$category.'" class="order-postings" title="';
	$subnav_2 .= $order_title.'">'.$order_text.'</a>';
	if ($settings['thread_view']==1)
		{
		$subnav_2 .= '&nbsp;<a href="forum_entry.php?id='.$thread["tid"].'&amp;page='.$page;
		$subnav_2 .= '&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category='.$category;
		$subnav_2 .= '" class="thread-view" title="'.$lang['thread_view_linktitle'].'">';
		$subnav_2 .= $lang['thread_view_linkname'].'</a>';
		}
	if ($settings['mix_view']==1)
		{
		$subnav_2 .= '&nbsp;<a href="mix_entry.php?id='.$thread["tid"].'&amp;page='.$page;
		$subnav_2 .= '&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category='.$category;
		$subnav_2 .= '" class="mix-view" title="'.$lang['mix_view_linktitle'].'">';
		$subnav_2 .= $lang['mix_view_linkname'].'</a>';
		}
	$subnav_2 .= nav_b($be_page, $settings['answers_per_topic'], $thread_count, $thread["tid"], $da, 1, $page, $category, $order, $descasc);

	parse_template();
	echo $header;

	echo '<table class="board-entry">'."\n";
	if ($be_page==0)
		{
		echo '<tr>'."\n";
		echo '<td class="autorcell" rowspan="2" valign="top">'."\n";
		// wenn eingelogged und Posting von einem angemeldeten User stammt, dann Link zu dessen Userdaten:
		echo outputAuthorInfo($mark, $thread, $page, $order, 'board', $category);
		if ($settings['user_edit'] == 1
		&& isset($_SESSION[$settings['session_prefix'].'user_id'])
		&& $thread["user_id"] == $_SESSION[$settings['session_prefix']."user_id"]
		|| isset($_SESSION[$settings['session_prefix'].'user_id'])
		&& $_SESSION[$settings['session_prefix']."user_type"] == "admin"
		|| isset($_SESSION[$settings['session_prefix'].'user_id'])
		&& $_SESSION[$settings['session_prefix']."user_type"] == "mod")
			{
			echo '<br /><br /><span class="small"><a href="posting.php?action=edit&amp;id=';
			echo $thread["id"].'&amp;view=board&amp;back='.$thread["tid"].'&amp;page='.$page;
			echo '&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category='.$category;
			echo '" title="'.$lang['edit_linktitle'].'"><img src="img/edit.gif" alt="" width="15"';
			echo ' height="10" title="'.$lang['edit_linktitle'].'" />';
			echo $lang['edit_linkname'].'</a></span>';
			}
		if ($settings['user_delete'] == 1
		&& isset($_SESSION[$settings['session_prefix'].'user_id'])
		&& $thread["user_id"] == $_SESSION[$settings['session_prefix']."user_id"]
		|| isset($_SESSION[$settings['session_prefix'].'user_id'])
		&& $_SESSION[$settings['session_prefix']."user_type"] == "admin"
		|| isset($_SESSION[$settings['session_prefix'].'user_id'])
		&& $_SESSION[$settings['session_prefix']."user_type"] == "mod")
			{
			echo '<br /><span class="small"><a href="posting.php?action=delete&amp;id=';
			echo $thread["id"].'&amp;back='.$thread["tid"].'&amp;view=board&amp;page=';
			echo $page.'&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category=';
			echo $category.'" title="'.$lang['delete_linktitle'].'"><img src="img/delete.gif"';
			echo ' alt="" width="12" height="9" title="'.$lang['delete_linktitle'].'" />';
			echo $lang['delete_linkname'].'</a></span>';
			}
		if (isset($_SESSION[$settings['session_prefix'].'user_id'])
		&& $_SESSION[$settings['session_prefix']."user_type"] == "admin"
		|| isset($_SESSION[$settings['session_prefix'].'user_id'])
		&& $_SESSION[$settings['session_prefix']."user_type"] == "mod")
			{
			echo '<br /><span class="small"><a href="posting.php?lock=true&amp;view=board&amp;id=';
			echo $thread["id"].'&amp;page='.$page.'&amp;order='.$order.'&amp;descasc=';
			echo $descasc.'&amp;category='.$category.'" title="';
			echo ($thread['locked'] == 0) ? $lang['lock_linktitle'] : $lang['unlock_linktitle'];
			echo '"><img src="img/lock.gif" alt="" width="12" height="12" title="';
			echo ($thread['locked'] == 0) ? $lang['lock_linktitle'] : $lang['unlock_linktitle'];
			echo '" />';
			echo ($thread['locked'] == 0) ? $lang['lock_linkname'] : $lang['unlock_linkname'];
			echo '</a></span>';
			}
		echo '<div class="autorcellwidth">&nbsp;</div></td>'."\n";
		echo '<td class="titlecell" valign="top">'."\n".'<div class="left">';
		echo '<h2>'.htmlspecialchars($thread["subject"]);
		if(isset($categories[$thread["category"]]) && $categories[$thread["category"]]!='')
			{
			echo "&nbsp;<span class=\"category\">(".$categories[$thread["category"]].")</span>";
			}
		echo '</h2></div>'."\n".'<div class="right">';
		if ($thread['locked'] == 0)
			{
			echo '<a class="textlink" href="posting.php?id='.$thread["id"];
			if (isset($page) && isset($order) && isset($descasc) && isset($category))
				{
				echo '&amp;page='.$page.'&amp;category='.$category;
				echo '&amp;order='.$order.'&amp;descasc='.$descasc;
				}
			echo '&amp;view=board" title="'.$lang['board_answer_linktitle'].'">';
			echo $lang['board_answer_linkname'].'</a>';
			}
		else
			{
			echo '<span class="xsmall"><img src="img/lock.gif" alt="" width="12" height="12" />';
			echo $lang['thread_locked'].'</span>';
			}
		echo '</div></td>'."\n";
		echo '</tr><tr>'."\n";
		echo '<td class="postingcell" valign="top">';
		if ($thread["text"]=="")
			{
			echo $lang['no_text'];
			}
		else
			{
			$ftext=$thread["text"];
			$ftext = htmlspecialchars($ftext);
			$ftext = nl2br($ftext);
			$ftext = zitat($ftext);
			if ($settings['autolink'] == 1)
				{
				$ftext = make_link($ftext);
				}
			if ($settings['bbcode'] == 1)
				{
				$ftext = bbcode($ftext);
				}
			if ($settings['smilies'] == 1)
				{
				$ftext = smilies($ftext);
				}
			echo '<p class="postingboard">'.$ftext.'</p>';
			}
		if (isset($signature) && $signature != "")
			{
			$signature = htmlspecialchars($signature);
			$signature = nl2br($signature);
			if ($settings['autolink'] == 1)
				{
				$signature = make_link($signature);
				}
			if ($settings['bbcode'] == 1)
				{
				$signature = bbcode($signature);
				}
			if ($settings['smilies'] == 1)
				{
				$signature = smilies($signature);
				}
			echo '<p class="signature">'.$settings['signature_separator'].$signature.'</p>';
			}
		echo '</td>'."\n";
		echo '</tr>';
		}
	$i=0;
	while ($entrydata = mysql_fetch_assoc($result))
		{
		unset($signature);
		$mark['admin'] = false;
		$mark['mod'] = false;
		$mark['user'] = false;
		if ($entrydata["user_id"] > 0)
			{
			$userdata_result=mysql_query("SELECT user_name, user_type, user_email, hide_email, user_hp, user_place, signature FROM ".$db_settings['userdata_table']." WHERE user_id = '".$entrydata["user_id"]."'", $connid);
			if (!$userdata_result) die($lang['db_error']);
			$userdata = mysql_fetch_array($userdata_result);
			mysql_free_result($userdata_result);
			$entrydata["email"] = $userdata["user_email"];
			$entrydata["hide_email"] = $userdata["hide_email"];
			$entrydata["place"] = $userdata["user_place"];
			$entrydata["hp"] = $userdata["user_hp"];
			if ($userdata["user_type"] == "admin" && $settings['admin_mod_highlight'] == 1)
				{
				$mark['admin'] = true;
				}
			else if ($userdata["user_type"] == "mod" && $settings['admin_mod_highlight'] == 1)
				{
				$mark['mod'] = true;
				}
			else if ($userdata["user_type"] == "user" && $settings['user_highlight'] == 1)
				{
				$mark['user'] = true;
				}
			if ($entrydata["show_signature"]==1)
				{
				$signature = $userdata["signature"];
				}
			}

		# Posting heraussuchen, auf das geantwortet wurde:
		$result_a = mysql_query("SELECT name FROM ".$db_settings['forum_table']." WHERE id = ".$entrydata["pid"], $connid);
		$posting_a = mysql_fetch_assoc($result_a);
		mysql_free_result($result_a);
		$entrydata['answer'] = $posting_a['name'];

		echo '<tr>'."\n";
		echo '<td class="autorcell" rowspan="2" valign="top">'."\n";
		# wenn eingelogged und Posting von einem angemeldeten User stammt, dann Link zu dessen Userdaten:
		echo outputAuthorInfo($mark, $entrydata, $page, $order, 'board', $category);
		if ($settings['user_edit'] == 1
		&& isset($_SESSION[$settings['session_prefix'].'user_id'])
		&& $entrydata["user_id"] == $_SESSION[$settings['session_prefix']."user_id"]
		|| isset($_SESSION[$settings['session_prefix'].'user_id'])
		&& $_SESSION[$settings['session_prefix']."user_type"] == "admin"
		|| isset($_SESSION[$settings['session_prefix'].'user_id'])
		&& $_SESSION[$settings['session_prefix']."user_type"] == "mod")
			{
			echo '<br /><br /><span class="small"><a href="posting.php?action=edit&amp;id=';
			echo $entrydata["id"].'&amp;view=board&amp;back='.$entrydata["tid"].'&amp;page=';
			echo $page.'&amp;order='.$order.'&amp;descasc='.$descasc.'&amp;category=';
			echo $category.'" title="'.$lang['edit_linktitle'].'"><img src="img/edit.gif"';
			echo ' alt="" width="15" height="10" title="'.$lang['edit_linktitle'].'" />';
			echo $lang['edit_linkname'].'</a></span>';
			}
		if ($settings['user_delete'] == 1
		&& isset($_SESSION[$settings['session_prefix'].'user_id'])
		&& $entrydata["user_id"] == $_SESSION[$settings['session_prefix']."user_id"]
		|| isset($_SESSION[$settings['session_prefix'].'user_id'])
		&& $_SESSION[$settings['session_prefix']."user_type"] == "admin"
		|| isset($_SESSION[$settings['session_prefix'].'user_id'])
		&& $_SESSION[$settings['session_prefix']."user_type"] == "mod")
			{
			echo '<br /><span class="small"><a href="posting.php?action=delete&amp;id=';
			echo $entrydata["id"].'&amp;back='.$entrydata["tid"].'&amp;view=board&amp;page=';
			echo $page.'&amp;order='.$order.'&amp;category='.$category.'" title="';
			echo $lang['delete_linktitle'].'"><img src="img/delete.gif" alt="" width="12"';
			echo ' height="9" title="'.$lang['delete_linktitle'].'">';
			echo $lang['delete_linkname'].'</a></span>';
			}
		echo '</td>'."\n";
		echo '<td class="titlecell" valign="top">'."\n";
		echo '<div class="left"><h2>'.htmlspecialchars($entrydata["subject"]).'</h2></div>'."\n";
		echo '<div class="right">'."\n";
		if ($entrydata['locked'] == 0)
			{
			echo '<a class="textlink" href="posting.php?id='.$entrydata["id"];
			if (isset($page) && isset($order) && isset($descasc) && isset($category))
				{
				echo '&amp;page='.$page.'&amp;category='.$category;
				echo '&amp;order='.$order.'&amp;descasc='.$descasc;
				}
			echo '&amp;view=board" title="'.$lang['board_answer_linktitle'].'">';
			echo $lang['board_answer_linkname'].'</a>';
			}
		else
			{
			echo "&nbsp;";
			}
		echo '</div></td>'."\n";
		echo '</tr><tr>'."\n";
		echo '<td class="postingcell" valign="top">';
		if ($entrydata["text"]=="")
			{
			echo $lang['no_text'];
			}
		else
			{
			$ftext=$entrydata["text"];
			$ftext = htmlspecialchars($ftext);
			$ftext = nl2br($ftext);
			$ftext = zitat($ftext);
			if ($settings['autolink'] == 1)
				{
				$ftext = make_link($ftext);
				}
			if ($settings['bbcode'] == 1)
				{
				$ftext = bbcode($ftext);
				}
			if ($settings['smilies'] == 1)
				{
				$ftext = smilies($ftext);
				}
			echo '<p class="postingboard">'.$ftext.'</p>'."\n";
			}
		if (isset($signature) && $signature != "")
			{
			$signature = htmlspecialchars($signature);
			$signature = nl2br($signature);
			if ($settings['autolink'] == 1)
				{
				$signature = make_link($signature);
				}
			if ($settings['bbcode'] == 1)
				{
				$signature = bbcode($signature);
				}
			if ($settings['smilies'] == 1)
				{
				$signature = smilies($signature);
				}
			echo '<p class="signature">'.$settings['signature_separator'].$signature.'</p>'."\n";
			}
		echo '</td>'."\n";
		echo '</tr>'."\n";
		}
	mysql_free_result($result);
	echo '</table>'."\n";
	echo $footer;
	}
else
	{
	header("location: ".$settings['forum_address']."login.php?msg=noaccess");
	die("<a href=\"login.php?msg=noaccess\">further...</a>");
	}
?>
