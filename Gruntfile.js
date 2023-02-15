module.exports = function (grunt) {

    grunt.initConfig({

        clean: {
            build: {
                src: ['./_build'],
            },
        },
        copy: {
            build: {
                src: ['**', '!**/node_modules/**', '!**/__/**', '!**/wp-assets/**', '!.gitignore', '!package.json', '!package-lock.json', '!README.md', '!Gruntfile.js', '!**/*.report.txt'],
                dest: './_build',
                expand: true,
            },
        },
        wp_deploy: {
            deploy: {
                options: {
                    plugin_slug: 'contact-form-7-dynamic-text-extension',
                    // svn_user: 'sevenspark',
                    build_dir: './_build', //relative path to your build directory
                    assets_dir: 'wp-assets', //relative path to your assets directory (optional).
                    tmp_dir: '../../tmp',
                },
            }
        },
    })


    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-wp-deploy');

    // grunt.registerTask('clean', ['clean:build']); // causes issues due to same task name I believe
    grunt.registerTask('build', ['clean:build', 'copy:build']);

    // Deploy
    grunt.registerTask('deploy', ['wp_deploy:deploy']);
};
