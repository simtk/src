<?php

/**
 * User account main page - show settings with means to change them
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2010-2011, Franck Villaume - Capgemini
 * Copyright 2011, Alain Peyrat - Alcatel-Lucent
 * Copyright 2012-2014, Franck Villaume - TrivialDev
 * Copyright 2013, French Ministry of National Education
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
require_once $gfcommon.'include/timezones.php';
require_once $gfcommon.'include/account.php';
require_once '../securimage/securimage.php';

// Allow alternate content-type rendering by hook
$default_content_type = 'text/html';

$script = 'sign_in';
$content_type = util_negociate_alternate_content_types($script, $default_content_type); 

if ($content_type != $default_content_type) {
        $hook_params = array();
        $hook_params['accept'] = $content_type;
        $hook_params['return'] = '';
        $hook_params['content_type'] = '';
        plugin_hook_by_reference('content_negociated_trove_list', $hook_params);
        if($hook_params['content_type'] != ''){
                header('Content-type: '. $hook_params['content_type']);
                echo $hook_params['content'];
        }
        else {
                header('HTTP/1.1 406 Not Acceptable',true,406);
        }
        exit(0);
}

if (forge_get_config ('user_registration_restricted')) {
	session_require_global_perm ('forge_admin');
}

if (forge_get_config('use_ssl') && !session_issecure()) {
	//force use of SSL for login
	header('Location: https://'.getStringFromServer('HTTP_HOST').getStringFromServer('REQUEST_URI'));
}

// Handle "suspended" users (passed in as parameter in HTTP request).
if (isset($_REQUEST["suspended"])) {
	// User is in suspended status..
	$suspended = $_REQUEST["suspended"];
	$error_msg = "To re-activate this suspended account, update inappropriate content and submit for review.";

	// Retrieve the suspended user's id.
	$sql = "SELECT time, user_id FROM user_session WHERE session_hash=$1 AND ip_addr=$2";
        $res = db_query_params($sql,
		array(
			session_get_session_cookie_hash($_COOKIE['session_tmp']),
			getStringFromServer('REMOTE_ADDR')
		)
	);
        if (!$res || db_numrows($res) != 1 || $suspended != $_COOKIE['session_tmp']) {
                $suspended = false;
		session_require_login();
        }
        else {
                $sus_uid = db_result($res, 0, 'user_id');
        }
}
else {
	// Normal user.
	session_require_login();
}

// get global users vars
if (isset($suspended) && $suspended) {
	// Get the suspended user object.
	$u =& user_get_object($sus_uid);
}
else {
	$u = session_get_user();
}

if (!$u || !is_object($u)) {
	exit_error(_('Could Not Get User'));
} elseif ($u->isError()) {
	exit_error($u->getErrorMessage(),'my');
}

$action = getStringFromRequest('action');
switch ($action) {
	case "deletesshkey":
	case "addsshkey": {
		include ($gfcommon."account/actions/$action.php");
		break;
	}
}

/*
if (isset($theme_id) && (!$theme_id || !is_numeric($theme_id))) {
        $theme_id = getThemeIdFromName(forge_get_config('default_theme'));
}
*/

$context = array(
	"ssl"=>array(
		"verify_peer"=>false,
		"verify_peer_name"=>false,
	),
);

