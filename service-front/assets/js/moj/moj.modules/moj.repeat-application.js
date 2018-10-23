// Repeat Application code module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.RepeatApplication = {
    selector: '#lpa-type',

    init: function () {
      _.bindAll(this, 'render');
      this.cacheEls();
      this.bindEvents();
      this.render(null, {wrap: 'body'});
    },

    cacheEls: function () {
      this.$selector = $(this.selector);
    },

    bindEvents: function () {
      moj.Events.on('RepeatApplication.render', this.render);
    },
    displayCaseNumber: function (duration) {
      if ($('#is-repeat-application:checked').length) {
          $('.js-case-number').removeClass('js-hidden');
      }
      else {
          $('.js-case-number').addClass('js-hidden');
      }

    },
    render: function () {
      this.displayCaseNumber(0);
      this.initialiseEvents();

    },
    onRepeatApplicationFormChangeHandler: function () {
      this.displayCaseNumber(500);
    },
    onRepeatApplicationFormClickHandler: function (evt) {
      var tplDialogConfirm = lpa.templates['dialog.confirmRepeatApplication'],
        html,
        formToSubmit,
        formSubmitted = false;

      if ($('#is-repeat-application:checked').length) {

        formToSubmit = evt.target.form;
        evt.preventDefault();
        evt.stopImmediatePropagation();

        html = tplDialogConfirm({
          'dialogTitle': 'Confirm',
          'dialogMessage': 'I confirm that OPG has said a repeat application can be made within 3 months for half the normal application fee.',
          'acceptButtonText': 'Confirm and continue',
          'cancelButtonText': 'Cancel',
          'acceptClass': 'js-dialog-accept',
          'cancelClass': 'js-dialog-cancel'
        });
        moj.Modules.Popup.open(html, {
          ident: 'dialog'
        });

        $('.dialog').on('click', 'a', function (evt) {
          var $target = $(evt.target);

          if (!formSubmitted) {

            if ($target.hasClass('js-dialog-accept')) {
              $target.addClass('disabled');
              formToSubmit.submit();
              formSubmitted = true;
            }
            else {
              moj.Modules.Popup.close();
            }

          }

        });

      }
    },
    initialiseEvents: function () {
      var self = this;

      $('form#form-repeat-application').on('change', 'input[type="radio"]', function (evt) {
        self.onRepeatApplicationFormChangeHandler(evt);
      });

      $('form#form-repeat-application').on('click', 'input[type="submit"]', function (evt) {
        self.onRepeatApplicationFormClickHandler(evt);
      });

    }
  };

})();
