// Repeat Application code module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.RepeatApplication = {
    selector: '#lpa-type',

    init: function () {
      console.log('RepeatApplication');

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
        $('.js-case-number').animate({
          'height': 'show',
          'opacity': 1
        }, duration); // show
      }
      else {
        $('.js-case-number').animate({
          'height': 'hide',
          'opacity': 0
        }, duration); // hide
      }

    },
    render: function (e, params) {
      var $el = $(this.selector, $(params.wrap));

      this.displayCaseNumber(0);
      this.initialiseEvents();

    },
    onRepeatApplicationFormChangeHandler: function (evt) {
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
          'dialogMessage': 'I confirm that the Office of the Public Guardian has told me that I can apply to make a repeat application for £55 within 3 months.',
          'acceptButtonText': 'Confirm and continue',
          'cancelButtonText': 'Cancel',
          'acceptClass': 'js-dialog-accept',
          'cancelClass': 'js-dialog-cancel'
        });
        moj.Modules.Popup.open(html, {
          ident: 'dialog-confirmation'
        });

        $('.dialog-confirmation').on('click', 'a', function (evt) {
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