// For CAPTCHA error display.
$errVerify = "";
if (getStringFromRequest('submit')) {

	$valide = 1;

	// Get override values from the hidden components.
	// Convert from string to boolean.
	$strOverride_lab_website = getStringFromRequest('override_lab_website');
	if ($strOverride_lab_website == "1") {
		$override_lab_website = 1;
	}
	else {
		$override_lab_website = 0;
	}
	$strOverride_university_website = getStringFromRequest('override_university_website');
	if ($strOverride_university_website == "1") {
		$override_university_website = 1;
	}
	else {
		$override_university_website = 0;
	}
	$strOverride_personal_website = getStringFromRequest('override_personal_website');
	if ($strOverride_personal_website == "1") {
		$override_personal_website = 1;
	}
	else {
		$override_personal_website = 0;
	}

	// Check CAPTCHA.
	$secOptions = array('captcha_type'=>Securimage::SI_CAPTCHA_MATHEMATIC);
	$securimage = new Securimage($secOptions);
	if ($securimage->check($_POST['captcha_code']) == false) {

		// CAPTCHA not solved correctly failed.
		$errVerify = "USER VERIFICATION FAILED:<BR/>You must solve the mathematical problem correctly";

		// Set up cookie for website overrides.
		// Keep cookie for 5 minutes.
		if ($override_lab_website) {
			setcookie("override_lab_website", "1", time() + 300);
		}
		if ($override_university_website) {
			setcookie("override_university_website", "1", time() + 300);
		}
		if ($override_personal_website) {
			setcookie("override_personal_website", "1", time() + 300);
		}

		// Do not proceed with registration.
		$valide = 0;
	}

	if (!form_key_is_valid(getStringFromRequest('form_key'))) {
		exit_form_double_submit('my');
	}

	$firstname = getStringFromRequest('firstname');
	$lastname = getStringFromRequest('lastname');
	$ccode = getStringFromRequest('ccode');
	$interest_simtk = getStringFromRequest('interest_simtk');

	$university_name = getStringFromRequest('university_name');
	$university_website = getStringFromRequest('university_website');
	$lab_name = getStringFromRequest('lab_name');
	$lab_website = getStringFromRequest('lab_website');
	$personal_website = getStringFromRequest('personal_website');

	$userpicfilename = getStringFromRequest('userpicfilename');
	$userpicfiletype = getStringFromRequest('userpicfiletype');

	if ($valide) {
		$mail_site = 0;
		$mail_va = 0;
		// English.
		$language_id = 1;
		$timezone = "US/Pacific";
		// Simtk.
		$theme_id = 24;
		$use_ratings = true;

		// Update account information in database.
		$updated = $u->update($firstname, $lastname, $language_id, $timezone, $mail_site, 
			$mail_va, $use_ratings, '', 0, $theme_id,
			"", "", "", "", "",
			$ccode, true, "",
			$interest_simtk, $lab_name, $lab_website,
			$university_name, $university_website, $personal_website,
//			$userpic_tmpfile, $userpic_type,
			$userpicfilename, $userpicfilename,
			$override_lab_website,
			$override_university_website,
			$override_personal_website);

		if ($updated) {
			// Successful update.

			// Synchronize data with phpBB forum.
			$urlUserUpdate = "https://".
				getStringFromServer('HTTP_HOST') .
				"/plugins/phpBB/sync_user.php?" .
				"userName=" . $u->getUnixName();

			// Invoke URL access to add the user to phpbb_users.
			//$resStr = file_get_contents($urlUserUpdate);
			// NOTE: context is required for file_get_contents().
			$resStr = file_get_contents($urlUserUpdate, false, stream_context_create($context));

			// Clear cookie(s) for website overrides.
			setcookie("override_lab_website", "1", time() - 300);
			setcookie("override_university_website", "1", time() - 300);
			setcookie("override_personal_website", "1", time() - 300);

//			$errVerify = "Account updated.";
			$feedback = "Account updated.";


			if (isset($suspended) && $suspended) {
				// Update status to "R" (i.e. user has edited profile.)
				$u->setStatus("R");

				// Email the webmaster if the account had been suspended 
				// and has just been edited by the user.
				$subject = "Suspended user profile '" . $u->getUnixName() . "' has been edited!";
				$message = "User '" . 
					$u->getUnixName() . 
					"' is suspended, but has just edited some personal details. " .
					"Please check that the new profile is satisfactory at:\n\n" .
					"https://simtk.org/users/" . 
					$u->getUnixName() . "\n\n" .
					"You can re-activate this user at:\n\n" .
					"https://simtk.org/admin/useredit.php?user_id=$sus_uid";
				util_send_message("webmaster@simtk.org", $subject, $message);
			}
		}
		else {
			// Failed update.

			form_release_key(getStringFromRequest('form_key'));

			// Set up cookie for website overrides.
			// Keep cookie for 5 minutes.
			if ($override_lab_website) {
				setcookie("override_lab_website", "1", time() + 300);
			}
			if ($override_university_website) {
				setcookie("override_university_website", "1", time() + 300);
			}
			if ($override_personal_website) {
				setcookie("override_personal_website", "1", time() + 300);
			}

			$error_msg = $u->getErrorMessage();

			// Extract name(s) of input components which should be flagged.
			$error_msg = retrieveErrorMessages($error_msg, $arrErrors);

			if (isset($register_error)) {
				$error_msg .= ' '. $register_error;
			}
		}
	}
}

