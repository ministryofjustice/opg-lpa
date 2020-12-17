import { Then } from "cypress-cucumber-preprocessor/steps";

When("I fill out", (dataTable) => {
    var rawTable = dataTable.rawTable;

    rawTable.forEach(row => { //cy.log("row contains: ", row); 
                            var searchStr = "[data-cy=" + row[0] + "]"; 
                            cy.get(searchStr).type(row[1]);
                });
});
