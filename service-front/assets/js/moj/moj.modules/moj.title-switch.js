// Person title (honorific) options module for LPA
;(function () {
  'use strict'

  window.moj = window.moj || {}
  const moj = window.moj

  moj.Modules.TitleSwitch = {
    selector: '[name="name-title"]',

    init: function () {
      const self = this

      document.body.addEventListener('change', function (e) {
        // delegated event listener
        if (moj.Helpers.matchesSelector(e.target, '.js-TitleSwitch-select')) {
          self._selectChanged(e)
        }
      })

      // default moj render event
      moj.Events.on('render', this.render.bind(this))

      // custom render event
      moj.Events.on('TitleSwitch.render', this.render.bind(this))
    },

    _switchTitle: function (titleInput) {
      const value = titleInput.value
      const options = JSON.parse(titleInput.getAttribute('data-select-options'))

      // If the current value isn't an option then exit and just display as text
      if (Object.prototype.toString.call(options) !== '[object Array]' || options.indexOf(value) === -1) {
        return
      }

      // build select box with the options
      const selectId = titleInput.getAttribute('id')
      const selectName = titleInput.getAttribute('name')
      const select = moj.Helpers.strToHtml(
        '<select id="' + selectId + '" name="' + selectName + '" ' +
        'class="js-TitleSwitch-select form-control" data-cy="' + selectId + '">'
      )

      // add options and select an existing value if possible
      options.forEach(function (text) {
        select.appendChild(
          moj.Helpers.strToHtml('<option value="' + text + '">' + text + '</option>')
        )

        if (value === text) {
          select.value = text
        }
      })

      // Replace the text input with the new select input
      titleInput.parentNode.replaceChild(select, titleInput)
    },

    _selectChanged: function (e) {
      const titleInput = e.target
      const value = titleInput.value

      if (value === 'Other') {
        // Replace the select input with a text input
        const id = titleInput.getAttribute('id')
        const name = titleInput.getAttribute('name')

        const text = moj.Helpers.strToHtml(
          '<input id="' + id + '" name="' + name + '" type="text" ' +
          'class="form-control" placeholder="Please specify" data-cy="' + id + '">'
        )

        titleInput.parentNode.replaceChild(text, titleInput)
        titleInput.value = value

        const focusOn = document.querySelector(this.selector)
        if (focusOn !== null) {
          focusOn.focus()
        }
      }
    },

    render: function (e, params) {
      const wrap = (params !== undefined && params.wrap !== undefined ? params.wrap : 'body')
      const wrapperElt = document.querySelector(wrap)

      if (wrapperElt === null) {
        return
      }

      wrapperElt.querySelectorAll(this.selector).forEach(this._switchTitle)
    }
  }
})()
