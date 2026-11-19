// cy.origin() serialises Cypress.env, which turns the Set of checked pages into a plain
// object. Call this after any cy.origin() block so later a11y checks keep working.
export function repairA11yCheckedPagesAfterOrigin() {
  cy.then(() => {
    if (!(Cypress.env("a11yCheckedPages") instanceof Set)) {
      Cypress.env("a11yCheckedPages", new Set());
    }
  });
}
