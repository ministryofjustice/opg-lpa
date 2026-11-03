import { Before, Then, When } from '@badeball/cypress-cucumber-preprocessor';
import { detectOneLoginMode } from '../../support/onelogin_mode';

const ONE_LOGIN_ORIGIN = 'https://signin.integration.account.gov.uk';

const MAX_ONE_LOGIN_PAGES = 10;

// One Login's journey can change without notice, so each page is recognised by its path and handled
// on its own instead of assuming a fixed order. An unrecognised page fails with a description of it.
const ONE_LOGIN_PAGES = {
  '/sign-in-or-create': { action: 'click', selector: '#sign-in-button' },
  '/enter-email': { action: 'type', selector: '#email', value: 'email' },
  '/enter-password': { action: 'type', selector: '#password', value: 'password' },
  '/enter-authenticator-app-code': { action: 'type', selector: '#code', value: 'code' },
};

function oneLoginPage(pathname) {
  if (ONE_LOGIN_PAGES[pathname]) {
    return ONE_LOGIN_PAGES[pathname];
  }

  // Not seen yet, but One Login sometimes asks returning users to accept updated terms
  if (pathname.includes('terms')) {
    return { action: 'submit' };
  }

  return { action: 'describe' };
}

Before({ tags: '@RealOneLogin' }, function () {
  detectOneLoginMode().then((mode) => {
    if (mode !== 'real') {
      cy.log(`This environment does not use real GOV.UK One Login (${mode}), skipping`);
      this.skip();
      return;
    }

    cy.task('oneLoginIntegrationCredentials', null, { log: false }).then((credentials) => {
      if (credentials === null) {
        cy.log('The One Login integration test account credentials are not set, skipping');
        this.skip();
      }
    });
  });
});

function valueFor(page, credentials) {
  if (page.value === 'code') {
    return cy.task('oneLoginIntegrationCode', null, { log: false });
  }

  return cy.wrap(page.value ? credentials[page.value] : null, { log: false });
}

function actOnOneLoginPage(page, value) {
  cy.origin(ONE_LOGIN_ORIGIN, { args: { page, value } }, ({ page, value }) => {
    const submit = ($form) =>
      cy.wrap($form.find('button').filter((i, button) => button.type === 'submit').first()).click();

    if (page.action === 'click') {
      cy.get(page.selector).click();
    } else if (page.action === 'type') {
      cy.get(page.selector).clear().type(value, { log: false });
      cy.get(page.selector).closest('form').then(submit);
    } else if (page.action === 'submit') {
      cy.get('main form').first().then(submit);
    } else {
      cy.location('pathname').then((pathname) => cy.get('main').then(($main) => {
        const fields = $main.find('input:not([type=hidden]), select, textarea')
          .map((i, field) => `${field.tagName.toLowerCase()}#${field.id}[name=${field.name}]`)
          .get();
        const buttons = $main.find('button')
          .map((i, button) => `"${button.innerText.trim()}"#${button.id}`)
          .get();

        throw new Error(
          `Unrecognised GOV.UK One Login page ${pathname}: ` +
          `heading "${$main.find('h1').text().trim().replace(/\s+/g, ' ')}", ` +
          `fields [${fields.join(', ')}], buttons [${buttons.join(', ')}]. ` +
          'Add it to ONE_LOGIN_PAGES in cypress/e2e/common/onelogin_integration.js',
        );
      }));
    }
  });
}

function continueThroughOneLogin(credentials, pagesLeft) {
  cy.location().then(({ origin, pathname }) => {
    if (origin === new URL(Cypress.config('baseUrl')).origin) {
      return;
    }

    if (pagesLeft === 0) {
      throw new Error(`Still on GOV.UK One Login after ${MAX_ONE_LOGIN_PAGES} pages, at ${pathname}`);
    }

    const page = oneLoginPage(pathname);
    valueFor(page, credentials).then((value) => actOnOneLoginPage(page, value));

    // Wait for the page to change before deciding what the next one is
    cy.location('pathname').should('not.eq', pathname);
    continueThroughOneLogin(credentials, pagesLeft - 1);
  });
}

When(`I sign in through GOV.UK One Login integration with the test account`, () => {
  cy.get('[data-cy="onelogin-signin-button"]').click();
  cy.location('origin').should('eq', ONE_LOGIN_ORIGIN);

  cy.task('oneLoginIntegrationCredentials', null, { log: false }).then((credentials) => {
    continueThroughOneLogin(credentials, MAX_ONE_LOGIN_PAGES);
  });

  cy.then(() => {
    if (!(Cypress.env('a11yCheckedPages') instanceof Set)) {
      Cypress.env('a11yCheckedPages', new Set());
    }
  });
});

// The test account is never linked (the test stops here), so it lands on the start of onboarding.
Then(`I am back in Make from GOV.UK One Login`, () => {
  cy.location('origin').should('eq', new URL(Cypress.config('baseUrl')).origin);
  cy.location('pathname').should('match', /^\/(which-best-describes-you|user\/dashboard|shared-space\/dashboard)$/);
});
