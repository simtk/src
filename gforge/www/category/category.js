/**
 *
 * category.js
 * 
 * File to support category display of projects.
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
 
$(document).ready(function() {

	// Get category projects.
	getCategoryProjects();
});


// Get featured projects.
var getFeaturedProjects = function(projectData) {

	$("#categories_featured").html("Loading...");

	// Get category.
	var catId = findQueryParam("cat");
	cats = catId.split(",");

	// Retrieve featured groups.
	var theURL = "/category/featuredGroups.php?cat=" + cats[0];
	$.ajax({
		url: theURL,
		success: function(strFeaturedGroups) {

			// Get featured projects as a comma-separated string.
			if (strFeaturedGroups.indexOf("***ERROR***") !== -1) {
				// Error retrieving from  featured_projects table.
				console.log("Error retrieving from  featured_projects table.!!!");
				return;
			}

			// Display featured projects.
			handleFeaturedProjects(projectData, strFeaturedGroups);
		}
	});
};


// Get category projects.
var getCategoryProjects = function() {

	$("#categories_home").html("Loading...");

	// Get category.
	var catId = findQueryParam("cat");

	cats = catId.split(",");

	// Get category projects.
	var theURL = "/category/ajax_categories.php?cat=" + cats[0];
	$.ajax({
		dataType: "json",
		url: theURL
	}).done(function(projectData) {

		// Display featured projects.
		handleCategoryProjects(projectData, cats[0]);
	});
};


// Display featured projects.
var handleFeaturedProjects = function(projectData, strFeaturedGroups) {

	// Generate an array from the string.
	var arrFeaturedGroups = strFeaturedGroups.split(',');

	// Add the featured projects once we have project data
	var featuredDiv = $("#categories_featured");
	$("#categories_featured").html("");

	// Use as associative array to avoid duplicates.
	var featuredProjs = [];

	for (var cnt = 0; cnt < projectData.length; cnt++) {
		for (var j = 0; j < projectData[cnt].trove_cats.length; j++) {

			if (strFeaturedGroups.indexOf("***ERROR***") !== -1) {

				// Cannot retrieve from featured_projects table.
				// Just show the candidate featured projects.

				// Fill in array if key does not exist yet.
				if (!(projectData[cnt].unix_group_name in featuredProjs)) {
					featuredProjs[projectData[cnt].unix_group_name] = 
						projectData[cnt];
				}
			}
			else {
				for (var cntFG = 0; cntFG < arrFeaturedGroups.length; cntFG++) {
					// Has a feature group "candidate".
					if (arrFeaturedGroups[cntFG] == 
						projectData[cnt].group_id) {

						// In feature group specification.

						// Fill in array if key does not exist yet.
						if (!(projectData[cnt].unix_group_name in featuredProjs)) {
							featuredProjs[projectData[cnt].unix_group_name] = 
								projectData[cnt];
						}

						break;
					}
				}
			}
		}
	}

	// Fetch featured project info from associative array.
	// Iterate over all keys.
	var cntFeaturedProjects = 0;
	for (var fpKey in featuredProjs) {
		cntFeaturedProjects++;
		renderProjectInfo("categories_featured", "item_featured_categories",
			featuredProjs[fpKey], false);
	}
	if (cntFeaturedProjects == 0) {
		// Hide DIV for Featured Projects.
		$(".featured_projs").hide();
	}
	else {
		// Show DIV for Featured Projects.
		$(".featured_projs").show();
	}
};


// Display category projects.
var handleCategoryProjects = function(projectData, catId) {

	// Update number of projects available.
	$(".statbox2-left .statbox2-number").text(projectData.length);

	// Added fullname sort support. Store trove_cat_id.
	var allCategories = {};

	// Get project data categories and associated counts.
	$.each(projectData, function(index, value) {
		if (value.trove_cats && value.trove_cats.length > 0) {
			for (var idx = 0; idx < value.trove_cats.length; idx++) {

				value.trove_cats[idx].id = parseInt(value.trove_cats[idx].id);
				// Use tmpCatId instead of repeated access.
				var tmpCatId = value.trove_cats[idx].id;
				if (!allCategories[tmpCatId]) {
					// Add trove_cat_id to array.
					allCategories[tmpCatId] = value.trove_cats[idx];
				}
			}
		}
	});

	// Initialize and define comparator function.
	var comp = function(a,b) {
		if (!a.modified || !b.modified)
			return 0;
		var aModified = parseInt(a.modified);
		var bModified = parseInt(b.modified);
		if (aModified < bModified)
			return 1;
		if (bModified < aModified)
			return -1;
		return 0;
	};

	// Sort projects data by last modified date.
	projectData.sort(comp);

	// Clear previous entries.
	$("#categories_home").html("");

	numProjsToShow = 8;
	if (projectData.length < numProjsToShow) {
		numProjsToShow = projectData.length;
	}
	for (var cnt = 0; cnt < numProjsToShow; cnt++) {
		renderProjectInfo("categories_home", "item_home_categories", 
			projectData[cnt], false);
	}

	if (projectData.length > numProjsToShow) {

/*
		strExtras = "<div class='related_link_trigger'>";
		strExtras += "<h2><a href='#' onclick=" + '"' + 
			"$('.related_more').show();" +
			"$('.related_link_trigger').hide();" +
			"return false;" + '"' + 
			">See all</a></h2>";
		strExtras += "</div><!-- related_link_trigger -->"; 
		strExtras += "<div class='related_more' id='related_more' " +
			"style='display:none'>";
		strExtras += "</div><!-- related_more -->";

		$("#categories_home").append(strExtras);

		// Add the rest of the projects.
		for (var cnt = numProjsToShow; cnt < projectData.length; cnt++) {
			renderProjectInfo("related_more", "item_home_categories", 
				projectData[cnt], false);
		}
*/
		strExtras = "<div class='related_link_trigger'>";
		strExtras += "<h2><a href='/search/search.php?" +
			"cat=" + catId + "'>See all</a></h2>";
		strExtras += "</div><!-- related_link_trigger -->"; 

		$("#categories_home").append(strExtras);
	}

	if (projectData.length == 0) {
		$("#categories_home").append("No Projects Found");
	}

	// Get featured projects.
	// NOTE: category project data are passed to this method. 
	getFeaturedProjects(projectData);
};


