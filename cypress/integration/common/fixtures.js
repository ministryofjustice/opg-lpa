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

// please refer to source of createLpa.py, for meaning of arguments

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
    cy.runPythonApiCommand("createLpa.py -d -a -r -cp").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider through the API with id " + lpaId);
    });
})
 
Then(`I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider`, () => {
    cy.runPythonApiCommand("createLpa.py -hw -d -a -r -cp").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider through the API with id " + lpaId);
    });
})

Then(`I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify`, () => {
    cy.runPythonApiCommand("createLpa.py -d -a -r -cp -pn").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify through the API with id " + lpaId);
    });
})
 
Then(`I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify`, () => {
    cy.runPythonApiCommand("createLpa.py -hw -d -a -r -cp -pn").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify through the API with id " + lpaId);
    });
})

Then(`I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences`, () => {
    cy.runPythonApiCommand("createLpa.py -d -a -r -cp -pn -i").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences through the API with id " + lpaId);
    });
})

Then(`I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences`, () => {
    cy.runPythonApiCommand("createLpa.py -hw -d -a -r -cp -pn -i").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences through the API with id " + lpaId);
    });
})
 
Then(`I create PF LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences`, () => {
    cy.runPythonApiCommand("createLpa.py -d -asingle -cp -pn -i").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences through the API with id " + lpaId);
    });
})
 
Then(`I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant`, () => {
    cy.runPythonApiCommand("createLpa.py -hw -d -a -r -cp -pn -i -w").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant through the API with id " + lpaId);
    });
})

Then(`I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant`, () => {
    cy.runPythonApiCommand("createLpa.py -d -a -r -cp -pn -i -w").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant through the API with id " + lpaId);
    });
})
 
Then(`I create PF LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences, applicant`, () => {
    cy.runPythonApiCommand("createLpa.py -d -asingle -cp -pn -i -w").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences, applicant through the API with id " + lpaId);
    });
})
 
Then(`I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent`, () => {
    cy.runPythonApiCommand("createLpa.py -hw -d -a -r -cp -pn -i -w -co").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent through the API with id " + lpaId);
    });
})

Then(`I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent`, () => {
    cy.runPythonApiCommand("createLpa.py -d -a -r -cp -pn -i -w -co").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent through the API with id " + lpaId);
    });
})
 
Then(`I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you`, () => {
    cy.runPythonApiCommand("createLpa.py -hw -d -a -r -cp -pn -i -w -co -y").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you through the API with id " + lpaId);
    });
})

Then(`I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you`, () => {
    cy.runPythonApiCommand("createLpa.py -d -a -r -cp -pn -i -w -co -y").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent,  who are you through the API with id " + lpaId);
    });
})
 
Then(`I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application`, () => {
    cy.runPythonApiCommand("createLpa.py -hw -d -a -r -cp -pn -i -w -co -y -ra").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application through the API with id " + lpaId);
    });
})

Then(`I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application`, () => {
    cy.runPythonApiCommand("createLpa.py -d -a -r -cp -pn -i -w -co -y -ra").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application through the API with id " + lpaId);
    });
})

Then(`I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application, fee reduction`, () => {
    cy.runPythonApiCommand("createLpa.py -hw -d -a -r -cp -pn -i -w -co -y -ra -pa").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application, fee reduction through the API with id " + lpaId);
    });
})

Then(`I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application, fee reduction`, () => {
    cy.runPythonApiCommand("createLpa.py -d -a -r -cp -pn -i -w -co -y -ra -pa").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application, fee reduction through the API with id " + lpaId);
    });
})
 
 
Then(`I create PF LPA test fixture with donor, single attorney, cert provider, instructions, preferences, applicant, correspondent, who are you, repeat application, fee reduction`, () => {
    cy.runPythonApiCommand("createLpa.py -d -asingle -cp -i -w -co -y -ra -pa").its('stdout').as('lpaId').then(lpaId => {
        cy.log("Created PF LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application, fee reduction through the API with id " + lpaId);
    });
})
 
 
