<?php

/**
 *
 * simulationsAdmin.php
 * 
 * UI for simulation administration.
 * 
 * Copyright 2005-2016, SimTK Team
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

$group_id = getIntFromRequest('group_id');

session_require_perm('project_admin', $group_id);

// This page updates simulation_job information.
// An entry must already exists in the simulation_job table.
// NOTE: simulation_job entry can only be updated, but cannot be 
// inserted or deleted by the project admin.

// Update simulation_job information.
if (getStringFromRequest('submit')) {
	$simuDesc = trim(getStringFromRequest('simuDesc'));
	$simuLic = trim(getStringFromRequest('simuLic'));
	$membersOnly = trim(getStringFromRequest('membersOnly'));

	if ($membersOnly == 1) {
		$isPermitAll = 0;
	}
	else {
		$isPermitAll = 1;
	}

	// Save simulation_job information.
	$status = updateSimulationInfo($simuDesc, $simuLic, $isPermitAll, $group_id);
	if ($status === true) {
		$feedback = "Updated simulation job information.";
	}
	else {
		$error_msg = $status;
	}
} 


// Retrieve simulation job information.
$simuDesc = "";
$simuLic = "";
$isPermitAll = 0;
$sqlInfo = "SELECT description, license_agreement, permission " .
	"FROM simulation_job " .
	"WHERE group_id=$1";
$resInfo = db_query_params($sqlInfo, array($group_id));
$cntInfo = db_numrows($resInfo);
while ($row = db_fetch_array($resInfo)) {
	$simuDesc = $row['description'];
	$simuLic = $row['license_agreement'];
	$isPermitAll = $row['permission'];
}
db_free_result($resInfo);

site_project_header(array('title'=>'Simulations', 
	'h1' => '', 
	'group'=>$group_id, 
	'toptab' => 'home' ));


// Update simulation job information.
function updateSimulationInfo($simuDesc, $simuLic, $isPermitAll, $group_id) {

	$sqlInfo = 'UPDATE simulation_job SET ' .
		'description=$1, ' .
		'license_agreement=$2, ' .
		'permission=$3 ' .
		'WHERE group_id=$4';

	db_begin();

	$resInfo = db_query_params($sqlInfo,
		array(
			$simuDesc,
			$simuLic,
			$isPermitAll,
			$group_id
		)
	);

	if (!$resInfo || db_affected_rows($resInfo) < 1) {
		db_rollback();
		$error_msg = sprintf('Error On Update: %s', db_error());
		return $error_msg;
	}

	db_commit();

	// Done.
	return true;
}

?>

<h2>Simulation Job Admin
<br/>
<br/>
</h2>

<fieldset>
<form enctype="multipart/form-data" method="POST">

<div class="project_overview_main">
        <div style="display: table; width: 100%;">
                <div class="main_col">

<table style="max-width:645px;" width="100%" cellpadding="2" cellspacing="2">

<tr>
	<td><strong>Description:&nbsp;</strong></td>
	<td><textarea style="margin-top:5px;" rows="6" cols="50" name="simuDesc"><?php echo $simuDesc; ?></textarea></td>
</tr>

<tr>
	<td><strong>License agreement:&nbsp;</strong></td>
	<td><textarea style="margin-top:5px;" rows="6" cols="50" name="simuLic"><?php echo $simuLic; ?></textarea></td>
</tr>

<tr>
	<td><strong>Team members only:&nbsp;</strong></td>
	<td><input type="checkbox"
		name="membersOnly"
		value="1"
		<?php
			if (isset($isPermitAll) && $isPermitAll == 0) {
				echo "checked";
			}
		?>
		>
	</td>
</tr>

</table>

<br/><br/>
<input type="submit" name="submit" value="Update" class="btn-cta" />


</form>
</fieldset>

		</div>
	</div>
</div>

<?php

$HTML->footer(array());

?>
