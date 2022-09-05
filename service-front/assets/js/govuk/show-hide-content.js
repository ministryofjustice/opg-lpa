;(function () {
  'use strict'

  const $ = window.jQuery
  const GOVUK = window.GOVUK || {}

  function ShowHideContent () {
    const self = this

    const handlers = {}

    // Radio and Checkbox selectors
    const selectors = {
      namespace: 'ShowHideContent',
      radio: '[data-target] > input[type="radio"]',
      checkbox: '[data-target] > input[type="checkbox"]'
    }

    // Escape name attribute for use in DOM selector
    function escapeElementName (str) {
      const result = str.replace('[', '\\[').replace(']', '\\]')
      return result
    }

    // Return toggled content for control
    function getToggledContent (control) {
      let id = control.getAttribute('aria-controls')

      // ARIA attributes aren't set before init
      if (!id) {
        id = $(control).closest('[data-target]').data('target')
      }

      // Find show/hide content by id
      return document.getElementById(id)
    }

    // Show toggled content for control
    function showToggledContent (control, content) {
      // Show content
      if (content.classList.contains('js-hidden')) {
        content.classList.remove('js-hidden')
        content.setAttribute('aria-hidden', 'false')

        // If the controlling input, update aria-expanded
        if (control.getAttribute('aria-controls')) {
          control.setAttribute('aria-expanded', 'true')
        }
      }
    }

    // Hide toggled content for control
    function hideToggledContent (control, content) {
      content = content || getToggledContent(control)

      // Hide content
      if (!content.classList.contains('js-hidden')) {
        content.classList.add('js-hidden')
        content.setAttribute('aria-hidden', 'true')

        // If the controlling input, update aria-expanded
        if (control.getAttribute('aria-controls')) {
          control.setAttribute('aria-expanded', 'false')
        }
      }
    }

    // Handle radio show/hide
    function handleRadioContent (control) {
      const content = getToggledContent(control)

      // All radios in this group which control content
      const selector = selectors.radio + '[name=' + escapeElementName(control.getAttribute('name')) + '][aria-controls]'
      const $form = $(control).closest('form')
      const $radios = $form.length ? $form.find(selector) : $(selector)

      // Hide content for radios in group
      $radios.each(function (idx, radio) {
        hideToggledContent(radio)
      })

      // Select content for this control
      if (moj.Helpers.matchesSelector(control, '[aria-controls]')) {
        showToggledContent(control, content)
      }
    }

    // Handle checkbox show/hide
    function handleCheckboxContent (control) {
      const content = getToggledContent(control)

      if (control.checked) {
        // Show checkbox content
        showToggledContent(control, content)
      } else {
        // Hide checkbox content
        hideToggledContent(control, content)
      }
    }

    // Set up event handlers etc
    function init (elementSelector, eventSelectors, handler) {
      document.querySelectorAll(elementSelector).forEach(function (control) {
        // Set aria-controls and defaults
        const content = getToggledContent(control)

        if (content !== null) {
          control.setAttribute('aria-controls', content.getAttribute('id'))
          control.setAttribute('aria-expanded', 'false')
          content.setAttribute('aria-hidden', 'true')
        }

        // Any already checked on init? Call the handler to show content if so
        if (control.checked) {
          handler(control)
        }
      })

      // Handle events
      handlers[elementSelector] = function (e) {
        for (let idx in eventSelectors) {
          if (moj.Helpers.matchesSelector(e.target, eventSelectors[idx])) {
            handler(e.target)
            break
          }
        }
      }

      document.body.addEventListener('click', handlers[elementSelector])
    }

    // Set up radio show/hide content for container
    self.showHideRadioToggledContent = function () {
      const eventSelectors = []

      // Build an array of radio group selectors; these are used to scope the
      // click handler to only the elements we want to show/hide content for
      document.querySelectorAll(selectors.radio).forEach(function (elt) {
        const eventSelector = 'input[type="radio"][name="' + elt.getAttribute('name') + '"]'

        if (eventSelectors.indexOf(eventSelector) === -1) {
          eventSelectors.push(eventSelector)
        }
      })

      init(selectors.radio, eventSelectors, handleRadioContent)
    }

    // Set up checkbox show/hide content for container
    self.showHideCheckboxToggledContent = function () {
      init(selectors.checkbox, [selectors.checkbox], handleCheckboxContent)
    }

    // Remove event handlers
    self.destroy = function () {
      document.body.removeEventListener('click', handlers[self.selectors.radio])
      document.body.removeEventListener('click', handlers[self.selectors.checkbox])
    }
  }

  ShowHideContent.prototype.init = function () {
    this.showHideRadioToggledContent()
    this.showHideCheckboxToggledContent()
  }

  GOVUK.ShowHideContent = ShowHideContent
  window.GOVUK = GOVUK
})()
