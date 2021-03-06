<?php
/**
 * Base layout class.
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2010 - Alain Peyrat
 * Copyright 2010-2011, Franck Villaume - Capgemini
 * Copyright 2010-2012, Alain Peyrat - Alcatel-Lucent
 * Copyright © 2011 Thorsten Glaser – tarent GmbH
 * Copyright 2011 - Marc-Etienne Vargenau, Alcatel-Lucent
 * Copyright 2012-2015, Franck Villaume - TrivialDev
 * Copyright 2005-2019, SimTK Team
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

/**
 *
 * Extends the basic Error class to add HTML functions
 * for displaying all site dependent HTML, while allowing
 * extendibility/overriding by themes via the Theme class.
 *
 * Make sure browser.php is included _before_ you create an instance
 * of this object.
 *
 */

require_once $gfcommon.'include/constants.php';
require_once $gfcommon.'include/FusionForge.class.php';
require_once $gfcommon.'include/Navigation.class.php';

class Layout extends FFError {

	/**
	 * Which doctype to use. Can be configured in the
	 * constructor. If set to 'strict', headerHTMLDeclaration will
	 * create a doctype definition that uses the strict doctype,
	 * otherwise it will use the transitional doctype.
	 * @var string $doctype
	 */
	var $doctype = 'transitional';

	/**
	 * The default main page content
	 * @var	string	$rootindex
	 */
	var $rootindex = 'index_std.php';

	/**
	 * The base directory of the theme in the servers file system
	 * @var	string	$themedir
	 */
	var $themedir;

	/**
	 * The base url of the theme
	 * @var	string	$themeurl
	 */
	var $themeurl;

	/**
	 * The base directory of the image files in the servers file system
	 * @var	string	$imgdir
	 */
	var $imgdir;

	/**
	 * The base url of the image files
	 * @var	string	$imgbaseurl
	 */
	var $imgbaseurl;

	/**
	 * The base directory of the js files in the servers file system
	 * @var	string	$jsdir
	 */
	var $jsdir;

	/**
	 * The base url of the js files
	 * @var	string	$jsbaseurl
	 */
	var $jsbaseurl;

	/**
	 * The navigation object that provides the basic links. Should
	 * not be modified.
	 */
	var $navigation;

	/**
	 * The color bars in pm reporting
	 */
	var $COLOR_LTBACK1 = '#C0C0C0';

	var $js = array();
	var $js_min = array();
	var $javascripts = array();
	var $css = array();
	var $css_min = array();
	var $stylesheets = array();

	/**
	 * Layout() - Constructor
	 */
	//function Layout() {
	function __construct() {
		// parent constructor
		parent::__construct();

		$this->navigation = new Navigation();

		// determine rootindex
		if ( file_exists(forge_get_config('custom_path') . '/index_std.php') ) {
			$this->rootindex = forge_get_config('custom_path') . '/index_std.php';
		} else {
			$this->rootindex = $GLOBALS['gfwww'].'index_std.php';
		}

		// determine theme{dir,url}
		$this->themedir = forge_get_config('themes_root') . '/' . forge_get_config('default_theme') . '/';
		if (!file_exists ($this->themedir)) {
			html_error_top(_("Cannot find theme directory!"));
			return;
		}
		$this->themeurl = util_make_uri('themes/' . forge_get_config('default_theme') . '/');

		// determine {css,img,js}{url,dir}
		if (file_exists ($this->themedir . 'images/')) {
			$this->imgdir = $this->themedir . 'images/';
			$this->imgbaseurl = $this->themeurl . 'images/';
		} else {
			$this->imgdir = $this->themedir;
			$this->imgbaseurl = $this->themeurl;
		}

		if (file_exists ($this->themedir . 'js/')) {
			$this->jsdir = $this->themedir . 'js/';
			$this->jsbaseurl = $this->themeurl . 'js/';
		} else {
			$this->jsdir = $this->themedir;
			$this->jsbaseurl = $this->themeurl;
		}

		$this->addStylesheet('/themes/css/fusionforge.css');

	}

	/**
	 * Build the list of required Javascript files.
	 *
	 * If js file is found, then a timestamp is automatically added to ensure
	 * that file is cached only if not changed.
	 *
	 * @param string $js path to the JS file
	 */
	function addJavascript($js) {
		// If a minified version of the javascript is available, then use it.
		if (isset($this->js_min[$js])) {
			$js = $this->js_min[$js];
		}
		if ($js && !isset($this->js[$js])) {
			$this->js[$js] = true;
			$filename = $GLOBALS['fusionforge_basedir'].'/www'.$js;
			if (file_exists($filename)) {
				$js .= '?'.date ("U", filemtime($filename));
			} else {
				$filename = str_replace('/scripts/', $GLOBALS['fusionforge_basedir'].'/vendor/', $js);
				if (file_exists($filename)) {
					$js .= '?'.date ("U", filemtime($filename));
				}
			}
			$this->javascripts[] = $js;
		}
	}

	function addStylesheet($css, $media='') {
		if (isset($this->css_min[$css])) {
			$css = $this->css_min[$css];
		}
		if (!isset($this->css[$css])) {
			$this->css[$css] = true;
			$filename = $GLOBALS['fusionforge_basedir'].'/www'.$css;
			if (file_exists($filename)) {
				$css .= '?'.date ("U", filemtime($filename));
			} else {
				$filename = str_replace('/scripts/', $GLOBALS['fusionforge_basedir'].'/vendor/', $css);
				if (file_exists($filename)) {
					$css .= '?'.date ("U", filemtime($filename));
				}
			}
			$this->stylesheets[] = array('css' => $css, 'media' => $media);
		}
	}

	/**
	 * getJavascripts - include javascript in html page. check to load only once the file
	 */
	function getJavascripts() {
		$code = '';
		foreach ($this->javascripts as $js) {
			$code .= html_e('script', array('type' => 'text/javascript', 'src' => util_make_uri($js)), '', false);
		}
		$this->javascripts = array();
		return $code;
	}

	/**
	 * getStylesheets - include stylesheet in html page. check to load only once the file
	 */
	function getStylesheets() {
		$code = '';
		foreach ($this->stylesheets as $c) {
			if ($c['media']) {
				$code .= html_e('link', array('rel' => 'stylesheet', 'type' => 'text/css', 'href' => util_make_uri($c['css']), 'media' => $c['media']));
			} else {
				$code .= html_e('link', array('rel' => 'stylesheet', 'type' => 'text/css', 'href' => util_make_uri($c['css'])));
			}
		}
		$this->stylesheets = array();
		return $code;
	}

