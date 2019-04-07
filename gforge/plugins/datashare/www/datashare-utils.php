<?php
/**
 * Data Share 
 */

function datashare_header($params,$group_id) {
	global $DOCUMENT_ROOT,$HTML;
	$params['toptab']='Data Share...';
	$params['group']=$group_id;
	
	if ($group_id) {
		$menu_texts=array();
		$menu_links=array();

		$params['titleurl']='/plugins/datashare/?pluginname=datashare&group_id='.$group_id;
		
		$menu_texts[]=_('Data Share:');
		$menu_links[]='/plugins/datashare/index.php?type=group&group_id='.$group_id;
		
		$params['submenu'] = $HTML->subMenu($menu_texts,$menu_links);
	}
	/*
		Show horizontal links
	*/
	site_project_header($params);
	
}

function datashare_footer($params) {
	GLOBAL $HTML;
	$HTML->footer($params);
}


// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
