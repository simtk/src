<?php

class ProjectRssXml{

	function getRssData() {

		global $cat_id;

		/*$sql="SELECT groups.group_name,groups.group_id,groups.unix_group_name,
                	groups.type_id,users.user_name,users.realname,news_bytes.id,
                	news_bytes.forum_id,news_bytes.summary,news_bytes.post_date,news_bytes.details 
                	FROM users,news_bytes,groups 
                	WHERE 
                	users.user_id=news_bytes.submitted_by 
                	AND news_bytes.group_id=groups.group_id 
			AND groups.status='A'
                	ORDER BY post_date DESC";*/

		 $sql = "SELECT nb.id, nb.forum_id, nb.group_id, nb.summary, nb.post_date, nb.details, groups.group_name, 
				nb.simtk_request_global::int, nb.simtk_sidebar_display::int 
			FROM plugin_simtk_news as nb, groups as groups 
			WHERE nb.is_approved=1
			AND nb.group_id = groups.group_id 
			AND groups.status='A'
			ORDER BY post_date DESC";

		$resNews = db_query($sql);

		if ($resNews) {
			$newsFieldNames = db_fieldnames($resNews);

			$newsNumRows = db_numrows($resNews);

			$rssData = 
				'<?xml version="1.0" encoding="UTF-8"?>
				<rss version="2.0">
				<channel>
					 <title>SimTK</title>
					 <description>Simtk.org is the home of the software framework initiated and developed by Simbios, the National NIH Center for Biomedical Computing focusing on Physics-based Simulation of Biological Structures. </description>';

			//$xmlData .= "<item>";
			$feed_date = "";
			$xmlData = "";

			for ($i = 0; $i < $newsNumRows; $i++) {
				$xmlData .= "<item>";

				// get the date of most recent new item, to mark as feed update date 
				if ($i == 0) {
					$feed_date = date("r",strtotime(getdate(db_result($resNews, $i, "post_date"))));
				}

				$xmlData .= "<title>";
				$xmlData .= db_result($resNews, $i, "summary");
				$xmlData .= "</title>";

				$xmlData .= "<link>";
				$xmlData .= htmlspecialchars("http://" . $_SERVER['SERVER_NAME'] . 
					"/news/news_details.php?group_id=" .
					db_result($resNews, $i, "group_id") .
					"&news_id=" .
					db_result($resNews, $i, "id") .
					"&news_flag=0");
				$xmlData .= "</link>";					

				// format the post date
				$postDate = getdate(db_result($resNews, $i, "post_date"));
				$xmlData .= "<pubDate>";
				$xmlData .= $postDate["month"] . ' ' . $postDate["mday"] . ', ' . $postDate["year"];
				$xmlData .= "</pubDate>";

				$xmlData .= "<description>";
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

				$arr = preg_split($re, db_result($resNews ,$i,'details') , -1, PREG_SPLIT_NO_EMPTY); 

				$summ_txt = '';
				// if the first paragraph is short, and so are following paragraphs, add the next paragraph on
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
				//$summ_txt .= $arr[0];
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
				$xmlData .= "</description>";
				$xmlData .= "</item>";
			}
			//$xmlData .= "</news_list>";
			db_free_result($resNews);
		}

		$rssData .= "<pubDate>" . $feed_date . "</pubDate><ttl>1800</ttl>";

		echo $rssFeed = $rssData . $xmlData . "</channel></rss>";
	}
}
?>
