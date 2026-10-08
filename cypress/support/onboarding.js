// The "Which best describes you?" answers as the page words them, mapped to the radio values.
export const USER_TYPES = {
  'myself, family or friends': 'lay',
  'other people as part of my work': 'professional',
};

export function sayIMakeLpasFor(description) {
  cy.get(`[data-cy="userType-${USER_TYPES[description]}"]`).check();
  cy.get('[data-cy="which-best-describes-you-continue"]').click();
}
