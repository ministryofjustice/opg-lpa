/* global zxcvbn */
$(function () {
  'use strict';

	// dynamically add password-strength p tag
	var strength = $('#password-strength'),
		password = $('#register-password');

	if (strength.length === 0) {
		password.after(strength = $('<p id="password-strength" class="hint"/>'));
	}

	// bind event to password text box
	password.keyup(function () {
		var textValue = $(this).val(),
        result,
        strengths;

		if (textValue.length < 7) {
			strength.html('');
			return;
		}

    result = zxcvbn(textValue);

    strengths = [
			/*'<span style="color:red">very weak - upper case characters and symbols can strengthen your password</span>',*/
                        '<span style="color:#ff6666">weak</span>',
			'<span style="color:#aaaa00">average</span>',
			'<span style="color:#448844">strong</span>',
                        '<span style="color:#448844">strong</span>',
			'<span style="color:#00aa00">very strong</span>'
		];
		strength.html('Password strength: ' + strengths[result.score]);
	});
});