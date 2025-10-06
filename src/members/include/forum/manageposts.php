<?php

/*
 * BlueThrust Clan Scripts
 * Copyright 2014
 *
 * Author: Bluethrust Web Development
 * E-mail: support@bluethrust.com
 * Website: http://www.bluethrust.com
 *
 * License: http://www.bluethrust.com/license.php
 *
 */

$dispError = '';
$countErrors = 0;

require_once("../classes/forumboard.php");
$boardObj = new ForumBoard($mysqli);

if (isset($_GET['tID']) && $boardObj->objTopic->select($_GET['tID'])) {
	$boardID = $boardObj->objTopic->get_info("forumboard_id");
	$boardObj->select($boardID);
} elseif (isset($_GET['pID']) && $boardObj->objPost->select($_GET['pID'])) {
	$topicID = $boardObj->objPost->get_info("forumtopic_id");
	$postMemberID = $boardObj->objPost->get_info("member_id");
	$boardObj->objTopic->select($topicID);
	$boardID = $boardObj->objTopic->get_info("forumboard_id");
	$boardObj->select($boardID);
}

if (!isset($member) || substr($_SERVER['PHP_SELF'], -11) != "console.php") {
	exit();
} else {
	$memberInfo = $member->get_info();
	$consoleObj->select($_GET['cID']);
	if (!$member->hasAccess($consoleObj) && !$boardObj->memberIsMod($memberInfo['member_id']) && $memberInfo['member_id'] != $postMemberID) {
		echo "
			<script type='text/javascript'>
				window.location = '".$MAIN_ROOT."members/console.php?cID=".$_GET['cID']."&noaccess=1'
			</script>
		";
		exit();
	}
}

$cID = $_GET['cID'];



// LOCK, STICKY, DELETE

$arrActions = ["sticky", "lock", "delete"];

