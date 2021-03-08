const {
  Then, After,
} = require("cypress-cucumber-preprocessor/steps");

// Clean up lpa test fixture used by the scenario
After({ tags: "@CleanupFixtures" }, () => {
    cy.log("hit it");
    cy.exec("python3 service-api/deleteLpa.py").its('stdout').then(response => {
        cy.log(response);
    });
});

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
