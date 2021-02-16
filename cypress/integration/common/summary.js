import { When } from "cypress-cucumber-preprocessor/steps";

Then("I see the following summary information", (dataTable) => {
    var rawTable = dataTable.rawTable;
    // row[0] is cya-question,  row[1] is optional cya-answer, row[2] is optional change link
    let counter = 0;
    let changeCounter = 0;
    rawTable.forEach(row => { 
        cy.get("[data-cy=cya-question]").eq(counter).should("contain",row[0]);
        if (row[1] != "") {
            cy.get("[data-cy=cya-answer]").eq(counter).should("contain",row[1]);
        }
        if (row[2] != "") {
            cy.getLpaId().then((lpaId) => { 
                cy.get("[data-cy=cya-change]").eq(changeCounter).invoke('attr', 'href').should('contain','/lpa/' + lpaId + '/' + row[2]);
                changeCounter++
            });
        }
        counter++
    });
});

//function checkAddress() {
//}