if (
	isset($_GET['tID']) &&
	$boardObj->objTopic->select($_GET['tID']) &&
	in_array($_GET['action'], $arrActions) &&
	(
		$boardObj->memberIsMod($memberInfo['member_id']) ||
		$member->hasAccess($consoleObj)
	)
) {
	$topicInfo = $boardObj->objTopic->get_info();
	$boardObj->objPost->select($topicInfo['forumpost_id']);
	$topicName = $boardObj->objPost->get_info_filtered("title");

	switch ($_GET['action']) {
		case "sticky":
			$newStickyStatus = 0;
			if ($topicInfo['stickystatus'] == 0) {
				$newStickyStatus = 1;
			}

			$boardObj->objTopic->update(["stickystatus"], [$newStickyStatus]);
			$redirectURL = $MAIN_ROOT."forum/viewtopic.php?tID=".$topicInfo['forumtopic_id'];
			$member->logAction("Stickied forum topic: <a href='".$MAIN_ROOT."forum/viewtopic.php?tID=".$topicInfo['forumtopic_id']."'>".$topicName."</a>");
			break;
		case "lock":
			$newLockStatus = 0;
			if ($topicInfo['lockstatus'] == 0) {
				$newLockStatus = 1;
			}

			$boardObj->objTopic->update(["lockstatus"], [$newLockStatus]);
			$redirectURL = $MAIN_ROOT."forum/viewtopic.php?tID=".$topicInfo['forumtopic_id'];
			$member->logAction("Locked forum topic: <a href='".$MAIN_ROOT."forum/viewtopic.php?tID=".$topicInfo['forumtopic_id']."'>".$topicName."</a>");
			break;
		case "delete":
			$mysqli->query("DELETE FROM ".$dbprefix."forum_topicseen WHERE forumtopic_id = '".$topicInfo['forumtopic_id']."'");
			$mysqli->query("DELETE FROM ".$dbprefix."forum_post WHERE forumtopic_id = '".$topicInfo['forumtopic_id']."'");

			$mysqli->query("OPTIMIZE TABLE `".$dbprefix."forum_topicseen`");
			$mysqli->query("OPTIMIZE TABLE `".$dbprefix."forum_post`");

			$boardObj->objTopic->delete();

			$member->logAction("Deleted forum topic: ".$topicName);

			$redirectURL = $MAIN_ROOT."forum/viewboard.php?bID=".$topicInfo['forumboard_id'];

			break;
	}

	if ($redirectURL != "") {
		echo "
		<script type='text/javascript'>
			window.location = '".$redirectURL."';
		</script>
	";
	}
} elseif (isset($_GET['pID']) && $boardObj->objPost->select($_GET['pID']) && ($_GET['action'] ?? '') == "delete") {
	// DELETE POST

	$postInfo = $boardObj->objPost->get_info_filtered();
	$boardObj->objTopic->select($postInfo['forumtopic_id']);

	$topicInfo = $boardObj->objTopic->get_info_filtered();
	$dialogMessage = "";
	if ($postInfo['forumpost_id'] != $topicInfo['forumpost_id']) {
		// Not First Post
		$boardObj->objPost->delete();

		$arrPosts = $boardObj->objTopic->getAssociateIDs("ORDER BY dateposted DESC");

		$boardObj->objTopic->update(["lastpost_id"], [$arrPosts[0]]);

		$dialogMessage = "Successfully deleted forum post!";
	} else {
		// Topics First Post
		$arrPosts = $boardObj->objTopic->getAssociateIDs();
		if (count($arrPosts) > 1) {
			$dialogMessage = "You cannot delete this post with out deleting the entire topic!<br><br>Ask a mod to delete the topic.";
		} else {
			$boardObj->objTopic->delete();
			$dialogMessage = "Successfully deleted forum post!";
		}
	}

	echo "
	
		<div style='display: none' id='successBox'>
			<p align='center'>
				".$dialogMessage."
			</p>
		</div>
		
		<script type='text/javascript'>
			popupDialog('Delete Post', '".$MAIN_ROOT."forum/viewtopic.php?tID=".$postInfo['forumtopic_id']."', 'successBox');
		</script>
	
	";

	$boardObj->objPost->select($topicInfo['forumpost_id']);

	$member->logAction("Deleted post in topic: <a href='".$MAIN_ROOT."forum/viewtopic.php?tID=".$topicInfo['forumtopic_id']."'>".$boardObj->objPost->get_info_filtered("title")."</a>");
} elseif (isset($_GET['pID']) && $boardObj->objPost->select($_GET['pID']) && ($_GET['action'] ?? '') != "delete") {
	// EDIT POST

	$postInfo = $boardObj->objPost->get_info();
	$boardObj->objTopic->select($postInfo['forumtopic_id']);

	$topicInfo = $boardObj->objTopic->get_info_filtered();
	$boardObj->objPost->select($topicInfo['forumpost_id']);

	$topicPostInfo = $boardObj->objPost->get_info_filtered();

	$boardObj->objPost->select($postInfo['forumpost_id']);



	if ( ! empty($_POST['submit']) ) {
		$_POST['wysiwygHTML'] = str_replace("<?", "&lt;?", $_POST['wysiwygHTML']);
		$_POST['wysiwygHTML'] = str_replace("?>", "?&gt;", $_POST['wysiwygHTML']);
		$_POST['wysiwygHTML'] = str_replace("<script", "&lt;script", $_POST['wysiwygHTML']);
		$_POST['wysiwygHTML'] = str_replace("</script>", "&lt;/script&gt;", $_POST['wysiwygHTML']);

		$arrColumns = ["message", "lastedit_date", "lastedit_member_id"];


		// Check Topic Title
		if ($topicPostInfo['forumpost_id'] == $postInfo['forumpost_id'] && trim($_POST['topicname']) == "") {
			$countErrors++;
			$dispError .= "&nbsp;&nbsp;&nbsp;<b>&middot;</b> You may not enter a blank topic title.<br>";
		}

		// Check Post

		if (trim($_POST['wysiwygHTML']) == "") {
			$countErrors++;
			$dispError .= "&nbsp;&nbsp;&nbsp;<b>&middot;</b> You may not make a blank post.<br>";
		}

		if ($countErrors == 0) {
			$arrValues = [$_POST['wysiwygHTML'], time(), $memberInfo['member_id']];

			if ($topicPostInfo['forumpost_id'] == $postInfo['forumpost_id']) {
				$arrColumns[] = "title";
				$arrValues[] = $_POST['topicname'];
			}

			if ($boardObj->objPost->update($arrColumns, $arrValues)) {
				echo "
				
					<div style='display: none' id='successBox'>
						<p align='center'>
							Successfully edited forum post!
						</p>
					</div>
					
					<script type='text/javascript'>
						popupDialog('Manage Forum Post', '".$MAIN_ROOT."forum/viewtopic.php?tID=".$postInfo['forumtopic_id']."', 'successBox');
					</script>
				
				";
			}
		}

		if ($countErrors > 0) {
			$_POST = filterArray($_POST);
			$_POST['submit'] = false;
		}
	}

	if ( empty($_POST['submit']) ) {
		require_once("../classes/form.php");
		$formObj = new Form();

		// Detect theme and generate appropriate CSS (same as Form class)
		$isDarkTheme = false;
		$cssFile = BASE_DIRECTORY . "themes/".$THEME."/css.php";
		if (file_exists($cssFile)) {
			include($cssFile);
			if (isset($arrCSSInfo['font-color'])) {
				// Light colors (white, silver, etc.) suggest dark theme background
				$lightColors = ['white', '#fff', '#ffffff', 'silver', '#c0c0c0', '#ddd', '#dddddd'];
				$isDarkTheme = in_array(strtolower($arrCSSInfo['font-color']), $lightColors);
			}
		}
		$darkCSS = "
body, .mce-content-body {
	background-color: #2d2d2d;
	color: white;
	font-family: verdana, sans-serif;
	font-size: 11px;
	margin: 8px;
}
p, div, span, td, th { color: white; }
a { color: silver; }
a:hover { color: #647d99; }
blockquote {
	background-color: #1a1a1a;
	border-left: 3px solid #647d99;
	padding: 10px;
	margin: 8px 0;
}
";
		$contentCSS = $isDarkTheme ? false : MAIN_ROOT."btcs4.css.php";
		$contentStyle = $isDarkTheme ? $darkCSS : false;
		$skin = $isDarkTheme ? 'oxide-dark' : 'oxide';

		// Build TinyMCE config
		$tinymceConfig = array(
			'selector' => '#richTextarea',
			'license_key' => 'gpl',
			'skin' => $skin,
			'plugins' => 'emoticons',
			'toolbar' => 'bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist | link unlink emoticons | quotebbcode codebbcode',
			'menubar' => false,
			'statusbar' => false,
			'resize' => true
		);
		if ($contentCSS) {
			$tinymceConfig['content_css'] = $contentCSS;
		}
		if ($contentStyle) {
			$tinymceConfig['content_style'] = $contentStyle;
		}

		if ($topicPostInfo['forumpost_id'] == $postInfo['forumpost_id']) {
			$dispEditTitle = "<input type='text' id='postSubject' name='topicname' value='".$topicPostInfo['title']."' class='textBox' style='width: 250px'>";
		} else {
			$dispEditTitle = "<b>".$topicPostInfo['title']."<input type='hidden' id='postSubject' value='".$topicPostInfo['title']."'></b>";
		}

		echo "
		
		<form action='".$MAIN_ROOT."members/console.php?cID=".$cID."&pID=".$_GET['pID']."' method='post'>
		<div class='formDiv'>
		";

		if ($dispError != "") {
			echo "
			<div class='errorDiv'>
			<strong>Unable to edit post because the following errors occurred:</strong><br><br>
			$dispError
			</div>
			";
		}

		echo "
			<table class='formTable'>
				<tr>
					<td class='formLabel'>Topic Name:</td>
					<td class='main'>".$dispEditTitle."</td>
				</tr>
				<tr>
					<td class='formLabel' valign='top'>Message:</td>
					<td class='main'>
						<textarea id='richTextarea' name='wysiwygHTML' style='width: 90%' rows='20'>".$postInfo['message']."</textarea>
					</td>
				</tr>
				<tr>
					<td class='main' align='center' colspan='2'><br>
						<input type='submit' name='submit' value='Edit Post' class='submitButton' style='width: 125px'><br><br>
						<input type='button' id='btnPreview' value='Preview Post' class='submitButton' style='width: 125px'>
					</td>
				</tr>
			</table>
		
		</div>
		<div id='loadingSpiral' class='loadingSpiral'>
			<p align='center'>
				<img src='".$MAIN_ROOT."themes/".$THEME."/images/loading-spiral.gif'><br>Loading
			</p>
		</div>
		<div id='previewPost'></div>
		</form>
		<script type='text/javascript'>
			$(document).ready(function() {
				$('#consoleTopBackButton').attr('href', '".$MAIN_ROOT."forum/viewtopic.php?tID=".$postInfo['forumtopic_id']."');
				$('#consoleBottomBackButton').attr('href', '".$MAIN_ROOT."forum/viewtopic.php?tID=".$postInfo['forumtopic_id']."');

				// Initialize TinyMCE for the textarea (same as Form class)
				setTimeout(function() {
					if (typeof tinymce !== 'undefined' && !tinymce.get('richTextarea')) {
						var config = {
							selector: '#richTextarea',
							license_key: 'gpl',
							skin: '".addslashes($skin)."',
							plugins: 'emoticons',
							toolbar: 'bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist | link unlink emoticons | quotebbcode codebbcode',
							menubar: false,
							statusbar: false,
							resize: true
						};
						".($contentCSS ? "config.content_css = '".addslashes($contentCSS)."';" : "")."
						".($contentStyle ? "config.content_style = ".json_encode($contentStyle).";" : "")."
						tinymce.init(config);
					}
				}, 100);

				$('#btnPreview').click(function() {
					$('#loadingSpiral').show();
					$.post('".$MAIN_ROOT."members/include/forum/include/previewpost.php', { wysiwygHTML: $('#richTextarea').val(), previewSubject: $('[name=\"topicname\"]').val() }, function(data) {
						$('#previewPost').hide();
						$('#previewPost').html(data);
						$('#loadingSpiral').hide();
						$('#previewPost').fadeIn(250);

						$('html, body').animate({
							scrollTop:$('#previewPost').offset().top
						}, 1000);
					});
				});
			});
		</script>
		
		";
	}
}
