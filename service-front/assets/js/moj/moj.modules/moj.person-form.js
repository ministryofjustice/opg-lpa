// Person Form module for LPA
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
      if (window && window.console) {
      	window.console.log('count ' + i);
      }

      // Set variables
      var $form = $(el),
        $submitBtn = $('input[type="submit"]', $form),
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
            day = parseInt($dayObj.val(), 10);
            if (isNaN(day) || day < 1 || day > 31) {
              day = undefined;
            }
          }
          if ($monthObj.val() !== '') {
            month = parseInt($monthObj.val(), 10);
            if (isNaN(month) || month <= 0 || month > 12) {
              month = undefined;
            }
            else {
              month = month - 1;
            }
          }
          if ($yearObj.val() !== '') {
            year = parseInt($yearObj.val(), 10);
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
        tplAlert = lpa.templates['alert.withinForm'],
        tplInputCheckbox = lpa.templates['input.checkbox'];

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
            duplicateName = null,
            loop,
            item;

          if (!$target.hasClass('confirmation-validation')) {
            // If the input changed is not a confirmation tick box, then do the form checks...


            // Are we editing the name fields?
            if (($target.attr('name') === 'name-first') || ($target.attr('name') === 'name-last')) {

              // Check for duplicate names
              var actorNames = $form.data('actor-names');

              if ((typeof actorNames !== 'undefined') && actorNames.length) {
                for (loop = 0; loop < actorNames.length; loop++) {
                  item = actorNames[loop];

                  if ($firstName.val().toLocaleLowerCase().trim() === item.firstname.toLocaleLowerCase()) {
                    if ($lastName.val().toLocaleLowerCase().trim() === item.lastname.toLocaleLowerCase()) {
                      duplicateName = item;
                      break;
                    }
                  }
                }
              }

              // Cleanup
              $('.js-duplication-alert').remove();

              // Display alert if duplicate
              if (duplicateName !== null) {
                //  Construct the correct starting phrase for the warning
                var alertStart = 'The ' + duplicateName.type + '\'s name is also ';

                if ($.inArray(duplicateName.type, ['replacement attorney', 'person to notify']) > -1) {
                  alertStart = 'There is already a ' + duplicateName.type + ' called ';
                } else if (duplicateName.type == 'attorney') {
                  alertStart = 'There is already an ' + duplicateName.type + ' called ';
                }

                $('label[for="name-last"]', $form)
                  .parents('.form-group')
                  .after($(tplAlert({
                    'elementJSref': 'js-duplication-alert',
                    'alertType': 'important-small',
                    'alertMessage': '<p>' + alertStart + duplicateName.firstname + ' ' + duplicateName.lastname + '. You cannot use the same person in multiple roles. By saving this section, you are confirming that these are two different people with the same name.</p>'
                  })));

                // Focus on alert panel for accessibility
                $('.alert.panel').focus();
              }
            }


            // Are we editing the DOB?
            if ($target.parents('.dob-element').length) {

              // Cleanup
              $('.js-age-check').remove();

              dob = getDOB();
              if (dob !== null) {

                // Display alerts if under 18 or over 100 years old
                // Under 18 and earlier than today. A server side validation check is in place for dob greater than today.
                if (dob > minAge && dob < new Date()) {
                  $('.dob-element', $form)
                    .after($(tplAlert({
                      'elementJSref': 'js-age-check',
                      'alertType': 'important-small',
                      'alertMessage': 'This person is under 18. I understand they must be at least 18 <strong class="bold-small">on the date the donor signs the LPA</strong>, otherwise it will be rejected.'
                    })));
                }
                // Over 100
                else if (dob <= maxAge) {
                  $('.dob-element', $form)
                    .after($(tplAlert({
                      'elementJSref': 'js-age-check',
                      'alertType': 'important-small',
                      'alertMessage': 'By saving this section, you confirm that the person is more than 100 years old. If not, please change the date.'
                    })));
                }

                // Focus on alert panel for accessibility
                $('.alert.panel').focus();

              }

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
    }

  };

})();
