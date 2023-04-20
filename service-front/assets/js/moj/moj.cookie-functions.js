// https://github.com/alphagov/govuk_publishing_components/master/app/assets/javascripts/govuk_publishing_components/lib/cookie-functions.js
// used by the cookie banner component

(function (global) {
  'use strict';

  var GOVUK = global.GOVUK || {};

  var DEFAULT_COOKIE_CONSENT = {
    essential: true,
    usage: false,
  };

  var COOKIE_CATEGORIES = {
    cookie_policy: 'essential',
    seen_cookie_message: 'essential',
    _ga: 'usage',
    _ga_DY4BCL021L: 'usage',
  };

  /*
      Cookie methods
      ==============

      Usage:

        Setting a cookie:
        GOVUK.cookie('hobnob', 'tasty', { days: 30 });

        Reading a cookie:
        GOVUK.cookie('hobnob');

        Deleting a cookie:
        GOVUK.cookie('hobnob', null);
    */
  GOVUK.cookie = function (name, value, options) {
    if (typeof value !== 'undefined') {
      if (value === false || value === null) {
        if (typeof options === 'undefined') {
          options = { days: -1 };
        }
        return GOVUK.setCookie(name, '', options);
      } else {
        // Default expiry date of 30 days
        if (typeof options === 'undefined') {
          options = { days: 30 };
        }
        return GOVUK.setCookie(name, value, options);
      }
    } else {
      return GOVUK.getCookie(name);
    }
  };

  GOVUK.setDefaultConsentCookie = function () {
    GOVUK.setCookie('cookie_policy', JSON.stringify(DEFAULT_COOKIE_CONSENT), {
      days: 365,
    });
  };

  GOVUK.getConsentCookie = function () {
    var consentCookie = GOVUK.cookie('cookie_policy');
    var consentCookieObj;

    if (consentCookie) {
      try {
        consentCookieObj = JSON.parse(consentCookie);
      } catch (err) {
        return null;
      }

      if (typeof consentCookieObj !== 'object' && consentCookieObj !== null) {
        consentCookieObj = JSON.parse(consentCookieObj);
      }
    } else {
      return null;
    }

    return consentCookieObj;
  };

  GOVUK.setConsentCookie = function (options) {
    var cookieConsent = GOVUK.getConsentCookie();
    var regEx = new RegExp('^www.');
    var cookieDomain = regEx.test(document.domain)
      ? document.domain.replace(regEx, '.')
      : document.domain;

    if (!cookieConsent) {
      cookieConsent = JSON.parse(JSON.stringify(DEFAULT_COOKIE_CONSENT));
    }

    for (var cookieType in options) {
      cookieConsent[cookieType] = options[cookieType];

      // Delete cookies of that type if consent being set to false
      if (!options[cookieType]) {
        for (var cookie in COOKIE_CATEGORIES) {
          if (COOKIE_CATEGORIES[cookie] === cookieType) {
            GOVUK.cookie(cookie, null, { days: -1, domain: cookieDomain });
          }
        }
      }
    }
    GOVUK.setCookie('cookie_policy', JSON.stringify(cookieConsent), {
      days: 365,
    });
  };

  GOVUK.checkConsentCookieCategory = function (cookieName, cookieCategory) {
    var currentConsentCookie = GOVUK.getConsentCookie();

    // If the consent cookie doesn't exist, but the cookie is in our known list, return true
    if (currentConsentCookie === null) {
      return false;
    }

    // Sometimes currentConsentCookie is malformed in some of the tests, so we need to handle these
    try {
      return currentConsentCookie[cookieCategory];
    } catch (e) {
      console.error(
        e + ' when checking ' + cookieName + ' and ' + cookieCategory,
      );
      return false;
    }
  };

  GOVUK.checkConsentCookie = function (cookieName, cookieValue) {
    // If we're setting the consent cookie OR deleting a cookie, allow by default
    if (
      cookieName === 'cookie_policy' ||
      cookieValue === null ||
      cookieValue === false
    ) {
      return true;
    }

    if (COOKIE_CATEGORIES[cookieName]) {
      var cookieCategory = COOKIE_CATEGORIES[cookieName];

      return GOVUK.checkConsentCookieCategory(cookieName, cookieCategory);
    } else {
      // Deny the cookie if it is not known to us
      return false;
    }
  };

  GOVUK.setCookie = function (name, value, options) {
    if (GOVUK.checkConsentCookie(name, value)) {
      if (typeof options === 'undefined') {
        options = {};
      }
      var cookieString = name + '=' + value + '; path=/';
      if (options.days) {
        var date = new Date();
        date.setTime(date.getTime() + options.days * 24 * 60 * 60 * 1000);
        cookieString = cookieString + '; expires=' + date.toGMTString();
      }
      if (options.domain) {
        cookieString = cookieString + '; domain=' + options.domain;
      }
      if (document.location.protocol === 'https:') {
        cookieString = cookieString + '; Secure';
      }
      document.cookie = cookieString;
    }
  };

  GOVUK.getCookie = function (name) {
    var nameEQ = name + '=';
    var cookies = document.cookie.split(';');
    for (var i = 0, len = cookies.length; i < len; i++) {
      var cookie = cookies[i];
      while (cookie.charAt(0) === ' ') {
        cookie = cookie.substring(1, cookie.length);
      }
      if (cookie.indexOf(nameEQ) === 0) {
        return decodeURIComponent(cookie.substring(nameEQ.length));
      }
    }
    return null;
  };

  global.GOVUK = GOVUK;
})(window);
