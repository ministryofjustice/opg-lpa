// Postcode lookup module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  // Define the class
  var PostcodeLookup = function (el) {
      _.bindAll(this, 'searchClicked', 'toggleClicked', 'changeClicked', 'resultsChanged', 'queryEnter', 'postcodeSuccess', 'postcodeError');
      this.cacheEls(el);
      this.bindEvents();
      this.init();
  };

  PostcodeLookup.prototype = {
    settings: {
      postcodeSearchUrl: '/address-lookup',
      // used to populate fields
      // key is the key name sent in response and value is name of app's field
      fieldMappings: {
        line1: 'address-address1',
        line2: 'address-address2',
        line3: 'address-address3',
        postcode: 'postcode'
      }
    },

    cacheEls: function (wrap) {
      this.$wrap = wrap;
      this.$form = this.$wrap.closest('form');
      this.$postalFields = this.$wrap.find('.js-PostcodeLookup__postal-add');

      this.searchTpl = lpa.templates['postcodeLookup.search-field'];
      this.toggleTpl = lpa.templates['postcodeLookup.address-toggle'];
      this.resultTpl = lpa.templates['postcodeLookup.search-result'];
      this.changeTpl = lpa.templates['postcodeLookup.address-change'];
      this.errorMessageTpl = lpa.templates['errors.formMessage'];
    },

    bindEvents: function () {
      this.$wrap.on('click.moj.Modules.PostcodeLookup', '.js-PostcodeLookup__search-btn', this.searchClicked);
      this.$wrap.on('click.moj.Modules.PostcodeLookup', '.js-PostcodeLookup__toggle-address', this.toggleClicked);
      this.$wrap.on('click.moj.Modules.PostcodeLookup', '.js-PostcodeLookup__change', this.changeClicked);
      this.$wrap.on('change.moj.Modules.PostcodeLookup', '.js-PostcodeLookup__search-results', this.resultsChanged);
      this.$wrap.on('keydown.moj.Modules.PostcodeLookup', '.js-PostcodeLookup__query', this.queryEnter);
    },

    init: function () {
        // prepend template to postal fields
        this.$postalFields.before(this.searchTpl() + this.toggleTpl() + this.changeTpl()).addClass('hidden');

        // if all fields are empty and there are no validation messages, hide them
        if (moj.Helpers.hasCleanFields(this.$postalFields) && !$('.error-summary').length) {
            this.$wrap.find('.js-PostcodeLookup__change').closest('div').addClass('hidden');
        } else {
            this.hideSearchForm();
            this.toggleAddress();
        }
    },

    hideSearchForm: function() {
        this.$wrap.find('.js-PostcodeLookup__search').addClass('hidden');
        this.$wrap.find('.js-PostcodeLookup__toggle-address').closest('div').addClass('hidden');
        this.$wrap.find('.js-PostcodeLookup__change').closest('div').removeClass('hidden');
    },

    changeClicked: function(e) {
        this.$wrap.find('.js-PostcodeLookup__change').closest('div').addClass('hidden');
        this.$wrap.find('.js-PostcodeLookup__search').removeClass('hidden');
        if (moj.Helpers.hasCleanFields(this.$postalFields) && !$('.error-summary').length) {
            this.$wrap.find('.js-PostcodeLookup__toggle-address').closest('div').removeClass('hidden');
        }
        this.$wrap.find('.js-PostcodeLookup__query').focus();
        return false;
    },

    searchClicked: function (e) {
      var $el = $(e.target);
      var $searchContainer = this.$wrap.find('.js-PostcodeLookup__search');
      var $postcodeLabel = $('label[for="postcode-lookup"]');

      // store the current query
      this.query = this.$wrap.find('.js-PostcodeLookup__query').val();

      if (!$el.hasClass('disabled')) {
        if (this.query !== '') {
          $el.spinner();
          this.findPostcode(this.query);
          $searchContainer.removeClass('error');
          $postcodeLabel.children('.error-message').remove();
        } else {
          $searchContainer.addClass('error');
          $postcodeLabel.children('.error-message').remove();
          $postcodeLabel
            .append($(this.errorMessageTpl({
              'errorMessage': 'Enter a postcode'
            })));
        }
      }
      return false;
    },

    toggleClicked: function (e) {
      var $el = $(e.target);
      this.toggleAddress();
      return false;
    },

    resultsChanged: function (e) {
      var $el = $(e.target),
      val = $el.val();

      var $selectedOption = $el.find(':selected');

      $('[name*="' + this.settings.fieldMappings.line1 + '"]').val($selectedOption.data('line1'));
      $('[name*="' + this.settings.fieldMappings.line2 + '"]').val($selectedOption.data('line2'));
      $('[name*="' + this.settings.fieldMappings.line3 + '"]').val($selectedOption.data('line3'));
      $('[name*="' + this.settings.fieldMappings.postcode + '"]').val($selectedOption.data('postcode')).change();

      this.toggleAddress();
    },

    queryEnter: function (e) {
      var code = (e.keyCode ? e.keyCode : e.which);

      if (code === 13) {
        e.preventDefault();
        this.$wrap.find('.js-PostcodeLookup__search-btn').click();
      }
    },

    findPostcode: function (query) {
      $.ajax({
        url: this.settings.postcodeSearchUrl,
        data: {postcode: query},
        dataType: 'json',
        timeout: 10000,
        cache: true,
        error: this.postcodeError,
        success: this.postcodeSuccess
      });
    },

    postcodeError: function (jqXHR, textStatus, errorThrown) {
      var errorText = 'There was a problem: ';

      this.$wrap.find('.js-PostcodeLookup__search-btn').spinner('off');

      if (textStatus === 'timeout') {
        errorText += 'the service did not respond in the allotted time';
      } else {
        errorText += errorThrown;
      }

      alert(errorText);
    },

    postcodeSuccess: function (response) {
      // not successful
      if (!response.success || response.addresses === null) {
        var $searchContainer = this.$wrap.find('.js-PostcodeLookup__search');
        var $postcodeLabel = $('label[for="postcode-lookup"]');

        if (response.isPostcodeValid) {
          $searchContainer.addClass('error');
          $postcodeLabel.children('.error-message').remove();
          $postcodeLabel
            .append($(this.errorMessageTpl({
              'errorMessage': 'No address found for this postcode. Please try again or enter the address manually.'
            })));
        } else {
          alert('Enter a valid UK postcode');
        }
      } else {
        // successful

        if (this.$wrap.find('.js-PostcodeLookup__search-results').length > 0) {
          this.$wrap.find('.js-PostcodeLookup__search-results').parent().replaceWith(this.resultTpl({results: response.addresses}));
        } else {
          this.$wrap.find('.js-PostcodeLookup__search').after(this.resultTpl({results: response.addresses}));
        }
        this.$wrap.find('.js-PostcodeLookup__search-results').focus();
      }
      this.$wrap.find('.js-PostcodeLookup__search-btn').spinner('off');
    },

    toggleAddress: function () {
        var $search = this.$wrap.find('.js-PostcodeLookup__query'),
          $pcode = this.$wrap.find('[name*="' + this.settings.fieldMappings.postcode + '"]');
        // populate postcode field
        if ($search.val() !== '' && $pcode.val() === '') {
          $pcode.val($search.val()).change();
        }
        this.$postalFields.removeClass('hidden');
        // focus on first address field
        if ($('.js-PostcodeLookup__postal-add').parent().find('#address-search-result').length === 1) {
          this.$postalFields.find('[name*="addr1"]').focus();
        }
    }
  };

  // Add module to LPA namespace
  moj.Modules.PostcodeLookup = {
    init: function () {
      $('.js-PostcodeLookup').each(function () {
        if (!$(this).data('moj.PostcodeLookup')) {
          $(this).data('moj.PostcodeLookup', new PostcodeLookup($(this), $(this).data()));
        }
      });

      moj.Events.on('PostcodeLookup.render', this.render);
    },

    render: function (e, params) {
      $('.js-PostcodeLookup', params.wrap).each(function () {
        if (!$(this).data('moj.PostcodeLookup')) {
          $(this).data('moj.PostcodeLookup', new PostcodeLookup($(this), $(this).data()));
        }
      });
    }
  };
}());
