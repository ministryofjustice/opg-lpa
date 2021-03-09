import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I can find {string}`, (object) => {
  cy.get("[data-cy=" + object + "]");
})

Then(`I can find old style id {string}`, (object) => {
  cy.get(object);
})

Then(`I cannot find {string}`, (object) => {
  cy.get("[data-cy=" + object + "]").should('not.exist');
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
    cy.getLpaId().then((lpaId) => { 
        let searchStr = 'a[href*="/lpa/' + lpaId + '/download/lp1/draft' + '"]'
        cy.get(searchStr)
    });
})

Then(`I can find old style id {string} with {int} options`, (object, count) => {
  cy.get(object).children().should("have.length", count);
})

Then(`I can find {string} with {int} options`, (object, count) => {
  cy.get("[data-cy=" + object + "]").children().should("have.length", count);
})

// used for dropdown list for example
Then(`I can find old style id {string} with options`, (object, dataTable) => {
  cy.get(object).children().should($foundObject => {
    var rawTable = dataTable.rawTable;
    rawTable.forEach(row => { 
        expect($foundObject).to.contain(row[0]);
        });
    })
})

Then(`I can find {string} with options`, (object, dataTable) => {
  cy.get("[data-cy=" + object + "]").children().should($foundObject => {
    var rawTable = dataTable.rawTable;
    rawTable.forEach(row => { 
        expect($foundObject).to.contain(row[0]);
        });
    })
})

