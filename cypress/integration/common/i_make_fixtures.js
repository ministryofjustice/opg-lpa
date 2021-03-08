import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I create PF LPA test fixture`, () => {
    // check wehther testFixtureLpaId is set, if not, create the fixture
    // TODO  use as, to set testFixtureLpaId here
    cy.exec("python3 service-api/createPFLpa.py").its('stdout').then(lpaId => {
        cy.log(lpaId);
    });
})
 
Then(`I create HW LPA test fixture`, () => {
    cy.exec("python3 service-api/createHWLpa.py").its('stdout').then(lpaId => {
        cy.log(lpaId);
    });
})

Then(`I create untyped LPA test fixture`, () => {
    cy.exec("python3 service-api/createHWLpa.py").its('stdout').then(lpaId => {
        cy.log(lpaId);
    });
})
