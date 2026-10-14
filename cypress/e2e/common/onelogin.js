import { Before, Then, When } from '@badeball/cypress-cucumber-preprocessor';
import { openEmailAndVisitLink } from '../../support/reset_link';
import {
  checkSignedIn,
  enterMyAddressManually,
  enterMyDetails,
  saveMyDetails,
  sayIMakeLpasFor,
  sayWhetherIHaveAnAccount,
  sayWhetherIKeepMyLpas,
} from '../../support/onboarding';

let oneLoginEnabled = null;

function detectOneLoginEnabled() {
  if (oneLoginEnabled !== null) {
    return cy.wrap(oneLoginEnabled, { log: false });
  }

  return cy.request({ url: '/login', log: false }).then((response) => {
    oneLoginEnabled = response.body.includes(
      'data-cy="onelogin-signin-button"',
    );
    return oneLoginEnabled;
  });
}

function oneLoginMockUrl() {
  return Cypress.env('oneLoginMockUrl') || 'http://localhost:4549';
}

Before({ tags: '@RequiresOneLogin' }, function () {
  detectOneLoginEnabled().then((enabled) => {
    if (!enabled) {
      cy.log('GOV.UK One Login is not enabled in this environment, skipping');
      this.skip();
    }
  });
});

Then(`I am returned to the appropriate page shown after a password reset`, () => {
  detectOneLoginEnabled().then((enabled) => {
    const expected = enabled ? '/home' : '/login';
    cy.url().should('eq', Cypress.config().baseUrl + expected);
    cy.OPGCheckA11y();
  });
});


function checkOnMockOneLoginPage() {
  cy.url().should('include', new URL(oneLoginMockUrl()).host);
  cy.contains('Continue').should('be.visible');
}

function repairA11yCheckedPagesAfterOrigin() {
  cy.then(() => {
    if (!(Cypress.env('a11yCheckedPages') instanceof Set)) {
      Cypress.env('a11yCheckedPages', new Set());
    }
  });
}

function continueThroughMockOneLogin() {
  cy.url().should('include', oneLoginMockUrl());
  cy.origin(oneLoginMockUrl(), () => {
    cy.contains('button', 'Continue').click();
    cy.wrap(null);
  });

  cy.location('origin').should('eq', new URL(Cypress.config('baseUrl')).origin);

  repairA11yCheckedPagesAfterOrigin();
}

// Runs in every environment with One Login enabled, so dev and preprod check the start of sign in
// without needing the mock. One Login expects a GET redirect to its /authorize endpoint.
Then(`starting One Login sign in redirects to the authorize endpoint`, () => {
  cy.request({ url: '/auth/onelogin', followRedirect: false }).then(
    (response) => {
      expect(response.status).to.eq(302);

      const authorizeUrl = new URL(response.redirectedToUrl);
      const params = authorizeUrl.searchParams;
      expect(authorizeUrl.pathname).to.match(/\/authorize$/);
      expect(params.get('response_type')).to.eq('code');
      expect(params.get('scope')).to.include('openid');
      expect(params.get('client_id')).to.not.be.empty;
      expect(params.get('state')).to.not.be.empty;
      expect(params.get('nonce')).to.not.be.empty;
      expect(params.get('redirect_uri')).to.match(/\/auth\/redirect$/);
    },
  );
});

Then(`I am on the mock One Login page`, checkOnMockOneLoginPage);

Then(`I continue through mock One Login`, continueThroughMockOneLogin);

When(`I sign in through mock One Login with a new Make account`, () => {
  cy.get('[data-cy="onelogin-signin-button"]').click();
  checkOnMockOneLoginPage();
  continueThroughMockOneLogin();
  sayIMakeLpasFor('myself, family or friends');
  sayWhetherIHaveAnAccount('No, I am new to this service');
  cy.url().should('eq', Cypress.config().baseUrl + '/your-details');
  enterMyDetails();
  enterMyAddressManually();
  saveMyDetails();
  checkSignedIn();
});

