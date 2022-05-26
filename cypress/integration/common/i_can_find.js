import { Then } from "cypress-cucumber-preprocessor/steps";

Then(`I can find {string}`, (object) => {
  cy.get("[data-cy=" + object + "]");
})

Then(`I can find use-my-details if lpa is new`, (object) => {
  if (Cypress.env('clonedLpa') !== true) {
      cy.get("[data-cy=use-my-details]");
  }
})

Then(`I can find {string} with data-inited`, (object) => {
  cy.get("[data-cy=" + object + "][data-inited=true]");
})

Then(`I cannot find {string}`, (object) => {
  cy.get("[data-cy=" + object + "]").should('not.exist');
})

Then(`I can find hidden {string}`, (object) => {
  cy.get("[data-cy=" + object + "]").should('be.hidden');
})

Then(`I can find {string} but it is not visible`, (object) => {
  cy.get("[data-cy=" + object + "]").then(Cypress.dom.isHidden);
})

Then(`I can find {string} and it is visible`, (object) => {
  cy.get("[data-cy=" + object + "]").then(Cypress.dom.isVisible);
})

Then(`I can find {string} wrapped with error highlighting`, (object) => {
    cy.get("div.form-group-error").within((el) => {
      cy.get("[data-cy=" + object + "]");
    })
})

Then(`I can find link pointing to {string}`, (linkAddr) => {
    let searchStr = 'a[href*="' + linkAddr + '"]'
    cy.get(searchStr)
})

Then(`I can find draft download link`, () => {
    cy.get('@lpaId').then((lpaId) => {
        let searchStr = 'a[href*="/lpa/' + lpaId + '/download/lp1/draft' + '"]'
        cy.get(searchStr)
    });
})

Then(`I can find {string} with {int} options`, (object, count) => {
  cy.get("[data-cy=" + object + "]").children().should("have.length", count);
})

// used for dropdown list for example
Then(`I can find {string} with options`, (object, dataTable) => {
  cy.get("[data-cy=" + object + "]").children().should($foundObject => {
    var rawTable = dataTable.rawTable;
    rawTable.forEach(row => {
        expect($foundObject).to.contain(row[0]);
        });
    })
})
