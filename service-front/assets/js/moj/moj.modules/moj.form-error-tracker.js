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

      if (nodeName === 'input' || nodeName === 'textarea') {
        questionText = $.trim($('label[for="' + elementID + '"] .question-text').text())
        legendText = $.trim($element.closest('fieldset').find('legend').text())
        questionText = legendText.length > 0 ? legendText + ': ' + questionText : questionText
      }
      else if (nodeName === 'fieldset') {
        legendText = $.trim($element.find('legend').text())
        questionText = legendText
      }
      else {
        questionText = ''
      }

      return questionText
    }
  }

  global.GOVUK = GOVUK
})(window)
