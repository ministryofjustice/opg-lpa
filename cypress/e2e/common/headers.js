import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(
  'I verify that the homepage response contains all the required headers',
  () => {
    cy.request('/home').then((response) => {
      expect(response.headers).to.have.property(
        'x-content-type-options',
        'nosniff',
      );
      expect(response.headers).to.have.property(
        'x-frame-options',
        'SAMEORIGIN',
      );
      expect(response.headers).to.have.property(
        'x-xss-protection',
        '1; mode=block',
      );
      expect(response.headers).to.have.property(
        'referrer-policy',
        'strict-origin-when-cross-origin',
      );
      expect(response.headers).to.have.property(
        'cache-control',
        'no-store, no-cache, must-revalidate',
      );
      expect(response.headers).to.have.property(
        'strict-transport-security',
        'max-age=3600; includeSubDomains',
      );
      var csp =
        "font-src 'self' data:; script-src 'self' *.googletagmanager.com *.google-analytics.com; " +
        "default-src 'self'; img-src 'self' *.googletagmanager.com; " +
        "connect-src 'self' *.google-analytics.com;";
      expect(response.headers).to.have.property('content-security-policy', csp);

      var xcsp = "default-src 'self'";
      expect(response.headers).to.have.property(
        'x-content-security-policy',
        xcsp,
      );
    });
  },
);
