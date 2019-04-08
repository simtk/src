<?php
/**
 * Project Admin: Obtain a package DOI.
 *
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

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'frs/FRSPackage.class.php';
require_once $gfcommon.'frs/include/frs_utils.php';

$group_id = getIntFromRequest('group_id');
$package_id = getIntFromRequest('package_id');
$func = getStringFromRequest('func');

if (!$group_id) {
	exit_no_group();
}
if (!$package_id) {
	session_redirect('/frs/admin/?group_id='.$group_id);
}

$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
}
elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'frs');
}
session_require_perm ('frs', $group_id, 'write') ;

// Get package.
$frsp = new FRSPackage($group, $package_id);
if (!$frsp || !is_object($frsp)) {
	exit_error(_('Could Not Get FRS Package'),'frs');
}
elseif ($frsp->isError()) {
	exit_error($frsp->getErrorMessage(),'frs');
}
$packName = $frsp->getName();

// Obtain package DOI.
if (getStringFromRequest('submit') && $func=="obtain_package_doi" && $package_id) {

	$doi = getIntFromRequest('doi');
	if (empty($doi)) {
		$doi = 0;
	}
	$doi_confirm = 0;
	
	// get user
	$user = session_get_user(); // get the session user
	$user_id = $user->getID();
	
	if ($doi) {
		$ret = $frsp->setDoi($user_id);
		if ($ret === false) {
			// Return the error message.
			$error_msg = $frsp->getErrorMessage();
		}

		$doi_confirm = 1;
		$real_name = $user->getRealName();
		$message = "\nPlease visit the following URL to assign DOI: \n" . 
			util_make_url('/admin/downloads-doi.php');
		util_send_message("webmaster@simtk.org", 
			sprintf('DOI for %s package requested by %s', $packName, $real_name), 
			$message);

		$feedback = 'Your DOI for the package will be emailed within 72 hours. ';
	}
}

frs_admin_header(array('title'=>'Obtain Package DOI','group'=>$group_id));
?>

<script>
	$(document).ready(function() {
		$('#doi').change(function() {
			if (this.checked)
				//$('#doi_info').fadeIn('slow');
				$('#doi_info').show();
			else
				$('#doi_info').hide();
		});
		$("#submit").click(function() {
			if ($('#doi').is(":checked")) {
				if (!confirm("I confirm that I would like to have this package made permanent. Please issue a DOI.")) {
					event.preventDefault();
				}
			}
		});
	});

</script>
<script type="text/javascript">    
    $(document).ready(function() {
     window.history.forward(1);
	});
</script>
  
<style>
td {
	padding-bottom:5px;
	vertical-align:top;
}
</style>

<?php if (isset($doi) && ($doi) && isset($doi_confirm) && ($doi_confirm)) { ?>

<p>This package is being assigned a DOI and can no longer be edited or deleted.</p>
<script type="text/javascript">
	$(document).ready(function() {
		window.history.forward(1);
	});
</script>

<?php } else { ?>

<div><h4><?php echo $frsp->getName(); ?><p/></h4></div>

<form id="obtainPackageDoi" enctype="multipart/form-data" action="obtainPackageDoi.php" method="post">

<input type="hidden" name="func" value="obtain_package_doi" />
<input type="hidden" name="package_id" value="<?php echo $package_id; ?>" />
<input type="hidden" name="group_id" value="<?php echo $group_id; ?>" />

<table>

<tr>
	<td><input type="checkbox" name="doi" id="doi" value="1" />&nbsp;Obtain a DOI for package
	<div id="doi_info" style="display:none">
        <font color="#ff0000">Warning: You will not be able to remove or edit this package after the DOI has been issued.  You will not be able to remove or edit releases or files it contains either.</font>
    </div>
	</td>
</tr>
<tr>
	<td><input type="submit" name="submit" id="submit" value="Submit" class="btn-cta" /></td>
</tr>
</table>

</form>

<?php
} // end of else

frs_admin_footer();

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
