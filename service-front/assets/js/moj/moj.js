;(function () {
  'use strict'

  const moj = {

    Modules: {},

    Helpers: {},

    /**
     * Event emitter;
     * modified from https://github.com/jeromeetienne/microevent.js/blob/master/microevent.js
     * (MIT licence)
     */
    Events: {
      on: function (event, fn) {
        this.evts = this.evts || {}
        this.evts[event] = this.evts[event] || []
        this.evts[event].push(fn)
      },
      off: function (event, fn) {
        this.evts = this.evts || {}
        if (event in this.evts) {
          this.evts[event].splice(this.evts[event].indexOf(fn), 1)
        }
      },
      trigger: function (event /* , args... */) {
        this.evts = this.evts || {}
        if (event in this.evts) {
          for (let i = 0; i < this.evts[event].length; i++) {
            // call the handler with all the arguments passed to trigger()
            this.evts[event][i].apply(this, arguments)
          }
        }
      }
    },

    init: function () {
      for (const x in moj.Modules) {
        if (typeof moj.Modules[x].init === 'function') {
          moj.Modules[x].init()
        }
      }
      // trigger initial render event
      moj.Events.trigger('render')
    },

    // safe logging
    log: function (msg) {
      if (window && window.console) {
        window.console.log(msg)
      }
    },

    dir: function (obj) {
      if (window && window.console) {
        window.console.dir(obj)
      }
    }
  }

  window.moj = moj
}());

(function () {
  'use strict'

  // Invite interested developers to join us
  window.moj.Modules.devs = {
    init: function () {
      const m = '      ___          ___       ___\n' +
                '     /__/\\        /  /\\     /  /\\\n' +
                '    |  |::\\      /  /::\\   /  /:/\n' +
                '    |  |:|:\\    /  /:/\\:\\ /__/::\\\n' +
                '  __|__|:|\\:\\  /  /:/  \\:\\\\__\\/\\:\\\n' +
                ' /__/::::| \\:\\/__/:/ \\__\\:\\  \\  \\:\\\n' +
                ' \\  \\:\\~~\\__\\/\\  \\:\\ /  /:/   \\__\\:\\\n' +
                '  \\  \\:\\       \\  \\:\\  /:/    /  /:/\n' +
                '   \\  \\:\\       \\  \\:\\/:/    /__/:/\n' +
                '    \\  \\:\\       \\  \\::/     \\__\\/\n' +
                '     \\__\\/        \\__\\/'
      const txt = '\n\nLike what you see? Want to make a difference?' +
                  "\n\nFind out how we're making the Ministry Of Justice Digital by Default." +
                  '\nhttp://blogs.justice.gov.uk/digital/.' +
                  '\n\nGet in touch to see what positions are available and see what projects you could be working on.' +
                  '\nhttps://twitter.com/Justice_Digital'
      window.moj.log(m + txt)
    }
  }
}())
