<?php

/**
 *
 * Theme.class.php
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
 
/**
 * SimTk Theme
 * Now it uses Navigation dropdown defined at the: Theme::show_topmenu_dropdowns();
 *
 * Two methods Revrited from  Navigation.class.php
 * 1) Theme::getUserLinks();
 * 2) Theme::getSearchBox();
 * 
 * Methods override from the Layout.class.php
 * 1) Theme::headerStart(); // Fixing incorrect BOOTSTRAP grid representations for iPhone: http://stackoverflow.com/questions/19933848/bootstrap-3-not-xs-on-iphone-5
 * 2) Theme::searchBox(); // here used revrited from Navigation Theme::getSearchBox
 * 3) Theme:: headerJS();
 * 4) Theme::footer();
 *
 * (!) Note: (!) I've comment out pluging hook at the top_menu_and_navigation, for avoiding unpredictable output in UL
 */

require_once $gfplugins.'following/include/Following.class.php';
require_once $gfwww.'include/Layout.class.php';
require_once $gfcommon.'include/Group.class.php';
require_once $gfcommon.'docman/DocumentManager.class.php';
require_once $gfwww.'include/popupmenuHandler.php';
require_once $gfwww.'include/popupmenuSetup.php';

// NOTE: Do not cache page.
// Otherwise, user information may not be updated.
// e.g. user logged out already may still be shown as logged in.
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');

define('TOP_TAB_HEIGHT', 30);
define('BOTTOM_TAB_HEIGHT', 22);

class Theme extends Layout {

	function Theme() {
		// Parent constructor
		$this->Layout();
		$this->themeurl = util_make_url('themes/simtk/');
		$this->imgbaseurl = $this->themeurl . 'images/';
		$this->imgroot = $this->imgbaseurl;

		$this->addStylesheet('/themes/simtk/css/theme.css');
		$this->addStylesheet('/themes/simtk/css/carousel.css');
	}

	function quicknewsbutton() {
		return "<div class='quicknews-toggle'><a href=# onclick='jQuery(\".quicknews\").slideToggle()'>news</a></div>";
	}

	function quicknews() {
		$ret = "<div class='quicknews'>";
		$ret .= "<ul>";
		$ret .= "<li><h1>news de ouf</h1>hello world</li>";
		$ret .= "<li><h1>news de ouf</h1>hello world</li>";
		$ret .= "<li><h1>news de ouf</h1>hello world</li>";
		$ret .= "<li><h1>news de ouf</h1>hello world</li>";
		$ret .= "</ul>";
		$ret .= "</div>";
		return $ret;
	}

	function bodyHeader($params) {
		if (!isset($params['h1']) && isset($params['title'])) {
			$params['h1'] = $params['title'];
		}

		if (!isset($params['title'])) {
			$params['title'] = forge_get_config('forge_name');
		}
		else {
			$params['title'] = $params['title'] . " - " . forge_get_config('forge_name');
		}
                // add google analytics here, after the body
				include_once("analyticstracking.php");
                $this->top_menu_and_navigation($params);
                
                return; // JUST CUT BOTTOM AWAY FOR A WHILE ------------------->
	}
        
         /**
         * Here I draw top layer of menu
         * @param type $params
         */
      private function top_menu_and_navigation($params) {
        echo "<div class='the_header'>\n"; //  1
            echo "<div class='cont_header'>\n"; //  2
?>

<nav class="navbar navbar-simtk" role="navigation">
	<div class="navbar-header">
		<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
			<span class="sr-only">Toggle navigation</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		</button>
		<?php  echo util_make_link('/', html_image('header/logo.png', null, null, array('alt'=>'FusionForge Home')),array('class'=>'navbar-brand')); ?>
	</div>
	
	<!-- HAVE TWO ADDITIONAL CLASSES: intend and action --->
	<div id="navbar" class="navbar-collapse collapse">
	    <!-- Decreased width of Search Box and moved to this location --->
		<ul class="nav navbar-nav navbar-right">
<?php
	    echo "<div class='the_search_box'>\n"; // Open 7 the_search_box
				$this->searchBox();
	    echo "</div>\n"; // close 6 close the search_box
?>

<?php

	$this->show_topmenu_dropdowns();
	$items = $this->getUserLinks();
	if (session_loggedin()) {
	  // only show account dropdown if logged in
	  $this->show_account_dropdown();
	}
	// Check that $items['titles'] is set before looking up count().
	if (isset($items['titles'])) {
		for ($j = 0; $j < count($items['titles']); $j++) {
			$links[] = "<li>".util_make_link($items['urls'][$j],$items['titles'][$j], false, true)."</li>";
		}
		echo implode(" ", $links);
	}
	//$this->quickNav(); // Now Quic nav is here!
	//plugin_hook('headermenu', $params);//(!) I've comment out pluging hook at the top_menu_and_navigation, for avoiding unpredictable output in UL

?>
		</ul>
	</div>
</nav>

<script>
// Safari page cache may cause user login/logout status to be not up-to-date.
// See http://stackoverflow.com/questions/8788802/prevent-safari-loading-from-cache-when-back-button-is-clicked
// Use the following to force a reload instead of using cache.
$(window).bind("pageshow", function(event) {
	if (event.originalEvent.persisted) {
		window.location.reload();
	}
});

// NOTE: Need to use $(window).load() instead of $(document).load() here to get
// consistent load behavior between Chrome, Safari, Firefox, and Opera.
// See: http://stackoverflow.com/questions/12354865/image-onload-event-and-browser-cache
$(window).load(function() {
	// Find and scale all img logos elements.
	$("img").each(function() {
		var theImage = new Image();
		theImage.src = $(this).attr("src");
		if (theImage.src.indexOf("/logos/") == -1 &&
			theImage.src.indexOf("/logos-frs/") == -1) {
			// Skip.
			return;
		}

		var myThis = $(this);
		theImage.onload = function() {
			// Image loaded.

			// Get element's dimenions.
			var elemWidth = myThis.width();
			var elemHeight = myThis.height();

			// Get image file's dimensions.
			var theNaturalWidth = theImage.width;
			var theNaturalHeight =  theImage.height;

			// Use the dimension that is constraining.
			var ratioH = elemHeight / theNaturalHeight;
			var ratioW = elemWidth / theNaturalWidth;
			var theRatio = ratioH;
			if (ratioH > ratioW) {
				theRatio = ratioW;
			}

			// New dimensions of image.
			// Subtract 2px in dimensions to account for roundoff errors.
			var theScaledWidth = Math.floor(theRatio * theNaturalWidth) - 2;
			var theScaledHeight = Math.floor(theRatio * theNaturalHeight) - 2;
			// Add margin at top/bottom or left/right.
			var marginTop = Math.floor((elemHeight - theScaledHeight)/2) - 2;
			if (marginTop < 0) {
				marginTop = 0;
			}
			var marginLeft = Math.floor((elemWidth - theScaledWidth)/2) - 2;
			if (marginLeft < 0) {
				marginLeft = 0;
			}

			// Set CSS for element with new dimensions with margin to center image.
			myThis.css({
				'width': theScaledWidth + 'px', 
				'height': theScaledHeight + 'px',
				'margin-top': marginTop + 'px',
				'margin-bottom': marginTop + 'px',
				'margin-left': marginLeft + 'px',
				'margin-right': marginLeft + 'px',
			});
		};
	});
});
</script>
		
<?php
			
                echo "</div>\n"; // close cont_teader
        echo "</div>\n"; // close the_heaer
 
        // Here starting main div
        echo "<div class='the_body'>\n";
        echo   "<div class='cont_body'>\n";

	$strFileAnnouncement = "/usr/share/gforge/www/announcement.html";
	if (file_exists($strFileAnnouncement)) {
		// Announcement file exists.
		$handle = fopen($strFileAnnouncement, "rb");
		if ($handle !== false) {
			// Opened successfully. Get content.
			$strAnnouncement = stream_get_contents($handle);
			fclose($handle);

			// Display announcement.
			echo $strAnnouncement;
		}
	}

        echo      "<div class='row_body'>\n";
        echo          "<div class='maindiv'>\n"; // here maindiv as bootstrap Column: http://www.helloerik.com/the-subtle-magic-behind-why-the-bootstrap-3-grid-works
        
        // call subheader if group is valid
        if (isset($params['group']) && $params['group']) {
          $this->subheader($params);
        }

        // close subheader
	// menus specific to project features
 
        plugin_hook('message', array());

		if(isset($GLOBALS['error_msg']) && $GLOBALS['error_msg']) {
			if (strpos($GLOBALS['error_msg'], "***NOSTRIPTAGS***") !== false) {
				echo $this->error_msg(substr($GLOBALS['error_msg'], 17), false);
			}
			else {
				echo $this->error_msg($GLOBALS['error_msg']);
			}
		}
		if(isset($GLOBALS['warning_msg']) && $GLOBALS['warning_msg']) {
			if (strpos($GLOBALS['warning_msg'], "***NOSTRIPTAGS***") !== false) {
				echo $this->warning_msg(substr($GLOBALS['warning_msg'], 17), false);
			}
			else {
				echo $this->warning_msg($GLOBALS['warning_msg']);
			}
		}
		if(isset($GLOBALS['feedback']) && $GLOBALS['feedback']) {
			if (strpos($GLOBALS['feedback'], "***NOSTRIPTAGS***") !== false) {
				echo $this->feedback(substr($GLOBALS['feedback'], 17), false);
			}
			else {
				echo $this->feedback($GLOBALS['feedback']);
			}
		}

		if (isset($params['h1'])) {
                        /* project sub title */
/*
			echo "<h2><a href='".$params['titleurl']."'>".$params['h1']."</a></h2>";
*/
		} elseif (isset($params['title'])) {
			echo '<h1 class="hide">'.$params['title'].'</h1>';
		}
		if (isset($params['submenu']))
			echo $params['submenu'];
        
    }