When(/I log in through Onelogin as the newly created fixture user/, () => {
  cy.get('@fixtureUser').then(({ email }) => {
    cy.visit('/home')
    cy.contains('Continue').click();

    cy.origin(oneLoginMockUrl(), { args: { email } }, ({ email }) => {
      cy.get('input[name="subject"][value="email"]').check();
      cy.get('#f-email').clear().type(email)
      cy.contains('Continue').click();
    });

    repairA11yCheckedPagesAfterOrigin();
  });
});

When(/I log in through Onelogin as a random user/, () => {
  cy.visit('/home')
  cy.contains('Continue').click();

  cy.origin(oneLoginMockUrl(), () => {
    cy.contains('Continue').click();
  });

  repairA11yCheckedPagesAfterOrigin();
});

When(`I sign in through mock One Login to keep my existing Make account`, () => {
  cy.get('[data-cy="onelogin-signin-button"]').click();
  checkOnMockOneLoginPage();
  continueThroughMockOneLogin();
  sayIMakeLpasFor('myself, family or friends');
  sayWhetherIHaveAnAccount('Yes, I already have an account');
  sayWhetherIKeepMyLpas('Yes');
});

Then(
  `the One Login callback shows the problem page for {string}`,
  (queryString) => {
    cy.visit('/auth/redirect' + queryString, { failOnStatusCode: false });
    cy.contains('There is a problem signing you in').should('be.visible');
    cy.contains('a', 'Return to sign in').should('have.attr', 'href', '/login');
  },
);

Then(`I am signed in with my new Make account`, checkSignedIn);

const TIMEOUT_PAGE = '/login/timeout';

function mockOneLoginLogoutPattern() {
  return oneLoginMockUrl() + '/logout*';
}

function expectOneLoginLogout(navigate, postLogoutRedirectUri) {
  cy.intercept('GET', mockOneLoginLogoutPattern()).as('oneLoginLogout');

  navigate();

  cy.wait('@oneLoginLogout')
    .its('request.url')
    .should((url) => {
      const params = new URL(url).searchParams;
      expect(params.get('id_token_hint')).to.not.be.empty;
      expect(params.get('post_logout_redirect_uri')).to.eq(
        postLogoutRedirectUri,
      );
    });
}

Then(`I sign out and am signed out of One Login`, () => {
  expectOneLoginLogout(
    () => cy.get('[data-cy=sign-out]').click(),
    Cypress.config().postLogoutUrl,
  );
});

function expectNoOneLoginLogout(navigate, expectedUrl) {
  cy.intercept('GET', mockOneLoginLogoutPattern()).as('oneLoginLogout');

  navigate();

  cy.url().should('eq', expectedUrl);
  cy.get('@oneLoginLogout.all').should('have.length', 0);
}

function returnTo(path) {
  return () => cy.window().then((win) => win.location.assign(path));
}

Then(
  `I return to {string} after timing out and am signed out of One Login`,
  (path) => {
    expectOneLoginLogout(
      returnTo(path),
      Cypress.config().baseUrl + TIMEOUT_PAGE,
    );
  },
);

Then(`I sign out without going through One Login`, () => {
  expectNoOneLoginLogout(
    () => cy.get('[data-cy=sign-out]').click(),
    Cypress.config().postLogoutUrl,
  );
});

Then(
  `I return to {string} after timing out without going through One Login`,
  (path) => {
    expectNoOneLoginLogout(
      returnTo(path),
      Cypress.config().baseUrl + TIMEOUT_PAGE,
    );
  },
);

// Mimics the page's own jQuery calls (e.g. dashboard status polling) after a timeout: they
// must get the normal timeout redirect, leaving the ID token for the next page load.
Then(
  `a background request to {string} is sent to the timeout page, not One Login`,
  (path) => {
    cy.request({
      url: path,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      followRedirect: false,
    }).then((response) => {
      expect(response.status).to.eq(302);
      expect(response.redirectedToUrl).to.eq(
        Cypress.config().baseUrl + TIMEOUT_PAGE,
      );
    });
  },
);

const ONELOGIN_LINK_ACCOUNTS = {
  'already linked': 'already_linked_email',
  'created through One Login': 'onelogin_created_email',
};

