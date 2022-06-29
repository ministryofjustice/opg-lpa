import { Given } from "cypress-cucumber-preprocessor/steps";

Then(`I can find feedback buttons`, () => {

    cy.get('[data-cy=rating-very-satisfied]');
    cy.get('[data-cy=rating-satisfied]');
    cy.get('[data-cy=rating-neither-satisfied-or-dissatisfied]');
    cy.get('[data-cy=rating-dissatisfied]');
    cy.get('[data-cy=rating-very-dissatisfied]');

    cy.get('[data-cy=feedback-textarea]');
    cy.get('[data-cy=feedback-email]');
    cy.get('[data-cy=feedback-phone]');
})

Then(`I select satisfied`, () => {
    cy.get('[data-cy=rating-satisfied]').click();
})

Then(`I select neither satisfied or dissatisfied`, () => {
    cy.get('[data-cy=rating-neither-satisfied-or-dissatisfied]').click();
})

Then(`I set feedback content as {string}`, (email) => {
    cy.get('[data-cy=feedback-textarea]').type(email);
})

Then(`I set feedback email as {string}`, (email) => {
    cy.get('[data-cy=feedback-email]').type(email);
})

Then(`I submit the feedback`, () => {
    cy.get('[data-cy=feedback-submit-button]').click();
    cy.OPGCheckA11y();
})
