;(function () {
  'use strict'

  const $ = window.jQuery
  const GOVUK = window.GOVUK || {}

  function ShowHideContent () {
    const self = this

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
    function getToggledContent ($control) {
      let id = control.getAttribute('aria-controls')

      // ARIA attributes aren't set before init
      if (!id) {
        id = $(control).closest('[data-target]').data('target')
      }

      // Find show/hide content by id
      return document.getElementById(id)
    }

    // Show toggled content for control
    function showToggledContent ($control, $content) {
      // Show content
      if ($content.hasClass('js-hidden')) {
        $content.removeClass('js-hidden')
        $content.attr('aria-hidden', 'false')

        // If the controlling input, update aria-expanded
        if ($control.attr('aria-controls')) {
          $control.attr('aria-expanded', 'true')
        }
      }
    }

    // Hide toggled content for control
    function hideToggledContent ($control, $content) {
      $content = $content || getToggledContent($control)

      // Hide content
      if (!$content.hasClass('js-hidden')) {
        $content.addClass('js-hidden')
        $content.attr('aria-hidden', 'true')

        // If the controlling input, update aria-expanded
        if ($control.attr('aria-controls')) {
          $control.attr('aria-expanded', 'false')
        }
      }
    }

    // Handle radio show/hide
    function handleRadioContent (control) {
      const $content = getToggledContent($(control))

      // All radios in this group which control content
      const selector = selectors.radio + '[name=' + escapeElementName($(control).attr('name')) + '][aria-controls]'
      const $form = $(control).closest('form')
      const $radios = $form.length ? $form.find(selector) : $(selector)

      // Hide content for radios in group
      $radios.each(function () {
        hideToggledContent($(this))
      })

      // Select content for this control
      if (moj.Helpers.matchesSelector(control, '[aria-controls]')) {
        showToggledContent($(control), $content)
      }
    }

    // Handle checkbox show/hide
    function handleCheckboxContent (control) {
      const $content = getToggledContent($(control))

      if (control.checked) {
        // Show checkbox content
        showToggledContent($(control), $content)
      } else {
        // Hide checkbox content
        hideToggledContent($(control), $content)
      }
    }

    // Set up event handlers etc
    function init (elementSelector, eventSelectors, handler) {
      document.querySelectorAll(elementSelector).forEach(function (control) {
        // Set aria-controls and defaults
        const $content = getToggledContent($(control))

        if ($content.length) {
          control.setAttribute('aria-controls', $content.attr('id'))
          control.setAttribute('aria-expanded', 'false')
          content.setAttribute('aria-hidden', 'true')
        }

        // Any already checked on init? Call the handler to show content if so
        if (control.checked) {
          handler(control)
        }
      })

      // Handle events
      document.body.addEventListener('click', function (e) {
        for (let eventSelector in eventSelectors) {
          if (moj.Helpers.matchesSelector(e.target, eventSelector)) {
            e.preventDefault()
            handler(e.target)
            break
          }
        }
      })
    }

    // Set up radio show/hide content for container
    self.showHideRadioToggledContent = function () {
      const selectors = []

      // Build an array of radio group selectors
      document.querySelectorAll(selectors.radio).forEach(function (elt) {
        const selector = 'input[type="radio"][name="' + elt.getAttribute('name') + '"]'

        if (selectors.indexOf(selector) === -1) {
          selectors.push(selector)
        }
      })

      init(selectors.radio, selectors, handleRadioContent)
    }

    // Set up checkbox show/hide content for container
    self.showHideCheckboxToggledContent = function () {
      init(selectors.checkbox, [selectors.checkbox], handleCheckboxContent)
    }

    // Remove event handlers
    self.destroy = function ($container) {
      $container = $container || $(document.body)
      $container.off('.' + selectors.namespace)
    }
  }

  ShowHideContent.prototype.init = function () {
    this.showHideRadioToggledContent()
    this.showHideCheckboxToggledContent()
  }

  GOVUK.ShowHideContent = ShowHideContent
  window.GOVUK = GOVUK
})()
