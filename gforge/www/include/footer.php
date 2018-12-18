<?php

/**
 *
 * footer.php
 * 
 * File to handle footer.
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
 
// Footer for local machine
?>

</div></div></div></div>

<div class="the_footer"><div class="cont_footer">
	<div class="footer_row">
		<div class="footer_information"><p>SimTK is maintained through Grant R01GM124443 01A1 from the National Institutes of Health (NIH). It was initially developed as part of the Simbios project funded by the NIH as part of the NIH Roadmap for Medical Research, Grant U54 GM072970.</p>
		</div>
		<div class="footer-right">
			<a href="/pledge.php">Our Pledge and Your Responsibility</a><br/><br/>
			<a class="feedback_href" href="#">Feedback</a> &emsp; 
			<a href="/whatIsSimtk.php">About</a> &emsp; 
			<a href="/account/register.php">Join</a>
		</div>
	</div>
	<div style="font-size:12px;">Version 2.0.29. Website design by <a href="http://www.viewfarm.com/">Viewfarm</a>. Icons created by SimTK team using art by <a href="http://graphberry.com" title="GraphBerry">GraphBerry</a> from <a href="http://www.flaticon.com" title="Flaticon">www.flaticon.com</a> under a CC BY 3.0 license. Forked from <a href="http://fusionforge.org">FusionForge</a> 5.3.2.
	</div>
</div></div>

<script>
	setTimeout(
		function() {
			$(document).ready(function() {
				$(".search_select").customSelect();
				$(".simtk_select").customSelect({customClass:"customSelectForms"});
			});
		},
		20
	);
	$(document).ready(function() {
		$(".recYes").click(function() {
			rec_group_id = $(this).parent().parent().attr("id");
			$("#" + rec_group_id).text("Thanks!");
		});
	});
	$(document).ready(function() {
		$(".recNo").click(function() {
			rec_group_id = $(this).parent().parent().attr("id");
			$("#" + rec_group_id).text("Thanks!");
		});
	});
	$(document).ready(function() {
		$(".feedback_href, .feedback_button").click(function(event) {
			event.preventDefault();
			// Get group_id if hidden DIV class "divGroupId" is present.
			theGroupId = -1;
			$(".divGroupId").each(function() {
				theGroupId = $(this).text();
			});
			if (theGroupId != -1) {
				// Has group_id.
				location.href="/feedback.php?group_id=" + theGroupId;
			}
			else {
				// No group_id.
				location.href="/feedback.php";
			}
		});
	});
</script>

<div class="check_image" style="display: none"></div>

<div class="palette" style="display: none">
                <div class="color light_yellow"></div> <div class="text">#FDF8E1 @light_yellow</div>
                <div style="clear: both;"></div>
                <div class="color red"></div> <div class="text">#F75236 @red</div>
                <div style="clear: both;"></div>
                <div class="color orange"></div> <div class="text">#F5B563 @orange</div>
                <div style="clear: both;"></div>
                <div class="color light_blue"></div> <div class="text">#81A5D4 @light_blue</div>
                <div style="clear: both;"></div>
                <div class="color dark_blue"></div> <div class="text">#5E96E1 @dark_blue</div>
                <div style="clear: both;"></div>
                <div class="color dark_grey"></div> <div class="text">#505050 @dark_grey</div>
                <div style="clear: both;"></div>
                <div class="color black"></div> <div class="text">#000000 @black</div>
                <div style="clear: both;"></div>
                <div class="color light_grey"></div> <div class="text">#A7A7A7 @light_grey</div>
                <div style="clear: both;"></div>
</div>

<div class="feedback_button"><div class="text">Feedback</div></div>

</body>
</html>
