import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(
  'the LPA with ID {string} should display with status {string}',
  (lpaId, expectedStatus) => {
    // Define a selector which only gets the element once the JS has finished
    // updating the statuses. This is to cope with the dashboard's first fetch
    // of the statuses for LPAs happening in client-side JS.
    expectedStatus = expectedStatus.toLowerCase();
    const selector =
      'li[data-cy=lpa-' +
      lpaId +
      '] [data-cy=opg-lozenge-status-' +
      expectedStatus +
      '][data-refreshed=true]';

    cy.get(selector).then((elt) => {
      expect(elt.text().toLowerCase()).to.eql(expectedStatus);
    });
  },
);
