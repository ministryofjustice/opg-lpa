import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I can find save pointing to primary attorney decisions page`, (linkAddr) => {
    cy.getLpaId().then((lpaId) => { 
        cy.get("[data-cy=save]").invoke('attr', 'href').should('eq','/lpa/' + lpaId + '/how-primary-attorneys-make-decision');
    });
})

Then(`I can find save pointing to replacement attorney page`, (linkAddr) => {
    cy.getLpaId().then((lpaId) => { 
        cy.get("[data-cy=save]").invoke('attr', 'href').should('eq','/lpa/' + lpaId + '/replacement-attorney');
    });
})
