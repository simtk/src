<?php

/*
 * Datashare plugin
 *
 * Tod Hing tod_hing@yahoo.com
 *
 */

//error_reporting(-1);
//ini_set('display_errors', 'On');

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfplugins.'datashare/www/datashare-utils.php';
require_once $gfplugins.'datashare/include/Datashare.class.php';
require_once $gfwww.'project/project_utils.php';


	$id = getStringFromRequest('id');
	if (!isset($id) || $id == null || trim($id) == "") {
		// Try group_id.
		$id = getStringFromRequest('group_id');
	}
	$pluginname = getStringFromRequest('pluginname');
	if (!isset($pluginname) || $pluginname == null || trim($pluginname) == "") {
		// Set a default.
		$pluginname = 'datashare';
	}

	if (!$id) {
		exit_error("Cannot Process your request","No ID specified");
	} else {

	        $login = getIntFromRequest('login');
	        if (isset($login) && $login) {
		       session_require_perm('datashare', $id, 'read_public');
	        }


			$group = group_get_object($id);
			if ( !$group) {
				exit_error("Invalid Project", "Inexistent Project");
			}

			if ( ! ($group->usesPlugin ( $pluginname )) ) {//check if the group has the Data Share plugin active
				exit_error("Error", "First activate the $pluginname plugin through the Project's Admin Interface");
			}

			// get user
            //$user = session_get_user(); // get the session user
            //$userid = $user->getID();

			// other perms checks here...
			datashare_header(array('title'=>'Data Share'),$id);

			?>


            <?php

            echo "<div class=\"project_overview_main\">\n";
            echo "<div style=\"display: table; width: 100%;\">\n";
            echo "<div class=\"main_col\">\n";


	    // get current studies
						$study = new Datashare($group_id);

						if (!$study || !is_object($study)) {
	                       exit_error('Error','Could Not Create Study Object');
                        } elseif ($study->isError()) {
	                       exit_error($study->getErrorMessage(), 'Datashare Error');
                        }

						$study_result = $study->getStudyByGroup($group_id);

						if ($study_result) {

						  echo '<table class="table">';
						  echo "<tr><th>Study Title</th><th>Status</th><th>Description</th></tr>";
						  foreach ($study_result as $result) {
						     if ($result->active) {
							   $status = "Active";
							 } else {
							   $status = "Pending Activation";
							 }
							 $display_link = 0;
							 if ($result->is_private < 2) {
							    // public
								$display_link = 1;
							 } else if (session_loggedin() && (user_ismember($group_id) || forge_check_global_perm('forge_admin'))) {
							    // private
								/*
								$res = db_query_params("SELECT * FROM pfo_role_setting,pfo_user_role WHERE pfo_role_setting.role_id = pfo_user_role.role_id and user_id = ".$user_id. " and section_name ='". $tool ."' AND ref_id = ".$group_id ,array());
                                if (!$res || db_numrows($res) < 1) {
                                   $data = 0;
                                }
                                $data = db_result($res,0,'perm_val');
								echo "perm_val: " . $data . "<br />";
							    if ($data > 2) {
								   // perm_val must be 2 or greater
								   $display_link = 1;
								}
								*/
								$perm_val1 = forge_check_perm ('datashare', $group_id, 'read_public');
								$perm_val2 = forge_check_perm ('datashare', $group_id, 'read_private');
								$perm_val3 = forge_check_perm ('datashare', $group_id, 'write');
								   //echo "val1: " . $perm_val1 . "<br />";
								   //echo "val2: " . $perm_val2 . "<br />";
								   //echo "val3: " . $perm_val3 . "<br />";
								if ($perm_val2) {
								   $display_link = 1;
								}
							 }
							 if ($display_link) {

								if ($result->active) {
								   echo "<form name=\"form$result->study_id\" action=\"view.php\" method=\"post\">";
								   echo "<input type=\"hidden\" name=\"id\" value=\"$group_id\">";
								   echo "<input type=\"hidden\" name=\"pluginname\" value=\"datashare\">";
								   echo "<input type=\"hidden\" name=\"studyid\" value=\"$result->study_id\">";
								   echo "</form>";
								   //echo "<tr><td><a class='btn-blue' href='view.php?id=$group_id&pluginname=datashare&studyid=$result->study_id'>$result->title</a></td>";
							       echo "<tr><td><a href=\"#\" onclick=\"document.forms['form$result->study_id'].submit();\" class='btn-blue'>$result->title</a></td>";

								} else {
								   echo "<tr><td>$result->title</td>";
								}
								echo "<td>$status</td><td>$result->description</td></tr>";
							 }
						  }
						  echo "</table>";
						} else {
						  echo "No current studies exist for this project<br /><br />";

						}

        echo "</div></div></div>"; // end of main_col

	}
	// Add side bar to show statistics and project leads.
	//constructSideBar($group);

	echo "</div></div>";

	site_project_footer(array());


// Construct side bar
function constructSideBar($groupObj) {

	if ($groupObj == null) {
		// Group object not available.
		return;
	}

	echo '<div class="side_bar">';

	// Statistics.
	displayStatsBlock($groupObj);

	// Get project leads.
	$project_admins = $groupObj->getLeads();
	displayCarouselProjectLeads($project_admins);

	echo '</div>';
}


// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