if ($errVerify != "") {
	// Include CAPTCHA verification message.
	if (isset($error_msg) && $error_msg != "") {
		$error_msg .= '<br/>'. $errVerify;
	}
	else {
		$error_msg = $errVerify;
	}
}

// Get country code.
if (!isset($ccode) || empty($ccode) || !preg_match('/^[a-zA-Z]{2}$/', $ccode)) {
	// Use country code from user setting.
	$ccode = $u->getCountryCode();
}
else {
	// Has valid country code passed in already from previous page.
}

// Retrieve the error message and names of components to flag.
function retrieveErrorMessages($error_msg, &$arrErrors) {

	// Error messages are separated using "^" as delimiter.
	$arrErrors = explode("^", $error_msg);

	// The error message is the last token.
	// Note: "^" can be not present in the string; i.e. "".
	$error_msg = $arrErrors[count($arrErrors) - 1];

	return $error_msg;
}

if (!$u || !is_object($u)) {
	// Check user object first before usage.
	$hookParams['user'] = $u;
	if (getStringFromRequest('submit')) {//if this is set, then the user has issued an Update
		plugin_hook("userisactivecheckboxpost", $hookParams);
	}
}

//use_javascript('/js/sortable.js');
$title = _('My Account');
site_user_header(array('title'=>$title));

?>

<script src='/account/register.js'></script>
<link rel='stylesheet' href='register.css' type='text/css' />

<!-- Force latest IE rendering engine or ChromeFrame if installed -->
<!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><![endif]-->
<!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
<link rel="stylesheet" href="/js/jquery.fileupload.css">
<script src='/js/jquery-ui-1.10.1.custom.min.js'></script>
<!-- The Load Image plugin for previewing images and image resizing -->
<script src="/js/load-image.all.min.js"></script>
<!-- The basic File Upload plugin -->
<script src="/js/jquery.fileupload.js"></script>
<!-- The File Upload processing plugin -->
<script src="/js/jquery.fileupload-process.js"></script>
<!-- The File Upload image preview & resize plugin -->
<script src="/js/jquery.fileupload-image.js"></script>
<!-- The File Upload validation plugin -->
<script src="/js/jquery.fileupload-validate.js"></script>
<script src='/account/uploadHandler.js'></script>

<style>
#fileuploadErrMsg {
	color: #f75236;
	font-size: 12px;
	padding-top: 5px;
}
#fileuploadErrMsg>img {
	float: left;
	padding: 0px;
	margin: 0px;
	width: 16px;
	height: 16px;
}
#fileuploadErrMsg>span {
	padding-left: 5px;
}
</style>

<script>
	// Update flag input components after document has been loaded completely.
	$(document).ready(function() {

<?php
		// Flag components that have errors.
		if (isset($arrErrors)) {
			for ($cnt = 0; $cnt < count($arrErrors) - 1; $cnt++) {
				$tagName = $arrErrors[$cnt];
				// Generate the css associated with component to be flagged.
				echo '$("input[name=\'' . $tagName . '\']").css("border-color", "red");';
			}
		}
?>
	});

	// Need to invoke CAPTCH refresh after all elements have been loaded.
	// Otherwise, CAPTCHA check sometimes fails.
	$(window).load(function() {
		document.getElementById('captcha').src = '/securimage/securimage_show.php?' + Math.random(); 
	});

</script>

<form id="mySubmit" action="<?php echo util_make_url('/account/index.php'); ?>" method="post" enctype="multipart/form-data">
<?php