    function subheader($params) {

        // group object is called in common/include/pre.php
        $group_id = $params['group'];
        $group = group_get_object($group_id);
        if (!$group || !is_object($group)) {
                exit_no_group();
        } elseif ($group->isError()) {
                exit_error($group->getErrorMessage(), 'home');
        }

        $following = new Following($group);
        if (!$following || !is_object($following)) {
           exit_error('Error','Could Not Create Following');
        } elseif ($following->isError()) {
           exit_error('Error',$following->getErrorMessage());
        }

        $navigation = new Navigation();

    ?>

    <div class='row'>
    <div style="display: table; min-height: 15px; width: 100%;" class="marker">


        <div class='project_title'>
            <?php
              $logofile =  $group->getLogoFile();  
              $theLink = "/projects/" . $group->getUnixName();
              if (!empty($logofile)) { 
                echo "<div class='project_titleimg'>";
                echo "<a href='" . $theLink . "'>" .
			"<img class='mini_logo' " .
			"src='/logos/" . $logofile . "?dummy_value=" . rand() .  "' " .
			"onError='this.onerror=null;this.src=" . '"' . "/logos/_thumb" . '";' . "' " .
			"alt='small logo'/></a>";
                echo "</div>";
                echo "<div class='project_titletxt2'>";
                echo "<a href='" . $theLink . "'>" . $group->getPublicName() . "</a>";
                echo "</div>";
              }
              else {
                echo "<div class='project_titletxt'>";
                echo "<a href='" . $theLink . "'>" . $group->getPublicName() . "</a>";
                echo "</div>";
              }
            ?>
        </div>
        <div class="project_share">
            <div class="social_buttons">
                <a class="popup" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $group->getPageURL(); ?>"><img src="/themes/simtk/images/demonstration/social-facebook.png" class="social_icon social_facebook"/></a>
                <a class="popup" href="https://plus.google.com/share?url={<?php echo $group->getPageURL(); ?>}"><img src="/themes/simtk/images/demonstration/social-google.png" class="social_icon social_google"/></a>
                <a class="popup" title="twitter" name="windowX" href="https://twitter.com/share"><img src="/themes/simtk/images/demonstration/social-twitter.png" /></a>
                <a class="popup" href="http://www.linkedin.com/shareArticle?mini=true&url=<?php echo $group->getPageURL(); ?>&title=<?php echo $group->getPublicName(); ?>&summary=<?php echo htmlspecialchars($group->getSummary()); ?>" rel="nofollow"><img src="/themes/simtk/images/demonstration/social-linkedin.png" class="social_icon social_linkedin"/></a>
                <script type="text/javascript"> 
                 $('.popup').popupWindow({ 
                  centerBrowser:1
                  });
                 $(function () {
			if ($('[data-toggle="popover"]').length) {
	                  $('[data-toggle="popover"]').popover()
			}
                  });
                </script>
            </div>

              <?php
               $result = $following->getFollowing($group_id);

               if (!$result) {
                  $total_followers = 0;
                  //echo "no results: " . $total_followers;
               }
               else {
                 // get public count
                 $public_following_count = $following->getPublicFollowingCount($group_id);

                 // get private count
                 $private_following_count = $following->getPrivateFollowingCount($group_id);
                 $total_followers = $public_following_count + $private_following_count;
                 //echo "results: " . $total_followers;
              }

              echo "<div style='clear: both;'></div>";
              // Get user - included in common/include/pre.php
              $user_name = "";
              if (session_get_user()) {
                $user_name = session_get_user()->getUnixName();
              }
              if ($user_name != "" && $following->isFollowing($group_id,$user_name)) {
			if (forge_check_perm('project_read', $group_id)) {
				echo "<div class='share_text'>";
				echo "<a class='btn-blue share_text_button' " .
					"href='/plugins/following/index.php?group_id=" . 
					$group_id .
					"&unfollow=1'>Unfollow</a>&nbsp;";
				echo "<a class='share_text_link' " .
					"href='/plugins/following/index.php?group_id=" . 
					$group_id .
					"'>" .
					"($total_followers)" .
					"</a>";
				echo "</div>";
			}
			else {
				// Do not show link if no read permission.
				echo "<div class='share_text'>";
				echo "<a class='btn-blue share_text_button' " .
					"href='/plugins/following/index.php?group_id=" . 
					$group_id .
					"&unfollow=1'>Unfollow</a>&nbsp;";
				echo "($total_followers)";
				echo "</div>";
			}
              }
              else {
			if (forge_check_perm('project_read', $group_id)) {
				echo "<div class='share_text'>" .
					"<a class='btn-blue share_text_button' " .
					"href='/plugins/following/follow.php?group_id=" . 
					$group_id .
					"'>Follow</a>&nbsp;";
				echo "<a class='share_text_link' " .
					"href='/plugins/following/index.php?group_id=" . 
					$group_id .
					"'>" .
					"($total_followers)" .
					"</a>";
				echo "</div>";
			}
			else {
				// Do not show link if no read permission.
				echo "<div class='share_text'>" .
					"<a class='btn-blue share_text_button' " .
					"href='/plugins/following/follow.php?group_id=" . 
					$group_id .
					"'>Follow</a>&nbsp;";
				echo "($total_followers)";
				echo "</div>";
			}
              }
              ?>
            <div style='clear: both;'></div>
<?php   /*
		if (forge_check_perm('project_read', $group_id)) {
			echo "<div class='share_text'>" .
				"<a class='btn-blue share_text_button' " .
				"href='/project/request.php?group_id=" .
				$group_id .
				"'>Join</a>&nbsp;";
			echo "<a class='share_text_link' " .
				"href='/project/memberlist.php?group_id=" .
				$group_id .
				"'>" .
				"(" . $this->getNumGroupMembers($group_id) . ")" .
				"</a>";
			echo "</div>";
		}
		else {
			echo "<div class='share_text'>" .
				"<a class='btn-blue share_text_button' " .
				"href='/project/request.php?group_id=" .
				$group_id .
				"'>Join</a>&nbsp;";
			echo "(" . $this->getNumGroupMembers($group_id) . ")";
			echo "</div>";
		}
		*/
?>

        </div>
    </div>
</div>

<?php
//if (forge_check_perm('project_read', $group_id)) {
// Anonymous role.
$roleToCheck = RoleAnonymous::getInstance();
if (session_loggedin()) {
	// User is logged in. Hence, use RoleLoggedIn instead to check.
	$roleToCheck = RoleLoggedIn::getInstance();
}
$isPermitted = $roleToCheck->hasPermission('project_read', $group_id);
if (!$isPermitted) {
	// General logged in guest mode does not permit access.
	// Check access of project members and admin user.
	$isPermitted = forge_check_perm('project_read', $group_id);
}

// Create a hidden DIV to keep the group_id. Otherwise, the group_id 
// may not be available at all times in a project's context 
// (e.g. after saving Project Info: Tools setting.)
echo '<div class="divGroupId" style="display:none;">' . $group_id . '</div>';

if ($isPermitted) {
?>

<div class="project_menu_row">
    <div class="project_menu_col">
        <div class="project_menu">
        <?php
              $menu = $navigation->getSimtkProjectMenu($group_id);
              //print_r ($menu);
              $max = count($menu['titles'],0);
              //echo "max: " . $max;
              if ($max > 10) {
                $menu_max = 10;
              }
              else {
                $menu_max = $max;
              }
              //echo "menu_max: " . $menu_max;

               for ($i=0; $i < $menu_max; $i++) {

			$menuTitle = $menu['titles'][$i];
			$menuId = str_replace(" ", "", $menu['titles'][$i]);
			$menuId = str_replace(".", "", $menuId);

			if ($menuTitle == "Forums") {
				// Look up group_name from groups table to match to phpbb forum.
				$group_name = $this->getGroupName($group_id);
				if ($group_name !== false) {
					$forumLink = "/plugins/phpBB/indexPhpbb.php?" .
						"group_id=" . $group_id .
						"&pluginname=phpBB";
		                	echo "<a href='" . $forumLink . "'><span " .
						"id='" . $menuId ."' " .
						"class='btnDropdown'>" . 
						$menuTitle . "</span></a>";
				}
			}
			else if ($menuTitle == "Admin") {
		                echo "<a href='" . $menu['urls'][$i] . "'>" .
					"<span " .
					"id='" . $menuId ."' " .
					"class='btnDropdown'>" . 
					$menuTitle . "</span></a>";
			}
			else {
		                echo "<a href='" . $menu['urls'][$i] . "'><span " .
					"id='" . $menuId ."' " .
					"class='btnDropdown'>" . 
					$menuTitle . "</span></a>";
			}

			// Generate the popup menus and sub-menus for each section.
			echo genPopupMenu($menuTitle, $group_id);
              }

         ?>
         <?php if ($max > 10) { ?>
            <div class="dropdown">
                <a class="btnDropdown btn-bodynav dropdown-toggle" data-toggle="dropdown" >
                    More
                    <span class="arrow_icon"></span>
                </a>
                <ul class="dropdown-menu" role="menu" >
                   <?php
                   for ($i=$menu_max; $i < $max; $i++) {
			if ($menu['titles'][$i] == "Forums") {
				// Look up group_name from groups table to match to phpbb forum.
				$group_name = $this->getGroupName($group_id);
				if ($group_name !== false) {
					$forumLink = "/plugins/phpBB/indexPhpbb.php?" .
						"group_id=" . $group_id .
						"&pluginname=phpBB";
		                	echo "<li><a role='menuitem' tabindex='-1' href='" . $forumLink . "'>" . 
						$menu['titles'][$i] . "</a></li>";
				}
			}
			else if ($menu['titles'][$i] == "Admin") {
				echo "<li><a style='color:#f75236;' role='menuitem' tabindex='-1' href='" .  $menu['urls'][$i] . "'>" . $menu['titles'][$i] . "</a></li>";
			}
			else {
				echo "<li><a role='menuitem' tabindex='-1' href='" .  $menu['urls'][$i] . "'>" . $menu['titles'][$i] . "</a></li>";
			}
                   }
                   ?>
                </ul>
            </div>
         <?php } ?>

        </div>

	<div class="hamburgerContainer">
		<button class="hamburger" data-toggle="collapse" data-target="#hamburger_menu">
			<span class="rows_icon"></span>
		</button>
		<div class="collapse" id="hamburger_menu">
			<ul class="nav">
			<?php
			for ($cnt = 0; $cnt < count($menu['titles']); $cnt++) {
				if ($menu['titles'][$cnt] == "Forums") {
					// Look up group_name from groups table to match to phpbb forum.
					$group_name = $this->getGroupName($group_id);
					if ($group_name !== false) {
						$forumLink = "/plugins/phpBB/indexPhpbb.php?" .
							"group_id=" . $group_id .
							"&pluginname=phpBB";
						echo "<li><a href='" . $forumLink . "'>" . 
							$menu['titles'][$cnt] . "</a></li>";
					}
				}
				else {
					// Generate dropdown submenus in hamburger.
					echo genDropdownMenu($menu['titles'][$cnt], $menu['urls'][$cnt], $group_id);
				}
			}
?>
			</ul>
		</div>
	</div>


    </div>

</div>

<?php
} 
else {
?>
<div class="project_menu_row">
    <div style="color:#3666a7;font-size:18px;font-weight:600;">
        <img src="/images/private-project.png"/>
        This is a private project. You must be a member to view its contents.
    </div>
</div>
<?php
}
?>


    <?php
      return;
    }
    /**
     * Top menu dropdowns.
     */
    function show_topmenu_dropdowns(){
        ?>

<li class="dropdown">
	<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Projects<span class="caret"></span></a>
	<ul class="dropdown-menu" style='min-width:330px;' role="menu">
<?php
	// Look up user name, if logged in.
	$user_name = "";
	if (session_get_user()) {
		$user_name = session_get_user()->getUnixName();

		echo "<li class='dropdown-header'>Recent</li>";

		// Get recently visited  projects.
		$arrRecentProjLinks = recentProjectsVisited();
		for ($cnt = 0; $cnt < count($arrRecentProjLinks); $cnt++) {
			echo "<li class='intend'>" . $arrRecentProjLinks[$cnt] . "</li>\n";
		}
	}
?>
		<li class="dropdown-submenu">
			<a href="#" data-toggle="dropdown" style="padding-left:20px;" >Project categories</a>
			<ul class="dropdown-menu">
				<li class="dropdown-header">Biological applications</li>
				<li class="intend"><a href="/category/category.php?cat=309&sort=date&page=0&srch=&" tabindex="-1">Cardiovascular system</a></li>
				<li class="intend"><a href="/category/category.php?cat=421&sort=date&page=0&srch=&" tabindex="-1">Cell</a></li>
				<li class="intend"><a href="/category/category.php?cat=308&sort=date&page=0&srch=&" tabindex="-1">Myosin</a></li>
				<li class="intend"><a href="/category/category.php?cat=310&sort=date&page=0&srch=&" tabindex="-1">Neuromuscular system</a></li>
				<li class="intend"><a href="/category/category.php?cat=406&sort=date&page=0&srch=&" tabindex="-1">Protein</a></li>
				<li class="intend"><a href="/category/category.php?cat=307&sort=date&page=0&srch=&" tabindex="-1">RNA</a></li>
				<li class="intend"><a href="/category/category.php?cat=420&sort=date&page=0&srch=&" tabindex="-1">Tissue</a></li>
				<li class="dropdown-header">Biocomputational focus</li>
				<li class="intend"><a href="/category/category.php?cat=411&sort=date&page=0&srch=&" tabindex="-1">Experimental analysis</a></li>
				<li class="intend"><a href="/category/category.php?cat=412&sort=date&page=0&srch=&" tabindex="-1">Image processing</a></li>
				<li class="intend"><a href="/category/category.php?cat=426&sort=date&page=0&srch=&" tabindex="-1">Network modeling and analysis</a></li>
				<li class="intend"><a href="/category/category.php?cat=409&sort=date&page=0&srch=&" tabindex="-1">Physics-based simulation</a></li>
				<li class="intend"><a href="/category/category.php?cat=416&sort=date&page=0&srch=&" tabindex="-1">Statistical analysis</a></li>
				<li class="intend"><a href="/category/category.php?cat=415&sort=date&page=0&srch=&" tabindex="-1">Visualization</a></li>
			</ul>
		</li>

<?php
	// Link to community pages.
	$arrCommunityNames = $this->getCommunityNames();
	$cntCommunityNames = count($arrCommunityNames);
	if ($cntCommunityNames > 0) {
?>
		<li class="dropdown-submenu">
			<a href="#" data-toggle="dropdown" style="padding-left:20px;" >Communities</a>
			<ul class="dropdown-menu">
<?php
	}
	$cnt = 0;
	foreach ($arrCommunityNames as $catId=>$fullName) {
		if ($cnt++ >= 5) {
			break;
		}
		$dispCommName = abbrGroupName($fullName);
		echo '<li class="intend"><a href="/category/communityPage.php?cat=' . $catId . 
			'&sort=date&page=0&srch=&" tabindex="-1">' . $dispCommName . '</a></li>';
	}
	echo '<li class="intend"><a href="/communities.php" tabindex="-1">All communities</a></li>';
	if ($cntCommunityNames > 0) {
?>
			</ul>
		</li>
<?php
	}

	// Look up user name, if logged in.
	$user_name = "";
	if (session_get_user()) {
?>
		<li class="dropdown-submenu">
			<a href="#" data-toggle="dropdown" style="padding-left:20px;" >My projects</a>
			<ul class="dropdown-menu">
<?php
		//echo "<li class='dropdown-header'>Recent</li>";
		$user_name = session_get_user()->getUnixName();

		// Get my projects.
		$arrMyProjLinks = getMyProjects();
		foreach ($arrMyProjLinks as $role_name=>$the_projs) {
			echo " <li class='dropdown-header'>" . $role_name . "</li>";
			foreach ($the_projs as $group_name=>$the_proj) {
				echo "<li class='intend' tabindex='-1'>" . $the_proj . "</li>\n";
			}
		}
?>

			</ul>
		</li>
<?php
	}
?>
		<li><a class="action" href="/register" style="padding-left:20px;" >Create a new project</a></li>

	</ul>
</li>
<li class="dropdown">
	<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">About<span class="caret"></span></a>
	<ul class="dropdown-menu" role="menu">
<!--
		<li role="presentation" class="dropdown-header">What is SimTK?</li>
		<li class="intend"><a href='/history.html'>History</a></li>
		<li class="intend"><a href='/features.html'>Features</a></li>
		<li class="intend"><a href='/case_studies.html'>Case studies</a></li>
		<li class="intend"><a href='/faq_simtk.html'>FAQ</a></li>

		<li role="presentation" class="dropdown-header">Support</li>
		<li class="intend"><a href='/how_to_use_simtk.html'>How to use SimTK</a></li>
		<li class="intend"><a href='/sendmessage.php?touser=101'>Contact</a></li>
-->
		<li class="intend"><a href="/whatIsSimtk.php">What is SimTK?</a></li>
		<li class="intend"><a href='/features.php'>Features</a></li>
		<li class="intend"><a href='/faq.php'>FAQ</a></li>
		<li class="intend"><a href='/sendmessage.php?touser=101'>Contact</a></li>
	</ul>
</li>

<?php }
           
function show_account_dropdown() {
	
	    if (session_loggedin()) {
           // get user - included in common/include/pre.php
           $user = session_get_user(); // get the session user
           $user_name = $user->getUnixName();
		}
?>

    <li class="dropdown">
	<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
<?php
// Display user firstname at top of "Account" menu.
$u = user_get_object(user_getid());
echo $u->getFirstName();
?>
<span class="caret"></span></a>
	<ul class="dropdown-menu" role="menu">
	    <li class="intend"><a href='<?php echo util_make_uri('/plugins/mypage/'); ?>'>My page</a></li>
	    <li class="intend"><a href='/account/settings.php'>Settings</a></li>
		<li class="intend"><a href='/account/'>Edit profile</a></li>
		<li class="intend"><a href='/users/<?php echo $user_name; ?>'>View profile</a></li>
		<li class="intend"><a href='/account/logout.php'>Logout</a></li>
	</ul>
    </li>

	
<?php
    }
    
