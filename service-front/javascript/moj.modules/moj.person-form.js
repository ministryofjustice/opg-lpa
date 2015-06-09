// Reusables module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.PersonForm = {
    selector: '.js-PersonForm',

    init: function () {
      _.bindAll(this, 'render', 'formEvents');
      this.cacheEls();
      this.bindEvents();
    },

    cacheEls: function () {
      this.$els = $(this.selector);
    },

    bindEvents: function () {
      // default moj render event
      moj.Events.on('render', this.render);
      // custom render event
      moj.Events.on('TitleSwitch.render', this.render);
    },

    render: function (e, params) {
      var wrap = params !== undefined && params.wrap !== undefined ? params.wrap : 'body';
      $(this.selector, wrap).each(this.formEvents);
    },

    formEvents: function (i, el) {
      var $form = $(el),
        $submitBtn = $('input[type="submit"]', $form),
        donorCannotSign = $('#donor_cannot_sign', $form).is(':checked'),
        $allFields = $('input[required], label.required + input, label.required ~ select', $form),
        $addressFields = $('input[name^="address"]', $form),
        allPopulated,
        countAddr,
        dob,
        getDOB = function () {
          var day,
            month,
            year,
            $dayObj = $('#dob-date-day'),
            $monthObj = $('#dob-date-month'),
            $yearObj = $('#dob-date-year'),
            returnDate;

          if ($dayObj.val() !== '') {
            day = parseInt($dayObj.val());
            if (isNaN(day) || (day <= 1)) {
              day = undefined;
            }
          }
          if ($monthObj.val() !== '') {
            month = parseInt($monthObj.val());
            if (isNaN(month) || (month <= 0)) {
              month = undefined;
            }
            else {
              month = month - 1;
            }
          }
          if ($yearObj.val() !== '') {
            year = parseInt($yearObj.val());
            if (isNaN(year) || (year <= 0)) {
              year = undefined;
            }
          }

          returnDate = new Date(year, month, day);
          if (!isFinite(returnDate)) {
            returnDate = null;
          }

          return returnDate;

        },
        tplFormElementErrors = lpa.templates['errors.formElement'],
        tplErrorsFormSummary = lpa.templates['errors.formSummary'],
        tplInputCheckbox = lpa.templates['input.checkbox'];

      // disable submit if empty form
      $submitBtn.attr('disabled', $('#address-addr1', $form).val() === '');

      // Listen for changes to form
      $form
        .on('change.moj.Modules.PersonForm', 'input, select', function (evt) {

          var currentDate = new Date(),
            minAge = new Date(currentDate.getUTCFullYear() - 18, currentDate.getUTCMonth(), currentDate.getUTCDate()),
            maxAge = new Date(currentDate.getUTCFullYear() - 100, currentDate.getUTCMonth(), currentDate.getUTCDate()),
            $dobElement = $(evt.target).parents('.dob-element'),
            $dobGroup,
            actionGroup = $('.group.action');


          // Verify the DOB
          if ($dobElement.length > 0) {

            dob = getDOB();
            $dobGroup = $dobElement.parents('.group');
            $dobGroup.removeClass('validation');
            $dobGroup.find('.form-element-errors').remove();
            $('.js-age-check').remove();
            $('.validation-summary').remove();

            if (dob !== null) {

              if (dob > minAge) {
                console.log('too young');
                $dobGroup.addClass('validation');
                $dobGroup.append(tplFormElementErrors({'validationMessage': 'Please confirm age' }));
                actionGroup.before($(tplInputCheckbox({
                  'elementJSref': 'js-age-check',
                  'elementName': 'ageCheck',
                  'elementLabel': 'This attorney is currently under 18. I understand they must be at least 18 <strong>when the donor sign the LPA,</strong> otherwise it may be rejected.'
                })).addClass('validation'));

                $form.prepend(tplErrorsFormSummary());

              }
              else if (dob <= maxAge) {
                $dobGroup.addClass('validation');
                $dobGroup.append(tplFormElementErrors({'validationMessage': 'Please confirm age' }));
                actionGroup.before($(tplInputCheckbox({
                  'elementJSref': 'js-age-check',
                  'elementName': 'ageCheck',
                  'elementLabel': 'Please confirm that they are over 100 years old.'
                })).addClass('validation'));

                $form.prepend(tplErrorsFormSummary());

              }

            }
            else {
              $dobGroup.addClass('validation');
              $dobGroup.append(tplFormElementErrors({'validationMessage': 'The age appears to be invalid'}));

              $form.prepend(tplErrorsFormSummary());
            }

          }


          $allFields = $('input[required], label.required + input, label.required ~ select', $form);

          allPopulated = true;

          // Test required fields are populated
          $allFields.each(function () {
            if (allPopulated) {

              var $field = $(this);

              if ($.trim($field.val()) === '') {
                allPopulated = false;
              }
              if ($field.prop('type') === 'checkbox') {
                allPopulated = $field.prop('checked');
              }

            }
          });

          // Count populated address fields
          countAddr = $addressFields.filter(function () {
            return this.value.length !== 0;
          }).length;

          // Test address fields - business logic states 2 address fields as min
          if (countAddr < 2) {
            allPopulated = false;
          }


          $submitBtn.attr('disabled', !allPopulated);
        })
        // Relationship: other toggle
        .on('change.moj.Modules.PersonForm', '[name="relationshipToDonor"]', function () {
          var other = $('#relationshipToDonorOther').closest('.group');
          if ($(this).val() === 'Other') {
            other.show().find('input').focus();
          } else {
            other.hide();
          }
        });

      // toggle initial change on donor relationship
      $('[name="relationshipToDonor"]', $form).change().closest('form').data('dirty', false);

      // donor toggle
      if (donorCannotSign) {
        $('#donorsignprompt', $form).show();
      } else {
        $('#donorsignprompt', $form).hide();
      }

      // Initialise details tag within the lightbox
      $('details', $form).details();

      // show free text field on certificate provider form when a statement type was chosen
      $('input:radio[name="certificateProviderStatementType"]').each(function (idx) {
        if ($(this).attr('checked') !== undefined) {
          if (idx === 0) {
            $(':input[name="certificateProviderKnowledgeOfDonor"]').closest('.form-element-textarea-cp-statement').show();
          } else {
            $(':input[name="certificateProviderProfessionalSkills"]').closest('.form-element-textarea-cp-statement').show();
          }
        }
      });
    }


  };

})();