<?php
/**
 *
 * news plugin admin/index.php
 * 
 * Admin view for status of homepage display or main simtk site display of project news.
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
//require_once $gfwww.'include/note.php';
require_once 'news_admin_utils.php';
require_once '../simtk_news_utils.php';
//common forum tools which are used during the creation/editing of news items
//require_once $gfcommon.'forum/Forum.class.php';
require_once $gfcommon.'include/TextSanitizer.class.php'; // to make the HTML input by the user safe to store
require_once $gfplugins.'simtk_news/include/Simtk_news.class.php';
require_once $gfwww.'project/project_utils.php';

$group_id = getIntFromRequest('group_id');
$group = group_get_object($group_id);


// If this was a submission from admin index page, make updates
if (getStringFromRequest('submit')) {
   $simtk_news = new Simtk_news($group);
   if (!$simtk_news || !is_object($simtk_news)) {
       exit_error('Error','Could Not Create SimTK News');
   } elseif ($simtk_news->isError()) {
       exit_error('Error',$simtk_news->getErrorMessage());
   }
   
   $result=db_query_params("SELECT * FROM plugin_simtk_news WHERE is_approved <> 4 AND group_id=$1", array($group_id));
   $rows=db_numrows($result);
   for ($i=0; $i<$rows; $i++) {
      $frontpage = "display_frontpage" . db_result($result,$i,'id');
      $simtk_sidebar_display = getStringFromRequest($frontpage);
	  if ($simtk_sidebar_display == "t") {
	    $simtk_sidebar_display = "TRUE";
	  } else {
	    $simtk_sidebar_display = "FALSE";
	  }
      $res = $simtk_news->updateDisplayFrontpage(db_result($result,$i,'id'),$simtk_sidebar_display);
   }
   
   $notification_sent = 0;
   for ($i=0; $i<$rows; $i++) {
      $global = "display_global" . db_result($result,$i,'id');
	  $is_approved = db_result($result,$i,'is_approved');
      $simtk_request_global = getStringFromRequest($global);
	  if ($simtk_request_global == "t") {
	    $simtk_request_global = 1;
	  } else {
	    $simtk_request_global = 0;
		$res1 = $simtk_news->updateDisplayGlobal(db_result($result,$i,'id'),$simtk_request_global);
	  }
	  $res = $simtk_news->updateRequestGlobal(db_result($result,$i,'id'),$simtk_request_global);
	  
	  if ($is_approved != 1 && $simtk_request_global && !$notification_sent) {
	     $simtk_news->sendDisplayNotificationEmail();
		 $notification_sent = 1; //only need to send this once even if more than 1 news pending. 
	  } 
   }
	
}


$post_changes = getStringFromRequest('post_changes');
$approve = getStringFromRequest('approve');
$status = getIntFromRequest('status');
$summary = getStringFromRequest('summary');
$details = getHtmlTextFromRequest('details');
$id = getIntFromRequest('id');
$for_group = getIntFromRequest('for_group');

if ($group_id && $group_id != forge_get_config('news_group')) {
	session_require_perm ('project_admin', $group_id) ;

	$status = getIntFromRequest('status');
	$summary = getStringFromRequest('summary');
	$details = getStringFromRequest('details');

	/*

		Per-project admin pages.

		Shows their own news items so they can edit/update.

		If their news is on the homepage, and they edit, it is removed from
			sf.net homepage.

	*/
	if ($post_changes) {
		$result = db_query_params("SELECT forum_id FROM plugin_simtk_news WHERE id=$1 AND group_id=$2", array($id, $group_id));
		if (db_numrows($result) < 1) {
			exit_error(_('Newsbyte not found'),'news');
		}

		$forum_id = db_result($result,0,'forum_id');
		$old_group_id = db_result($result,0,'group_id');

		if ($approve) {
			/*
				Update the db so the item shows on the home page
			*/
			if ($status != 0 && $status != 4) {
				//may have tampered with HTML to get their item on the home page
				// 0 = default when created.  Show on project home page?
				// 1 = approved for global page
				// 2 = rejected
				// 3 = ?
				// 4 = ?
				$status=0;
			}

			if (!$summary) {
				$summary='(none)';
			}
			if (!$details) {
				$details='(none)';
			}

			$result = db_query_params("UPDATE plugin_simtk_news SET is_approved=$1, summary=$2,
details=$3, simtk_request_global=$6 WHERE id=$4 AND group_id=$5", array($status, htmlspecialchars($summary), $details, $id, $group_id, "FALSE"));

			if (!$result || db_affected_rows($result) < 1) {
				$error_msg .= sprintf(_('Error On Update: %s'), db_error());
			} else {
				$feedback .= _('Newsbyte Updated');
			}
			/*
				Show the list_queue
			*/
			$approve='';
			$list_queue='y';
		}
	}

	news_header(array('title'=>_('News')),$group_id);
	
	echo "<div class=\"project_overview_main\">";
    echo "<div style=\"display: table; width: 100%;\">";
    echo "<div class=\"main_col\">";
	
	//echo $HTML->beginSubMenu();
	//echo $HTML->printSubMenu($subMenuTitle, $subMenuUrl, array());
	//echo $HTML->endSubMenu();
			
	if ($approve) {
		/*
			Show the submit form
		*/

		$result=db_query_params("SELECT * FROM plugin_simtk_news WHERE id=$1 AND group_id=$2", array($id, $group_id));
		if (db_numrows($result) < 1) {
			exit_error(_('Newsbyte not found'),'news');
		}

		//$group = group_get_object($group_id);

		//echo notepad_func();
		echo '
		<form id="newsadminform" action="'.getStringFromServer('PHP_SELF').'" method="post">
		<div class="form_simtk">
		<input type="hidden" name="group_id" value="'.db_result($result,0,'group_id').'" />
		<input type="hidden" name="id" value="'.db_result($result,0,'id').'" />';

		$user = user_get_object(db_result($result,0,'submitted_by'));

		echo '
		<strong>'._('Submitted by')._(':').'</strong> '.$user->getRealName().'<br />
		<input type="hidden" name="approve" value="y" />
		<input type="hidden" name="post_changes" value="y" />

		<strong>'._('Status').'</strong><br />
		<p><input type="radio" name="status" value="0" checked="checked" /> '._('Displayed').'</p>
		<p><input type="radio" name="status" value="4" /> '._('Delete').'</p>

		<strong>'._('Subject').'</strong><br />
		<input type="text" name="summary" value="'.db_result($result,0,'summary').'" size="50" /><br />
		<strong>'._('Details').'</strong>:<br />';

		$params = array () ;
		$params['name'] = 'details';
		$params['width'] = "600";
		$params['height'] = "300";
		$params['group'] = $group_id;
		$params['body'] = db_result($result,0,'details');
		$params['content'] = '<textarea name="details" rows="5" cols="50">'.$params['body'].'</textarea>';
		//plugin_hook_by_reference("text_editor",$params);

		echo $params['content'].'<br/>';
		echo '<p>
		<strong>'.sprintf(_('If this item is on the %s home page and you edit it, it will be removed from the home page.'), forge_get_config('forge_name')).'</strong></p>
		<input type="submit" name="update" value="'._('Submit').'" class="btn-cta" />
		</div>
		</form>';

	} else {
		/*
			Show list of waiting news items
		*/
		// Show in DESC order.
		$result=db_query_params("SELECT * FROM plugin_simtk_news " .
			"WHERE is_approved <> 4 AND group_id=$1 order by id DESC", 
			array($group_id));
		$rows=db_numrows($result);
		$group = group_get_object($group_id);

		if ($rows < 1) {
			echo '<p class="information">'._('No Queued Items Found').'</p>';
		} else {
            echo "<form id=\"simtknewsadminform\" action=\"".getStringFromServer('PHP_SELF'). "\" method=\"post\">";
			echo "<p>Click on news item to edit or delete a news item</p>";
			echo "<input type=\"hidden\" name=\"group_id\" value=\"$group_id\">";
			echo '<div class="table-responsive">';
			echo '<table class="table table-condensed table-bordered table-striped">';
			echo '<tr><th>Title</th><th>Display on Project<br /> Homepage</th><th>Display in<br /> SimTK Site News</th><th>SimTK Site News Status</th></tr>';
			for ($i=0; $i<$rows; $i++) {
                                echo "<tr>";
				echo '
				<td>'.util_make_link ('/plugins/simtk_news/admin/?approve=1&amp;id='.db_result($result,$i,'id').'&amp;group_id='.db_result($result,$i,'group_id'),db_result($result,$i,'summary')).'</td>';
                                echo "<td align=\"center\"><input type=\"checkbox\" name=\"display_frontpage".db_result($result,$i,'id'). "\" value=\"t\" ";
                                if (db_result($result,$i,'simtk_sidebar_display') == "t") {
                                   echo "checked></td>";
                                } else {
                                   echo "></td>";
                                }
                                echo "<td align=\"center\"><input type=\"checkbox\" name=\"display_global".db_result($result,$i,'id'). "\" value=\"t\" ";
				
				// Find approved front page news items and pending news items.
				// NOTE: After approval, front page news item has 1 for 'is_approved'.
				if (db_result($result,$i,'simtk_request_global') == "t" ||
					db_result($result,$i,'is_approved') == 1) {
					echo "checked></td>";
				}
				else if (!$group->isPublic()) {
				    echo "disabled=\"disabled\" ></td>";
				}
				else {
					echo "></td>";
				}
				// Get approval status.
				$is_approved_display = "";
				$is_approved = db_result($result,$i,'is_approved');
				if ($is_approved == 1) {
					$is_approved_display = "Approved";
				}
				else if ($is_approved == 2) {
					$is_approved_display = "Rejected";
				}
				else {
					// Waiting in queue for global display.
					if (db_result($result,$i,'simtk_request_global') == "t") {
						if ($is_approved == 0) {
							$is_approved_display = "Pending";
						}
					}
				}
				echo "<td>" . $is_approved_display . "</td>";
                                echo "</tr>";
			}
			echo '</table></div><br />';
			if (!$group->isPublic()) {
				    echo "Note: Private projects cannot have news displayed on the main SimTK site. <br />";
			}
            echo '<div><p><input type="submit" name="submit" value="'._('Update').'" class="btn-cta" /></p></div></form>';
		}

	}
	
	// main_col.
	echo "</div>";

	// Add side bar to show statistics and project leads.
	constructSideBar($group);

	// display table.
	echo "</div>";
	// project_overview_main.
	echo "</div>";
	news_footer(array());

} else { // No group, or newsadmin group
	session_redirect('/admin/pending-news.php');
}

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
