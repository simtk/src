<?php
/**
 * project_home.php
 *
 * FusionForge Project Home
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2010, FusionForge Team
 * Copyright (C) 2011-2012 Alain Peyrat - Alcatel-Lucent
 * Copyright 2013, Franck Villaume - TrivialDev
 * Copyright 2016-2023, Henry Kwong, Tod Hing - SimTK Team
 * http://fusionforge.org
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

require_once $gfwww.'project/project_utils.php';
require_once $gfplugins.'simtk_news/www/simtk_news_utils.php';
require_once $gfwww.'include/trove.php';
require_once $gfwww.'include/project_summary.php';
require_once $gfwww.'include/forum_db_utils.php';
require_once $gfcommon.'include/tag_cloud.php';
require_once $gfcommon.'include/HTTPRequest.class.php';
require_once $gfcommon.'widget/WidgetLayoutManager.class.php';
require_once $gfplugins.'following/include/Following.class.php';
require_once $gfcommon.'frs/include/frs_utils.php';
require_once $gfcommon.'frs/FRSPackage.class.php';
require_once $gfplugins.'publications/include/Publications.class.php';
//require_once $gfcommon.'include/utils.php';

$request =& HTTPRequest::instance();
$request->set('group_id', $group_id);



// *** Disabled here. Otherwise, project description will not be displayed.
// and user login will be prompted.
//session_require_perm ('project_read', $group_id) ;

$title = _('Project Home');


$params['submenu'] = '';

html_use_jqueryui();
site_project_header(array('title'=>$title, 'h1' => '', 'group'=>$group_id, 'toptab' => 'home' ));

$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
        exit_no_group();
}

// get project leads 

$project_admins = $group->getLeads();

?>
<script type="text/javascript">
	$(function() {
		$('.expander').simpleexpand();
	});
</script>

<div class="project_overview_main">
    <div style="display: table; width: 100%;"> 
        <div class="main_col">

            <?php 
			      if ($group->isPublicationProject()) { 
				     $pub = New Publication($group);
					 if (!$pub || !is_object($pub)) {
                          exit_error('Error','Could Not Create Publication Object');
                     } elseif ($pub->isError()) {
                          exit_error('Error',$pub->getErrorMessage());
                     }
					 $res = $pub->getPrimary();
				     echo "<i>" . $res['publication'] . " " . " (" . $res['publication_year'] . ")</i>";
					
                     if ($res['abstract'] != "") {
					    echo "<div class=\"expand_content\">\n";
                        echo "<div id='pub".$res['pub_id']."'>";
					    echo "<a id=\"expander\" class=\"expander toggle\" href=\"#\">Abstract</a>";
						if ($res['url'] != "") {
                        echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='" . $res['url'] . "' target='_blank'>View</a>";			 
					    } 
						echo "<div class='content'><p>".$res['abstract']."</p></div></div></div><br />";
                     } else if ($res['url'] != "") {
                        echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='" . $res['url'] . "' target='_blank'>View</a>";			 
                        echo '<br /><br />';
					 } else {
					    echo '<br /><br />';
					 }
				  }
				  
			?>
				
            <?php 
/*
              $logofile =  $group->getLogoFile(); 
              if (!empty($logofile)) { 
                echo "<img class='project_icon' src='/logos/$logofile' alt='big logo'/>";
              } 
*/
            ?>

            <div class="project_description">
						
                <p><?php echo html_entity_decode(nl2br(util_whitelist_tags($group->getSummary()))); ?></p></div>

<?php if (forge_check_perm('project_read', $group_id)) { ?>
            <?php 
              $pulldown_menu = frs_download_files_pulldown($group, $group_id);
              if ($group->getDisplayDownloadPulldown() && !empty($pulldown_menu)) { ?>

            <div class="download_widget">
                <div class="dropdown">
                    <a class="btn-download dropdown-toggle" data-toggle="dropdown" >
                        Download Latest Releases
                        <span class="arrow_icon"></span>
                    </a>
                    <ul class="dropdown-menu" role="menu" >
                      <?php foreach ($pulldown_menu as $menu_items) { ?>
                        <li><a role="menuitem" tabindex="-1" href="<?php echo $menu_items['url']; ?>"><?php echo $menu_items['name']; ?></a></li>
                      <?php } ?>
                    </ul> 
                </div>
            </div>

                <?php $result_use_agreement = frs_package_use_agreement($group_id); ?>

            <?php } ?>
<?php } ?>

            <div class="project_section">
		<hr/>
                <p>
                <?php echo html_entity_decode(nl2br(util_make_clickable_links(util_whitelist_tags($group->getDescription())))); ?>
                </p>
            </div>
            <div style="clear: both;"></div> 
        </div>
        <div class="side_bar">

           <?php if (forge_check_perm('project_read', $group_id)) displayStatsBlock($group); ?>
           <?php displayCarouselProjectLeads($project_admins); ?>

        </div> <!-- /.side-bar -->
    </div>
