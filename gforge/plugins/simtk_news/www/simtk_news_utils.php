<?php
/**
 *
 * news plugin simtk_news_utils.php
 * 
 * The main news utility file for simtk.
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

function news_header($params,$group_id) {
	global $HTML, $news_name,$news_id;

    $params['titleurl']='/plugins/simtk_news/?group_id='.$group_id;
    $params['toptab']='news';
    $params['group']=$group_id;
	
    // Create submenu under project_overview_main DIV, such that it does not
    // occupy the whole width of the page (rather than using the 
    // submenu population in Theme.class.php)
    $subMenuTitle = array();
    $subMenuUrl = array();
    $subMenuTitle[] = _('View News');
    $subMenuUrl[]='/plugins/simtk_news/?group_id=' . $group_id;
    if (session_loggedin()) {
	   $group = group_get_object($group_id);
	   if ($group && is_object($group) && !$group->isError()) {
		  if (forge_check_perm ('project_admin', $group_id)) {
			// Check permission before adding administrative menu items.
			$subMenuTitle[]='Add News';
			$subMenuUrl[]='/plugins/simtk_news/submit.php?group_id=' . $group_id;
			$subMenuTitle[]=_('Administration');
			$subMenuUrl[]='/plugins/simtk_news/admin/?group_id=' . $group_id;

		  }
	   }
   }

   site_project_header($params);
   

   // Show the submenu.
   echo $HTML->beginSubMenu();
   echo $HTML->printSubMenu($subMenuTitle, $subMenuUrl, array());
   echo $HTML->endSubMenu();


}

function news_footer($params) {
	GLOBAL $HTML;
	$HTML->footer($params);
}


function getNewsByProject($group_id,$details_condensed=1) {

	$arrNews = array();

	if (!$group_id) {
		$group_id=forge_get_config('news_group');
	}
	$result = db_query_params ('
       SELECT groups.group_id, group_name, unix_group_name, plugin_simtk_news.id,plugin_simtk_news.summary, plugin_simtk_news.post_date, plugin_simtk_news.details, plugin_simtk_news.forum_id, picture_file, user_name, realname
       FROM plugin_simtk_news,groups,users WHERE (plugin_simtk_news.group_id=$1 AND plugin_simtk_news.is_approved <> 4)
	   AND plugin_simtk_news.submitted_by = users.user_id
       AND plugin_simtk_news.group_id=groups.group_id
       AND groups.status=$2
       AND plugin_simtk_news.simtk_sidebar_display=$3
       ORDER BY post_date DESC',
				   array ($group_id,
					  'A',true));
	$rows=db_numrows($result);
    //echo "rows: " . $rows . "<br>";
	$return = '';

	if (!$result || $rows < 1) {
		$return .= false;
		$return .= db_error();
	} else {
	    for ($i = 0; $i < $rows; $i++) {
		   $arrNews[$i]['summary'] = db_result($result, $i, 'summary');
		   //echo "summary: " . $arrNews[$i]['summary'] . "<br>";
		   /*
		   if ($details_condensed == 1) {
		     $arrNews[$i]['details'] = get_news_detail_split(db_result($result, $i, 'details'));
		   }
		   else {
		     $arrNews[$i]['details'] = db_result($result, $i, 'details');
		   }
		   */
		   $arrNews[$i]['details'] = get_news_details_split(db_result($result, $i, 'details'));
		   //echo "summary: " . $arrNews[$i]['details'] . "<br>";
		   $arrNews[$i]['id'] = db_result($result, $i, 'id');
		   //echo "id: " . $arrNews[$i]['id'] . "<br>";
		   $arrNews[$i]['post_date'] = db_result($result, $i, 'post_date');
		   //echo "date: " . $arrNews[$i]['post_date'] . " " . date('M d, Y',$arrNews[$i]['post_date']) . "<br>";
		   
		   $arrNews[$i]['picture_file'] = db_result($result, $i, 'picture_file');
		   $arrNews[$i]['realname'] = db_result($result, $i, 'realname');
		   $arrNews[$i]['user_name'] = db_result($result, $i, 'user_name');
		   $arrNews[$i]['group_id'] = db_result($result, $i, 'group_id');
		   $arrNews[$i]['group_name'] = db_result($result, $i, 'group_name');
		   $arrNews[$i]['unix_group_name'] = db_result($result, $i, 'unix_group_name');
		   
	    }		

	}
	return $arrNews;
}


/**
 * Display news for project home page (frontpage).
 *
 * @param int  $group_id group_id of the news (forge_get_config('news_group') used if none given)
 * @return string
 */
