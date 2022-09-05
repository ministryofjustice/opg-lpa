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

    // Adds ARIA attributes to control + associated content
    function initToggledContent () {
      const $control = $(this)
      const $content = getToggledContent($control)

      // Set aria-controls and defaults
      if ($content.length) {
        $control.attr('aria-controls', $content.attr('id'))
        $control.attr('aria-expanded', 'false')
        $content.attr('aria-hidden', 'true')
      }
    }

    // Return toggled content for control
    function getToggledContent ($control) {
      let id = $control.attr('aria-controls')

      // ARIA attributes aren't set before init
      if (!id) {
        id = $control.closest('[data-target]').data('target')
      }

      // Find show/hide content by id
      return $('#' + id)
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
    function handleRadioContent ($control, $content) {
      // All radios in this group which control content
      const selector = selectors.radio + '[name=' + escapeElementName($control.attr('name')) + '][aria-controls]'
      const $form = $control.closest('form')
      const $radios = $form.length ? $form.find(selector) : $(selector)

      // Hide content for radios in group
      $radios.each(function () {
        hideToggledContent($(this))
      })

      // Select content for this control
      if ($control.is('[aria-controls]')) {
        showToggledContent($control, $content)
      }
    }

    // Handle checkbox show/hide
    function handleCheckboxContent ($control, $content) {
      // Show checkbox content
      if ($control.is(':checked')) {
        showToggledContent($control, $content)
      } else { // Hide checkbox content
        hideToggledContent($control, $content)
      }
    }

    // Set up event handlers etc
    function init (elementSelector, eventSelectors, handler) {
      const $container = $(document.body)

      // Handler for clicks on controls
      function deferred () {
        const $control = $(this)
        handler($control, getToggledContent($control))
      }

      // Prepare ARIA attributes
      const $controls = $(elementSelector)

      $controls.each(initToggledContent)

      // Handle events
      $.each(eventSelectors, function (idx, eventSelector) {
        $container.on('click.' + selectors.namespace, eventSelector, deferred)
      })

      // Any already :checked on init?
      if ($controls.is(':checked')) {
        $controls.filter(':checked').each(deferred)
      }
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
