<?php

/**
 *
 * related_projects.php
 * 
 * File for setting up related projects.
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
require_once $gfwww.'project/admin/project_admin_utils.php';

$group_id = getIntFromRequest('group_id');

session_require_perm ('project_admin', $group_id) ;

// get current information
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'admin');
}

$group->clearError();

// Get a list of files associated with this release
$strProjectsQuery = "SELECT * from groups where simtk_is_public = 1 and status = 'A' order by group_name";
$result_projects = db_query_params($strProjectsQuery, array());
$numRowsProjects = db_numrows($result_projects);
	
	


// If this was a submission, make updates
if (getStringFromRequest('submit')) {
	
	$header_order = getStringFromRequest('header_order');
	$res = $group->updateRelatedProjects($header_order);

	if (!$res) {
		$error_msg .= $group->getErrorMessage();
	} else {
		$feedback .= _('Related Projects Updated');
	}
    
	
}

// get related projects after the submit code
$res = $group->getRelatedProjects();
$numRows = db_numrows($res);
//$row = db_fetch_array($related_projects);
//var_dump($row);

project_admin_header(array('title'=>'Admin','group'=>$group->getID()));

?>

  <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
  <script src="//code.jquery.com/jquery-1.10.2.js"></script>
  <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
  
  <style>
  .sortable1, .sortable2 {
    border: 1px solid #eee;
    width: 400px;
    min-height: 20px;
    list-style-type: none;
    margin: 0;
    padding: 5px 0 0 0;
    float: left;
    margin-right: 10px;
  }
  .sortable1 li, .sortable2 li {
    margin: 5px 5px 5px;
    padding: 5px;
    font-size: 0.9em;
    width: 387px;
  }
  .sortable1 li span, .sortable2 li span {
	position: absolute;
	margin-left: 0em;
  }
  .sortable1 li label, .sortable2 li label {
	margin-left: 1.0em;
  }
  .sortable1 ul {
    max-height: 250px;
	overflow-y: auto;
    overflow-x: hidden;
  }
  .sortable1 ul label, .sortable2 ul label {
  margin-left: 0.9em;
  }
  #sortbox1{height:320px; overflow-y:scroll; overflow-x:none; padding: 10px 10px 10px 10px;position:relative;float:left;}
  #sortbox2{height:320px; overflow-y:scroll; overflow-x:none; padding: 10px 10px 10px 10px;position:relative;float:left;}
  #sortbox1 label, #sortbox2 label {
  margin-left: 0.9em;
  }
  </style>
  <script>
  $(function() {
    $( ".sortable1" ).sortable({
      connectWith: ".sortable2",
	  scroll: true,
    }).disableSelection();
  });
  
  $(function() {
     // Initialization.
      var data = "";
      $(".sortable2 li").each(function(i, el) {
          var groupId = $(el).attr("id");
          data += groupId + "=" + $(el).index() + ",";
      });

      $("#header_order").val(data.slice(0, -1));
				
      $( ".sortable2" ).sortable({
      connectWith: ".sortable1",
	  stop: function(event, ui) {
				// Get order of elements in each sortable
				// identified by group ids.
				var data = "";
				$(".sortable2 li").each(function(i, el) {
					var groupId = $(el).attr("id");
					data += groupId + "=" + $(el).index() + ",";
				});

				$("#header_order").val(data.slice(0, -1));
				
			},	 
       update: function(event, ui) {
				// Get order of elements in each sortable
				// identified by group ids.
				var data = "";
				$(".sortable2 li").each(function(i, el) {
					var groupId = $(el).attr("id");
					data += groupId + "=" + $(el).index() + ",";
				});

				$("#header_order").val(data.slice(0, -1));
				
			}				
    });
	$(".sortable2").disableSelection();
	
	$(".sortable2 .delete").on('click', function(e){
          $(this).parent().remove();
		  
		  var data = "";
				$(".sortable2 li").each(function(i, el) {
					var groupId = $(el).attr("id");
					data += groupId + "=" + $(el).index() + ",";
				});

				$("#header_order").val(data.slice(0, -1));
    });
	
  });
  
  </script>

  <h3>Edit Related Projects</h3>
  
<form action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="post" >

<div>
  
  <div id="sortbox1">
  <ul class="sortable1"><label>All Projects</label>
    <?php
     for ($i = 0; $i < $numRowsProjects; $i++) {
        echo '<li id="'. db_result($result_projects, $i, 'group_id') . '" class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span><label>' . db_result($result_projects, $i, 'group_name') . '</label></li>';
     }
	?>
  </ul>
  </div>
  
  <div id="sortbox2">
  <ul class="sortable2"><label>Related Projects to Display</label>
    <?php
     for ($i = 0; $i < $numRows; $i++) {
        echo '<li id="'. db_result($res, $i, 'related_group') . '" class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span><label>' . db_result($res, $i, 'group_name') . '</label> <img src="/themes/simtk/images/list-remove.png" class="delete"></li>';
     }
    ?>
  </ul>
  </div>


<input type="hidden" id="header_order" name="header_order" value="<?php echo $header_order; ?>" />
<input type="hidden" name="group_id" value="<?php echo $group->getID(); ?>" />

<input style='margin-top:5px;' class='btn-cta' type="submit" name="submit" value="<?php echo _('Update') ?>" />

</div>

</form>

<?php

project_admin_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
