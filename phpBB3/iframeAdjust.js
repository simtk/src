/**
 *
 * iframeAdjust.js
 * 
 * Adjust the iframe size.
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

$(window).ready(function() {
	$('iframe').load(function() {
		// Set iframe width to project menu width.
		var viewportWidth = $('.project_menu').width();
		this.style.width = viewportWidth + 'px';

		// Get content height.
		var frameHeight = this.contentWindow.document.body.scrollHeight;
		// Add a 50px vertical buffer, just in case, since we are not 
		// using a scrollbar in the iframe.
		frameHeight += 50;
		// Set iframe height with content height.
		this.style.height = frameHeight + 'px';
	});
});

