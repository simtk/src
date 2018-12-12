<?php

/**
 *
 * close_acct.php
 * 
 * Close user account.
 * 
 * Copyright 2005-2018, SimTK Team
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
require_once $gfcommon . 'include/clean_user_utils.php';

session_require_login() ;

$u = user_get_object(user_getid());
if (!$u || !is_object($u)) {
	exit_error(_('Could Not Get User'),'my');
}
elseif ($u->isError()) {
	exit_error($u->getErrorMessage(),'my');
}

// Check where user deletion has already been requested.
$res_user = db_query_params("SELECT * FROM user_delete_pending " .
	"WHERE user_name=$1",
	array($u->getUnixName()));
if (db_numrows($res_user) > 0) {
	exit_error('Your account is being reviewed for closure. If you have questions, you can contact the SimTK webmaster.','my');
}
db_free_result($res_user);

if (getStringFromRequest('submit')) {
	if (!form_key_is_valid(getStringFromRequest('form_key'))) {
		exit_form_double_submit('my');
	}

	$sure = getStringFromRequest('sure');
	$really_sure = getStringFromRequest('really_sure');
	if (!isset($sure) || $sure != "1") {
		$error_msg = 'You did not check “I am Sure”';
	}
	else if (!isset($really_sure) || $really_sure != "1") {
		$error_msg = 'You did not check “I am Really Sure”';
	}
	else {

		// Retrieve Wiki users.
		$arrWikiUser = getWikiUsersFromDB();

		// Retrieve user-associated database tables/columns descriptions.
		$arrAssociationsGforge = array();
		$arrAssociationsPhpbb = array();
		getUserAssociatedTablesAndColumns($arrAssociationsGforge, $arrAssociationsPhpbb);

		// Get phpBB db connection (different from gforge db connection, which is the default).
		$dbconnPhpbb = getDbconnPhpbb();

		$userName = $u->getUnixName();
		$uidGforge = $u->getID();
		$realName = $u->getRealName();

		// Get the associated phpBB user_id given the user name.
		$uidPhpbb = getPhpbbUserIdWithUsername($dbconnPhpbb, $userName);

		// Check whether there are associated entries in
		// gforge or phpbbdatabase wth the user before cleaning.
		$isEntryPresent = checkUserReferences($dbconnPhpbb,
			$userName,
			$uidGforge,
			$uidPhpbb,
			$arrAssociationsGforge,
			$arrAssociationsPhpbb,
			$arrWikiUser,
			$msg);

		$isSuccess = false;
		if ($isEntryPresent !== true) {
			// Delete the user entry from gforge and phpbb databases.
			$status = cleanUserEntries($dbconnPhpbb);
			if ($status) {
				// Send email.
				$msgClose = "\nSuccessfully closed account\n";
				util_send_message("webmaster@simtk.org",
					sprintf('SimTK close account request from  %s', $realName),
					$msgClose);

				$error_msg = "Your SimTK account has been successfully closed.";
				$isSuccess = true;
			}
			else {
				// Send email.
				$msgClose = "\nCannot close SimTK account\n";
				util_send_message("webmaster@simtk.org",
					sprintf('SimTK close account request from  %s', $realName),
					$msgClose);

				// Cannot delete user entry.
				$error_msg = "Your request to close your SimTK account is being reviewed.<br/>Contact webmaster@simtk.org if you have questions";
			}
		}
		else {
			// Send email.
			$msgClose = "\nCannot close SimTK account: $msg\n";
			util_send_message("webmaster@simtk.org",
				sprintf('SimTK close account request from  %s', $realName),
				$msgClose);

			// Cannot delete user entry.
			$error_msg = "Your request to close your SimTK account is being reviewed.<br/>Contact webmaster@simtk.org if you have questions";
		}

		// Cannot delete user; add user entry to user_delete_pending table.
		if ($isSuccess !== true) {
			$res_user = db_query_params("INSERT INTO user_delete_pending " .
				"(user_name) VALUES ($1) ",
				array($u->getUnixName()));
		}

		session_logout();
		session_redirect("/");
	}
}

site_user_header(array('title'=>'Close Account'));

?>

<style>
.maindiv>.submenu {
	padding-left: 0px !important;
}
</style>

	<form action="/account/close_acct.php" method="post">
	<input type="hidden" name="form_key" value="<?php echo form_generate_key(); ?>"/>
        <h4>Are you sure you want to close your SimTK account?</h4>
	<p>Closing your account will delete your SimTK account and profile information. Any other information or messages posted on the site will not be deleted and will instead be associated with an anonymous account. Closing your account does not unsubscribe you from mailing lists.</p>
        <input type="checkbox" name="sure" value="1" />&nbsp;I am Sure<br/>
        <input type="checkbox" name="really_sure" value="1" />&nbsp;I am Really Sure<br/><br/>
        <input type="submit" name="submit" value="Close Account" class="btn-cta" />
<?php

site_user_footer(array());

?>
