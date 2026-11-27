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

function createInvite(sharedSpaceId, userEmail, inviteEmail, isAdmin = false) {
  return cy
    .request({
      method: 'POST',
      url: '/testing/cypress-fixture/shared-space-invite',
      body: { sharedSpaceId, userEmail, inviteEmail, isAdmin },
    })
    .then((response) => response.body);
}

function createExpiredInvite(sharedSpaceId, userEmail, inviteEmail) {
  return cy
    .request({
      method: 'POST',
      url: '/testing/cypress-fixture/shared-space-invite',
      body: { sharedSpaceId, userEmail, inviteEmail, created: '-8 days' },
    })
    .then((response) => response.body);
}

function createDeletionLog(email) {
  return cy.request({
    method: 'POST',
    url: '/testing/cypress-fixture/deletion-log',
    body: { email },
  });
}

function login(email, password) {
  cy.visitWithChecks('/login');

  cy.title().then((title) => {
    expect(title.toLowerCase()).to.include('sign in');
  });

  cy.get('[data-cy=login-email]').clear().type(email);
  cy.get('[data-cy=login-password]').clear().type(password);
  cy.get('[data-cy=login-submit-button]').click();
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
    ({ email, password, lpaIds, userFullName }) => {
      cy.task('log', `Created fixture user ${email} with ${lpaIds.length} LPA(s)`);

      if (sharedSpaceName) {
        if (sharedSpaceName.toLowerCase() === 'random') {
          sharedSpaceName = `Shared Space ${Math.random().toString(10).substring(2, 10)}`;
        }

        createSharedSpace(sharedSpaceName, email).then(
          ({ sharedSpaceId }) => {
            cy.wrap({ email, password, lpaIds, sharedSpaceId, sharedSpaceName, lpaCount, userFullName }).as(storedAs ?? 'fixtureUser');

            cy.task('log', `Created shared space ${sharedSpaceName} with ID ${sharedSpaceId} for fixture user ${email}`);
          },
        );
      } else {
        cy.wrap({ email, password, lpaIds, userFullName }).as(storedAs ?? 'fixtureUser');
      }
    },
  );
});

Given(/^I create a new One Login user( stored as "([^"]+)")? with (\d+) LPAs?( that belongs to a shared space called "([^"]*)")?$/, (storedAs, lpaCountString, sharedSpaceName) => {
  const lpaCount = parseInt(lpaCountString, 10);

  const loginAndOnboard = (email) => {
    cy.visit('/home');

    cy.get('body').then(($body) => {
      if ($body.find('[data-cy=sign-out]').length > 0) {
        cy.get('[data-cy=sign-out]').click();
      }
      cy.visit('/home');
    });

    cy.contains('a', 'Continue').click();

    cy.contains('label', 'Email').click();
    cy.get('input[name=email]').type(email);
    cy.get('form').submit();

    cy.contains('label', 'I will be making LPAs for other people as part of my work').click();
    cy.get('form').submit();

    cy.contains('label', 'Yes, I already have an account').click();
    cy.get('form').submit();

    cy.contains('label', 'Yes').click();
    cy.get('form').submit();

    cy.get('input[name=email]').type(email);
    cy.get('input[name=password]').type('Password1234!');
    cy.get('form').submit();
  };

  createUserWithLpas(lpaCount, 'property-and-financial').then(
    ({ userId, email, password, lpaIds, userFullName }) => {
      cy.task('log', `Created fixture user ${email} with ${lpaIds.length} LPA(s)`);

      if (sharedSpaceName) {
        if (sharedSpaceName.toLowerCase() === 'random') {
          sharedSpaceName = `Shared Space ${Math.random().toString(10).substring(2, 10)}`;
        }

        createSharedSpace(sharedSpaceName, email).then(
          ({ sharedSpaceId }) => {
            cy.wrap({ userId, email, password, lpaIds, sharedSpaceId, sharedSpaceName, lpaCount, userFullName }).as(storedAs ?? 'fixtureUser');

            loginAndOnboard(email);
            cy.task('log', `Created shared space ${sharedSpaceName} with ID ${sharedSpaceId} for fixture user ${email}`);
          },
        );
      } else {
        cy.wrap({ userId, email, password, lpaIds, userFullName }).as(storedAs ?? 'fixtureUser');
        loginAndOnboard(email);
      }
    },
  );
});

Given('a deletion log exists for {string}', (emailTemplate) => {
  const email = emailTemplate.replace('{random}', Math.random().toString(10).substring(2, 10));

  createDeletionLog(email).then(() => {
    cy.task('log', `Created deletion log for ${email}`);
    cy.wrap(email).as('deletionLogEmail');
  });
});

