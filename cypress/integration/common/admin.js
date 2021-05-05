import { Then } from "cypress-cucumber-preprocessor/steps";

let findActivationDates = () => {
    return Cypress.$('[data-role=user-activation-date]');
};

// cypress steps specific to the admin UI
Then("I am taken to the find users page", () => {
    cy.url().should('eq', Cypress.env('adminUrl') + '/user-find');
});

Then("the first activation date is {string}", (dateString) => {
    const dates = findActivationDates();
    const firstDate = dates.get(0);
    expect(firstDate.innerHTML).to.eql(dateString);
});

Then("the second activation date is {string}", (dateString) => {
    const dates = findActivationDates();
    const secondDate = dates.get(1);
    expect(secondDate.innerHTML).to.eql(dateString);
});

Then("the email address input contains {string}", (emailAddress) => {
    cy.get('[data-cy=email-address-input]').then((elt) => {
        expect(elt.attr('value')).to.eql(emailAddress);
    });
});
