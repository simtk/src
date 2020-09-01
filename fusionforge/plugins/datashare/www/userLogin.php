<?php
/**
 * FusionForge login page
 *
 * This is main login page. It takes care of different account states
 * (by disallowing logging in with non-active account, with appropriate
 * notice).
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2011, Roland Mas
 * Copyright 2011, Franck Villaume - Capgemini
 * Copyright 2016-2020, SimTK Team
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

Header("Expires: Wed, 11 Nov 1998 11:11:11 GMT");
Header("Cache-Control: no-cache");
Header("Cache-Control: must-revalidate");

require_once '../../env.inc.php';
require_once $gfcommon .'include/pre.php';

global $warning_msg;

$typeConfirm = getIntFromRequest('typeConfirm');
$group_id = getIntFromRequest('groupid');
$study_id = getIntFromRequest('studyid');
$nameDownload = trim(getStringFromRequest('nameDownload'));
// Validate pathname for download.
if (!empty($nameDownload)) {
	$tmpName = preg_replace("/[-A-Z0-9+_\. ~\/]/i", "", $nameDownload);
	if (!empty($tmpName) || strstr($nameDownload, "..")) {
		$warning_msg = "Invalid filename for download.";
		$HTML->header(array('title'=>'Login'));
		$HTML->footer(array());
		exit;
	}
}

$login = getStringFromRequest('login');
$skip = getStringFromRequest('skip');
$form_loginname = getStringFromRequest('form_loginname');
$form_pw = getStringFromRequest('form_pw');

$plugin = plugin_get_object('authbuiltin');

if (forge_get_config('use_ssl') && !session_issecure()) {
	// Force use of SSL for login.
	header('Location: https://' . getStringFromServer('HTTP_HOST') . getStringFromServer('REQUEST_URI'));
}

// Check for valid login; if validatd, redirect.
if ($login == "Login") {
	// Log out user first.
	session_logout();

	$urlView = "view.php?plugin=datashare&" .
		"id=$group_id&studyid=$study_id&typeConfirm=$typeConfirm";
	if ($nameDownload != "") {
		// File download. Add file name.
		$urlView .= "&nameDownload=" . $nameDownload;
	}

	if (trim($form_loginname) == "" || trim($form_pw) == "") {
		$warning_msg = "Missing Password Or User Name";
	}
	else if (session_check_credentials_in_database(strtolower($form_loginname), $form_pw, false)) {
		if ($plugin->isSufficient()) {
			$plugin->startSession($form_loginname);
		}
		// User validated.
		header("Location: $urlView");
		exit;
	}
	else {
		$warning_msg = "Invalid Password Or User Name";
	}
}
else if ($skip == "Skip") {
	$urlView = "view.php?plugin=datashare&" .
		"id=$group_id&studyid=$study_id&typeConfirm=$typeConfirm";
	if ($nameDownload != "") {
		// File download. Add file name.
		$urlView .= "&nameDownload=" . $nameDownload;
	}

	header("Location: $urlView");
	exit;
}


$HTML->header(array('title'=>'Login'));


// Otherwise, display the login form again

?>

<script>
	$(document).ready(function () {
		$(".loginname").focus();
	});
</script>


<h2>Please login if you have a SimTK account.</h2>
<form action="/plugins/datashare/userLogin.php" method="post">
<?php
	if ($nameDownload != "") {
		echo '<input type="hidden" name="nameDownload" value="' . $nameDownload . '">';
	}
	echo '<input type="hidden" name="groupid" value="' . $group_id . '">';
	echo '<input type="hidden" name="studyid" value="' . $study_id . '">';
	echo '<input type="hidden" name="typeConfirm" value="' . $typeConfirm . '">';
?>
	<p>Login Name:<br/>
	<input type="text" class="loginname" name="form_loginname" value="" >
	</p>
	<p>Password:<br/>
	<input type="password" name="form_pw" ></p>
	<p><input type="submit" class="btn-cta" name="login" value="Login">
	<input type="submit" class="btn-cta" name="skip" value="Skip"></p>
</form>

<?php

$HTML->footer(array());

?>

