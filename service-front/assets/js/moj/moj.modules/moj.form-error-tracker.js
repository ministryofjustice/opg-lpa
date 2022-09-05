// Analytics form error tracking module for LPA
;(function () {
  'use strict'

  const GOVUK = window.GOVUK || {}

  window.moj = window.moj || {}
  const moj = window.moj

  moj.Modules.formErrorTracker = {

    init: function () {
      this.checkErrors()

      // Make available within popups by adding to the Events object
      moj.Events.on('formErrorTracker.checkErrors', this.checkErrors)
    },

    checkErrors: function () {
      // Iterate through link errors
      document.querySelectorAll('.error-summary-list li a').forEach(function (errorLink) {
        moj.Modules.formErrorTracker.trackError(errorLink)
      })

      // Iterate through text errors
      document.querySelectorAll('.error-summary-text p').forEach(function (errorText) {
        moj.Modules.formErrorTracker.trackErrorText(errorText)
      })
    },

    trackError: function (error) {
      if (GOVUK.analytics === undefined) {
        return
      }

      const errorText = error.textContent
      const errorId = error.getAttribute('href')
      const questionText = this.getQuestionText(error)

      const options = {
        transport: 'beacon',
        label: errorId + ' - ' + errorText
      }

      GOVUK.analytics.trackEvent('form error', questionText, options)
    },

    trackErrorText: function (error) {
      if (GOVUK.analytics === undefined) {
        return
      }

      const errorSummaryElt = document.querySelector('.error-summary-text')
      let trackingContext = ''
      if (errorSummaryElt !== null) {
        trackingContext = errorSummaryElt.getAttribute('data-tracking-context')
      }

      const options = {
        transport: 'beacon',
        label: error.getAttribute('data-tracking-summary')
      }

      GOVUK.analytics.trackEvent('form error', trackingContext, options)
    },

    getQuestionText: function (error) {
      const errorId = error.getAttribute('href')

      let questionText = ''

      if (errorId.indexOf('secret_') >= 0) {
        questionText = 'CSRF error'
      } else {
        const element = document.querySelector(errorId)
        if (element === null) {
          return ''
        }

        const elementId = element.getAttribute('id')

        let nodeName
        try {
          nodeName = document.getElementById(elementId).nodeName.toLowerCase()
        } catch (e) {
          console.error(e)

          // if we can't get the question text, we can't track the error on GA
          console.error('moj.form-error-tracker.js: unable to track error during form save; culprit was: error ID = ' + errorId, error)
          return ''
        }

        // If the error is on an input or textarea
        if (nodeName === 'input' || nodeName === 'textarea' || nodeName === 'select') {
          // Get the label
          questionText = document.querySelector('label[for="' + elementId + '"]').childNodes[0].nodeValue

          // Get the legend for that label/input
          // fieldset which is the parent of the element
          let legend = null
          let candidate = element
          while (candidate !== document.body) {
            candidate = candidate.parentNode
            if (candidate.tagName === 'FIELDSET') {
              legend = candidate.querySelector('legend')
              break
            }
          }

          const legendText = (legend === null ? '' : legend.textContent)

          // combine the legend with the label
          questionText = legendText.length > 0 ? legendText + ': ' + questionText : questionText
        } else if (nodeName === 'fieldset') {
          // If the error is on a fieldset (for radio buttons and checkboxes)
          questionText = element.querySelector('legend').textContent
        }
      }

      return questionText
    }
  }
})()
