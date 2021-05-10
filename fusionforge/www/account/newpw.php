<?php

/**
 *
 * newpw.php
 * 
 * File to get a new password for user.
 *
 * Copyright 2005-2021, SimTK Team
 *
 * This file is part of the SimTK web portal originating from        
 * Simbios, the NIH National Center for Physics-Based               
 * Simulation of Biological Structures at Stanford University,      
 * funded under the NIH Roadmap for Medical Research, grant          
 * U54 GM072970, with continued maintenance and enhancement
 * funded under NIH grants R01 GM107340 & R01 GM104139, and 
 * the U.S. Army Medical Research & Material Command award 
 * W81XWH-15-1-0232R01.
 * 
 * SimTK is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as 
 * published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 * 
 * SimTK is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details. 
 * 
 * You should have received a copy of the GNU General Public 
 * License along with SimTK. If not, see  
 * <http://www.gnu.org/licenses/>.
 */ 
 
require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';

$loginname = getStringFromRequest('loginname');
$HTML->header(array('title'=>"New Account Password",'pagename'=>'account_newpw'));

if ($loginname) {

	$resPass = db_query_params('SELECT unix_pw, user_pw FROM users WHERE user_name=$1',
		array($loginname));
	$pass = db_fetch_array($resPass);
	$theUnixPw = $pass['unix_pw'];
	$theUserPw = $pass['user_pw'];
	if ($theUnixPw != "UPDATE_ME" && $theUserPw != "OUT OF DATE") {
		header('Location: https://' .
			$_SERVER['HTTP_HOST']);
		return;
	}

	$u = user_get_object_by_name($loginname);
	if (!$u || !is_object($u)){
		exit_error(_('That user does not exist.'),'my');
	}

	if ($u->getEmail() == "") {
/*
		exit_error($Language->getText('general','error'),
			$Language->getText('account_change_email','invalid_email'));
*/
		exit_error('Invalid user account');
	}

	echo "<h2>You are required to change your password</h2>";

	// First, we need to create new confirm hash
	$confirm_hash = md5(forge_get_config('session_key') . 
		strval(time()) . 
		strval(util_randbytes()));

	$u->setNewEmailAndHash($u->getEmail(), $confirm_hash);
	if ($u->isError()) {
		exit_error('Error', $u->getErrorMessage());
	}
	else {
		$message = 'A password change is required for your account on the SimTK site. ';
		$message .= 'Please visit the following URL to change your password:';
		$message .= "\n\n";

		$message .= util_make_url("/account/lostlogin.php?ch=_".$confirm_hash);
		$message .= "\n\n";
		$message .= '-- the SimTK staff';
		$message .= "\n";


		util_send_message($u->getEmail(),
			sprintf('%s Verification', forge_get_config('forge_name')),
			$message);

		echo $strPassChgMsg = "For security reasons, we are asking you to change your password. " .
			'An email with the subject "SimTK Verification" has been sent to the address you have on file. ' .
			"Click on the link in the email to change your account password.<br/><br/>" .
			"Should you have any questions about this, you can email security@simtk.org. " .
			"Thanks for your cooperation.";
	}
}

$HTML->footer(array());

?>


