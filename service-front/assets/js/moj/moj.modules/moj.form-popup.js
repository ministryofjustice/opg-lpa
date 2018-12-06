// Form Popup module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  // Define the class
  var FormPopup = function (options) {
    this.settings = $.extend({}, this.defaults, options);
  };

  FormPopup.prototype = {
    defaults: {
      selector: '.js-form-popup',
      overlayIdent: 'form-popup',
      overlaySource: '#content'
    },

    init: function () {
      // bind 'this' as this in following methods
      _.bindAll(this, 'btnClick', 'submitForm');
      this.cacheEls();
      this.bindEvents();
    },

    cacheEls: function () {
      this.formContent = [];
      this.originalSource = false;
      this.source = false;
    },

    bindEvents: function () {
      $('body')
        // form open
        .on('click.moj.Modules.FormPopup', this.settings.selector, this.btnClick)
        // submit form
        .on('submit.moj.Modules.FormPopup', '#popup.form-popup form', this.submitForm);
        moj.Events.on('FormPopup.renderSelectionButtons', this.renderSelectionButtons);
        moj.Events.on('FormPopup.checkReusedDetails', this.checkReusedDetails);
    },

    renderSelectionButtons: function() {
        //switch checkboxes on (usually only happens on page load so need to manually do this step when opening lightbox)
        // Use GOV.UK selection-buttons.js to set selected
        // and focused states for block labels
        var $blockLabels = $(".block-label input[type='radio'], .block-label input[type='checkbox']");
        new GOVUK.SelectionButtons($blockLabels); // eslint-disable-line
    },

    btnClick: function (e) {
      var source = $(e.target),
        href = source.attr('href');

      // set original source to be the original link clicked form the body to be able to return to it when the popup is closed
      // fixes when links inside a popup load another form. User should be focused back to original content button when closing
      if ($('#popup').length === 0) {
        this.originalSource = source;
      }
      // always set this source to be the clicked link
      this.source = source;

      // If this link is disabled then stop here
      if (!source.hasClass('disabled')) {
        // set loading spinner
        source.spinner();
        // show form
        this.loadContent(href);
      }

      return false;
    },

    loadContent: function (url) {
      var self = this;

      $.get(url, function (html) {
        if (html.toLowerCase().indexOf('sign in') !== -1) {
          // if no longer signed in, redirect
          window.location.reload();
        } else {
          // render form and check the reused details content
          self.renderForm(html);

          if (url.indexOf('reuse-details') !== -1) {
            self.checkReusedDetails();
          }
        }
      });
    },

    checkReusedDetails: function () {
      // Align to top after loading in the content to avoid having the form starting half
      // way down (a scenario that happens when you've scrolled far down on Reuse details page)
      moj.Helpers.scrollTo('#popup-content');
      // If the user is reusing details then trigger some actions manually to give warning messages a chance to display
      $('#dob-date-day').trigger('change');
      $('input[name="name-first"]').trigger('change');
    },

    renderForm: function (html) {
      this.source.spinner('off');

      moj.Modules.Popup.open(html, {
        ident: this.settings.overlayIdent,
        source: this.originalSource,
        beforeOpen: function () {
          // trigger title replacement event
          moj.Events.trigger('TitleSwitch.render', {wrap: '#popup'});
          // trigger postcode lookup event
          moj.Events.trigger('PostcodeLookup.render', {wrap: '#popup'});
          // trigger person form events
          moj.Events.trigger('PersonForm.render', {wrap: '#popup'});
          // trigger polyfill form events
          moj.Events.trigger('Polyfill.fill', {wrap: '#popup'});
        }
      });

      this.renderSelectionButtons();
    },

    submitForm: function (e) {
      var $form = $(e.target),
        url = $form.attr('action'),
        method = 'post';

      $form.find('input[type="submit"]').spinner();

      //  If a method is set on the form use that value instead of the default post
      if ($form.attr('method') !== undefined) {
          method = $form.attr('method');
      }

      $.ajax({
        url: url,
        type: method,
        data: $form.serialize(),
        context: $form,
        success: this.ajaxSuccess,
        error: this.ajaxError
      });

      return false;
    },

    ajaxSuccess: function (response, textStatus, jqXHR) {
      var $form = $(this),
        data;

      if (response.success !== undefined && response.success) {
        // successful, so redirect
        window.location.reload();
      } else if (response.toLowerCase().indexOf('sign in') !== -1) {
        // if no longer signed in, redirect
        window.location.reload();
      } else if (jqXHR.status !== 200) {
        // if not a succesful request, reload page
        window.location.reload();
      } else {
        // if field errors, display them
        if (response.errors !== undefined) {
          data = {errors: []};
          $.each(response.errors, function (name, errors) {
            data.errors.push({label_id: name + '_label', label: $('#' + name + '_label').text(), error: errors[0]});
            moj.Events.trigger('Validation.renderFieldSummary', {form: $form, name: name, errors: errors});
          });
          moj.Events.trigger('Validation.renderSummary', {form: $form, data: data});
          // Track form errors
          moj.Events.trigger('formErrorTracker.checkErrors', {wrap: '#popup'});
          // show error summary
        } else if (response.success === undefined) {
          // repopulate popup
          $('#popup-content').html(response);
          // trigger title replacement event
          moj.Events.trigger('TitleSwitch.render', {wrap: '#popup'});
          // trigger postcode lookup event
          moj.Events.trigger('PostcodeLookup.render', {wrap: '#popup'});
          // trigger validation accessibility method
          moj.Events.trigger('Validation.render', {wrap: '#popup'});
          moj.Events.trigger('FormPopup.renderSelectionButtons');
          //  If the form submitted a reuse details parameter then execute the check details
          if ($form.serialize().indexOf('reuse-details') !== -1) {
            moj.Events.trigger('FormPopup.checkReusedDetails');
          }
          // Track form errors
          moj.Events.trigger('formErrorTracker.checkErrors', {wrap: '#popup'});
        } else {
          window.location.reload();
        }

        // Get the containing popup to redo the tab limiting as the DOM has changed
        moj.Modules.Popup.redoLoopedTabKeys();

        // stop spinner
        $form.find('input[type="submit"]').spinner('off');
      }
    },

    ajaxError: function () {
      // an error occured, reload the page
      window.location.reload();
    }
  };

  // Add module to MOJ namespace
  moj.Modules.FormPopup = new FormPopup();
}());
