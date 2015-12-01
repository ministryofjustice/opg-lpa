module.exports = function (grunt) {
  'use strict';

  grunt.initConfig({
    watch: {
      scss: {
        files: 'assets/**/*.scss',
        tasks: ['sass', 'replace:image_url']
      }
    },
    sass: {
      dev: {
        options: {
          loadPath: [
          'assets/bower/govuk_elements/govuk/public/sass',
          'assets/bower/govuk_elements/public/sass'
          ]
        },
        files: {
          'public/assets/v2/css/application.css': 'assets/sass/application.scss',
          'public/assets/v2/css/application-ie8.css': 'assets/sass/application-ie8.scss',
          'public/assets/v2/css/application-ie7.css': 'assets/sass/application-ie7.scss',
          'public/assets/v2/css/application-ie6.css': 'assets/sass/application-ie6.scss',
          'public/assets/v2/css/govuk-template.css': 'assets/bower/govuk_template/source/assets/stylesheets/govuk-template.scss',
          'public/assets/v2/css/govuk-template-ie8.css': 'assets/bower/govuk_template/source/assets/stylesheets/govuk-template-ie8.scss',
          'public/assets/v2/css/govuk-template-ie7.css': 'assets/bower/govuk_template/source/assets/stylesheets/govuk-template-ie7.scss',
          'public/assets/v2/css/govuk-template-ie6.css': 'assets/bower/govuk_template/source/assets/stylesheets/govuk-template-ie6.scss',
          'public/assets/v2/css/govuk-template-print.css': 'assets/bower/govuk_template/source/assets/stylesheets/govuk-template-print.scss'
        }
      }
    },

    scsslint: {
      allFiles: [
        'assets/sass/*.scss'
      ],
      options: {
        config: null,
        reporterOutput: null,
        colorizeOutput: true
      }
    },

    replace: {
      image_url: {
        src: ['public/assets/v2/css/*.css'],
        dest: 'public/assets/v2/css/',
        replacements: [{
          from: 'image-url',
          to: 'url'
        }]
      }
    },

    // Minifying the css
    cssmin: {
      options: {
        sourceMap: true
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

    // Join the JS files
    concat: {
      options: {
        sourceMap: true
      },
      dist: {
        src: [
          // Vendor Scripts
          'assets/js/vendor/handlebars.js',
          'assets/js/vendor/lodash-2.4.1.min.js',

          // GOVUK Scripts
          'assets/bower/stageprompt/script/stageprompt.js',

          // UI Framework
          'assets/js/ui_framework/jquery-ui-1.10.3.custom/js/jquery-ui-1.10.3.custom.min.js',

          // OPG Scripts
          'assets/js/opg/jquery-plugin-opg-hascrollbar.js',
          'assets/js/opg/jquery-plugin-opg-spinner.js',

          // Polyfills
          'assets/js/polyfills/jquery-details/jquery.details.min.js',

          // MoJ Scripts - Base
          'assets/js/moj/moj.js',
          'assets/js/moj/moj.helpers.js',

          // LPA Scripts - Templates
          'assets/js/lpa/lpa.templates.js',

          // MoJ Scripts - Modules
          'assets/js/moj/moj.modules/moj.popup.js',
          'assets/js/moj/moj.modules/moj.help-system.js',
          'assets/js/moj/moj.modules/moj.form-popup.js',
          'assets/js/moj/moj.modules/moj.title-switch.js',
          'assets/js/moj/moj.modules/moj.reusables.js',
          'assets/js/moj/moj.modules/moj.postcode-lookup.js',
          'assets/js/moj/moj.modules/moj.person-form.js',
          'assets/js/moj/moj.modules/moj.validation.js',
          'assets/js/moj/moj.modules/moj.user-timeout.js',
          'assets/js/moj/moj.modules/moj.sticky-nav.js',
          'assets/js/moj/moj.modules/moj.repeat-application.js',

          // LPA Scripts
          'assets/js/main.js',
          'assets/js/lpa/who-are-you.js',
          'assets/js/lpa/form.js'
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
        'assets/js/main.js'
      ]
    },

    // Minify for production
    uglify: {
      options: {
        sourceMap: true
      },
      build: {
        src: 'public/assets/v2/js/application.js',
        dest: 'public/assets/v2/js/application.min.js'
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

  // define default task
  grunt.registerTask('default', ['watch']);
  grunt.registerTask('build', ['sass', 'replace:image_url']);
  grunt.registerTask('test', ['scsslint', 'jshint']);
};