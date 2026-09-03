const {
  Given,
  When,
  Then,
  Before,
  After,
} = require('@badeball/cypress-cucumber-preprocessor');

function createUserWithLpas(lpaCount, lpaType, name = '') {
  return cy
    .request({
      method: 'POST',
      url: '/testing/cypress-fixture/user',
      body: { lpaCount, lpaType, name },
    })
    .then((response) => response.body);
}

function deleteUserFixture(email, password) {
  return cy.request({
    method: 'DELETE',
    url: '/testing/cypress-fixture/user',
    body: { email, password },
    failOnStatusCode: false,
  });
}

function createSharedSpace(sharedSpaceName, userEmail) {
  return cy
    .request({
      method: 'POST',
      url: '/testing/cypress-fixture/shared-space',
      body: { sharedSpaceName, userEmail },
    })
    .then((response) => response.body);
}

function addMemberToSharedSpace(sharedSpaceId, userToAddId, userAddingEmail, isAdmin) {
  return cy
    .request({
      method: 'POST',
      url: '/testing/cypress-fixture/shared-space-member',
      body: { sharedSpaceId, userToAddId, userAddingEmail, isAdmin },
    })
    .then((response) => response.body);
}

function createInvite(sharedSpaceId, userEmail) {
  return cy
    .request({
      method: 'POST',
      url: '/testing/cypress-fixture/shared-space-invite',
      body: { sharedSpaceId, userEmail },
    })
    .then((response) => response.body);
}

Before({ tags: '@CleanupUserFixtures' }, () => {
  cy.wrap(null).as('fixtureUser');
});

// Not currently in use but we may have instances where we want to do some cleanup
After({ tags: '@CleanupUserFixtures' }, () => {
  cy.get('@fixtureUser').then(({ email, password }) => {
    if (email !== null && password !== null) {
      deleteUserFixture(email, password).then(
        (deleteResponse) => {
          cy.task('log', `Deleting fixture user with email ${email} (and their LPAs) gave status ${deleteResponse.status}`);
        },
      );
    }
  });
});


Given(/^I create a new user( stored as "([^"]+)")? with (\d+) LPAs?(?: that belongs to a shared space called "([^"]*)")?$/, (storedAs, lpaCountString, sharedSpaceName) => {
  const lpaCount = parseInt(lpaCountString, 10);

  createUserWithLpas(lpaCount, 'property-and-financial').then(
    ({ email, password, lpaIds }) => {
      cy.task('log', `Created fixture user ${email} with ${lpaIds.length} LPA(s)`);

      if (sharedSpaceName) {
        createSharedSpace(sharedSpaceName, email).then(
          ({ sharedSpaceId }) => {
            cy.wrap({ email, password, lpaIds, sharedSpaceId }).as(storedAs ?? 'fixtureUser');

            cy.task('log', `Created shared space ${sharedSpaceName} with ID ${sharedSpaceId} for fixture user ${email}`);
          },
        );
      } else {
        cy.wrap({ email, password, lpaIds }).as(storedAs ?? 'fixtureUser');
      }
    },
  );
});

Given(/^I have been invited to a shared space called "([^"]*)" with (\d+) LPAs?$/, (sharedSpaceName, lpaCountString) => {
  const lpaCount = parseInt(lpaCountString, 10);

  createUserWithLpas(lpaCount, 'property-and-financial').then(({ email, password, lpaIds }) => {
    cy.task('log', `Created fixture user ${email} with ${lpaIds.length} LPA(s)`);
    createUserWithLpas(0, 'property-and-financial').then(({ email: spaceEmail }) => {
      cy.task('log', `Created space fixture user ${spaceEmail}`);

      createSharedSpace(sharedSpaceName, spaceEmail).then(({ sharedSpaceId }) => {
        cy.task('log', `Created shared space ${sharedSpaceName} with ID ${sharedSpaceId} for fixture user ${email}`);

        createInvite(sharedSpaceId, spaceEmail).then(({ accessCode }) => {
          cy.wrap({ email, password, lpaIds, sharedSpaceId, accessCode }).as('fixtureUser');
        });
      });
    });
  });
});

