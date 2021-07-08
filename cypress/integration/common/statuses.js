import { Then } from "cypress-cucumber-preprocessor/steps";

Then('the LPA with ID {string} should display with status {string}', (lpaId, expectedStatus) => {
    // Define a selector which only gets the element once the JS has finished
    // updating the statuses. This is to cope with the dashboard's first fetch
    // of the statuses for LPAs happening in client-side JS.
    expectedStatus = expectedStatus.toLowerCase();
    const selector = '.opg-lozenge-status--' + expectedStatus +
        '[data-cy=lpa-status-lozenge-' + lpaId + ']';

    cy.get(selector).then((elt) => {
        expect(elt.text().toLowerCase()).to.eql(expectedStatus);
    })
})

Then('I click on the View {string} message link for LPA with ID {string}', (status, lpaId) => {
    // NB check for data-journey-click to ensure we are getting the link after its
    // status has been updated from Sirius
    cy.get('[data-journey-click="page:link:View ' + status + ' message"]' +
        '[data-cy=lpa-view-message-link-' + lpaId + ']').click();
})

Then('I am taken to the detail page for LPA with ID {string}', (lpaId) => {
    cy.url().should('contain', 'lpa/' + lpaId + '/status');
})

Then('the LPA status is shown as {string}', (expectedStatus) => {
    expectedStatus = expectedStatus.toLowerCase();

    const selector = 'li[data-cy=lpa-progress-' + expectedStatus + '] > [data-cy=lpa-progress-text]';

    cy.get(selector).then((elt) => {
        expect(elt.text().toLowerCase()).to.eql(expectedStatus);
    })
})

Then('the date by which the LPA should be received is shown as {string}', (expectedDate) => {
    cy.get('[data-cy=lpa-should-receive-by-date]').then((elt) => {
        expect(elt.text()).to.eql(expectedDate);
    })
})
