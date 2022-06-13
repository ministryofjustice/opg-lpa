import { Then } from "cypress-cucumber-preprocessor/steps";

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

Then('I can see fields for the PF donor, certificate provider, attorney, applicant', () => {
    const expectedNames = ['Mr Dead Pool (the donor)', 'Mr Cee Pee (certificate provider)',
                           'Mr A Att (attorney)', 'Mr Dead Pool (applicant)']
	    cy.get('[data-cy=person-name]').then(($name) => {
		    const names = $name.map(function() {return this.innerText}).toArray();
			expect(names).to.deep.eq(expectedNames);
		})
})

Then('I can see fields for the HW donor, certificate provider, attorney, applicant', () => {
    const expectedNames = ['Miss Rapunzel Tower (the donor)', 'Mr Cee Pee (certificate provider)',
                                                 'Mr A Att (attorney)', 'Miss Rapunzel Tower (applicant)']
    cy.get('[data-cy=person-name]').then(($name) => {
        const names = $name.map(function() { return this.innerText }).toArray();
        expect(names).to.deep.eq(expectedNames);
    })
})

Then('I can see the revelant HW continuation sheet notes', () => {
    const text = ["As the donor cannot sign the LPA, a trusted person will need to sign 'continuation sheet 3' on the donor's behalf. The continuation sheet is included at the end of the LPA form."]
    cy.get('[data-cy=continuation-sheet-notes]').should('have.length', 1);
    cy.get('[data-cy=continuation-sheet-notes]').then(($note) => {
        const notes = $note.map(function() { return this.innerText }).toArray();
        expect(notes).to.deep.eq(text);
    })
})

Then('I can see a reminder to sign continuation sheet 1 and 2', () => {
    const text = 'You must have signed and dated continuation sheets 1 and 2 before you signed section 9 of the LPA, or on the same day.'
    cy.get('[data-cy=continuation-sheet-info]').should('contain.text', text);
})

Then('I can see a reminder to sign continuation sheet 3 for PF', () => {
    const text = 'This person must have signed continuation sheet 3 before the certificate provider has signed section 10.'
    cy.get('[data-cy=continuation-sheet-info]').should('contain.text', text);
})

Then('I can see a reminder to sign continuation sheet 3 for HW', () => {
    const text = 'This person must have signed continuation sheet 3 on the same day as they sign section 5 and before the certificate provider signs section 10.'
    cy.get('[data-cy=continuation-sheet-info]').should('contain.text', text);
})

Then('I can see a reminder to sign continuation sheet 4', () => {
    const text = 'They must have signed continuation sheet 4 after the ‘certificate provider’ has signed section 10 of the LPA form.'
    cy.get('[data-cy=continuation-sheet-info]').should('contain.text', text);
})
