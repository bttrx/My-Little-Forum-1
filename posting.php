<?php
###############################################################################
# my little forum 1                                                            #
# Copyright (C) 2013 Heiko August                                             #
# http://www.auge8472.de/                                                     #
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

include_once("inc.php");
include_once("functions/include.prepare.php");

# generate captcha if captcha is on
# and a not logged user wants to post
if (empty($_SESSION[$settings['session_prefix'].'user_id'])
	and $settings['captcha_posting'] == 1)
	{
	require('captcha/captcha.php');
	$captcha = new captcha();
	}

# look for banned user:
if (isset($_SESSION[$settings['session_prefix'].'user_id']))
	{
	$lockQuery = "SELECT user_lock
	FROM ". $db_settings['userdata_table'] ."
	WHERE user_id = '". intval($_SESSION[$settings['session_prefix'].'user_id']) ."'
	LIMIT 1";
	$lockResult = mysql_query($lockQuery, $connid);
	if ($lockResult === false) die($lang['db_error']);
	$lockResultArray = mysql_fetch_assoc($lockResult);
	mysql_free_result($lockResult);
	if ($lockResultArray['user_lock'] > 0)
		{
		header("location: ". $settings['forum_address'] ."user.php");
		die('<a href="user.php">further...</a>');
		}
	} # End: if (isset($_SESSION[$settings['session_prefix'].'user_id']))

/**
 * Start: block for special cases
 */
# lock or unlock a thread (forbid or allow answers to a thread)
if (isset($_GET['lock'])
	and isset($_SESSION[$settings['session_prefix'].'user_id'])
	and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
		or $_SESSION[$settings['session_prefix']."user_type"] == "mod"))
	{
	$lockQuery = "UPDATE ". $db_settings['forum_table'] ." SET
	time = time,
	last_answer = last_answer,
	edited = edited,
	locked = IF(locked = 0, 1, 0)
	WHERE tid = ". intval($_GET['id']);
	@mysql_query($lockQuery, $connid);
	if (!empty($_SESSION[$settings['session_prefix'].'user_view'])
		and in_array($_SESSION[$settings['session_prefix'].'user_view'], $possViews))
		{
		if ($_SESSION[$settings['session_prefix'].'user_view'] == 'thread')
			{
			$header_href = 'forum_entry.php?id='. intval($_GET['id']);
			}
		else
			{
			$header_href = $_SESSION[$settings['session_prefix'].'user_view'] .'_entry.php?id='. intval($_GET['id']);
			}
		}
	else
		{
		$header_href = ($setting['standard'] == 'thread') ? 'forum.php' : $setting['standard'] .'.php';
		}
	header('location: '.$settings['forum_address'].$header_href);
	} # if (isset($_GET['lock']) ...)

# pin or unpin threads to the top of the views
if (isset($_GET['fix'])
	and isset($_SESSION[$settings['session_prefix'].'user_id'])
	and ($_SESSION[$settings['session_prefix']."user_type"] == "admin"
		or $_SESSION[$settings['session_prefix']."user_type"] == "mod"))
	{
	$fixQuery = "UPDATE ". $db_settings['forum_table'] ." SET
	time = time,
	last_answer = last_answer,
	edited = edited,
	fixed = IF(fixed = 0, 1, 0)
	WHERE tid = ". intval($_GET['id']);
	@mysql_query($fixQuery, $connid);
	if (!empty($_SESSION[$settings['session_prefix'].'user_view'])
		and in_array($_SESSION[$settings['session_prefix'].'user_view'], $possViews))
		{
		if ($_SESSION[$settings['session_prefix'].'user_view'] == 'thread')
			{
			$header_href = 'forum_entry.php?id='. intval($_GET['id']);
			}
		else
			{
			$header_href = $_SESSION[$settings['session_prefix'].'user_view'] .'_entry.php?id='. intval($_GET['id']);
			}
		}
	else
		{
		$header_href = ($setting['standard'] == 'thread') ? 'forum.php' : $setting['standard'] .'.php';
		}
	header('location: '.$settings['forum_address'].$header_href);
	} # if (isset($_GET['fix']) ...)

# subscribe or unsubscribe threads
if (isset($_GET['subscribe'])
	and isset($_SESSION[$settings['session_prefix'].'user_id'])
	and isset($_GET['back']))
	{
	if ($_GET['subscribe'] == 'true')
		{
		$querySubscribe = "INSERT INTO ". $db_settings['usersubscripts_table'] ." SET
		user_id = ". intval($_SESSION[$settings['session_prefix'].'user_id']) .",
		tid = ". intval($_GET['back']) ."
		ON DUPLICATE KEY UPDATE
		user_id = user_id,
		tid = tid";
		$queryUnsubscribePost = "UPDATE ". $db_settings['forum_table'] ." SET
		email_notify = 0
		WHERE user_id = ". intval($_SESSION[$settings['session_prefix'].'user_id']) ."
		AND tid = ". intval($_GET['id']);
		}
	else if ($_GET['subscribe'] == 'false')
		{
		$subscriptThread = processSearchThreadSubscriptions($_GET['back'], $_SESSION[$settings['session_prefix'].'user_id']);
		if (($subscriptThread !== false
		and is_array($subscriptThread))
		and ($subscriptThread['user_id'] == $_SESSION[$settings['session_prefix'].'user_id']
		and $subscriptThread['tid'] == $_GET['back']))
			{
			$querySubscribe = "DELETE FROM ". $db_settings['usersubscripts_table'] ."
			WHERE tid = ". intval($_GET['back']) ."
			AND user_id = ". intval($_SESSION[$settings['session_prefix'].'user_id']) ."
			LIMIT 1";
			}
		}
	if (!empty($querySubscribe)) @mysql_query($querySubscribe, $connid);
	if (!empty($queryUnsubscribePost)) @mysql_query($queryUnsubscribePost, $connid);
	if (!empty($_SESSION[$settings['session_prefix'].'user_view'])
	and in_array($_SESSION[$settings['session_prefix'].'user_view'], $possViews))
		{
		if ($_SESSION[$settings['session_prefix'].'user_view'] == 'thread')
			{
			$header_href = 'forum_entry.php?id='. intval($_GET['id']);
			}
		else
			{
			$header_href = $_SESSION[$settings['session_prefix'].'user_view'] .'_entry.php?id='.  intval($_GET['back']);
			}
		}
	else
		{
		$header_href = ($setting['standard'] == 'thread') ? 'forum.php' : $setting['standard'] .'.php';
		}
	header('location: '.$settings['forum_address'].$header_href);
	} # if (isset($_GET['subscribe'] ...)
