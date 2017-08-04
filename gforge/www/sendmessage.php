<?php
/**
 * Send an Email Message Page
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright 2010-2013, Franck Villaume - TrivialDev
 * Copyright 2016, Henry Kwong, Tod Hing - SimTK Team
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

if (!session_loggedin()) {
	exit_permission_denied();
}

$toaddress = getStringFromRequest('toaddress');
$touser = getStringFromRequest('touser');

if (!$toaddress && !$touser) {
	exit_missing_param('', array(_('toaddress'), _('touser')), 'home');
}

if ($touser) {
	/*
		check to see if that user even exists
		Get their name and email if it does
	*/
	$result = db_query_params('SELECT email, user_name, realname ' .
		'FROM users WHERE user_id=$1',
		array($touser));

	if (!$result || db_numrows($result) < 1) {
		exit_error(_('That user does not exist.'), 'home');
	}
}

if ($toaddress && !preg_match('/'.forge_get_config('web_host').'/i',$toaddress)) {
	exit_error(sprintf(_('You can only send to addresses @<em>%s</em>.'),forge_get_config('web_host')),'home');
}

if (getStringFromRequest('send_mail')) {
	if (!form_key_is_valid(getStringFromRequest('form_key'))) {
		exit_form_double_submit('home');
	}

	$valide = 1;
	if (!session_loggedin()) {
		$params['valide'] =& $valide;
		$params['warning_msg'] =& $warning_msg;
		plugin_hook('captcha_check', $params);
	}

	$subject = getStringFromRequest('subject');
	$body = getStringFromRequest('body');
	$name = getStringFromRequest('name');
	$email = getStringFromRequest('email');

	if ($valide) {
		if (!$subject || !$body || !$name || !$email) {
			/*
				force them to enter all vars
			*/
			form_release_key(getStringFromRequest('form_key'));
			exit_missing_param('', array(_('Subject'), _('Body'), _('Name'), _('Email')), 'home');
		}

		// we remove the CRLF in all thoses vars. This is to make sure that there will be no CRLF Injection
		$name = util_remove_CRLF($name);
		// Really don't see what wrong could happen with CRLF in message body
		//$email = util_remove_CRLF($email);
		$subject = util_remove_CRLF($subject);

		if ($toaddress) {
			/*
				send it to the toaddress
			*/
			$to = preg_replace('/_maillink_/i', '@', $toaddress);
			$to = util_remove_CRLF($to);
			util_send_message($to, $subject, $body, $email, '', $name);
			$HTML->header(array('title' => forge_get_config('forge_name').' ' ._('Contact')));
			echo '<p>'._('Message has been sent').'.</p>';
			$HTML->footer(array());
			exit;
		} elseif ($touser) {
			/*
				figure out the user's email and send it there
			*/
			$to = db_result($result,0,'email');
			$to = util_remove_CRLF($to);
			util_send_message($to, $subject, $body, $email, '', $name);
			$HTML->header(array('title' => forge_get_config('forge_name').' '._('Contact')));
			echo '<p>'._('Message has been sent').'</p>';
			$HTML->footer(array());
			exit;
		}
	}
}

if ($toaddress) {
	$titleaddress = $toaddress;
} else {
	$titleaddress = db_result($result,0,'user_name');
}

if (session_loggedin()) {
	$user  =& session_get_user();
	$name  = $user->getRealName();
	$email = $user->getEmail();
	$is_logged = true;
} else {
	$is_logged = false;
	if (!isset($valide)) {
		$name  = '';
		$email = '';
	}
}

$realname = db_result($result,0,'realname');
if ($realname == "Local GForge Admin") {
	// Update display name.
	$realname = "SimTK WebMaster";
}

$subject = getStringFromRequest('subject');

// Get group_id if present.
$group_id = false;
if (isset($_GET["group_id"])) {
	$group_id = $_GET["group_id"];
}
// Display header.
$HTML->header(array('title' => forge_get_config('forge_name').' '._('Contact')));

if ($touser == 101 && $group_id !== false && trim($group_id) !== "") {
	// Display when sending to SimTK WebMaster when group_id is present.
	echo "<br/><span><b>For general questions about the SimTK website:</b> Send message to $realname using the form below. All fields are required.</span>";

	// Check permission first.
	if (forge_check_perm('project_read', $group_id)) {
		echo "<br/><br/>";

		// Has group_id. Look up group object.
		$groupObj = group_get_object($group_id);
		// Get project leads.
		$projectLeads = $groupObj->getLeads();

		// Check if forum is used in project.
		$useForum = false;
		$navigation = new Navigation();
		$menu = $navigation->getSimtkProjectMenu($group_id);
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
				'<a href="/plugins/phpBB/indexPhpbb.php?group_id=' . $group_id .
				'&pluginname=phpBB">discussion forum</a>. ';

			if (count($projectLeads) > 0) {
				// Has project lead(s).
				echo 'For questions not addressed in the forum, you can contact the ' .
					'<a href="/sendmessage.php?touser=' .
					$projectLeads[0]->getID() .
					'&group_id=' . $group_id . '">project administrators</a>.'; 
			}
		}
		else {
			// Does not use forum.
			if (count($projectLeads) > 0) {
				// Has project lead(s).
				echo 'For questions related to <b>this project ("' .
					$groupObj->getPublicName() .
					'")</b>:" Contact the ' .
					'<a href="/sendmessage.php?touser=' .
					$projectLeads[0]->getID() .
					'&group_id=' . $group_id . '">project administrators</a>.'; 
			}
		}
	}
}
else {
	echo "<br/><span>Send message to $realname using the form below. All fields are required.</span>";
}
?>

<br/><br/>

<form action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="post">

<input type="hidden" name="form_key" value="<?php echo form_generate_key(); ?>" />
<input type="hidden" name="toaddress" value="<?php echo $toaddress; ?>" />
<input type="hidden" name="touser" value="<?php echo $touser; ?>" />
<input type="hidden" name="name" value="<?php echo $name; ?>" />
<input type="hidden" name="email" value="<?php echo $email; ?>" />
<?php
if ($group_id !== false && trim($group_id) !== "") {
	// Pass along group_id if present.
	echo '<input type="hidden" name="group_id" value="' . $group_id . '" />';
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
<?php
if (!$is_logged) {
	plugin_hook('captcha_form');
}
?>
<br/>
<input type="submit" name="send_mail" value="Send Message" class="btn-cta" />
</form>
<?php
$HTML->footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
