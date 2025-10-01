import { initAll } from '/assets/v2/js/govuk-frontend.min.js';

if (!window.__govukInited) {
  window.__govukInited = true;

  const start = () => {
    initAll();
    document.documentElement.dataset.govukInit = 'done';
    document.dispatchEvent(new CustomEvent('govuk:init'));
  };

  document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', start, { once: true })
    : start();
}
