import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I create PF LPA test fixture`, () => {
    cy.exec("python3 service-api/createPFLpa.py").its('stdout').then(lpaId => {
        cy.log(lpaId);
    });
})
 
Then(`I create HW LPA test fixture`, () => {
    cy.exec("python3 service-api/createHWLpa.py").its('stdout').then(lpaId => {
        cy.log(lpaId);
    });
})
