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

var dashboard = function () {
  if ($('span[data-page=dashboard]').length === 0) {
    return;
  }
};

var jsEnabled = function () {
  document.body.className = document.body.className + ' js-enabled';
};

var inline = function () {
  dashboard();
  jsEnabled();
};

$(document).ready(inline);
