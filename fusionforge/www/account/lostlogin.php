<?php
/**
 * Recover lost password page
 *
 * This page is accessed via confirmation URL in email
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2010 (c) Franck Villaume
 * Copyright 2016-2021, Henry Kwong, Tod Hing - SimTK Team
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
require_once $gfcommon.'include/account.php';

$passwd = getStringFromRequest('passwd');
$passwd2 = getStringFromRequest('passwd2');
$confirm_hash = htmlspecialchars(getStringFromRequest('confirm_hash'));

if (!$confirm_hash) {
	$confirm_hash = htmlspecialchars(getStringFromRequest('ch'));
}
if (!$confirm_hash) {
	exit_missing_param('',array(_('Confirm Hash')),'my');
}
// Remove noise from hash produced by buggy mail clients
$confirm_hash = html_clean_hash_string($confirm_hash);

$res_user = db_query_params ('SELECT * FROM users WHERE confirm_hash=$1',
			array($confirm_hash)) ;

if (db_numrows($res_user) > 1) {
	exit_error(_('This confirm hash exists more than once.'),'my');
}
if (db_numrows($res_user) < 1) {
	exit_error(_('Invalid confirmation hash.'),'my');
}
$u = user_get_object(db_result($res_user, 0, 'user_id'), $res_user);
if (!$u || !is_object($u)) {
	exit_error(_('Could Not Get User'),'home');
} elseif ($u->isError()) {
	exit_error($u->getErrorMessage(),'my');
}

$context = array(
	"ssl"=>array(
		"verify_peer"=>false,
		"verify_peer_name"=>false,
	),
);

if (getStringFromRequest("submit")) {

	if (preg_match("/.{10,}/", $passwd) == false) {
		$error_msg = 'You must supply valid password (at least 10 characters, a number, lower and upper-case letter).';
	}
	else if ($passwd != $passwd2) {
		$error_msg = 'New passwords do not match.';
	}
	else if (!$u->setPasswd($passwd)) {
		$error_msg = $u->getErrorMessage();
	}
	else {
		// Update user credentials in phpbb_users.
		// NOTE: Perform this operation because the user's
		// FusionForge credentials may have been updated.
		$urlUserUpdate = "https://". 
			getStringFromServer('HTTP_HOST') .
			"/plugins/phpBB/sync_user.php?" .
			"userName=" . $u->getUnixName();

		// Invoke URL access to add the user to phpbb_users.
		//$resStr = file_get_contents($urlUserUpdate);
		// NOTE: context is required for file_get_contents().
		$resStr = file_get_contents($urlUserUpdate, false, stream_context_create($context));

		// Invalidate confirm hash
		$u->setNewEmailAndHash('', 0);

		$HTML->header(array('title'=>"Password changed"));
		print '<p>';
                printf (_('Congratulations, you have reset your account password. You may <a href="%s">login</a> to the site now.'), util_make_url ("/account/login.php"));
                print '</p>';
		$HTML->footer(array());
		exit();
	}
}

$title = "Change Password";
$HTML->header(array('title'=>$title));
echo '<p>' ;
printf (_('Welcome, %s. You may now change your password.'),$u->getUnixName());
echo '</p>';
?>

<p><span class="required_note"><br/>Required fields outlined in blue.</span></p>

<form action="<?php echo util_make_url('/account/lostlogin.php'); ?>" method="post">
<p><?php echo _('New Password (at least 10 characters, a number, lower and upper-case letter)')._(':'); ?>
<br />
<label for="passwd">
	<input id="passwd" type="password" name="passwd" class="required" />
</label>
</p>
<p><?php echo _('New Password (repeat)')._(':'); ?>
<br />
<label for="passwd2">
	<input id="passwd2" type="password" name="passwd2" class="required" />
</label>
<input type="hidden" name="confirm_hash" value="<?php print $confirm_hash; ?>" /></p>
<p><input type="submit" name="submit" value="<?php echo _('Update'); ?>" class="btn-cta" /></p>
</form>

<?php

$HTML->footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
