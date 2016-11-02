<?php
/**
 * Project Statistics Page
 *
 * Copyright 2003 GForge, LLC
 * Copyright 2010 (c) Franck Villaume - Capgemini
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2012-2013, Franck Villaume - TrivialDev
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

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'reporting/report_utils.php';
require_once $gfcommon.'reporting/Report.class.php';
require_once $gfcommon.'reporting/ReportProjectAct.class.php';
require_once $gfwww.'project/admin/project_admin_utils.php';
require_once $gfwww.'project/project_utils.php';

require_once $gfcommon.'frs/include/frs_utils.php';
require_once $gfcommon.'reporting/ReportDownloads.class.php';

$group_id = getIntFromRequest('group_id');
if (!$group_id) {
	exit_no_group();
}

//session_require_perm('project_admin', $group_id);

$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'admin');
}

$report=new Report();
if ($report->isError()) {
	exit_error($report->getErrorMessage(),'admin');
}

$area = getStringFromRequest('area');
$SPAN = getIntFromRequest('SPAN', REPORT_TYPE_MONTHLY);
$start = getIntFromRequest('start');
$end = getIntFromRequest('end');

$package_id = getIntFromRequest('package_id');
$start_frs = getIntFromRequest('start_frs');
$end_frs = getIntFromRequest('end_frs');

$project_report = getIntFromRequest('project_report');
$download_report = getIntFromRequest('download_report');

/*
 * Set the start date to birth of the project.
 */
$res = db_query_params('SELECT register_time FROM groups WHERE group_id=$1', array($group_id));
$report->site_start_date = db_result($res,0,'register_time');

if (!$start || !$end) $z =& $report->getMonthStartArr();

if (!$start) {
	$start = $z[0];
}
if (!$end) {
	$end = $z[count($z)-1];
}
if ($end < $start) list($start, $end) = array($end, $start);

// Find a default SPAN value depending on the number of days.
$delta=($end - $start)/24/60/60;
if (!$SPAN) {
	$SPAN = 1;
	if ($delta > 60) $SPAN=2;
	if ($delta > 365) $SPAN=3;
}

if ($SPAN && !is_numeric($SPAN)) { $SPAN = 1; }
if ($start && !is_numeric($start)) { $start = false; }
if ($end && !is_numeric($end)) { $end = false; }

/* Always show stats - comment this out
if (!$group->usesStats()) {
	exit_disabled('activity');
}
*/

html_use_jqueryjqplotpluginCanvas();
html_use_jqueryjqplotpluginhighlighter();
html_use_jqueryjqplotplugindateAxisRenderer();
html_use_jqueryjqplotpluginBar();

//project_admin_header(array('title'=>sprintf(_('Project Statistics for %s'), $group->getPublicName()),'group'=>$group_id));
$params['toptab']='Project Statistics';
$params['group'] = $group_id;
$params['titleurl'] = '/project/stats/index.php?group_id='.$group_id;
site_project_header($params);

//
// BEGIN PAGE CONTENT CODE
//

echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n"; 
echo "<div class=\"main_col\">\n";

// Create submenu under downloads_main DIV, such that it does not
// occupy the whole width of the page (rather than using the
// submenu population in Theme.class.php)
$subMenuTitle = array();
$subMenuUrl = array();
$subMenuAttr = array();
$subMenuTitle["Title"] = "Statistics: Project Activity Plots";
$subMenuUrl[] = '/project/stats/index.php?group_id=' . $group_id;

// Show the submenu.
echo $HTML->beginSubMenu();
echo $HTML->printSubMenu($subMenuTitle, $subMenuUrl, $subMenuAttr);
echo $HTML->endSubMenu();

?>

<h3>Project Statistics</h3>

<form action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="get">
<input type="hidden" name="group_id" value="<?php echo $group_id; ?>" />
<input type="hidden" name="project_report" value="1" />
<table class="table">
<tr>
<td><strong><?php echo _('Select Project Area')._(':'); ?></strong><br /><?php echo report_area_box('area',$area,$group); ?></td>
<td><strong><?php echo _('Type')._(':'); ?></strong><br /><?php echo report_span_box('SPAN',$SPAN); ?></td>
<td><strong><?php echo _('Start Date')._(':'); ?></strong><br /><?php echo report_months_box($report, 'start', $start); ?></td>
<td><strong><?php echo _('End Date')._(':'); ?></strong><br /><?php echo report_months_box($report, 'end', $end); ?></td>
<td style="vertical-align:bottom"><input type="submit" name="submit" value="<?php echo _('Display'); ?>" class="btn-cta" /></td>
</tr>
</table>
</form>
<p>
<?php
if (!empty($project_report)) {

   if ($start == $end) {
	 echo '<p class="error">'._('Start and end dates must be different').'</p>';
   } else {
	 if (!report_actgraph('project', $SPAN, $start, $end, $group_id, $area)) {
		echo '<p class="error">'._('Error during graphic computation.');
	 }
   }

}



// Begin FRS report

echo "<br /><br />";

//session_require_perm('frs', $group_id, 'write');

$report = new Report();
if ($report->isError()) {
	exit_error($report->getErrorMessage(), 'frs');
}

if (!$start_frs || !$end_frs) $z =& $report->getMonthStartArr();

