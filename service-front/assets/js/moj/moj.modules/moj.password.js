// Password module for LPA
;(function () {
  window.moj = window.moj || {}
  const moj = window.moj

  // Applies to /login /signup and /user/change-password
  // on change password page there are two show / hide links
  moj.Modules.PasswordHide = {
    init: function () {
      const links = document.querySelectorAll('.js-showHidePassword')
      const skipConfirmPasswords = document.querySelectorAll('#js-skipConfirmPassword')
      const pwdConfirms = document.querySelectorAll('#password_confirm')

      // By default ensure that the confirm password hidden validation
      // skip value is set to false and show the link
      skipConfirmPasswords.forEach(function (skipConfirmPassword) {
        skipConfirmPassword.value = '0'
      })

      links.forEach(function (link) {
        // The show/hide password links are themselves hidden
        // by default so they're not available for non-JS - show them now
        link.classList.remove('hidden')

        link.addEventListener('click', function (e) {
          e.preventDefault()

          const pwd = document.querySelector('#' + link.getAttribute('data-for'))

          // Determine if we are showing or hiding the password confirm input
          const isShowing = (pwd !== null && pwd.getAttribute('type') === 'password')

          const alsoHideConfirm = link.getAttribute('data-alsoHideConfirm')

          if (alsoHideConfirm) {
            if (isShowing) {
              pwdConfirms.forEach(function (pwdConfirm) {
                pwdConfirm.parentNode.classList.add('hidden')
              })

              skipConfirmPasswords.forEach(function (skipConfirmPassword) {
                skipConfirmPassword.value = '1'
              })
            } else {
              pwdConfirms.forEach(function (pwdConfirm) {
                pwdConfirm.parentNode.classList.remove('hidden')
              })

              skipConfirmPasswords.forEach(function (skipConfirmPassword) {
                skipConfirmPassword.value = '0'
              })
            }
          }

          // Change the input values as required
          pwd.setAttribute('type', (isShowing ? 'text' : 'password'))
          link.innerHTML = (isShowing ? 'Hide password' : 'Show password')

          return false
        })
      })
    }
  }
})()
