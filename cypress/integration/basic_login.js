describe('Test user login', () => {
  it('Visits the Make website', () => {
    //cy.visit('https://localhost:7002/login')
    cy.visit('/login')
    cy.contains('Sign in')
    cy.get("input#email.form-control").clear().type("seeded_test_user@digital.justice.gov.uk");
    cy.get("input#password.form-control").clear().type("Pass1234");
    cy.get('input#signin-form-submit.button').click()
    cy.contains("h1.heading-xlarge", "Your LPAs");
  })
})
