// Reusables module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  var self;

  moj.Modules.PersonForm = {
    selector: '.js-PersonForm',

    init: function () {
      self = this;

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

          var $target = $(evt.target),
            currentDate = new Date(),
            minAge = new Date(currentDate.getUTCFullYear() - 18, currentDate.getUTCMonth(), currentDate.getUTCDate()),
            maxAge = new Date(currentDate.getUTCFullYear() - 100, currentDate.getUTCMonth(), currentDate.getUTCDate()),
            $dobElement = $('.dob-element'),
            $dobGroup,
            actionGroup = $('.group.action'),
            $firstName = $('input[name="name-first"]', $form),
            $lastName = $('input[name="name-last"]', $form),
            $nameGroups = $('input[name^="name"]:not(input[name="name-title"])', $form).parents('.group'),
            duplicateName = null,
            loop,
            item;

          if (!$target.hasClass('confirmation-validation')) {
            // If the input changed is not a confirmation tick box, then do the form checks...

            // Are we editing the name fields?
            if (($target.attr('name') === 'name-first') || ($target.attr('name') === 'name-last')) {

              // Check for duplicate names
              if ((typeof actors !== 'undefined') && actors.names && actors.names.length) {
                for (loop = 0; loop < actors.names.length; loop++) {
                  item = actors.names[loop];
                  if ($firstName.val().toLocaleLowerCase() === item.firstname.toLocaleLowerCase()) {
                    if ($lastName.val().toLocaleLowerCase() === item.lastname.toLocaleLowerCase()) {
                      duplicateName = item;
                      break;
                    }
                  }
                }
              }

              $('.js-confirm-name').remove();
              $nameGroups.removeClass('validation').find('.form-element-errors').remove();

              if (duplicateName !== null) {
                $nameGroups.addClass('validation');
                $nameGroups.append(tplFormElementErrors({'validationMessage': 'Is this a duplicate name?' }));

                actionGroup.before($(tplInputCheckbox({
                  'elementJSref': 'js-confirm-name',
                  'elementName': 'confirmName',
                  'elementLabel': 'The ' + duplicateName.type + '\'s name is also ' + duplicateName.firstname + ' ' + duplicateName.lastname + '. You can\'t use the same person in multiple roles. Click here to confirm that these are 2 different people with the same name.'
                })).addClass('validation'));
              }

            }


            // Are we editing the DOB?
            if ($target.parents('.dob-element').length) {

              $dobGroup = $dobElement.parents('.group');
              $dobGroup.removeClass('validation');
              $dobGroup.find('.form-element-errors').remove();
              $('.js-age-check').remove();

              dob = getDOB();
              if (dob !== null) {

                if (dob > minAge) {
                  $dobGroup.addClass('validation');
                  $dobGroup.append(tplFormElementErrors({'validationMessage': 'Please confirm age' }));
                  actionGroup.before($(tplInputCheckbox({
                    'elementJSref': 'js-age-check',
                    'elementName': 'ageCheck',
                    'elementLabel': 'This attorney is currently under 18. I understand they must be at least 18 <strong>when the donor sign the LPA,</strong> otherwise it may be rejected.'
                  })).addClass('validation'));

                }
                else if (dob <= maxAge) {
                  $dobGroup.addClass('validation');
                  $dobGroup.append(tplFormElementErrors({'validationMessage': 'Please confirm age' }));
                  actionGroup.before($(tplInputCheckbox({
                    'elementJSref': 'js-age-check',
                    'elementName': 'ageCheck',
                    'elementLabel': 'Please confirm that they are over 100 years old.'
                  })).addClass('validation'));

                }

              }

            }


            $('.validation-summary').remove();
            if ($form.find('.group.validation').length > 0) {
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
        }
      )
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