/**
 *
 * SimtkPeopleFilters.js
 * 
 * File to filter people display.
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
 
SimtkPeopleFilters = function() {

	var container;
	var containerCenter;
	var strSrch = "";

	// Look up query parameter.
	var findQueryParam = function(param) {
		var out="";

		// Use window.location.href instead of window.location.search.
		// Look for last '?'. Then, replace commas "%2C" with ',' if present.
		var strHref = window.location.href;
		var strSearch = strHref.substring(strHref.lastIndexOf("?"), strHref.length);
		var strSearch = decodeURIComponent(strSearch);
		var queries = strSearch.slice(1).split("&");
		$.each(queries, function(i, item){
			if (item.split("=")[0].toLowerCase() === param.toLowerCase())
				out = item.split("=")[1]
		});
		return out;
	};


var func = {
setup: function(c) {
	container = c.find(".category-center");
	containerCenter = c.find(".category-center");
	containerCenter.append("<div class='after-categories' style='clear: both' /> ");
	containerCenter.append("<div id='people-listing' class='news_and_trending_projects' /> ");

	// Insert a message before result is displayed.
	containerCenter.append("<div id='searching'>Searching...</div>");
	strSrch = findQueryParam("srch");
	var theURL = "/search/ajax_people.php?srch=" + strSrch;

	// Require a minimum of 3 characters.
	if ($.trim(strSrch).length < 3) {
		$(".SearchMsg").text("");
		containerCenter.find(".news_and_trending_projects").html("<span style='font-size:20px;'>Your search returned too many results.<br/>Please use a longer string.</span>");
		$("#searching").remove();
		return;
	}

	// Get people search result.
	$.ajax({
		dataType: "json",
		url: theURL
	}).done(function(peopleData) {
		// Clear message after result is obtained.
		$("#searching").remove();
		$(".CountPeople").text(peopleData.length);

		var project_list = containerCenter.find(".news_and_trending_projects");
		for (var cnt = 0; cnt < peopleData.length; cnt++) {

			var item = peopleData[cnt];
			var pDiv = $("<div class='project_representation'/>");
			var theLink = "/users/" + item['user_name'];
			pDiv.append("<div class='wrapper_img'><a href='" + theLink + "'>" +
				"<div class='item_home_news'><img alt='pic' src='/userpics/" + 
				item['picture_file'] + "' " +
				"onerror=\"this.src='/userpics/user_profile.jpg'\" " +
				"class='news_img' /></a></div></div>");

			var strPersonDesc = "<div class='wrapper_text'>" + 
				"<h4><a href='" + theLink + "' class='title'>" + 
				item['realname'] + 
				"</a></h4>";

			var strInfo = "";
			var strUniversity = $.trim(item['university_name']);
			if (strUniversity != "") {
				strInfo += strUniversity;
			}
			var strLab = $.trim(item['lab_name']);
			if (strLab != "") {
				if (strInfo == "") {
					strInfo = strLab;
				}
				else {
					strInfo += "&nbsp;" + strLab;
				}
			}
			if (strInfo != "") {
				strPersonDesc += strInfo + "<br/><br/>";
			}

			var strInterestSimtk = $.trim(item['interest_simtk']);
			if (strInterestSimtk != "") {
				strPersonDesc += strInterestSimtk + "<br/>";
			}

			var strInterestOther = $.trim(item['interest_other']);
			if (strInterestOther != "") {
				strPersonDesc += strInterestOther + "<br/>";
			}

			strPersonDesc += "</div>";

			pDiv.append(strPersonDesc);

			project_list.append(pDiv);
		}
	}).fail(function(peopleData){
		alert("Something went wrong while retrieving people!");
	});
},

}
return func;
}();

