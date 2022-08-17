// Applicant form (Who's applying to register the LPA?)
;(function () {
  'use strict'

  const moj = window.moj || {}

  const renderRegisteredByInputs = function () {
    // Render the radio buttons and checkboxes as required
    document.querySelectorAll('input[name=whoIsRegistering]').forEach(function (radio) {
      radio.parentNode.classList.remove('selected')
      if (radio.checked) {
        radio.parentNode.classList.add('selected')
      }
    })

    document.querySelectorAll('.js-attorney-list input[type=checkbox]').forEach(function (checkbox) {
      checkbox.parentNode.classList.remove('selected')
      if (checkbox.checked) {
        checkbox.parentNode.classList.add('selected')
      }
    })
  }

  moj.Modules.Applicant = {
    init: function () {
      // Only do the following if .js-attorney-list exists,
      // i.e. there is more than one primary attorney
      const attorneyList = document.querySelector('.js-attorney-list')

      if (attorneyList !== null) {
        // If donor radio is selected, untick all attorney checkboxes
        document.querySelectorAll('input[name=whoIsRegistering][value=donor]').forEach(function (radio) {
          radio.addEventListener('change', function () {
            attorneyList.querySelectorAll('input[type=checkbox]').forEach(function (checkbox) {
              checkbox.checked = false
              renderRegisteredByInputs()
            })
          })
        })

        // Convert nodelist of attorney checkboxes to an array so it can be manipulated more easily
        const checkboxes = Array.prototype.slice.call(
          attorneyList.querySelectorAll('input[type=checkbox]')
        )

        // If an attorney checkbox is checked, select the attorneys radio button
        checkboxes.forEach(function (checkbox) {
          checkbox.addEventListener('change', function () {
            // Are any checkboxes checked?
            const checked = checkboxes.filter(function (checkbox) {
              return checkbox.checked
            }).length > 0

            if (checked) {
              document.querySelector('input[name=whoIsRegistering]:not([value=donor]').checked = true
            }

            renderRegisteredByInputs()
          })
        })
      }
    }
  }
})()
