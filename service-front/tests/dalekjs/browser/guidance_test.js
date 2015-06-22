var helper = require('./helper');

module.exports = {
  name: 'Check JS Guidance System',

  'Open Guidance System': function (test) {
    'use strict';

    test
      .open(helper.url)
      .query('.js-guidance')
        .assert.chain()
          .exists('Guidance link exists')
          .attr('href').to.contain('/help/#topic-lpa-basics', 'Guidance link contains correct url')
        .end()
        .click()
        .waitForElement('#popup')
        .assert.exists('#popup', 'Popup has been loaded')
      .end()
      .done();
  },

  'Show correct section': function (test) {
    'use strict';

    test
      .wait(250)
      .assert.chain()
        .visible('#topic-lpa-basics', 'Correct topic is visible')
        // .notVisible('#topic-glossary', 'Other topic is not visible') // doesn't seem to work
        .url().to.contain('/#/help/topic-lpa-basics', 'URL has been changed correctly')
      .end()
      .done();
  },

  'Click Sidebar Navigation link': function (test) {
    'use strict';

    test
      .query('.help-navigation ul li:nth-child(2) a')
        .assert.chain()
          .visible('Nav link exists')
          .attr('href').to.contain('/help/#topic-about-this-tool', 'Second link contains correct URL')
        .end()
        .click()
      .end()
      .assert.chain()
        .url().to.contain('/#/help/topic-about-this-tool', 'Second URL has been changed correctly')
        .visible('#topic-about-this-tool', 'Second topic is visible')
      .end()
      .done();
  },

  'Navigate back with browser button': function (test) {
    'use strict';

    test
      .back()
      .assert.chain()
        .url().to.contain('/#/help/topic-lpa-basics', 'URL changed correctly after back click')
        .visible('#topic-lpa-basics', 'Correct topic visible after back click')
      .end()
      .done();
  },

  'Navigate forward with browser button': function (test) {
    'use strict';

    test
      .forward()
      .assert.chain()
        .url().to.contain('/#/help/topic-about-this-tool', 'URL changed correctly after forward click')
        .visible('#topic-about-this-tool', 'Correct topic visible after forward click')
      .end()
      .done();
  },

  'Close system when hash is removed through browser back button': function (test) {
    'use strict';

    test
      .back().back()
      .wait(1000)
      .assert.chain()
        .doesntExist('#popup', 'Popup has been removed after back twice')
        .url(helper.url, 'URL has been reset correctly after back twice')
      .end()
      .done();
  },

  'Close JS Guidance on link click': function (test) {
    'use strict';

    test
      .open(helper.url)
      .click('.js-guidance')
      .waitForElement('#popup')
      .wait(750)
      .assert.visible('#popup', 'Popup has been opened')
      .click('.js-popup-close')
      .wait(1000)
      .assert.doesntExist('#popup', 'Popup has been removed')
      .done();
  },

  'Close JS Guidance on escape keypress': function (test) {
    'use strict';

    test
      .open(helper.url)
      .click('.js-guidance')
      .waitForElement('#popup')
      .wait(750)
      .assert.visible('#popup', 'Popup has been opened')
      .sendKeys('body', '\uE00C')
      .wait(1000)
      .assert.doesntExist('#popup', 'Popup has been removed')
      .done();
  }
};