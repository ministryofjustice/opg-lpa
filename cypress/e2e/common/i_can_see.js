import { Then } from "@badeball/cypress-cucumber-preprocessor";

Then('I can see a "Reuse LPA details" link for the test fixture lpa', () => {
    seeReuseDetailsLink(true);
});

Then('I cannot see a "Reuse LPA details" link for the test fixture lpa', () => {
    seeReuseDetailsLink(false);
});

const seeReuseDetailsLink = (shouldExist) => {
    cy.get('@lpaId').then((lpaId) => {
        const selector = 'a[href*="/user/dashboard/create/' + lpaId + '"]';
        const condition = (shouldExist ? 'exist' : 'not.exist');
        cy.get(selector).should(condition);
    });
}

Then('I cannot see continuation sheet reminders', () => {
    cy.get('[data-cy=continuation-sheet-info]').should('not.exist');
});

Then('I can see fields for the donor, certificate provider, attorney, applicant', () => {
    const expectedNames = ['Mr Dead Pool (donor)',
                           'Mr Cee Pee (certificate provider)',
                           'Mr A Att (attorney)',
                           'Mr Dead Pool (applicant)']
    cy.get('[data-cy=person-name]').then(($name) => {
        const names = $name.map(function() {return this.innerText}).toArray();
        expect(names).to.deep.eq(expectedNames);
    })
})

Then('I can see a reminder to sign continuation sheet 1 and 2', () => {
    const text = 'Continuation sheets 1 and 2 must have been signed and dated before or on the same day as they signed continuation sheet 3.'
    cy.get('[data-cy=continuation-sheet-info]').should('contain.text', text);
})

Then('I can see a reminder to sign continuation sheet 4', () => {
    const text = 'They must have signed continuation sheet 4 after the \'certificate provider\' has signed section 10 of the LPA form.'
    cy.get('[data-cy=primary-attorney]').find('[data-cy=continuation-sheet-info]').should('contain.text', text);
})
