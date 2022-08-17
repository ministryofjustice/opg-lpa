// ====================================================================================
// INITITALISE ALL MOJ MODULES
;window.moj.init()

// ====================================================================================
// INITITALISE ALL GOVUK MODULES

// Where .block-label uses the data-target attribute
// to toggle hidden content
const showHideContent = new window.GOVUK.ShowHideContent()
showHideContent.init()

// ====================================================================================
// SIMPLE UTILITIES

// Remove the no-js class
document.body.classList.remove('no-js')
