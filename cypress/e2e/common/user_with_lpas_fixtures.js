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
            body: {lpaCount, lpaType},
        })
        .then((response) => response.body);
}

function deleteUserFixture(email, password) {
    return cy.request({
        method: 'DELETE',
        url: '/testing/cypress-fixture',
        body: {email, password},
        failOnStatusCode: false,
    });
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
                    cy.task(
                        'log',
                        'Deleting fixture user with email ' +
                        email +
                        ' (and their LPAs) gave status ' +
                        deleteResponse.status,
                    );
                },
            );
        }
    });
});


Given(`I create a new user with {int} LPAs`, (lpaCount) => {
    createUserWithLpas(lpaCount, 'property-and-financial').then(
        ({email, password, lpaIds}) => {
            cy.wrap({email, password, lpaIds}).as('fixtureUser');

            cy.task(
                'log',
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
