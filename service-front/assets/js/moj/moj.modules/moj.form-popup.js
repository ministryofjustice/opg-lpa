// Form Popup module for LPA
// Dependencies: moj, jQuery

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
  const FormPopup = function (options) {
    this.settings = $.extend({}, this.defaults, options)
  }

  FormPopup.prototype = {
    defaults: {
      selector: '.js-form-popup',
      overlayIdent: 'form-popup',
      overlaySource: '#content'
    },

    init: function () {
      // bind 'this' as this in following methods
      _.bindAll(this, 'btnClick', 'submitForm')
      this.cacheEls()
      this.bindEvents()
      $(this.settings.selector).attr('data-inited', true)
    },

    cacheEls: function () {
      this.formContent = []
      this.originalSource = false
      this.source = false
    },

    bindEvents: function () {
      $('body')
        // form open
        .on('click.moj.Modules.FormPopup', this.settings.selector, this.btnClick)
        // submit form
        .on('submit.moj.Modules.FormPopup', '#popup.form-popup form', this.submitForm)
      moj.Events.on('FormPopup.checkReusedDetails', this.checkReusedDetails)
    },

    btnClick: function (e) {
      e.preventDefault()

      // if our clicked element is not a link traverse up the dom to find the parent that is one.
      const source = $(e.target).closest('a')
      const href = source.attr('href')

      // set original source to be the original link clicked form the body to be able to return to it when the popup is closed
      // fixes when links inside a popup load another form. User should be focused back to original content button when closing
      if ($('#popup').length === 0) {
        this.originalSource = source
      }
      // always set this source to be the clicked link
      this.source = source

      // If this link is disabled then stop here
      if (!source.hasClass('disabled')) {
        // set loading spinner on link
        linkSpinner = moj.Helpers.spinner(source.get(0))
        linkSpinner.on()

        // show form
        this.loadContent(href)
      }

      return false
    },

    loadContent: function (url) {
      const self = this

      moj.Helpers.ajax({
        url: url,
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
        ident: this.settings.overlayIdent,
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

    submitForm: function (e) {
      e.preventDefault()

      const $form = $(e.target)
      const url = $form.attr('action')

      formSpinner = moj.Helpers.spinner($form.find('input[type="submit"]').get(0))
      formSpinner.on()

      const successCb = this.ajaxSuccess
      const failureCb = this.ajaxError

      moj.Helpers.ajax({
        url: url,
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
        body: $form.serialize(),
        success: function (response) {
          successCb($form, response, null, null)
        },
        error: failureCb
      })

      return false
    },

    ajaxSuccess: function (form, response, textStatus, jqXHR) {
      let data

      if (response.success !== undefined && response.success) {
        // successful, so redirect
        window.location.reload()
      } else if (response.toLowerCase().indexOf('sign in') !== -1) {
        // if no longer signed in, redirect
        window.location.reload()
      } else if (jqXHR !== null && jqXHR.status !== 200) {
        // if not a succesful request, reload page
        window.location.reload()
      } else {
        // if field errors, display them
        if (response.errors !== undefined) {
          data = { errors: [] }

          $.each(response.errors, function (name, errors) {
            data.errors.push({ label_id: name + '_label', label: $('#' + name + '_label').text(), error: errors[0] })
            moj.Events.trigger('Validation.renderFieldSummary', { form: form, name, errors })
          })

          moj.Events.trigger('Validation.renderSummary', { form: form, data })

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
