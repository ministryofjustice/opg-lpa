/*jshint unused: false */
// Reusables module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';
  var selected;
  moj.Modules.Reusables = {
    selector: '.js-reusable',
    message: 'This will replace the information which you have already entered, are you sure?',

    init: function () {
      _.bindAll(this, 'linkClicked', 'selectChanged');
      this.bindEvents();
    },

    bindEvents: function () {
      $('body')
        .on('click.moj.Modules.Reusables', 'a' + this.selector, this.linkClicked)
        .on('change.moj.Modules.Reusables', 'select' + this.selector, this.selectChanged);
    },

    // <a> click
    linkClicked: function (e, params) {
      var $el = $(e.target),
        $form = $el.closest('form'),
        url = $el.data('service'),
        proceed = this.isFormClean($form) ? true : confirm(this.message),
        _this = this;

      $el.spinner();
      $.get(url, function (data) {
        $el.spinner('off');
        if (proceed) {
          _this.populateForm(data);
        }
      });
      return false;
    },

    // <select> change
    selectChanged: function (e, params) {
      var $el = $(e.target),
        $form = $el.closest('form'),
        url = $form.attr('action'),
        postData,
        _this = this,
        proceed;

      if (($el.val() === '') || ($el.val() === selected)) {
        return;
      }

      proceed = this.isFormClean($form.next('form')) ? true : confirm(this.message);

      if (proceed) {
        $el.spinner();

        selected = $el.val();

        if ($form.find('[name=switch-to-type]').length === 0) {
          postData = {'secret': $form.find('[name=secret]').val(), 'pick-details': $form.find('[name=pick-details]').val()};
        }
        else {
          postData = {'secret': $form.find('[name=secret]').val(), 'switch-to-type': $form.find('[name=switch-to-type]').val(), 'switcher-submit': $form.find('[name=switcher-submit]').val()};
        }

        $.post(url, postData, function (data) {
          $el.spinner('off');
          if (proceed) {
            _this.populateForm(data);
          }
        });
      } else {
        // In case the user chose not to overwrite the details, we must select something
        // neutral to allow re-selecting that option (on change)
        $el.val($el.find('option:first').val());
      }
    },

    populateForm: function (data) {
      var $el,
        $focus,
        i = 0,
        props,
        property,
        value,
        value2;
      
      // prepare the data
      for( props in data ){
      	// if value is an object then flatten it with PHP array notation...
    	  value = data[props];
    	  if(_.isObject(value)){
    		  for ( property in value ){
      			value2 = value[property];
      			data[props + '[' + property + ']'] = value2;
      		  } 
    	  }
      }

      // Show any fields which were hidden
      $('.js-PostcodeLookup__toggle-address[data-address-type="postal"]').click();
      // loop over data and change values
      _(data).each(function (value, key) {
    	      	  
        // set el
        $el = $('[name="' + key + '"]');
        // if value is null, set to empty string
        value = (value === null) ? '' : value;
        // make sure the element exists && that new value doesn't match current value
        if ($el.length > 0 && $el.val() !== value) {
          // increment counter
          i += 1;
          // change the value of the element
          if (key === 'canSign') {
            //for donor canSign checkbox
            if ((value === false)) {
              $el.filter('[type=checkbox]').attr('checked', 'checked');
            }
          }
          else {
            $el.val(value).change();
          }
          // if first element changed, save the el
          if (i === 1) {
            $focus = $('[name="' + key + '"]');
          }
        }
      });
      // focus on first changed, or first form element (accessibility)
      if ($focus !== undefined) {
        $focus.focus();
      } else {
        $('input[type=text], select, textarea').filter(':visible').first().focus();
      }
    },

    isFormClean: function (form) {
      var clean = true;
      $('input[type="text"], select:not(.js-reusable), textarea', form).each(function () {
        if ($(this).val() !== '' && $(this).filter('[name*="name-title"]').val() !== 'Mr') {
          clean = false;
        }
      });
      return clean;
    }
  };

})();