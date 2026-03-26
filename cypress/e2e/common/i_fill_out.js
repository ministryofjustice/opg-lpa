import { When, Then } from '@badeball/cypress-cucumber-preprocessor';

When(`I type {string} into {string}`, (value, id) => {
  cy.get('[data-cy=' + id + ']')
    .clear({ force: true })
    .type(value);
});

When(
  `I type {string} into {string} working around cypress bug`,
  (value, id) => {
    // it seems that sometimes Cypress doesn't fill out text properly, for example in postcode lookup. This step
    // fills it in a different way in an attempt to workaround this bug
    cy.get('[data-cy=' + id + ']').invoke('val', value);
  },
);

When(`I select {string} on {string}`, (value, id) => {
  cy.get('[data-cy=' + id + ']').select(value);
});

When(`I select {string} on {string} with data-inited`, (value, id) => {
  cy.get('[data-cy=' + id + '][data-inited=true]').select(value);
});

Then(`I select option {string} of {string}`, (option, object) => {
  cy.get('[data-cy=' + object + ']').select(option);
});

Then(`I select element containing {string}`, (linkText) => {
  cy.contains(linkText).select();
});

Then(`I check element containing {string}`, (linkText) => {
  cy.contains(linkText).check();
});

When('I fill out', (dataTable) => {
  var rawTable = dataTable.rawTable;
  rawTable.forEach((row) => {
    cy.get('[data-cy=' + row[0] + ']')
      .clear()
      .type(row[1]);
  });
});

// todo : this uses force, to forcibly fill out elements even if they're meant to be hidden
// the casper tests just bludgeoned their way through not checking whether things were hidden
// cypress is more careful. We should ultimately revisit this and ensure we aren't hiding
// things we shouldn't
When('I force fill out', (dataTable) => {
  var rawTable = dataTable.rawTable;

  rawTable.forEach((row) => {
    cy.get('[data-cy=' + row[0] + ']')
      .clear({ force: true })
      .type(row[1], { force: true });
  });
});

Then('I force fill out {string} element with {string}', (element, value) => {
  cy.get(element).clear({ force: true }).type(value, { force: true });
});

Then(
  'I force fill out {string} with the value of the year {int} years ago',
  (id, value) => {
    var year = new Date().getFullYear() - value;
    cy.get('[data-cy=' + id + ']')
      .clear({ force: true })
      .type(year, { force: true });
  },
);

Then('I clear the value in {string}', (object) => {
  cy.get('[data-cy=' + object + ']').clear();
});

Then('I see form prepopulated with', (dataTable) => {
  var rawTable = dataTable.rawTable;
  rawTable.forEach((row) => {
    cy.get('[data-cy=' + row[0] + ']').should('include.value', row[1]);
  });
});

Then(
  'I see {string} prepopulated within timeout with {string}',
  (object, value) => {
    // set higher timeout because sometimes cypress takes more than the default 4 secs to fill in an element
    cy.get('[data-cy=' + object + ']', { timeout: 10000 }).should(
      'have.value',
      value,
    );
  },
);

Then('I see {string} prepopulated with {string}', (object, value) => {
  // set higher timeout because sometimes cypress takes more than the default 4 secs to fill in an element
  cy.get('[data-cy=' + object + ']').should('include.value', value);
});