When(`I log in as the newly created fixture user`, () => {
  cy.get('@fixtureUser').then(({ email, password }) => {
    login(email, password)
  });
});

When(/I try to log in as "([^"]+)"/, (storedAs) => {
  cy.contains('Sign Out').click();
  cy.get(`@${storedAs}`).then(({ email, password }) => {
    login(email, password)
  });
});

When(`I (try to )log in as the member added to the shared space`, () => {
  cy.get('@addedMember').then(({ email, password }) => {
    login(email, password)
  });
});

function login(email, password) {
  cy.visitWithChecks('/login');

  cy.title().then((title) => {
    expect(title.toLowerCase()).to.include('sign in');
  });

  cy.get('[data-cy=login-email]').clear().type(email);
  cy.get('[data-cy=login-password]').clear().type(password);
  cy.get('[data-cy=login-submit-button]').click();
}

Then(`I should not be logged in`, () => {
  cy.url().should('include', Cypress.config().baseUrl + '/login');
});

Then(`I see a suspended account error`, () => {
  cy.contains('This user account has been suspended').should('exist');
});

Given(/the shared space has a member called "([^"]+)" who is an? "(admin|member)"(?: with (\d+) LPAs?)?/, (memberName, adminStatus, lpaCountString) => {
  const lpaCount = parseInt(lpaCountString, 10);

  cy.get('@fixtureUser').then(({ email: userAddingEmail, sharedSpaceId }) => {
    createUserWithLpas(lpaCount, 'property-and-financial', memberName).then(
      ({ email, password, userId }) => {
        cy.task('log', `Created fixture user ${email} with ${lpaCount} LPA(s)`);

        addMemberToSharedSpace(sharedSpaceId, userId, userAddingEmail, adminStatus === 'admin').then(
          () => {
            cy.task('log', `Added member ${memberName} to shared space with ID ${sharedSpaceId} as ${adminStatus}`);
            cy.wrap({ email, password }).as('addedMember');
          },
        );
      },
    );
  });
});

Then(`{string} permissions should be set to {string}`, (memberName, adminStatus) => {
  cy.contains('tr', memberName).within(() => {
    if (adminStatus.toLowerCase() === 'admin') {
      cy.contains('Admin').should('exist');
    } else {
      cy.contains('Admin').should('not.exist');
    }
  });
});

Then(`{string} status should be {string}`, (memberName, activeStatus) => {
  cy.contains('tr', memberName).within(() => {
    if (activeStatus.toLowerCase() === 'active') {
      cy.contains('Active').should('exist');
    } else {
      cy.contains('Suspended').should('exist');
    }
  });
});

When(`I type the access code into field labelled {string}`, (label) => {
  cy.get('@fixtureUser').then(({ accessCode }) => {
    cy.contains('label', label)
      .invoke('attr', 'for')
      .then((id) => cy.get('#' + id))
      .clear({ force: true })
      .type(accessCode);
  })
});

Then('I cannot see any invites', () => {
  cy.contains('table', 'Invited members').should('not.exist');
});

When(/I enter the login details of "([^"]+)"/, (storedAs) => {
  cy.get(`@${storedAs}`).then(({ email, password }) => {
    cy.get('#email').type(email);
    cy.get('#password').type(password);
  });
});

When(/I enter the email of "([^"]+)"/, (storedAs) => {
  cy.get(`@${storedAs}`).then(({ email }) => {
    cy.get('#email').type(email);
    cy.get('#email_confirm').type(email);
  });
});

Then('I log out', () => {
  cy.contains('Sign Out').click();
})

Then('I cannot see any links to manage members', () => {
  cy.contains('a', 'Fixture user').should('not.exist');
  cy.contains('a', 'Invite member').should('not.exist');
  cy.contains('a', 'Import LPAs from existing account').should('not.exist');
});
