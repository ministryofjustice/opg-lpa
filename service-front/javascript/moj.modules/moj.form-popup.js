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
    },

    btnClick: function (e) {
      var source = $(e.target),
        href = source.attr('href'),
        form = source.data('form');

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
        this.loadContent(href, form);
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
          // render form
          self.renderForm(html);
          if (url.indexOf('use-my-details') !== -1) {
            $('#dob-date-day').trigger('change');
          }
        }
      });
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
        }
      });

      // hide use button and switch button
      $('#form-seed-details-picker, #form-correspondent-selector').find('input[type=submit]').hide();

    },

    submitForm: function (e) {
      var $form = $(e.target),
        url = $form.attr('action');

      $form.find('input[type="submit"]').spinner();

      $.ajax({
        url: url,
        type: 'post',
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
          // show error summary
        } else if (response.success === undefined) {
          // repopulate popup
          $('#popup-content').html(response);
          // trigger title replacement event
          moj.Events.trigger('TitleSwitch.render', {wrap: '#popup'});
          // trigger postcode lookup event
          moj.Events.trigger('PostcodeLookup.render', {wrap: '#popup'});
          // trigger use these details event
          moj.Events.trigger('Reusables.render', {wrap: '#popup'});
          // trigger validation accessibility method
          moj.Events.trigger('Validation.render', {wrap: '#popup'});
        } else {
          window.location.reload();
        }
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