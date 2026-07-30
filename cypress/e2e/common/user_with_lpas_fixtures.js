const {
  Given,
  When,
  Before,
  After,
} = require('@badeball/cypress-cucumber-preprocessor');

function createUserWithLpas(lpaCount, lpaType) {
  return cy
    .request({
      method: 'POST',
      url: '/testing/cypress-fixture',
      body: { lpaCount, lpaType },
    })
    .then((response) => response.body);
}

function deleteUserFixture(email, password) {
  return cy.request({
    method: 'DELETE',
    url: '/testing/cypress-fixture',
    body: { email, password },
    failOnStatusCode: false,
  });
}

Before({ tags: '@CleanupUserFixtures' }, () => {
  cy.wrap(null).as('fixtureUserEmail');
  cy.wrap(null).as('fixtureUserPassword');
});

After({ tags: '@CleanupUserFixtures' }, () => {
  cy.log('cleaning up user with LPAs fixture');
  cy.get('@fixtureUserEmail').then((fixtureUserEmail) => {
    if (fixtureUserEmail !== null) {
      cy.get('@fixtureUserPassword').then((fixtureUserPassword) => {
        deleteUserFixture(fixtureUserEmail, fixtureUserPassword).then(
          (deleteResponse) => {
            cy.log(
              'Deleting fixture user with email ' +
                fixtureUserEmail +
                ' (and their LPAs) gave status ' +
                deleteResponse.status,
            );
          },
        );
      });
    }
  });
});

Given(`I create a new user with {int} LPAs`, (lpaCount) => {
  createUserWithLpas(lpaCount, 'property-and-financial').then(
    ({ email, password, lpaIds }) => {
      cy.wrap(email).as('fixtureUserEmail');
      cy.wrap(password).as('fixtureUserPassword');
      cy.wrap(lpaIds).as('fixtureUserLpaIds');

      cy.log(
        'Created fixture user ' +
          email +
          ' with ' +
          lpaIds.length +
          ' LPA(s)',
      );
    },
  );
});

When(`I log in as the newly created fixture user`, () => {
  cy.get('@fixtureUserEmail').then((fixtureUserEmail) => {
    cy.get('@fixtureUserPassword').then((fixtureUserPassword) => {
      cy.visitWithChecks('/login');

      cy.title().then((title) => {
        expect(title.toLowerCase()).to.include('sign in');
      });

      cy.get('[data-cy=login-email]').clear().type(fixtureUserEmail);
      cy.get('[data-cy=login-password]').clear().type(fixtureUserPassword);
      cy.get('[data-cy=login-submit-button]').click();
    });
  });
});
