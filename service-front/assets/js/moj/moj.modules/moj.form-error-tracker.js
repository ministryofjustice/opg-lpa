/* globals $ */
// Analytics form error tracking module for LPA
// Dependencies: moj, jQuery
(function () {
  'use strict'

  const moj = window.moj
  const GOVUK = window.GOVUK

  moj.Modules.formErrorTracker = {
    init: function () {
      this.checkErrors()

      // Make available within popups by adding to the Events object
      moj.Events.on('formErrorTracker.checkErrors', this.checkErrors)
    },

    checkErrors: function () {
      // Iterating through link errors
      const errorsLinked = $('.error-summary-list li a')
      for (let i = 0; i < errorsLinked.length; i++) {
        moj.Modules.formErrorTracker.trackError(errorsLinked[i])
      }

      // Iterating through text errors
      const errorsText = $('.error-summary-text p')
      for (let i = 0; i < errorsText.length; i++) {
        moj.Modules.formErrorTracker.trackErrorText(errorsText[i])
      }
    },

    trackError: function (error) {
      if (GOVUK.analytics === undefined) {
        return
      }

      const $error = $(error)
      const errorText = ('' + $error.text()).trim()
      const errorID = $error.attr('href')
      const questionText = this.getQuestionText(error)

      const actionLabel = errorID + ' - ' + errorText

      const options = {
        transport: 'beacon',
        label: actionLabel
      }

      GOVUK.analytics.trackEvent('form error', questionText, options)
    },

    trackErrorText: function (error) {
      if (GOVUK.analytics === undefined) {
        return
      }

      const trackingContext = $('.error-summary-text').data('tracking-context')
      const trackingSummary = $(error).data('tracking-summary')

      const options = {
        transport: 'beacon',
        label: trackingSummary
      }

      GOVUK.analytics.trackEvent('form error', trackingContext, options)
    },

    getQuestionText: function (error) {
      const $error = $(error)
      const errorID = $error.attr('href')
      let questionText = ''

      if (errorID.indexOf('secret_') >= 0) {
        questionText = 'CSRF error'
      } else {
        const $element = $(errorID)
        const elementID = $element.prop('id')

        const nodeName = document.getElementById(elementID).nodeName.toLowerCase()
        let legendText

        // If the error is on an input or textarea
        if (nodeName === 'input' || nodeName === 'textarea' || nodeName === 'select') {
          // Get the label
          questionText = $('label[for="' + elementID + '"]')[0].childNodes[0].nodeValue.trim()
          // Get the legend for that label/input
          legendText = ('' + $element.closest('fieldset').find('legend').text()).trim()
          // combine the legend with the label
          questionText = legendText.length > 0 ? legendText + ': ' + questionText : questionText
        } else if (nodeName === 'fieldset') {
          // If the error is on a fieldset (for radio buttons and checkboxes)
          legendText = ('' + $element.find('legend').text()).trim()
          questionText = legendText
        }
      }

      return questionText
    }
  }
})()
