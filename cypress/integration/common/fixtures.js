const {
  Then, After,
} = require("cypress-cucumber-preprocessor/steps");

// For tagged Scenarios, clean up lpa test fixture used by the scenario
// but only if we're not running under CI. In CI we leave it intact for 
// the next scenario, to simulate the user journey
After({ tags: "@CleanupFixtures" }, () => {
    if (!Cypress.env('CI')) {
        cy.get('@lpaId').then((lpaId) => {
            cy.runPythonApiCommand("deleteLpa.py -i " + lpaId).its('stdout').then(deleteResult => {
                cy.log("Deleting test fixture lpa with id " + lpaId + " gave result " + deleteResult);
            });
        });
    }
});

Then(`I create PF LPA test fixture`, () => {
    cy.runPythonApiCommand("createPFLpa.py").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture through the API with id " + lpaId);
    });
})
 
Then(`I create HW LPA test fixture`, () => {
    cy.runPythonApiCommand("createHWLpa.py").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture through the API with id " + lpaId);
    });
})

Then(`I create PF LPA test fixture with a donor`, () => {
    cy.runPythonApiCommand("createPFLpaWithDonor.py").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor through the API with id " + lpaId);
    });
})
 
Then(`I create HW LPA test fixture with a donor`, () => {
    cy.runPythonApiCommand("createHWLpaWithDonor.py").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor through the API with id " + lpaId);
    });
})
