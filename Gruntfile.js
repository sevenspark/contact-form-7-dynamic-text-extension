module.exports = function(grunt) {

    grunt.initConfig({

        clean: {
            build: {
                // Delete everything in the build folder and all minified JS
                src: ['./_build', './**/*.min.js']
            }
        },
        copy: {
            build: {
                // Copy files from working folder to build folder
                src: ['**', '!**/node_modules/**', '!**/__/**', '!**/wp-assets/**', '!.gitignore', '!package.json', '!package-lock.json', '!README.md', '!Gruntfile.js', '!**/*.report.txt'],
                dest: './_build',
                expand: true
            }
        },
        uglify: {
            // Minify and Compress JavaScript files
            options: {
                banner: '/*! Do not edit, this file is generated automatically - <%= grunt.template.today("yyyy-mm-dd HH:mm:ss Z") %> */',
                mangle: false,
                compress: true
            },
            build: {
                files: [{
                    expand: true, // Enable dynamic expansion
                    cwd: 'assets/scripts/', // Src matches are relative to this path
                    src: ['*.js', '!*.min.js'], // Actual pattern(s) to match (all JS except minified files)
                    dest: './assets/scripts/', // Destination path prefix - generate it in working file, it will be copied to build
                    ext: '.min.js' // Dest filepaths will have this extension
                }]
            }
        },
        wp_deploy: {
            deploy: {
                options: {
                    // Documentation: https://github.com/stephenharris/grunt-wp-deploy
                    plugin_slug: 'contact-form-7-dynamic-text-extension',
                    //svn_user: 'tessawatkinsllc', // WordPress repository username
                    build_dir: './_build', // Relative path to build directory
                    assets_dir: 'wp-assets', // Relative path to assets directory (i.e. banners and screenshots)
                    tmp_dir: '../../_svn', // Location where SVN repository is checked out to (Note: Before the the repository is checked out `<tmp_dir>/<plugin_slug>` is deleted.)
                    deploy_trunk: true, // Set to false to skip committing to the trunk directory (i.e. if only committing to assets)
                    deploy_tag: true // Set to false to skip creating a new tag (i.e. if only committing to trunk or assets)
                }
            }
        }
    })

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-wp-deploy');

    // grunt.registerTask('clean', ['clean:build']); // causes issues due to same task name I believe
    grunt.registerTask('build', ['clean:build', 'uglify:build', 'copy:build']);

    // Deploy
    grunt.registerTask('deploy', ['wp_deploy:deploy']);
};