// Add suspended user info as hidden parameter to keep information for submit handling.
if (isset($suspended) && $suspended) {
	echo '<input type="hidden" name="suspended" value="'. $suspended .'"/>';
}

echo '<input type="hidden" name="form_key" value="'.form_generate_key().'"/>';
?>

<table class="infotable">

<tr class="top">
<td><?php echo _('Login Name')._(':'); ?> </td>
<td><?php print $u->getUnixName(); ?> <a href="change_pw.php">[<?php echo _('Change Password'); ?>]</a></td>
</tr>
<tr class="top">
<td><?php echo _('Email Address') . _(': '); ?> </td>
<td><?php print $u->getEmail(); ?> <a href="change_email.php">[<?php echo _('Change Email Address'); ?>]</a></td>
</tr>
<tr class="top">
<td><?php echo _('Member since')._(':'); ?> </td>
<td><?php print date(_('Y-m-d H:i'),$u->getAddDate()); ?></td>
</tr>

</table>

<div class="row">
<div class="col-sm-10">
<div class="module_account_setup">
	<p><span class="required_note"><br/>Required fields outlined in blue.</span></p>
	<div class="account_table">
		<div class="account_row">
			<div class="account_column">
				<input type="text" name="firstname" class="required" value="<?php if (isset($firstname)) echo $firstname; else echo $u->getFirstName(); ?>" placeholder="First name"/>
			</div> <!-- account_column -->
			<div class="account_column">
				<input type="text" name="lastname" class="required" value="<?php if (isset($lastname)) echo $lastname; else echo $u->getLastName(); ?>" placeholder="Last name"/>
			</div> <!-- account_column -->
		</div> <!-- account_row -->
		<div class="account_row">
			<div class="account_column">
				<input type="text" name="university_name" class="required" value="<?php if (isset($university_name)) echo $university_name; else echo $u->getUniversityName(); ?>" placeholder="University or institution"/>
			</div> <!-- account_column -->
			<div class="account_column">
				<div class="url_box">
					<input type="text" name="university_website" id="university_website" class="url_textfield" value="<?php if (isset($university_website)) echo $university_website; else echo $u->getUniversityWebsite(); ?>" placeholder="University URL" onFocus="focusHandlerRegistrationURL(this, 'captcha_code');" onBlur="validateURL(this, 'Update Account');" onkeydown="if (event.keyCode == 13) {return false;}" />
					<div id="university_website_txtHint" class="url_box_status"></div>
					<div id="university_website_indicator" class="url_box_indicator"></div>
					<div id="university_website_message" class="url_box_message"></div>
					<input type="hidden" id="override_university_website" name="override_university_website" value="<?php if (isset($override_university_website)) echo $override_university_website; else echo "0"; ?>"/>
				</div> <!-- url_box -->
			</div> <!-- account_column -->
		</div> <!-- account_row -->
		<div class="account_row">
			<div class="account_column">
				<?php echo html_get_ccode_popup('ccode', $ccode); ?>
			</div> <!-- account_column -->
		</div> <!-- account_row -->
	</div> <!-- account_table -->
</div> <!-- module_account_setup -->

<div class="module_your_profile_picture">
	<h2>Your profile</h2>
	<p>Your profile picture</p>
	<div class="submodule_picture">
		<div id="fileDataDiv"></div>
		<div class="picture_wrapper"><img id="userpicpreview" 
src="/userpics/<?php

	if (trim($u->getPictureFile() != "")) {
		// Has picture file.
		echo $u->getPictureFile();
	}
	else {
		// Show a default picture file.
//		echo "user_default_thumb.gif";
		echo "user_profile_thumb.jpg";
	}

	// Force image to refresh; otherwise, the cached image 
	// would be used which may be old.
	echo '?dummy_value=' . rand();

