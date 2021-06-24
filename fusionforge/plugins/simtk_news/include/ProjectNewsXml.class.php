<?php

require_once("common/xml/ProjectXml.class.php");

error_reporting(1);

class ProjectNewsXml extends ProjectXml {

	function ProjectNewsXml($groupId) {
		parent::__construct($groupId);
	}

	// Parse RSS feed XML using SimpleXML.
	function parseRSSXmlSimple(&$rss, $feedUrl, $theAge) {

		$strUrlUnAged = $_SERVER['SERVER_NAME'] . "/project/rss.php?group_id=". $this->groupId;
		$skipAgeCheck = false;
		if (stripos($feedUrl, $strUrlUnAged) !== false) {
			// Keep older items from our server news URL. Skip age check.
			$skipAgeCheck = true;
		}

		// Load URL using SimpleXML.
		$xml = simplexml_load_file($feedUrl);

		if ($xml) {
			// Get namespaces.
			$ns = $xml->getNamespaces(true);

			if (isset($xml->channel->item)) {
				// RSS 2.0 format.
				$theItems = $xml->channel->item;
			}
			else if (isset($xml->item)) {
				// RDF format.
				$theItems = $xml->item;
			}
			else {
				// Unrecognized format.
				return;
			}

			foreach ($theItems as $item) {

				// If age is zero, there's nothing to do since we allow 
				// all news items from all feeds no matter how old they are.
				if ($theAge != 0 && !$skipAgeCheck) {
					$deadline = time() - $theAge * 24 * 60 * 60;

					// If news item is older than the deadline, skip this item
					// Note: Feed may NOT be arrange in the order of publication date!!!
					// RSS 2.0 format.
					if (isset($item->pubDate) && strtotime(trim($item->pubDate)) < $deadline) {
						continue;
					}
					// RDF format.
					if (isset($item->children($ns['dc'])->date) && 
						strtotime(trim($item->children($ns['dc'])->date)) < $deadline) {
						continue;
					}
					// Otherwise, skip date check.
				}

				// Locate isolated '& " and convert into "&amp ".
				// Otherwise, the XSLT transform will fail!
				$theTitle = str_replace('& ', '&amp; ', trim((string) $item->title));
				$theTitle = util_strip_insecure_tags( 
						util_whitelist_tags($theTitle)
					);
				$feedInfo['title'] = $theTitle;


				if (isset($item->pubDate)) {
					// RSS 2.0 format.
					$feedInfo['pubDate'] = date("M d, Y", strtotime($item->pubDate));
				}
				else if (isset($item->children($ns['dc'])->date)) {
					// RDF format.
					$feedInfo['pubDate'] = date("M d, Y", strtotime($item->children($ns['dc'])->date));
				}

				// Note: It is necessary to trim the link; otherwise, a space before
				// "http://" (i.e. " http://") becomes "+http://".
				$feedInfo['link'] = urlencode(trim((string) $item->link));

				$theText = escapeOnce( 
					util_strip_insecure_tags( 
						util_whitelist_tags($item->description)
					)
				);

				/*
				// Remove any <em/> which does not do anything but causes problem in display.
				//$theText = str_replace('&lt;em/&gt;', '', $theText);
				$theText = $this->remove_no_content_tag($theText, "em");
				$theText = $this->remove_no_content_tag($theText, "a");
				*/

				// Remove no content tags: "<em/>", "<a/>", "<strong/>", "<i/>", "<b/>".
				$theText = $this->remove_no_content_tags($theText, array("em", "a", "strong", "i", "b"));

/*
				//$theText = htmlspecialchars($item->description);
				$theText = strip_tags(trim((string) $item->description));
				$theText = trim(str_replace("&nbsp;", "", $theText));
				$theText = preg_replace("/[^a-zA-Z0-9_.'-\s]/", "", strip_tags(html_entity_decode($theText)));
				$theWordCount = str_word_count($theText);
				$foundSentence = false;
                                if ($theWordCount > 25) {
                                        $words = preg_split("/[\s,]+/", $theText);
                                        $tmpText = "";
                                        for ($i = 0; $i < 25; $i++) {
						$idx = stripos($words[$i], '.');
						if ($idx == false) {
                                                	$tmpText .= $words[$i] . ' ';
						}
						else {
							$tmpText .= substr($words[$i], 0, $idx) . ".";
							$foundSentence = true;
							break;
						}
                                        }
					if ($foundSentence === false) {
                                        	$tmpText .= "...";
					}
					$theText = $tmpText;
                                }
*/
				$feedInfo['description'] = $theText;

				$rss[] = $feedInfo;
			}
		}
	}


	// Strip tags with no content as specified in the $tag_types array; e.g. array("em", "a").
	function remove_no_content_tags($the_text, $tag_types) {

		foreach ($tag_types as $tag_type) {
			$the_text = $this->remove_no_content_tag($the_text, $tag_type);
		}

		return $the_text;
	}

