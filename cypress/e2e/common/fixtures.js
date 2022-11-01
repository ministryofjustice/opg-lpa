const {
  Then,
  After,
  Before,
} = require('@badeball/cypress-cucumber-preprocessor');

// For tagged Scenarios, clean up lpa test fixture used by the scenario
// but only if we're not running under CI. In CI we leave it intact for
// the next scenario, to simulate the user journey
Before({ tags: '@CleanupFixtures' }, () => {
  if (!Cypress.env('CI')) {
    cy.wrap(null).as('lpaId');
    cy.wrap(null).as('signedUpUserEmail');
  }
});

After({ tags: '@CleanupFixtures' }, () => {
  cy.log('cleaning up fixtures');
  if (!Cypress.env('CI')) {
    cy.get('@lpaId').then((lpaId) => {
      if (lpaId !== null) {
        cy.runPythonApiCommand('deleteLpa.py -i ' + lpaId)
          .its('stdout')
          .then((deleteResult) => {
            cy.log(
              'Deleting test fixture lpa with id ' +
                lpaId +
                ' gave result ' +
                deleteResult,
            );
          });
      }
    });

    cy.get('@signedUpUserEmail').then((signedUpUserEmail) => {
      if (signedUpUserEmail !== null) {
        cy.runPythonApiCommand(
          'manageUsers.py deleteIfExists ' +
            signedUpUserEmail +
            ' ' +
            Cypress.env('seeded_password'),
        )
          .its('stdout')
          .then((deleteResult) => {
            cy.log(
              'Deleting user with ID ' +
                signedUpUserEmail +
                ' gave result ' +
                deleteResult,
            );
          });
      }
    });
  }
});

// please refer to source of createLpa.py, for meaning of arguments

Then(`I create PF LPA test fixture`, () => {
  cy.runPythonApiCommand('createLpa.py')
    .its('stdout')
    .as('lpaId')
    .then((lpaId) => {
      cy.log('Created PF LPA test fixture through the API with id ' + lpaId);
    });
});

Then(`I create HW LPA test fixture`, () => {
  cy.runPythonApiCommand('createLpa.py -hw')
    .its('stdout')
    .as('lpaId')
    .then((lpaId) => {
      cy.log('Created HW LPA test fixture through the API with id ' + lpaId);
    });
});

Then(`I create PF LPA test fixture with a donor`, () => {
  cy.runPythonApiCommand('createLpa.py -d')
    .its('stdout')
    .as('lpaId')
    .then((lpaId) => {
      cy.log(
        'Created PF LPA test fixture with donor through the API with id ' +
          lpaId,
      );
    });
});

Then(`I create HW LPA test fixture with a donor`, () => {
  cy.runPythonApiCommand('createLpa.py -hw -d')
    .its('stdout')
    .as('lpaId')
    .then((lpaId) => {
      cy.log(
        'Created HW LPA test fixture with donor through the API with id ' +
          lpaId,
      );
    });
});

Then(`I create PF LPA test fixture with a donor and attorneys`, () => {
  cy.runPythonApiCommand('createLpa.py -d -a')
    .its('stdout')
    .as('lpaId')
    .then((lpaId) => {
      cy.log(
        'Created PF LPA test fixture with donor and attorneys through the API with id ' +
          lpaId,
      );
    });
});

Then(`I create HW LPA test fixture with a donor and attorneys`, () => {
  cy.runPythonApiCommand('createLpa.py -hw -d -a')
    .its('stdout')
    .as('lpaId')
    .then((lpaId) => {
      cy.log(
        'Created HW LPA test fixture with donor and attorneys through the API with id ' +
          lpaId,
      );
    });
});

