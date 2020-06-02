<?php
/**
 * Registration verification page
 *
 * This page is accessed with the link sent in account confirmation
 * email.
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2010, Franck Villaume - Capgemini
 * Copyright 2012,2014, Franck Villaume - TrivialDev
 * Copyright 2016-2019, Henry Kwong, Tod Hing - SimTK Team
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

require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';

$confirm_hash = getStringFromRequest('confirm_hash');
if (ctype_xdigit(trim(html_clean_hash_string($confirm_hash))) === false) {
	// confirm_hash does not meet criteria.
	exit;
}

$context = array(
	"ssl"=>array(
		"verify_peer"=>false,
		"verify_peer_name"=>false,
	),
);

if (getStringFromRequest('submit')) {
	$loginname = getStringFromRequest('loginname');
	$passwd = getStringFromRequest('passwd');

	if (!$loginname) {
		exit_missing_param('',array(_('User Name')),'my');
	}
	$loginname = strtolower($loginname);

	$u = user_get_object_by_name($loginname);
	if (!$u && forge_get_config('require_unique_email')) {
		$u = user_get_object_by_email ($loginname);
	}
	$confirm_hash = html_clean_hash_string($confirm_hash);
	if (!$u || !is_object($u)) {
		$error_msg = _('Invalid Password Or User Name');
	} elseif ($u->isError()) {
		$error_msg = $u->getErrorMessage();
	} elseif ($u->getStatus()=='A'){
		$error_msg = _('Account already active.');
	} elseif ($confirm_hash != $u->getConfirmHash()) {
		$error_msg = _('Cannot confirm account identity - invalid confirmation hash (or login name)');
	} elseif (!session_login_valid($loginname, $passwd, 1)) {
		$warning_msg = _('Credentials you entered do not correspond to valid account.');
	} elseif (!$u->setStatus('A')) {
		$error_msg = _('Error while activating account')._(': ').$u->getErrorMessage();
	} else {
		// Success.

		// Add user to phpbb_users.
		$theUserName = $u->getUnixName();
		$urlUserUpdate = "http". (($_SERVER["HTTPS"] == "on") ? 's' : '') . "://" .
			$_SERVER["SERVER_NAME"] .
			"/plugins/phpBB/sync_user.php?" .
			"userName=" . $theUserName;

		// Invoke URL access to add the user to phpbb_users.
		//$resStr = file_get_contents($urlUserUpdate);
		// NOTE: context is required for file_get_contents().
		$resStr = file_get_contents($urlUserUpdate, false, stream_context_create($context));

		if (forge_get_config('user_notification_on_activation')) {
			$u->setAdminNotification();
		}
		session_redirect("/account/first.php");
	}
}

// Always log out first!
session_logout();

$HTML->header(array('title'=>_('Verify')));

echo '<p><br/><br/>To complete your registration and activate your account, log in below.</p>';

?>

<form action="<?php echo util_make_url('/account/verify.php?confirm_hash='.$confirm_hash); ?>" method="post">

<p><?php
if (forge_get_config('require_unique_email')) {
	echo _('Login name or email address')._(':');
} else {
	echo _('Login Name')._(':');
}
?>

<script>
$(document).ready(function () {
	$("#loginname").focus();
});
</script>

<br />
<label for="loginname">
	<input id="loginname" type="text" name="loginname"/>
</label>
</p>
<p><?php echo _('Password')._(':'); ?>
<br />
<label for="passwd">
	<input id="passwd" type="password" name="passwd"/>
</label>
</p>
<input type="hidden" name="confirm_hash" value="<?php print htmlentities($confirm_hash); ?>" />
<p><input type="submit" name="submit" value="<?php echo _('Login'); ?>" class="btn-cta" /></p>
</form>

<?php
$HTML->footer(array());
