// ====================================================================================
// INITITALISE ALL MOJ MODULES
$(moj.init);
// ====================================================================================
// SIMPLE UTILITIES

(function () {
  function markSupported() {
    var b = document.body;

    if (!b) {
      document.addEventListener('DOMContentLoaded', markSupported, {
        once: true,
      });
      return;
    }

    b.classList.add('js-enabled');

    if ('noModule' in HTMLScriptElement.prototype) {
      b.classList.add('govuk-frontend-supported');
    }
  }

  markSupported();
})();

(function initGovuk() {
  function run() {
    if (
      window.GOVUKFrontend &&
      typeof window.GOVUKFrontend.initAll === 'function'
    ) {
      window.GOVUKFrontend.initAll();
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run, { once: true });
  } else {
    run();
  }
})();
