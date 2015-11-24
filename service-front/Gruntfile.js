module.exports = function (grunt) {
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
    scsslint: {
      allFiles: [
        'assets/sass/*.scss',
      ],
      options: {
        config: null,
        reporterOutput: null,
        colorizeOutput: true
      },
    }
  });

  // load npm tasks
  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-scss-lint');
  grunt.loadNpmTasks('grunt-text-replace');

  // define default task
  grunt.registerTask('default', ['watch']);
  grunt.registerTask('build', ['sass', 'replace:image_url']);
};