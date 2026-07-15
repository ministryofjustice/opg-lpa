import {
  createAll,
  Accordion,
  Button,
  CharacterCount,
  Checkboxes,
  ErrorSummary,
  ExitThisPage,
  FileUpload,
  NotificationBanner,
  PasswordInput,
  Radios,
  ServiceNavigation,
  SkipLink,
  Tabs,
} from '/assets/v2/js/govuk-frontend.min.js';

/**
 * Workaround for a known govuk-frontend bug (issue #979, still open as of v6.x):
 * https://github.com/alphagov/govuk-frontend/issues/979
 *
 * govuk-frontend's Radios component sets aria-expanded on radio inputs to
 * communicate the state of conditional reveals. However, aria-expanded is not
 * a permitted attribute for role="radio" per the ARIA spec, and axe-core flags
 * it as a critical violation (aria-allowed-attr).
 *
 * This subclass overrides syncConditionalRevealWithInputState to omit the
 * invalid aria-expanded attribute. The govuk-radios__conditional--hidden CSS
 * class already applies display:none, which natively hides content from
 * assistive technologies — no additional ARIA attribute is needed.
 *
 * Remove this workaround once govuk-frontend resolves issue #979.
 */
class AccessibleRadios extends Radios {
  syncConditionalRevealWithInputState($input) {
    const targetId = $input.getAttribute('aria-controls');
    if (!targetId) {
      return;
    }
    const $target = document.getElementById(targetId);
    if ($target !== null && $target.classList.contains('govuk-radios__conditional')) {
      $target.classList.toggle('govuk-radios__conditional--hidden', !$input.checked);
    }
  }
}

if (!window.__govukInited) {
  window.__govukInited = true;

  const start = () => {
    [
      Accordion,
      Button,
      CharacterCount,
      Checkboxes,
      ErrorSummary,
      ExitThisPage,
      FileUpload,
      NotificationBanner,
      PasswordInput,
      AccessibleRadios,
      ServiceNavigation,
      SkipLink,
      Tabs,
    ].forEach((Component) => createAll(Component));

    document.documentElement.dataset.govukInit = 'done';
    document.dispatchEvent(new CustomEvent('govuk:init'));
  };

  document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', start, { once: true })
    : start();
}
