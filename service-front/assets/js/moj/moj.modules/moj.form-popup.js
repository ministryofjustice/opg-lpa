// Form Popup module for LPA
;(function () {
  'use strict'

  window.moj = window.moj || {}
  const moj = window.moj

  // spinner shown next to the link which opens the popup
  let linkSpinner = null

  // spinner shown next to submit button on the popup
  let formSpinner = null

  // cribbed from jQuery; see
  // https://github.com/jquery/jquery/blob/d0ce00cdfa680f1f0c38460bc51ea14079ae8b07/src/offset.js#L65
  // simplified to use window rather than document.ownerDocument.defaultView
  // as we don't have iframes
  const getOffset = function (element) {
    try {
      const rect = element.getBoundingClientRect()

      return {
        top: rect.top + window.pageYOffset,
        left: rect.left + window.pageXOffset
      }
    } catch (e) {
      // for browsers which don't support getBoundingClientRect()
      return { top: 0, left: 0 }
    }
  }

  // helper to scroll popup into view
  const scrollIntoView = function () {
    const targetElt = document.querySelector('#popup-content')
    const scrollElt = document.querySelector('#mask')
    const popupElt = document.querySelector('#popup')

    const topPos = getOffset(targetElt).top - getOffset(popupElt).top

    scrollElt.scrollTop = topPos

    // put focus into the first user input inside the target element
    const inputs = targetElt.querySelectorAll(
      'input:not([type=hidden]), checkbox, radio, select'
    )

    if (inputs.length > 0) {
      inputs[0].focus()
    }
  }

  // Define the class
  const FormPopup = function () {}

  FormPopup.prototype = {
    selector: '.js-form-popup',
    overlayIdent: 'form-popup',

    init: function () {
      this.formContent = []
      this.originalSource = false
      this.source = false

      const submitFormHandler = this.submitForm.bind(this)
      document.addEventListener('submit', function (e) {
        // capture submit events on forms inside the popup (delegated event handler)
        if (moj.Helpers.matchesSelector(e.target, '#popup.form-popup form')) {
          return submitFormHandler(e)
        }
        return true
      }, true)

      moj.Events.on('FormPopup.checkReusedDetails', this.checkReusedDetails)

      // note a popup is instantiated for every page, but not every page has
      // a button to open it; therefore we attach event handlers etc.
      // using querySelectorAll(), so it doesn't fail if the page lacks the button
      const openFormHandler = this.openForm.bind(this)
      document.querySelectorAll(this.selector).forEach(function (element) {
        element.addEventListener('click', openFormHandler)
        element.setAttribute('data-inited', 'true')
      })
    },

    openForm: function (e) {
      e.preventDefault()

      // if our clicked element is not a link traverse up the dom to find the parent that is one.
      const source = e.target
      const href = $(source).closest('a').attr('href')

      // If this link is disabled then stop here
      if (!source.classList.contains('disabled')) {
        // set loading spinner on link
        linkSpinner = moj.Helpers.spinner(source)
        linkSpinner.on()

        // show form
        this.loadContent(href)
      }

      return false
    },

    submitForm: function (e) {
      e.preventDefault()

      const form = e.target

      formSpinner = moj.Helpers.spinner(form.querySelector('input[type="submit"]'))
      formSpinner.on()

      const successCb = this.ajaxSuccess.bind(this)

      moj.Helpers.ajax({
        url: form.getAttribute('action'),
        method: 'POST',
        body: new FormData(form),
        success: function (response) {
          successCb(form, response)
        },
        error: this.ajaxError
      })

      return false
    },

    loadContent: function (url) {
      const self = this

      moj.Helpers.ajax({
        url,
        method: 'GET',
        success: function (html) {
          if (html.toLowerCase().indexOf('sign in') !== -1) {
            // if no longer signed in, redirect
            window.location.reload()
          } else {
            // render form and check the reused details content
            self.renderForm(html)

            if (url.indexOf('reuse-details') !== -1) {
              self.checkReusedDetails()
            }
          }
        }
      })
    },

    checkReusedDetails: function () {
      // Align to top after loading in the content to avoid having the form starting half
      // way down (a scenario that happens when you've scrolled far down on Reuse details page)
      scrollIntoView()

      // If the user is reusing details then trigger some actions manually to give warning messages a chance to display
      $('#dob-date-day').trigger('change')
      $('input[name="name-first"]').trigger('change')
    },

    renderForm: function (html) {
      linkSpinner.off()

      moj.Modules.Popup.open(html, {
        ident: this.overlayIdent,
        source: this.originalSource,
        beforeOpen: function () {
          // trigger title replacement event
          moj.Events.trigger('TitleSwitch.render', { wrap: '#popup' })

          // trigger postcode lookup event
          moj.Events.trigger('PostcodeLookup.render', { wrap: '#popup' })

          // trigger person form events
          moj.Events.trigger('PersonForm.render', { wrap: '#popup' })

          // trigger polyfill form events
          moj.Events.trigger('Polyfill.fill', { wrap: '#popup' })
        }
      })
    },

    ajaxSuccess: function (form, response) {
      form = $(form)

      let data

      if (response.success !== undefined && response.success) {
        // successful, so redirect
        window.location.reload()
      } else if (response.toLowerCase().indexOf('sign in') !== -1) {
        // if no longer signed in, redirect
        window.location.reload()
      } else {
        // if field errors, display them
        if (response.errors !== undefined) {
          data = { errors: [] }

          $.each(response.errors, function (name, errors) {
            data.errors.push({ label_id: name + '_label', label: $('#' + name + '_label').text(), error: errors[0] })
            moj.Events.trigger('Validation.renderFieldSummary', { form, name, errors })
          })

          moj.Events.trigger('Validation.renderSummary', { form, data })

          // Track form errors
          moj.Events.trigger('formErrorTracker.checkErrors', { wrap: '#popup' })

          // show error summary
        } else if (response.success === undefined) {
          // repopulate popup
          $('#popup-content').html(response)

          // trigger title replacement event
          moj.Events.trigger('TitleSwitch.render', { wrap: '#popup' })

          // trigger postcode lookup event
          moj.Events.trigger('PostcodeLookup.render', { wrap: '#popup' })

          // trigger validation accessibility method
          moj.Events.trigger('Validation.render', { wrap: '#popup' })

          //  If the form submitted a reuse details parameter then execute the check details
          if (form.serialize().indexOf('reuse-details') !== -1) {
            moj.Events.trigger('FormPopup.checkReusedDetails')
          }

          // Track form errors
          moj.Events.trigger('formErrorTracker.checkErrors', { wrap: '#popup' })
        } else {
          window.location.reload()
        }

        // Get the containing popup to redo the tab limiting as the DOM has changed
        moj.Modules.Popup.redoLoopedTabKeys()

        // stop spinner
        formSpinner.off()
      }
    },

    ajaxError: function () {
      // an error occured, reload the page
      window.location.reload()
    }
  }

  // Add module to MOJ namespace
  moj.Modules.FormPopup = new FormPopup()
}())
