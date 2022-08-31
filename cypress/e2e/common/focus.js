import { Then } from "@badeball/cypress-cucumber-preprocessor";

Then('I wait for focus on {string}', (focusable) => {
    cy.get("[data-cy=" + focusable + "]").focus();
})

Then('I am focused on {string}', (focusable) => {
    cy.focused().then((el) => {
        expect(Cypress.$(el).attr('data-cy')).to.equal(focusable);
    });
})

Then('my focus is within {string}', (dataCyReference) => {
    // check that the element which has focus is a descendant of
    // the element specified by dataCyReference
    cy.get("[data-cy=" + dataCyReference + "]").then((el) => {
        cy.focused().then((focusedEl) => {
            expect(Cypress.$(focusedEl).closest(el).length).to.eql(1);
        });
    });
})

Then('{string} is the active element', (dataCyReference) => {
    cy.document().then((doc) => {
        const activeDataCy = Cypress.$(doc.activeElement).attr('data-cy');
        expect(dataCyReference).to.equal(activeDataCy);
    });
})
