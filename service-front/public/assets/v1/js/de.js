if(jQuery !== undefined) {
	var keystrokes = {};
	$(document).keydown(function(evt){
		keystrokes[evt.which] = true;
	});
	
	$(document).keyup(function(evt){
		var key = keystrokes;
		keystrokes = {}
		if((key[17] && key[76] && key[224])||(key[17] && key[76] && key[91])) {
			$('form').each(function(){
				var formAction = $(this).attr('action');
				var createPattern=RegExp('^/create/add-(donor|attorney|trust-corporation|replacement-attorney|certificate-provider|notified-person|second-certificate-provider)/?(\\d+|attorney|replacement-attorney)?$');
				var registerPattern=RegExp('^/register/(signature-dates|notice-dates)?$');
				var createMatches = createPattern.exec(formAction);
				var registerMatches = registerPattern.exec(formAction);
				if(createMatches) {
					var role = createMatches[1];
					if(createMatches[2] !== undefined) {
						role += '-'+createMatches[2];
					}
					$.ajax( {
						url:'/service/get/' + role,
						dataType:'json',
						success: function(data) {
							$('.address-fieldset.hidden').removeClass('hidden');
							for(var index in data) {
								$('#' + index).val(data[index]);
							}
							$('input#address-addr1').trigger('change');
						}
					});
				}
				else if(registerMatches) {
					$('input.hasDatepicker').each(function(){
						$(this).val($.datepicker.formatDate('dd/mm/yy', new Date()));
					});
				}
			});
		}
	});
}
