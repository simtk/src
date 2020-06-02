/**
 *
 * SimtkFilters.js
 * 
 * File to filter projects display.
 *
 * Copyright 2005-2019, SimTK Team
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
 
SimtkFilters = function(){
	// For keeping initial project items count to be re-used later.
	var initFilterItemsCount = -1;
	var container;
	var containerCenter;
	var containerLeft;
	var searchItems = window.location.search.replace(/^\?/, "").split("&");
	var strCats;
	var arrCats;
	var isAllGroups = false;
	var useInitCategoryId = false;
	var allFullNames = [];
	var projects;
	// For page selection.
	var pageSelection = 0;
	// Initialize and define comparator function. The comparator function changes
	// when different sorting criteria is selected (sort-date/sort-project/sort-downloads). HK.
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
	//var default_pagination = 0;
	var default_pagination = 10; // Set default to 10 to turn it on. HK.

	// Added fullname sort support. Store trove_cat_id. HK.
	var sortedCatIds = [];

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


	// Handle loading of page.
	// Support reading of parameters as specified in the URL given by the user.
	var handlePageLoad = function() {
		strCats = findQueryParam("cat");

		// Set checkboxes.
		var checkboxes = $(".content input[type='checkbox']:not(.myDisabled)");
		checkboxes.prop("checked", null);
		if (strCats && strCats != "") {
			// Has category id.
			$.each(strCats.split(","), function(i,item) {
				checkboxes.filter("[value='" + item + "']").prop("checked", "checked");
			});

			// Category page; Use sort by download as default.
			$('.mySelect').val("Downloads").change();
		}
		else {
			// Check for presence of "srch" string.
			var tmpSearch = findQueryParam("srch"); 
			if ($.trim(tmpSearch) != "") {
				// For search page, use sort by relevance as default.
				$('.mySelect').val("Relevance").change();
			}
			else {
				var tmpSort = findQueryParam("sort"); 
				if ($.trim(tmpSort) == "date") {
					// Not category page nor community pages: use sort by download as default.
					$('.mySelect').val("Date").change();
				}
				else {
					// Not category page nor community pages: use sort by download as default.
					$('.mySelect').val("Downloads").change();
				}
			}
		}
		if (containerLeft.find("input[type='checkbox']:not(.myDisabled):checked").length === 0) {
			containerLeft.find(".no-filter").prop("checked", "checked");
		}

		if (projects && projects.items) {
			func.filterCategories();
		}

		// If History is sending page back to different 
		// searching criteria, fill in search text.
		// Otherwise, fill in empty string.
		var strSearch = "";
		var loc = window.location.href;
		// Change back whitespaces first.
		var strLoc = unescape(loc);
		if (strLoc.indexOf("srch=") >= 0) {
			// Has search string.
			var idxSearch = strLoc.indexOf("srch=") + 5; 
			strSearch = strLoc.substring(idxSearch, strLoc.length);
			var idxLast = strSearch.indexOf("&");
			if (idxLast >=0) {
				// Has other parameters. Only include up to the next "&".
				strSearch = strSearch.substring(0, idxLast);

				// Remove leading and trailing '+' characters (i.e. spaces).
				strSearch = strSearch.replace(/^\++|\++$/gm,'');

				// Handle '+' as space to do AND search if there are enclosing " or '.
				// Strip leading and trailing " or '.
				if ((strSearch.substring(0, 1) == '"' && 
					strSearch.substring(strSearch.length-1) == '"') ||
					(strSearch.substring(0, 1) == "'" && 
					strSearch.substring(strSearch.length-1) == "'")) {

					strSearch = strSearch.substring(1, strSearch.length - 1);
					strSearch = strSearch.split("+").join(" ");
				}
				else {
					// Handle '+' as space to do OR search. 
					strSearch = strSearch.split("+").join(",");
				}
			}
		}
		$("#titleFilter").val(strSearch);

		// Text search action invocation: From history.
		myTextSearchClick(true);
	};


	// Invoke text search click: handle user input and handle history playback.
	var myTextSearchClick = function(isHistory) {
		// History playback.
		// Invoke search click handler.
		myTextSearchClickHandler();
	};


	// Click handler for text search input.
	var myTextSearchClickHandler = function() {

		// Save search text in history and reset page to first page.
		var loc = window.location.href;
		var strTitleSearch = $("#titleFilter").val();

		// Handle '+' as space to do AND search if there are enclosing " or '.
		// Strip leading and trailing " or '.
		if ((strTitleSearch.substring(0, 1) == '"' && 
			strTitleSearch.substring(strTitleSearch.length-1) == '"') ||
			(strTitleSearch.substring(0, 1) == "'" && 
			strTitleSearch.substring(strTitleSearch.length-1) == "'")) {

			strTitleSearch = strTitleSearch.substring(1, strTitleSearch.length - 1);
			strTitleSearch = strTitleSearch.split("+").join(" ");
		}
		else {
			// Handle '+' as space to do OR search. 
			strTitleSearch = strTitleSearch.split("+").join(",");
		}

		var project_list = containerCenter.find(".news_and_trending_projects");
		project_list.html("");
		// NOTE: Hide/Show is necessary because Chrome has problem of caching old content.
		// Hide/Show refreshes the page.
		$(".news_and_trending_projects").hide().show(0);
		$(".page_nav").hide().show(0);

		if (projects) {
			projects.display();
		}
	};


	// This method is invoked at initial page load.
	// This loading is necessary because currently only Chrome and Safari browsers
	// send onpopstate event on initial page load, but others, like Firefox, IE, and Opera, do not.
	// However, the load event is always sent; hence, do a page load here to allow read in of
	// URL parameters as specified by the user.
	$(window).load(function() {
		handlePageLoad();
	});

	// Support hashchange event in older browser. HK.
	// Note: This method will not get invoked when using newer browsers.
	$(window).bind("hashchange", function(e) {
		// Note: the following method has to be invoked here; otherwise, population 
		// of parameters from URL as specified by the user, or usage of back button 
		// would not work properly.
		handlePageLoad();
	});

	// Note: onpopstate is used for newer browsers. 
	// Older browsers do not use the onpopstate event and 
	// would use the hashchange event above instead.
	window.onpopstate = function(curPopstate) {
		return function(e) {
			// Note: the following method has to be invoked here; otherwise, population 
			// of parameters from URL as specified by the user, or usage of back button 
			// would not work properly.
			handlePageLoad();

			if (typeof curPopstate === "function") {
				curPopstate(e);
			}
		}
	} (window.onpopstate);

	// Handle carriage return from textfield. HK.
	$(function() {
		$("#titleFilter").keyup(function(event) {
			// Fade out featured projects list on search text input.
			// Close div. Change text to "&#9668;" which is left arrow.
			$("#featured-projects").attr("title", "Click Here to See Featured Projects List");
			$("#featured-projects>span").css("padding-left", "0px").html("&#9668;");
			$(".featured-project-listing").fadeOut(500, "linear");

			// Text search action invocation: From user text input.
			myTextSearchClick(false);
		});
		$("#titleFilter").keypress(function(event) {
			if (event.which == 13) {
				// Start search when carriage return is entered.
				event.preventDefault();
			}
		});
	});


var func = {
setup: function(c, inUseInitCategoryId, inIsAllGroups) {
	isAllGroups = inIsAllGroups;
	useInitCategoryId = inUseInitCategoryId;
	sortedCatIds = []; // HK.
	var d = $.Deferred();
	container = c.find(".category-center");
	containerCenter = c.find(".category-center");
	containerLeft = c.find(".category-left");

	containerCenter.append("<div class='after-categories' style='clear: both' /> ");
	containerCenter.append("<div id='project-listing' class='news_and_trending_projects' /> ");

	containerHeader = c.find(".category-header");
	if (containerHeader.length > 0) {
		containerHeader.find(".mySelect").change(function(e) {
			// Item selected in "Sort by" select.
			// Take action according to the item selected.
			var selected = $('.mySelect').val();
			if (selected == "Date") {
				// Invoke sort by date.
				mySortDate();
			}
			else if (selected == "Title") {
				// Invoke sort by project.
				mySortProject();
			}
			else if (selected == "Downloads") {
				// Invoke sort by downloads.
				mySortDownloads();
			}
			else if (selected == "Relevance") {
				// Invoke sort by relevance.
				mySortRelevance();
			}
		});
	}

	for (var i = 0; i < searchItems.length; i++) {
		if (searchItems[i].split("=")[0].toLowerCase() === "cat") {
			strCats = searchItems[i].split("=")[1];
		}
	}

	var theURL;
	if (useInitCategoryId) {
		arrCats = strCats.split(",");
		theURL = "/category/ajax_categories.php?cat=" + arrCats[0];
		if (isAllGroups) {
			// Include both private and public projects.
			theURL += "&all_groups=1";
		}
	}
	else {
		theURL = "/category/ajax_categories.php";
		if (isAllGroups) {
			// Include both private and public projects.
			theURL += "?all_groups=1";
		}
	}

	// Insert a message before result is displayed.
	containerCenter.append("<div id='searching'>Searching...</div>");
	$.ajax({
		dataType: "json",
		url: theURL
	}).done(function(projectData){
		// Clear message after result is obtained.
		$("#searching").remove();

		$(".category-stats strong:first").text(projectData.length);
		$(".CountProjects").text(projectData.length);

		// Information on all associated categories present.
		var allCategories = {};

		projects = FilterSearch.create({items: projectData, display: func.projectDisplay});

		// NOTE: Do a one time set up of match scores if search string is present.
		// This process needs to be done first, because otherwise, match scores
		// calculation does not take place until the projects.display() method
		// which is after project.sort() invocation. However, projects.sort()
		// may need the match score data if sort by relevance is used.
		// Get search string.
		var strTitleSearch = $("#titleFilter").val();
		if ($.trim(strTitleSearch) != "") {
			var filteredItems = func.filterItemsByTitle(projects.items, strTitleSearch);
			// NOTE: Only filteredItems have match score property, but not projects.items.
			// If there is text search filtering, after the text search filtering,
			// set project items to use the filtered items to get the match score property.
			projects.items = filteredItems;
		}

		// Get project data categories and associated counts. HK.
		$.each(projectData, function(index, value) {
			if (value.trove_cats && value.trove_cats.length > 0) {
				for (var idx = 0; idx < value.trove_cats.length; idx++) {
					value.trove_cats[idx].id = parseInt(value.trove_cats[idx].id);
					// Use tmpCatId instead of repeated access. HK.
					var tmpCatId = value.trove_cats[idx].id;
					if (!allCategories[tmpCatId]) {
						// Add trove_cat_id to array. HK.
						sortedCatIds.push(tmpCatId);

						allCategories[tmpCatId] = value.trove_cats[idx];
					}
				}
			}
		});

		// Sort array of trove_cat_id using fullname of category. HK.
		sortedCatIds.sort(function(a, b) {
			var nameA = allCategories[a].fullname.toLowerCase();
			var nameB = allCategories[b].fullname.toLowerCase();
			if (nameA < nameB) return -1;
			if (nameA > nameB) return 1;
			return 0;
		});

		// Fill category names. Use sorted categorey ids. HK.
		allFullNames = [];
		var displayCategoryIds = [];
		for (cnt in sortedCatIds) {
			var theCatId = sortedCatIds[cnt];
			allFullNames[theCatId] = allCategories[theCatId].fullname;
		}

		// Sort projects. HK.
		projects.sort(comp);

		projects.display();

		if (arrCats && arrCats.length > 1) {
			var countChecks = 0;
			var checkboxes = $(".content input[type='checkbox']:not(.myDisabled)");
			checkboxes.prop("checked", null);
			$.each(strCats.split(","), function(i,item) {
				if (useInitCategoryId && arrCats && item != arrCats[0]) {
					// Check box if not the main category.
					// Note: the main category is always checked and it is disabled.
					// Hence, no need to check it.
					// Handle only other checkboxes here!
					checkboxes.filter("[value='" + item + "']").prop("checked", "checked");
					countChecks++;
				}
			});
			func.filterCategories();

			if (countChecks === 0) {
				// Check ALL if other checkboxes that are not disabled are not checked.
				containerLeft.find(".no-filter").prop("checked", "checked");
			}
		}
		else {
			containerLeft.find(".no-filter").prop("checked", "checked");
		}

		d.resolve(projectData);
	}).fail(function(projectData){
		alert("Something went wrong while retrieving the categories!");
		d.reject(projectData);
	});

	$(".content").on("change", containerLeft.find("input[type='checkbox']:not(.filter-by)"), function(e){
		// Unselecting the "All" checkbox when no other checkboxes are selected
		// will re-select the "All" checkbox. HK.
		var hasChecked = false;
		$(".content input[type='checkbox']:not(.myDisabled)").each(function() {
			if (this.checked === true) {
				if (hasChecked === false) {
					hasChecked = true;
				}
			}
		});
		if (hasChecked === false) {
			//alert("No checkbox selected");
			$(".no-filter").prop("checked", "checked");
		}
	});
	$(".content").on("change", "input[type='checkbox']:not(.myDisabled):not(.no-filter)", function(e){
		containerLeft.find(".no-filter").prop("checked", null);
		func.onFilterChange(e);
	});
	$(".content").on("change", ".no-filter", function(e) {
		$(".content input[type='checkbox']:not(.myDisabled):not(.no-filter):checked").prop("checked", null);
		func.onFilterChange(e);
	});

	$("#featured-projects").click(function() {
		// Note: Flip-flop padding-left to be 0px and 1px to decide whether the div is opened or closed.
		// Cannot use visible to decide whether the div is opened or closed: It does not work
		// reliably in Chrome. Also, cannot check for the code "&#9668;" or "&#9660;" either.
		// Check for the value of "1px" vs "0px" instead in the padding-left attribute; since the arrow 
		// floats to the right, the padding-left value here does not matter and is only used for 
		// distinguishing the 2 states.

		// Note: in Chrome, the "padding-left" may be returned as a float value.
		// Get the integer ceiling value to check.
		var theText = $("#featured-projects>span").css("padding-left");
		var pxIdx = theText.indexOf("px");
		var pxVal = theText.substring(0, pxIdx);
		pxVal = Math.ceil(pxVal) + "px";
		if (pxVal.indexOf("1px") >= 0) {
			// Currently opened. Close div. Change text to "&#9668;" which is left arrow.
			$(".featured-project-listing").fadeOut(500, "linear");
			$("#featured-projects>span").css("padding-left", "0px").html("&#9668;");
			$("#featured-projects").attr("title", "Click Here to See Featured Projects List");
		}
		else {
			// Currently closed. Open div to show contents. Change text to "&#9660;" which is down arrow.
			$(".featured-project-listing").fadeIn(500, "linear");
			$("#featured-projects>span").css("padding-left", "1px").html("&#9660;");
			$("#featured-projects").attr("title", "");
		}
	});


	// Sort by date.
	var mySortDate = function() {
		// Change comparator function. HK.
		comp = function(a,b) {
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

		var project_list = containerCenter.find(".news_and_trending_projects");
		project_list.html("");
		// NOTE: Hide/Show is necessary because Chrome has problem of caching old content.
		// Hide/Show refreshes the page.
		$(".news_and_trending_projects").hide().show(0);
		$(".page_nav").hide().show(0);

		if (projects) {
			projects.sort(comp);
		}

		if (projects) {
			projects.display();
		}
	};
	// Sort by project.
	var mySortProject = function() {
		// Change comparator function. HK.
		comp = function(a,b) {
			if (!a.group_name || !b.group_name)
				return 0;
			if (a.group_name.toLowerCase() < b.group_name.toLowerCase())
				return -1;
			if (b.group_name.toLowerCase() < a.group_name.toLowerCase())
				return 1;
			return 0;
		};

		var project_list = containerCenter.find(".news_and_trending_projects");
		project_list.html("");
		// NOTE: Hide/Show is necessary because Chrome has problem of caching old content.
		// Hide/Show refreshes the page.
		$(".news_and_trending_projects").hide().show(0);
		$(".page_nav").hide().show(0);

		if (projects) {
			projects.sort(comp);
		}

		if (projects) {
			projects.display();
		}
	}
	// Sort my downloads.
	var mySortDownloads = function() {
		// Change comparator function. HK.
		comp = function(a,b) {
			if (!a.downloads || !b.downloads)
				return 0;
			// Need to first convert to integer to sort correctly.
			// The input is a string. HK.
			var aDownloads = parseInt(a.downloads);
			var bDownloads = parseInt(b.downloads);
			if (aDownloads < bDownloads)
				return 1;
			if (bDownloads < aDownloads)
				return -1;
			return 0;
		};

		var project_list = containerCenter.find(".news_and_trending_projects");
		project_list.html("");
		// NOTE: Hide/Show is necessary because Chrome has problem of caching old content.
		// Hide/Show refreshes the page.
		$(".news_and_trending_projects").hide().show(0);
		$(".page_nav").hide().show(0);

		if (projects) {
			projects.sort(comp);
		}

		if (projects) {
			projects.display();
		}
	}
	// Sort by relevance.
	var mySortRelevance = function() {
		// Change comparator function. HK.
		comp = function(a,b) {
			if (!a.scoreMatch || !b.scoreMatch)
				return 0;
			// Need to first convert to integer to sort correctly.
			// The input is a string. HK.
			var aScoreMatch = parseInt(a.scoreMatch);
			var bScoreMatch = parseInt(b.scoreMatch);
			if (aScoreMatch < bScoreMatch)
				return 1;
			if (bScoreMatch < aScoreMatch)
				return -1;
			if (!a.downloads || !b.downloads)
				return 0;
			// Need to first convert to integer to sort correctly.
			// The input is a string. HK.
			var aDownloads = parseInt(a.downloads);
			var bDownloads = parseInt(b.downloads);
			if (aDownloads < bDownloads)
				return 1;
			if (bDownloads < aDownloads)
				return -1;
			return 0;
		};

		var project_list = containerCenter.find(".news_and_trending_projects");
		project_list.html("");
		// NOTE: Hide/Show is necessary because Chrome has problem of caching old content.
		// Hide/Show refreshes the page.
		$(".news_and_trending_projects").hide().show(0);
		$(".page_nav").hide().show(0);

		var checked_els = $(".content input[type='checkbox']:not(.no-filter):checked");
		var checked_ids = [];
		checked_els.each(function(i, el){checked_ids.push(parseInt($(el).val()));});

		if (projects) {
			projects.reset();
			projects.filter("trove_cats", checked_ids, func.projectFilter);

			var strTitleSearch = $("#titleFilter").val();
			if ($.trim(strTitleSearch) != "") {
				var filteredItems = func.filterItemsByTitle(projects.items, strTitleSearch);
				// NOTE: Only filteredItems have match score property, but not projects.items.
				// If there is text search filtering, after the text search filtering,
				// set project items to use the filtered items to get the match score property.
				projects.items = filteredItems;
		}

		// Get project data categories and associated counts. HK.
			projects.sort(comp);
		}

		if (projects) {
			projects.display();
		}
	}

	containerCenter.on("click", ".page_nav .page_back, .page_nav .page_next, .page_nav .page_number", function(e) {
		e.preventDefault();

		var loc = window.location.href;
		var newPage = parseInt(findQueryParam("page")) || 0;

		if ($(e.currentTarget).hasClass("page_back")) {
			//newPage = Math.max(0, newPage - 1);
			if (pageSelection > 0) {
				// Set to previous page.
				newPage = pageSelection - 1;
			}
		}
		else if ($(e.currentTarget).hasClass("page_next")) {
			//newPage = Math.min(newPage + 1, containerCenter.find(".page_nav .page_number").length);
			// Set to next page.
			newPage = Math.min(pageSelection + 1, containerCenter.find(".page_nav .page_number").length);
		}
		else if ($(e.currentTarget).hasClass("page_number")) {
			newPage = $(e.currentTarget).text() - 1;
		}

		var project_list = containerCenter.find(".news_and_trending_projects");
		project_list.html("");
		// NOTE: Hide/Show is necessary because Chrome has problem of caching old content.
		// Hide/Show refreshes the page.
		$(".news_and_trending_projects").hide().show(0);
		$(".page_nav").hide().show(0);

		// Perform sort each time as radio button may have changed. HK.
		if (projects) {
			projects.sort(comp);
			// Update page selection.
			pageSelection = newPage;
			projects.display();
		}
	});

	return d;
},

	projectDisplay: function(items) {

		var catItems = {};
		//var page = parseInt(findQueryParam("page")) || 0;
		// Go to page selected.
		var page = pageSelection;
		var pagination = parseInt(findQueryParam("pagination")) || default_pagination;
		if (pagination <= 0 || isNaN(pagination))
			pagination = 0;
		var start = pagination === 0 ? 0 : pagination * page;

		// Retrieve the title search string.
		var strTitleSearch = $("#titleFilter").val();
		var filteredItems = func.filterItemsByTitle(items, strTitleSearch);

		// Get project data categories and associated counts. HK.
		$.each(filteredItems, function(index, value) {
			if (value.trove_cats && value.trove_cats.length > 0) {
				for (var idx = 0; idx < value.trove_cats.length; idx++) {
					value.trove_cats[idx].id = parseInt(value.trove_cats[idx].id);

					// Use tmpCatId instead of repeated access. HK.
					var tmpCatId = value.trove_cats[idx].id;
					if (!catItems[tmpCatId]) {
						// Initialize catItems[tmpCatId] to an array.
						catItems[tmpCatId] = [];
					}

					if (catItems[tmpCatId].indexOf(value) == -1) {
						catItems[tmpCatId].push(value);
					}
				}
			}
		});

		$(".lblCategory").each(function() {
			var theCatId = this.id;
			if (allFullNames[theCatId]) {
				if (catItems[theCatId]) {
					$('.' + theCatId + ' span').html(catItems[theCatId].length);
				}
				else {
					$('.' + theCatId + ' span').html(0);
				}
			}
		});

		// Updated count of categories. HK.
		if (initFilterItemsCount == -1) {
			// Keep initial count of project items.
			initFilterItemsCount = filteredItems.length;
		}
		// Use initial count of project items.
		$('.categoryAll span').html(initFilterItemsCount);

		if (start > filteredItems.length) {
			// Change to first page if there are insufficent entries.
			start = 0;
		}

		//var filteredItems = items;
		var end = pagination === 0 ? filteredItems.length : start + pagination;

		var project_list = containerCenter.find(".news_and_trending_projects");
		project_list.html("");
		// NOTE: Hide/Show is necessary because Chrome has problem of caching old content.
		// Hide/Show refreshes the page.
		$(".news_and_trending_projects").hide().show(0);
		$(".page_nav").hide().show(0);

		for (var i = start; i < end; i++) {
			if (filteredItems[i]) {
				project_list.append(func.renderProject(filteredItems[i], false));
			}
		}
		if (pagination > 0) {
			func.showPages(pagination, page, filteredItems, start);
		}
	},

	renderProject: function(project, use_thumb) {
		if (typeof(use_thumb) === 'undefined') {
			use_thumb = true;
		}
		var pDiv = $("<div class='project_representation'/>");

		var theLink = "/projects/" + project.unix_group_name;
		// Provide default logo if logo is not found. HK.
		if (use_thumb) {
			pDiv.append("<div class='wrapper_img'><a href='" + 
				theLink + 
				"'><img alt='pic' src='/logos/" + 
				project.logo_file + 
				"_thumb' onerror=\"this.src='/logos/_thumb'\"/></a></div>");
		}
		else {
			pDiv.append("<div class='wrapper_img'><a href='" + 
				theLink + 
				"'><img alt='pic' src='/logos/" + 
				project.logo_file + 
				"' onerror=\"this.src='/logos/_thumb'\"/></a></div>");
		}

		// Add "Downloads" and "Last updated". HK.
		var tmpDate = new Date(parseInt(project.modified) * 1000);
		//var theDate = (tmpDate.getMonth() + 1) + "/" + tmpDate.getDate() + "/" + tmpDate.getFullYear();
		var theDate = ((tmpDate.getMonth() + 1) < 10 ? ("0" + (tmpDate.getMonth() + 1)) : (tmpDate.getMonth() + 1)) + "/" +
			(tmpDate.getDate() < 10 ? ("0" + tmpDate.getDate()) : tmpDate.getDate()) + "/" +
			tmpDate.getFullYear();

		pDiv.append("<div class='wrapper_text'>" + 
			"<h4><a href='" + theLink + "' class='title'>" + project.group_name + 
			"</a></h4>" + 
			$.trim(project.short_description) + "<br/>" +
			"<span class='type'>Total downloads: </span>" + 
			"<span class='content'>" + project.downloads + "</span>" +
			"&nbsp;&nbsp" +
			"<span class='type'>Last updated: </span>" + 
			"<span class='content'>" + theDate + "</span>" +
			"<br/>" +
			"</div>");

		return pDiv;
	},

	showPages: function(pagination, page, items, start) {
		var pageNav = $("<div class='page_nav' />");
		containerCenter.find(".news_and_trending_projects").append(pageNav);
		// Do not show navigation if there are not any items. HK.
		if (items.length == 0) {
			pageNav.append("<span>* No projects meet selected criteria.</span>");
			return;
		}
		if (start > 0)
			pageNav.append("<a class='page_back'>Prev</a>");
		else {
			//pageNav.append("<span class='page_back inactive'>Prev</span>");
		}
		for (var cnt = 0; cnt * pagination < items.length; cnt++) {
			if (cnt === page) {
				pageNav.append("<span class='page_number inactive'>" + (cnt+1) + "</span>");
			}
			else {
				pageNav.append("<a class='page_number'>" + (cnt+1) + "</a>");
			}
		}
		if (pagination * (page + 1) >= items.length) {
			//pageNav.append("<span class='page_next inactive'>Next</span>");
		}
		else {
			pageNav.append("<a class='page_next'>Next</a>");
		}

		// Find and scale all "img" logos elements.
		$("img").each(function() {
			var theImage = new Image();
			theImage.src = $(this).attr("src");
			if (theImage.src.indexOf("/logos/") == -1) {
				// Not logos. Skip.
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
				var theScaledWidth = Math.floor(theRatio * theNaturalWidth);
				var theScaledHeight = Math.floor(theRatio * theNaturalHeight);
				// Add margin at top/bottom or left/right.
				var marginTop = Math.floor((elemHeight - theScaledHeight)/2);
				var marginLeft = Math.floor((elemWidth - theScaledWidth)/2);

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
	},


	projectFilter: function(item_cats, checked_ids) {
		var item_ids = [];
		for (var i = 0; item_cats && i < item_cats.length; i++) {
			item_ids.push(item_cats[i].id);
		}
		for (var i = 0; checked_ids && i < checked_ids.length; i++) {
			// indexOf operation is not supported by IE 8. 
			// Replace by the section below. HK.
			var foundIt = false;
			for (var cnt = 0, totalCnt = item_ids.length; cnt < totalCnt; cnt++) {
				if (item_ids[cnt] === checked_ids[i]) {
					foundIt = true;
					break;
				}
			}
			if (foundIt === false) {
				return false;
			}
		}

		return true;
	},

	onFilterChange: function(e){
		// Filter change. Reset page selection.
		pageSelection = 0;

		// Fade out featured projects list on filter change.
		// Close div. Change text to "&#9668;" which is left arrow.
		$("#featured-projects").attr("title", "Click Here to See Featured Projects List");
		$("#featured-projects>span").css("padding-left", "0px").html("&#9668;");

		// NOTE: Need hide()/show() here to avoid overlapping text in Chrome.
		$(".featured-project-listing").hide().show(0);
		$(".featured-project-listing").fadeOut(500, "linear");

		// NOTE: Hide/Show is necessary because Chrome has problem of caching old content.
		// Hide/Show refreshes the page.
		var project_list = containerCenter.find(".news_and_trending_projects");
		project_list.html("");
		$(".news_and_trending_projects").hide().show(0);
		$(".page_nav").hide().show(0);

		var checked_ids = func.filterCategories();
		var loc = window.location.href;

		if (useInitCategoryId && arrCats && arrCats[0]) {
			var catQuery = arrCats[0];
		}
		else {
			var catQuery = "";
		}

		if (checked_ids.length > 0) {
			if (catQuery != "") {
				catQuery += "," + checked_ids.join(",");
			}
			else {
				catQuery += checked_ids.join(",");
			}
		}

		// Show projects after filter change.
		if (projects) {
			projects.display();
		}
	},

	filterCategories: function() {

		var checked_els = $(".content input[type='checkbox']:not(.no-filter):checked");
		var checked_ids = [];
		checked_els.each(function(i, el){checked_ids.push(parseInt($(el).val()));});

		if (projects) {
			projects.reset();
			projects.filter("trove_cats", checked_ids, func.projectFilter);

			var strTitleSearch = $("#titleFilter").val();
			if ($.trim(strTitleSearch) != "") {
				var filteredItems = func.filterItemsByTitle(projects.items, strTitleSearch);
				// NOTE: Only filteredItems have match score property, but not projects.items.
				// If there is text search filtering, after the text search filtering,
				// set project items to use the filtered items to get the match score property.
				projects.items = filteredItems;
			}

			// Sort projects. HK.
			projects.sort(comp);

			projects.display();
		}

		return checked_ids;
	},

	// Filter items using the text search filter.
	filterItemsByTitle: function(items, strTitleTextToSearch) {

		// Convert search text to lower case first.
		strTitleTextToSearch = strTitleTextToSearch.toLowerCase();

		if ($.trim(strTitleTextToSearch) == "") {
			// No search string.
			// Return first array.
			return items;
		}

		// Get original search string as in search textfield.
		strOrigSearchStr = false
		var loc = window.location.href;
		// Change back whitespaces first.
		var strLoc = unescape(loc);
		if (strLoc.indexOf("srch=") >= 0) {
			// Has search string.
			var idxSearch = strLoc.indexOf("srch=") + 5;
			strSearch = strLoc.substring(idxSearch, strLoc.length);
			var idxLast = strSearch.indexOf("&");
			if (idxLast >=0) {
				// Has other parameters. Only include up to the next "&".
				strOrigSearchStr = strSearch.substring(0, idxLast);
				// Change "+" to " " since " " is represented as "+" in URL.
				strOrigSearchStr = strOrigSearchStr.replace("+", " ");
				strOrigSearchStr = strOrigSearchStr.toLowerCase();
			}
		}

		// Check for OR of words in search string.
		var arrSearch = strTitleTextToSearch.split(",");
		var filteredItems = [];
		for (var cnt = 0; cnt < items.length; cnt++) {
			var scoreMatch = 0;
			var arrScores = [];
			for (var idx = 0; idx < arrSearch.length; idx++) {
				arrScores[idx] = 0;
			}
			for (var cntSearch = 0; cntSearch < arrSearch.length; cntSearch++) {
				var toSrch = arrSearch[cntSearch];
				if (items[cnt].group_name.toLowerCase().indexOf(toSrch) >= 0 ||
					items[cnt].unix_group_name.toLowerCase().indexOf(toSrch) >= 0 ||
					items[cnt].short_description.toLowerCase().indexOf(toSrch) >= 0 ||
					items[cnt].long_description.toLowerCase().indexOf(toSrch) >= 0 ||
					items[cnt].keywords.toLowerCase().indexOf(toSrch) >= 0 ||
					items[cnt].ontologies.toLowerCase().indexOf(toSrch) >= 0 ||
					items[cnt].projMembers.toLowerCase().indexOf(toSrch) >= 0) {
					// Found a match for one of strings to search for.
					arrScores[cntSearch]++;
				}
			}
			for (idx = 0; idx < arrSearch.length; idx++) {
				if (arrScores[idx] > 0) {
					scoreMatch++;
				}
			}

			// NOTE: Add 1000 to score to give significance to full match.
			if (strOrigSearchStr != false) {
				if (items[cnt].group_name.toLowerCase().indexOf(strOrigSearchStr) >= 0 ||
					items[cnt].unix_group_name.toLowerCase().indexOf(strOrigSearchStr) >= 0 ||
					items[cnt].short_description.toLowerCase().indexOf(strOrigSearchStr) >= 0 ||
					items[cnt].long_description.toLowerCase().indexOf(strOrigSearchStr) >= 0 ||
					items[cnt].keywords.toLowerCase().indexOf(strOrigSearchStr) >= 0 ||
					items[cnt].ontologies.toLowerCase().indexOf(strOrigSearchStr) >= 0 ||
					items[cnt].projMembers.toLowerCase().indexOf(strOrigSearchStr) >= 0) {
					// Found full match of search string.
					scoreMatch += 1000;
				}
			}

			if (scoreMatch > 0) {
				// Has at least one match.
				items[cnt].scoreMatch = scoreMatch;
				filteredItems.push(items[cnt]);
			}
		}
		return filteredItems;
	}
}
return func;
}();

