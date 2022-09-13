;(function () {
  'use strict'

  window.moj = window.moj || {}
  const moj = window.moj

  moj.Modules.Dashboard = {
    init: function () {
      // Set background colour of the dashboard search box to white
      // when it is focused; set to transparent when blurred.
      // This obscures the prompt text "Search for an LPA or donor".
      const searchBox = document.querySelector('.js-search-focus')

      if (searchBox === null) {
        return
      }

      // Add .focus class if there's text already there (on load)
      if (searchBox.value !== '') {
        searchBox.classList.add('focus')
      }

      // Add .focus class when input gets focus
      searchBox.addEventListener('focus', function () {
        if (!searchBox.classList.contains('focus')) {
          searchBox.classList.add('focus')
        }
      })

      // Remove .focus class if input is blurred
      searchBox.addEventListener('blur', function () {
        if (searchBox.value === '') {
          searchBox.classList.remove('focus')
        }
      })
    }
  }
})()
