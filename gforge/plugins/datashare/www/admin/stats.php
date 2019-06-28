<?php
/**
 *
 * index.php
 *
 * Main admin index page for creating new study.
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

require_once '../../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once '../datashare-utils.php';
require_once $gfplugins.'datashare/include/Datashare.class.php';


$group_id = getIntFromRequest('group_id');
if (!$group_id) {
	exit_no_group();
}
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(), 'datashare');
}

$study_id = getIntFromRequest('study_id');
$typeid = getIntFromRequest('typeid');
$pluginname="datashare";
datashare_header(array('title'=>'Datashare','pagename'=>"$pluginname",'sectionvals'=>array(group_getname($group_id))),$group_id);

?>
<link href = "https://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css"
         rel = "stylesheet">
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
  $( function() {
    $( "#datepickerfrom" ).datepicker();
  } );
  $( function() {
    $( "#datepickerto" ).datepicker();
  } );
  </script>
<?php

echo "\n";
echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n";
echo "<div class=\"main_col\">\n";

if (session_loggedin()) {

	//if (!forge_check_perm('pubs', $group_id, 'project_admin')) {
	if (!forge_check_perm ('datashare', $group_id, 'write')) {
		//exit_permission_denied(_('You cannot access the datashare admin section for a project unless you are an admin on that project.'), 'home');
		exit_error("Access Denied: You cannot access the datashare admin section for a project unless you are an admin on that project", 'datashare');
	}


			$userperm = $group->getPermission();//we'll check if the user belongs to the group (optional)
			if ( !$userperm->IsMember()) {
				exit_error("Access Denied", "You are not a member of this project");
			}
                        echo "<a class=\"btn-blue\" href=\"stats.php?group_id=$group_id&study_id=$study_id&typeid=1\" style=\"width:158px;\">Query Report</a>";
                        echo "&nbsp;<a class=\"btn-blue\" href=\"stats.php?group_id=$group_id&study_id=$study_id&typeid=2\" style=\"width:165px;\">Downloads Report</a>";
echo "<br />";
echo "<form action=\"stats.php\">";
echo "<div class=\"form_simtk\">";
echo "<input type=\"hidden\" name=\"group_id\" value=\"$group_id\">";
echo "<input type=\"hidden\" name=\"study_id\" value=\"$study_id\">";
echo "<input type=\"hidden\" name=\"typeid\" value=\"$typeid\">";
echo "Date Range: <input type=\"radio\" name=\"all\" value=\"1\"> All or From: <input type=\"text\" id=\"datepickerfrom\" name=\"datefrom\">";
echo " To: <input type=\"text\" id=\"datepickerto\" name=\"dateto\">";
echo " <input type=\"submit\" class=\"btn-cta btn-sm\" name=\"submit\">";
echo "</div>";
echo "</form>";

                        if ($typeid == 1) {
                           echo "<h4>Query Report</h4>";
                           $th = "Query";
                        } else {
                           echo "<h4>Downloads Report</h4>";
                           $th = "Query";
                        }

                        $date_error = 0;
                        include 'server.php';
                        if (!empty($_REQUEST['datefrom']) && !empty($_REQUEST['dateto'])) {
                           $datefrom = $_REQUEST['datefrom']; 
                           $dateto = $_REQUEST['dateto']; 
                           if (date("Ymd",strtotime($datefrom)) > date("Ymd",strtotime($dateto))) {
                             $date_error = 1;
                           } else {
                             $url = "https://$domain_name/reports/getStats.php?apikey=$api_key&studyid=$study_id&typeid=$typeid&datefrom=$datefrom&dateto=$dateto";
                             echo "Date Range Selected: $datefrom to $dateto";
                           }
                        } else {
                           $url = "https://$domain_name/reports/getStats.php?apikey=$api_key&studyid=$study_id&typeid=$typeid&date_all=1";
                           echo "Date Range Selected: ALL";
                        }
                        echo "<br />"; 
                        if ($date_error) {
                           echo "The <b>From</b> Date can't be past the <b>To</b> date, From: $datefrom  To: $dateto"; 
                        } else {
                           //echo "url = $url<br /><br />";
                           $response_json = file_get_contents($url);
                           $response = json_decode($response_json);
                           //var_dump($response) . "<br />";
                       
                           echo "<table class=\"table\">";
                           echo "<tr><th>Date</th><th>First Name</th><th>Last Name</th><th>Email</th><th>$th</th></tr>";
                           if ($response) {
                            foreach($response as $obj){
                              $info = urldecode($obj->info);
                              //$info = preg_replace('/select.*where/i','',$info);
                              //$info = preg_replace('/\(json.*::/i','',$info);
                              //$info = urldecode($info);
                              //$info = str_replace("(%)","",$info);
                              //$info = str_replace("select%where","",$info);
                              echo "<tr>";
                              echo "<td nowrap>".date("Y-m-d",strtotime($obj->dateentered))."</td>";
                              echo "<td>".$obj->firstname."</td>";
                              echo "<td>".$obj->lastname."</td>";
                              echo "<td>".$obj->email."</td>";
                              echo "<td>".$info."</td>";
                              echo "</tr>";
                            }
                           } else {
                              echo "<tr><td colspan=5>No Data Found</td></tr>";
                           }

                        echo "</table>";
                        }


}

echo "</div><!--main_col-->\n</div><!--display table-->\n</div><!--project_overview_main-->\n";

datashare_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
