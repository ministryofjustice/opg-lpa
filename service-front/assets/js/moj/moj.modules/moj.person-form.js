// Person Form module for LPA
// Dependencies: moj, _, jQuery
(function () {
  'use strict';

  const moj = window.moj;
  const lpa = window.lpa;

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
      const wrap =
        params !== undefined && params.wrap !== undefined
          ? params.wrap
          : 'body';
      $(this.selector, wrap).each(this.formEvents);
    },

    formEvents: function (i, el) {
      // Set variables
      const $form = $(el);
      let dob;

      const getDOB = function () {
        let day;
        let month;
        let year;
        const $dayObj = $('#dob-date-day');
        const $monthObj = $('#dob-date-month');
        const $yearObj = $('#dob-date-year');
        let birthdate;

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
          } else {
            month = month - 1;
          }
        }
        if ($yearObj.val() !== '') {
          year = parseInt($yearObj.val(), 10);
          if (isNaN(year) || year <= 0) {
            year = undefined;
          }
        }

        birthdate = new Date(year, month, day);
        if (!isFinite(birthdate)) {
          birthdate = null;
        }

        return birthdate;
      };

      const tplAlert = lpa.templates['alert.withinForm'];

      // Listen for changes to form
      $form.on(
        'change.moj.Modules.PersonForm',
        'input, select',
        function (evt) {
          const $target = $(evt.target);
          const currentDate = new Date();
          const minAge = new Date(
            currentDate.getUTCFullYear() - 18,
            currentDate.getUTCMonth(),
            currentDate.getUTCDate(),
          );
          const maxAge = new Date(
            currentDate.getUTCFullYear() - 100,
            currentDate.getUTCMonth(),
            currentDate.getUTCDate(),
          );
          const $firstName = $('input[name="name-first"]', $form);
          const $lastName = $('input[name="name-last"]', $form);
          let duplicateName = null;
          let loop;
          let item;

          if (!$target.hasClass('confirmation-validation')) {
            // If the input changed is not a confirmation tick box, then do the form checks...
            const actorType = $form.data('actor-type');

            // Are we editing the name fields?
            if (
              $target.attr('name') === 'name-first' ||
              $target.attr('name') === 'name-last'
            ) {
              // Check for duplicate names
              const actorNames = $form.data('actor-names');

              if (typeof actorNames !== 'undefined' && actorNames.length) {
                for (loop = 0; loop < actorNames.length; loop++) {
                  item = actorNames[loop];

                  if (
                    $firstName.val().toLocaleLowerCase().trim() ===
                    item.firstname.toLocaleLowerCase()
                  ) {
                    if (
                      $lastName.val().toLocaleLowerCase().trim() ===
                      item.lastname.toLocaleLowerCase()
                    ) {
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
                let alertStart =
                  'The ' + duplicateName.type + "'s name is also ";

                if (
                  $.inArray(duplicateName.type, [
                    'replacement attorney',
                    'person to notify',
                  ]) > -1
                ) {
                  alertStart =
                    'There is also a ' + duplicateName.type + ' called ';
                } else if (duplicateName.type === 'attorney') {
                  alertStart =
                    'There is also an ' + duplicateName.type + ' called ';
                }

                //  Construct the middle part of the message
                let alertMiddle = 'The ' + duplicateName.type + ' cannot be ';

                //  If the user is attempting to create an attorney or
                // replacement attorney twice show a specific line
                if (
                  actorType === duplicateName.type &&
                  actorType === 'attorney'
                ) {
                  alertMiddle =
                    'A person cannot be named as an attorney twice on the same LPA';
                } else if (
                  actorType === duplicateName.type &&
                  actorType === 'replacement attorney'
                ) {
                  alertMiddle =
                    'A person cannot be named as a replacement attorney twice on the same LPA';
                } else if (
                  actorType === duplicateName.type &&
                  actorType === 'person to notify'
                ) {
                  alertMiddle =
                    'A person should not be named as a person to notify twice on the same LPA';
                } else {
                  //  Check the rest of the logic
                  if (
                    $.inArray(duplicateName.type, [
                      'replacement attorney',
                      'person to notify',
                    ]) > -1
                  ) {
                    alertMiddle = 'A ' + duplicateName.type + ' cannot be ';
                  } else if (duplicateName.type === 'attorney') {
                    alertMiddle = 'An ' + duplicateName.type + ' cannot be ';
                  }

                  if (
                    $.inArray(actorType, [
                      'replacement attorney',
                      'person to notify',
                    ]) > -1
                  ) {
                    alertMiddle += 'a ' + actorType;
                  } else if (actorType === 'attorney') {
                    alertMiddle += 'an ' + actorType;
                  } else {
                    alertMiddle += 'the ' + actorType;
                  }
                }

                $('label[for="name-last"]', $form)
                  .parents('.form-group')
                  .after(
                    $(
                      tplAlert({
                        elementJSref: 'js-duplication-alert',
                        alertType: 'important-small',
                        alertMessage:
                          '<p>' +
                          alertStart +
                          duplicateName.firstname +
                          ' ' +
                          duplicateName.lastname +
                          '. ' +
                          alertMiddle +
                          '. By saving this section, you are confirming that these are ' +
                          '2 different people with the same name.</p>',
                      }),
                    ),
                  );

                // Focus on alert panel for accessibility
                $('.alert.panel').trigger('focus');
              }
            }

            // Are we editing the DOB?
            if ($target.parents('.dob-element').length) {
              // Cleanup
              $('.js-age-check').remove();

              let ageWarningAlertStart;
              let ageWarningAlertMiddle;

              dob = getDOB();
              if (dob !== null) {
                // Display alerts if under 18 or over 100 years old
                // Under 18 and earlier than today.
                // A server side validation check is in place for dob greater than today.
                if (dob > minAge && dob < new Date()) {
                  //  Build up the under 18 warning message
                  ageWarningAlertStart = 'The ' + actorType + ' is under 18.';
                  ageWarningAlertMiddle = 'the donor';

                  if (
                    $.inArray(actorType, [
                      'attorney',
                      'replacement attorney',
                      'person to notify',
                    ]) > -1
                  ) {
                    ageWarningAlertStart =
                      'This ' + actorType + ' is under 18.';
                  } else if (actorType === 'donor') {
                    ageWarningAlertMiddle = 'they';
                  }

                  $('.dob-element', $form).after(
                    $(
                      tplAlert({
                        elementJSref: 'js-age-check',
                        alertType: 'important-small',
                        alertMessage:
                          ageWarningAlertStart +
                          ' I understand that the ' +
                          actorType +
                          ' must be at least 18 <strong class="bold-small">on the date ' +
                          ageWarningAlertMiddle +
                          ' signs the LPA</strong>, ' +
                          'otherwise the LPA will be rejected.',
                      }),
                    ),
                  );
                } else if (dob <= maxAge) {
                  if (
                    $.inArray(actorType, ['attorney', 'replacement attorney']) >
                    -1
                  ) {
                    ageWarningAlertMiddle = 'this ' + actorType;
                  } else if (actorType === 'donor') {
                    ageWarningAlertMiddle = 'the donor';
                  }

                  // Over 100
                  $('.dob-element', $form).after(
                    $(
                      tplAlert({
                        elementJSref: 'js-age-check',
                        alertType: 'important-small',
                        alertMessage:
                          'By saving this section, you confirm that ' +
                          ageWarningAlertMiddle +
                          ' is more than 100 years old. If not, please change the date.',
                      }),
                    ),
                  );
                }

                // Focus on alert panel for accessibility
                $('.alert.panel').trigger('focus');
              }
            }
          }
        },
      );

      // Relationship: other toggle
      $form.on(
        'change.moj.Modules.PersonForm',
        '[name="relationshipToDonor"]',
        function () {
          const other = $('#relationshipToDonorOther').closest('.group');
          if ($(this).val() === 'Other') {
            other.show().find('input').trigger('focus');
          } else {
            other.hide();
          }
        },
      );

      // toggle initial change on donor relationship
      const relationshipElement = $('[name="relationshipToDonor"]', $form);
      relationshipElement.trigger('change');
      relationshipElement.closest('form').data('dirty', false);
    },
  };
})();
