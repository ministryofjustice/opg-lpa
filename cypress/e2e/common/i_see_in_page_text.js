import { Then } from "@badeball/cypress-cucumber-preprocessor";

Then(`I see {string} in the page text`, (text) => {
  cy.contains(text).and('be.visible');
})

Then(`I should not see {string} in the page text`, (text) => {
  cy.contains(text).and('not.be.visible');
})

Then(`I do not see {string} in the page text`, (text) => {
  cy.contains(text).should('not.exist');
})

Then("I see in the page text", (dataTable) => {
    var rawTable = dataTable.rawTable;
    rawTable.forEach(row => {
        cy.contains(row[0]);
    });
});

Then(`I see standard test user in the page text`, () => {
  cy.contains(Cypress.env("email"));
})
