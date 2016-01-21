module.exports = function (grunt) {
  'use strict';

  grunt.initConfig({

    // watching sass and js (as they need post tasks)
    watch: {
      scss: {
        files: 'assets/sass/**/*.scss',
        tasks: ['sass', 'replace:image_url']
      },
      js: {
        files: 'assets/js/**/*.js',
        tasks: ['concat']
      }
    },

    // sass files to compile
    sass: {
      dev: {
        options: {
          loadPath: [
          'assets/bower/govuk_frontend_toolkit/stylesheets',
          'assets/bower/govuk_template/source/assets/stylesheets',
          'assets/bower/govuk_elements/public/sass',
          ]
        },
        files: {
          'public/assets/v2/css/application.css': 'assets/sass/application.scss',
          'public/assets/v2/css/application-ie8.css': 'assets/sass/application-ie8.scss',
          'public/assets/v2/css/application-ie7.css': 'assets/sass/application-ie7.scss',
          'public/assets/v2/css/application-ie6.css': 'assets/sass/application-ie6.scss',
          'public/assets/v2/css/govuk-template-print.css': 'assets/bower/govuk_template/source/assets/stylesheets/govuk-template-print.scss'
        }
      }
    },

    // lint scss files
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

    // replacing a compass depended helper within govuk template css
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

    // minifying the css
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

    // join the JS files
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

    // minify for production
    uglify: {
      options: {
        sourceMap: true
      },
      build: {
        src: 'public/assets/v2/js/application.js',
        dest: 'public/assets/v2/js/application.min.js'
      }
    },

    // refreshes browser on scss, js & twig changes
    // runs a mini-server on localhost:3000 as a proxy on vagrant box
    browserSync: {
      dev: {
        bsFiles: {
          src: [
            'public/assets/v2/css/application.css',
            'public/assets/v2/js/application.js',
            'module/Application/view/**/*.twig'
          ]
        },
        options: {
          watchTask: true,
          proxy: "https://192.168.33.103/home"
        }
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
  grunt.loadNpmTasks('grunt-browser-sync');
  grunt.loadNpmTasks('grunt-contrib-handlebars');

  // define tasks
  grunt.registerTask('default', ['watch']);
  grunt.registerTask('compile', ['sass', 'replace:image_url']);
  grunt.registerTask('test', ['scsslint', 'jshint']);
  grunt.registerTask('compress', ['cssmin', 'uglify']);
  grunt.registerTask('refresh', ['browserSync', 'watch']);
};