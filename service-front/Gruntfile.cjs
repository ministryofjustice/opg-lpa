const process = require('process');

const Handlebars = require('handlebars');

// content is the handlebars template content for the env-vars JS file
const injectEnvVars = function (content) {
  // load the template into Handlebars
  const template = Handlebars.compile(content);

  // construct the ENV_VARS variable to inject into the template
  // from the nodejs runtime environment variables; these will be from the
  // pipeline env and similar
  let envVars = {};

  // render the template
  return template({ENV_VARS: envVars});
};

module.exports = function (grunt) {
  'use strict';

  grunt.initConfig({

    // watching sass and js (as they need post tasks)
    watch: {
      js: {
        files: 'assets/js/**/*.js',
        tasks: ['build_js']
      },
      templates: {
        files: ['<%= handlebars.compile.src %>'],
        tasks: ['handlebars']
      }
    },

    copy: {
      jsenv: {
        src: 'assets/js/opg/env-vars.template.js',
        dest: 'assets/js/opg/env-vars.js',
        options: {
          process: injectEnvVars
        },
        flatten: true
      },

      jsdev: {
        src: 'public/assets/v2/js/application.js',
        dest: 'public/assets/v2/js/application.min.js'
      },

      jsdevgovuk: {
        src: 'node_modules/govuk-frontend/dist/govuk/govuk-frontend.min.js',
        dest: 'public/assets/v2/js/govuk-frontend.min.js'
      },

      jsdevgovukinit: {
        src: 'assets/js/opg/govuk-init.js',
        dest: 'public/assets/v2/js/govuk-init.js'
      },
    },

    // join the JS files
    concat: {
      options: {
          sourceMap: false,
          separator: ';\n'
      },
      dist: {
        src: [
          // Dependencies
          'node_modules/handlebars/dist/handlebars.js',
          'node_modules/lodash/lodash.js',
          'node_modules/urijs/src/URI.min.js',
          'node_modules/govuk_frontend_toolkit/javascripts/govuk/show-hide-content.js',

          // OPG Scripts
          'assets/js/opg/jquery-plugin-opg-spinner.js',
          'assets/js/opg/session-timeout-dialog.js',
          'assets/js/opg/env-vars.js',
          'assets/js/opg/cache-busting.js',

          // MoJ Scripts - Base
          'assets/js/moj/moj.js',
          'assets/js/moj/moj.helpers.js',
          'assets/js/moj/moj.cookie-functions.js',

          // LPA Scripts - Templates
          'assets/js/lpa/lpa.templates.js',

          // MoJ Scripts - Modules
          'assets/js/moj/moj.modules/moj.password.js',
          'assets/js/moj/moj.modules/moj.popup.js',
          'assets/js/moj/moj.modules/moj.help-system.js',
          'assets/js/moj/moj.modules/moj.form-popup.js',
          'assets/js/moj/moj.modules/moj.title-switch.js',
          'assets/js/moj/moj.modules/moj.postcode-lookup.js',
          'assets/js/moj/moj.modules/moj.print-link.js',
          'assets/js/moj/moj.modules/moj.person-form.js',
          'assets/js/moj/moj.modules/moj.validation.js',
          'assets/js/moj/moj.modules/moj.repeat-application.js',
          'assets/js/moj/moj.modules/moj.dashboard.js',
          'assets/js/moj/moj.modules/moj.ui-behaviour.js',
          'assets/js/moj/moj.modules/moj.applicant.js',
          'assets/js/moj/moj.modules/moj.polyfill.js',
          'assets/js/moj/moj.modules/moj.single-use.js',
          'assets/js/moj/moj.modules/moj.analytics.js',
          'assets/js/moj/moj.modules/moj.cookie-consent.js',

          // Init Script
          'assets/js/main.js',
        ],
        dest: 'public/assets/v2/js/application.js',
        nonull: true
      }
    },

    // minify for production
    uglify: {
      options: {
        sourceMap: false
      },
      build1: {
        src: 'public/assets/v2/js/application.js',
        dest: 'public/assets/v2/js/application.min.js'
      },
      build2: {
        src: 'assets/js/opg/session-timeout-init.js',
        dest: 'public/assets/v2/js/opg/session-timeout-init.min.js'
      },
      build3: {
        src: 'assets/js/opg/dashboard-statuses.js',
        dest: 'public/assets/v2/js/opg/dashboard-statuses.min.js'
      },
      build4: {
        src: 'assets/js/opg/init-polyfill.js',
        dest: 'public/assets/v2/js/opg/init-polyfill.min.js'
      },
    },

    // compile handlebars templates
    handlebars: {
      compile: {
        options: {
          namespace: 'lpa.templates',
          prettify: false,
          amdWrapper: false,
          processName: function (filename) {
            // Shortens the file path for the template and removes file extension.
            return filename.slice(filename.indexOf('templates') + 10, filename.length).replace(/\.[^/.]+$/, '');
          }
        },
        src: ['assets/js/lpa/templates/*.html'],
        dest: 'assets/js/lpa/lpa.templates.js'
      }
    }
  });

  // load npm tasks
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-handlebars');
  grunt.loadNpmTasks('grunt-contrib-copy');

  // define tasks
  grunt.registerTask('build_js', ['copy:jsenv', 'handlebars', 'concat', 'uglify', 'copy:jsdevgovuk', 'copy:jsdevgovukinit']);
  grunt.registerTask('build', ['build_js']);
};
