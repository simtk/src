<?php
/**
 * Change user's password
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2010 (c) Franck Villaume - Capgemini
 * Copyright 2014, Franck Villaume - TrivialDev
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

global $HTML;

session_require_login () ;

$u = user_get_object(user_getid());
if (!$u || !is_object($u)) {
	exit_error(_('Could Not Get User'),'my');
} elseif ($u->isError()) {
	exit_error($u->getErrorMessage(),'my');
}

if (getStringFromRequest('submit')) {
	if (!form_key_is_valid(getStringFromRequest('form_key'))) {
		exit_form_double_submit('my');
	}

	$old_passwd = getStringFromRequest('old_passwd');
	$passwd = getStringFromRequest('passwd');
	$passwd2 = getStringFromRequest('passwd2');

/*
	if ($u->getMD5Passwd() != md5($old_passwd)) {
		form_release_key(getStringFromRequest('form_key'));
		$error_msg = 'Old password is incorrect';
	}
*/
	if ($u->getUnixPasswd() != crypt($old_passwd, $u->getUnixPasswd())) {
		form_release_key(getStringFromRequest('form_key'));
		$error_msg = 'Old password is incorrect';
	}
	else if (preg_match("/.{10,}/", $passwd) == false) {
		form_release_key(getStringFromRequest('form_key'));
		$error_msg = 'You must supply valid password (at least 10 characters, a number, lower and upper-case letter).';
	}
	else if ($passwd != $passwd2) {
		form_release_key(getStringFromRequest('form_key'));
		$error_msg = 'New passwords do not match.';
	}
	else if (!$u->setPasswd($passwd)) {
		form_release_key(getStringFromRequest('form_key'));
		$error_msg = $u->getErrorMessage();
	}
	else {
		$error_msg = 'Congratulations. You have changed your password.';
	}
}

site_user_header(array('title'=>_('Change Password')));

?>
	<p><span class="required_note"><br/>Required fields outlined in blue.</span></p>

	<form action="<?php echo util_make_url('/account/change_pw.php'); ?>" method="post">
	<input type="hidden" name="form_key" value="<?php echo form_generate_key(); ?>"/>
	<p><?php echo _('Old Password')._(':') ?>
	<br />
	<label for="old_passwd">
		<input id="old_passwd" type="password" name="old_passwd" class="required" />
	</label>
	</p>
	<p><?php echo _('New Password (at least 10 characters, a number, lower and upper-case letter)')._(':') ?>
	<br />
	<label for="passwd">
		<input id="passwd" type="password" name="passwd" class="required" />
	</label>
	</p>
	<p><?php echo _('New Password (repeat)')._(':') ?>
	<br />
	<label for="passwd2">
		<input id="passwd2" type="password" name="passwd2" class="required" />
	</label>
	</p>
	<p>
		<input type="submit" name="submit" value="<?php echo _('Update password') ?>" class="btn-cta" />
	</p>
	</form>

<?php

site_user_footer(array());
