<?php

//require_once("common/xml/ProjectXml.class.php");require_once $gfcommon.'include/Error.class.php';

class ProjectNewsXml2 extends ProjectXml {

	function ProjectNewsXml2($groupId) {
		parent::__construct($groupId);
	}

	function getProjectXmlDataInternal() {
		$groupId = $this->groupId;
		$project = $this->project;

		$xmlData = parent::getProjectXmlDataInternal();

		// ######################### News

		if ($project->usesNews()) {
			$resNews = db_query(
				"SELECT u.user_name, u.realname,
					nb.id, nb.forum_id, nb.summary, nb.post_date, nb.details, 
					nb.request_global::int, nb.sidebar_display::int 
				FROM users AS u, plugin_simtk_news AS nb, groups 
				WHERE u.user_id = nb.submitted_by 
				AND nb.group_id='$groupId'
				AND nb.is_approved <> 4
				AND nb.group_id=groups.group_id
				AND groups.status='A'
				ORDER BY post_date DESC"
			);

			if ($resNews) {
				$newsFieldNames = db_fieldnames($resNews);
				$newsNumRows = db_numrows($resNews);

				// added a project id field (group id) for using on RSS Feed for project news. Added by Achint 06/04/2013
				$xmlData .= "<project_id>".$groupId."</project_id>";
				$xmlData .= "<news_list>";

				for ($i = 0; $i < $newsNumRows; $i++) {

					$xmlData .= "<news>";

/*
					// pull the list of categories this news item is tagged for. SELECT trove_cat.fullname AS fullname,trove_cat.trove_cat_id AS trove_cat_id
					$rsCat = db_query("SELECT t.fullname,n.news FROM trove_cat as t, news_bytes_categories as n where n.category = t.trove_cat_id and n.news='".db_result($resNews, $i, "id")."'");
					if (pg_num_rows($rsCat) > 0) {
						while($category = pg_fetch_array($rsCat)) {
							$xmlData .= "<cats>";
							$xmlData .= "<category>";
							$xmlData .= $category['fullname'];
							$xmlData .= "</category>";
							$xmlData .= "</cats>";
						}
					}
*/

					//group id, added by Yan 06/18/2012
					$xmlData .= "<group_id>";
					$xmlData .= $groupId;
					$xmlData .= "</group_id>";

					//news id, added by Yan 06/18/2012
					$xmlData .= "<news_id>";
					$xmlData .= db_result($resNews, $i, "id");
					$xmlData .= "</news_id>";					

					//exclude fields we will handle manually
					$xmlData .= db_row_to_xml($resNews, $i, $newsFieldNames, array("post_date", "details"));

					//format the post date
					$postDate = getdate(db_result($resNews, $i, "post_date"));
					$xmlData .= "<post_date>";
					$xmlData .= date("r",strtotime($postDate["month"] . ' ' . $postDate["mday"] . ', ' . $postDate["year"]));
					$xmlData .= "</post_date>";

					// get the date of the first element 
					if ($i == 0) {
						$first_news_date = date("r",strtotime($postDate["month"] . ' ' . 
							$postDate["mday"] . ', ' . 
							$postDate["year"]));
					}

					$xmlData .= "<news_index_item>";
					$xmlData .= "news".($i+1);
					$xmlData .= "</news_index_item>";

					$xmlData .= "<news_url>";
					$xmlData .= "/news/news_details.php?group_id=" . $groupId .
						"&amp;news_id=" . db_result($resNews, $i, "id") .
						"&amp;news_flag=0";
					$xmlData .= "</news_url>";

					$xmlData .= "<details>";
					$news = db_result($resNews, $i, "details");
					$news = escapeOnce( 
						util_make_links( 
							util_strip_insecure_tags( 
								util_whitelist_tags( 
									unescape($news)
								)
							),
							true
						)
					);
					$xmlData .= $news;
					$xmlData .= "</details>";

					$xmlData .= "<summ_txt>";
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
						| Jan\.             # months
						| Feb\.             # months
						| Mar\.             # months
						| Apr\.             # months
						| Jun\.             # months
						| Jul\.             # months
						| Aug\.             # months
						| Sep\.             # months
						| Sept\.             # months
						| Oct\.             # months
						| Nov\.             # months
						| Dec\.             # months
						| Va\.		    # states
						| Ca\.		    # states
									# or... (you get the idea).
						)                   # End negative lookbehind.
						\s+                 # Split on whitespace between sentences.
						/ix'; 

					$arr = preg_split($re, db_result($resNews , $i, 'details') , -1, PREG_SPLIT_NO_EMPTY); 

					$summ_txt = '';
					//if the first paragraph is short, and so are following paragraphs, add the next paragraph on
					if ((strlen($arr[0]) < 50) && (strlen($arr[0].$arr[1]) < 300)) {
						if ($arr[1]) {
							$summ_txt .= $arr[0] . '. ' . $arr[1];
						}
						else {
							$summ_txt .= $arr[0]; // the news has only one sentence
						}
						
					}
					else {
						$summ_txt.=$arr[0];
					} 
					//	$summ_txt .= $arr[0];
					$summ_txt = escapeOnce(
						util_make_links(
							util_strip_insecure_tags(
								util_whitelist_tags(
									unescape($summ_txt)
								)
							),
							true
						)
					);
					$xmlData .= $summ_txt;
					$xmlData .= "</summ_txt>";

					$xmlData .= "</news>";
				}

				$xmlData .= "</news_list>";

				$xmlData .= "<news_date>";
				$xmlData .= $first_news_date;
				$xmlData .= "</news_date>";

				db_free_result($resNews);
			}
		}
		return $xmlData;
	}
}

?>