</div>
<?php
if($group->getDisplayFunderInfo()){
  $funders = $group->getFundersInfo();
  $funders_count = 0;
  if($funders){
    $funders_count = count($funders);
  }
  echo '<div class="three_cols">';
  echo '<h2>Funder Information </h2>';
  if($funders_count > 0){
      echo '<div style="margin: 24px 0;">';
      echo 'This project is funded by ' . build_funder_info($funders[0]);
      for($i = 1; $i < $funders_count; $i++){
          echo ", " .  build_funder_info($funders[$i]);
        }
      echo '.</div>';

      echo '<div>';
      foreach($funders as $funder){
          if($funder['funder_description']){
              echo '<p> ' . $funder['funder_description'] . '</p>';
          }
      }
      echo '</div>';

  }else{
      echo '<p>No funder information has been added for this project.';
  }
  echo '</div>';
}


?>


<?php if (forge_check_perm('project_read', $group_id)) { ?>
   <?php 
     $displayDownloads = false;
     // Only show Downloads section if the description is present.
     $strDownloadDescription = $group->getDownloadDescription();
     if ($group->getDisplayDownloads() &&
	$strDownloadDescription != null && trim($strDownloadDescription) != "") {
	$displayDownloads = true;
     }

     $displayRelated = false;
     if ($group->getDisplayRelated()) {
	$related_projects = $group->getRelatedProjects();
	if (db_numrows($related_projects) > 0) {
		$displayRelated = true;
	}
     }

     $num_columns = 0;
	 //echo "display: " . $group->getDisplayNews() . "<br />";
	 //echo "simtk news: " . $group->usesPlugin("simtk_news") . "<br />";
     if ($group->usesPlugin("simtk_news") && $group->getDisplayNews() && ($news_return = news_show_project_overview($group_id))) {    
	 $num_columns += 1;
     }
     if ($group->usesFRS() && $displayDownloads === true) {
        $num_columns += 1;
     }
     if ($displayRelated === true) {
        $num_columns += 1;
     }

   if ($num_columns) { ?>

   <div class="three_cols">

    <?php 
	  if ($group->usesPlugin("simtk_news") && $group->getDisplayNews() && $news_return) {
        if ($num_columns == 1) {
          echo "<div>";
        } else if ($num_columns == 2) {
          // Let news use a wider column.
          echo "<div class='two_third_col'>";
        } else if ($num_columns == 3) {
          echo "<div class='one_third_col'>";
        }
    ?>
        <div class="module_news">
            <h2 id="news">News</h2>
			<p>
            <?php echo news_show_project_overview($group_id); ?>
            </p>
			<a href="/plugins/simtk_news/?group_id=<?php echo $group_id; ?>"> See all News</a>
        </div>
    </div>
    <?php } ?>

    <?php 
      if ($group->usesFRS() && $displayDownloads === true) { 
        if ($num_columns == 1) {
          echo "<div>";
        } else if ($num_columns == 2 && $group->usesPlugin("simtk_news") && $group->getDisplayNews() && $news_return) {
          echo "<div class='one_third_col'>";
        } else if ($num_columns == 2) {
          echo "<div class='two_third_col'>";
        } else if ($num_columns == 3) {
          echo "<div class='one_third_col'>";
        }
    ?>
    <?php 
/*
	// Only show Downloads section if the description is present.
	$strDownloadDescription = $group->getDownloadDescription();
	if ($strDownloadDescription != null && trim($strDownloadDescription) != "") {
*/
	if ($displayDownloads === true) {
    ?>
        <div class="module_downloads">
            <h2>Downloads</h2>
            <p>
            <?php echo html_entity_decode(nl2br(util_make_clickable_links(util_whitelist_tags($group->getDownloadDescription())))); ?>
            </p>
            <a href="/frs/?group_id=<?php echo $group_id; ?>"> See all Downloads</a>
        </div>
    <?php 
	}
    ?>
    </div>
    <?php } ?>

    <?php 
      if ($displayRelated === true) { 
        if ($num_columns == 1) {
          echo "<div>";
        } else {
          echo "<div class='one_third_col'>";
        }
    ?>
        <div class="module_recommeded">
            <?php
            displayRelatedProjects($related_projects);
            ?>
        </div>
    </div>
    <?php } ?>

   </div> 

   <?php } ?>


<?php } ?>

<?php

function build_funder_info($funder_info){
    $funder_info_string = $funder_info['funder_name'] .
    ' ' . $funder_info['award_number']  . ' ' . $funder_info['award_title'];
    return trim($funder_info_string);
}

$rec_projects = $group->getRecommendedProjects(15);
displayRecommendedProjects($group, $rec_projects);


site_project_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
