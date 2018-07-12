// ====================================================================================
// INITITALISE ALL MOJ MODULES
;$(moj.init);


// ====================================================================================
// INITITALISE ALL GOVUK MODULES

// Initiating the SelectionButtons GOVUK module
var $blockLabels = $(".block-label input[type='radio'], .block-label input[type='checkbox']");
new GOVUK.SelectionButtons($blockLabels);


// Where .block-label uses the data-target attribute
// to toggle hidden content
var showHideContent = new GOVUK.ShowHideContent();
showHideContent.init();


// ====================================================================================
// SIMPLE UTILITIES

// Remove the no-js class
$('body').removeClass('no-js');
