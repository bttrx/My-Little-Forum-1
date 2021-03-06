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

include("inc.php");

if (count($_GET) > 0)
foreach ($_GET as $key => $value)
$$key = $value;

if (!isset($_SESSION[$settings['session_prefix'].'user_id']) && isset($_COOKIE['auto_login']) && isset($settings['autologin']) && $settings['autologin'] == 1) {
	if (isset($_GET['id'])) $id = $_GET['id']; else $id = "";
	header("location: login.php?referer=mix_entry.php&id=".$id);
	die('<a href="login.php?referer=mix_entry.php&id='. intval($id) .'">further...</a>');
}

if ($settings['access_for_users_only'] == 1 && isset($_SESSION[$settings['session_prefix'].'user_name']) || $settings['access_for_users_only'] != 1) {
	function thread($id, $aktuellerEintrag = 0, $tiefe = 0) {
		global $settings, $connid, $lang, $db_settings, $parent_array, $child_array, $user_delete, $page, $category, $order, $descasc, $time_difference, $categories;
		$posting_result = mysqli_query($connid, "SELECT id, pid, tid, pid, user_id, UNIX_TIMESTAMP(time + INTERVAL ". $time_difference ." HOUR) AS Uhrzeit,
                      UNIX_TIMESTAMP(time) AS time, UNIX_TIMESTAMP(edited + INTERVAL ". $time_difference ." HOUR) AS e_Uhrzeit,
                      UNIX_TIMESTAMP(edited - INTERVAL ". $settings['edit_delay'] ." MINUTE) AS edited_diff, edited_by, name, email,
                      subject, hp, place, text, category, show_signature, locked FROM ". $db_settings['forum_table'] ."
                      WHERE id = ". intval($parent_array[$id]["id"]) ." ORDER BY time ASC");
		if(!$posting_result) die($lang['db_error']);
		$entrydata = mysqli_fetch_assoc($posting_result);
		mysqli_free_result($posting_result);

		if ($entrydata["user_id"] > 0) {
			$userdata_result=mysqli_query($connid, "SELECT user_name, user_email, hide_email, user_hp, user_place, signature FROM ". $db_settings['userdata_table'] ." WHERE user_id = ". intval($entrydata["user_id"]));
			if (!$userdata_result) die($lang['db_error']);
			$userdata = mysqli_fetch_assoc($userdata_result);
			mysqli_free_result($userdata_result);
			$entrydata["email"] = $userdata["user_email"];
			$entrydata["hide_email"] = $userdata["hide_email"];
			$entrydata["place"] = $userdata["user_place"];
			$entrydata["hp"] = $userdata["user_hp"];
			if ($entrydata["show_signature"]==1) $signature = $userdata["signature"];
		}

		// Posting heraussuchen, auf das geantwortet wurde:
		$result_a = mysqli_query($connid, "SELECT name FROM ". $db_settings['forum_table'] ." WHERE id = ". intval($parent_array[$id]["pid"]));
		$posting_a = mysqli_fetch_assoc($result_a);
		mysqli_free_result($result_a);
		if ($tiefe == 0 or $tiefe >= ($settings['max_thread_indent_mix_topic']/$settings['thread_indent_mix_topic'])) {
			$intendation = 0;
		} else {
			$intendation = intval($settings['thread_indent_mix_topic']);
		}

   ?><div class="mixdivl" id="p<?php echo intval($entrydata['id']); ?>" style="margin-left: <?php echo $intendation; ?>px;">
    <table class="mix-entry" border="0" cellpadding="5" cellspacing="1">
    <tr>
     <td class="autorcell" rowspan="2" valign="top"><?php
      // wenn eingelogged und Posting von einem angemeldeten User stammt, dann Link zu dessen Userdaten:
      if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $entrydata["user_id"] > 0)
       {
        $show_userdata_linktitle_x = str_replace("[name]", htmlsc($entrydata["name"]), $lang['show_userdata_linktitle']);
        ?><a id="<?php echo "p". intval($entrydata["id"]); ?>" href="user.php?id=<?php echo intval($entrydata["user_id"]); ?>"><b><?php echo htmlsc($entrydata["name"]); ?></b><?php if ($settings['show_registered'] ==1) { ?><img src="<?php echo $settings['themepath']; ?>/img/registered.gif" alt="<?php echo htmlsc($lang['registered_user_title']); ?>" width="10" height="10" /><?php } ?></a><br /><?php
       }
      // ansonsten nur den Namen anzeigen:
      else
       {
        ?>
        <a id="<?php echo "p". intval($entrydata["id"]); ?>"><b><?php echo htmlsc($entrydata["name"]); ?></b><?php if ($entrydata["user_id"] > 0 && $settings['show_registered'] ==1) { ?><img src="<?php echo $settings['themepath']; ?>/img/registered.gif" alt="<?php echo htmlsc($lang['registered_user_title']); ?>" width="10" height="10" /><?php } ?></a><br />
        <?php
       }
        if (empty($entrydata["hide_email"])) $entrydata["hide_email"] = 0;
        if (($entrydata["email"]!="" && $entrydata["hide_email"] != 1) or $entrydata["hp"]!="") { echo "<br />"; }
        if ($entrydata["hp"]!="") { if (mb_substr($entrydata["hp"], 0, 7) != "http://" && mb_substr($entrydata["hp"], 0, 8) != "https://" && mb_substr($entrydata["hp"], 0, 6) != "ftp://" && mb_substr($entrydata["hp"], 0, 9) != "gopher://" && mb_substr($entrydata["hp"], 0, 7) != "news://") $entrydata["hp"] = "http://".$entrydata["hp"]; echo '<a href="'. $entrydata["hp"] .'"><img src="'. $settings['themepath'] .'/img/homepage.gif" alt="'. htmlsc($lang['homepage_alt']) .'" width="13" height="13" /></a>'; }
        if (($entrydata["email"]!="" && $entrydata["hide_email"] != 1) && $entrydata["hp"]!="") { echo "&nbsp;"; }
        #if ($entrydata["email"]!="" && $entrydata["hide_email"] != 1) { echo '<a href="mailto:'. $entrydata["email"] .'"><img src="img/email.gif" alt="'. htmlsc($lang['email_alt']) .'" width="14" height="10" /></a>'; }
        if ($entrydata["email"]!="" && $entrydata["hide_email"] != 1 && isset($page) && isset($order) && isset($category)) { echo '<a href="contact.php?id='. intval($entrydata["id"]) .'&amp;page='. intval($page) .'&amp;category='. intval($category) .'&amp;order='. urlencode($order) .'&amp;view=mix" title="'. str_replace("[name]", htmlsc($entrydata["name"]), $lang['email_to_user_linktitle']) .'"><img src="'. $settings['themepath'] .'/img/email.gif" alt="'. htmlsc($lang['email_alt']) .'" width="13" height="10" /></a>'; }
        elseif ($entrydata["email"]!="" && $entrydata["hide_email"] != 1) { echo '<a href="contact.php?id='. intval($entrydata["id"]) .'&amp;view=mix" title="'. str_replace("[name]", htmlsc($entrydata["name"]), $lang['email_to_user_linktitle']) .'"><img src="'. $settings['themepath'] .'/img/email.gif" alt="'. htmlsc($lang['email_alt']) .'" width="13" height="10" /></a>'; }
        if (($entrydata["email"]!="" && $entrydata["hide_email"] != 1) or $entrydata["hp"]!="") { echo "<br />"; }
        echo "<br />";
        if ($entrydata["place"]!="") { echo htmlsc($entrydata["place"]); echo ", <br />"; }
        echo strftime($lang['time_format'],$entrydata["Uhrzeit"]); ?>
        <?php if ($entrydata["edited_diff"] > 0 && $entrydata["edited_diff"] > $entrydata["time"] && $settings['show_if_edited'] == 1) { $board_em = str_replace("[name]", htmlsc($entrydata["edited_by"]), $lang['board_edited_marking']); $board_em = str_replace("[time]", strftime($lang['time_format'],$entrydata["e_Uhrzeit"]), $board_em); ?><br /><span class="xsmall"><?php echo $board_em; ?></span><?php } ?>
        <?php if ($entrydata["pid"]!=0) { ?><span class="xsmall"><br /><br />@ <?php echo htmlsc($posting_a["name"]); ?></span><?php } ?>
        <?php
        if ($settings['user_edit'] == 1 && isset($_SESSION[$settings['session_prefix'].'user_id']) && $entrydata["user_id"] == $_SESSION[$settings['session_prefix']."user_id"] || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "admin" || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "mod") { ?><br /><br /><span class="small"><a href="posting.php?action=edit&amp;id=<?php echo intval($entrydata["id"]); ?>&amp;view=mix&amp;back=<?php echo intval($entrydata["tid"]); ?>&amp;page=<?php echo intval($page); ?>&amp;order=<?php echo urlencode($order); ?>&amp;descasc=<?php echo urlencode($descasc); ?>&amp;category=<?php echo intval($category); ?>"><img src="<?php echo $settings['themepath']; ?>/img/edit.gif" alt="" width="15" height="10" /><?php echo $lang['edit_linkname']; ?></a></span><?php } if ($settings['user_delete'] == 1 && isset($_SESSION[$settings['session_prefix'].'user_id']) && $entrydata["user_id"] == $_SESSION[$settings['session_prefix']."user_id"] || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "admin" || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "mod") { ?><br /><span class="small"><a href="posting.php?action=delete&amp;id=<?php echo intval($entrydata["id"]); ?>&amp;back=<?php echo intval($entrydata["tid"]); ?>&amp;view=mix&amp;page=<?php echo intval($page); ?>&amp;order=<?php echo urlencode($order); ?>&amp;descasc=<?php echo urlencode($descasc); ?>&amp;category=<?php echo urlencode($category); ?>"><img src="<?php echo $settings['themepath']; ?>/img/delete.gif" alt="" width="12" height="9" /><?php echo $lang['delete_linkname']; ?></a></span><?php }
        if (isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "admin" && $entrydata['pid'] == 0 || isset($_SESSION[$settings['session_prefix'].'user_id']) && $_SESSION[$settings['session_prefix']."user_type"] == "mod" && $entrydata['pid'] == 0) { ?><br /><span class="small"><a href="posting.php?lock=true&amp;view=mix&amp;id=<?php echo intval($entrydata["id"]); ?>&amp;page=<?php echo intval($page); ?>&amp;order=<?php echo urlencode($order); ?>&amp;descasc=<?php echo urlencode($descasc); ?>&amp;category=<?php echo intval($category); ?>"><img src="<?php echo $settings['themepath']; ?>/img/lock.gif" alt="" width="12" height="12" /><?php if ($entrydata['locked'] == 0) echo $lang['lock_linkname']; else echo $lang['unlock_linkname']; ?></a></span><?php }
        ?><div class="autorcellwidth">&nbsp;</div></td>
     <td class="titlecell" valign="top"><div class="left"><h2><?php echo htmlsc($entrydata["subject"]); if(isset($categories[$entrydata["category"]]) && $categories[$entrydata["category"]]!='' && $entrydata["pid"]==0) echo '&nbsp;<span class="category">('. $categories[$entrydata["category"]] .')</span>'; ?></h2></div><div class="right"><?php if ($entrydata['locked'] == 0) { ?><a class="textlink" href="posting.php?id=<?php echo intval($entrydata["id"]); if (isset($page) && isset($order) && isset($descasc) && isset($category)) { ?>&amp;page=<?php echo intval($page); ?>&amp;category=<?php echo intval($category); ?>&amp;order=<?php echo urlencode($order); ?>&amp;descasc=<?php echo urlencode($descasc); } ?>&amp;view=mix"><?php echo $lang['board_answer_linkname']; ?></a><?php } else { if ($entrydata['pid']==0) { ?><span class="xsmall"><img src="<?php echo $settings['themepath']; ?>/img/lock.gif" alt="" width="12" height="12" /><?php echo $lang['thread_locked']; ?></span><?php } else echo "&nbsp;"; } ?></div></td>
    </tr>
    <tr>
     <td class="postingcell" valign="top"><?php
     if ($entrydata["text"]=="") {
       echo $lang['no_text'];
     } else {
       $ftext=$entrydata["text"];
       $ftext = htmlsc($ftext);
       $ftext = nl2br($ftext);
       $ftext = zitat($ftext);
       if ($settings['autolink'] == 1) $ftext = make_link($ftext);
       if ($settings['bbcode'] == 1) $ftext = bbcode($ftext);
       if ($settings['smilies'] == 1) $ftext = smilies($ftext);
       echo '<p class="postingboard">'.$ftext.'</p>';
     }
     if (isset($signature) && $signature != "") {
       $signature = htmlsc($signature);
       $signature = nl2br($signature);
       if ($settings['autolink'] == 1) $signature = make_link($signature);
       if ($settings['bbcode'] == 1) $signature = bbcode($signature);
       if ($settings['smilies'] == 1) $signature = smilies($signature);
       echo '<p class="signature">'.$settings['signature_separator'].$signature.'</p>';
     }
   ?></td>
   </tr>
  </table>
  <?php
		if (isset($child_array[$id]) && is_array($child_array[$id])) {
			foreach ($child_array[$id] as $kind) {
				thread($kind, $aktuellerEintrag, $tiefe+1);
			}
		}
	?></div><?php
	}

	unset($entrydata);
	unset($parent_array);
	unset($child_array);

	if (empty($page)) $page = 0;
	if (empty($order)) $order="last_answer";
	if (empty($category)) $category="all";
	if (empty($descasc)) $descasc="DESC";

	if (isset($id)) {
		$id = (int) $id;
		if ($id > 0) {
			$result=mysqli_query($connid, "SELECT tid, pid, subject, category FROM ". $db_settings['forum_table'] ." WHERE id = ". intval($id));
			if (!$result) die($lang['db_error']);
			if (mysqli_num_rows($result) > 0) {
				// look if an entry with the id exists
				$entrydata = mysqli_fetch_assoc($result);
				// Look if id correct:
				if ($entrydata['pid'] != 0) header("location: ". basename($_SERVER['SCRIPT_NAME']) ."?id=". $entrydata['tid'] ."&page=". $page ."&category=". $category ."&order=". $order ."&descasc=". $descasc ."#p". $id);
				// category of this posting accessible by user?
				if (!(isset($_SESSION[$settings['session_prefix'].'user_type']) && $_SESSION[$settings['session_prefix'].'user_type'] == "admin")) {
					if (is_array($category_ids) && !in_array($entrydata['category'], $category_ids)) {
						header("location: mix.php");
						die();
					}
				}
				// count views:
				if (isset($settings['count_views']) && $settings['count_views'] == 1) mysqli_query($connid, "UPDATE ". $db_settings['forum_table'] ." SET time=time, last_answer=last_answer, edited=edited, views=views+1 WHERE tid=". intval($id));
			}
		}
	}

	if (!isset($entrydata)) {
		header("Location: mix.php");
		exit();
	}


	$thread = $entrydata["tid"];
	$result = mysqli_query($connid, "SELECT id, pid FROM ". $db_settings['forum_table'] ." WHERE tid = ". intval($thread) ." ORDER BY time ASC");
	if (!$result) die($lang['db_error']);

	// Ergebnisse einlesen
	while ($tmp = mysqli_fetch_assoc($result)) {
		// Ergebnis holen
		$parent_array[$tmp["id"]] = $tmp;          // Ergebnis im Array ablegen
		$child_array[$tmp["pid"]][] =  $tmp["id"]; // Vorwärtsbezüge konstruieren
	}

	mysqli_free_result($result); // Aufräumen

	$wo = $entrydata["subject"];
	$subnav_1 = '<a class="textlink" href="mix.php?page='. intval($page) .'&amp;category='. intval($category) .'&amp;order='. urlencode($order) .'&amp;descasc='. urlencode($descasc) .'">'.$lang['back_to_overview_linkname'].'</a>';
	$subnav_2 = "";
	if ($settings['thread_view'] == 1) $subnav_2 .= '<span class="small"><a href="forum_entry.php?id='. intval($entrydata["tid"]) .'&amp;page='. intval($page) .'&amp;order='. urlencode($order) .'&amp;descasc='. urlencode($descasc) .'&amp;category='. intval($category) .'"><img src="'. $settings['themepath'] .'/img/thread.gif" alt="" />'.$lang['thread_view_linkname'].'</a></span>';
	if ($settings['board_view'] == 1) $subnav_2 .= '&nbsp;&nbsp;<span class="small"><a href="board_entry.php?id='. intval($entrydata["tid"]) .'&amp;page='. intval($page) .'&amp;order='. urlencode($order) .'&amp;category='. urlencode($category) .'"><img src="'. $settings['themepath'] .'/img/board.gif" alt="" width="12" height="9" />'.$lang['board_view_linkname'].'</a></span>';

	parse_template();
	echo $header;
	thread($thread, $id);
	echo $footer;

} else {
	header("location: login.php?msg=noaccess");
	die('<a href="login.php?msg=noaccess">further...</a>');
}
?>
