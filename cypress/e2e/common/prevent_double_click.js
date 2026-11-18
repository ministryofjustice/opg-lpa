import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`I double click {string} and the second click is prevented`, (dataCy) => {
  cy.get(`[data-cy="${dataCy}"]`)
    .should('not.be.disabled')
    .then(($el) => {
      const click = () =>
        $el[0].dispatchEvent(
          new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
            view: $el[0].ownerDocument.defaultView,
          }),
        );

      expect(click(), 'first click is allowed').to.be.true;
      expect(click(), 'second click is prevented').to.be.false;
    });
});
