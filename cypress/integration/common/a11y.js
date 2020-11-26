const {
  After,
} = require("cypress-cucumber-preprocessor/steps");
 
// this will get called after each scenario
After(() => {
    cy.injectAxe();
    cy.checkA11y();
});