Then(
  `I create PF LPA test fixture with a donor, attorneys and replacement attorneys`,
  () => {
    cy.runPythonApiCommand('createLpa.py -d -a -r')
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created PF LPA test fixture with donor, attorneys, replacement attorneys through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create HW LPA test fixture with a donor, attorneys and replacement attorneys`,
  () => {
    cy.runPythonApiCommand('createLpa.py -hw -d -a -r')
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created HW LPA test fixture with donor, attorneys, replacement attorneys through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider`,
  () => {
    cy.runPythonApiCommand('createLpa.py -d -a -r -cp')
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider`,
  () => {
    cy.runPythonApiCommand('createLpa.py -hw -d -a -r -cp')
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify`,
  () => {
    cy.runPythonApiCommand('createLpa.py -d -a -r -cp -pn')
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify`,
  () => {
    cy.runPythonApiCommand('createLpa.py -hw -d -a -r -cp -pn')
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences`,
  () => {
    cy.runPythonApiCommand('createLpa.py -d -a -r -cp -pn -i')
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences`,
  () => {
    cy.runPythonApiCommand('createLpa.py -hw -d -a -r -cp -pn -i')
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create PF LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences`,
  () => {
    cy.runPythonApiCommand('createLpa.py -d -asingle -cp -pn -i')
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created HW LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant`,
  () => {
    cy.runPythonApiCommand('createLpa.py -hw -d -a -r -cp -pn -i -w donor')
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant`,
  () => {
    cy.runPythonApiCommand('createLpa.py -d -a -r -cp -pn -i -w donor')
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create PF LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences, applicant`,
  () => {
    cy.runPythonApiCommand('createLpa.py -d -asingle -cp -pn -i -w donor')
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created PF LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences, applicant through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent`,
  () => {
    cy.runPythonApiCommand('createLpa.py -hw -d -a -r -cp -pn -i -w donor -co')
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent`,
  () => {
    cy.runPythonApiCommand('createLpa.py -d -a -r -cp -pn -i -w donor -co')
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you`,
  () => {
    cy.runPythonApiCommand(
      'createLpa.py -hw -d -a -r -cp -pn -i -w donor -co -y',
    )
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you`,
  () => {
    cy.runPythonApiCommand('createLpa.py -d -a -r -cp -pn -i -w donor -co -y')
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent,  who are you through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application`,
  () => {
    cy.runPythonApiCommand(
      'createLpa.py -hw -d -a -r -cp -pn -i -w donor -co -y -ra true',
    )
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application`,
  () => {
    cy.runPythonApiCommand(
      'createLpa.py -d -a -r -cp -pn -i -w donor -co -y -ra true',
    )
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application, fee reduction`,
  () => {
    cy.runPythonApiCommand(
      'createLpa.py -hw -d -a -r -cp -pn -i -w donor -co -y -ra true -pa',
    )
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application, fee reduction through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application, fee reduction`,
  () => {
    cy.runPythonApiCommand(
      'createLpa.py -d -a -r -cp -pn -i -w donor -co -y -ra true -pa',
    )
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application, fee reduction through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create PF LPA test fixture with donor, single attorney, cert provider, instructions, preferences, applicant, correspondent, who are you, repeat application, fee reduction`,
  () => {
    cy.runPythonApiCommand(
      'createLpa.py -d -asingle -cp -i -w donor -co -y -ra true -pa',
    )
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created PF LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application, fee reduction through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(
  `I create PF LPA test fixture with donor, single attorney, cert provider, instructions, preferences, applicant, correspondent, who are you as first primary attorney, not repeat application, fee reduction`,
  () => {
    cy.runPythonApiCommand(
      'createLpa.py -d -asingle -cp -i -w 1 -co -y -ra false -pa',
    )
      .its('stdout')
      .as('lpaId')
      .then((lpaId) => {
        cy.log(
          'Created PF LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you as first primary attorney, not repeat application, fee reduction through the API with id ' +
            lpaId,
        );
      });
  },
);

Then(`an existing user has the email {string}`, (email) => {
  cy.runPythonApiCommand(
    'manageUsers.py getOrCreate ' +
      email +
      ' ' +
      Cypress.env('seeded_password'),
  )
    .its('stdout')
    .then((getUserResult) => {
      const getUserResultObj = JSON.parse(getUserResult);
      if (getUserResultObj.success) {
        cy.wrap(email).as('signedUpUserEmail');
        cy.log('Got user with email ' + email);
      } else {
        cy.log('Unable to get or create user with email ' + email);
      }
    });
});
