import { When } from "cypress-cucumber-preprocessor/steps";

When(`I type {string} into {string}`, (value, id) => {
        cy.get("[data-cy=" + id + "]").type(value);
})

When(`I type {string} into old style id {string}`, (value, id) => {
    // this is for elements that we have been unable to tag with data-cy=
        cy.get(id).type(value);
})

When(`I select {string} on {string}`, (value, id) => {
        cy.get("[data-cy=" + id + "]").select(value);
})

When(`I select {string} on old style id {string}`, (value, id) => {
    // this is for elements that we have been unable to tag with data-cy=
        cy.get(id).select(value);
})

When("I fill out", (dataTable) => {
    var rawTable = dataTable.rawTable;

    rawTable.forEach(row => { 
                    cy.get("[data-cy=" + row[0] + "]").clear().type(row[1]);
            });
});

// todo : this uses force, to forcibly fill out elements even if they're meant to be hidden
// the casper tests just bludgeoned their way through not checking whether things were hidden
// cypress is more careful. We should ultimately revisit this and ensure we aren't hiding 
// things we shouldn't
When("I force fill out", (dataTable) => {
    var rawTable = dataTable.rawTable;

    rawTable.forEach(row => { 
                    cy.get("[data-cy=" + row[0] + "]").clear({ force: true }).type(row[1], { force: true });
            });
});

Then("I see form prefilled out with", (dataTable) => {
    var rawTable = dataTable.rawTable;

    rawTable.forEach(row => { 
                    cy.get("[data-cy=" + row[0] + "]").should("have.value",row[1]);
            });
});
