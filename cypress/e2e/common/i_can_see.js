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

Then('I can see a reminder to sign continuation sheet 1, 2 and 3', () => {
    const text = 'Continuation sheets 1 and 2 must have been signed and dated before or on the same day as they signed continuation sheet 3.'
    cy.get('[data-cy=continuation-sheet-info]').should('contain.text', text);
})

Then('I can see a reminder to sign continuation sheet 4', () => {
    const text = 'They must have signed continuation sheet 4 after the \'certificate provider\' has signed section 10 of the LPA form.'
    cy.get('[data-cy=primary-attorney]').find('[data-cy=continuation-sheet-info]').should('contain.text', text);
})

Then('I can see that the donor cannot sign', () => {
    const donorName = 'The person signing on behalf of Mr Christopher Robin (donor)'
    const donorText = 'This person signed continuation sheet 3 on behalf of the donor, followed by two witnesses, on'
    cy.get('[data-cy=date-check-donor]').find('h3').should('contain.text', donorName);
    cy.get('[data-cy=date-check-donor]').find('p').should('contain.text', donorText);

    const applicantName = 'The person signing on behalf of Mr Christopher Robin (applicant)'
    const applicantText = 'This person signed section 15 of the LPA on behalf of the applicant on'
    cy.get('[data-cy=date-check-applicant]').find('h3').should('contain.text', applicantName);
    cy.get('[data-cy=date-check-applicant]').find('p').should('contain.text', applicantText);
})

Then('I cannot see that the donor cannot sign', () => {
    const donorName = 'Mr Dead Pool (donor)'
    const donorText = 'This person signed section 9 of the LPA on'
    cy.get('[data-cy=date-check-donor]').find('h3').should('contain.text', donorName);
    cy.get('[data-cy=date-check-donor]').find('p').should('contain.text', donorText);

    const applicantName = 'Mr Dead Pool (applicant)'
    const applicantText = 'This person signed section 15'
    cy.get('[data-cy=date-check-applicant]').find('h3').should('contain.text', applicantName);
    cy.get('[data-cy=date-check-applicant]').find('p').should('contain.text', applicantText);
})

Then('I can see validation errors do not refer to the donor', () => {
    const donorText = 'Enter the signature date of the person signing on behalf of the donor'
    const applicantText = 'Enter the signature date of the person signing on behalf of the applicant'
    cy.get('[data-cy=date-check-donor]').find('.error-message').should('contain.text', donorText)
    cy.get('[data-cy=date-check-applicant]').find('.error-message').should('contain.text', applicantText)
})

Then('I can see validation errors refer to the donor and applicant', () => {
    const donorText = 'Enter the donor\'s signature date'
    cy.get('[data-cy=date-check-donor]').find('.error-message').should('contain.text', donorText);

    const applicantText = 'Enter the applicant\'s signature date'
    cy.get('[data-cy=date-check-applicant]').find('.error-message').should('contain.text', applicantText );
})
