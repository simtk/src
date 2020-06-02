/**
 *
 * uploadHandler.js
 * 
 * Javascript to handle file upload.
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
 
/*jslint unparam: true, regexp: true */
/*global window, $ */
$(function () {
	'use strict';

	var hasError = false;

	$('#fileupload').fileupload({
		url: 'uploadHandler.php',
		dataType: 'json',
		autoUpload: false,
		acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
		maxFileSize: 2097152,

		// Enable image resizing, except for Android and Opera,
		// which actually support image resizing, but fail to
		// send Blob objects via XHR requests:
		disableImageResize: /Android(?!.*Chrome)|Opera/.test(window.navigator.userAgent),
		previewMaxWidth: 75,
		previewMaxHeight: 75,
		previewCrop: true
	}).on('fileuploadadd', function (e, data) {
		// Reset error message first.
		$("#fileuploadErrMsg").html("");

		var cnt = 0;
		$.each(data.files, function (index, file) {

			if (file.size > 2097152) {
				// File upload is too large.
				$("#fileuploadErrMsg").html("<img src='/account/form_error.gif'><span>File size exceeded: " + file.size + "</span>");
				$("#fileDataDiv").html("");

				// Set error status.
				hasError = true;
				return;
			}

			// Validate file name.
			if (!file.name.match(/^\s*[a-z-._\d,\s]+\s*$/i)) {
				// Invalid file name.
				$("#fileuploadErrMsg").html("<img src='/account/form_error.gif'><span>Invalid file name: " + file.name + "</span>");
				$("#fileDataDiv").html("");

				// Set error status.
				hasError = true;
				return;
			}

			if (cnt > 1) {
				// Only upload the first file if there are multiple files selected.
				return false;
			}

			if (file != null && file.name != null && file.name != "") {
				// A file has been selected.
				// Save file name and type in a div.
				$("#fileDataDiv").html("");
				$("#fileDataDiv").append('<input type="hidden" name="userpicfilename" id="userpicfilename" value="' + 
					file.name + '" />');
				$("#fileDataDiv").append('<input type="hidden" name="userpicfiletype" id="userpicfiletype" value="' + 
					file.type + '" />');
			}

			cnt++;
		});

		if (!hasError) {
			// No error.
			// Clear logo area.
			$('.picture_wrapper').html("<div></div>");
			// Picture preview is put inside the div in data.context ("picture_wrapper").
			data.context = $('.picture_wrapper');
		}
	}).on('fileuploadprocessalways', function (e, data) {

		if (hasError) {
			// File upload has error. Do not continue.
			$(".picture_wrapper>a").remove();
			$(".picture_wrapper>div").remove();
			$(".picture_wrapper>img").remove();
			$(".picture_wrapper").append("<img id='userpicpreview' src='/userpics/user_profile_thumb.jpg'>");

			// Reset error status.
			hasError = false;
			return;
		}

		var index = data.index, file = data.files[index], node = $(data.context.children()[index]);

		// Only show preview for first selected file if there are multiple files selected.
		var theFirstFileName = $("#userpicfilename").val();
		if (theFirstFileName != null && 
			theFirstFileName != "" && 
			file.name != theFirstFileName) {
			return;
		}
		if (file.preview) {
			// Show preview of image.
			node.prepend('<br>').prepend(file.preview);
		}
		if (file.error) {
			// File upload has error. Reset to default image.
			$(".picture_wrapper>a").remove();
			$(".picture_wrapper>div").remove();
			$(".picture_wrapper>img").remove();
			$(".picture_wrapper").append("<img id='userpicpreview' src='/userpics/user_profile_thumb.jpg'>");
			$("#fileuploadErrMsg").html("<img src='/account/form_error.gif'><span>File upload failed</span>");
			$("#fileDataDiv").html("");

			return;
		}

		if (index + 1 === data.files.length) {
			data.context.find('button').text('Upload').prop('disabled', !!data.files.error);
		}

		// Upload the file now.
		data.submit();

	}).on('fileuploaddone', function (e, data) {
		$.each(data.result.files, function (index, file) {
			if (file.url) {
				var link = $('<a>').attr('target', '_blank').prop('href', file.url);
				$(data.context.children()[index]).wrap(link);
			}
			if (file.error) {
				// Set error status.
				hasError = true;
			}
		});

		if (hasError) {
			// File upload has error. Reset to default image.
			$(".picture_wrapper>a").remove();
			$(".picture_wrapper>div").remove();
			$(".picture_wrapper>img").remove();
			$(".picture_wrapper").append("<img id='userpicpreview' src='/userpics/user_profile_thumb.jpg'>");
			$("#fileuploadErrMsg").html("<img src='/account/form_error.gif'><span>File upload failed</span>");
			$("#fileDataDiv").html("");

			// Reset error status.
			hasError = false;
		}
	}).on('fileuploadfail', function (e, data) {
		// File upload has error. Reset to default image.
		$(".picture_wrapper>a").remove();
		$(".picture_wrapper>div").remove();
		$(".picture_wrapper>img").remove();
		$(".picture_wrapper").append("<img id='userpicpreview' src='/userpics/user_profile_thumb.jpg'>");
		$("#fileuploadErrMsg").html("<img src='/account/form_error.gif'><span>File upload failed</span>");
		$("#fileDataDiv").html("");

		// Reset error status.
		hasError = false;
	}).prop('disabled', !$.support.fileInput).parent().addClass($.support.fileInput ? undefined : 'disabled');
});

