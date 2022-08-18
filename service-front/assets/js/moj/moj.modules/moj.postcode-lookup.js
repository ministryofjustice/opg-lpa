// Postcode lookup module for LPA
// Dependencies: moj, _, jQuery

;(function () {
  'use strict'

  const lpa = window.lpa || {}
  const moj = window.moj || {}

  const makePostcodeLookup = function (wrap) {
    const that = {}

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

    // TODO remove jQuery
    const $wrap = $(wrap)
    const $postalFields = $wrap.find('.js-PostcodeLookup__postal-add')

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

      const $el = $(e.target)
      const $searchContainer = $wrap.find('.js-PostcodeLookup__search')
      const $postcodeLabel = $('label[for="postcode-lookup"]')

      // store the current query
      query = $wrap.find('.js-PostcodeLookup__query').val()

      if (!$el.hasClass('disabled')) {
        if (query !== '') {
          $el.spinner()
          that.findPostcode(query)
          $searchContainer.removeClass('error')
          $postcodeLabel.children('.error-message').remove()
        } else {
          $searchContainer.addClass('error')
          $postcodeLabel.children('.error-message').remove()
          $postcodeLabel.append(
            errorMessageTpl({ errorMessage: 'Enter a postcode' })
          )
        }
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
      const $el = $(e.target)

      const $selectedOption = $el.find(':selected')

      $('[name*="' + settings.fieldMappings.line1 + '"]').val($selectedOption.data('line1'))
      $('[name*="' + settings.fieldMappings.line2 + '"]').val($selectedOption.data('line2'))
      $('[name*="' + settings.fieldMappings.line3 + '"]').val($selectedOption.data('line3'))
      $('[name*="' + settings.fieldMappings.postcode + '"]').val($selectedOption.data('postcode')).change()

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
    const postcodeRequestError = function (jqXHR, textStatus, errorThrown) {
      let errorText = 'There is a problem: '

      $wrap.find('.js-PostcodeLookup__search-btn').spinner('off')

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
        const $searchContainer = $wrap.find('.js-PostcodeLookup__search')
        const $postcodeLabel = $('label[for="postcode-lookup"]')

        if (response.isPostcodeValid) {
          $searchContainer.addClass('error')
          $postcodeLabel.children('.error-message').remove()
          $postcodeLabel.append(
            $(errorMessageTpl({
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
        const resultsNode = $(resultTpl({ results: response.addresses }))
        resultsNode.on('change', resultsChanged)

        if ($wrap.find('.js-PostcodeLookup__search-results').length > 0) {
          $wrap.find('.js-PostcodeLookup__search-results').parent().replaceWith(resultsNode)
        } else {
          $wrap.find('.js-PostcodeLookup__search').after(resultsNode)
        }
        $wrap.find('.js-PostcodeLookup__search-results').focus()
      }
      $wrap.find('.js-PostcodeLookup__search-btn').spinner('off')
    }

    // public API
    that.init = function () {
      // prepend template to postal fields
      $postalFields.before(searchTpl() + toggleTpl() + changeTpl()).addClass('hidden')

      // if all fields are empty and there are no validation messages, hide the fields
      if (hasCleanFields() && document.querySelector('.error-summary') === null) {
        wrap.querySelector('[data-role=postcodelookup-search]').classList.add('hidden')
      } else {
        that.hideSearchForm()
        that.toggleAddress()
      }
    }

    that.findPostcode = function (query) {
      $.ajax({
        url: settings.postcodeSearchUrl,
        data: { postcode: query },
        dataType: 'json',
        timeout: 10000,
        cache: true,
        error: postcodeRequestError,
        success: postcodeRequestSuccess
      })
    }

    that.hideSearchForm = function () {
      wrap.querySelector('.js-PostcodeLookup__search').classList.add('hidden')
      $wrap.find('.js-PostcodeLookup__toggle-address').closest('div').addClass('hidden')
      $wrap.find('.js-PostcodeLookup__change').closest('div').removeClass('hidden')
    }

    that.toggleAddress = function () {
      const $search = $wrap.find('.js-PostcodeLookup__query')
      const $pcode = $wrap.find('[name*="' + settings.fieldMappings.postcode + '"]')

      // populate postcode field
      if ($search.val() !== '' && $pcode.val() === '') {
        $pcode.val($search.val()).change()
      }

      $postalFields.removeClass('hidden')

      // focus on first address field
      if ($('.js-PostcodeLookup__postal-add').parent().find('#address-search-result').length === 1) {
        $postalFields.find('[name*="addr1"]').focus()
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
