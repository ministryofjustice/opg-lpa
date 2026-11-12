const { TOTP } = require("totp-generator");

describe('Smoke test: One Login', () => {
  it('can login', { pageLoadTimeout: 30000, requestTimeout: 30000, defaultCommandTimeout: 30000 }, () => {
    cy.visit('/home')
    cy.get('h1').should('contain', 'Make a lasting power of attorney');

    if (Cypress.config().baseUrl.includes('preproduction')) {
      cy.intercept('https://signin.integration.account.gov.uk/**', (req) => {
        req.headers['Authorization'] = 'Basic ' + btoa(Cypress.env('TEST_ONELOGIN_BASIC_AUTH'));
      });

      cy.contains('a', 'Continue').click({ timeout: 30000 });
      cy.wait(10000);

      // wrap null to get a Cypress "promise", so the async library call works
      cy.wrap(null).then(() => TOTP.generate(Cypress.env('TEST_ONELOGIN_TOTP_KEY')).then(({otp}) => {
        cy.wrap(otp).as('otp');
      }));

      cy.get('@otp').then(otp => {
        cy.origin('https://signin.integration.account.gov.uk', { args: { token: otp } }, ({ token }) => {
          cy.url().should('contain', '/sign-in-or-create');

          cy.contains('Sign in').click();
          cy.get('[type=email]').invoke('val', 'makealastingpowerofattorney+smoketest@justice.gov.uk');
          cy.get('form').submit();
          cy.get('[type=password]').invoke('val', Cypress.env('TEST_ONELOGIN_PASSWORD'), { parseSpecialCharSequences: false });
          cy.get('form').submit();

          cy.get('[name=code]').invoke('val', token);
          cy.contains('button', 'Continue').click();
        });
      });

      cy.location('origin').then(currentOrigin => {
        if (currentOrigin === 'https://signin.integration.account.gov.uk') {
          cy.origin('https://signin.integration.account.gov.uk', () => {
            cy.get('body').then(($body) => {
              if ($body.text().includes('terms of use update')) {
                cy.contains('button', 'Continue').click()
              }

              if ($body.text().includes('Sign in faster with your face, fingerprint or passcode')) {
                cy.contains('button', 'Skip for now').click()
              }
            })
          });
        }

        cy.wait(10000);
      })

      cy.url().should('contain', '/make-or-add-an-lpa');
      cy.contains('Make or add LPAs');
    } else {
      cy.contains('a', 'Continue');
    }
  });
});
