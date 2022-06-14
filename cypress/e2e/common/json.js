import { And, Then, When } from "cypress-cucumber-preprocessor/steps";

When('I visit the {string} JSON endpoint and save the response as {string}', (path, key) => {
    cy.request(path).then((response) => {
        cy.task('putValue', {name: key, value: response})
    });
})

Then('I should have a valid JSON response saved as {string}', (key) => {
    // this can only run after "I visit the {string} JSON endpoint..."
    cy.task('getValue', key).then((response) => {
        expect(response.body).to.not.be.null
        expect(response.headers['content-type']).to.contain('application/json')
    })
})
