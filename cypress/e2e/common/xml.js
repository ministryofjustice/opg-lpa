import { Then, When } from '@badeball/cypress-cucumber-preprocessor';

When(
  'I visit the {string} XML endpoint and save the response as {string}',
  (path, key) => {
    cy.task('deleteValue', key);
    // failOnStatusCode turned on here for ping.feature because healthcheck will fail in Cypress
    // but we only want to check it's a valid XML response
    cy.request({ url: path, failOnStatusCode: false }).then((response) => {
      cy.task('putValue', { name: key, value: response });
    });
  },
);

Then('I should have a valid XML response saved as {string}', (key) => {
  // this can only run after "I visit the {string} XML endpoint..."
  cy.task('getValue', key).then((response) => {
    expect(response.body).to.not.be.null;
    expect(response.headers['content-type']).to.contain('text/xml');
  });
});