/**
 * End: block for special cases
 */

/**
 * processing of normal script requests
 */
if (($settings['access_for_users_only'] == 1
	and isset($_SESSION[$settings['session_prefix'].'user_name']))
	or $settings['access_for_users_only'] != 1)
	{
	if (($settings['entries_by_users_only'] == 1
		and isset($_SESSION[$settings['session_prefix'].'user_name']))
		or $settings['entries_by_users_only'] != 1)
		{
		if (is_array($categories)
			and !in_array($_SESSION[$settings['session_prefix'].'mlf_category'], $categories))
			{
			header('location: '.$settings['forum_address'].'index.php');
			die('<a href="index.php">further...</a>');
			}

		# delete arrays if present
		if (isset($errors)) unset($errors);
		if (isset($Thread)) unset($Thread);
		# safety: forbid editing and deletion of postings
		$authorisation['edit'] = 0;
		$authorisation['delete'] = 0;
		# $action can only be submitted via POST, is set
		# to standard value or it will be changed during
		# the script run (i.e. by checking GET parameters)
		$action = (!empty($_POST['action']) and in_array($_POST['action'], $allowSubmittedActions)) ? $_POST['action'] : "new";
		$action = (!empty($_GET['edit']) and $_GET['edit'] == "true") ? "edit" : $action;
		$action = (!empty($_GET['delete']) and $_GET['delete'] == "true") ? "delete" : $action;
		$action = (!empty($_GET['delete_ok']) and $_GET['delete_ok'] == "true") ? "delete ok" : $action;
		# if a posting should be edited or deleted, check for authorisation
		# check call via GET or POST parameter
		if ((isset($_GET['id']) and is_numeric($_GET['id']))
			or (isset($_POST['id']) and is_numeric($_POST['id']))
			and ($action == "edit"
				or $action == "delete"
				or $action == "delete ok"))
			{
			$authorisation =  processCheckAuthorisation(isset($_GET['id']) ? $_GET['id'] : $_POST['id'], $authorisation, $connid);
			} # End: check for authorisation if called via GET or POST parameter
		# if form was submitted (old file: line 618)
		if (isset($_POST['form']))
			{
			$_POST['id'] = empty($_POST['id']) ? 0 : intval($_POST['id']);
			switch ($action)
				{
				case "new":
					# is it a registered user?
					if (isset($_SESSION[$settings['session_prefix'].'user_id']))
						{
						$user_id = $_SESSION[$settings['session_prefix'].'user_id'];
						$name = $_SESSION[$settings['session_prefix'].'user_name'];
						}
					# if the posting is an answer, search the thread-ID:
					if ($_POST['id'] > 0)
						{
						$threadIdQuery = "SELECT
						tid,
						locked
						FROM ". $db_settings['forum_table'] ."
						WHERE id = ". intval($_POST['id']);
						$threadIdResult = mysql_query($threadIdQuery, $connid);
						if (!$threadIdResult) die($lang['db_error']);

						if (mysql_num_rows($threadIdResult) != 1)
							{
							die($lang['db_error']);
							}
						else
							{
							$field = mysql_fetch_assoc($threadIdResult);
							$Thread = $field['tid'];
							if ($field['locked'] > 0)
								{
								unset($action);
								$show = "no authorization";
								$reason = $lang['thread_locked_error'];
								}
							}
						mysql_free_result($threadIdResult);
						}
					else if ($_POST['id'] == 0)
						{
						$Thread = 0;
						}
				break;
				case "edit";
					# fetch missing data from database:
					$postingQuery = "SELECT
					name,
					locked,
					UNIX_TIMESTAMP(time) AS time,
					UNIX_TIMESTAMP(NOW() - INTERVAL ". $settings['edit_period'] ." MINUTE) AS edit_diff
					FROM ". $db_settings['forum_table'] ."
					WHERE id = ". intval($_POST['id']);
					$edit_result = mysql_query($postingQuery, $connid);
					if (!$edit_result) die($lang['db_error']);
					$field = mysql_fetch_assoc($edit_result);
					mysql_free_result($edit_result);
					if (empty($name))
						{
						$name = $field["name"];
						}
				break;
				} # End: switch ($action)
			} # End: if (isset($_POST['form']))
		} # End: if (($settings['entries_by_users_only'] == 1 ...)
	else
		{
		header("Location: ". $settings['forum_address'] ."login.php?msg=noentry");
		die('<a href="login.php?msg=noentry">further...</a>');
		}
	} # End: if (($settings['access_for_users_only'] == 1 ...)
else
	{
	header("Location: ". $settings['forum_address'] ."login.php?msg=noaccess");
	die('<a href="login.php?msg=noaccess">further...</a>');
	}
