(function () {
  'use strict';

  var moj = {
    Modules: {},

    Helpers: {},

    Events: $({}),

    init: function () {
      for (var x in moj.Modules) {
        if (x != 'formErrorTracker') {
          if (typeof moj.Modules[x].init === 'function') {
            moj.Modules[x].init();
          }
        }
      }
      // trigger initial render event
      moj.Events.trigger('render');
    },

    // safe logging
    log: function (msg) {
      if (window && window.console) {
        window.console.log(msg);
      }
    },
    dir: function (obj) {
      if (window && window.console) {
        window.console.dir(obj);
      }
    },
  };

  window.moj = moj;
})();

(function () {
  'use strict';

  // Invite interested developers to join us
  moj.Modules.devs = {
    init: function () {
      var m =
          '      ___          ___       ___\n     /__/\\        /  /\\     /  /\\\n    |  |::\\      /  /::\\   /  /:/\n    |  |:|:\\    /  /:/\\:\\ /__/::\\\n  __|__|:|\\:\\  /  /:/  \\:\\\\__\\/\\:\\\n /__/::::| \\:\\/__/:/ \\__\\:\\  \\  \\:\\\n \\  \\:\\~~\\__\\/\\  \\:\\ /  /:/   \\__\\:\\\n  \\  \\:\\       \\  \\:\\  /:/    /  /:/\n   \\  \\:\\       \\  \\:\\/:/    /__/:/\n    \\  \\:\\       \\  \\::/     \\__\\/\n     \\__\\/        \\__\\/',
        txt =
          '\n\nLike what you see? Want to make a difference?' +
          "\n\nFind out how we're making the Ministry Of Justice Digital by Default." +
          '\nhttp://blogs.justice.gov.uk/digital/.' +
          '\n\nGet in touch to see what positions are available and see what projects you could be working on.' +
          '\nhttps://twitter.com/Justice_Digital';
      moj.log(m + txt);
    },
  };
})();