function oneLoginLinkEmail(account) {
  const envKey = ONELOGIN_LINK_ACCOUNTS[account];
  if (!envKey) {
    throw new Error(`Unknown One Login link account: ${account}`);
  }
  return Cypress.env(envKey);
}

When(`I create a new Make account`, () => {
  sayWhetherIHaveAnAccount('No, I am new to this service');
  cy.url().should('eq', Cypress.config().baseUrl + '/your-details');
  enterMyDetails();
  enterMyAddressManually();
  saveMyDetails();
});

function fillCredentialsAndSubmit(email, password) {
  cy.get('[data-cy=login-email]').clear().type(email);
  cy.get('[data-cy=login-password]').clear().type(password);
  cy.get('[data-cy=link-account-submit]').click();
}

When(`I link the newly created fixture users Make account`, () => {
  cy.get('@fixtureUser').then(({ email, password }) => {
    fillCredentialsAndSubmit(email, password);
  });
});

When(`I attempt to sign in to my existing Make account with the newly created fixture users incorrect password`, () => {
  cy.get('@fixtureUser').then(({ email }) => {
    fillCredentialsAndSubmit(email, 'this-is-the-wrong-password');
  });
});

Then(`I link the {string} Make account`, (account) => {
  cy.get('[data-cy=login-email]').clear().type(oneLoginLinkEmail(account));
  cy.get('[data-cy=login-password]').clear().type(Cypress.env('seeded_password'));
  cy.get('[data-cy=link-account-submit]').click();
});

Then(
  `I attempt to link the {string} Make account with an incorrect password`,
  (account) => {
    cy.get('[data-cy=login-email]').clear().type(oneLoginLinkEmail(account));
    cy.get('[data-cy=login-password]').clear().type('this-is-the-wrong-password');
    cy.get('[data-cy=link-account-submit]').click();
  },
);

Then(`I choose to reset my Make account password`, () => {
  cy.get('[data-cy=link-account-forgot-password]').click();
  cy.location('pathname').should('eq', '/link-account/reset-password');
});

Then(
  `I attempt to link a Make account already linked to another One Login`,
  () => {
    cy.get('[data-cy=login-email]')
      .clear()
      .type(Cypress.env('already_linked_email'));
    cy.get('[data-cy=login-password]').clear().type('any-password-here-123');
    cy.get('[data-cy=link-account-submit]').click();
  },
);

Then(`I am advised my account could not be linked`, () => {
  cy.get('[data-cy=cannot-link-heading]').should(
    'contain',
    'We could not complete your request to keep LPAs and account information',
  );
});

Then(`I choose to try again`, () => {
  cy.get('[data-cy=cannot-link-try-again]').click();
});

Then(`I choose to create a new account`, () => {
  cy.get('[data-cy=cannot-link-create-new-account]').click();
});

When(
  `I ask for a password reset link for the {string} account`,
  (account) => {
    const email = oneLoginLinkEmail(account);

    cy.visit('/forgot-password');
    cy.get('[data-cy=email]').clear().type(email);
    cy.get('[data-cy=email_confirm]').clear().type(email);
    cy.get('[data-cy=email-me-the-link]').click();
  },
);

When(`I use the password reset link for the {string} account`, (account) => {
  openEmailAndVisitLink('passwordreset', oneLoginLinkEmail(account));
});

When(
  `I attempt to sign in {int} times with an incorrect password as the {string} account`,
  (attempts, account) => {
    const email = oneLoginLinkEmail(account);

    for (let attempt = 0; attempt < attempts; attempt += 1) {
      cy.visit('/login');
      cy.get('[data-cy=login-email]').clear().type(email);
      cy.get('[data-cy=login-password]').clear().type('this-is-the-wrong-password');
      cy.get('[data-cy=login-submit-button]').click();
    }
  },
);

Then(`the {string} link goes to {string}`, (dataCy, href) => {
  cy.get(`[data-cy=${dataCy}]`).should('have.attr', 'href', href);
});

// The GOV.UK One Login header carries the sign-out link, so the service navigation must not.
Then(`the service navigation has no sign-out link`, () => {
  cy.get('[data-cy=service-nav-list]').should('exist');
  cy.get('[data-cy=service-nav-list] [data-cy=sign-out]').should('not.exist');
});
