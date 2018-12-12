// Title Switch module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.TitleSwitch = {
    selector: '[name="name-title"]',

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
      var $titleInput = $(el),
          $label = $('label[for="' + $titleInput.attr('id') + '"]'),
          value = $titleInput.val(),
          options = $titleInput.data('select-options'),
          _this = this,
          $select;

      //  If the current value isn't an option then exit and just display as text
      if (!$.isArray(options) || !_.includes(options, value)) {
        return;
      }

      // build select box with the options
      $select = $('<select>', {
        'id': $titleInput.attr('id'),
        'name': $titleInput.attr('name'),
        'class': 'js-TitleSwitch-select form-control'
      });

      // add options and select an existing value if possible
      $.each(options, function (idx, text) {
        $select.append($('<option>', { value: text }).text(text));

        if (value === text) {
          $select.val(text);
        }
      });

      //  Replace the text input with the new select input
      $titleInput.replaceWith($select);
    },

    selectChanged: function (e) {
      var $titleInput = $(e.target),
          value = $titleInput.val();

      if (value === 'Other') {
        //  Replace the select input with a text input
        var $text = $('<input>', {
          'id': $titleInput.attr('id'),
          'name': $titleInput.attr('name'),
          'class': 'form-control',
          'type':'text',
          'placeholder': 'Please specify'
        });

        //  Replace the select input with the new text input
        $titleInput.replaceWith($text);
        $titleInput.val(value);

        var modifiedTitleField = $(this.selector);
        modifiedTitleField.focus();
      }
    }
  };

})();
