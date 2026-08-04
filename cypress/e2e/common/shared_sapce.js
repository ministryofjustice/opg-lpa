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
            body: {lpaCount, lpaType, name},
        })
        .then((response) => response.body);
}

function deleteUserFixture(email, password) {
    return cy.request({
        method: 'DELETE',
        url: '/testing/cypress-fixture/user',
        body: {email, password},
        failOnStatusCode: false,
    });
}

function createSharedSpace(sharedSpaceName, userEmail) {
  return cy
    .request({
      method: 'POST',
      url: '/testing/cypress-fixture/shared-space',
      body: {sharedSpaceName, userEmail},
    })
    .then((response) => response.body);
}

function addMemberToSharedSpace(sharedSpaceId, userToAddId, userAddingEmail, isAdmin) {
  return cy
    .request({
      method: 'POST',
      url: '/testing/cypress-fixture/shared-space-member',
      body: {sharedSpaceId, userToAddId, userAddingEmail, isAdmin},
    })
    .then((response) => response.body);
}

Before({tags: '@CleanupUserFixtures'}, () => {
    cy.wrap(null).as('fixtureUser');
});

// Not currently in use but we may have instances where we want to do some cleanup
After({tags: '@CleanupUserFixtures'}, () => {
    cy.get('@fixtureUser').then(({email, password}) => {
        if (email !== null && password !== null) {
            deleteUserFixture(email, password).then(
                (deleteResponse) => {
                    cy.task('log', `Deleting fixture user with email ${email} (and their LPAs) gave status ${deleteResponse.status}`);
                },
            );
        }
    });
});


Given(/^I create a new user with (\d+) LPAs?(?: that belongs to a shared space called "([^"]*)")?$/, (lpaCountString, sharedSpaceName) => {
    const lpaCount = parseInt(lpaCountString, 10);

    createUserWithLpas(lpaCount, 'property-and-financial').then(
        ({email, password, lpaIds}) => {
            cy.task('log', `Created fixture user ${email} with ${lpaIds.length} LPA(s)`);

            if (sharedSpaceName) {
                createSharedSpace(sharedSpaceName, email).then(
                    ({sharedSpaceId}) => {
                        cy.wrap({email, password, lpaIds, sharedSpaceId}).as('fixtureUser');

                        cy.task('log', `Created shared space ${sharedSpaceName} with ID ${sharedSpaceId} for fixture user ${email}`);
                    },
                );
            } else {
                cy.wrap({email, password, lpaIds}).as('fixtureUser');
            }
        },
    );
});

When(`I log in as the newly created fixture user`, () => {
    cy.get('@fixtureUser').then(({email, password}) => {
        cy.visitWithChecks('/login');

        cy.title().then((title) => {
            expect(title.toLowerCase()).to.include('sign in');
        });

        cy.get('[data-cy=login-email]').clear().type(email);
        cy.get('[data-cy=login-password]').clear().type(password);
        cy.get('[data-cy=login-submit-button]').click();
    });
});

Given(`the shared space has a member called {string} who is a(n) {string}`, (memberName, adminStatus) => {
  cy.get('@fixtureUser').then(({email: userAddingEmail, sharedSpaceId}) => {
    createUserWithLpas(0, '', memberName).then(
      ({email, userId}) => {
        cy.task('log', `Created fixture user ${email} with 0 LPA(s)`);

        addMemberToSharedSpace(sharedSpaceId, userId, userAddingEmail, adminStatus === 'admin').then(
          () => {
            cy.task('log', `Added member ${memberName} to shared space with ID ${sharedSpaceId} as ${adminStatus}`);
          },
        );
      },
    );
  });
});

Then(`{string} should be a(n) {string}`, (memberName, adminStatus) => {
    cy.contains('tr', memberName).within(() => {
        if (adminStatus === 'admin') {
            cy.contains('Admin').should('exist');
        } else {
            cy.contains('Admin').should('not.exist');
        }
    });
});
