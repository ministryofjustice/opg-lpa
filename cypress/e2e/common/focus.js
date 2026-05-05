import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then('I wait for focus on {string}', (focusable) => {
  cy.get('[data-cy=' + focusable + ']').focus();
});

Then('I am focused on {string}', (focusable) => {
  cy.focused().then((el) => {
    expect(Cypress.$(el).attr('data-cy')).to.equal(focusable);
  });
});

Then('my focus is within {string}', (dataCyReference) => {
  // check that the element which has focus is a descendant of
  // the element specified by dataCyReference
  cy.get('[data-cy=' + dataCyReference + ']').then((el) => {
    cy.focused().then((focusedEl) => {
      expect(Cypress.$(focusedEl).closest(el).length).to.eql(1);
    });
  });
});

Then('{string} is the active element', (dataCyReference) => {
  cy.focused().should('have.attr', 'data-cy', dataCyReference);
});

Then('{string} is a modal dialog', (dataCyReference) => {
  // Verify the element is a native <dialog> (not a div with ARIA bolted on)
  // and that it was opened with showModal() — indicated by the 'open' attribute
  // and the ::backdrop pseudo-element being present (only exists for modal dialogs)
  cy.get('[data-cy=' + dataCyReference + ']').should(($el) => {
    expect($el[0].tagName.toLowerCase()).to.equal('dialog');
    expect($el[0].open).to.be.true;
    expect($el[0].matches(':modal')).to.be.true;
  });
});
