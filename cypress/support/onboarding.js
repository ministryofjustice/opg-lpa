function continueToNextPage(button) {
  cy.location('pathname').then((page) => {
    cy.get(button).click();
    cy.location('pathname').should('not.eq', page);
  });
}

function chooseAnswer(label, continueButton) {
  cy.contains('label', label).click();
  continueToNextPage(`[data-cy="${continueButton}"]`);
}

export function sayIMakeLpasFor(description) {
  chooseAnswer(description, 'which-best-describes-you-continue');
}

export function sayWhetherIHaveAnAccount(answer) {
  chooseAnswer(answer, 'existing-account-continue');
}

export function sayWhetherIKeepMyLpas(answer) {
  chooseAnswer(answer, 'keep-your-lpas-continue');
}

export function checkAnswerIsSelected(label) {
  cy.contains('.govuk-radios__item', label).find('input').should('be.checked');
}

export function checkFormError(message) {
  cy.get('[data-cy="error-summary"]').should('be.visible').and('contain', message);
  cy.get('[data-cy="form-error"]').should('contain', message);
  cy.OPGCheckA11yStrict('form-error');
}

// Details a new user enters on the onboarding "Your details" page.
export const MY_DETAILS = {
  'name-title': 'Mr',
  'name-first': 'Brand',
  'name-last': 'New',
  'dob-date-day': '1',
  'dob-date-month': '2',
  'dob-date-year': '1980',
};

export const MY_ADDRESS = {
  'address-address1': '123 Test Street',
  'address-postcode': 'SW1A 1AA',
};

function fillIn(fields) {
  Object.entries(fields).forEach(([field, value]) => {
    cy.get(`[data-cy="${field}"]`).clear().type(value);
  });
}

export function enterMyDetails(details = MY_DETAILS) {
  fillIn(details);
}

export function enterMyAddressManually(address = MY_ADDRESS) {
  fillIn(address);
}

export function checkSignedIn() {
  cy.get('[data-cy="service-nav-list"]').should('contain', 'Your LPAs');
}

export function saveMyDetails() {
  cy.get('[data-cy="save"]').click();
}
