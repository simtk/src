<?php

/**
 *
 * publications plugin index.php
 *
 * Main index page which displays primary publications and related publications.
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

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once 'publications-utils.php';
require_once $gfplugins.'publications/include/Publications.class.php';
require_once $gfwww.'project/project_utils.php';

	
	$id = getStringFromRequest('id');
	if (!isset($id) || $id == null || trim($id) == "") {
		// Try group_id.
		$id = getStringFromRequest('group_id');
	}
	$pluginname = getStringFromRequest('pluginname');
	if (!isset($pluginname) || $pluginname == null || trim($pluginname) == "") {
		// Set a default.
		$pluginname = 'publications';
	}

	if (!$id) {
		exit_error("Cannot Process your request","No ID specified");
	} else {

			$group = group_get_object($id);
			if ( !$group) {
				exit_error("Invalid Project", "Inexistent Project");
			}

			if ( ! ($group->usesPlugin ( $pluginname )) ) {//check if the group has the Publications plugin active
				exit_error("Error", "First activate the $pluginname plugin through the Project's Admin Interface");
			}
			
			// other perms checks here...
			publications_Project_Header(array('title'=>'Publications','pagename'=>"$pluginname",'sectionvals'=>array(group_getname($id))),$id);
			// DO THE STUFF FOR THE PROJECT PART HERE

			?>


            <script type="text/javascript">
	        $(function() {
		      $('.expander').simpleexpand();
	        });
            </script>

            <?php

            echo "<div class=\"project_overview_main\">\n";
            echo "<div style=\"display: table; width: 100%;\">\n"; 
            echo "<div class=\"main_col\">\n";

			// Create submenu under project_overview_main DIV, such that it does not
			// occupy the whole width of the page (rather than using the 
			// submenu population in Theme.class.php)
			/*
			$subMenuTitle = array();
			$subMenuUrl = array();
			$subMenuTitle[] = _('View Publications');
			$subMenuUrl[] = '/plugins/publications/?pluginname=publications&id=' . $id;
			if (session_loggedin()) {
				$project = false;
				$project = group_get_object($id);
				if ($project && is_object($project) && !$project->isError()) {
				    // Check permission before allowing posting.
	                if (forge_check_perm ('project_admin', $id) || forge_check_perm ('project_read', $id)) {
						$subMenuTitle[]=_('Add Publications');
						$subMenuUrl[]='/plugins/publications/admin/add.php?group_id=' . $id;
					}
					// Check permission before adding administrative menu items.
					if (forge_check_perm ('project_admin', $id)) {
						$subMenuTitle[]=_('Administration');
						$subMenuUrl[]='/plugins/publications/admin/?group_id=' . $id;
					}
				}
			}
			*/
			// Show the submenu.
			//echo $HTML->beginSubMenu();
			//echo $HTML->printSubMenu($subMenuTitle, $subMenuUrl, array());
			//echo $HTML->endSubMenu();
			
			
			echo "<div class=\"expand_content\">\n";

                        $pub = new Publication($group);
                        if (!$pub || !is_object($pub)) {
                          exit_error('Error','Could Not Create Publication');
                        } elseif ($pub->isError()) {
                          exit_error('Error',$pub->getErrorMessage());
                        }

                        $result = $pub->getPublications();

                        if (!$result) {
                          echo '<p class="information">'._('No Publications Found').'</p';
                        }

                        $i = 0;
                        $primary_exist = 0;
						echo '<table class="table">';
                        foreach ($result as $result_list) {
                          if ($result_list->is_primary) {
                            echo '<tr><th>Primary Publication</th></tr>';
                            $primary_exist = 1;
                          }
                          if ($i == 1 && $primary_exist) {
                            echo '<tr><th>Related Publications</th></tr>';
                          }
                          echo '<tr><td>';
                          echo $result_list->publication . " ";
                          echo ' (' . $result_list->publication_year . ')';
                          if ($result_list->url != "") {
                            echo "&nbsp;&nbsp;<a href='$result_list->url' target='_blank'>View</a>";
                          }
                          if ($result_list->abstract != "") {
                            //echo "<br /><button type=\"button\" class=\"btn btn-link\" data-toggle=\"collapse\" data-target=\"#pub$result_list->pub_id\">";
                            //echo "ABSTRACT";
                            //echo "</button>";
                            //echo "<div id=\"pub$result_list->pub_id\" class=\"collapse\">$result_list->abstract</div>";
							echo "<div id=\"pub$result_list->pub_id\">";
							echo "<a id=\"expander\" class=\"expander toggle\" href=\"#\">Abstract</a>";
							echo "<div class=\"content\"><p>$result_list->abstract</p></div></div>";
                          }
                          echo '</td></tr>';
                          $i++;
                        }
                        echo "</table>";

		
	}

        echo "</div></div>"; // end of main_col

	// Add side bar to show statistics and project leads.
	constructSideBar($group);

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
