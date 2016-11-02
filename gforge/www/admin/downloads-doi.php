<?php
/**
 * Project Admin: Update file in a release.
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright 2012-2014, Franck Villaume - TrivialDev
 * Copyright 2016, Henry Kwong, Tod Hing - SimTK Team
 * http://fusionforge.org/
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
require_once $gfcommon.'include/utils.php';
require_once $gfcommon.'include/User.class.php';
require_once $gfcommon.'frs/FRSPackage.class.php';
require_once $gfcommon.'frs/FRSRelease.class.php';
require_once $gfcommon.'frs/FRSFile.class.php';
require_once $gfcommon.'frs/include/frs_utils.php';
require_once $gfwww.'admin/admin_utils.php';

$result_pending = db_query_params('SELECT group_name,filename,doi_identifier,file_user_id,file_id FROM frs_file,frs_release,frs_package,groups where frs_file.release_id = frs_release.release_id and frs_release.package_id = frs_package.package_id and groups.group_id = frs_package.group_id and frs_file.doi = 1 and doi_identifier is null',array());
$result_assigned = db_query_params("SELECT group_name,filename,doi_identifier FROM frs_file,frs_release,frs_package,groups where frs_file.release_id = frs_release.release_id and frs_release.package_id = frs_package.package_id and groups.group_id = frs_package.group_id and frs_file.doi = 1 and doi_identifier <> '' ",array());


// Update file in release.
if (getStringFromRequest('submit')) {
	
	$file_id = getStringFromRequest('file_id');
    $doi_identifier = getStringFromRequest('doi_identifier');
	$file_user_id = getStringFromRequest('file_user_id');
	$filename = getStringFromRequest('filename');
	$group_name = getStringFromRequest('group_name');
	
	// get user
    //$user = session_get_user(); // get the session user
    //$user_id = $user->getID();
	if ($file_user_id) {
	   $user = user_get_object($file_user_id);
	   $user_email = $user->getEmail();
	}
	
	$result = db_query_params("UPDATE frs_file SET doi_identifier=$1 WHERE file_id=$2", array($doi_identifier, $file_id));

	if (!$result || db_affected_rows($result) < 1) {
		$feedback .= sprintf(_('Error On DOI Update: %s'), db_error());
	} else {
		$feedback .= _('DOI Updated');
		
		if ($file_user_id) {
		   $message = "The DOI has been assigned for the following:\n\n" . "Project: " . $group_name . "\nFilename: " . $filename . "\n" . "DOI Identifier: " . $doi_identifier;
		   $message .= "\n\nThe information for the DOI citation were based upon the information in your project.  Go to http://ezid.cdlib.org/id/doi:" . $doi_identifier . "\nto see how your resource is listed.  If you would like to add ORCIDs or funding institutions, or if any of the information needs to be updated, please email us at webmaster@simtk.org.";
		   util_send_message($user_email, sprintf(_('DOI Assigned')), $message);
		}
		
		// refresh query results
		$result_pending = db_query_params('SELECT group_name,filename,doi_identifier,file_user_id,file_id FROM frs_file,frs_release,frs_package,groups where frs_file.release_id = frs_release.release_id and frs_release.package_id = frs_package.package_id and groups.group_id = frs_package.group_id and frs_file.doi = 1 and doi_identifier is null',array());
        $result_assigned = db_query_params("SELECT group_name,filename,doi_identifier FROM frs_file,frs_release,frs_package,groups where frs_file.release_id = frs_release.release_id and frs_release.package_id = frs_package.package_id and groups.group_id = frs_package.group_id and frs_file.doi = 1 and doi_identifier <> '' ",array());

	}
			
	
}

site_admin_header(array('title'=>_('Site Admin')));
//$title = _('Downloads DOI');
//$HTML->header(array('title'=>$title));
?>

<script>
	$(document).ready(function() {
		if ($('#docLink').is(":checked")) {
			// Disable inputs for File upload.
			$('.upFile').prop("disabled", true);
			$('[name="group_list_id"]').prop("disabled", true);
		}
		else {
			// Enable inputs for File upload.
			$('.upFile').prop("disabled", false);
			$('[name="group_list_id"]').prop("disabled", false);
			$('#doi').prop("disabled", false);
		}
		$('#docFile').click(function() {
			// Disable inputs for File upload.
			$('.upFile').prop("disabled", false);
			$('[name="group_list_id"]').prop("disabled", false);
			$('#doi').prop("disabled", false);
		});
		$('#docLink').click(function() {
			// Enable inputs for File upload.
			$('.upFile').prop("disabled", true);
			$('#doi').attr('checked', false);
		    $('#doi_info').hide();
		    $('#doi').prop("disabled", true);
			$('[name="group_list_id"]').prop("disabled", true);
		});

		$('#collect_info').click(function() {
			var theValue = $('#collect_info').is(":checked");
			if (theValue == 0) {
				$('#use_mail_list').prop('checked', 0);
				$('#use_mail_list').prop('disabled', true);
				$('[name="group_list_id"]').prop("disabled", true);
			}
			else {
				$('#use_mail_list').prop('disabled', false);
				$('[name="group_list_id"]').prop("disabled", false);
			}
		});
		if (!$('#collect_info').is(":checked")) {
			$('#use_mail_list').prop('checked', 0);
			$('#use_mail_list').prop('disabled', true);
			$('[name="group_list_id"]').prop("disabled", true);
		}
		$('#doi').change(function(){
            if(this.checked)
               //$('#doi_info').fadeIn('slow');
			   $('#doi_info').show();
		    else
		       $('#doi_info').hide();
        });
		$("#submit").click(function(){
	       if ($('#doi').is(":checked")) {
              if (!confirm("Please confirm that you would like the DOI issued.")){
                event.preventDefault();
              }
	       }
        });
	});

</script>

<style>
td {
	padding-bottom:5px;
	vertical-align:top;
}
</style>


<h2>Admin DOI</h2>

<h3>DOI Pending</h3>

<table class="table">

<tr><th>Project Name</th><th>Display Name</th><th>DOI Identifier</th><th></th></tr>
	
	<?php
	
	while ($row = db_fetch_array($result_pending)) {	
	   echo "<form action='downloads-doi.php' method='post'><input type='hidden' name='file_id' value='".$row['file_id']."'><input type='hidden' name='file_user_id' value='".$row['file_user_id']."'><input type='hidden' name='filename' value='".$row['filename']."'><input type='hidden' name='group_name' value='".$row['group_name']."'><tr><td>". $row['group_name'] . "</td><td>" . $row['filename'] . "</td><td><input type='text' name='doi_identifier'></td><td><input type='submit' name='submit' id='submit' value='Update' class='btn-cta' /></td></tr></form>";
	}
	
	?>

</table>

<h3>DOI Assigned</h3>
<table class="table">
<tr><th>Project Name</th><th>Display Name</th><th>DOI Identifier</th></tr>
<tr>
	<?php
	
	while ($row = db_fetch_array($result_assigned)) {
	
	   echo "<tr><td>" . $row['group_name'] . "</td><td>" . $row['filename'] . "</td><td>" . $row['doi_identifier'] . "</td></tr>";
	}
	
	?>

</table>

<?php

site_project_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
