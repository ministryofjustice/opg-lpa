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
				var routePattern = RegExp('^/lpa/\\d+/(donor|primary-attorney|replacement-attorney|certificate-provider|people-to-notify)/(add|add-trust)$');
				var routeMatch = routePattern.exec(formAction);
				if(routeMatch) {
					var role = routeMatch[1];
					if(routeMatch[2] == 'add-trust') {
						role += '-trust';
					}
					
					$.ajax( {
						url: location.pathname + '?load=' + role,
						dataType:'json',
						success: function(data) {
							$('.js-PostcodeLookup__postal-add.hidden').removeClass('hidden');
							for(var index in data) {
								$('[name=' + index+']').val(data[index]);
								if(index == 'name-title') {
									$('[name=name-title__select]').val(data[index]);
								}
							}
							$('[name=name-title__select]').trigger('change');
						}
					});
				}
			});
		}
	});
}






















































































