// https://github.com/alphagov/govuk_publishing_components/master/app/assets/javascripts/govuk_publishing_components/lib/cookie-functions.js
// used by the cookie banner component

;(function () {
  'use strict'

  const GOVUK = window.GOVUK || {}

  const DEFAULT_COOKIE_CONSENT = {
    essential: true,
    usage: false
  }

  const COOKIE_CATEGORIES = {
    cookie_policy: 'essential',
    seen_cookie_message: 'essential',
    _ga: 'usage',
    _gid: 'usage'
  }

  /*
   * Cookie methods
   * ==============

   * Usage:

   * Setting a cookie:
   * GOVUK.cookie('hobnob', 'tasty', { days: 30 });

   * Reading a cookie:
   * GOVUK.cookie('hobnob');

   * Deleting a cookie:
   * GOVUK.cookie('hobnob', null);
   */
  GOVUK.cookie = function (name, value, options) {
    if (typeof value !== 'undefined') {
      if (value === false || value === null) {
        if (typeof options === 'undefined') {
          options = { days: -1 }
        }
        return GOVUK.setCookie(name, '', options)
      } else {
        // Default expiry date of 30 days
        if (typeof options === 'undefined') {
          options = { days: 30 }
        }
        return GOVUK.setCookie(name, value, options)
      }
    } else {
      return GOVUK.getCookie(name)
    }
  }

  GOVUK.setDefaultConsentCookie = function () {
    GOVUK.setCookie('cookie_policy', JSON.stringify(DEFAULT_COOKIE_CONSENT), { days: 365 })
  }

  GOVUK.getConsentCookie = function () {
    const consentCookie = GOVUK.cookie('cookie_policy')
    let consentCookieObj

    if (consentCookie) {
      try {
        consentCookieObj = JSON.parse(consentCookie)
      } catch (err) {
        return null
      }

      if (typeof consentCookieObj !== 'object' && consentCookieObj !== null) {
        consentCookieObj = JSON.parse(consentCookieObj)
      }
    } else {
      return null
    }

    return consentCookieObj
  }

  GOVUK.setConsentCookie = function (options) {
    let cookieConsent = GOVUK.getConsentCookie()
    const regEx = /^www\./
    const cookieDomain = regEx.test(document.domain) ? document.domain.replace(regEx, '.') : document.domain

    if (!cookieConsent) {
      cookieConsent = JSON.parse(JSON.stringify(DEFAULT_COOKIE_CONSENT))
    }

    for (const cookieType in options) {
      cookieConsent[cookieType] = options[cookieType]

      // Delete cookies of that type if consent being set to false
      if (!options[cookieType]) {
        for (const cookie in COOKIE_CATEGORIES) {
          if (COOKIE_CATEGORIES[cookie] === cookieType) {
            GOVUK.cookie(cookie, null, { days: -1, domain: cookieDomain })
          }
        }
      }
    }
    GOVUK.setCookie('cookie_policy', JSON.stringify(cookieConsent), { days: 365 })
  }

  GOVUK.checkConsentCookieCategory = function (cookieName, cookieCategory) {
    const currentConsentCookie = GOVUK.getConsentCookie()

    // If the consent cookie doesn't exist, but the cookie is in our known list, return true
    if (currentConsentCookie === null) {
      return false
    }

    // Sometimes currentConsentCookie is malformed in some of the tests, so we need to handle these
    try {
      return currentConsentCookie[cookieCategory]
    } catch (e) {
      console.error(e + ' when checking ' + cookieName + ' and ' + cookieCategory)
      return false
    }
  }

  GOVUK.checkConsentCookie = function (cookieName, cookieValue) {
    // If we're setting the consent cookie OR deleting a cookie, allow by default
    if (cookieName === 'cookie_policy' || (cookieValue === null || cookieValue === false)) {
      return true
    }

    if (COOKIE_CATEGORIES[cookieName]) {
      const cookieCategory = COOKIE_CATEGORIES[cookieName]
      return GOVUK.checkConsentCookieCategory(cookieName, cookieCategory)
    } else {
      // Deny the cookie if it is not known to us
      return false
    }
  }

  GOVUK.setCookie = function (name, value, options) {
    if (GOVUK.checkConsentCookie(name, value)) {
      if (typeof options === 'undefined') {
        options = {}
      }
      let cookieString = name + '=' + value + '; path=/'
      if (options.days) {
        const date = new Date()
        date.setTime(date.getTime() + (options.days * 24 * 60 * 60 * 1000))
        cookieString = cookieString + '; expires=' + date.toGMTString()
      }
      if (options.domain) {
        cookieString = cookieString + '; domain=' + options.domain
      }
      if (document.location.protocol === 'https:') {
        cookieString = cookieString + '; Secure'
      }
      document.cookie = cookieString
    }
  }

  GOVUK.getCookie = function (name) {
    const nameEQ = name + '='
    const cookies = document.cookie.split(';')
    for (let i = 0, len = cookies.length; i < len; i++) {
      let cookie = cookies[i]
      while (cookie.charAt(0) === ' ') {
        cookie = cookie.substring(1, cookie.length)
      }
      if (cookie.indexOf(nameEQ) === 0) {
        return decodeURIComponent(cookie.substring(nameEQ.length))
      }
    }
    return null
  }

  window.GOVUK = GOVUK
}())
