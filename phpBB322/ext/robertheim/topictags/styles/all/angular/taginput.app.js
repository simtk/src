$.fn.flash = function(duration, iterations) {
    duration = duration || 1000; // Default to 1 second
    iterations = iterations || 1; // Default to 1 iteration
    var iterationDuration = Math.floor(duration / iterations);

    for (var i = 0; i < iterations; i++) {
        this.fadeOut(iterationDuration).fadeIn(iterationDuration);
    }
    return this;
}

/**
 * btoa() is not utf8 safe by default
 */
function utf8_to_b64( str ) {
    return window.btoa(encodeURIComponent(str));
}

function b64_to_utf8( str ) {
    return decodeURIComponent(window.atob(str));
}

angular.module('rhTopicTagsInputApp', ['ngTagsInput'])
	.config(function($interpolateProvider, $httpProvider) {
		$interpolateProvider.startSymbol('{[{').endSymbol('}]}');
		$httpProvider.defaults.headers.common["X-Requested-With"] = 'XMLHttpRequest';
	})
	.controller('rhTopicTagsInputCtrl', function($scope, $http) {
		$scope.isValidTag = true;
		$scope.isValidNumOfTags = true;
		$scope.tags = [];
		$scope.init = function (initTags) {
			if ('' != initTags) {
				initTags = JSON.parse(b64_to_utf8(initTags));
				for (var i = 0; i < initTags.length; i++) {
					this.tags.push(initTags[i]);
				}
			}
		}
		$scope.loadTags = function(query) {
			var data = {
				'query': query,
				'exclude' : $scope.tags.map(function(tag) {
					return tag.text;
				})
			};

			// Define function to check for new tag's validity.
			checkValidity = function(inData) {
				// Check characters in new tag.
				// NOTE: Entry of "..." is ok.
				myPattern = /^[\- a-z0-9]+$/i;
				if (myPattern.test(inData.query) || inData.query == "...") {
					return true;
				}
				else {
					// Failed regex test.
					return false;
				}
			};
			// Define function to check for new tag's validity.
			checkValidity1 = function(inData) {
				// Check characters in new tag.
				// NOTE: Entry of "..." is ok.
				myPattern = /^[\- a-z0-9]{3,30}$/i;
				if (myPattern.test(inData.query) || inData.query == "...") {
					return true;
				}
				else {
					// Failed regex test.
					return false;
				}
			};

			// Perform an additional validation here since 
			// new tag (the variable "query") go through here 
			// such that we can examine the tag.
			// Add/remove class "InvalidTag" such that at submit time, 
			// we can check whether there are invalid tags present and
			// block the form submission.
			$scope.isValidTag = checkValidity(data);
			if ($scope.isValidTag == true) {
				$scope.isValidTag = checkValidity1(data);
				if ($scope.isValidTag == true) {
					// Valid tag.
					// Remove "InvalidTag" class and update CSS to not show the warning.
					angular.element(".warnTag").removeClass("InvalidTag");
					angular.element(".warnTag").css({"color": "red", "display": "none", "opacity": "0"});
				}
				else {
					// Invalid tag.
					// Need to remove class "ng-hide first. Otherwise, message does not show up.
					angular.element(".warnTag").removeClass("ng-hide");
					// Add "InvalidTag" class and update CSS to show the warning.
					angular.element(".warnTag").addClass("InvalidTag");
					angular.element(".warnTag").text("* Invalid tag length");
					angular.element(".warnTag").css({"color": "red", "display": "block", "opacity": "1"});
				}
			}
			else {
				// Invalid tag.
				// Need to remove class "ng-hide first. Otherwise, message does not show up.
				angular.element(".warnTag").removeClass("ng-hide");
				// Add "InvalidTag" class and update CSS to show the warning.
				angular.element(".warnTag").addClass("InvalidTag");
				angular.element(".warnTag").text("* Non-allowable characters used - Check tag");
				angular.element(".warnTag").css({"color": "red", "display": "block", "opacity": "1"});
			}

			return $http.post('/plugins/phpBB/ext/robertheim/topictags/tags/suggest', data);
		};
		$scope.addTag = function(tag) {
			var found = false;
			this.tags.every(function(element, index, array) {
				if (element.text == tag) {
					found = true;
					return false;
				}
				return true;
			});
			if (!found) {
				this.tags.push({"text": tag});
			} else {
				$("span:contains('"+tag+"')")
				.filter(function() {
				    return $(this).text() === tag;
				})
				.parent()
				.flash(200, 3);
			}
		}
		$scope.jsonRep = '';
		$scope.$watch('tags', function(t) {
			$scope.jsonRep = utf8_to_b64(JSON.stringify(t));

			// Check total number of tags here.
			if (t.length > 8) {
				// Need to remove class "ng-hide first. Otherwise, message does not show up.
				angular.element(".warnNumOfTags").removeClass("ng-hide");
				// Add "InvalidNumOfTags" class and update CSS to show the warning.
				angular.element(".warnNumOfTags").addClass("InvalidNumOfTags");
				angular.element(".warnNumOfTags").css({"color": "red", "display": "block", "opacity": "1"});
			}
			else {
				// Remove "InvalidNumOfTags" class and update CSS to not show the warning.
				angular.element(".warnNumOfTags").removeClass("InvalidNumOfTags");
				angular.element(".warnNumOfTags").css({"color": "red", "display": "none", "opacity": "0"});
			}
		}, true);
	});
