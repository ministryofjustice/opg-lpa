module.exports = function (grunt) {
  'use strict';

  // Show execution time of tasks
  require('time-grunt')(grunt);

  // Load all plugins
  require('matchdep').filterDev(['grunt-*', '!grunt-cli']).forEach(grunt.loadNpmTasks);

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    meta: {
      banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
    },

    // Join the files
    concat: {
      dist: {
        options: {
          banner: '<%= meta.banner %>'
        },
        src: [
          // vendor scripts
          'javascript/vendor/handlebars.js',
          'javascript/vendor/lodash-2.4.1.min.js',
          // LPA scripts
          'javascript/stageprompt.2.0.0.js',
          'public/assets/v1/js/jquery-ui-1.10.3.custom/js/jquery-ui-1.10.3.custom.min.js',
          'javascript/jquery-plugin/opg/jquery-plugin-opg-hascrollbar.js',
          'javascript/jquery-plugin/opg/jquery-plugin-opg-spinner.js',
          'javascript/jquery-plugin/jquery-details/jquery.details.min.js',
          // 'javascript/jquery-plugin/tools/jquery.tools.min.js',
          // MOJ style js
          'javascript/moj.js',
          'javascript/moj.helpers.js',
          'javascript/lpa.templates.js',
          'javascript/moj.modules/moj.popup.js',
          'javascript/moj.modules/moj.help-system.js',
          'javascript/moj.modules/moj.form-popup.js',
          'javascript/moj.modules/moj.title-switch.js',
          'javascript/moj.modules/moj.reusables.js',
          'javascript/moj.modules/moj.postcode-lookup.js',
          'javascript/moj.modules/moj.person-form.js',
          'javascript/moj.modules/moj.validation.js',
          'javascript/moj.modules/moj.user-timeout.js',
          'javascript/moj.modules/moj.sticky-nav.js',
          'javascript/moj.modules/moj.repeat-application.js',
          'javascript/main.js',
          'javascript/who-are-you.js',
          'javascript/form.js'
        ],
        dest: 'public/assets/v1/js/application.js',
        nonull: true
      },
      govukStatic: {
        files: {
          'public/assets/v1/js/pwstrength.js': ['javascript/zxcvbn.js', 'javascript/pwstrength.js'],
          'public/assets/v1/js/zxcvbn-async.js': 'javascript/zxcvbn-async.js'
        }
      }
    },

    // Minify for production
    uglify: {
      build: {
        options: {
          banner: '<%= meta.banner %>'
        },
        src: 'public/assets/v1/js/application.js',
        dest: 'public/assets/v1/js/application.min.js'
      },
      jquery: {
        src: 'public/assets/v1/js/jquery-ui-1.10.3.custom/js/jquery-1.9.1.js',
        dest: 'public/assets/v1/js/jquery-ui-1.10.3.custom/js/jquery-1.9.1.min.js'
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
        src: ['javascript/templates/**/*.html'],
        dest: 'javascript/lpa.templates.js'
      }
    },

    // compile sass files
    sass: {
      dist: {
        files: [
          {
            expand: true,
            cwd: 'sass',
            src: ['*.scss'],
            dest: 'public/assets/v1/css',
            ext: '.css'
          }
        ]
      }
    },

    // lint js files
    jshint: {
      options: {
        jshintrc: '.jshintrc',
        ignores: [
          // ignore templates
          '<%= handlebars.compile.dest %>',
          // ignore vendor files
          'javascript/vendor/**',
          // ignore jquery plugins
          // 'javascript/jquery-plugin/**',
          // ignore govuk copies
          'javascript/govukcopies/**'
        ]
      },
      files: [
        'Gruntfile.js',
        'javascript/jquery-plugin/opg/**',
        'javascript/moj.modules/**',
        'javascript/lpa.*.js',
        'javascript/moj*.js',
        'javascript/main.js',
        'javascript/date-picker.js',
        'javascript/help-popup.js',
        'javascript/pwstrength.js',
        'javascript/zfdebug.js',
        'test/**/*.js',
        'test/*.js'
      ]
    },

    dalek: {
      options: {
        reporter: ['console'],
        files: [
          'tests/dalekjs/browser/*_test.js'
        ]
      },
      headless: {
        options: {
          browser: ['phantomjs']
        },
        src: ['<%= dalek.options.files %>']
      },
      chrome: {
        options: {
          browser: ['chrome']
        },
        src: ['<%= dalek.options.files %>']
      },
      firefox: {
        options: {
          browser: ['firefox']
        },
        src: ['<%= dalek.options.files %>']
      }
    },

    // Watch files for changes and run relevant tasks
    watch: {
      styles: {
        files: ['sass/**/*.scss'],
        tasks: ['sass']
      },
      templates: {
        files: ['<%= handlebars.compile.src %>'],
        tasks: ['handlebars']
      },
      scripts: {
        files: ['javascript/**/*.js', 'test/**', '!<%= handlebars.compile.dest %>'],
        tasks: ['concat:dist']
      }
    }
  });

  // Tasks
  grunt.registerTask('default', ['build']);
  grunt.registerTask('dev', ['sass', 'handlebars', 'concat:dist', 'watch']);
  grunt.registerTask('nosass', ['handlebars', 'concat:dist', 'watch']);
  grunt.registerTask('test', ['jshint', 'dalek:headless']);
  grunt.registerTask('build', ['sass', 'handlebars', 'concat', 'uglify']);
};
