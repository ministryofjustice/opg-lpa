/* globals $ */
// Dependencies: moj, jQuery
(function () {
  'use strict'

  const moj = window.moj

  moj.Modules.Applicant = {
    init: function () {
      this.selectionBehaviour()
    },

    selectionBehaviour: function () {
      // Only do the following if .js-attorney-list exists
      if ($('.js-attorney-list')[0]) {
        // Toggle all checkboxes under Attorneys
        $('[name="whoIsRegistering"]').on('change', function () {
          if ($(this).val() === 'donor') {
            //  Uncheck the attorney checkboxes and re-render the input styles
            $('.js-attorney-list input:checkbox').prop('checked', false)

            moj.Modules.Applicant.renderRegisteredByInputs()
          }
        })

        //  If an attorney checkbox is checked then ensure that the correct radio button is selected
        $('.js-attorney-list input').on('change', function () {
          //  If an attorney is selected directly then trigger the radio button select
          if ($('.js-attorney-list input').is(':checked')) {
            $('input[name="whoIsRegistering"][value!="donor"]').prop('checked', true)
          }

          moj.Modules.Applicant.renderRegisteredByInputs()
        })
      }
    },

    renderRegisteredByInputs: function () {
      //  Render the radio buttons and checkboxes as required
      $('[name="whoIsRegistering"]').parent().removeClass('selected')
      $('[name="whoIsRegistering"]:checked').parent().addClass('selected')

      $('.js-attorney-list input').parent().removeClass('selected')
      $('.js-attorney-list input:checked').parent().addClass('selected')
    }

  }
})()
