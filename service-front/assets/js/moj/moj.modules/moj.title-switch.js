/* globals _, $ */
// Title Switch module for LPA
// Dependencies: moj, _, jQuery
;(function () {
  'use strict'

  const moj = window.moj

  moj.Modules.TitleSwitch = {
    selector: '[name="name-title"]',

    init: function () {
      // bind 'this' as this in following methods
      _.bindAll(this, 'render', 'selectChanged', 'switchTitle')
      this.bindEvents()
    },

    bindEvents: function () {
      $('body').on('change.moj.Modules.TitleSwitch', '.js-TitleSwitch-select', this.selectChanged)

      // default moj render event
      moj.Events.on('render', this.render)

      // custom render event
      moj.Events.on('TitleSwitch.render', this.render)
    },

    render: function (e, params) {
      const wrap = params !== undefined && params.wrap !== undefined ? params.wrap : 'body'
      $(this.selector, wrap).each(this.switchTitle)
    },

    switchTitle: function (i, el) {
      const $titleInput = $(el)
      const value = $titleInput.val()
      const options = $titleInput.data('select-options')

      // If the current value isn't an option then exit and just display as text
      if (!Array.isArray(options) || !_.includes(options, value)) {
        return
      }

      // build select box with the options
      const $select = $('<select>', {
        id: $titleInput.attr('id'),
        name: $titleInput.attr('name'),
        class: 'js-TitleSwitch-select form-control',
        'data-cy': $titleInput.attr('id')
      })

      // add options and select an existing value if possible
      $.each(options, function (idx, text) {
        $select.append($('<option>', { value: text }).text(text))

        if (value === text) {
          $select.val(text)
        }
      })

      // Replace the text input with the new select input
      $titleInput.replaceWith($select)
      $select.attr('data-inited', 'true')
    },

    selectChanged: function (e) {
      const $titleInput = $(e.target)
      const value = $titleInput.val()

      if (value === 'Other') {
        // Replace the select input with a text input
        const $text = $('<input>', {
          id: $titleInput.attr('id'),
          name: $titleInput.attr('name'),
          class: 'form-control',
          type: 'text',
          placeholder: 'Please specify',
          'data-cy': $titleInput.attr('id')
        })

        // Replace the select input with the new text input
        $titleInput.replaceWith($text)
        $titleInput.val(value)

        const modifiedTitleField = $(this.selector)
        modifiedTitleField.trigger('focus')
      }
    }
  }
})()
