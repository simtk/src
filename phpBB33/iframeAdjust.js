
	// Default scroll height;
	var theScrollHeight = 700;

	// Set up event listener for message posted from child iframe.
	var myEventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
	var myEventHandler = window[myEventMethod];
	var myMessageEvent = myEventMethod == "attachEvent" ? "onmessage" : "message";

	// Listen to message.
	myEventHandler(myMessageEvent, function(theEvent) {
		if (theEvent.data.event_id == "ScrollHeight") {
			theScrollHeight = theEvent.data.scroll_height;
		}
		// Set iframe width to project menu width.
		var viewportWidth = $('.project_menu').width();
		$('iframe').css("width", viewportWidth + 'px');

		// Get content height.
		var frameHeight = theScrollHeight;
		// Add a 50px vertical buffer, just in case, since we are not 
		// using a scrollbar in the iframe.
		frameHeight += 50;
		// Set iframe height with content height.
		$('iframe').css("height", frameHeight + 'px');
	}, false);

