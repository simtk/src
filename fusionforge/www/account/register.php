<?php
/**
 * Register new account page
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2010 (c) FusionForge Team
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2013-2014, Franck Villaume - TrivialDev
 * Copyright 2016-2020, Henry Kwong, Tod Hing - SimTK Team
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
require_once $gfcommon.'include/timezones.php';
require_once '../securimage/securimage.php';
// Allow alternate content-type rendering by hook
$default_content_type = 'text/html';

global $HTML;
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

if (isset($theme_id) && (!$theme_id || !is_numeric($theme_id))) {
	$theme_id = getThemeIdFromName(forge_get_config('default_theme'));
}

$context = array(
	"ssl"=>array(
		"verify_peer"=>false,
		"verify_peer_name"=>false,
	),
);

// For CAPTCHA error display.
$errVerify = "";
if (getStringFromRequest('submit') == "Create Account") {

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

	// Adding call to library rather than logic that used to be coded in this page
	$res = form_key_is_valid(getStringFromRequest('form_key'), $reason);
	if (!$res) {
		if ($reason == "expired") {
			$error_msg = "Registration session expired";

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
		else {
			exit_form_double_submit('my');
		}
	}

	/*
	if (forge_get_config('user_registration_accept_conditions') && ! $accept_conditions) {
		$warning_msg = _("You can't register an account unless you accept the terms of use.");
		$valide = 0;
	}
	*/

	if (!forge_check_global_perm('forge_admin')) {
		$params['valide'] =& $valide;
		$params['warning_msg'] =& $warning_msg;
		plugin_hook('captcha_check', $params);
	}

	$unix_name = getStringFromRequest('unix_name');
	$password1 = getStringFromRequest('password1');
	$password2 = getStringFromRequest('password2');
	$email = getStringFromRequest('email');
	$firstname = getStringFromRequest('firstname');
	$lastname = getStringFromRequest('lastname');
	$university_name = getStringFromRequest('university_name');
	$university_website = getStringFromRequest('university_website');
	$interest_simtk = getStringFromRequest('interest_simtk');
	$lab_name = getStringFromRequest('lab_name');
	$lab_website = getStringFromRequest('lab_website');
	$personal_website = getStringFromRequest('personal_website');
	$ccode = getStringFromRequest('ccode');
	$found_us = getStringFromRequest('found_us');
	$found_us_note = getStringFromRequest('found_us_note');

	// The found_us_note is only used for Conference.
	if (!isset($found_us) || $found_us != "Conference") {
		$found_us_note = "";
	}

	$userpicfilename = getStringFromRequest('userpicfilename');
	$userpicfiletype = getStringFromRequest('userpicfiletype');

	/*
	echo "unix_name: $unix_name \n";
	echo "password1: $password1 \n";
	echo "password2: $password2 \n";
	echo "email: $email \n";
	echo "firstname: $firstname \n";
	echo "lastname: $lastname \n";
	echo "university_name: $university_name \n";
	echo "university_website: $university_website \n";
	echo "interest_simtk: $interest_simtk \n";
	echo "lab_name: $lab_name \n";
	echo "lab_website: $lab_website \n";
	echo "personal_website: $personal_website \n";
	echo "ccode: $ccode \n";
	echo "found_us: $found_us \n";
	echo "found_us_note: $found_us_note \n";
	echo "userpicfilename: " . $userpicfilename;
	echo "v: " . $userpicfiletype;
	echo "override_lab_website: " . $override_lab_website;
	echo "override_university_website: " . $override_university_website;
	echo "override_personal_website: " . $override_personal_website;
	*/

	// Proceed with registration.
	if ($valide) {
		$mail_site = 0;
		$mail_va = 0;
		// English.
		$language_id = 1;
		$timezone = "US/Pacific";
		// Simtk.
		$theme_id = 24;

		$activate_immediately = 1;
		//$activate_immediately = getIntFromRequest('activate_immediately');
		if (($activate_immediately == 1) && 
			forge_check_global_perm ('forge_admin')) {
			$send_mail = false;
			$activate_immediately = true;
		}
		else {
			$send_mail = true;
			$activate_immediately = false;
		}

		// Save to db.
		$new_user = new GFUser();
		$registered = $new_user->create($unix_name, $firstname, $lastname, 
			$password1, $password2, $email,
			$mail_site, $mail_va, $language_id, $timezone, 
			'', 0, $theme_id, '', 
			"", "", "", "", "",
			$ccode, $send_mail, true,
			$interest_simtk, $lab_name, $lab_website,
			$university_name, $university_website, $personal_website,
//			$userpic_tmpfile, $userpic_type,
			$userpicfilename, $userpicfilename,
			$found_us, $found_us_note,
			$override_lab_website,
			$override_university_website,
			$override_personal_website);

		if ($registered) {
			// Successful registration.

			// Clear cookie(s) for website overrides.
			setcookie("override_lab_website", "1", time() - 300);
			setcookie("override_university_website", "1", time() - 300);
			setcookie("override_personal_website", "1", time() - 300);

			site_header(array('title'=>_('Register Confirmation')));

			if ($activate_immediately) {
				if (!$new_user->setStatus('A')) {
					print '<span class="error">' .
						_('Error during user activation but after user registration (user is now in pending state and will not get a notification eMail!)') . '</span>' ;
					print '<p>' . sprintf(_("Could not activate newly registered user's forge account: %s"), htmlspecialchars($new_user->getErrorMessage())) . '</p>';
					$HTML->footer(array());
					exit;
				}
			}

			// Success.
			if ($send_mail) {
				echo '<p>';
				printf('<br/><br/>You have registered the %1$s account on %2$s.',
				       $new_user->getUnixName(),
				       forge_get_config('forge_name'));
				echo '</p>';
				print '<p>' . _('A confirmation email is being sent to verify the submitted email address. Visiting the link sent in this email will activate the account.') . '</p>';
			}
			else {
				print '<p>' ;
/*
				printf (_('You have registered and activated user %1$s on %2$s. They will not receive an eMail about this fact.'), $unix_name, forge_get_config('forge_name'));
*/
				printf (_('You have registered and activated user %1$s. The user will not receive an eMail about this fact.'), $unix_name);

				print '</p>' ;

				// Add user to phpbb_users.
				$urlUserUpdate = "https://".
					getStringFromServer('HTTP_HOST') .
					"/plugins/phpBB/sync_user.php?" .
					"userName=" . $unix_name;

				// Invoke URL access to add the user to phpbb_users.
				//$resStr = file_get_contents($urlUserUpdate);
				// NOTE: context is required for file_get_contents().
				$resStr = file_get_contents($urlUserUpdate, false, stream_context_create($context));
			}
			site_footer(array());
			exit;
		}
		else {
			// Failed registration.

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

			$error_msg = $new_user->getErrorMessage();

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


if (!isset($ccode) || empty($ccode) || !preg_match('/^[a-zA-Z]{2}$/', $ccode)) {
	$ccode = forge_get_config('default_country_code');
}

$HTML->header(array('title'=>_(''),'pagename'=>''));

// Retrieve the error message and names of components to flag.
function retrieveErrorMessages($error_msg, &$arrErrors) {

	// Error messages are separated using "^" as delimiter.
	$arrErrors = explode("^", $error_msg);

	// The error message is the last token.
	// Note: "^" can be not present in the string; i.e. "".
	$error_msg = $arrErrors[count($arrErrors) - 1];

	return $error_msg;
}

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

<form id="mySubmit" action="<?php echo util_make_url('/account/register.php'); ?>" method="post" enctype="multipart/form-data">

<div class='row'>
	<div class='col-sm-12'><h1>Create a SimTK account</h1></div> <!-- col-sm-12 -->
	<input type="hidden" name="form_key" value="<?php echo form_generate_key(); ?>"/>
</div> <!-- row -->
<div class="row">
<div class="col-sm-10">
<div class="module_account_setup">
	<h2>Account setup</h2>
	<p><span class="required_note">Required fields outlined in blue.</span></p>
	<div class="account_table">
		<div class="account_row">
			<div class="account_column">
				<input type="text" name="unix_name" class="required" value="<?php if (isset($unix_name)) echo $unix_name; ?>" placeholder="Login name (lower-case, no space)"/>
			</div> <!-- account_column -->
			<div class="responsive_placeholder"></div>
		</div> <!-- account_row -->
		<div class="account_row">
			<div class="account_column">
				<input type="password" name="password1" class="required" placeholder="Password (at least 6 chars)"/>
			</div> <!-- account_column -->
			<div class="account_column">
				<input type="password" name="password2" class="required" placeholder="Confirm Password"/>
			</div> <!-- account_column -->
		</div> <!-- account_row -->
		<div><span style="color:#f75236;">Do not use a Hotmail account.<br/>You will not receive the activation email.</span></div>
		<div class="account_row">
			<div class="account_column">
				<input type="text" name="email" class="required" value="<?php if (isset($email)) echo $email; ?>" placeholder="Email address"/>
			</div> <!-- account_column -->
			<div class="responsive_placeholder"> </div>
		</div> <!-- account_row -->
		<div class="account_row">
			<div class="account_column">
				<input type="text" name="firstname" class="required" value="<?php if (isset($firstname)) echo $firstname; ?>" placeholder="First name"/>
			</div> <!-- account_column -->
			<div class="account_column">
				<input type="text" name="lastname" class="required" value="<?php if (isset($lastname)) echo $lastname; ?>" placeholder="Last name"/>
			</div> <!-- account_column -->
		</div> <!-- account_row -->
		<div class="account_row">
			<div class="account_column">
				<input type="text" name="university_name" class="required" value="<?php if (isset($university_name)) echo $university_name; ?>" placeholder="University or institution"/>
			</div> <!-- account_column -->
			<div class="account_column">
				<div class="url_box">
					<input type="text" name="university_website" id="university_website" class="url_textfield" value="<?php if (isset($university_website)) echo $university_website; ?>" placeholder="University URL" onFocus="focusHandlerRegistrationURL(this, 'captcha_code');" onBlur="validateURL(this, 'Create Account');" onkeydown="if (event.keyCode == 13) {return false;}" />
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
		<div class="picture_wrapper">
			<img id="userpicpreview" src="/userpics/user_profile_thumb.jpg" />
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
				<textarea name="interest_simtk" placeholder="Your interest in SimTK"><?php if (isset($interest_simtk)) echo $interest_simtk; ?></textarea>
			</div> <!-- profile_2_columns -->
		</div> <!-- profile_row -->
		<div class="profile_row">
			<div class="profile_column">
				<input type="text" name="lab_name" class="" value="<?php if (isset($lab_name)) echo $lab_name; ?>" placeholder="Lab name"/>
			</div> <!-- profile_column -->
			<div class="profile_column">
				<div class="url_box">
					<input type="text" name="lab_website" id="lab_website" class="url_textfield" value="<?php if (isset($lab_website)) echo $lab_website; ?>" placeholder="Lab URL" onFocus="focusHandlerRegistrationURL(this, 'captcha_code');" onBlur="validateURL(this, 'Create Account');" onkeydown="if (event.keyCode == 13) {return false;}" />
					<div id="lab_website_txtHint" class="url_box_status"></div>
					<div id="lab_website_indicator" class="url_box_indicator"></div>
					<div id="lab_website_message" class="url_box_message"></div>
					<input type="hidden" id="override_lab_website" name="override_lab_website" value="<?php if (isset($override_lab_website)) echo $override_lab_website; else echo "0"; ?>"/>
				</div> <!-- url_box -->
			</div> <!-- profile_column -->
		</div> <!-- profile_row -->
		<div class="profile_row">
			<div class="profile_column">
				<div class="url_box">
					<input type="text" name="personal_website" id="personal_website" class="url_textfield" value="<?php if (isset($personal_website)) echo $personal_website; ?>" placeholder="Individual URL" onFocus="focusHandlerRegistrationURL(this, 'captcha_code');" onBlur="validateURL(this, 'Create Account');" onkeydown="if (event.keyCode == 13) {return false;}" />
					<div id="personal_website_txtHint" class="url_box_status"></div>
					<div id="personal_website_indicator" class="url_box_indicator"></div>
					<div id="personal_website_message" class="url_box_message"></div>
					<input type="hidden" id="override_personal_website" name="override_personal_website" value="<?php if (isset($override_personal_website)) echo $override_personal_website; else echo "0"; ?>"/>
				</div> <!-- url_box -->
			</div> <!-- profile_column -->
		</div> <!-- profile_row -->
	</div> <!-- profile_table -->
</div> <!-- module_your_profile_picture -->
<div class="module_research_help">
	<h2>Help us reach more great people</h2>
	<p>How did you learn about SimTK?<br/><span style="color: #999;">This information is only used to assess our dissemination efforts.</span></p>
	<input type="radio" name="found_us" value="Publication" <?php if (isset($found_us) && $found_us == 'Publication') echo 'checked="checked"'; ?>><label>Publication (e.g., journal article)</label><br/>
	<input type="radio" name="found_us" value="Mailing_list" <?php if (isset($found_us) && $found_us == 'Mailing_list') echo 'checked="checked"'; ?>><label>Mailing list or newsletter</label><br/>
	<input type="radio" name="found_us" value="Conference" <?php if (isset($found_us) && $found_us == 'Conference') echo 'checked="checked"'; ?>><label>Conference</label><br/>
	<input type="text" name="found_us_note" class="conferecne_name" value="<?php if (isset($found_us_note)) echo $found_us_note; ?>" placeholder="Please Specify"/><br/>
	<input type="radio" name="found_us" value="Class" <?php if (isset($found_us) && $found_us == 'Class') echo 'checked="checked"'; ?>><label>Class</label><br/>
	<input type="radio" name="found_us" value="Biomedical_Computation_Review" <?php if (isset($found_us) && $found_us == 'Biomedical_Computation_Review') echo 'checked="checked"'; ?>><label>Biomedical Computation Review</label><br/>
	<input type="radio" name="found_us" value="Word of Mouth" <?php if (isset($found_us) && $found_us == 'Word of Mouth') echo 'checked="checked"'; ?>><label>Word of Mouth</label><br/>
	<input type="radio" name="found_us" value="Other" <?php if (isset($found_us) && $found_us == 'Other') echo 'checked="checked"'; ?>><label>Other</label><br/>

	<span class="textHeader2"><br/><br/><h2>Verify you're not a robot</h2></span><BR/>
	<img id="captcha" src="/securimage/securimage_show.php" alt="CAPTCHA Image" /><BR/>
	Enter the result of the mathematical problem above:<BR/>
	<input type="text" class="required" name="captcha_code" size="20" maxlength="10" /><BR/>
	<a href="#" onclick="document.getElementById('captcha').src = '/securimage/securimage_show.php?' + Math.random(); return false">Try another</a>
	<hr/>
	<input type="submit" name="submit" id="_account" value="Create Account" class="btn-cta"/>
</div> <!-- module_research_help -->
</div> <!-- col-sm-10 -->
<div class="col-sm-2 sign-up-right">
	<p>A SimTK account allows you to create projects, join existing projects, and connect with other SimTK members.  To learn more about our privacy policies, read <a href="/pledge.php">Our Pledge and Your Responsibility</a>.</p>
</div> <!-- col-sm-2 sign-up-right -->
</div> <!-- row -->

</form>
<?php
$HTML->footer(array());
// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