var findQueryParam = function(param) {

	var out="";

	// Support hashchange returned from older browser when using History.js.
	//
	// Use window.location.href instead of window.location.search.
	// Look for last '?'. Then, replace commas "%2C" with ',' if present.
	var strHref = window.location.href;
	var strSearch = strHref.substring(strHref.lastIndexOf("?"), strHref.length);
	var strSearch = decodeURIComponent(strSearch);
	var queries = strSearch.slice(1).split("&");
	$.each(queries, function(i, item) {
		if (item.split("=")[0].toLowerCase() === param.toLowerCase())
			out = item.split("=")[1]
	});

	return out;
};


// Display project item.
var renderProjectInfo = function(targetDivId, itemName, projectItem, use_thumb) {

	if (typeof(use_thumb) === 'undefined') {
		use_thumb = true;
	}

	var pDiv = $("#" + targetDivId);

	var strToAdd = "<div class='" + itemName + "'>";

	var theLink = "/projects/" + projectItem.unix_group_name;
	// Provide default logo if logo is not found.
	if (use_thumb) {
		strToAdd += "<div class='categories_img'>" + 
			"<img alt='pic' src='/logos/" + projectItem.logo_file + 
			"_thumb' onerror=\"this.src='/logos/_thumb'\"/>" + 
			"</div>";
	}
	else {
		strToAdd += "<div class='categories_img'>" +
			"<img alt='pic' src='/logos/" + projectItem.logo_file + 
			"' onerror=\"this.src='/logos/_thumb'\"/>" +
			"</div>";
	}

	// Add "Downloads" and "Last updated".
	var tmpDate = new Date(parseInt(projectItem.modified) * 1000);
	var theDate = ((tmpDate.getMonth() + 1) < 10 ? 
			("0" + (tmpDate.getMonth() + 1)) : 
			(tmpDate.getMonth() + 1)) + "/" +
		(tmpDate.getDate() < 10 ? 
			("0" + tmpDate.getDate()) : 
			tmpDate.getDate()) + "/" +
		tmpDate.getFullYear();

	strToAdd += "<div class='categories_text'>" + 
		"<h4><a href='" + theLink + "' class='title'>" + 
			projectItem.group_name + "</a></h4>" + 
		"<p>" + $.trim(projectItem.short_description) + "</p>" +
		"<div class='categories_data'>" +
			"Total downloads: " + projectItem.downloads + " | " +
			"Last updated: " + theDate + 
		"</div>" +
		"</div>";

	strToAdd += "<div style='clear: both;'></div>";

	strToAdd += "</div'>";

	pDiv.append(strToAdd);

	return pDiv;
};