    function searchBox() {
		echo $this->getSearchBox();
    }
    
	function bodyFooter($params) {
            echo "\n\n<!--Here the Footer starts--></div>\n"; // the_body
            echo "</div>\n"; // cont_body
            echo "</div>\n"; // row_body
            echo "</div>\n"; // 'maindiv'
	}

	function footer($params) {
		$this->bodyFooter($params);
            echo '<div class="the_footer">';
            echo '<div class="cont_footer">';
                echo '<div class="footer_row">';
                    echo '<div class="footer_information">';
                        echo '<p>SimTK is maintained through Grant R01 GM107340 from the National Institutes of Health (NIH). It was initially developed as part of the Simbios project funded by the NIH as part of the NIH Roadmap for Medical Research, Grant U54 GM072970.';
                        echo '</p>';
                    echo '</div>';
                    echo '<div class="footer-right">';
/*
                        echo '<a href="httlp://google.com">Our Pledge and Your Responsibility</a><br/><br/>';
                        echo '<a href="httlp://google.com">Website</a> &emsp; <a href="httlp://google.com">Feedback</a> &emsp; <a href="httlp://google.com">About</a> &emsp; <a href="httlp://google.com">Join</a>'; 
*/
                        echo '<a href="/pledge.php">Our Pledge and Your Responsibility</a><br/><br/>';
			echo '<a class="feedback_href" href="#">Feedback</a> &emsp; ';
			echo '<a href="/whatIsSimtk.php">About</a> &emsp; ';
			echo '<a href="/account/register.php">Join</a>';
                    echo '</div>';
                echo '</div>';

		echo '<div style="font-size:12px;">';
		echo 'Version 2.0.17. Website design by <a href="http://www.viewfarm.com/">Viewfarm</a>. Icons created by SimTK team using art by <a href="http://graphberry.com" title="GraphBerry">GraphBerry</a> from <a href="http://www.flaticon.com" title="Flaticon">www.flaticon.com</a> under a CC BY 3.0 license. Forked from <a href="http://fusionforge.org">FusionForge</a> 5.3.2.';
                echo '</div>';

            echo '</div>';
            echo '</div>';
            $res = "";
            $res .= "<script>\n";
            // This plugin fials at the first start of the FireFox
            // that why I put it here, also add some delay.
            //$res .= 'setTimeout(function(){ $(document).ready(function () {$(".search_select").customSelect();});}, 20);';
            $res .= 
		'setTimeout(
			function() {
				$(document).ready(function() {
					$(".search_select").customSelect();
					$(".simtk_select").customSelect({customClass:"customSelectForms"});
				});
			},
			20
		);';
            $res .= "\n"; 
            $res .=
                '$(document).ready(function() {
                        $(".recYes").click(function() {
                            rec_group_id = $(this).parent().parent().attr("id");
                            $("#" + rec_group_id).text("Thanks!");
                        });
                });';
            $res .= "\n"; 
            $res .=
                '$(document).ready(function() {
                        $(".recNo").click(function() {
                            rec_group_id = $(this).parent().parent().attr("id");
                            $("#" + rec_group_id).text("Thanks!");
                        });
                });';
            $res .=
                '$(document).ready(function() {
			$(".feedback_href, .feedback_button").click(function() {
				// Get group_id if hidden DIV class "divGroupId" is present.
				theGroupId = -1;
				$(".divGroupId").each(function() {
					theGroupId = $(this).text();
				});
				if (theGroupId != -1) {
					// Has group_id.
					location.href="/feedback.php?group_id=" + theGroupId;
				}
				else {
					// No group_id.
					location.href="/feedback.php";
				}
				event.preventDefault();
			});
                });';
            $res .= "\n</script>\n";
            echo $res;
            $this->show_developer_divs();

            echo '<div class="feedback_button"><div class="text">Feedback</div></div>'."\n";
            echo '</body>' . "\n";
            echo '</html>' . "\n";
	}
        
        function show_developer_divs() {
            ?>
            <div class='check_image' style='display: none'> 
            </div>
            <div class='palette' style='display: none'>
                <div class='color light_yellow' ></div> <div class='text'>#FDF8E1 @light_yellow</div>
                <div style='clear: both;'></div>
                <div class='color red' ></div> <div class='text'>#F75236 @red</div>
                <div style='clear: both;'></div>
                <div class='color orange' ></div> <div class='text'>#F5B563 @orange</div>
                <div style='clear: both;'></div>
                <div class='color light_blue' ></div> <div class='text'>#81A5D4 @light_blue</div>
                <div style='clear: both;'></div>
                <div class='color dark_blue' ></div> <div class='text'>#5E96E1 @dark_blue</div>
                <div style='clear: both;'></div>
                <div class='color dark_grey' ></div> <div class='text'>#505050 @dark_grey</div>
                <div style='clear: both;'></div>
                <div class='color black' ></div> <div class='text'>#000000 @black</div>
                <div style='clear: both;'></div>
                <div class='color light_grey' ></div> <div class='text'>#A7A7A7 @light_grey</div>
                <div style='clear: both;'></div>
            </div>
            <?php
    }

    /**
	 * boxTop() - Top HTML box
	 *
	 * @param	string	$title	Box title
	 * @param	string	$id
	 * @return	string
	 */
	function boxTop($title, $id = '') {
		if ($id) {
			$id = $this->toSlug($id);
			$idid = ' id="' . $id . '"';
			$idtitle = ' id="' . $id . '-title"';
			$idtcont = ' id="' . $id . '-title-content"';
		} else {
			$idid = "";
			$idtitle = "";
			$idtcont = "";
		}

		$t_result = '';
		$t_result .= '<div' . $idid . ' class="box-surround">';
		$t_result .= '<div' . $idtitle . ' class="box-title">';
		$t_result .= '<div' . $idtcont . ' class="box-title-content">'. $title .'</div>';
		$t_result .= '</div> <!-- class="box-title" -->';

		return $t_result;
	}

	/**
	 * boxMiddle() - Middle HTML box
	 *
	 * @param	string	$title	Box title
	 * @param	string	$id
	 * @return	string
	 */
	function boxMiddle($title, $id = '') {
		if ($id) {
			$id = $this->toSlug($id);
			$idtitle = ' id="' . $id . '-title"';
		} else {
			$idtitle = "";
		}

		$t_result ='<div' . $idtitle . ' class="box-middle">'.$title.'</div>';

		return $t_result;
	}

	/**
	 * boxContent() - Content HTML box
	 *
	 * @param	string	$content	Box content
	 * @param	string	$id
	 * @return	string
	 */
	function boxContent($content, $id = '') {
		if ($id) {
			$id = $this->toSlug($id);
			$idcont = ' id="' . $id . '-content"';
		} else {
			$idcont = "";
		}

		$t_result ='<div' . $idcont . ' class="box-content">'.$content.'</div>';
		return $t_result;
	}

	/**
	 * boxBottom() - Bottom HTML box
	 *
	 * @return	string
	 */
	function boxBottom() {
		$t_result='</div><!-- class="box-surround" -->';

		return $t_result;
	}

	/**
	 * boxGetAltRowStyle() - Get an alternating row style for tables
	 *
	 * @param	int	$i	Row number
	 * @return	string
	 */
	function boxGetAltRowStyle($i) 	{
		if ($i % 2 == 0)
			return 'class="bgcolor-white"';
		else
			return 'class="bgcolor-grey"';
	}

	function tabGenerator($TABS_DIRS, $TABS_TITLES, $TABS_TOOLTIPS, $nested=false,  $selected=false, $sel_tab_bgcolor='WHITE',  $total_width='100%', $group_id=-1) {
		$count = count($TABS_DIRS);

		if ($count < 1) {
			return '';
		}

		global $use_tooltips;

		if ($use_tooltips) {
			?>
			<script type="text/javascript">//<![CDATA[
				if (typeof(jQuery(window).tipsy) == 'function') {
					jQuery(document).ready(
						function() {
							jQuery('.tabtitle').tipsy({delayIn: 500, delayOut: 0, fade: true});
							jQuery('.tabtitle-nw').tipsy({gravity: 'nw', delayIn: 500, delayOut: 0, fade: true});
							jQuery('.tabtitle-ne').tipsy({gravity: 'ne', delayIn: 500, delayOut: 0, fade: true});
							jQuery('.tabtitle-w').tipsy({gravity: 'w', delayIn: 500, delayOut: 0, fade: true});
							jQuery('.tabtitle-e').tipsy({gravity: 'e', delayIn: 500, delayOut: 0, fade: true});
							jQuery('.tabtitle-sw').tipsy({gravity: 'sw', delayIn: 500, delayOut: 0, fade: true});
							jQuery('.tabtitle-se').tipsy({gravity: 'se', delayIn: 500, delayOut: 0, fade: true});
						}
					);
				}
			//]]></script>
			<?php
		}

		$return = '<!-- start tabs -->';
		$return .= '<table class="tabGenerator fullwidth" ';

		if ($total_width != '100%')
			$return .= 'style="width:' . $total_width . ';"';

		$return .= ">\n";
		$return .= '<tr>';

		$accumulated_width = 0;

		for ($i=0; $i<$count; $i++) {
			$tabwidth = intval(ceil(($i+1)*100/$count)) - $accumulated_width ;
			$accumulated_width += $tabwidth ;

			$return .= "\n";

			// middle part
			$return .= '<td class="tg-middle" style="width:'.$tabwidth.'%;"><a ';
			$return .= 'id="'.md5($TABS_DIRS[$i]).'" ';
			$return .= 'href="'.$TABS_DIRS[$i].'">' . "\n";
			$return .= '<span';

			if ($selected == $i)
				$return .= ' class="selected"';
			$return .= '>';
			$return .= '<span';

			$classes = '';
			if ($nested)
				$classes .= 'nested ';

			if ($use_tooltips) {
				$classes .= ' tabtitle ';
				if  (isset($TABS_TOOLTIPS[$i])) {
					$return .= ' title="'.$TABS_TOOLTIPS[$i].'" ';
				}
			}
                        $return .= ' class="'.$classes.'" ';
			$return .= '>' . "\n";
			$return .= ''.$TABS_TITLES[$i].'' . "\n";
			$return .= '</span>';
			$return .= '</span>' . "\n";
			$return .= '</a></td>' . "\n";

		}

		$return .= '</tr></table><!-- end tabs -->';

		return $return;
	}

	// FusionForge and forum shares the same group name.
	// First convert group_id used in FusionForge to "unix_group_name".
	// Then, use the "unix_group_name" to access the forum by group name.
	// viewforumbyname?fname=$unix_group_name is method added to the phpBB Forum
	// to support this lookup.
	function getGroupName($group_id) {
		$group_name = false;
		$resGroup = db_query_params('SELECT group_name FROM groups WHERE group_id=$1',
			array($group_id));
		while ($theRow = db_fetch_array($resGroup)) {
			$group_name = $theRow['group_name'];
		}

		return $group_name;
	}

	// Get community names
	function getCommunityNames() {
		$arrCommunities = array();
		$resCommunities = db_query_params('SELECT trove_cat_id, fullname FROM trove_cat ' .
			'WHERE parent=1000 ' .
			'ORDER BY trove_cat_id',
			array());
		while ($theRow = db_fetch_array($resCommunities)) {
			$trove_cat_id = $theRow['trove_cat_id'];
			$fullname = $theRow['fullname'];
			$arrCommunities[$trove_cat_id] = $fullname;
		}

		return $arrCommunities;
	}

	// Get the number of group members in the group.
	function getNumGroupMembers($group_id) {
		$project = group_get_object($group_id);
		$theMembers = $project->getUsers();
		return count($theMembers);
	}

	/**
	 * beginSubMenu() - Opening a submenu.
	 *
	 * @return	string	Html to start a submenu.
	 */
	function beginSubMenu() {

		$return = '<ul class="submenu">';
		return $return;
	}

	/**
	 * endSubMenu() - Closing a submenu.
	 *
	 * @return	string	Html to end a submenu.
	 */
	function endSubMenu() {

		$return = '</ul>';
		return $return;
	}

	/**
	 * printSubMenu() - Takes two array of titles and links and builds the contents of a menu.
	 *
	 * @param	array	$title_arr	The array of titles.
	 * @param	array	$links_arr	The array of title links.
	 * @param	array	$attr_arr	The array of attributs by link
	 * @return	string	Html to build a submenu.
	 */
	function printSubMenu($title_arr, $links_arr, $attr_arr) {

		$return = '';
/*
		$count  = count($title_arr) - 1;

		if (!count($attr_arr)) {
			for ($i=0; $i<count($title_arr); $i++) {
				$attr_arr[] = NULL;
			}
		}

		for ($i = 0; $i < $count; $i++) {
			$return .= "<a href=\"$links_arr[$i]\" class=\"btn-blue\" >$title_arr[$i]</a>&nbsp;";
		}

                $return .= "<a href=\"$links_arr[$i]\" class=\"btn-blue\" >$title_arr[$i]</a>&nbsp;";
*/

		// Build submenu.
		$return .= $this->buildSubMenu($title_arr, $links_arr);

		return $return;
	}

	// Build submenu.
	function buildSubMenu($title_arr, $links_arr) {

		$return = "";

		// Look up page title and subtitle.
		$pageTitle = $this->getPageTitle($title_arr, $links_arr);
		$pageSubTitle = $this->getPageSubTitle($title_arr, $links_arr);

		// Submenu box.
		$return .= "<div class='project_submenubox'>";

		// Page title.
		$return .= "<div class='project_submenu'>$pageTitle</div>";

		// Only display submenu if there are at least 2 menu items.
		// Note: Count links, because there may be preset title name given
		// as associative index in $title_arr["Title"], which is not part of
		// the indexed array. $links_arr has indexed array only.
		if (count($links_arr) <= 1) {
			$return .= "</div>"; // project_submenubox
			$return .= "<div style='clear: both;'></div>"; // Clear formatting
			return $return;
		}

/*

// NOTE:
// Switched to use popup menu for each section.
// Hence, no longer using the submenu dropdowns here.

		$return .= "<div class='project_submenunav'>";
		$return .= "<div class='dropdown'>";

		$return .= "<a class='btnDropdown btn-bodynav dropdown-toggle' data-toggle='dropdown'>";
		// Page subtitle.
		if ($pageSubTitle == "") {
			// Cannot find subtitle. Put in page title.
			$return .= $pageTitle;
		}
		else {
			$return .= $pageSubTitle;
		}
		$return .= "<span class='arrow_icon'></span></a>";
		$return .= "<ul class='dropdown-menu' role='menu'>";
		for ($i = 0; $i < count($links_arr); $i++) {
			$return .= "<li><a role='menuitem' tabindex='-1' href='$links_arr[$i]'>$title_arr[$i]</a></li>";
		}
		$return .= "</ul>";

		$return .= "</div>"; // dropdown
		$return .= "</div>"; // project_submenunav
*/

		$return .= "</div>"; // project_submenubox

		$return .= "<div style='clear: both;'></div>"; // Clear formatting

		return $return;
	}

	// Look up page title name given the URL.
	function getPageTitle($title_arr, $links_arr) {

		if (isset($title_arr["Title"])) {
			// Use the preset title.
			return $title_arr["Title"];
		}

		// Get the URI.
		$theUri = getStringFromServer('REQUEST_URI');

		$idxStart = strpos($theUri, "/");
		if ($idxStart === false) {
			// Cannot get information from the URI.
			return "";
		}
		$idxEnd = strpos($theUri, "/", $idxStart + 1);
		if ($idxEnd === false) {
			// Cannot get information from the URI.
			return "";
		}
		// Get page title.
		$tmpStr = substr($theUri, $idxStart, $idxEnd - $idxStart);
		// Using plugin; needs further parsing.
		if ($tmpStr == "/plugins") {
			$idxStart = $idxEnd;
			$idxEnd = strpos($theUri, "/", $idxEnd + 1);
			if ($idxEnd === false) {
				// Cannot find information.
				return "";
			}
			// Get page title from specific plugin.
			$tmpStr = substr($theUri, $idxStart, $idxEnd - $idxStart);
		}

		$pageTitle = "";

		switch ($tmpStr) {
		case "/frs":
			$pageTitle = "Downloads";
			break;
		case "/news":
		case "/simtk_news":
			$pageTitle = "News";
			break;
		case "/docman":
			$pageTitle = "Documents";
			break;
		case "/tracker":
			$pageTitle = "Trackers";
			break;
		case "/scm":
		case "/githubAccess":
			$pageTitle = "Source Code";
			break;
		case "/mail":
			$pageTitle = "Mailing List";
			break;
		case "/project":
			$pageTitle = "Project Admin";
			break;
		case "/publications":
			$pageTitle = "Publications";
			break;
		case "/my":
		case "/account":
			$pageTitle = "My Account";
			break;
		case "/phpBB":
			$pageTitle = "Forum";
			break;
		case "/moinmoin":
			$pageTitle = "Wiki";
			break;
		case "/reports":
			$pageTitle = "Reports";
			break;
		case "/simulations":
			$pageTitle = "Simulations";
			break;
		}

		if ($pageTitle == "Project Admin") {
			// Get Admin subpage title.
			$idxStart = strpos($theUri, "/");
			$idxEnd = strpos($theUri, "?");
			if ($idxEnd !== false) {
				$subpageInfo = substr($theUri, $idxStart, $idxEnd - $idxStart);
			}
			else {
				// "?" not found. Use rest of string after "/".
				$subpageInfo = substr($theUri, $idxStart);
			}
			switch ($subpageInfo) {
			case "/project/admin/tools.php":
				$pageTitle = "Project Info: Tools";
				break;
			case "/project/admin/category.php":
				$pageTitle = "Project Admin: Categories";
				break;
			case "/project/admin/projCommunities.php":
				$pageTitle = "Project Admin: Communities";
				break;
			case "/project/admin/layout.php":
				$pageTitle = "Project Admin: Main Page Layout";
				break;
			case "/project/admin/settings.php":
				$pageTitle = "Project Admin: Settings";
				break;
			case "/project/admin/users.php":
				$pageTitle = "Project Admin: Members";
				break;
			case "/project/admin/statistics.php":
				$pageTitle = "Statistics: Downloads Details";
				break;
			case "/project/admin/related_projects.php":
				$pageTitle = "Project Admin: Main Page Layout: Edit Related Projects";
				break;	
			case "/project/stats/index.php":
				$pageTitle = "Statistics: Project Activity Plots";
				break;
			default:
				$pageTitle = "Project Admin: Project Info";
				break;
			}
		}
		else if ($pageTitle == "Downloads") {
			// Get Downloads subpage title.
			$idxStart = strpos($theUri, "/");
			$idxEnd = strrpos($theUri, "/");
			$subpageInfo = substr($theUri, $idxStart, $idxEnd - $idxStart);
			switch ($subpageInfo) {
			case "/frs/admin":
				$pageTitle = "Downloads: Admin";
				break;
			case "/frs/reporting":
				$pageTitle = "Downloads: Reporting";
				break;
			}
		}
		else if ($pageTitle == "Reports") {
			// Get Reports subpage title.
			$idxStart = strpos($theUri, "/");
			$idxEnd = strpos($theUri, "?");
			$subpageInfo = substr($theUri, $idxStart, $idxEnd - $idxStart);
			switch ($subpageInfo) {
			case "/plugins/reports/index.php":
				$pageTitle = "Statistics: Downloads Summary";
				break;	
			case "/plugins/reports/usagemap.php":
				$pageTitle = "Statistics: Geography of Use";
				break;
			default:
				$pageTitle = "Project Statistics";
				break;
			}
		}
		else if ($pageTitle == "Source Code") {
			// Get Reports subpage title.
			$idxStart = strpos($theUri, "/");
			$idxEnd = strpos($theUri, "?");
			$subpageInfo = substr($theUri, $idxStart, $idxEnd - $idxStart);
			switch ($subpageInfo) {
			case "/scm/":
				$pageTitle = "Source Code: Summary";
				break;	
			case "/scm/viewvc.php/":
				$pageTitle = "Source Code: Browse";
				break;
			case "/scm/admin/":
				$pageTitle = "Source Code: Admin";
				break;	
			case "/githubAccess/":
				$pageTitle = "Source Code: Summary";
				break;	
			case "/githubAccess/admin/":
				$pageTitle = "Source Code: Admin";
				break;	
			default:
				$pageTitle = "Source Code";
				break;
			}
		}
		else if ($pageTitle == "Publications") {
			// Get Reports subpage title.
			$idxStart = strpos($theUri, "/");
			$idxEnd = strpos($theUri, "?");
			$subpageInfo = substr($theUri, $idxStart, $idxEnd - $idxStart);
			switch ($subpageInfo) {
			case "/plugins/publications/index.php":
				$pageTitle = "Publications: View";
				break;		
			case "/plugins/publications/admin/add.php":
				$pageTitle = "Publications: Add";
				break;	
			case "/plugins/publications/admin/edit.php":
				$pageTitle = "Publications: Edit";
				break;	
			case "/plugins/publications/admin/":
				$pageTitle = "Publications: Admin";
				break;	
			default:
				$pageTitle = "Publications";
				break;
			}
		}
		else if ($pageTitle == "News") {
			// Get Reports subpage title.
			$idxStart = strpos($theUri, "/");
			$idxEnd = strpos($theUri, "?");
			$subpageInfo = substr($theUri, $idxStart, $idxEnd - $idxStart);
			switch ($subpageInfo) {
			case "/plugins/simtk_news/index.php":
				$pageTitle = "News";
				break;	
			case "/plugins/simtk_news/submit.php":
				$pageTitle = "News: Add";
				break;
			case "/plugins/simtk_news/admin/":
				$pageTitle = "News: Admin";
				break;	
			default:
				$pageTitle = "News";
				break;
			}
		}
		else if ($pageTitle == "Mailing List") {
			// Get Reports subpage title.
			$idxStart = strpos($theUri, "/");
			$idxEnd = strpos($theUri, "?");
			$subpageInfo = substr($theUri, $idxStart, $idxEnd - $idxStart);
			switch ($subpageInfo) {
			case "/mail/admin/":
				$pageTitle = "Mailing List: Administration";
				break;	
			case "/mail/admin/index.php":
				$pageTitle = "Mailing List: Administration";
				break;
			default:
				$pageTitle = "Mailing List";
				break;
			}
		}
		else if ($pageTitle == "My Account") {
			// Get My Account subpage title.
			$idxStart = strpos($theUri, "/");
			$subpageInfo = substr($theUri, $idxStart);
			switch ($subpageInfo) {
			case "/account/change_pw.php":
				$pageTitle = "Update Password";
				break;
			case "/account/change_email.php":
				$pageTitle = "Update Email";
				break;
			default:
				$pageTitle = "My Account";
				break;
			}
		}
		else if ($pageTitle == "Simulations") {
			// Get Simulations subpage title.
			$idxStart = strpos($theUri, "/");
			$idxEnd = strpos($theUri, "?");
			$subpageInfo = substr($theUri, $idxStart, $idxEnd - $idxStart);
			switch ($subpageInfo) {
			case "/simulations/viewJobs.php":
				$pageTitle = "Simulations: View My Jobs";
				break;
			case "/simulations/submitJob.php":
				$pageTitle = "Simulations: Submit Job";
				break;
			case "/simulations/admin.php":
				$pageTitle = "Simulations: Administration";
				break;
			case "/simulations/requestedJob.php":
				$pageTitle = "Simulations: License";
				break;
			}
		}
		
		
		return $pageTitle;
	}

	// Get the page subtitle.
	// Note: Match subtitle using information from $title_arr and $links_arr.
	function getPageSubTitle($title_arr, $links_arr) {

		// Get the URI.
		$theUri = getStringFromServer('REQUEST_URI');

		arsort($links_arr, SORT_NATURAL);

		foreach ($links_arr as $idx=>$theLink) {
			if (stripos($theLink, "&amp;") !== false) {
				// The specified link uses "&amp;". Replace with "&".
				// Otherwise, match with the URI would not work.
				$theLink = str_ireplace("&amp;", "&", $theLink);
			}
			if (stripos($theUri, $theLink) !== false) {
				// Found it.
				return $title_arr[$idx];
			}
			else if (stripos($theUri, "/index.php?") !== false) {
				// Try removing "index.php" from "/index.php?" and retry search
				// (because they mean the same.)
				$tmpUri = str_replace("/index.php?", "/?", $theUri);
				if (stripos($tmpUri, $theLink) !== false) {
					// Found it.
					return $title_arr[$idx];
				}
				else {
					// Try matching up to before "?"

					// Try removing "index.php" from "/index.php?"
					// (because they mean the same.)
					$tmpLink = str_replace("/index.php?", "/?", $theLink);

					$idx1 = stripos($tmpUri, "?");
					if ($idx1 !== false) {
						$tmpUri = substr($tmpUri, 0, $idx1);
					}
					$idx2 = stripos($tmpLink, "?");
					if ($idx2 !== false) {
						$tmpLink = substr($tmpLink, 0, $idx2);
					}
					if (stripos($tmpUri, $tmpLink) !== false) {
						// Found it.
						return $title_arr[$idx];
					}
				}
			}
		}

		// Subtitle not found.
		return "";
	}

	/**
	 * subMenu() - Takes two array of titles and links and build a menu.
	 *
	 * @param	array	$title_arr	The array of titles.
	 * @param	array	$links_arr	The array of title links.
	 * @param	array	$attr_arr	The array of attributes by link
	 * @return	string	Html to build a submenu.
	 */
	function subMenu($title_arr, $links_arr, $attr_arr = array()) {
		// For the Downloads/Publications/News home page, the side-bar is shown.
		// Same for "Team" and "Project Management/Members".
		// The submenu needs to be inserted under the "main_col" DIV
		// such that the submenu does not occupy the full width of the page. 
		// The submenu has to be set up within the section-specific PHP file 
		// and cannot be created here.
		$pageTitle = $this->getPageTitle($title_arr, $links_arr);
		$pageSubTitle = $this->getPageSubTitle($title_arr, $links_arr);
		$pageInfo = "$pageTitle/$pageSubTitle";
		switch ($pageInfo) {
		case "Downloads/":
		case "Downloads/View Downloads":
		case "Publications/":
		case "Publications/View Publications":
		case "News/":
		case "News/View News":
		case "Project Management/Members":
			return "";
		}

		$return = $this->beginSubMenu();
		$return .= $this->printSubMenu($title_arr, $links_arr, $attr_arr);
		$return .= $this->endSubMenu();
		return $return;
	}

	/**
	 * multiTableRow() - create a multilevel row in a table
	 *
	 * @param	string	$row_attr	the row attributes
	 * @param	array	$cell_data	the array of cell data, each element is an array,
	 *					the first item being the text,
	 *					the subsequent items are attributes (dont include
	 *					the bgcolor for the title here, that will be
	 *					handled by $istitle
	 * @param	bool	$istitle	is this row part of the title ?
	 *
	 * @return string
	 */
	function multiTableRow($row_attr, $cell_data, $istitle)
	{
		$return= '<tr class="ff" '.$row_attr;
		if ( $istitle )
			$return .=' align="center"';

		$return .= '>';
		for ( $c = 0; $c < count($cell_data); $c++ ) {
			$return .='<td class="ff" ';
			for ( $a=1; $a < count($cell_data[$c]); $a++)
				$return .= $cell_data[$c][$a].' ';

			$return .= '>';
			if ( $istitle )
				$return .='<strong>';

			$return .= $cell_data[$c][0];
			if ( $istitle )
				$return .='</strong>';

			$return .= '</td>';
		}
		$return .= '</tr>';
		return $return;
	}
        /**
	 * headerStart() - generates the header code for all themes up to the
	 * closing </head>.
	 * Override any of the methods headerHTMLDeclaration(), headerTitle(),
	 * headerFavIcon(), headerRSS(), headerSearch(), headerCSS(), or
	 * headerJS() to adapt your theme.
	 *
	 * @param	array	$params		Header parameters array
	 */
	function headerStart($params) {
		$this->headerHTMLDeclaration();
		?>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <?php // The 'viewport' bellow fixes: Icorrect iPhone Bootstrap3 Representation: http://stackoverflow.com/questions/19933848/bootstrap-3-not-xs-on-iphone-5 ?>
                <meta name="viewport" content="width=device-width, initial-scale=1" />
<?php if (isset($params['meta-description'])) { ?>
		<meta name="description" content="<?php echo $params['meta-description'] ?>" />
<?php } ?>
<?php if (isset($params['meta-keywords'])) { ?>
		<meta name="keywords" content="<?php echo $params['meta-keywords'] ?>" />
<?php } ?>
		<?php
		plugin_hook('htmlhead', array());
		$this->headerTitle($params);
		$this->headerFavIcon();
		$this->headerRSS();
		$this->headerSearch();
		$this->headerJS();
		$this->headerCSS();
		$this->headerForgepluckerMeta();
		$this->headerLinkedDataAutodiscovery();
		?>
	</head>
		<?php
	}

	/**
	 * headerJS() - creates the JS headers and calls the plugin javascript hook
	 * @todo generalize this
	 */
	function headerJS()
	{
		echo '<script type="text/javascript" src="'. util_make_uri('/js/common.js') .'"></script>';

		plugin_hook("javascript_file", false);

		// invoke the 'javascript' hook for custom javascript addition
		$params = array('return' => false);
		plugin_hook("javascript", $params);
		$javascript = $params['return'];
		if($javascript) {
			echo '<script type="text/javascript">//<![CDATA['."\n";
			echo $javascript;
			echo "\n//]]></script>\n";
		}
		//html_use_tooltips();
		html_use_storage();
		//html_use_coolfieldset();
		//html_use_jqueryui(); // here Old one Uploaded
		echo $this->getJavascripts();
		?>
		<script type="text/javascript">//<![CDATA[
		jQuery(window).load(function(){
			jQuery(".quicknews").hide();
			setTimeout("jQuery('.feedback').hide('slow')", 5000);
			setInterval(function() {
					setTimeout("jQuery('.feedback').hide('slow')", 5000);
				}, 5000);
		});
                $(function(){
                    // hack to remove documents from the SELECT
                    $(".search_select").find('[value=alldocs]').remove(); 
                });
		//]]></script>
		<?php
		        //jquery-1.11.2.min.js caused docman interface to break - commenting out for now - is this needed?
		        //echo "<script type=\"text/javascript\" src=\"/js/jquery-1.11.2.min.js\"></script>\n"; // need new js for SlideShow
		        echo "<script type=\"text/javascript\" src=\"{$this->themeurl}js/bootstrap.min.js\"></script>\n";
                echo "<script type=\"text/javascript\" src=\"{$this->themeurl}js/help_ruler.js\"></script>\n";
                echo "<script type=\"text/javascript\" src=\"{$this->themeurl}js/dropdowns-enhancement.js\"></script>\n";
                // This plugin bring a lot of troubles at the  FireFox
                // I put the code of this pluging at the footer, also add tiny delay.
                echo "<script type=\"text/javascript\" src=\"{$this->themeurl}js/jquery.customSelect.min.js\"></script>\n";
                echo "<script type=\"text/javascript\" src=\"{$this->themeurl}js/jquery.popupWindow.js\"></script>\n";
				echo "<script type=\"text/javascript\" src=\"{$this->themeurl}js/simple-expand.js\"></script>\n";
				
		?>
		
		<script type="text/javascript">
			$(function () {
				if ($('[data-toggle="tooltip"]').length) {
					$('[data-toggle="tooltip"]').tooltip();
				}
				if ($('[data-toggle="popover"]').length) {
					$('[data-toggle="popover"]').popover();
				}
			});
		</script>
		
        <?php
	}

     //<============================== Function REVRITED from Navigation.class.php ======================================>
     /**
      * Revrited from Navigation.class.php
      * Reason: Change "New Account" to "Sign Up" and switch places with Log In
      * * Get an array of the user links (Login/Logout/My Account/Register) with the following structure:
      *	$result['titles']: list of the titles. $result['urls']: list of the urls.
      */
	function getUserLinks() {
		$res = array();
		if (!session_loggedin()) {
			// Show login menu if user is not logged in.
			$url = '/account/login.php';
			if(getStringFromServer('REQUEST_METHOD') != 'POST') {
				$url .= '?return_to=';
				$url .= urlencode(getStringFromServer('REQUEST_URI'));
			}
                        
                        if (!forge_get_config ('user_registration_restricted')) {
				//$res['titles'][] = _('New Account');
                                $res['titles'][] = 'Sign Up';
				$res['urls'][] = util_make_uri('/account/register.php');
			}
                        
			$res['titles'][] = _('Log In');
			$res['urls'][] = util_make_uri($url);
		}
		return $res;
	}   
        
        
     /**
     * Here Rearrange SearchBox from the  Navigation.class.php
     * 
     * 1 - search_box_inputs [div wrapper for input]
     * 2 - search_box_select [div wrapper for box select]
     * 3 - search_box_advanced_search [span wrapper for advansed search link]
     * (3) - out of the 'form > div'
     * 4 - search_box_words add class to input
     * @global type $words
     * @global type $forum_id
     * @global type $group_id
     * @global type $group_project_id
     * @global type $atid
     * @global int $exact
     * @global type $type_of_search
     * @return string
     */
    function getSearchBox() {
	global $words, $forum_id, $group_id, $group_project_id, $atid, $exact, $type_of_search;

	if (get_magic_quotes_gpc()) {
		$defaultWords = stripslashes($words);
	}
	else {
		$defaultWords = $words;
	}
	$defaultWords = htmlspecialchars($defaultWords);

	// Look up current type of search.
	$isSearchProj = true;
	// Get URL.
	$url = $_SERVER["REQUEST_URI"];
	if (isset($url)) {
		$idx = stripos($url, "type_of_search=");
		$strTypeSearch = substr($url, $idx + 15); 
		if (stripos($strTypeSearch, "soft") === 0) {
			// Projects search.
			$isSearchProj = true;
		}
		else if (stripos($strTypeSearch, "people") === 0) {
			// People search.
			$isSearchProj = false;
		}
	}

	$res = "
<form id='searchBox' action='/search/search.php' method='get'>
	<div class='search_box_inputs'>
		<input type='text' id='searchBox-words' 
			placeholder='Search for' name='srch' 
			value='$defaultWords' required='required' />
		<input type='submit' name='Search' value='Search' />
	</div>
	<span class='search_box_select'>
		<input type='radio' name='type_of_search' value='soft' ";
		if ($isSearchProj === true) {
			$res .= "checked='checked'";
		}
		$res .= " />&nbsp;<label>Projects</label>&nbsp;&nbsp;";

		$res .= " <input type='radio' name='type_of_search' value='people' ";
		if ($isSearchProj === false) {
			$res .= "checked='checked'";
		}
		$res .= " />&nbsp;<label>People</label>
	</span>
</form>
	";
                
	return $res;
	}
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
