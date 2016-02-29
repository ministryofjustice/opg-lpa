// Title Switch module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.TitleSwitch = {
    selector: '[name="name-title"]',

    options: {
      'Mr': 'Mr',
      'Mrs': 'Mrs',
      'Miss': 'Miss',
      'Ms': 'Ms',
      'Dr': 'Dr',
      'Other': 'Other'
    },

    init: function () {
      // bind 'this' as this in following methods
      _.bindAll(this, 'render', 'selectChanged', 'switchTitle');
      this.bindEvents();
    },

    bindEvents: function () {
      $('body').on('change.moj.Modules.TitleSwitch', '.js-TitleSwitch-select', this.selectChanged);
      // default moj render event
      moj.Events.on('render', this.render);
      // custom render event
      moj.Events.on('TitleSwitch.render', this.render);
    },

    render: function (e, params) {
      var wrap = params !== undefined && params.wrap !== undefined ? params.wrap : 'body';
      $(this.selector, wrap).each(this.switchTitle);
    },

    switchTitle: function (i, el) {
      var $text = $(el),
          $label = $('label[for="' + $text.attr('id') + '"]'),
          value = $text.val(),
          _this = this,
          $select;

      // if the current value is not one of our options
      // or if element has already been replaced
      if ((!_.contains(this.options, value) && value !== '') || $text.data('moj.TitleSwitch') !== undefined) {
        return;
      }

      // set default value to Mr
      if (value === '') {
        $text.val('Mr').change();
        value = $text.val();
      }

      // build select box
      $select = $('<select>', {
        'id': $text.attr('id') + '__select',
        'name': $text.attr('id') + '__select',
        'class': 'js-TitleSwitch-select form-control'
      });
      // add options
      $.each(this.options, function (name, value) {
        $select.append($('<option>', { value: value }).text(name));
      });
      // check if current value matches an option
      if (_.contains(this.options, value)) {
        $text.hide();
        $select.val(value);
      }
      // add select box after element
      $text.data('moj.TitleSwitch', true).after($select).change(function () {
        var value = $(this).val();

        if (_.contains(_this.options, value)) {
          $select.val(value).change();
        } else {
          $select.val('Other').change();
        }
      });
      // change label to point to select element
      $label.attr('for', $text.attr('id') + '__select');
      // create a new label for the hidden input element
      $text.append($('<label>', { 'for': $text.attr('id'),'text':'Title', 'class':'visuallyhidden' }));
    },

    selectChanged: function (e) {
      var $select = $(e.target),
          $text = $select.prev(),
          $label = $('label[for="' + $text.attr('id') + '__select"]'),
          value = $select.val();

      if (value === 'Other') {
        if (_.contains(this.options, $text.val())) {
          $text.val('');
        }
        $label.attr('for', $text.attr('id'));
        $text.show().focus();
        $select.remove();
      } else {
        $text.val(value);
      }
    }
  };

})();