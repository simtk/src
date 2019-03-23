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


	$group_id = getStringFromRequest('id');
	$pluginname = getStringFromRequest('pluginname');
	if (!isset($pluginname) || $pluginname == null || trim($pluginname) == "") {
		// Set a default.
		$pluginname = 'datashare';
	}

	$studyid = getStringFromRequest('studyid');

	if (!$group_id || !$studyid) {
		exit_error("Cannot Process your request","No ID specified");
	} else {

			$group = group_get_object($group_id);
			if ( !$group) {
				exit_error("Invalid Project", "Inexistent Project");
			}

			if ( ! ($group->usesPlugin ( $pluginname )) ) {//check if the group has the Data Share plugin active
				exit_error("Error", "First activate the $pluginname plugin through the Project's Admin Interface");
			}
			// get study
			// get current studies
			$study = new Datashare($group_id);

			if (!$study || !is_object($study)) {
	           exit_error('Error','Could Not Create Study Object');
            } elseif ($study->isError()) {
	           exit_error($study->getErrorMessage(), 'Datashare Error');
            }

			$study_result = $study->getStudy($studyid);

			// other perms checks here...
			$study_title = "Data Share:" . $study_result[0]->title;
			datashare_header(array('title'=>'Data Share:'),$group_id);
            //datashare_header(array('title'=>$study_title),$group_id);
			echo "<h4>&nbsp; " . $study_result[0]->title . "</h4>";
			?>


            <?php

            echo "<div class=\"project_overview_main\">\n";
            echo "<div style=\"display: table; width: 100%;\">\n";
            echo "<div class=\"main_col\">\n";
            if ($study_result) {
			 $token = $study_result[0]->token;
			 $private = $study_result[0]->is_private;
			 $userid = 0;
			 $add_date = 0;
			 $display_study = 0;
			 $member = 0;
			 if (session_loggedin()) {
				   // get user
                   $user = session_get_user(); // get the session user
                   $userid = $user->getID();
				   $add_date = $user->getAddDate();
				   if (user_ismember($group_id)) {
				      $member = 1;
				   }
			 }
	     if ($study_result[0]->is_private == 0) {
				// public
				$display_study = 1;
			 }
			 elseif ($study_result[0]->is_private == 1) {
			    // check if registered user
				// Check permission and prompt for login if needed.
                //session_require_perm('project_read', $group_id);
				if ($userid) {
				   $display_study = 1;
				} else {
				   //echo "Must be registered User.  Please log in";
				   header('Location: index.php?group_id='.$group_id.'&login=1');
				   exit;
				   //session_require_perm('datashare', $group_id, 'read_public');
				}
			 }
			 elseif ($study_result[0]->is_private == 2) {
			    // check if member
				if ($userid) {
	         if (user_ismember($group_id) || forge_check_global_perm('forge_admin')) {
				      $display_study = 1;
				   } else {
				      echo "Must be Member of Project";
				   }
				} else {
				   echo "Private Study.  Please log in";
				}
			 }
			 if ($display_study) {
			    echo "<form id='fname' action='http://simtkdata-stage1.stanford.edu' method='post' target='my_iframe'>";
				echo "<input type='hidden' name='section' value='datashare'>";
				echo "<input type='hidden' name='groupid' value='$group_id'>";
				echo "<input type='hidden' name='userid' value='$userid'>";
				echo "<input type='hidden' name='groupid' value='$group_id'>";
				echo "<input type='hidden' name='studyid' value='$studyid'>";
				echo "<input type='hidden' name='token' value='$token'>";
				echo "<input type='hidden' name='private' value='$private'>";
				echo "<input type='hidden' name='member' value='$member'>";
				echo "<input type='hidden' name='add_date' value='$add_date'>";
                echo "</form>";
			    echo "<iframe src=\"https://simtkdata-stage1.stanford.edu/?section=datashare&groupid=$group_id&userid=$userid&studyid=$studyid&token=$token&private=$private&member=$member&add_date=$add_date\" frameborder=\"0\" scrolling=\"yes\"  height=\"1000\" width=\"1000\" align=\"left\"></iframe>";

                //echo "<iframe name=\"my_iframe\" src=\"#\" frameborder=\"1\" scrolling=\"yes\"  height=\"1000\" width=\"1000\" align=\"left\" style=\"border:0\"></iframe>";
			    ?>
<!---
				<script>
                $(document).ready(function(){
                var loginform= document.getElementById("fname");
                loginform.style.display = "none";
                loginform.submit();
                });
                </script>
--->
                <?php
			 }
			} else {
			  echo "Error getting study";
			}


        echo "</div></div></div>"; // end of main_col

	}

	echo "</div></div>";

	site_project_footer(array());


// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