	/**
	 * header() - generates the complete header of page by calling
	 * headerStart() and bodyHeader().
	 */
	function header($params) {
		$this->headerStart($params);
		echo html_ao('body');
		$this->bodyHeader($params);
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
		echo html_ao('head');
		echo html_e('meta', array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=utf-8'));
		if (isset($params['meta-description'])) {
			echo html_e('meta', array('name' => 'description', 'content' => $params['meta-description']));
		}
		if (isset($params['meta-keywords'])) {
			echo html_e('meta', array('name' => 'keywords', 'content' => $params['meta-keywords']));
		}
		plugin_hook('htmlhead');
		$this->headerTitle($params);
		$this->headerFavIcon();
		$this->headerRSS();
		$this->headerSearch();
		$this->headerJS();
		$this->headerCSS();
		$this->headerForgepluckerMeta();
		$this->headerLinkedDataAutodiscovery();
		echo html_ac(html_ap() -1);
	}

	/**
	 * headerHTMLDeclaration() - generates the HTML declaration, i.e. the
	 * XML declaration, the doctype definition, and the opening <html>.
	 *
	 */
	function headerHTMLDeclaration() {
		global $sysDTDs, $sysXMLNSs;

		print '<' . '?xml version="1.0" encoding="utf-8"?>'."\n";
		if (!util_ifsetor($this->doctype) || !util_ifsetor($sysDTDs[$this->doctype])) {
			$this->doctype = 'transitional';
		}
		echo $sysDTDs[$this->doctype]['doctype'] . "\n";
		echo '<html xml:lang="' . _('en') . '" lang="' . _('en') .
		    '" ' . $sysXMLNSs . ">\n";
	}

	/**
	 * headerTitle() - creates the <title> header
	 *
	 * @param	array	$params		Header parameters array
	 */
	function headerTitle($params) {
		echo $this->navigation->getTitle($params);
	}

	/**
	 * headerFavIcon() - creates the favicon <link> headers.
	 *
	 */
	function headerFavIcon() {
		echo $this->navigation->getFavIcon();
	}

	/**
	 * headerRSS() - creates the RSS <link> headers.
	 *
	 */
	function headerRSS() {
		echo $this->navigation->getRSS();
	}

	/**
	 * headerSearch() - creates the search <link> header.
	 *
	 */
	function headerSearch() {
		echo html_e('link', array('rel' => "search", 'title' => forge_get_config('forge_name'),
					'href' => util_make_uri('/export/search_plugin.php'),
					'type' => 'application/opensearchdescription+xml'));
	}

	/**
	 * Create the CSS headers for all cssfiles in $cssfiles and
	 * calls the plugin cssfile hook.
	 */
	function headerCSS() {
		plugin_hook ('cssfile',$this);
		echo $this->getStylesheets();
	}

	/**
	 * headerJS() - creates the JS headers and calls the plugin javascript hook
	 * @todo generalize this
	 */
	function headerJS() {
		echo html_e('script', array('type' => 'text/javascript', 'src' => util_make_uri('/js/common.js')), '', false);
		echo '	<script type="text/javascript">/* <![CDATA[ */';
		plugin_hook ("javascript");
		echo '
			/* ]]> */</script>';
		plugin_hook ("javascript_file");
		echo $this->getJavascripts();

		// invoke the 'javascript' hook for custom javascript addition
		$params = array('return' => false);
		plugin_hook("javascript",$params);
		$javascript = $params['return'];
		if($javascript) {
			echo '<script type="text/javascript">';
			echo $javascript;
			echo '
			</script>';
		}
	}

	/**
 	 * headerLinkedDataAutodiscovery() - creates the link+alternate links to alternate
 	 * 		representations for Linked Data autodiscovery
 	 */
	function headerLinkedDataAutodiscovery() {

		// retrieve the script's prefix
		$script_name = getStringFromServer('SCRIPT_NAME');
		$end = strpos($script_name,'/',1);
		if($end) {
			$script_name = substr($script_name,0,$end);
		}

		// Only activated for /projects, /users or /softwaremap for the moment
		if ($script_name == '/projects' || $script_name == '/users' || $script_name == '/softwaremap') {

			$php_self = getStringFromServer('PHP_SELF');

			// invoke the 'alt_representations' hook to add potential 'alternate' links (useful for Linked Data)
			// cf. http://www.w3.org/TR/cooluris/#linking
			$params = array('script_name' => $script_name,
							'php_self' => $php_self,
							'return' => array());

			plugin_hook_by_reference('alt_representations', $params);

			foreach($params['return'] as $link) {
				echo "                        $link"."\n";
			}
		}
	}

	function headerForgepluckerMeta() {
		/*-
		 * Forge-Identification Meta Header, Version 1.0
		 * cf. http://home.gna.org/forgeplucker/forge-identification.html
		 */
		$ff = new FusionForge();
		echo html_e('meta', array('name' => 'Forge-Identification', 'content' => $ff->software_name.':'.$ff->software_version));
	}

	function bodyHeader($params){
		?>
			<div class="header">
			<table class="fullwidth" id="headertable">
			<tr>
			<td><?php util_make_link('/', html_image('logo.png',198,52,array('border'=>'0'))); ?></td>
			<td><?php $this->searchBox(); ?></td>
			<td align="right"><?php
			$items = $this->navigation->getUserLinks();
		for ($j = 0; $j < count($items['titles']); $j++) {
			echo util_make_link($items['urls'][$j], $items['titles'][$j], array('class'=>'lnkutility'), true);
		}

		$params['template'] = ' {menu}';
		plugin_hook('headermenu', $params);

		$this->quickNav();

		plugin_hook('message', array());

		?></td>
		<td></td>
	</tr>

</table>

<table class="fullwidth">

	<tr>
		<td></td>
		<td colspan="3">

<?php $this->outerTabs($params); ?>

		</td>
		<td></td>
	</tr>

	<tr>
		<td class="align-left toptab" width="9"><img src="<?php echo $this->imgbaseurl; ?>tabs/topleft.png" height="9" width="9" alt="" /></td>
		<td class="toptab" width="30"><img src="<?php echo $this->imgbaseurl; ?>clear.png" width="30" height="1" alt="" /></td>
		<td class="toptab"><img src="<?php echo $this->imgbaseurl; ?>clear.png" width="1" height="1" alt="" /></td>
		<td class="toptab" width="30"><img src="<?php echo $this->imgbaseurl; ?>clear.png" width="30" height="1" alt="" /></td>
		<td class="align-right toptab" width="9"><img src="<?php echo $this->imgbaseurl; ?>tabs/topright.png" height="9" width="9" alt="" /></td>
	</tr>

	<tr>

		<!-- Outer body row -->

		<td class="toptab"><img src="<?php echo $this->imgbaseurl; ?>clear.png" width="10" height="1" alt="" /></td>
		<td class="top toptab" width="99%" colspan="3">

			<!-- Inner Tabs / Shell -->

			<table class="fullwidth">
<?php


if (isset($params['group']) && $params['group']) {

			?>
			<tr>
				<td></td>
				<td>
				<?php $this->projectTabs($params['toptab'],$params['group']); ?>
				</td>
				<td></td>
			</tr>
			<?php

}

?>
			<tr>
				<td class="align-left projecttab" width="9"><img src="<?php echo $this->imgbaseurl; ?>tabs/topleft-inner.png" height="9" width="9" alt="" /></td>
				<td class="projecttab" ><img src="<?php echo $this->imgbaseurl; ?>clear.png" width="1" height="1" alt="" /></td>
				<td class="align-right projecttab"  width="9"><img src="<?php echo $this->imgbaseurl; ?>tabs/topright-inner.png" height="9" width="9" alt="" /></td>
			</tr>

			<tr>
				<td class="projecttab" ><img src="<?php echo $this->imgbaseurl; ?>clear.png" width="10" height="1" alt="" /></td>
				<td style="width:99%" class="top projecttab">

	<?php

	}

	function footer($params = array()) {

	?>

			<!-- end main body row -->

				</td>
				<td width="10" class="footer3" ><img src="<?php echo $this->imgbaseurl; ?>clear.png" width="2" height="1" alt="" /></td>
			</tr>
			<tr>
				<td class="align-left footer1" width="9"><img src="<?php echo $this->imgbaseurl; ?>tabs/bottomleft-inner.png" height="11" width="11" alt="" /></td>
				<td class="footer3"><img src="<?php echo $this->imgbaseurl; ?>clear.png" width="1" height="1" alt="" /></td>
				<td class="align-right footer1" width="9"><img src="<?php echo $this->imgbaseurl; ?>tabs/bottomright-inner.png" height="11" width="11" alt="" /></td>
			</tr>
			</table>

		<!-- end inner body row -->

		</td>
		<td width="10" class="footer2"><img src="<?php echo $this->imgbaseurl; ?>clear.png" width="2" height="1" alt="" /></td>
	</tr>
	<tr>
		<td class="align-left footer2" width="9"><img src="<?php echo $this->imgbaseurl; ?>tabs/bottomleft.png" height="9" width="9" alt="" /></td>
		<td class="footer2" colspan="3"><img src="<?php echo $this->imgbaseurl; ?>clear.png" width="1" height="1" alt="" /></td>
		<td class="align-right footer2" width="9"><img src="<?php echo $this->imgbaseurl; ?>tabs/bottomright.png" height="9" width="9" alt="" /></td>
	</tr>
</table>
<?php
		$this->footerEnd();
	}

	function footerEnd() { ?>

		<!-- PLEASE LEAVE "Powered By FusionForge" on your site -->
		<div class="align-right">
		<?php echo $this->navigation->getPoweredBy(); ?>
		</div>

		<?php echo $this->navigation->getShowSource();

		plugin_hook('webanalytics_url');

		echo html_ac(html_ap() -1);
		echo "</html>\n";
	}

	function getRootIndex() {
		return $this->rootindex;
	}

	/**
	 * boxTop() - Top HTML box.
	 *
	 * @param	string	$title	Box title
	 * @return	string	the html code
	 */
	function boxTop($title) {
		return '
			<!-- Box Top Start -->

			<table class="fullwidth" style="background:url('.$this->imgroot.'vert-grad.png)">
			<tr class="align-center">
			<td class="top align-right" width="10" style="background:url('.$this->imgbaseurl.'box-topleft.png)"><img src="'.$this->imgbaseurl.'clear.png" width="10" height="20" alt="" /></td>
			<td class="fullwidth" style="background:url('.$this->imgbaseurl.'box-grad.png)"><span class="titlebar">'.$title.'</span></td>
			<td class="top" width="10" style="background:url('.$this->imgbaseurl.'box-topright.png)"><img src="'.$this->imgbaseurl.'clear.png" width="10" height="20" alt="" /></td>
			</tr>
			<tr>
			<td colspan="3">
			<table cellspacing="2" cellpadding="2" class="fullwidth">
			<tr class="align-left">
			<td colspan="2">

			<!-- Box Top End -->';
	}

	/**
	 * boxMiddle() - Middle HTML box.
	 *
	 * @param	string	$title	Box title
	 * @return	string	The html code
	 */
	function boxMiddle($title) {
		return '
			<!-- Box Middle Start -->
			</td>
			</tr>
			<tr class="align-center">
			<td colspan="2" style="background:url('.$this->imgbaseurl.'box-grad.png)"><span class="titlebar">'.$title.'</span></td>
			</tr>
			<tr class="align-left">
			<td colspan="2">
			<!-- Box Middle End -->';
	}

	/**
	 * boxBottom() - Bottom HTML box.
	 *
	 * @return	string	the html code
	 */
	function boxBottom() {
		return '
			<!-- Box Bottom Start -->
			</td>
			</tr>
			</table>
			</td>
			</tr>
			</table><br />
			<!-- Box Bottom End -->';
	}

	/**
	 * boxGetAltRowStyle() - Get an alternating row style for tables.
	 *
	 * @param	int	$i		Row number
	 * @param	bool	$classonly	Return class name only
	 * @return	string	the class code
	 */
	function boxGetAltRowStyle($i, $classonly = false) {
		if ($i % 2 == 0)
			$ret = 'altRowStyleEven';
		else
			$ret = 'altRowStyleOdd';
		if ($classonly)
			return $ret;
		else
			return 'class="'.$ret.'"';
	}

	/**
	 * listTableTop() - Takes an array of titles and builds the first row of a new table.
	 *
	 * @param	array	$titleArray		The array of titles
	 * @param	array	$linksArray		The array of title links
	 * @param	string	$class			The css classes to add (optional)
	 * @param	string	$id			The id of the table (needed by sortable for example)
	 * @param	array	$thClassArray		specific class for th column
	 * @param	array	$thTitleArray		specific title for th column
	 * @param	array	$thOtherAttrsArray	optional other html attributes for the th
	 * @return	string	the html code
	 */
	function listTableTop($titleArray = array(), $linksArray = array(), $class = '', $id = '', $thClassArray = array(), $thTitleArray = array(), $thOtherAttrsArray = array()) {
		$attrs = array('class' => 'listing');
		$args = '';
		if ($class) {
			$attrs['class'] .= ' '.$class;
		} else {
			$attrs['class'] .= ' full';
		}
		if ($id) {
			$attrs['id'] = $id;
		}
		$return = html_ao('table', $attrs);

		if (count($titleArray)) {
			$ap = html_ap();
			$return .= html_ao('thead');
			$return .= html_ao('tr');

			$count = count($titleArray);
			for ($i = 0; $i < $count; $i++) {
				$thAttrs = array();
				if ($thOtherAttrsArray && isset($thOtherAttrsArray[$i])) {
					$thAttrs = $thOtherAttrsArray[$i];
				}
				if ($thClassArray && isset($thClassArray[$i])) {
					$thAttrs['class'] = $thClassArray[$i];
				}
				if ($thTitleArray && isset($thTitleArray[$i])) {
					$thAttrs['title'] = $thTitleArray[$i];
				}
				$cell = $titleArray[$i];
				if ($linksArray && isset($linksArray[$i])) {
					$cell = util_make_link($linksArray[$i], $titleArray[$i]);
				}
				$return .= html_e('th', $thAttrs, $cell, false);
			}
			$return .= html_ac($ap);
		}
		$return .= html_ao('tbody');
		return $return;
	}

	function listTableBottom() {
		return html_ac(html_ap() -2);
	}

	function outerTabs($params) {
		$menu = $this->navigation->getSiteMenu();
		echo $this->tabGenerator($menu['urls'], $menu['titles'], $menu['tooltips'], false, $menu['selected'], '');
	}

	/**
	 * Prints out the quicknav menu, contained here in case we
	 * want to allow it to be overridden.
	 */
	function quickNav() {
		if (!session_loggedin()) {
			return;
		} else {
			// get all projects that the user belongs to
			$groups = session_get_user()->getGroups();

			if (count($groups) < 1) {
				return;
			} else {
				sortProjectList($groups);

				$result = html_ao('form', array('id' => 'quicknavform', 'name' => 'quicknavform', 'action' => ''));
				$result .= html_ao('div');
				$result .= html_ao('select', array('name' => 'quicknav', 'id' => 'quicknav', 'onchange' => 'location.href=document.quicknavform.quicknav.value'));
				$result .= html_e('option', array('value' => ''), _('Quick Jump To...'), false);

				foreach ($groups as $g) {
					$group_id = $g->getID();
					$menu = $this->navigation->getProjectMenu($group_id);
					$result .= html_e('option', array('value' => $menu['starturl']), $menu['name'], true);
					for ($j = 0; $j < count($menu['urls']); $j++) {
						$result .= html_e('option', array('value' => $menu['urls'][$j]), '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$menu['titles'][$j], true);
						if (@$menu['adminurls'][$j]) {
							$result .= html_e('option', array('value' => $menu['adminurls'][$j]), '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'._('Admin'), true);
						}
					}
				}
				$result .= html_ac(html_ap() - 3);
			}
			return $result;
		}
	}

	/**
	 * projectTabs() - Prints out the project tabs, contained here in case
	 * we want to allow it to be overriden.
	 *
	 * @param	string	$toptab		Is the tab currently selected
	 * @param	string	$group_id	Is the group we should look up get title info
	 */
	function projectTabs($toptab, $group_id) {
		/* This menu is for the project menu - will not need this since is is recreated - tod hing */
		// get group info using the common result set
		//$menu = $this->navigation->getProjectMenu($group_id, $toptab);
		//echo $this->tabGenerator($menu['urls'], $menu['titles'], $menu['tooltips'], true, $menu['selected'], 'white');
	}

	function tabGenerator($TABS_DIRS, $TABS_TITLES, $TABS_TOOLTIPS, $nested=false, $selected=false, $sel_tab_bgcolor='white', $total_width='100%') {

		$count=count($TABS_DIRS);
		$width=intval((100/$count));

		$return = '';
		$return .= '
			<!-- start tabs -->
			<table class="tabGenerator" ';

		if ($total_width != '100%') {
			$return .= 'style="width:' . $total_width . ';"';
		}
		$return .= ">\n";
		$return .= '<tr>';
		if ($nested) {
			$inner='bottomtab';
		} else {
			$inner='toptab';
		}
		$rowspan = '';
		for ($i=0; $i<$count; $i++) {
			if ($i == 0) {
				//
				//	this is the first tab, choose an image with end-name
				//
				$wassel=false;
				$issel=($selected==$i);
				$bgimg=(($issel)?'theme-'.$inner.'-selected-bg.png':'theme-'.$inner.'-notselected-bg.png');
				//		$rowspan=(($issel)?'rowspan="2" ' : '');

				$return .= '
					<td '.$rowspan.'class="top" width="10" style="background:url('.$this->imgbaseurl . 'theme-'.$inner.'-end-'.(($issel) ? '' : 'not').'selected.png)">'.
					'<img src="'.$this->imgbaseurl . 'clear.png" height="25" width="10" alt="" /></td>'.
					'<td '.$rowspan.'style="background:url('.$this->imgbaseurl . $bgimg.')" width="'.$width.'%" align="center">'.util_make_link ($TABS_DIRS[$i],$TABS_TITLES[$i],array('class'=>(($issel)?'tabsellink':'tablink')),true).'</td>';
			} elseif ($i==$count-1) {
				//
				//	this is the last tab, choose an image with name-end
				//
				$wassel=($selected==$i-1);
				$issel=($selected==$i);
				$bgimg=(($issel)?'theme-'.$inner.'-selected-bg.png':'theme-'.$inner.'-notselected-bg.png');
				//		$rowspan=(($issel)?'rowspan="2" ' : '');
				//
				//	Build image between current and prior tab
				//
				$return .= '
					<td '.$rowspan.'colspan="2" class="top" width="20" style="background:url('.$this->imgbaseurl . 'theme-'.$inner.'-'.(($wassel) ? '' : 'not').'selected-'.(($issel) ? '' : 'not').'selected.png)">'.
					'<img src="'.$this->imgbaseurl . 'clear.png" height="2" width="20" alt="" /></td>'.
					'<td '.$rowspan.'style="background:url('.$this->imgbaseurl . $bgimg.')" width="'.$width.'%" align="center">'.util_make_link($TABS_DIRS[$i], $TABS_TITLES[$i], array('class'=>(($issel)?'tabsellink':'tablink')), true).'</td>';
				//
				//	Last graphic on right-side
				//
				$return .= '
					<td '.$rowspan.'class="top" width="10" style="background:url('.$this->imgbaseurl . 'theme-'.$inner.'-'.(($issel) ? '' : 'not').'selected-end.png)">'.
					'<img src="'.$this->imgbaseurl . 'clear.png" height="2" width="10" alt="" /></td>';

			} else {
				//
				//	middle tabs
				//
				$wassel=($selected==$i-1);
				$issel=($selected==$i);
				$bgimg=(($issel)?'theme-'.$inner.'-selected-bg.png':'theme-'.$inner.'-notselected-bg.png');
				//		$rowspan=(($issel)?'rowspan="2" ' : '');
				//
				//	Build image between current and prior tab
				//
				$return .= '
					<td '.$rowspan.'colspan="2" class="top" width="20" style="background:url('.$this->imgbaseurl . 'theme-'.$inner.'-'.(($wassel) ? '' : 'not').'selected-'.(($issel) ? '' : 'not').'selected.png)">'.
					'<img src="'.$this->imgbaseurl . 'clear.png" height="2" width="20" alt="" /></td>'.
					'<td '.$rowspan.'style="background:url('.$this->imgbaseurl . $bgimg.')" width="'.$width.'%" align="center">'.util_make_link($TABS_DIRS[$i], $TABS_TITLES[$i], array('class'=>(($issel)?'tabsellink':'tablink')), true).'</td>';

			}
		}
		$return .= '</tr>';

		//
		//	Building a bottom row in this table, which will be darker
		//
		if ($selected == 0) {
			$beg_cols=0;
			$end_cols=((count($TABS_DIRS)*3)-3);
		} elseif ($selected == (count($TABS_DIRS)-1)) {
			$beg_cols=((count($TABS_DIRS)*3)-3);
			$end_cols=0;
		} else {
			$beg_cols=($selected*3);
			$end_cols=(((count($TABS_DIRS)*3)-3)-$beg_cols);
		}
		$return .= '<tr>';
		if ($beg_cols > 0) {
			$return .= '<td colspan="'.$beg_cols.'" height="1" class="notSelTab"><img src="'.$this->imgbaseurl.'clear.png" height="1" width="10" alt="" /></td>';
		}
		$return .= '<td colspan="3" height="1" class="selTab"><img src="'.$this->imgbaseurl.'clear.png" height="1" width="10" alt="" /></td>';
		if ($end_cols > 0) {
			$return .= '<td colspan="'.$end_cols.'" height="1" class="notSelTab"><img src="'.$this->imgbaseurl.'clear.png" height="1" width="10" alt="" /></td>';
		}
		$return .= '</tr>';

		return $return.'
			</table>

			<!-- end tabs -->
			';
	}

	function searchBox() {
		return $this->navigation->getSearchBox();
	}

	/**
	 * beginSubMenu() - Opening a submenu.
	 *
	 * @return	string	Html to start a submenu.
	 */
	function beginSubMenu() {
		$return = '
			<p><strong>';
		return $return;
	}

	/**
	 * endSubMenu() - Closing a submenu.
	 *
	 * @return	string	Html to end a submenu.
	 */
	function endSubMenu() {
		$return = '</strong></p>';
		return $return;
	}

	/**
	 * printSubMenu() - Takes two array of titles and links and builds the contents of a menu.
	 *
	 * @param	array	$title_arr	The array of titles.
	 * @param	array	$links_arr	The array of title links.
	 * @param	array	$attr_arr	The array of string for title attributes.
	 * @return	string	Html to build a submenu.
	 */
	function printSubMenu($title_arr, $links_arr, $attr_arr) {
		$count=count($title_arr);
		$count--;

		$return = '';
		for ($i=0; $i<$count; $i++) {
			$return .= util_make_link($links_arr[$i],$title_arr[$i],$attr_arr[$i]). $this->subMenuSeparator();
		}
		$return .= util_make_link($links_arr[$i],$title_arr[$i],$attr_arr[$i]);
		return $return;
	}

	/**
	 * subMenuSeparator() - returns the separator used between submenus
	 *
	 * @return	string	Html to build a submenu separator.
	 */
	function subMenuSeparator() {
		return '';
	}

	/**
	 * subMenu() - Takes two array of titles and links and build a menu.
	 *
	 * @param	array	$title_arr	The array of titles.
	 * @param	array	$links_arr	The array of title links.
	 * @param	array	$attr_arr	The array of string for title attributes.
	 * @return	string	Html to build a submenu.
	 */
	function subMenu($title_arr, $links_arr, $attr_arr = array()) {
		$return  = $this->beginSubMenu();
		$return .= $this->printSubMenu($title_arr, $links_arr, $attr_arr);
		$return .= $this->endSubMenu();
		return $return;
	}

	/**
	 * multiTableRow() - create a multilevel row in a table
	 *
	 * @param	array	$row_attrs	the row attributes
	 * @param	array	$cell_data	the array of cell data, each element is an array,
	 *					the first item being the text,
	 *					the subsequent items are attributes (dont include
	 *					the bgcolor for the title here, that will be
	 *					handled by $istitle
	 * @param	bool	$istitle	is this row part of the title ?
	 * @return	string	the html code
	 */
	function multiTableRow($row_attrs, $cell_data, $istitle = false) {
		$ap = html_ap();
		if ( $istitle ) {
			(isset($row_attrs['class'])) ? $row_attrs['class'] .= ' align-center multiTableRowTitle' : $row_attrs['class'] = 'align-center multiTableRowTitle';
			$row_attrs['class'] .= '';
		}
		$return = html_ao('tr', $row_attrs);
		for ( $c = 0; $c < count($cell_data); $c++ ) {
			$locAp = html_ap();
			$cellAttrs = array();
			foreach (array_slice($cell_data[$c],1) as $k => $v) {
				$cellAttrs[$k] = $v;
			}
			$return .= html_ao('td', $cellAttrs);
			if ( $istitle ) {
				$return .= html_ao('span', array('class' => 'multiTableRowTitle'));
			}
			$return .= $cell_data[$c][0];
			if ( $istitle ) {
				$return .= html_ac(html_ap() -1);
			}
			$return .= html_ac($locAp);
		}
		$return .= html_ac($ap);
		return $return;
	}

	/**
	 * feedback() - returns the htmlized feedback string when an action is performed.
	 *
	 * @param	string	$feedback	feedback string
	 * @return	string	htmlized feedback
	 */
	function feedback($feedback, $isStripTags=true) {
		if (!$feedback) {
			return '';
		} else {
			// Decode message first; otherwise, tags like <br/> 
			// are shown as-is but formatting does not function.
			$feedback = htmlspecialchars_decode($feedback);

//			return '<p class="feedback">'.strip_tags($feedback, '<br>').'</p>';

			// Message DIV.
			$theMsg = '<div class="warning_msg" style="padding:8px;border:1px dotted;margin-top:12px;margin-bottom:12px;line-height:18px;">';

			// Left-justified message.
			if ($isStripTags === true) {
				$theMsg .= '<div style="float:left;">' . strip_tags($feedback, '<br>') . '</div>';
			}
			else {
				$theMsg .= '<div style="float:left;">' . $feedback . '</div>';
			}

			// 'X' to close.
			$msgX = '<div style="float:right;" onclick="'. 
				"$('.warning_msg').hide('slow');" . 
				'">&nbsp;&nbsp;&nbsp;&nbsp;X&nbsp;&nbsp;&nbsp;&nbsp;</div>';

			$theMsg .= $msgX;

			// Clear formatting.
			$theMsg .= '<div style="clear: both;"></div>';

			$theMsg .= '</div>';

			return $theMsg;
		}
	}
	/**
	 * warning_msg() - returns the htmlized warning string when an action is performed.
	 *
	 * @param	string	$msg	msg string
	 * @return	string	htmlized warning
	 */
	function warning_msg($msg, $isStripTags=true) {
		if (!$msg) {
			return '';
		} else {
			// Decode message first; otherwise, tags like <br/> 
			// are shown as-is but formatting does not function.
			$msg = htmlspecialchars_decode($msg);

//			return '<p class="warning_msg">'.strip_tags($msg, '<br>').'</p>';

			// Message DIV.
			$theMsg = '<div class="warning_msg" style="padding:8px;border:1px dotted;margin-top:12px;margin-bottom:12px;line-height:18px;">';

			// Left-justified message.
			if ($isStripTags === true) {
				$theMsg .= '<div style="float:left;">' . strip_tags($msg, '<br>') . '</div>';
			}
			else {
				$theMsg .= '<div style="float:left;">' . $msg . '</div>';
			}

			// 'X' to close.
			$msgX = '<div style="float:right;" onclick="'. 
				"$('.warning_msg').hide('slow');" . 
				'">&nbsp;&nbsp;&nbsp;&nbsp;X&nbsp;&nbsp;&nbsp;&nbsp;</div>';

			$theMsg .= $msgX;

			// Clear formatting.
			$theMsg .= '<div style="clear: both;"></div>';

			$theMsg .= '</div>';

			return $theMsg;
		}
	}

	/**
	 * error_msg() - returns the htmlized error string when an action is performed.
	 *
	 * @param	string	$msg	msg string
	 * @return	string	htmlized error
	 */
	function error_msg($msg, $isStripTags=true) {
		if (!$msg) {
			return '';
		} else {
			// Decode message first; otherwise, tags like <br/> 
			// are shown as-is but formatting does not function.
			$msg = htmlspecialchars_decode($msg);

//			return '<p class="error">'.strip_tags($msg, '<br>')."</p>\n";

			// Message DIV.
			$theMsg = '<div class="warning_msg" style="padding:8px;border:1px dotted;margin-top:12px;margin-bottom:12px;line-height:18px;">';

			// Left-justified message.
			if ($isStripTags === true) {
				$theMsg .= '<div style="float:left;">' . strip_tags($msg, '<br>') . '</div>';
			}
			else {
				$theMsg .= '<div style="float:left;">' . $msg . '</div>';
			}

			// 'X' to close.
			$msgX = '<div style="float:right;" onclick="'. 
				"$('.warning_msg').hide('slow');" . 
				'">&nbsp;&nbsp;&nbsp;&nbsp;X&nbsp;&nbsp;&nbsp;&nbsp;</div>';

			$theMsg .= $msgX;

			// Clear formatting.
			$theMsg .= '<div style="clear: both;"></div>';

			$theMsg .= '</div>';

			return $theMsg;
		}
	}

	/**
	 * information() - returns the htmlized information string.
	 *
	 * @param	string	$msg	msg string
	 * @return	string	htmlized information
	 */
	function information($msg) {
		if (!$msg) {
			return '';
		} else {
			return '
			<p class="information">'.strip_tags($msg, '<br>').'</p>';
		}
	}

	function confirmBox($msg, $params, $buttons, $image='*none*') {
		if ($image == '*none*') {
			$image = html_image('stop.png','48','48',array());
		}

		foreach ($params as $b => $v) {
			$prms[] = '<input type="hidden" name="'.$b.'" value="'.$v.'" />'."\n";
		}
		$prm = join('	 	', $prms);

		foreach ($buttons as $b => $v) {
			$btns[] = '<input type="submit" name="'.$b.'" value="'.$v.'" />'."\n";
		}
		$btn = join('	 	&nbsp;&nbsp;&nbsp;'."\n	 	", $btns);

		return '
			<div id="infobox" style="margin-top: 15%; margin-left: 15%; margin-right: 15%; text-align: center;">
			<table align="center">
			<tr>
			<td>'.$image.'</td>
			<td>'.$msg.'<br/></td>
			</tr>
			<tr>
			<td colspan="2" align="center">
			<br />
			<form action="' . getStringFromServer('PHP_SELF') . '" method="get" >
			'.$prm.'
			'.$btn.'
			</form>
			</td>
			</tr>
			</table>
			</div>
			';
	}

	function jQueryUIconfirmBox($id = 'dialog-confirm', $title = 'Confirm your action', $message = 'Do you confirm your action?') {
		$htmlcode = html_ao('div', array('id' => $id, 'title' => $title, 'class' => 'hide'));
		$htmlcode .= html_e('p', array(), html_e('span', array('class' => 'ui-icon ui-icon-alert', 'style' => 'float:left; margin:0 7px 20px 0;'), '', false).$message);
		$htmlcode .= html_ac(html_ap() -1);
		return $htmlcode;
	}

	function html_input($name, $id = '', $label = '', $type = 'text', $value = '', $extra_params = '') {
		if (!$id) {
			$id = $name;
		}
		$return = '<div class="field-holder">
			';
		if ($label) {
			$return .= '<label for="' . $id . '">' . $label . '</label>
				';
		}
		$return .= '<input id="' . $id . '" type="' . $type . '"';
		//if input is a submit then name is not present
		if ($name) {
			$return .= ' name="' . $name . '"';
		}
		if ($value) {
			$return .= ' value="' . $value . '"';
		}
		if (is_array($extra_params)) {
			foreach ($extra_params as $key => $extra_params_value) {
				$return .= $key . '="' . $extra_params_value . '" ';
			}
		}
		$return .= '/>
			</div>';
		return $return;
	}

	function html_checkbox($name, $value, $id = '', $label = '', $checked = '', $extra_params = array()) {
		if (!$id) {
			$id = $name;
		}
		$return = html_ao('div', array('class' => 'field-holder'));
		$attrs = array('name' => $name, 'id' => $id, 'type' => 'checkbox', 'value' => $value);
		if ($checked) {
			$attrs['checked'] = 'checked';
		}
		if (is_array($extra_params)) {
			foreach ($extra_params as $key => $extra_params_value) {
				$attrs[$key] = $extra_params_value;
			}
		}
		$return .= html_e('input', $attrs);
		if ($label) {
			$return .= html_e('label', array('for' => $id), $label, true);
		}
		$return .= html_ac(html_ap() -1);
		return $return;
	}

	function html_text_input_img_submit($name, $img_src, $id = '', $label = '', $value = '', $img_title = '', $img_alt = '', $extra_params = array(), $img_extra_params = '') {
		if (!$id) {
			$id = $name;
		}
		if (!$img_title) {
			$img_title = $name;
		}
		if (!$img_alt) {
			$img_alt = $img_title;
		}
		$return = '<div class="field-holder">
			';
		if ($label) {
			$return .= '<label for="' . $id . '">' . $label . '</label>
				';
		}
		$return .= '<input id="' . $id . '" type="text" name="' . $name . '"';
		if ($value) {
			$return .= ' value="' . $value . '"';
		}
		if (is_array($extra_params)) {
			foreach ($extra_params as $key => $extra_params_value) {
				$return .= $key . '="' . $extra_params_value . '" ';
			}
		}
		$return .= '/>
			<input type="image" id="' . $id . '_submit" src="' . $this->imgbaseurl . $img_src . '" alt="' . util_html_secure($img_alt) . '" title="' . util_html_secure($img_title) . '"';
		if (is_array($img_extra_params)) {
			foreach ($img_extra_params as $key => $img_extra_params_value) {
				$return .= $key . '="' . $img_extra_params_value . '" ';
			}
		}
		$return .= '/>
			</div>';
		return $return;
	}

	function html_select($vals, $name, $label = '', $id = '', $checked_val = '', $text_is_value = false, $extra_params = '') {
		if (!$id) {
			$id = $name;
		}
		$return = '<div class="field-holder">
			';
		if ($label) {
			$return .= '<label for="' . $id . '">' . $label . '</label>
				';
		}
		$return .= '<select name="' . $name . '" id="' . $id . '" ';
		if (is_array($extra_params)) {
			foreach ($extra_params as $key => $extra_params_value) {
				$return .= $key . '="' . $extra_params_value . '" ';
			}
		}
		$return .= '>';
		$rows = count($vals);
		for ($i = 0; $i < $rows; $i++) {
			if ( $text_is_value ) {
				$return .= '
					<option value="' . $vals[$i] . '"';
				if ($vals[$i] == $checked_val) {
					$return .= ' selected="selected"';
				}
			} else {
				$return .= '
					<option value="' . $i . '"';
				if ($i == $checked_val) {
					$return .= ' selected="selected"';
				}
			}
			$return .= '>' . htmlspecialchars($vals[$i]) . '</option>';
		}
		$return .= '
			</select>
			</div>';
		return $return;
	}

	function html_textarea($name, $id = '', $label = '', $value = '',  $extra_params = '') {
		if (!$id) {
			$id = $name;
		}
		$return = '<div class="field-holder">
			';
		if ($label) {
			$return .= '<label for="' . $id . '">' . $label . '</label>
				';
		}
		$return .= '<textarea id="' . $id . '" name="' . $name . '" ';
		if (is_array($extra_params)) {
			foreach ($extra_params as $key => $extra_params_value) {
				$return .= $key . '="' . $extra_params_value . '" ';
			}
		}
		$return .= '>';
		if ($value) {
			$return .= $value;
		}
		$return .= '</textarea>
			</div>';
		return $return;
	}

	/**
	 * @todo use listTableTop and make this function deprecated ?
	 */
	function html_table_top($cols, $summary = '', $class = '', $extra_params = '') {
		$return = '<table summary="' . $summary . '" ';
		if ($class) {
			$return .= 'class="' . $class . '" ';
		}
		if (is_array($extra_params)) {
			foreach ($extra_params as $key => $extra_params_value) {
				$return .= $key . '="' . $extra_params_value . '" ';
			}
		}
		$return .= '>';
		$return .= '<thead><tr>';
		$nbCols = count($cols);
		for ($i = 0; $i < $nbCols; $i++) {
			$return .= '<th scope="col">' . $cols[$i] . '</th>';
		}
		$return .= '</tr></thead>';
		return $return;
	}

	function getMonitorPic($title = '', $alt = '') {
		return $this->getPicto('ic/mail16w.png', $title, $alt);
	}

	function getStartMonitoringPic($title = '', $alt = '') {
		return $this->getPicto('ic/startmonitor.png', $title, $alt);
	}

	function getStopMonitoringPic($title = '', $alt = '') {
		return $this->getPicto('ic/stopmonitor.png', $title, $alt);
	}

	function getReleaseNotesPic($title = '', $alt = '') {
		return $this->getPicto('ic/manual16c.png', $title, $alt);
	}

	/* no picto for download */
	function getDownloadPic($title = '', $alt = '') {
		return $this->getPicto('ic/save.png', $title, $alt);
	}

	function getHomePic($title = '', $alt = '') {
		return $this->getPicto('ic/home16b.png', $title, $alt);
	}

	function getFollowPic($title = '', $alt = '') {
		return $this->getPicto('ic/tracker20g.png', $title, $alt);
	}

	function getForumPic($title = '', $alt = '') {
		return $this->getPicto('ic/forum20g.png', $title, $alt);
	}

	function getDocmanPic($title = '', $alt = '') {
		return $this->getPicto('ic/docman16b.png', $title, $alt);
	}

	function getMailPic($title = '', $alt = '') {
		return $this->getPicto('ic/mail16b.png', $title, $alt);
	}

	function getPmPic($title = '', $alt = '') {
		return $this->getPicto('ic/taskman20g.png', $title, $alt);
	}

	function getSurveyPic($title = '', $alt = '') {
		return $this->getPicto('ic/survey16b.png', $title, $alt);
	}

	function getScmPic($title = '', $alt = '') {
		return $this->getPicto('ic/cvs16b.png', $title, $alt);
	}

	function getFtpPic($title = '', $alt = '') {
		return $this->getPicto('ic/ftp16b.png', $title, $alt);
	}

	function getDeletePic($title = '', $alt = '', $otherAttr = array()) {
		return $this->getPicto('ic/delete.png', $title, $alt, 20, 20, $otherAttr);
	}

	function getRemovePic($title = '', $alt = '', $otherAttr = array()) {
		return $this->getPicto('ic/remove.png', $title, $alt, 20, 20, $otherAttr);
	}

	function getConfigurePic($title = '', $alt = '', $otherAttr = array()) {
		return $this->getPicto('ic/configure.png', $title, $alt, 20, 20, $otherAttr);
	}

	function getZipPic($title = '', $alt = '', $otherAttr = array()) {
		return $this->getPicto('ic/file_type_archive.png', $title, $alt, 20, 20, $otherAttr);
	}

	function getAddDirPic($title = '', $alt = '', $otherAttr = array()) {
		return $this->getPicto('ic/directory-add.png', $title, $alt, 20, 20, $otherAttr);
	}

	function getNewPic($title = '', $alt = '', $otherAttr = array()) {
		return $this->getPicto('ic/add.png', $title, $alt, 20, 20, $otherAttr);
	}

	function getFolderPic($title = '', $alt = '', $otherAttr = array()) {
		return $this->getPicto('ic/folder.png', $title, $alt, 20, 20, $otherAttr);
	}

	function getPicto($url, $title, $alt, $width = '20', $height = '20', $otherAttr = array()) {
		$otherAttr['title'] = $title;
		if (!$alt) {
			$otherAttr['alt'] = $title;
		} else {
			$otherAttr['alt'] = $alt;
		}
		return html_image($url, $width, $height, $otherAttr);
	}

	/**
	 * toSlug() - protect a string to be used as a link or an anchor
	 *
	 * @param   string	$string  the string used as a link or an anchor
	 * @param   string	$space   the character used as a replacement for a space
	 * @return  string	a protected string with only alphanumeric characters
	 */
	function toSlug($string, $space = "-") {
		if (function_exists('iconv')) {
			$string = @iconv('UTF-8', 'ASCII//TRANSLIT', $string);
		}
		$string = preg_replace("/[^a-zA-Z0-9_:. -]/", "-", $string);
		$string = strtolower($string);
		$string = str_replace(" ", $space, $string);
		if (!preg_match("/^[a-zA-Z:_]/", $string)) {
			/* some chars aren't allowed at the begin */
			$string = "_" . $string;
		}
		return $string;
	}

	function widget(&$widget, $layout_id, $readonly, $column_id, $is_minimized, $display_preferences, $owner_id, $owner_type) {
		$element_id = 'widget_'. $widget->id .'-'. $widget->getInstanceId();
		echo html_ao('div', array('class' => 'widget', 'id' => $element_id));
		echo html_ao('div', array('class' => 'widget_titlebar '. ($readonly?'':'widget_titlebar_handle')));
		echo html_e('div', array('class' => 'widget_titlebar_title'), $widget->getTitle(), false);
		if (!$readonly) {
			echo html_ao('div', array('class' => 'widget_titlebar_close'));
			echo util_make_link('/widgets/updatelayout.php?owner='.$owner_type.$owner_id.'&action=widget&name['.$widget->id.'][remove]='.$widget->getInstanceId().'&column_id='.$column_id.'&layout_id='.$layout_id, $this->getPicto('ic/close.png', _('Close'), _('Close')));
			echo html_ac(html_ap() -1);
			if ($is_minimized) {
				echo html_ao('div', array('class' => 'widget_titlebar_maximize'));
				echo util_make_link('/widgets/updatelayout.php?owner='.$owner_type.$owner_id.'&action=maximize&name['.$widget->id.']='.$widget->getInstanceId().'&column_id='.$column_id.'&layout_id='.$layout_id, $this->getPicto($this->_getTogglePlusForWidgets(), _('Maximize'), _('Maximize')));
				echo html_ac(html_ap() -1);
			} else {
				echo html_ao('div', array('class' => 'widget_titlebar_minimize'));
				echo util_make_link('/widgets/updatelayout.php?owner='.$owner_type.$owner_id.'&action=minimize&name['.$widget->id.']='.$widget->getInstanceId().'&column_id='.$column_id.'&layout_id='.$layout_id, $this->getPicto($this->_getToggleMinusForWidgets(), _('Minimize'), _('Minimize')));
				echo html_ac(html_ap() -1);
			}
			if (strlen($widget->hasPreferences())) {
				echo html_ao('div', array('class' => 'widget_titlebar_prefs'));
				echo util_make_link('/widgets/updatelayout.php?owner='.$owner_type.$owner_id.'&action=preferences&name['.$widget->id.']='.$widget->getInstanceId().'&layout_id='.$layout_id, _('Preferences'));
				echo html_ac(html_ap() -1);
			}
		}
		if ($widget->hasRss()) {
			echo html_ao('div', array('class' => 'widget_titlebar_rss'));
			$url = $widget->getRssUrl($owner_id, $owner_type);
			if (util_check_url($url)) {
				echo util_make_link($widget->getRssUrl($owner_id, $owner_type), 'rss', array(), true);
			} else {
				echo util_make_link($widget->getRssUrl($owner_id, $owner_type), 'rss');
			}
			echo html_ac(html_ap() -1);
		}
		echo html_ac(html_ap() -1);
		$style = '';
		if ($is_minimized) {
			$style = 'display:none;';
		}
		echo html_ao('div', array('class' => 'widget_content', 'style' => $style));
		if (!$readonly && $display_preferences) {
			echo html_e('div', array('class' => 'widget_preferences'), $widget->getPreferencesForm($layout_id, $owner_id, $owner_type));
		}
		if ($widget->isAjax()) {
			echo html_ao('div', array('id' => $element_id.'-ajax'));
			echo '<noscript><iframe width="99%" frameborder="0" src="'. $widget->getIframeUrl($owner_id, $owner_type) .'"></iframe></noscript>';
			echo html_ac(html_ap() -1);
		} else {
			echo $widget->getContent();
		}
		echo html_ac(html_ap() -1);
		if ($widget->isAjax()) {
			$spinner = '<div style="text-align:center">'.trim($this->getPicto('ic/spinner.gif',_('Spinner'), _('Spinner'), 10, 10)).'</div>';
			echo '<script type="text/javascript">/* <![CDATA[ */'."
				jQuery(document).ready(function() {
						jQuery('#$element_id-ajax').html('".$spinner."');
						jQuery.ajax({url:'". util_make_uri($widget->getAjaxUrl($owner_id, $owner_type)) ."',
							success: function(result){jQuery('#$element_id-ajax').html(result)}
							});
						});
			/* ]]> */</script>";
		}
		echo html_ac(html_ap() -1);
	}

	function _getTogglePlusForWidgets() {
		return 'ic/toggle_plus.png';
	}

	function _getToggleMinusForWidgets() {
		return 'ic/toggle_minus.png';
	}

	/* Get the navigation links for the software map pages (trove,
	 * tag cloud, full project list) according to what's enabled
	 */
	function printSoftwareMapLinks() {
		$subMenuTitle = array();
		$subMenuUrl = array();
		$subMenuAttr = array();

		if (forge_get_config('use_project_tags')) {
			$subMenuTitle[] = _('Tag Cloud');
			$subMenuUrl[] = '/softwaremap/tag_cloud.php';
			$subMenuAttr[] = array('title' => _('Browse per tags defined by the projects.'));
		}

		if (forge_get_config('use_trove')) {
			$subMenuTitle[] = _('Project Tree');
			$subMenuUrl[] = '/softwaremap/trove_list.php';
			$subMenuAttr[] = array('title' => _('Browse by Category'));
		}

		if (forge_get_config('use_project_full_list')) {
			$subMenuTitle[] = _('Project List');
			$subMenuUrl[] = '/softwaremap/full_list.php';
			$subMenuAttr[] = array('title' => _('Complete listing of available projects.'));
		}

		// Allow plugins to add more softwaremap submenu entries
		$hookParams = array();
		$hookParams['TITLES'] = & $subMenuTitle;
		$hookParams['URLS'] = & $subMenuUrl;
		$hookParams['ATTRS'] = & $subMenuAttr;
		plugin_hook("softwaremap_links", $hookParams);

		echo $this->subMenu($subMenuTitle, $subMenuUrl, $subMenuAttr);
	}

	function displayStylesheetElements() {
		/* Codendi/Tuleap compatibility */
	}

	/**
	 * openForm - create the html code to open a form
	 *
	 * @param	array	$args	argument of the form (method, action, ...)
	 * @return	string	html code
	 */
	function openForm($args) {
		return html_ao('form', $args);
	}

	/**
	 * closeForm - create the html code to close a form
	 *		must be used after openForm function.
	 *
	 * @return	string	html code
	 */
	function closeForm() {
		return html_ac(html_ap() -1);
	}

	function addRequiredFieldsInfoBox() {
		return html_e('p', array(), sprintf(_('Fields marked with %s are mandatory.'), utils_requiredField()), false);
	}

	/**
	 * html_list - create the html code:
	 *	<ol>
	 *		<li>
	 *	</ol>
	 *	or
	 *	<ul>
	 *		<li>
	 *	</ul>
	 *
	 * @param	array	$elements	array of args to create li elements
	 *					format array['content'] = the content to display in li
	 *						['attrs'] = array of html attrs applied to the li element
	 * @param	array	$attrs		array of attributes of the ol element. Default empty array.
	 * @param	string	$type		type of list : ol or ul. Default is ul.
	 * @return	string
	 */
	function html_list($elements, $attrs = array() , $type = 'ul') {
		$htmlcode = html_ao($type, $attrs);
		foreach ($elements as $element) {
			if (!isset($element['attrs'])) {
				$element['attrs'] = array();
			}
			$htmlcode .= html_e('li', $element['attrs'], $element['content']);
		}
		$htmlcode .= html_ac(html_ap() -1);
		return $htmlcode;
	}

	/**
	 * html_chartid - create the div code to be used with jqplot script
	 *
	 * @param	string	$chart_id		id to identify the div.
	 * @param	string	$figcaption_title	title of the chart
	 * @return	string
	 */
	function html_chartid($chart_id = 'chart0', $figcaption_title = '') {
		$htmlcode = html_ao('figure');
		$htmlcode .= html_e('figcaption', array(), $figcaption_title, false);
		$htmlcode .= html_ao('div', array('id' => $chart_id));
		$htmlcode .= html_ac(html_ap() -2);
		return $htmlcode;
	}
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
