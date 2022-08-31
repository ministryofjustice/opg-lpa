import { Then } from "@badeball/cypress-cucumber-preprocessor";

Then(`I can find save pointing to primary attorney decisions page`, (linkAddr) => {
    canFindButtonLinkedTo('how-primary-attorneys-make-decision');
})

Then(`I can find save pointing to replacement attorney page`, (linkAddr) => {
    canFindButtonLinkedTo('replacement-attorney');
})

Then(`I can find save pointing to people to notify page`, (linkAddr) => {
    canFindButtonLinkedTo('people-to-notify');
})

function canFindButtonLinkedTo(urlPart) {
    cy.get('@lpaId').then((lpaId) => {
        let expectedHref = '/lpa/' + lpaId + '/' + urlPart;
        cy.get('[data-cy=save][href="' + expectedHref + '"]');
    });
}
