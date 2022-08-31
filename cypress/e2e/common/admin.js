import { Then } from "@badeball/cypress-cucumber-preprocessor";

const findActivationDates = () => {
    return Cypress.$('[data-role=user-activation-date]');
};

const findLoginTimes = () => {
    return Cypress.$('[data-role=user-last-login-time]');
};

// cypress steps specific to the admin UI
Then("I am taken to the find users page", () => {
    cy.url().should('eq', Cypress.env('adminUrl') + '/user-find');
});

Then("I am taken to the system message page", () => {
    cy.url().should('eq', Cypress.env('adminUrl') + '/system-message');
});

Then("I am taken to the feedback page", () => {
    cy.url().should('eq', Cypress.env('adminUrl') + '/feedback');
});

Then("the first activation date is {string}", (dateString) => {
    const dates = findActivationDates();
    const firstDate = dates.get(0);
    expect(firstDate.innerHTML).to.eql(dateString);
});

Then("the second last login time is {string}", (timeString) => {
    const times = findLoginTimes();
    const secondLoginTime = times.get(1);
    expect(secondLoginTime.innerHTML).to.eql(timeString);
});

Then("deleted user is displayed with deletion date of {string}", (dateString) => {
    const deletionDate = Cypress.$('[data-role=deletion-date]').get(0);
    expect(deletionDate.innerHTML).to.eql(dateString);
});

Then("the email address input contains {string}", (emailAddress) => {
    cy.get('[data-cy=email-address-input]').then((elt) => {
        expect(elt.attr('value')).to.eql(emailAddress);
    });
});
