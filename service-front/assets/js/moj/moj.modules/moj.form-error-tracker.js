// Analytics form error tracking module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.formErrorTracker = {

    init: function () {
      this.checkErrors();
      // Make available within popups by adding to the Events object
      moj.Events.on('formErrorTracker.checkErrors', this.checkErrors);
    },

    checkErrors: function(){
      // Iterating through link errors
      var errorsLinked = $('.error-summary-list li a')
      for (var i = 0; i < errorsLinked.length; i++) {
        moj.Modules.formErrorTracker.trackError(errorsLinked[i])
      }

      // Iterating through text errors
      var errorsText = $('.error-summary-text p')
      for (var i = 0; i < errorsText.length; i++) {
        moj.Modules.formErrorTracker.trackErrorText(errorsText[i])
      }
    },

    trackError: function(error) {
      var $error = $(error)
      var errorText = $.trim($error.text())
      var errorID = $error.attr('href')
      var questionText = this.getQuestionText(error)

      var actionLabel = errorID + ' - ' + errorText

      var options = {
        transport: 'beacon',
        label: actionLabel
      }

      GOVUK.analytics.trackEvent('form error', questionText, options)
    },

    trackErrorText: function(error){
      var trackingContext = $('.error-summary-text').data('tracking-context')
      var trackingSummary = $(error).data('tracking-summary')

      var options = {
        transport: 'beacon',
        label: trackingSummary
      }

      GOVUK.analytics.trackEvent('form error', trackingContext, options)
    },

    getQuestionText: function(error) {
      var $error = $(error)
      var errorID = $error.attr('href')

      var $element = $(errorID)
      var elementID = $element.prop('id')

      var nodeName = document.getElementById(elementID).nodeName.toLowerCase()
      var questionText
      var legendText

      // If the error is on an input or textarea
      if (nodeName === 'input' || nodeName === 'textarea' || nodeName === 'select') {
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
  };
})();