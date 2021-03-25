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
    cy.runPythonApiCommand("createLpa.py").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture through the API with id " + lpaId);
    });
})
 
Then(`I create HW LPA test fixture`, () => {
    cy.runPythonApiCommand("createLpa.py -hw").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture through the API with id " + lpaId);
    });
})

Then(`I create PF LPA test fixture with a donor`, () => {
    cy.runPythonApiCommand("createLpa.py -d").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor through the API with id " + lpaId);
    });
})
 
Then(`I create HW LPA test fixture with a donor`, () => {
    cy.runPythonApiCommand("createLpa.py -hw -d").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor through the API with id " + lpaId);
    });
})

Then(`I create PF LPA test fixture with a donor and attorneys`, () => {
    cy.runPythonApiCommand("createLpa.py -d -a").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor and attorneys through the API with id " + lpaId);
    });
})
 
Then(`I create HW LPA test fixture with a donor and attorneys`, () => {
    cy.runPythonApiCommand("createLpa.py -hw -d -a").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor and attorneys through the API with id " + lpaId);
    });
})

Then(`I create PF LPA test fixture with a donor, attorneys and replacement attorneys`, () => {
    cy.runPythonApiCommand("createLpa.py -d -a -r").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor, attorneys, replacement attorneys through the API with id " + lpaId);
    });
})

Then(`I create HW LPA test fixture with a donor, attorneys and replacement attorneys`, () => {
    cy.runPythonApiCommand("createLpa.py -hw -d -a -r").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor, attorneys, replacement attorneys through the API with id " + lpaId);
    });
})

Then(`I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider`, () => {
    cy.runPythonApiCommand("createLpa.py -d -a -r -c").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider through the API with id " + lpaId);
    });
})
 
Then(`I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider`, () => {
    cy.runPythonApiCommand("createLpa.py -hw -d -a -r -c").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider through the API with id " + lpaId);
    });
})

Then(`I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify`, () => {
    cy.runPythonApiCommand("createLpa.py -d -a -r -c -n").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify through the API with id " + lpaId);
    });
})
 
Then(`I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify`, () => {
    cy.runPythonApiCommand("createLpa.py -hw -d -a -r -c -n").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify through the API with id " + lpaId);
    });
})

Then(`I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions`, () => {
    cy.runPythonApiCommand("createLpa.py -d -a -r -c -n -i").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions through the API with id " + lpaId);
    });
})
 
Then(`I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions`, () => {
    cy.runPythonApiCommand("createLpa.py -hw -d -a -r -c -n -i").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions through the API with id " + lpaId);
    });
})

Then(`I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preference`, () => {
    cy.runPythonApiCommand("createLpa.py -d -a -r -c -n -i -p").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preference through the API with id " + lpaId);
    });
})
 
Then(`I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preference`, () => {
    cy.runPythonApiCommand("createLpa.py -hw -d -a -r -c -n -i -p").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preference through the API with id " + lpaId);
    });
})
