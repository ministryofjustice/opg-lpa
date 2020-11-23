describe('Test homepage', () => {
  it('Visits the Make homepage', () => {
    cy.visit('/')
    cy.contains('Make a lasting power of attorney')
    cy.injectAxe();
    cy.checkA11y();
  })
})
