module.exports = function (grunt) {
    grunt.initConfig({
        watch: {
            files: 'assets/sass/*.scss',
            tasks: ['sass']
        },
        sass: {
            dev: {
                files: {
                    'public/assets/v2/css/main.css': 'assets/sass/main.scss'
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