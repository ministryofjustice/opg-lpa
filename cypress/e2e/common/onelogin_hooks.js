const { Before } = require('@badeball/cypress-cucumber-preprocessor');

let oneLoginEnabled = null;

function detectOneLoginEnabled() {
  if (oneLoginEnabled !== null) {
    return cy.wrap(oneLoginEnabled, { log: false });
  }

  return cy.request({ url: '/login', log: false }).then((response) => {
    oneLoginEnabled = response.body.includes(
      'data-cy="onelogin-signin-button"',
    );
    return oneLoginEnabled;
  });
}

Before({ tags: '@RequiresOneLogin' }, function () {
  detectOneLoginEnabled().then((enabled) => {
    if (!enabled) {
      cy.log('GOV.UK One Login is not enabled in this environment, skipping');
      this.skip();
    }
  });
});
