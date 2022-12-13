import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then('the forbidden filename patterns', (dataTable) => {
  cy.wrap(dataTable.rawTable[0]).as('forbiddenFilePatterns');
});

Then(
  'I attempt to fetch a resource matching each forbidden filename pattern',
  () => {
    let aliases = [];

    cy.get('@forbiddenFilePatterns').each((forbiddenFilePattern) => {
      aliases.push('@' + forbiddenFilePattern);
      cy.request({
        url: '/foo.' + forbiddenFilePattern,
        failOnStatusCode: false,
      }).as(forbiddenFilePattern);
    });

    cy.wrap(aliases).as('forbiddenFileResponses');
  },
);

Then('I get a 403 response code for each resource', () => {
  cy.get('@forbiddenFileResponses').each((forbiddenFileResponse) => {
    cy.get(forbiddenFileResponse).then((response) => {
      expect(response.status).to.eql(403);
    });
  });
});