?>" />
		</div> <!-- picture_wrapper -->
		<div class="drag_and_drop_wrapper">
			<div class="div_drag_and_drop" id="div_drag_and_drop"><p>To add your photo, drag and drop a file into this box or select a file from your computer. <b>Restrictions: JPG, PNG, GIF, BMP less than 2 MB in file size. Image must be perfect square or else image will be cropped.</b></p>
				<span class="btn btn-success fileinput-button">
					<i class="glyphicon"></i>
					<span>Browse...</span>
					<input type="file" name="files[]" id="fileupload" />
				</span>
			</div> <!-- div_drag_and_drop -->
		</div> <!-- drag_and_dropp_wrapper -->
	</div> <!-- submodule_picture -->
	<div id="fileuploadErrMsg"></div><br/>

	<p>Share your story to build connections and jump start collaborations with others.</p>
	<div class="profile_table">
		<div class="profile_row">
			<div class="profile_2_columns">
				<textarea name="interest_simtk" placeholder="Your interest in SimTK"><?php if (isset($interest_simtk)) echo $interest_simtk; else echo $u->getSimTKInterest(); ?></textarea>
			</div> <!-- profile_2_columns -->
		</div> <!-- profile_row -->
		<div class="profile_row">
			<div class="profile_column">
				<input type="text" name="lab_name" class="" value="<?php if (isset($lab_name)) echo $lab_name; else echo $u->getLabName(); ?>" placeholder="Lab name"/>
			</div> <!-- profile_column -->
			<div class="profile_column">
				<div class="url_box">
					<input type="text" name="lab_website" id="lab_website" class="url_textfield" value="<?php if (isset($lab_website)) echo $lab_website; else echo $u->getLabWebsite(); ?>" placeholder="Lab URL" onFocus="focusHandlerRegistrationURL(this, 'captcha_code');" onBlur="validateURL(this, 'Update Account');" onkeydown="if (event.keyCode == 13) {return false;}" />
					<div id="lab_website_txtHint" class="url_box_status"></div>
					<div id="lab_website_indicator" class="url_box_indicator"></div>
					<div id="lab_website_message" class="url_box_message"></div>
						<input type="hidden" id="override_lab_website" name="override_lab_website" value="<?php if (isset($override_personal_website)) echo $override_lab_website; else echo "0"; ?>"/>
				</div> <!-- url_box -->
			</div> <!-- profile_column -->
		</div> <!-- profile_row -->
		<div class="profile_row">
			<div class="profile_column">
				<div class="url_box">
					<input type="text" name="personal_website" id="personal_website" class="url_textfield" value="<?php if (isset($personal_website)) echo $personal_website; else echo $u->getPersonalWebsite(); ?>" placeholder="Individual URL" onFocus="focusHandlerRegistrationURL(this, 'captcha_code');" onBlur="validateURL(this, 'Update Account');" onkeydown="if (event.keyCode == 13) {return false;}" />
					<div id="personal_website_txtHint" class="url_box_status"></div>
					<div id="personal_website_indicator" class="url_box_indicator"></div>
					<div id="personal_website_message" class="url_box_message"></div>
					<input type="hidden" id="override_personal_website" name="override_personal_website" value="<?php if (isset($override_personal_website)) echo $override_personal_website; else echo "0"; ?>"/>
				</div> <!-- url_box -->
			</div> <!-- profile_column -->
		</div> <!-- profile_row -->
	</div> <!-- profile_table -->
</div> <!-- module_account_setup -->
<div class="module_research_help">
	<span class="textHeader2"><br/><br/><h2>Verify you're not a robot</h2></span><BR/>
	<img id="captcha" src="/securimage/securimage_show.php" alt="CAPTCHA Image" /><BR/>
	Enter the result of the mathematical problem above:<BR/>
	<input type="text" class="required" name="captcha_code" size="20" maxlength="10" /><BR/>
	<a href="#" onclick="document.getElementById('captcha').src = '/securimage/securimage_show.php?' + Math.random(); return false">Try another</a>
	<hr/>
	<input type="submit" name="submit" id="_account" value="Update Account" class="btn-cta"/>
</div> <!-- module_research_setup -->
</div> <!-- col-sm-10 -->
</div> <!-- row-->
</form>

<?php
site_user_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
