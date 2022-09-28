import { Then } from "@badeball/cypress-cucumber-preprocessor";

Then('I can see a "Reuse LPA details" link for the test fixture lpa', () => {
    seeReuseDetailsLink(true);
});

Then('I cannot see a "Reuse LPA details" link for the test fixture lpa', () => {
    seeReuseDetailsLink(false);
});

const seeReuseDetailsLink = (shouldExist) => {
    cy.get('@lpaId').then((lpaId) => {
        const selector = 'a[href*="/user/dashboard/create/' + lpaId + '"]';
        const condition = (shouldExist ? 'exist' : 'not.exist');
        cy.get(selector).should(condition);
    });
}

// should('be.checked')  or not checked exists here to ensure that cypress doesn't race off
// and carry out the next operation without making sure first that the check or uncheck has taken effect
Then(`I opt not to re-use details`, (checkable) => {
    cy.get("[data-cy=reuse-details--1]").check().should('be.checked');
    cy.get("[data-cy=continue]").click();
})

Then(`I opt not to re-use details if lpa is a clone`, (checkable) => {
    if (Cypress.env('clonedLpa') === true) {
      cy.get("[data-cy=reuse-details--1]").check().should('be.checked');
      cy.get("[data-cy=continue]").click();
    }
})

Then(`I can see {string} as a label in the reuse popup`, (text) => {
    cy.get("[data-cy=form-reuse-details]")
    .find("label")
    .contains(text)
    .should("have.length", 1);
})

Then(`I click the option labelled with {string} in the reuse popup`, (text) => {
    cy.get("[data-cy=form-reuse-details]")
    .find("label")
    .contains(text)
    .then((elt) => {
        // Get the ID of the radio button which is labelled
        return Cypress.$(elt).attr('for');
    })
    .then((radioId) => {
        cy.get('#' + radioId).click();
    });
})
