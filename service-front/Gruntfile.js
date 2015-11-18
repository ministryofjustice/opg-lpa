module.exports = function (grunt) {
    grunt.initConfig({
        watch: {
            scss: {
                files: 'assets/**/*.scss',
                tasks: ['sass']
            }
        },
        sass: {
            dev: {
                options: {
                    loadPath: 'assets/bower/govuk_elements/govuk/public/sass'
                },
                files: {
                    'public/assets/v2/css/main.css': 'assets/sass/main.scss',
                    'public/assets/v2/css/govuk-template.css': 'assets/bower/govuk_template/source/assets/stylesheets/govuk-template.scss',
                    'public/assets/v2/css/govuk-template-ie8.css': 'assets/bower/govuk_template/source/assets/stylesheets/govuk-template-ie8.scss',
                    'public/assets/v2/css/govuk-template-ie7.css': 'assets/bower/govuk_template/source/assets/stylesheets/govuk-template-ie7.scss',
                    'public/assets/v2/css/govuk-template-ie6.css': 'assets/bower/govuk_template/source/assets/stylesheets/govuk-template-ie6.scss',
                    'public/assets/v2/css/govuk-template-print.css': 'assets/bower/govuk_template/source/assets/stylesheets/govuk-template-print.scss'
                }
            }
        }
    });

    // load npm tasks
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-watch');

    // define default task
    grunt.registerTask('default', ['watch']);
};