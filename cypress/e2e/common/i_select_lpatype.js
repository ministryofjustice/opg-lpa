import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then('I choose Property and Finance', () => {
  cy.get('[data-cy=type-property-and-financial]')
    .should('not.be.disabled')
    .check()
    .should('be.checked');
  cy.get('body').then(($body) => {
    if (
      $body.find('input[name="isRepeatApplication"][value="is-repeat"]').length
    ) {
      cy.get('input[name="isRepeatApplication"][value="is-repeat"]').check({
        force: true,
      });
      cy.get('#conditional-repeat-application')
        .should('exist')
        .and('not.have.class', 'js-hidden')
        .and('not.have.css', 'display', 'none');
    }

    if (
      $body.find(
        '[data-target="reduced-fee-low-income"], [aria-controls="reduced-fee-low-income"], [data-aria-controls="reduced-fee-low-income"]',
      ).length
    ) {
      cy.get(
        '[data-target="reduced-fee-low-income"], [aria-controls="reduced-fee-low-income"], [data-aria-controls="reduced-fee-low-income"]',
      )
        .first()
        .scrollIntoView()
        .click({ force: true });
      cy.get('#reduced-fee-low-income')
        .should('exist')
        .and('not.have.class', 'js-hidden')
        .and('not.have.css', 'display', 'none');
    }
  });
});

Then('I choose Health and Welfare', () => {
  cy.get('[data-cy=type-health-and-welfare]')
    .should('not.be.disabled')
    .check()
    .should('be.checked');
  cy.get('body').then(($body) => {
    if (
      $body.find('input[name="isRepeatApplication"][value="is-repeat"]').length
    ) {
      cy.get('input[name="isRepeatApplication"][value="is-repeat"]').check({
        force: true,
      });
      cy.get('#conditional-repeat-application-details')
        .should('exist')
        .and('not.have.class', 'js-hidden')
        .and('not.have.css', 'display', 'none');
    }
    if (
      $body.find(
        '[data-target="reduced-fee-low-income"], [aria-controls="reduced-fee-low-income"], [data-aria-controls="reduced-fee-low-income"]',
      ).length
    ) {
      cy.get(
        '[data-target="reduced-fee-low-income"], [aria-controls="reduced-fee-low-income"], [data-aria-controls="reduced-fee-low-income"]',
      )
        .first()
        .scrollIntoView()
        .click({ force: true });
      cy.get('#reduced-fee-low-income')
        .should('exist')
        .and('not.have.class', 'js-hidden')
        .and('not.have.css', 'display', 'none');
    }
  });
});
