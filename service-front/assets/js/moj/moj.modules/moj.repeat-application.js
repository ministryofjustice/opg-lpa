// Repeat Application code module for LPA
;(function () {
  'use strict'

  window.moj = window.moj || {}
  const moj = window.moj

  const lpa = window.lpa

  moj.Modules.RepeatApplication = {
    _dialogEventsBound: false,

    init: function () {
      this.render = this.render.bind(this)
      moj.Events.on('RepeatApplication.render', this.render)
      this.render()
    },

    render: function () {
      const self = this

      document.querySelectorAll('form#form-repeat-application').forEach(function (elt) {
        elt.addEventListener('click', function (e) {
          if (!moj.Helpers.matchesSelector(e.target, 'input[type="submit"]')) {
            return true
          }

          e.preventDefault()
          self.onRepeatApplicationFormClickHandler(e)
          return false
        })
      })
    },

    onRepeatApplicationFormClickHandler: function (e) {
      let formSubmitted = false

      const isRepeatChecked = document.querySelector('[name="isRepeatApplication"][value="is-repeat"]')

      if (isRepeatChecked !== null && isRepeatChecked.checked) {
        const formToSubmit = e.target.form

        e.preventDefault()
        e.stopImmediatePropagation()

        const html = lpa.templates['dialog.confirmRepeatApplication']({
          dialogTitle: 'Confirm',
          dialogMessage: 'I confirm that OPG has said a repeat application ' +
            'can be made within 3 months for half the normal application fee.',
          acceptButtonText: 'Confirm and continue',
          cancelButtonText: 'Cancel',
          acceptClass: 'js-dialog-accept',
          cancelClass: 'js-dialog-cancel'
        })

        moj.Modules.Popup.open(html, {
          ident: 'dialog',
          beforeOpen: moj.Modules.Popup.redoLoopedTabKeys
        })

        // this is required to prevent multiple click handlers being
        // attached on top of the existing event handlers on the popup
        if (!this._dialogEventsBound) {
          document.querySelectorAll('.dialog').forEach(function (elt) {
            elt.addEventListener('click', function (e) {
              // we only care about clicks on the "Confirm and continue"
              // or "Cancel" buttons on this mini-popup: the standard
              // close button in the top-right already has a handler on it,
              // so don't add another one here
              if (!moj.Helpers.matchesSelector(e.target, '.js-dialog-accept, .js-dialog-cancel')) {
                return true
              }

              e.preventDefault()

              if (!formSubmitted) {
                if (e.target.classList.contains('js-dialog-accept')) {
                  e.target.classList.add('disabled')
                  formToSubmit.submit()
                  formSubmitted = true
                } else {
                  moj.Modules.Popup.close()
                }
              }

              return false
            })
          })

          this._dialogEventsBound = true
        }
      }
    }
  }
})()
