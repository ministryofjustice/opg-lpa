const process = require('process');

const Handlebars = require('handlebars');

// for global replace of incorrect colours (from obsolete
// govuk design system) with correct colours (from latest
// design system); this will not be required when we upgrade to the latest
// design system as we will just use the colours as they are
// without any patching
const colourPatching = [
  // focus-colour
  {
    'from': /ffbf47/g,
    'to': 'ffdd00'
  },
  // button-colour
  {
    'from': /00823b/g,
    'to': '00703c'
  }
];

const colourPatch = function (content) {
    for (let patch in colourPatching) {
        let oldColour = patch.from;
        let newColour = patch.to;
        content = content.replace(oldColour, newColour);
    }

    return content;
};

// content is the handlebars template content for the env-vars JS file
const injectEnvVars = function (content) {
    // load the template into Handlebars
    const template = Handlebars.compile(content);

    // construct the ENV_VARS variable to inject into the template
    // from the nodejs runtime environment variables; these will be from the
    // pipeline env and similar
    let envVars = {};

    // The following variables are set and passed to the template:
    //   revision: set from the REVISION env var; in CircleCI, this is set
    //   from a build-unique string in the pipeline;
    //   this is then used as a cacheBust parameter on JS ajax calls
    //   (see cache-busting.js)
    if ('REVISION' in process.env) {
        envVars.revision = process.env.REVISION;
    }

    // render the template
    return template({ENV_VARS: envVars});
};

module.exports = function (grunt) {
  'use strict';

  grunt.initConfig({

    // watching sass and js (as they need post tasks)
    watch: {
      scss: {
        files: 'assets/sass/**/*.scss',
        tasks: ['sass', 'replace:image_url', 'cssmin']
      },
      js: {
        files: 'assets/js/**/*.js',
        tasks: ['concat', 'copy:jsdev']
      },
      templates: {
        files: ['<%= handlebars.compile.src %>'],
        tasks: ['handlebars']
      }
    },

    // sass files to compile
    sass: {
      dev: {
        options: {
          loadPath: [
            'node_modules/govuk_frontend_toolkit/stylesheets',
            'node_modules/govuk-elements-sass/public/sass'
          ],
          sourcemap: false
        },
        files: {
          'public/assets/v2/css/application.css': 'assets/sass/application.scss',
          'public/assets/v2/css/print.css': 'assets/sass/print.scss'
        }
      }
    },

    // lint scss files
    scsslint: {
      allFiles: [
        'assets/sass/**/*.scss'
      ],
      options: {
        config: '.scss-lint.yml',
        reporterOutput: null,
        colorizeOutput: true
      }
    },

    replace: {
      // replacing a compass depended helper within govuk template css
      image_url: {
        src: ['public/assets/v2/css/*.css'],
        dest: 'public/assets/v2/css/',
        replacements: [{
          from: 'image-url',
          to: 'url'
        }]
      },

      // replace deprecated colours in CSS files
      colours: {
        src: ['public/assets/v2/css/*.css'],
        dest: 'public/assets/v2/css/',
        replacements: colourPatching,
      },
    },

    copy: {
      css: {
        src: [
          'node_modules/govuk_template_mustache/assets/stylesheets/fonts.css',
          'node_modules/govuk_template_mustache/assets/stylesheets/govuk-template-print.css',
          'node_modules/govuk_template_mustache/assets/stylesheets/govuk-template.css'
        ],
        dest: 'public/assets/v2/css/',
        options: {
          process: colourPatch
        },
        flatten: true,
      },

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
      }
    },

    // minifying the css
    cssmin: {
      options: {
        sourceMap: false
      },
      target: {
        files: [{
          expand: true,
          cwd: 'public/assets/v2/css',
          src: ['*.css', '!*.min.css'],
          dest: 'public/assets/v2/css',
          ext: '.min.css'
        }]
      }
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
          'assets/js/govuk/stageprompt.js',
          'node_modules/govuk_frontend_toolkit/javascripts/govuk/show-hide-content.js',
          'node_modules/govuk_frontend_toolkit/javascripts/govuk/analytics/govuk-tracker.js',
          'assets/js/moj/moj.ga-tracker.js',
          'node_modules/govuk_frontend_toolkit/javascripts/govuk/analytics/analytics.js',

          // OPG Scripts
          'assets/js/opg/jquery-plugin-opg-hascrollbar.js',
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
          'assets/js/moj/moj.modules/moj.user-timeout.js',
          'assets/js/moj/moj.modules/moj.sticky-nav.js',
          'assets/js/moj/moj.modules/moj.repeat-application.js',
          'assets/js/moj/moj.modules/moj.dashboard.js',
          'assets/js/moj/moj.modules/moj.ui-behaviour.js',
          'assets/js/moj/moj.modules/moj.applicant.js',
          'assets/js/moj/moj.modules/moj.fees.js',
          'assets/js/moj/moj.modules/moj.who-are-you.js',
          'assets/js/moj/moj.modules/moj.polyfill.js',
          'assets/js/moj/moj.modules/moj.single-use.js',
          'assets/js/moj/moj.modules/moj.analytics.js',
          'assets/js/moj/moj.modules/moj.form-error-tracker.js',
          'assets/js/moj/moj.modules/moj.cookie-consent.js',

          // Init Script
          'assets/js/main.js',
        ],
        dest: 'public/assets/v2/js/application.js',
        nonull: true
      }
    },

    // lint js files
    jshint: {
      options: {
        jshintrc: '.jshintrc',
        ignores: []
      },
      files: [
        'Gruntfile.js',
        'assets/js/moj/**/*.js',
        'assets/js/lpa/**/*.js',
        'assets/js/main.js',
        // ignore compiled handlebars templates
        '!assets/js/lpa/lpa.templates.js'
      ]
    },

    // minify for production
    uglify: {
      options: {
        sourceMap: false
      },
      build: {
        src: 'public/assets/v2/js/application.js',
        dest: 'public/assets/v2/js/application.min.js'
      }
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
  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-scss-lint');
  grunt.loadNpmTasks('grunt-text-replace');
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-cssmin');
  grunt.loadNpmTasks('grunt-contrib-handlebars');
  grunt.loadNpmTasks('grunt-contrib-copy');

  // define tasks
  grunt.registerTask('test', ['scsslint', 'jshint']);
  grunt.registerTask('build_js', ['copy:jsenv', 'handlebars', 'concat', 'uglify']);
  grunt.registerTask('build_js_dev', ['copy:jsenv', 'handlebars', 'concat', 'copy:jsdev']);
  grunt.registerTask('build_css', ['sass', 'replace', 'copy:css', 'cssmin']);
  grunt.registerTask('build', ['build_js', 'build_css']);
};
