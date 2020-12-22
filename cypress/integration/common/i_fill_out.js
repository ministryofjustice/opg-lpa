import { Then } from "cypress-cucumber-preprocessor/steps";

When("I fill out", (dataTable) => {
    var rawTable = dataTable.rawTable;

    rawTable.forEach(row => { 
                cy.log("field is " + row[0])
                if (row[0].valueOf() === new String("#name-title").valueOf()) { // we cannot currently label name-title with a data-cy tag
                    cy.get(row[0]).type(row[1]);
                }
                else {
                    cy.get("[data-cy=" + row[0] + "]").type(row[1]);
                }
            });
});

// this uses force, to forcibly fill out elements even if they're meant to be hidden
When("I force fill out", (dataTable) => {
    var rawTable = dataTable.rawTable;

    rawTable.forEach(row => { 
                cy.log("field is " + row[0])
                if (row[0].valueOf() === new String("#name-title").valueOf()) { // we cannot currently label name-title with a data-cy tag
                    cy.get(row[0]).type(row[1], { force: true });
                }
                else {
                    cy.get("[data-cy=" + row[0] + "]").type(row[1], { force: true });
                }
            });
});