if (!$start_frs) {
	$start_frs = $z[0];
}

if (!$end_frs) {
	$end_frs = $z[ count($z)-1 ];
}

if ($end_frs < $start_frs) list($start_frs, $end_frs) = array($end_frs, $start_frs);

html_use_jqueryjqplotpluginCanvas();
html_use_jqueryjqplotpluginhighlighter();

/*
frs_header(array('title' => _('File Release Reporting'),
		 'group' => $group_id,
		 'pagename' => 'project_showfiles',
		 'sectionvals' => group_getname($group_id)));
*/

$only_public = 1;
if (session_loggedin() && forge_check_perm ('frs', $group_id, 'write')) {
   $only_public = 0;
}

echo "<h3>Download Packages Statistics</h3>";

$report = new ReportDownloads($group_id, $package_id, $start_frs, $end_frs);
if ($report->isError()) {
	echo '<p class="error_msg">'.$report->getErrorMessage().'</p>';
	//site_project_footer(array());
	//exit;
} else {

?>

<form action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="get">	
    <input type="hidden" name="group_id" value="<?php echo $group_id; ?>" />
	<input type="hidden" name="download_report" value="1" />
	<table class="table">
    <tr>
	<td><strong><?php echo _('Select Package')._(':'); ?></strong><br />
	<?php echo report_package_box($group_id,'package_id',$package_id,$only_public); ?></td>
	<td><strong><?php echo _('Start Date')._(':'); ?></strong><br />
	<?php echo report_months_box($report, 'start_frs', $start_frs); ?></td>
	<td><strong><?php echo _('End Date')._(':'); ?></strong><br />
	<?php echo report_months_box($report, 'end_frs', $end_frs); ?></td>	
	<td style="vertical-align:bottom"><input type="submit" name="submit" value="<?php echo _('Display'); ?>" class="btn-cta" /></td>
	</tr>
	</table>
</form>


<?php
}  // else no error

$package_okay = 1;
if ($only_public) {
   // make sure selected package_id is public
   $res = db_query_params ('SELECT name FROM frs_package WHERE frs_package.package_id = $1 AND frs_package.is_public = 1 AND status_id = 1',array ($package_id));
   if (!$res || db_numrows($res) < 1) {
      $package_okay = 0;
   }
}

//echo "only_public: " . $only_public . "<br />";
//echo "package_okay: " . $package_okay . "<br />";
//echo "package_id: " . $package_id . "<br />";

if (!empty($download_report) && $package_okay) {


$data = $report->getData();

if ($start_frs == $end_frs) {
    echo '<p class="error">'._('Start and end dates must be different').'</p>';
} elseif (count($data) == 0) {
	echo '<p class="information">';
	echo _('There have been no downloads for this package.');
	echo '</p>';
} else {
	echo '<script type="text/javascript">//<![CDATA['."\n";
	echo 'var ticks = new Array();';
	echo 'var values = new Array();';
	$arr =& $report->getMonthStartArr();
	$arr2 = array();
	$valuesArr = array();
	for ($i=0; $i < count($arr); $i++) {
		if ($arr[$i] >= $start_frs && $arr[$i] <= $end_frs) {
			$arr2[$i] = date(_('Y-m'), $arr[$i]);
			$valuesArr[$i] = 0;
		}
	}
	foreach ($arr2 as $key) {
		echo 'ticks.push("'.$key.'");';
	}
	for ($i=0; $i < count($data); $i++) {
		echo 'var labels = [{label:\''.$data[$i][0].'\'}];';
		$thisdate = date(_('Y-m'), mktime(0, 0, 0, substr($data[$i][4], 4, 2), 0, substr($data[$i][4], 0, 4)));
		$indexkey = array_search($thisdate, $arr2);
		$valuesArr[$indexkey+1]++;
	}
	foreach ($valuesArr as $key) {
		echo 'values.push('.$key.');';
	}
	echo 'var plot1;';
	echo 'jQuery(document).ready(function(){
			plot1 = jQuery.jqplot (\'chart1\', [values], {
					axesDefaults: {
						tickOptions: {
							angle: -90,
							fontSize: \'8px\',
							showGridline: false,
							showMark: false,
						},
					},
					legend: {
						show: true,
						placement: \'insideGrid\',
						location: \'nw\'
					},
					series:
						labels
					,
					axes: {
						xaxis: {
							label: "'._('Date').'",
							ticks: ticks,
							pad: 90,
							tickOptions: {
							    angle: -180,
							    fontSize: \'9px\',
							    showMark: true,
							},
							renderer: jQuery.jqplot.CategoryAxisRenderer
						
						},
						yaxis: {
							label: "'._('Downloads').'",
							padMin: 0,
							tickOptions: {
								angle: 180,
								fontSize: \'12px\',
								showMark: true,
							}
						}
					},
					highlighter: {
						show: true,
						sizeAdjust: 2.5,
					},
				});
		});';
	echo 'jQuery(window).resize(function() {
			plot1.replot( { resetAxes: true } );
		});'."\n";
	echo '//]]></script>';
	
	
	echo '<div id="chart1"></div>';

}

}  // download_report

echo "</div><!--main_col-->\n";

// Add side bar to show statistics and project leads.
//constructSideBar($group);

echo "</div><!--display table-->\n</div><!--project_overview_main-->\n";

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
