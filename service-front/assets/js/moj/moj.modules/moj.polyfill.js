/* globals $ */
// <details> polyfill
// http://caniuse.com/#feat=details
// FF Support for HTML5's <details> and <summary>
// https://bugzilla.mozilla.org/show_bug.cgi?id=591737
// http://www.sitepoint.com/fixing-the-details-element/
// A fork of https://github.com/alphagov/govuk_elements/commits/master/assets/javascripts/govuk/details.polyfill.js
;(function () {
  'use strict'

  const moj = window.moj

  // Wrap a single jQuery element with the necessary event handlers
  // and add required aria attributes; usually, this will be a <details>
  // element, but other custom elements can be used (e.g. for testing)
  //
  // details: jQuery element to polyfill
  // identifier: unique number for creation of ID on the wrapped element
  //     (used for ARIA controls)
  // hasNativeImplementation: set to true if the element has native
  //     details support (i.e. a boolean open attribute)
  function wrapDetails (details, identifier, hasNativeImplementation) {
    const noop = function () {}

    // --------- private variables

    // element references
    const summary = $(details.find('summary'))
    const content = $(details.children('div'))
    let contentId = content.attr('id')
    const focusables = $(details.find('a,:input'))

    // icon shown when details are not native to the browser
    let arrow = null

    let inited = false

    // set to true while we are in the middle of opening/closing the element
    let transitioning = false

    // --------- public API
    const wrapped = {}

    const openNonNative = hasNativeImplementation
      ? noop
      : function () {
        // non-native details emulation
        content.css('display', '')
        arrow.removeClass('arrow-closed')
        arrow.addClass('arrow-open')
        arrow.text('\u25bc')
      }

    wrapped.open = function () {
      if (transitioning) {
        return false
      }
      transitioning = true

      summary.attr('aria-expanded', 'true')
      content.attr('aria-hidden', 'false')
      focusables.attr('tabindex', '0')

      openNonNative()

      transitioning = false
      return true
    }

    const closeNonNative = hasNativeImplementation
      ? noop
      : function () {
        // non-native details emulation
        content.css('display', 'none')
        arrow.removeClass('arrow-open')
        arrow.addClass('arrow-closed')
        arrow.text('\u25ba')
      }

    wrapped.close = function () {
      if (transitioning) {
        return false
      }
      transitioning = true

      summary.attr('aria-expanded', 'false')
      content.attr('aria-hidden', 'true')
      focusables.attr('tabindex', '-1')

      closeNonNative()

      transitioning = false
      return true
    }

    const handleStateChange = function () {
      (details.attr('open') === 'open' ? wrapped.open() : wrapped.close())
    }

    const polyfillToggle = function () {
      // Toggle the open attribute on details element
      (details.attr('open') === 'open'
        ? details.removeAttr('open')
        : details.attr('open', 'open'))

      // Fire a toggle event on the details element; as we've set the
      // open attr, the existing handler for native details will trigger
      // the correct response
      details.trigger('toggle')
    }

    // Additional initialisation required where details is non-native
    // (this is the crux of the details polyfill)
    const nonNativeInit = hasNativeImplementation
      ? noop
      : function () {
        // Set tabIndex so the summary is keyboard accessible
        // http://www.saliences.com/browserBugs/tabIndex.html
        summary.attr('tabIndex', '0')

        // Create an arrow as the first child inside the summary
        arrow = $('<i>').insertBefore(summary.children().first())
        arrow.attr('aria-hidden', 'true')
        arrow.addClass('arrow')

        // A click on the summary toggles the open state of the details
        // element and fires a "toggle" event from it
        summary.on('click', polyfillToggle)
        summary.on('keypress', function (e) {
          // Enter/Return or Space key
          if (e.keyCode === 13 || e.keyCode === 32) {
            polyfillToggle()

            // This prevents space from scrolling the page
            // and enter from submitting a form
            e.preventDefault()
            return false
          }
          return true
        })

        // mark the element as polyfilled
        details.attr('data-polyfilled-details', 'true')

        // set the display type to block so the elements
        // don't end up running along a single line
        details.css('display', 'block')

        return true
      }

    // Initialises necessary state on the <details> and its children;
    // where <details> is native, this is mostly adding aria attributes
    wrapped.init = function () {
      // Don't call init more than once
      if (inited) {
        return inited
      }

      // Create an id for the content div if not present
      if (contentId === undefined) {
        contentId = 'details-content-' + identifier
        content.attr('id', contentId)
      }

      // Add ARIA attributes to details
      details.attr('role', 'group')

      // Add ARIA attributes to summary
      summary.attr('role', 'button')
      summary.attr('aria-controls', contentId)

      // Put a tabindex on summary so it is focusable (mostly for tests)
      summary.attr('tabindex', '0')

      // When we get a "toggle" event on the details element,
      // set attribute on the details element and change display;
      // note that native events are fired after the open/close completes;
      // for non-native, we synthesise this event
      details.on('toggle', handleStateChange)

      // additional work required where <details> is not native
      nonNativeInit();

      // Call open or close based on current state
      (details.attr('open') === 'open') ? wrapped.open() : wrapped.close()

      inited = true
      return inited
    }

    wrapped.init()

    return wrapped
  }

  // Initialisation function
  // This expects <details> elements with this structure:
  // <details>
  //   <summary>
  //   <div>
  // </details>
  // Anything matching the selector "a,:input" within details gets special
  // treatment, being added to the tab order as appropriate
  function addDetailsPolyfill (tag) {
    // Do we have a native implementation which supports an "open" attribute?
    // Note that we "polyfill" all matching elements, as we want
    // to incorporate ARIA attributes. We also do additional work when we
    // must provide a polyfill for <details> in browsers which don't have it.
    const hasNativeImplementation = typeof document.createElement(tag).open === 'boolean'

    // Get the collection of details elements; if it's empty
    // we don't need to bother initialising anything on this page
    const list = $(document).find(tag)
    if (list.length === 0) {
      return true
    }

    // Else iterate through them to apply their initial state
    for (let i = 0; i < list.length; i++) {
      const details = $(list[i])

      // Ensure each details element is only processed once
      if (details.data('filled')) {
        continue
      }
      details.data('filled', true)

      wrapDetails(details, i, hasNativeImplementation)
    }

    return true
  }

  moj.Modules.DetailsPolyfill = {
    init: function () {
      moj.Events.on('Polyfill.fill', this.fill)
      this.fill()
    },

    fill: function () {
      addDetailsPolyfill('details')
    },

    wrap: wrapDetails
  }
})()
