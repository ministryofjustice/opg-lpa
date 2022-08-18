// Postcode lookup module for LPA
;(function () {
  'use strict'

  const lpa = window.lpa || {}
  const moj = window.moj || {}

  const makePostcodeLookup = function (wrap) {
    const that = {}

    // assigned when the templates are first loaded and the search button
    // is added to the page
    that.spinner = null

    const settings = {
      postcodeSearchUrl: '/address-lookup',
      // used to populate fields
      // key is the key name sent in response and value is name of app's field
      fieldMappings: {
        line1: 'address-address1',
        line2: 'address-address2',
        line3: 'address-address3',
        postcode: 'postcode'
      }
    }

    const postalForm = wrap.querySelector('.js-PostcodeLookup__postal-add')

    const searchTpl = lpa.templates['postcodeLookup.search-field']
    const toggleTpl = lpa.templates['postcodeLookup.address-toggle']
    const resultTpl = lpa.templates['postcodeLookup.search-result']
    const changeTpl = lpa.templates['postcodeLookup.address-change']
    const errorMessageTpl = lpa.templates['errors.formMessage']

    let query = ''

    // check if form elements within the element context are all empty
    const hasCleanFields = function () {
      let clean = true
      const selector = 'input:not([type="submit"]), select:not([name*="country"]), textarea'

      postalForm.querySelectorAll(selector).forEach(function (element) {
        const val = element.getAttribute('value')

        if (val !== '') {
          clean = false
        }
      })

      return clean
    }

    // event handlers
    const changeClicked = function (e) {
      e.preventDefault()

      wrap.querySelector('[data-role=postcodelookup-search]').classList.add('hidden')
      wrap.querySelector('.js-PostcodeLookup__search').classList.remove('hidden')
      if (hasCleanFields(postalForm) && document.querySelector('.error-summary') === null) {
        wrap.querySelector('[data-role="postcodelookup-search-input"]').classList.remove('hidden')
      }
      wrap.querySelector('.js-PostcodeLookup__query').focus()

      return false
    }

    const searchClicked = function (e) {
      e.preventDefault()

      const el = e.target
      const searchContainer = wrap.querySelector('.js-PostcodeLookup__search')
      const postcodeLabel = wrap.querySelector('label[for=postcode-lookup]')

      // store the current query
      query = wrap.querySelector('.js-PostcodeLookup__query').value

      if (!el.classList.contains('disabled')) {
        // remove any error message elements
        postcodeLabel.querySelectorAll('.error-message').forEach(function (child) {
          child.parentNode.removeChild(child)
        })

        if (query === '') {
          searchContainer.classList.add('error')

          postcodeLabel.appendChild(
            moj.Helpers.strToHtml(
              errorMessageTpl({ errorMessage: 'Enter a postcode' })
            )
          )

          return false
        }

        that.spinner.on()

        that.findPostcode(query)
        searchContainer.classList.remove('error')
      }

      return false
    }

    const toggleClicked = function (e) {
      e.preventDefault()
      that.toggleAddress()
      return false
    }

    // when a postcode result (address) is selected, update address fields
    const resultsChanged = function (e) {
      const selectedOption = e.target.options[e.target.selectedIndex]

      wrap.querySelector('[name*="' + settings.fieldMappings.line1 + '"]').value = selectedOption.getAttribute('data-line1')
      wrap.querySelector('[name*="' + settings.fieldMappings.line2 + '"]').value = selectedOption.getAttribute('data-line2')
      wrap.querySelector('[name*="' + settings.fieldMappings.line3 + '"]').value = selectedOption.getAttribute('data-line3')
      wrap.querySelector('[name*="' + settings.fieldMappings.postcode + '"]').value = selectedOption.getAttribute('data-postcode')

      that.toggleAddress()
    }

    // capture "Return" key presses in the search box
    const queryEnter = function (e) {
      const code = (e.keyCode ? e.keyCode : e.which)

      if (code === 13) {
        e.preventDefault()

        // synthesise a click event on the search button
        const event = document.createEvent('HTMLEvents')
        event.initEvent('click', true, true)
        wrap.querySelector('.js-PostcodeLookup__search-btn').dispatchEvent(event)
      }
    }

    // request handling
    const postcodeRequestError = function (textStatus, errorThrown) {
      let errorText = 'There is a problem: '

      that.spinner.off()

      if (textStatus === 'timeout') {
        errorText += 'the service did not respond in the allotted time'
      } else {
        errorText += errorThrown
      }

      window.alert(errorText)
    }

    const postcodeRequestSuccess = function (response) {
      // not successful
      if (!response.success || response.addresses === null) {
        const searchContainer = wrap.querySelector('.js-PostcodeLookup__search')
        const postcodeLabel = wrap.querySelector('label[for=postcode-lookup]')

        if (response.isPostcodeValid) {
          searchContainer.classList.add('error')

          postcodeLabel.querySelectorAll('.error-message').forEach(function (child) {
            child.parentNode.removeChild(child)
          })

          postcodeLabel.appendChild(
            moj.Helpers.strToHtml(errorMessageTpl({
              errorMessage: 'Enter a real postcode. If you live overseas, ' +
                  'enter your address manually instead of using the postcode lookup'
            }))
          )
        } else {
          // don't think this is reachable...
          window.alert('Enter a valid UK postcode')
        }
      } else {
        // successful
        const resultsNode = moj.Helpers.strToHtml(resultTpl({ results: response.addresses }))
        resultsNode.addEventListener('change', resultsChanged)

        // remove the old results node
        wrap.querySelectorAll('[data-role=postcodelookup-search-result]').forEach(function (element) {
          element.parentNode.removeChild(element)
        })

        // add the new results after the search box; there's no insertAfter, so
        // we get the next sibling node of the search box and insert before it, so
        // it ends up between the search box and its next sibling node
        const searchBox = wrap.querySelector('.js-PostcodeLookup__search')
        searchBox.parentNode.insertBefore(resultsNode, searchBox.nextSibling)

        // focus on the drop-down with found addresses
        wrap.querySelector('.js-PostcodeLookup__search-results').focus()
      }

      that.spinner.off()
    }

    // public API
    that.init = function () {
      // prepend search box etc. templates to form containing address fields
      postalForm.classList.add('hidden')

      const changeNode = moj.Helpers.strToHtml(changeTpl())
      const toggleNode = moj.Helpers.strToHtml(toggleTpl())
      const searchNode = moj.Helpers.strToHtml(searchTpl())

      const parent = postalForm.parentNode
      parent.insertBefore(changeNode, postalForm)
      parent.insertBefore(toggleNode, changeNode)
      parent.insertBefore(searchNode, toggleNode)

      that.spinner = moj.Helpers.spinner(wrap.querySelector('.js-PostcodeLookup__search-btn'))

      // if all fields are empty and there are no validation messages, hide the fields
      if (hasCleanFields() && document.querySelector('.error-summary') === null) {
        wrap.querySelector('[data-role=postcodelookup-search]').classList.add('hidden')
      } else {
        that.hideSearchForm()
        that.toggleAddress()
      }
    }

    that.findPostcode = function (query) {
      moj.Helpers.ajax({
        url: settings.postcodeSearchUrl,
        query: {postcode: query},
        isJSON: true,
        timeout: 10000,
        error: postcodeRequestError,
        success: postcodeRequestSuccess
      })
    }

    that.hideSearchForm = function () {
      wrap.querySelector('.js-PostcodeLookup__search').classList.add('hidden')
      wrap.querySelector('[data-role=postcodelookup-manual]').classList.add('hidden')
      wrap.querySelector('[data-role=postcodelookup-search]').classList.remove('hidden')
    }

    that.toggleAddress = function () {
      const search = wrap.querySelector('.js-PostcodeLookup__query')
      const pcode = wrap.querySelector('[name*="' + settings.fieldMappings.postcode + '"]')

      // populate postcode field
      if (search.value !== '' && pcode.value === '') {
        pcode.value = search.value

        // trigger change event
        const event = document.createEvent('HTMLEvents')
        event.initEvent('change', true, true)
        pcode.dispatchEvent(event)
      }

      postalForm.classList.remove('hidden')

      // focus on first address field if the drop-down is populated
      if (wrap.querySelectorAll('#address-search-result').length === 1) {
        postalForm.querySelector('[name*="address1"]').focus()
      }
    }

    that.init()

    // attach event handlers
    wrap.querySelector('.js-PostcodeLookup__search-btn').addEventListener('click', searchClicked)
    wrap.querySelector('.js-PostcodeLookup__toggle-address').addEventListener('click', toggleClicked)
    wrap.querySelector('.js-PostcodeLookup__change').addEventListener('click', changeClicked)
    wrap.querySelector('.js-PostcodeLookup__query').addEventListener('keydown', queryEnter)

    return that
  }

  // Add module to LPA namespace
  moj.Modules.PostcodeLookup = {
    init: function () {
      document.querySelectorAll('.js-PostcodeLookup').forEach(function (element) {
        moj.Modules.PostcodeLookup.wrap(element)
      })

      moj.Events.on('PostcodeLookup.render', this.render)
    },

    render: function (e, params) {
      const context = document.querySelector(params.wrap)
      context.querySelectorAll('.js-PostcodeLookup').forEach(function (element) {
        moj.Modules.PostcodeLookup.wrap(element)
      })
    },

    wrap: function (element) {
      if (element.getAttribute('data-inited') === null) {
        makePostcodeLookup(element)
        element.setAttribute('data-inited', 'true')
      }
    }
  }
}())
