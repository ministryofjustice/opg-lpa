import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`I continue through mock One Login`, () => {
    cy.origin('http://localhost:4549', () => {
        cy.contains('Continue').click();
    });
});

Then(
    `the One Login callback shows the problem page for {string}`,
    (queryString) => {
        cy.visit('/auth/redirect' + queryString, { failOnStatusCode: false });
        cy.contains('There is a problem signing you in').should('be.visible');
        cy.contains('a', 'Return to sign in').should('have.attr', 'href', '/login');
    },
);
