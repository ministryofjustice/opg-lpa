// ====================================================================================
// INITITALISE ALL MOJ MODULES
$(moj.init);

// ====================================================================================
// INITITALISE ALL GOVUK MODULES

// Where .block-label uses the data-target attribute
// to toggle hidden content
var showHideContent = new GOVUK.ShowHideContent();
showHideContent.init();

// ====================================================================================
// SIMPLE UTILITIES

// Remove the no-js class
$('body').removeClass('no-js');

var jsEnabled = function () {
  document.body.className = document.body.className + ' js-enabled';
};

var inline = function () {
  jsEnabled();
};

$(document).ready(inline);
