import { Then } from "cypress-cucumber-preprocessor/steps";

When("I fill out", (dataTable) => {
    var rawTable = dataTable.rawTable;

    rawTable.forEach(row => { cy.get("[data-cy=" + row[0] + "]").type(row[1]);
                });
});

// this uses force, to forcibly fill out elements even if they're meant to be hidden
When("I force fill out", (dataTable) => {
    var rawTable = dataTable.rawTable;

    rawTable.forEach(row => { cy.get("[data-cy=" + row[0] + "]").type(row[1], { force: true });
                });
});