function news_show_project_overview($group_id=0) {

	if (!$group_id) {
		$group_id=forge_get_config('news_group');
	}

	$strQuery = 'SELECT p.id, p.summary, p.post_date, p.details, p.forum_id ' .
		'FROM plugin_simtk_news AS p, groups AS g ' .
		'WHERE (p.group_id=$1 AND p.is_approved <> 4) ' .
		'AND p.group_id=g.group_id ' .
		'AND g.status=$2 ' .
		'AND p.simtk_sidebar_display=$3 ' .
		'ORDER BY post_date DESC';
	$result = db_query_params($strQuery, array ($group_id, 'A', true));
	$rows=db_numrows($result);

	$return = '';

	if (!$result || $rows < 1) {
		$return .= false;
		$return .= db_error();
	} else {
		$return .= '<div class="news_item">';
		for ($i=0; $i<$rows; $i++) {
			$t_thread_title = db_result($result,$i,'summary');
			$forum_id = db_result($result,$i,'forum_id');
			$return .= "<a href='/plugins/simtk_news/news_details.php?flag=3&group_id=$group_id&id=" .  db_result($result,$i,'id') . "'><h4>". $t_thread_title . '</a></h4>';
				//get the first paragraph of the story
                                /*
				if (strstr(db_result($result,$i,'details'),'<br/>')) {
					// the news is html, fckeditor made for example
					$arr=explode("<br/>",db_result($result,$i,'details'));
				} else {
					$arr=explode("\n",db_result($result,$i,'details'));
				}
                                */

                                $return .= '<span class="small grey">' . date("M j, Y" , db_result($result,$i,'post_date')) . '</span>';
                                /*
				$summ_txt=util_make_links( $arr[0] );

                                //added from simtk 1.0
                                if ($summ_txt) {
                                  $theWordCount = str_word_count($summ_txt);
                                  if ($theWordCount > 25) {
                                        $words = preg_split("/[\s,.]+/", $summ_txt);
                                        $summ_txt = "";
                                        for ($i = 0; $i < 25; $i++) {
                                                $summ_txt .= $words[$i] .' ';
                                        }
                                        $summ_txt .= "...";
                                  }
                                }

				if ($summ_txt != "") {
					$return .= '<p>'.$summ_txt.'</p>';
				}
                                */

                                $re = '/# Split sentences on whitespace between them.
                                        (?<=                # Begin positive lookbehind.
                                          [.!?]             # Either an end of sentence punct,
                                        | [.!?][\'"]        # or end of sentence punct and quote.
                                        )                   # End positive lookbehind.
                                        (?<!                # Begin negative lookbehind.
                                          Mr\.              # Skip either "Mr."
                                        | Mrs\.             # or "Mrs.",
                                        | Ms\.              # or "Ms.",
                                        | Jr\.              # or "Jr.",
                                        | Dr\.              # or "Dr.",
                                        | Prof\.            # or "Prof.",
                                        | Sr\.              # or "Sr.",
                                                                                # or... (you get the idea).
                                        )                   # End negative lookbehind.
                                        \s+                 # Split on whitespace between sentences.
                                        /ix';

                                $arr=preg_split($re,  db_result($result,$i,'details') , -1, PREG_SPLIT_NO_EMPTY);
                                $summ_txt = '';
                                //if the first paragraph is short, and so are following paragraphs, add the next paragraph on
                                if ((strlen($arr[0]) < 50) && isset($arr[1]) && (strlen($arr[0].$arr[1]) < 300)) {
                                        if($arr[1])
                                        {
                                                $summ_txt.=$arr[0].'. '.$arr[1];
                                        } else {
                                                $summ_txt.=$arr[0]; // the news has only one sentence
                                        }
                                } else {
                                        $summ_txt.=$arr[0];
                                }

				if ($summ_txt != "") {
				    $summ_txt = util_make_clickable_links(util_whitelist_tags($summ_txt));
					$return .= '<p>'.$summ_txt.'</p>';
				}

                }
		$return .= '</div><!-- class="news_item" -->';

	}
	return $return;
}


/**
 * Display latest news for News page (one level below project home page).
 *
 * @param int  $group_id group_id of the news (forge_get_config('news_group') used if none given)
 * @param int  $limit number of news to display (default: 10)
 * @param bool $show_summaries (default: true)
 * @param bool $allow_submit (default: true)
 * @param bool $flat (default: false)
 * @param int  $tail_headlines number of additional news to display in short (-1 for all the others, default: 0)
 * @param bool $show_forum
 * @return string
 */
function news_show_latest($group_id=0,$limit=10,$show_summaries=true,$allow_submit=true,$flat=false,$tail_headlines=0,$show_forum=true) {

	if (!$group_id) {
		$group_id=forge_get_config('news_group');
	}
	/*
		Show a simple list of the latest news items with a link to the forum
	*/
	if ($tail_headlines == -1) {
		$l = 0;
	} else {
		$l = $limit + $tail_headlines;
	}
	
	$result = db_query_params ('
       SELECT groups.group_name, groups.unix_group_name, groups.group_id,
       groups.type_id, users.user_name, users.realname, users.picture_file,
       plugin_simtk_news.forum_id, plugin_simtk_news.summary, plugin_simtk_news.post_date,
       plugin_simtk_news.details, plugin_simtk_news.id
       FROM users,plugin_simtk_news,groups
       WHERE (plugin_simtk_news.group_id=$1 AND plugin_simtk_news.is_approved <> 4 OR 1!=$2)
         AND (plugin_simtk_news.is_approved=1 OR 1 != $3)
         AND users.user_id=plugin_simtk_news.submitted_by
         AND plugin_simtk_news.group_id=groups.group_id
         AND groups.status=$4
         ORDER BY post_date DESC',
	   array ($group_id,
			  $group_id != forge_get_config('news_group') ? 1 : 0,
			  $group_id != forge_get_config('news_group') ? 0 : 1,
	    	  'A'),
		      $l);
	$rows=db_numrows($result);

	$return = "\n";

	if (!$result || $rows < 1) {
		$return .= _('No News Found');
		$return .= db_error();
//		$return .= "</div>";
	} else {
		for ($i=0; $i<$rows; $i++) {
			$t_thread_title = db_result($result,$i,'summary');
			$t_thread_url = "/forum/forum.php?forum_id=" . db_result($result,$i,'forum_id');
			$t_news_url = "/plugins/simtk_news/news_details.php?flag=0&group_id=$group_id&id=" . db_result($result,$i,'id');
			$t_thread_author = db_result($result,$i,'realname');

			//$return .= '<div class="one-news bordure-dessous">';
			$return .= "\n";
			if ($show_summaries && $limit) {
				//get the first paragraph of the story
				if (strstr(db_result($result,$i,'details'),'<br/>')) {
					// the news is html, fckeditor made for example
					$arr=explode("<br/>",html_entity_decode(util_make_clickable_links(util_whitelist_tags(db_result($result,$i,'details')))));
				} else {
					$arr=explode("\n",html_entity_decode(nl2br(util_make_clickable_links(util_whitelist_tags(db_result($result,$i,'details'))))));
				}
				$summ_txt=util_make_links( $arr[0] );
				$proj_name=util_make_link_g (strtolower(db_result($result,$i,'unix_group_name')),db_result($result,$i,'group_id'),db_result($result,$i,'group_name'));
			} else {
				$proj_name='';
				$summ_txt='';
			}

			$tmpPicFile = db_result($result, $i, 'picture_file');
			if ($tmpPicFile == "") {
//				$tmpPicFile = "user_default.gif";
				$tmpPicFile = "user_profile.jpg";
			}
			if (!$limit) {
				$return .= '<p><h3>'.util_make_link ($t_news_url, $t_thread_title).'</h3></p>';			
				$return .= ' &nbsp; <em>'. date(_('M d, Y'),db_result($result,$i,'post_date')).'</em><br />';
			} else {
				$return .= '<p>
					<div class="news_representation">
						<div class="news_img">
							<a href="/users/' . db_result($result, $i, 'user_name') . '">' .
							'<img ' .
							' onError="this.onerror=null;this.src=' . "'" . '/userpics/user_profile.jpg' . "';" . '"' .
							' alt="Image not available"' .
							' src="/userpics/' . $tmpPicFile . '" ></a>
						</div><!--news_img-->
						<div class="wrapper_text"><h4>' . 
							util_make_link($t_news_url, $t_thread_title) . 
							' <small>' . 
							date(_('M d, Y'), db_result($result, $i, 'post_date')) .
							'</small></h4>
						</div><!-- class="wrapper_text" -->
					</div><!-- class="news_representation" -->
					</p>';
			}

			if ($limit) {
				$limit--;
			}
			
			$return .= "\n\n";
		} // for

		if ($group_id != forge_get_config('news_group')) {
			$archive_url = '/news/?group_id='.$group_id;
		} else {
			$archive_url = '/news/';
		}
		if ($tail_headlines != -1) {
			if ($show_forum) {
				$return .= '<div>' . util_make_link($archive_url, _('News archive'), array('class' => 'dot-link')) . '</div>';
			} else {
				$return .= '<div>...</div>';
			}
		}
	} //else
	
	if ($allow_submit && $group_id != forge_get_config('news_group')) {
		if(!$result || $rows < 1) {
			$return .= '';
		}
		//you can only submit news from a project now
		//you used to be able to submit general news
		$return .= '<div>' . util_make_link ('/news/submit.php?group_id='.$group_id, _('Submit News')).'</div>';
	}
	return $return;
}

function news_foundry_latest($group_id=0,$limit=5,$show_summaries=true) {
	/*
		Show a the latest news for a portal
	*/

	$result=db_query_params("SELECT groups.group_name,groups.unix_group_name,groups.group_id,
		users.user_name,users.realname,plugin_simtk_news.forum_id,
		plugin_simtk_news.summary,plugin_simtk_news.post_date,plugin_simtk_news.details
		FROM users,plugin_simtk_news,groups,foundry_news
		WHERE foundry_news.foundry_id=$1
		AND users.user_id=plugin_simtk_news.submitted_by
		AND foundry_news.news_id=plugin_simtk_news.id
		AND plugin_simtk_news.group_id=groups.group_id
		AND foundry_news.is_approved=1
		ORDER BY plugin_simtk_news.post_date DESC", array($group_id),$limit);

	$rows=db_numrows($result);

	if (!$result || $rows < 1) {
		$return .= '<h3>' . _('No News Found') . '</h3>';
		$return .= db_error();
	} else {
		for ($i=0; $i<$rows; $i++) {
			if ($show_summaries) {
				//get the first paragraph of the story
				$arr=explode("\n",db_result($result,$i,'details'));
				if ((isset($arr[1]))&&(isset($arr[2]))&&(strlen($arr[0]) < 200) && (strlen($arr[1].$arr[2]) < 300) && (strlen($arr[2]) > 5)) {
					$summ_txt=util_make_links( $arr[0].'<br />'.$arr[1].'<br />'.$arr[2] );
				} else {
					$summ_txt=util_make_links( $arr[0] );
				}

				//show the project name
				$proj_name=' &nbsp; - &nbsp; '.util_make_link_g (strtolower(db_result($result,$i,'unix_group_name')),db_result($result,$i,'group_id'),db_result($result,$i,'group_name'));
			} else {
				$proj_name='';
				$summ_txt='';
			}
			$return .= util_make_link ('/forum/forum.php?forum_id='. db_result($result,$i,'forum_id'),'<strong>'. db_result($result,$i,'summary') . '</strong>')
				.'<br /><em>'. db_result($result,$i,'realname') .' - '.
					date(_('M d, Y'),db_result($result,$i,'post_date')) . $proj_name . '</em>
				'. $summ_txt .'';
		}
	}
	return $return;
}

function get_news_name($id) {
	/*
		Takes an ID and returns the corresponding forum name
	*/
	$result=db_query_params('SELECT summary FROM plugin_simtk_news WHERE id=$1', array($id));
	if (!$result || db_numrows($result) < 1) {
		return _('Not Found');
	} else {
		return db_result($result, 0, 'summary');
	}
}

function get_news_details_split($details) {


                                $re = '/# Split sentences on whitespace between them.
                                        (?<=                # Begin positive lookbehind.
                                          [.!?]             # Either an end of sentence punct,
                                        | [.!?][\'"]        # or end of sentence punct and quote.
                                        )                   # End positive lookbehind.
                                        (?<!                # Begin negative lookbehind.
                                          Mr\.              # Skip either "Mr."
                                        | Mrs\.             # or "Mrs.",
                                        | Ms\.              # or "Ms.",
                                        | Jr\.              # or "Jr.",
                                        | Dr\.              # or "Dr.",
                                        | Prof\.            # or "Prof.",
                                        | Sr\.              # or "Sr.",
                                                                                # or... (you get the idea).
                                        )                   # End negative lookbehind.
                                        \s+                 # Split on whitespace between sentences.
                                        /ix';

                                $arr=preg_split($re, $details,  -1, PREG_SPLIT_NO_EMPTY);
                                $summ_txt = '';
                                //if the first paragraph is short, and so are following paragraphs, add the next paragraph on
                                if ((strlen($arr[0]) < 50) && 
					isset($arr[1]) && (strlen($arr[0].$arr[1]) < 300)) {
					$summ_txt.=$arr[0].'. '.$arr[1];
                                } else {
                                        $summ_txt.=$arr[0];
                                }


				$return = '<p>'.$summ_txt.'</p>';
                                return $return;

}

/**
 * util_make_links() - Turn URL's into HREF's.
 *
 * @param		string	The URL
 * @returns The HREF'ed URL
 *
 */
function util_make_links2 ($data='', $escape=false) {
	if(empty($data)) { 
		return $data; 
	}
	
	// Kinda stupid algorithm that splits on "<" and ">" and only does URL parsing 
	// on anything after the last ">" of each set (a set being denoted by "<")
	// For this reason, only use "<" and ">" for legit tags; "&lt;" and "&gt;" for the rest
	$beginTags = explode( "<", $data );
	for ( $i = 0; $i < count( $beginTags ); $i++ )
	{
		$endTags = explode( ">", $beginTags[ $i ] );
		// Don't replace tag text if the tag already has an href attribute
		//echo "tag: " . $endTags[ 0 ] . "<br />";
		//echo "count: " . count($endTags) . "<br />";
		if ( (( count( $endTags ) > 1 || $i == 0 ) && !preg_match("/href/", $endTags[ 0 ] ) ))
		{
			$newText = "";
			$lines = explode("\n",$endTags[ count( $endTags ) - 1 ] );
			//echo "line: " . $lines . "<br />";
			while ( list ($key,$line) = each ($lines)  ) {
				// When we come here, we usually have form input
				// encoded in entities. Our aim is to NOT include
				// angle brackets in the URL
				// (RFC2396; http://www.w3.org/Addressing/URL/5.1_Wrappers.html)

				// NOTE: In replacing eregi_replace(), which was deprecated in PHP 5.3.0 
				// and removed in PHP 7.0.0, with preg_replace9), the normal convention
				// is to replace the PATTERN in eregi_replace() to that in preg_replace()
				// with "/PATTERN/i" or "/PATTERN/". Because the PATTERNs here include "/",
				// we have to use '%' as the delimeters, i.e. "%PATTERN%".

				$line = str_replace('&gt;', "\1", $line);

				//$line = eregi_replace("([ \t]|^)www\."," http://www.",$line);
				$line = preg_replace("%([ \t]|^)www\.%i"," http://www.",$line);

				if ($escape) {
/*
					$text = eregi_replace("([[:alnum:]]+)://([^[:space:]<\1]*)([[:alnum:]#?/&=])", "&lt;a href=\"\\1://\\2\\3\" target=\"_blank\"&gt;\\1://\\2\\3&lt;/a&gt;", $line);
					$text = eregi_replace("([[:space:]]|^)(([a-z0-9_]|\\-|\\.)+@([^[:space:]]*)([[:alnum:]-]))", "\\1&lt;a href=\"mailto:\\2\" target=\"_new\"&gt;\\2&lt;/a&gt;", $text);
*/

					$text = preg_replace("%([[:alnum:]]+)://([^[:space:]<\1]*)([[:alnum:]#?/&=])%", "&lt;a href=\"\\1://\\2\\3\" target=\"_blank\"&gt;\\1://\\2\\3&lt;/a&gt;", $line);
					$text = preg_replace("%([[:space:]]|^)(([a-z0-9_]|\\-|\\.)+@([^[:space:]]*)([[:alnum:]-]))%", "\\1&lt;a href=\"mailto:\\2\" target=\"_new\"&gt;\\2&lt;/a&gt;", $text);

				} else {
/*
					$text = eregi_replace("([[:alnum:]]+)://([^[:space:]<\1]*)([[:alnum:]#?/&=])", "<a href=\"\\1://\\2\\3\" target=\"_blank\">\\1://\\2\\3</a>", $line);
					$text = eregi_replace("([[:space:]]|^)(([a-z0-9_]|\\-|\\.)+@([^[:space:]]*)([[:alnum:]-]))", "\\1<a href=\"mailto:\\2\" target=\"_new\">\\2</a>", $text);
*/

					$text = preg_replace("%([[:alnum:]]+)://([^[:space:]<\1]*)([[:alnum:]#?/&=])%", "<a href=\"\\1://\\2\\3\" target=\"_blank\">\\1://\\2\\3</a>", $line);
					$text = preg_replace("%([[:space:]]|^)(([a-z0-9_]|\\-|\\.)+@([^[:space:]]*)([[:alnum:]-]))%", "\\1<a href=\"mailto:\\2\" target=\"_new\">\\2</a>", $text);

				}
				$text = str_replace("\1", '&gt;', $text);
				$newText .= $text;
			}
			$endTags[ count( $endTags ) - 1 ] = $newText;
		}
		if ( $escape )
			$beginTags[ $i ] = implode( "&gt;", $endTags );
		else
			$beginTags[ $i ] = implode( ">", $endTags );
	}
	if ( $escape )
		return implode( "&lt;", $beginTags );
	else
		return implode( "<", $beginTags );
}




// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
