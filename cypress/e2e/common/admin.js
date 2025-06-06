const path = require('path');
import { Then } from '@badeball/cypress-cucumber-preprocessor';

const findActivationDates = () => {
  return Cypress.$('[data-role=user-activation-date]');
};

const findLoginTimes = () => {
  return Cypress.$('[data-role=user-last-login-time]');
};

Then('I find {string} on the admin site', (name) => {
  let user = Cypress.env(name + '-user');
  cy.visit(Cypress.env('adminUrl'));
  cy.get('[data-cy=user-search-link]').should('not.be.disabled').click();
  cy.get('[data-cy=email-address-input]').clear({ force: true }).type(user);
  cy.get('[data-cy=submit-button]').should('not.be.disabled').click();
});

// cypress steps specific to the admin UI
Then('I am taken to the find users page', () => {
  cy.url().should('eq', Cypress.env('adminUrl') + '/user-find');
});

Then('I am taken to the system message page', () => {
  cy.url().should('eq', Cypress.env('adminUrl') + '/system-message');
});

Then('I am taken to the feedback page', () => {
  cy.url().should('eq', Cypress.env('adminUrl') + '/feedback');
});

Then('the first user email address is {string}', (dateString) => {
  const firstEmailAddress = Cypress.$('[data-role=user-email]').get(0);
  expect(firstEmailAddress.innerHTML.trim()).to.eql(dateString);
});

Then('the first user account status is {string}', (status) => {
  const firstStatus = Cypress.$('[data-role=user-account-status]').get(0);
  expect(firstStatus.innerText).to.eql(status);
});

Then('the first deletion reason is {string}', (reason) => {
  const firstReason = Cypress.$('[data-role=deletion-reason]').get(0);
  expect(firstReason.innerText).to.eql(reason);
});

Then('the first activation date is {string}', (dateString) => {
  const dates = findActivationDates();
  const firstDate = dates.get(0);
  expect(firstDate.innerHTML).to.eql(dateString);
});

Then('the second last login time is {string}', (timeString) => {
  const times = findLoginTimes();
  const secondLoginTime = times.get(1);
  expect(secondLoginTime.innerHTML).to.eql(timeString);
});

Then(
  'deleted user is displayed with deletion date of {string}',
  (dateString) => {
    const deletionDate = Cypress.$('[data-role=deletion-date]').get(0);
    expect(deletionDate.innerHTML).to.eql(dateString);
  },
);

Then('the email address input contains {string}', (emailAddress) => {
  cy.get('[data-cy=email-address-input]').then((elt) => {
    expect(elt.attr('value')).to.eql(emailAddress);
  });
});

Then(`I can export feedback and download it as a CSV file`, () => {
  cy.intercept('POST', '/feedback?export=true', (req) => {
    req.continue((res) => {
      const contentDisposition = res.headers['content-disposition'];
      const downloadedFile = contentDisposition.split('filename=')[1];
      const downloadsFolder = Cypress.config('downloadsFolder');

      cy.readFile(path.join(downloadsFolder, downloadedFile)).then(
        (download) => {
          const lines = download.split('\n');

          // header line + 3 rows + 1 empty line
          expect(lines.length).to.eql(5);
          expect(lines[0]).to.eql(
            'Received,From,"Phone number",Rating,Details,Page,Browser',
          );

          // check structure of rows after header
          for (let i = 1; i < 4; i++) {
            expect(lines[i]).to.match(
              /"\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}",.+,.+,.+,.+,\/.+,".+"/,
            );
          }
        },
      );
    });
  });

  cy.contains('Export').click();
});

Then(
  'the information icon for the system message should not be on the page',
  () => {
    cy.get('div.notice > i.icon.icon-important').should('not.exist');
  },
);

Then(
  'very long feedback details from user {string} displays correctly in the page',
  (user) => {
    cy.window().then((window) => {
      cy.get('[data-role=from]:contains(' + user + ')').then((elt) => {
        // check left-hand edge of details cell is within the viewport
        const details = elt.siblings('[data-role=details]');
        const rect = details[0].getBoundingClientRect();
        expect(rect.left).to.be.within(0, window.innerWidth);

        return elt;
      });
    });
  },
);
