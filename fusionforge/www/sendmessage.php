<?php
/**
 * Send an Email Message Page
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright 2010-2013, Franck Villaume - TrivialDev
 * Copyright 2016-2021, Henry Kwong, Tod Hing - SimTK Team
 * http://fusionforge.org/
 *
 * This file is part of FusionForge. FusionForge is free software;
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or (at your option)
 * any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with FusionForge; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

require_once './env.inc.php';
require_once $gfcommon.'include/pre.php';

// Log message sent.
function logSendMessage($recipientEmail, $senderId) {
	// 5 minutes.
	$INTERVAL_FLOOD = 300;

	// Check recent message sent.
	$res = db_query_params("SELECT * FROM user_sent_message " .
		"WHERE user_id=$1 " .
		"AND extract(epoch from (current_timestamp - last_message_time)) < $2",
		array($senderId, $INTERVAL_FLOOD));
	if (db_numrows($res) > 0) {
		return "Message sent recently. Please wait before sending another message.";
	}
	else {
		// OK, no recent message sent.
		// Update table.
		$res = db_query_params("INSERT INTO user_sent_message " .
			"(user_id, last_message_time) VALUES " .
			"($1, CURRENT_TIMESTAMP) " .
			"ON CONFLICT (user_id) DO UPDATE SET " .
			"last_message_time=CURRENT_TIMESTAMP",
			array($senderId));
		return "";
	}
}

if (!session_loggedin()) {
	exit_permission_denied();
}
$thisUser =& session_get_user();
$thisUserName = $thisUser->getRealName();
$thisUserEmail = $thisUser->getEmail();

$recipient = htmlspecialchars(getStringFromRequest('recipient'));
if (!$recipient) {
	exit_error('Missing parameter', 'home');
}
$theRecipient = user_get_object_by_name($recipient);
if (!$theRecipient || !is_object($theRecipient)){
	exit_error('User does not exist.', 'home');
}
$recipientName = $theRecipient->getRealName();
$recipientEmail = trim($theRecipient->getEmail());

$subject = htmlspecialchars(getStringFromRequest('subject'));
$subject = trim(util_remove_CRLF($subject));
$body = trim(htmlspecialchars(getStringFromRequest('body')));

$groupObj = false;
$groupId = false;
$unix_group_name = trim(htmlspecialchars(getStringFromRequest("groupname")));
if ($unix_group_name) {
	$groupObj = group_get_object_by_name($unix_group_name);
	if (!$groupObj || !is_object($groupObj)){
		exit_error('Group does not exist.', 'home');
	}
	$groupId = $groupObj->getID();
}

if (getStringFromRequest('send_mail')) {
	if (!form_key_is_valid(getStringFromRequest('form_key'))) {
		exit_form_double_submit('home');
	}

	if (!$subject || !$body || !$recipientEmail || !$thisUserName || !$thisUserEmail) {
		form_release_key(getStringFromRequest('form_key'));
		exit_error('Missing parameters', 'home');
	}

	// Check and log message sent.
	$resStatus = logSendMessage($recipientEmail, $thisUser->getId());
	if ($resStatus != "") {
		exit_error($resStatus, 'home');
	}

	// Send message.
	util_send_message($recipientEmail, $subject, $body, $thisUserEmail, '', $thisUserName);

	$HTML->header(array('title' => forge_get_config('forge_name') . ' Contact'));
	echo '<p>Message has been sent</p>';
	$HTML->footer(array());

	exit;
}

// Display header.
$HTML->header(array('title' => forge_get_config('forge_name') . ' Contact'));

if ($recipient == "admin" && $groupId !== false) {
	// Display when sending to SimTK WebMaster and group id is present.
	echo "<h2>Feedback on SimTK</h2>";
	echo "<span><b>For general questions about the SimTK website:</b> Send message to $recipientName using the form below. All fields are required.</span>";

	// Check permission first.
	if ($groupObj !== false && forge_check_perm('project_read', $groupId)) {

		echo "<h2>Feedback on " . $groupObj->getPublicName() . "</h2>";

		// Get project leads.
		$projectLeads = $groupObj->getAdmins();

		// Check if forum is used in project.
		$useForum = false;
		$navigation = new Navigation();
		$menu = $navigation->getSimtkProjectMenu($groupId);
		$menu_max = count($menu['titles'], 0);
		for ($i=0; $i < $menu_max; $i++) {
			$menuTitle = $menu['titles'][$i];
			if ($menuTitle == "Forums") {
				// Project uses forum.
				$useForum = true;
				break;
			}
		}

		if ($useForum === true) {
			// Uses forum.
			echo 'For questions related to <b>this project ("' . 
				$groupObj->getPublicName() . 
				'")</b>: We recommend posting to their ' .
				'<a href="/plugins/phpBB/indexPhpbb.php?groupname=' . $unix_group_name .
				'&pluginname=phpBB">discussion forum</a>. ';

			if (count($projectLeads) > 0) {
				// Has project lead(s).
				echo 'For questions not addressed in the forum, you can contact the ' .
					'<a href="/sendmessage.php?recipient=' .
					$projectLeads[0]->getUnixName() .
					'&groupname=' . $unix_group_name . '">project administrators</a>.'; 
			}
		}
		else {
			// Does not use forum.
			if (count($projectLeads) > 0) {
				// Has project lead(s).
				echo 'For questions related to <b>this project ("' .
					$groupObj->getPublicName() .
					'")</b>:" Contact the ' .
					'<a href="/sendmessage.php?recipient=' .
					$projectLeads[0]->getUnixName() .
					'&groupname=' . $unix_group_name . '">project administrators</a>.'; 
			}
		}
	}
}
else {
	echo "<br/><span>Send message to $recipientName using the form below. All fields are required.</span>";
}
?>

<br/><br/>

<form action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="post">

<input type="hidden" name="form_key" value="<?php echo form_generate_key(); ?>" />
<input type="hidden" name="recipient" value="<?php echo $recipient; ?>" />
<?php
if ($unix_group_name) {
	// Pass along group id if present.
	echo '<input type="hidden" name="groupname" value="' . $unix_group_name . '" />';
}
?>

<p>
<strong>Subject:</strong><br/>
<input type="text" class="required" required="required" name="subject" size="60" maxlength="255" value="<?php echo $subject; ?>" />
</p>
<p>
<strong>Message:</strong><br/>
<textarea name="body" class="required" required="required" rows="15" cols="60" >
<?php
if (isset($body)) {
	echo $body;
}
?>
</textarea>
</p>
<br/>
<input type="submit" name="send_mail" value="Send Message" class="btn-cta" />
</form>
<?php
$HTML->footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
