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
 * Copyright 2016-2022, SimTK Team
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
require_once $gfplugins .'datashare/include/Datashare.class.php';

global $warning_msg;

$typeConfirm = getIntFromRequest('typeConfirm');
$group_id = getIntFromRequest('groupid');
$study_id = getIntFromRequest('studyid');
$nameDownload = trim(getStringFromRequest('nameDownload'));
$pathSelected = trim(getStringFromRequest('pathSelected'));
$strFilesHash = trim(getStringFromRequest('filesHash'));

$isPrivate = true;
$study = new Datashare($group_id);
if ($study) {
	$study_result = $study->getStudy($study_id);
	if (isset($study_result[0])) {
		$isPrivate = $study_result[0]->is_private;
	}
	else {
		$isPrivate = true;
	}
}

// Validate download name.
if (!empty($nameDownload)) {
	$tmpName = preg_replace("/[-A-Z0-9+_\. ~\/]/i", "", $nameDownload);
	if (!empty($tmpName) || strstr($nameDownload, "..")) {
		$warning_msg = "Invalid filename for download.";
		$HTML->header(array('title'=>'Log in'));
		$HTML->footer(array());
		exit;
	}
}
// Validate selected path.
if (!empty($pathSelected)) {
	$tmpName = preg_replace("/[-A-Z0-9+_\. ~\/]/i", "", $pathSelected);
	if (!empty($tmpName) || strstr($pathSelected, "..")) {
		$warning_msg = "Invalid pathname selected.";
		$HTML->header(array('title'=>'Log in'));
		$HTML->footer(array());
		exit;
	}
}

if (session_loggedin()) {
	// User is logged in already.
	// Do not prompt user to log in again.
	$urlView = "view.php?plugin=datashare&" .
		"id=$group_id&studyid=$study_id&typeConfirm=$typeConfirm";

	// Use filesHash instead of nameDownload if fileHash is present.
	if ($strFilesHash != "") {
		// Files hash. Add file hash.
		// NOTE: Encode string before sending it as part of the URL.
		$urlView .= "&filesHash=" . urlencode($strFilesHash);
	}
	else if ($nameDownload != "") {
		// File download. Add file name.
		$urlView .= "&nameDownload=" . $nameDownload;
	}

	if ($pathSelected != "") {
		// Path selected. Add path.
		$urlView .= "&pathSelected=" . $pathSelected;
	}

	header("Location: $urlView");
	exit;
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
if ($login == "Log in") {
	// Log out user first.
	session_logout();

	$urlView = "view.php?plugin=datashare&" .
		"id=$group_id&studyid=$study_id&typeConfirm=$typeConfirm";

	// Use filesHash instead of nameDownload if fileHash is present.
	if ($strFilesHash != "") {
		// Files hash. Add file hash.
		// NOTE: Encode string before sending it as part of the URL.
		$urlView .= "&filesHash=" . urlencode($strFilesHash);
	}
	else if ($nameDownload != "") {
		// File download. Add file name.
		$urlView .= "&nameDownload=" . $nameDownload;
	}

	if ($pathSelected != "") {
		// Path selected. Add path.
		$urlView .= "&pathSelected=" . $pathSelected;
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
else if ($skip == "I do not have an account") {
	$urlView = "view.php?plugin=datashare&" .
		"id=$group_id&studyid=$study_id&typeConfirm=$typeConfirm";

	// Use filesHash instead of nameDownload if fileHash is present.
	if ($strFilesHash != "") {
		// Files hash. Add file hash.
		// NOTE: Encode string before sending it as part of the URL.
		$urlView .= "&filesHash=" . urlencode($strFilesHash);
	}
	else if ($nameDownload != "") {
		// File download. Add file name.
		$urlView .= "&nameDownload=" . $nameDownload;
	}

	if ($pathSelected != "") {
		// Path selected. Add path.
		$urlView .= "&pathSelected=" . $pathSelected;
	}

	header("Location: $urlView");
	exit;
}


$HTML->header(array('title'=>'Log in'));


// Otherwise, display the login form again

?>

<script>
	$(document).ready(function () {
		$(".loginname").focus();
	});
</script>


<?php
if ($isPrivate) {
?>
<h2>Please log in to access this study</h2>
<h5>No account? <a href="/account/register.php">Create your free account</a></h5>
<?php
}
else {
?>
<h2>Please log in if you have a SimTK account</h2>
<?php
}
?>

<form action="/plugins/datashare/userLogin.php" method="post">
<?php
	// Use filesHash instead of nameDownload if fileHash is present.
	// NOTE: Decode the string first, in case the string has been encoded already.
	// If string has been decoded already, it is fine to decode it again
	// because deocding a string does not change the string.
	// However, if the string has been encoded already, decode the string first
	// to avoid encoding an encoded string.
	if ($strFilesHash != "") {
		echo '<input type="hidden" name="filesHash" value="' .
			urlencode(urldecode($strFilesHash)) . '">';
	}
	else if ($nameDownload != "") {
		echo '<input type="hidden" name="nameDownload" value="' . $nameDownload . '">';
	}

	if ($pathSelected != "") {
		echo '<input type="hidden" name="pathSelected" value="' . $pathSelected . '">';
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
	<p><input type="submit" class="btn-cta" name="login" value="Log in">

<?php
if (!$isPrivate) {
?>
	<input type="submit" class="btn-cta" name="skip" value="I do not have an account"></p>
<?php
}
?>

</form>

<?php

$HTML->footer(array());

?>