	// Strip tag with no content; e.g. "<em/>", "<a />".
	function remove_no_content_tag($in_text, $tag_type) {

		$updated_text = $in_text;
		$offset = 0;

		while (TRUE) {

			$idxStart = stripos($updated_text, "&lt;" . $tag_type, $offset);
			if ($idxStart === FALSE) {
				// Tag not present. Done.
				break;
			}

			// Further validate the tag:
			// In order to distingiuish <b> from <br>, we need to inspect the next character,
			// which can be "&", " ", or "/"; i.e. <b>, <b >, <b/>.
			$next_char = $updated_text[$idxStart + strlen("&lt;" . $tag_type)];
			if ($next_char != '&' && $next_char != ' ' && $next_char != '/') {
				// Tag not present. Done.
				break;
			}

			// Update offset, since we've examined characters before the offset already.
			$offset = $idxStart + strlen("&lt;" . $tag_type);

			// Check if the tag is closed by "/>" (for no content).
			$isNoContent = stripos($updated_text, "/&gt;", $offset);
			if ($isNoContent === FALSE) {
				// OK. Not terminated by "/>"; i.e. Has content.
				continue;
			}

			// Remove this tag.
			$updated_text = substr($updated_text, 0, $idxStart) . substr($updated_text, $isNoContent + strlen("/&gt;"));

			// Set offset to next point of inspection after the removal of tag.
			$offset = $idxStart;
		}

		return $updated_text;
	}


	function get_feed_array($url) {
		
		$group_id = $this->groupId;;
			
		// get the feedage from DB
		$rs = db_query_params("select * from project_feed_settings where group_id=$1", array($group_id));
			
		// if no record found, set the age to default of 0 days which selects all items.
		if (pg_num_rows($rs) == 0) {
			$age = 0;
		}
		else {
			$row = pg_fetch_array($rs);
			$age = $row['refresh_time'];				
		}
			
		$rss = array();
		$ts = array();

		// push data to two diamentional array from all feeds 
		foreach ($url as $feed_url) {
			// Parse RSS Xml using SimpleXML.
			$this->parseRSSXmlSimple($rss, $feed_url, $age);
		}
					
		// prepare sorting logic.
		foreach ($rss as $key => $row) {
			$ts[$key] = strtotime($row['pubDate']);
		}
			
		// sort two diamentional array
		array_multisort($ts, SORT_DESC, $rss);

		return($rss);
	}

	
	function getProjectXmlDataInternal() {
		$groupId = $this->groupId;
		$project = $this->project;
		
		$xmlData = parent::getProjectXmlDataInternal();

		// ######################### News
		
		//////////////////////// CACHING LOGIC STARTS /////////////////////////////////////////
		
		$filename = $_SERVER['DOCUMENT_ROOT'] . "/newscache/" . $groupId . ".xml";
	
		$cache_duration = 4; // number of hours 
		$cache_timestamp = time() - $cache_duration * 60 * 60;

		$is_fresh = false;

		// check if file exists for this group
		if (file_exists($filename)) {
			// check the cached file age
			if ($cache_timestamp < filectime($filename)) {
				// OK to use cache file.
				return file_get_contents($filename);
			}
			// Cache expired. Fetch new contents and re-popluate cache file.
		}
		else {
			// File doe snot exist yet. Fetch contents and populate into cache file.
		}

		////////////////////////////////// CACHING LOGIC ENDS /////////////////////////////////////////

		if (!$is_fresh) {
			if ($project->usesNews()) {
				$resNews = db_query_params(
				"SELECT u.user_name, u.realname,
					nb.id, nb.forum_id, nb.summary, nb.post_date, nb.details, 
					nb.request_global::int, nb.sidebar_display::int 
				FROM users AS u, news_bytes AS nb, groups 
				WHERE u.user_id = nb.submitted_by 
				AND nb.group_id=$1
				AND nb.is_approved <> 4
				AND nb.group_id=groups.group_id
				AND groups.status='A'
				ORDER BY post_date DESC", array($groupId)
				);

				if ($resNews) {
					$newsFieldNames = db_fieldnames($resNews);
					$newsNumRows = db_numrows($resNews);
				
					// added a project id field (group id) for using on RSS Feed for project news. Added by Achint 06/04/2013
					$xmlData .= "<project_id>" . $groupId . "</project_id>\n";
					$xmlData .= "<news_list>";
				
					// Get list of RSS Feeds for this project 
					$resRSS = db_query_params("SELECT * FROM rss_feeds_project WHERE group_id=$1", array($groupId));
				
					$url[] = "http://" . $_SERVER['SERVER_NAME'] . "/project/rss.php?group_id=" . $groupId;
				
					if (pg_num_rows($resRSS) > 0) {
						while($row = pg_fetch_array($resRSS)) {
							$url[] = $row['rss_url'];
						}
						
					}
				
					// include the manually entered news from its RSS Feed. 
				
					$feed_array = $this->get_feed_array($url);
					foreach($feed_array as $feed_item) {
						$xmlData .= "<news>";
					
						$xmlData .= "<group_id>";
						$xmlData .= $groupId;
						$xmlData .= "</group_id>\n";
					
						$xmlData .= "<summary>";
						$xmlData .= $feed_item['title'];
						$xmlData .= "</summary>\n";	
									
						$xmlData .= "<summ_txt>";
						$xmlData .= $feed_item['description'];
						$xmlData .= "</summ_txt>\n";

						$xmlData .= "<news_url>";
						$xmlData .= $feed_item['link'];
						$xmlData .= "</news_url>\n";

						$xmlData .= "<post_date>";
						$xmlData .= $feed_item['pubDate'];
						$xmlData .= "</post_date>\n";
					
						$xmlData .= "</news>\n";
					}

					$xmlData .= "</news_list>\n";
					db_free_result($resNews);
				}

			}
		
			// dump the output in the XML Cache
			file_put_contents($filename, $xmlData);
		
			return $xmlData;
		
		  }
	}
}

?>
