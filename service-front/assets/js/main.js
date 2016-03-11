// ====================================================================================
// INITITALISE ALL MOJ MODULES
$(moj.init);


// ====================================================================================
// VENDOR CONFIGURATION

// JQUERY UI DATEPICKER SETUP
// NOTE: Only on the 'date-check' page.

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