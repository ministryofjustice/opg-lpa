// Person Form module for LPA
;(function () {
  'use strict'

  window.moj = window.moj || {}
  const moj = window.moj

  const lpa = window.lpa

  const _getDOB = function (form) {
    const dayObj = form.querySelector('#dob-date-day')
    const monthObj = form.querySelector('#dob-date-month')
    const yearObj = form.querySelector('#dob-date-year')

    let day
    let month
    let year

    let birthdate

    if (dayObj !== null && dayObj.value !== '') {
      day = parseInt(dayObj.value, 10)
      if (isNaN(day) || day < 1 || day > 31) {
        day = undefined
      }
    }

    if (monthObj !== null && monthObj.value !== '') {
      month = parseInt(monthObj.value, 10)
      if (isNaN(month) || month <= 0 || month > 12) {
        month = undefined
      } else {
        month = month - 1
      }
    }

    if (yearObj !== null && yearObj.value !== '') {
      year = parseInt(yearObj.value, 10)
      if (isNaN(year) || (year <= 0)) {
        year = undefined
      }
    }

    birthdate = new Date(year, month, day)
    if (!isFinite(birthdate)) {
      birthdate = null
    }

    return birthdate
  }

  moj.Modules.PersonForm = {
    selector: '.js-PersonForm',

    init: function () {
      // bind events

      // default moj render event
      moj.Events.on('render', this.render.bind(this))

      // custom render event
      moj.Events.on('TitleSwitch.render', this.render.bind(this))
    },

    // params.wrap is a selector for a page element which acts as the context for
    // finding the person form to be initialised
    render: function (e, params) {
      const wrap = params !== undefined && params.wrap !== undefined ? params.wrap : 'body'

      const context = document.querySelector(wrap)
      if (context !== null) {
        context.querySelectorAll(this.selector).forEach(this.formEvents)
      }
    },

    formEvents: function (form) {
      const tplAlert = lpa.templates['alert.withinForm']

      // Listen for changes to form
      form.addEventListener('change', function (e) {
        // Only interested in events on input and select elements
        if (!moj.Helpers.matchesSelector(e.target, 'input, select')) {
          return true
        }

        const currentDate = new Date()

        const minAge = new Date(
          currentDate.getUTCFullYear() - 18,
          currentDate.getUTCMonth(),
          currentDate.getUTCDate()
        )

        const maxAge = new Date(
          currentDate.getUTCFullYear() - 100,
          currentDate.getUTCMonth(),
          currentDate.getUTCDate()
        )

        const firstName = form.querySelector('input[name="name-first"]')
        const firstNameValue = (firstName === null ? null : firstName.value.toLocaleLowerCase().trim())

        const lastName = form.querySelector('input[name="name-last"]')
        const lastNameValue = (lastName === null ? null : lastName.value.toLocaleLowerCase().trim())

        let duplicateName = null

        if (!e.target.classList.contains('confirmation-validation')) {
          // If the input changed is not a confirmation tick box, then do the form checks...
          const actorType = form.getAttribute('data-actor-type')

          // --- Duplicate person client-side validation
          if (
            e.target.getAttribute('name') === 'name-first' ||
            e.target.getAttribute('name') === 'name-last'
          ) {
            // Check for duplicate names
            const actorNames = JSON.parse(form.getAttribute('data-actor-names'))

            let item
            if (actorNames !== null && actorNames.length > 0) {
              for (let loop = 0; loop < actorNames.length; loop++) {
                item = actorNames[loop]

                if (
                  firstNameValue === item.firstname.toLocaleLowerCase() &&
                  lastNameValue === item.lastname.toLocaleLowerCase()
                ) {
                  duplicateName = item
                  break
                }
              }
            }

            // Cleanup
            form.querySelectorAll('.js-duplication-alert').forEach(function (elt) {
              elt.parentNode.removeChild(elt)
            })

            if (duplicateName !== null) {
              // Construct the correct starting phrase for the warning
              let alertStart = 'The ' + duplicateName.type + '\'s name is also '

              if (duplicateName.type === 'replacement attorney' || duplicateName.type === 'person to notify') {
                alertStart = 'There is also a ' + duplicateName.type + ' called '
              } else if (duplicateName.type === 'attorney') {
                alertStart = 'There is also an ' + duplicateName.type + ' called '
              }

              // Construct the middle part of the message
              let alertMiddle = 'The ' + duplicateName.type + ' cannot be '

              // If the user is attempting to create an attorney or replacement attorney twice show a specific line
              if (actorType === duplicateName.type && actorType === 'attorney') {
                alertMiddle = 'A person cannot be named as an attorney twice on the same LPA'
              } else if (actorType === duplicateName.type && actorType === 'replacement attorney') {
                alertMiddle = 'A person cannot be named as a replacement attorney twice on the same LPA'
              } else if (actorType === duplicateName.type && actorType === 'person to notify') {
                alertMiddle = 'A person should not be named as a person to notify twice on the same LPA'
              } else {
                // Check the rest of the logic
                if (duplicateName.type === 'replacement attorney' || duplicateName.type === 'person to notify') {
                  alertMiddle = 'A ' + duplicateName.type + ' cannot be '
                } else if (duplicateName.type === 'attorney') {
                  alertMiddle = 'An ' + duplicateName.type + ' cannot be '
                }

                if (actorType === 'replacement attorney' || actorType === 'person to notify') {
                  alertMiddle += 'a ' + actorType
                } else if (actorType === 'attorney') {
                  alertMiddle += 'an ' + actorType
                } else {
                  alertMiddle += 'the ' + actorType
                }
              }

              // Show warning after the last name field
              //
              const labelNode = form.querySelector('label[for="name-last"]')
              if (labelNode !== null) {
                const newNode = moj.Helpers.strToHtml(
                  tplAlert({
                    elementJSref: 'js-duplication-alert',
                    alertType: 'important-small',
                    alertMessage: '<p>' + alertStart + duplicateName.firstname + ' ' +
                      duplicateName.lastname + '. ' + alertMiddle +
                      '. By saving this section, you are confirming that ' +
                      'these are two different people with the same name.</p>'
                  })
                )

                // the form-group above the label
                const referenceNode = labelNode.parentNode

                // insert the warning after the last name field
                referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling)
              }

              // Focus on alert panel for accessibility
              const alertPanel = form.querySelector('.alert.panel')
              if (alertPanel !== null) {
                alertPanel.focus()
              }
            }
          }

          // --- Date of birth client-side validation
          const inputId = e.target.id
          if (inputId === 'dob-date-day' || inputId === 'dob-date-month' || inputId === 'dob-date-year') {
            // Cleanup
            form.querySelectorAll('.js-age-check').forEach(function (elt) {
              elt.parentNode.removeChild(elt)
            })

            // get dob input group
            const dobNode = form.querySelector('.dob-element')

            // if there's no dob input group, the following checks are moot, as we can't show the error anyway
            if (dobNode === null) {
              return true
            }

            // get the form-group above the input
            const referenceNode = dobNode.parentNode

            let newNode

            const dob = _getDOB(form)

            if (dob !== null) {
              // Display alerts if under 18 or over 100 years old
              // Under 18 and earlier than today. A server side validation
              // check is in place for dob greater than today.
              if (dob > minAge && dob < new Date()) {
                // Build up the under 18 warning message
                let ageWarningAlertStart = 'The ' + actorType + ' is under 18.'
                let ageWarningAlertMiddle = 'the donor'

                if (
                  actorType === 'attorney' ||
                  actorType === 'replacement attorney' ||
                  actorType === 'person to notify'
                ) {
                  ageWarningAlertStart = 'This ' + actorType + ' is under 18.'
                } else if (actorType === 'donor') {
                  ageWarningAlertMiddle = 'they'
                }

                newNode = moj.Helpers.strToHtml(
                  tplAlert({
                    elementJSref: 'js-age-check',
                    alertType: 'important-small',
                    alertMessage: ageWarningAlertStart + ' I understand that the ' + actorType +
                      ' must be at least 18 <strong class="bold-small">on the date ' +
                      ageWarningAlertMiddle + ' sign the LPA</strong>, otherwise the LPA will be rejected.'
                  })
                )

                // insert the warning after date of birth fields
                referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling)
              } else if (dob <= maxAge) {
                // Over 100
                newNode = moj.Helpers.strToHtml(
                  tplAlert({
                    elementJSref: 'js-age-check',
                    alertType: 'important-small',
                    alertMessage: 'By saving this section, you confirm that the person ' +
                      'is more than 100 years old. If not, please change the date.'
                  })
                )

                // insert the warning after date of birth fields
                referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling)
              }

              // Focus on alert panel for accessibility
              const alertPanel = form.querySelector('.alert.panel')
              if (alertPanel !== null) {
                alertPanel.focus()
              }
            }
          }
        }
      })
    }

  }
})()
