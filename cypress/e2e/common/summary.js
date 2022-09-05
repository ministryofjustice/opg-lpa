import { Then } from "@badeball/cypress-cucumber-preprocessor";

Then("I see the following summary information", (dataTable) => {
    var rawTable = dataTable.rawTable;
    let counter = 0;
    let changeCounter = 0;
    let addressCounter = 0;
    rawTable.forEach(row => {
        // cya-question should always match row[0]
        cy.get("[data-cy=cya-question]").eq(counter).should("contain",row[0]);
        // addresses are broken up for checking
        if (row[0].replace(/\s+/g, '')  == 'Address') {
            checkAddress(row[1], addressCounter);
            addressCounter++
        }
        else {
            // all other non-empty cases of cya-answer should match row[1]
            if (row[1] != "") {
                cy.get("[data-cy=cya-answer]").eq(counter).should("contain",row[1]);
            }
        }
        // row[2] is optional change link, if specified , we ensure it exists and href points to right place
        if (row[2] != "") {
            cy.get('@lpaId').then((lpaId) => {
                cy.get("[data-cy=cya-change]").eq(changeCounter).invoke('attr', 'href').should('contain','/lpa/' + lpaId + '/' + row[2]);
                changeCounter++
            });
        }
        counter++
    });
});

function checkAddress(addrString, addressCounter) {
    var addrArray = addrString.split('$');
    if (addrArray.length > 0) {
        cy.get("[data-cy=streetAddress]").eq(addressCounter).should("contain", addrArray[0].trim());
    }
    if (addrArray.length > 1) {
        cy.get("[data-cy=addressLocality]").eq(addressCounter).should("contain", addrArray[1].trim());
    }
    if (addrArray.length > 2) {
        cy.get("[data-cy=addressRegion]").eq(addressCounter).should("contain", addrArray[2].trim());
    }
    if (addrArray.length > 3) {
        cy.get("[data-cy=postalCode]").eq(addressCounter).should("contain", addrArray[3].trim());
    }
}
