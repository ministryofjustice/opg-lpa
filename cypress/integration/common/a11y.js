const {
  After,
} = require("cypress-cucumber-preprocessor/steps");
 
// this will get called after each scenario if flag is set
After(() => {
    if (Cypress.env('RUN_A11Y_TESTS'))
    {
        cy.injectAxe();
        cy.checkA11y();
    }
});
