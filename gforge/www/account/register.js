/**
 *
 * register.js
 * 
 * Javascript utilities to support registeration UI.
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
 
// For previewing user picture selected.
function previewImage(idInputPicfile, idUserPicPreview) {
	var theReader = new FileReader();
	theReader.readAsDataURL(document.getElementById(idInputPicfile).files[0]);

	theReader.onload = function(theReaderEvent) {
		document.getElementById(idUserPicPreview).src = theReaderEvent.target.result;
	};
}


// Add drag-and-drop support for previewing pictures.
function addDragAndDrop(idCompDragAndDrop, idInputPicfile, idUserPicPreview) {

	$("#" + idCompDragAndDrop).on("dragover", function(e) {
		e.preventDefault();
		e.stopPropagation();
	});
	$("#" + idCompDragAndDrop).on("dragenter", function(e) {
		e.preventDefault();
		e.stopPropagation();
	});
	$("#" + idCompDragAndDrop).on("drop", function(e) {
		if (e.originalEvent.dataTransfer) {
			if (e.originalEvent.dataTransfer.files.length) {
				e.preventDefault();
				e.stopPropagation();

				var theReader = new FileReader();
				theFile = e.originalEvent.dataTransfer.files[0];
				theReader.readAsDataURL(theFile);
				theReader.onload = function(theReaderEvent) {
					document.getElementById(idUserPicPreview).src = theReaderEvent.target.result;

					// Reset file input text value to empty.
					var theFileInput = document.getElementById(idInputPicfile);
					clone = theFileInput.cloneNode(true);
					theFileInput.parentNode.replaceChild(clone, theFileInput);
				}
			}
		}
	});
}


// Retrieve cookie by name.
// Note: Cookie is set in account/register.php or account/index.php.
function getMyCookie(theCookieName) {
	var name;
	var value;

	// Put cookies into an array.
	var arrCookies = document.cookie.split(";");
	for (var cnt = 0; cnt < arrCookies.length; cnt++) {
		name = arrCookies[cnt].substr(0, arrCookies[cnt].indexOf("="));
		value = arrCookies[cnt].substr(arrCookies[cnt].indexOf("=") + 1);
		if (name.trim() == theCookieName.trim()) {
			// Found cookie.
			return value;
		}
	}

	// Cookie not set for the cookie name and given user.
	return -1;
}

function overrideUrl(tfName) {

	// Change hidden override value to 1 from 0.
	var inputOverride = "#override_" + tfName;
	$(inputOverride).val("1");

	// Clear messages.
	var divElem = "#" + tfName;
	var divIndicator = "#" + tfName + "_indicator";
	var divTxtHint = "#" + tfName + "_txtHint";
	var divMessage = "#" + tfName + "_message";
	var buttonAccount = "#_account";
	$(divIndicator).html("");
	$(divTxtHint).html("");
	$(divMessage).html("");

	// This div is for showing message and is dynamically added and remove.
	var divMsg = "#" + tfName + "_msg";
	// Remove existing message div.
	$(divMsg).remove();

	// Reset textfield color of element.
	$(divElem).removeClass("url_textfield_alert");

	// Check for error elements; if none, reactivate the submit button
	errorItems = document.querySelectorAll(".url_textfield_alert");
	if (typeof errorItems != "undefined" && errorItems.length == 0) {
		// Enable Account button.
		$(buttonAccount).attr("value", nameAccountButton);
		$(buttonAccount).removeAttr("disabled");
	}

	return false;
}

var nameAccountButton = "";

// Validate URL specified in given textfield.
function validateURL(theTextField, theNameAccountButton) {

	// Remember name of account button for restoring later.
	// such that the validateURL the methods in this file 
	// can be used for both "Create Account" and "Update Account",
	// the only difference being the acocunt button name.
	nameAccountButton = theNameAccountButton;

	var tfName = theTextField.name;
	var divElem = "#" + tfName;
	var divIndicator = "#" + tfName + "_indicator";
	var divTxtHint = "#" + tfName + "_txtHint";
	var divMessage = "#" + tfName + "_message";
	var buttonAccount = "#_account";

	// Clear validation result.
	$(divElem).removeClass("url_textfield_alert");
	$(divMessage).html("");

	// This div is for showing message and is dynamically added and remove.
	var divMsg = "#" + tfName + "_msg";
	// Remove existing message div.
	$(divMsg).remove();

	// Get URL string from textfield..
	strUrl = theTextField.value;
	if (strUrl.length <= 0) {
		// Ignore.
		// Empty textfield.
		$(divIndicator).html("");
		$(divTxtHint).html("");

		// Check for error elements; if none, reactivate the submit button
		errorItems = document.querySelectorAll(".url_textfield_alert");
		if (typeof errorItems != "undefined" && errorItems.length == 0) {
			// Enable Account button.
			$(buttonAccount).attr("value", nameAccountButton);
			$(buttonAccount).removeAttr("disabled");
		}

		return;
	}

	$(divIndicator).html("<img src='/account/indicator.gif' width='16' height='16' />");
	$(divTxtHint).html("Validating URL ...");

	// Get cookies for website override.
	var cookieName = "override_" + tfName;
	var cookieWebsiteOverride = getMyCookie(cookieName);


	// Get input override component.
	var inputOverride = "#override_" + tfName;
	var inputOverrideValue = $(inputOverride).val();
	if (cookieWebsiteOverride != -1 || inputOverrideValue == 1) {
		// Ignore.
		// Website override; no need to validate URL.
		$(divIndicator).html("");
		$(divTxtHint).html("");
		return;
	}

	// Use jquery/ajax to invoke php to validate URL.
	// POST is used for sending data.
	$.ajax({type: "POST",
		url:	"/account/validateUrl.php",
		data:	{"URL": strUrl},
	})
	.done(function(strOut) {

		var data = strOut.split("#");
		var override = "";

		if (strOut.indexOf("Invalid") > 0 ||
			strOut.indexOf("Improper") > 0 ||
			strOut.indexOf("NotFound") > 0) {

			// URL is invalid.

			// Override message.
			override = "<span style='font-size:14px;'>" +
				"<span style='font-style:italic;'>" +
				"Invalid URL. Please try again.<br/></span>" +
				"<span style='font-size:12px;'>Verified URL correct?" +
				"<a href='#' onclick='return overrideUrl(\"" +
				tfName +
				"\");' class='override_url'>" +
				"&nbsp;Click to override warning.</a></span>";

			// Change textfield color of element.
			$(divElem).addClass("url_textfield_alert");

			// Disable Account button.
			$(buttonAccount).attr("value", "URLs are invalid");
			$(buttonAccount).attr("disabled", "disabled");
		}
		else {
			// Reset textfield color of element.
			$(divElem).removeClass("url_textfield_alert");

			// Check for error elements; if none, reactivate the submit button
			errorItems = document.querySelectorAll(".url_textfield_alert");
			if (typeof errorItems != "undefined" && errorItems.length == 0) {
				// Enable Account button.
				$(buttonAccount).attr("value", nameAccountButton);
				$(buttonAccount).removeAttr("disabled");
			}
		}

		// Update using validation result.
		var strIndicator = data[1];
		var strTxtHint = data[2];
		var strMessage = data[3];
		$(divIndicator).html(strIndicator);
		$(divTxtHint).html(strTxtHint);
		$(divMessage).html(strMessage);
		// Add message div.
		$(divIndicator).parent().append("<div " +
			"id='" + tfName + "_msg'>" + 
			override + 
			"</div>");
	});
}

function focusHandlerRegistrationURL(theTextField, theCaptcha) {
	// Put up a new CAPTCHA.
	var elemsHref = document.getElementsByTagName("a");
	for (var i = 0; i < elemsHref.length; i++) {
		if (elemsHref[i].innerHTML == "Try another") {
			//alert(elemsHref[i].innerHTML);
			elemsHref[i].onclick();
		}
	}

	// Clear the CAPTCHA input text.
	var theCaptchaCode = document.getElementsByName(theCaptcha);
	theCaptchaCode[0].value = "";
}


