// Error tracking module for Google Analytics

;(function (global) {
  'use strict'

  var $ = global.jQuery
  var GOVUK = global.GOVUK || {}

  GOVUK.analyticsPlugins = GOVUK.analyticsPlugins || {}
  GOVUK.analyticsPlugins.formErrorTracker = function () {

    var errorSummarySelector = '.error-summary-list a'

    var errors = $('.error-summary-list li a')
    for (var i = 0; i < errors.length; i++) {
      trackError(errors[i])
    }

    function trackError(error) {
      var $error = $(error)
      var errorText = $.trim($error.text())
      var errorID = $error.attr('href')
      var questionText = getQuestionText(error)

      var actionLabel = errorID + ' - ' + errorText

      var options = {
        transport: 'beacon',
        label: actionLabel
      }

      window.optionsGlobal = options

      GOVUK.analytics.trackEvent('form error', questionText, options)
    }

    function getQuestionText(error) {
      var $error = $(error)
      var errorID = $error.attr('href')

      var $element = $(errorID)
      var elementID = $element.prop('id')

      var nodeName = document.getElementById(elementID).nodeName.toLowerCase()
      var questionText
      var legendText

      // If the error is on an input or textarea
      if (nodeName === 'input' || nodeName === 'textarea') {
        // Get the label
        questionText = $.trim($('label[for="' + elementID + '"]')[0].childNodes[0].nodeValue)
        // Get the legend for that label/input
        legendText = $.trim($element.closest('fieldset').find('legend').text())
        // combine the legend with the label
        questionText = legendText.length > 0 ? legendText + ': ' + questionText : questionText
      }
      // If the error is on a fieldset (for radio buttons and checkboxes)
      else if (nodeName === 'fieldset') {
        legendText = $.trim($element.find('legend').text())
        questionText = legendText
      }
      // Anything else
      else {
        questionText = ''
      }

      return questionText
    }
  }

  global.GOVUK = GOVUK
})(window)
