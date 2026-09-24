import { Then, When } from '@badeball/cypress-cucumber-preprocessor';

function postForm(path, body) {
  return cy.request({
    method: 'POST',
    url: path,
    form: true,
    body,
    followRedirect: false,
    failOnStatusCode: false,
  });
}

When(`I post to {string} without a CSRF token`, (path) => {
  cy.wrap(path).as('csrfPath');
  postForm(path, { email: 'someone@example.com', password: 'Pass12345678' }).as( // pragma: allowlist secret
    'csrfResponse',
  );
});

When(`I post to {string} with an invalid CSRF token`, (path) => {
  cy.wrap(path).as('csrfPath');
  postForm(path, {
    __csrf: 'not-the-token-we-issued',
    email: 'someone@example.com',
    password: 'Pass12345678', //pragma: allowlist secret
  }).as('csrfResponse');
});

Then(`the request is rejected as a CSRF failure`, () => {
  cy.get('@csrfPath').then((path) => {
    cy.get('@csrfResponse').then((response) => {
      expect(response.status).to.eq(302);
      expect(response.headers.location).to.contain(path);
    });

    cy.visit(path);
    cy.get('body').should('contain', 'Invalid CSRF token');
  });
});

Then(`the page carries a CSRF token`, () => {
  cy.get('input[name="__csrf"]')
    .should('exist')
    .invoke('val')
    .should('not.be.empty')
    .as('issuedCsrfToken');
});

Then(`signing in with that token is not rejected as a CSRF failure`, () => {
  cy.get('@issuedCsrfToken').then((token) => {
    postForm('/login', {
      __csrf: token,
      email: 'nobody@example.com',
      password: 'DeliberatelyWrong123', //pragma: allowlist secret
    }).then((response) => {
      expect(response.status).to.eq(200);
      expect(response.body).not.to.contain('Invalid CSRF token');
    });
  });
});