function inviteUserToSharedSpace(sharedSpaceName, lpaCountString, isAdmin) {
  const lpaCount = parseInt(lpaCountString, 10);

  if (sharedSpaceName.toLowerCase() === 'random') {
    sharedSpaceName = `Shared Space ${Math.random().toString(10).substring(2, 10)}`;
  }

  createUserWithLpas(lpaCount, 'property-and-financial').then(({ email, password, lpaIds, userFullName }) => {
    cy.task('log', `Created fixture user ${email} with ${lpaIds.length} LPA(s)`);
    createUserWithLpas(0, 'property-and-financial').then(({ email: spaceEmail }) => {
      cy.task('log', `Created space fixture user ${spaceEmail}`);

      createSharedSpace(sharedSpaceName, spaceEmail).then(({ sharedSpaceId }) => {
        cy.task('log', `Created shared space ${sharedSpaceName} with ID ${sharedSpaceId} for fixture user ${email}`);
        cy.wrap({ email, password, lpaIds, sharedSpaceId, sharedSpaceName, spaceEmail, userFullName }).as('fixtureUser');

        createInvite(sharedSpaceId, spaceEmail, email, isAdmin).then(({ accessCode, fullName, inviteEmail }) => {
          cy.wrap({ accessCode, fullName, inviteEmail, lpaIds }).as('invitedUser');
        });
      });
    });
  });
}

Given(/^I have been invited to a shared space called "([^"]*)" with (\d+) LPAs?$/, (sharedSpaceName, lpaCountString) => {
  inviteUserToSharedSpace(sharedSpaceName, lpaCountString, false);
});

Given(/^I have been invited as an admin to a shared space called "([^"]*)" with (\d+) LPAs?$/, (sharedSpaceName, lpaCountString) => {
  inviteUserToSharedSpace(sharedSpaceName, lpaCountString, true);
});

Given('I have sent an expired invite to {string}', (inviteEmail) => {
  cy.get('@fixtureUser').then(({ sharedSpaceId, email }) => {
    createExpiredInvite(sharedSpaceId, email, inviteEmail);
  });
});

When(`I log in as the newly created fixture user`, () => {
  cy.get('@fixtureUser').then(({ email, password }) => {
    login(email, password)
  });
});

When(/I try to log in as "([^"]+)"/, (storedAs) => {
  cy.get('[data-cy=sign-out]').click();
  cy.get(`@${storedAs}`).then(({ email, password }) => {
    login(email, password)
  });
});

When(`I (try to )log in as the member added to the shared space`, () => {
  cy.get('@addedMember').then(({ email, password }) => {
    login(email, password)
  });
});

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

Given('user {string} is a member of the shared space', (storedAs) => {
  cy.get('@fixtureUser').then(({ email: userAddingEmail, sharedSpaceId }) => {
    cy.get(`@${storedAs}`).then(({ userId }) => {
      addMemberToSharedSpace(sharedSpaceId, userId, userAddingEmail, false);
    });
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
  cy.get('@invitedUser').then(({ accessCode }) => {
    cy.contains('label', label)
      .invoke('attr', 'for')
      .then((id) => cy.get('#' + id))
      .clear({ force: true })
      .type(`  ${accessCode}  `);
  })
});

When(`I type the email of the added member into field labelled {string}`, (label) => {
  cy.get('@addedMember').then(({ email }) => {
    cy.contains('label', label)
      .invoke('attr', 'for')
      .then((id) => cy.get('#' + id))
      .clear({ force: true })
      .type(email);
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
  cy.get('[data-cy=sign-out]').click();
})

Then('I cannot see any links to manage members', () => {
  cy.contains('a', 'Fixture user').should('not.exist');
  cy.contains('a', 'Invite member').should('not.exist');
  cy.contains('a', 'Import LPAs from existing account').should('not.exist');
});

Then('I should see an expired invite for {string}', (inviteEmail) => {
  cy.contains('tr', inviteEmail).within(() => {
    cy.contains('Expired');
  });
});

Then('I should see a pending invite for {string}', (inviteEmail) => {
  cy.contains('tr', inviteEmail).within(() => {
    cy.contains('Pending');
  });
});

function lpaReference(lpaId) {
  const digits = String(lpaId).padStart(11, '0');
  return `A${digits.slice(0, 3)} ${digits.slice(3, 7)} ${digits.slice(7)}`;
}

When('I search the dashboard for the first fixture LPA by its reference', () => {
  cy.get('@fixtureUser').then(({ lpaIds }) => {
    cy.get('[data-cy=dashboard-search-box]').clear().type(lpaReference(lpaIds[0]));
    cy.get('[data-cy=dashboard-search-icon]').click();
  });
});
