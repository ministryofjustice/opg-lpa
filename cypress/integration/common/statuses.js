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

Then('I click on the view message link for LPA with ID {string}', (lpaId) => {
    cy.get('[data-cy=lpa-view-message-link-' + lpaId + ']').click();
})

Then('I am taken to the detail page for LPA with ID {string}', (lpaId) => {
    cy.url().should('contain', 'lpa/' + lpaId + '/status');
})

Then('the LPA status is shown as {string}', (expectedStatus) => {
    expectedStatus = expectedStatus.toLowerCase();

    const selector = '.progress-bar__steps--text' +
        '[data-cy=lpa-progress-' + expectedStatus + ']';

    cy.get(selector).then((elt) => {
        expect(elt.text().toLowerCase()).to.eql(expectedStatus);
    })
})

Then('the date by which the LPA should be received is shown as {string}', (expectedDate) => {
    cy.get('[data-cy=lpa-should-receive-by-date]').then((elt) => {
        expect(elt.text()).to.eql(expectedDate);
    })
})
