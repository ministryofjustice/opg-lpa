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
// VENDOR CONFIGURATION

// JQUERY UI DATEPICKER SETUP
// NOTE: Only on the 'date-check' page and not on mobile
if (!moj.Helpers.isMobileWidth()) {

	$('.date-field input').datepicker(
		{
			dateFormat: 'dd/mm/yy',
			altFormat: 'dd/mm/yy',
			firstDay: 1,
			autoSize:true,
			changeMonth:true,
			changeYear:true
		}
	);
}




// Remove the no-js class
$('body').removeClass('no-js');
