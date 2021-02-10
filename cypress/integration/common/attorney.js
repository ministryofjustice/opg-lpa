import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I can find save pointing to primary attorney decisions page`, (linkAddr) => {
    canFindPage('how-primary-attorneys-make-decision');
})

Then(`I can find save pointing to replacement attorney page`, (linkAddr) => {
    canFindPage('replacement-attorney');
})

Then(`I can find save pointing to people to notify page`, (linkAddr) => {
    canFindPage('people-to-notify');
})

function canFindPage(urlPart) {
    cy.getLpaId().then((lpaId) => { 
        cy.get("[data-cy=save]").invoke('attr', 'href').should('eq','/lpa/' + lpaId + '/' + urlPart);
    });
}
