// Which GOV.UK One Login the environment under test signs in with:
//   'mock' - the opg-mock-onelogin service (local and PR environments)
//   'real' - GOV.UK One Login itself (preproduction uses the integration environment)
let oneLoginMode = null;

export function detectOneLoginMode() {
  if (oneLoginMode !== null) {
    return cy.wrap(oneLoginMode, { log: false });
  }

  return cy.request({ url: '/login', log: false }).then((response) => {
    if (!response.body.includes('data-cy="onelogin-signin-button"')) {
      oneLoginMode = 'off';
      return oneLoginMode;
    }

    return cy
      .request({ url: '/auth/onelogin', followRedirect: false, log: false })
      .then((authResponse) => {
        const authorizeHost = new URL(authResponse.redirectedToUrl).host;
        oneLoginMode = authorizeHost.endsWith('.account.gov.uk') ? 'real' : 'mock';
        return oneLoginMode;
      });
  });
}
