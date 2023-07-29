module.exports = function(grunt) {

    grunt.initConfig({

        clean: {
            build: {
                src: ['./_build', './**/*.min.js'],
            },
        },
        copy: {
            build: {
                src: ['**', '!**/node_modules/**', '!**/__/**', '!**/wp-assets/**', '!.gitignore', '!package.json', '!package-lock.json', '!README.md', '!Gruntfile.js', '!**/*.report.txt'],
                dest: './_build',
                expand: true,
            },
        },
        uglify: {
            options: {
                banner: '/*! Do not edit, this file is generated automatically - <%= grunt.template.today("yyyy-mm-dd HH:mm:ss Z") %> */',
                mangle: false,
                compress: true
            },
            build: {
                files: [{
                    expand: true, // Enable dynamic expansion
                    cwd: 'assets/scripts/', // Src matches are relative to this path
                    src: ['*.js'], // Actual pattern(s) to match
                    dest: './assets/scripts/', // Destination path prefix - generate it in working file, it will be copied to build
                    ext: '.min.js', // Dest filepaths will have this extension
                }]
            }
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

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-wp-deploy');

    // grunt.registerTask('clean', ['clean:build']); // causes issues due to same task name I believe
    grunt.registerTask('build', ['clean:build', 'uglify:build', 'copy:build']);

    // Deploy
    grunt.registerTask('deploy', ['wp_deploy:deploy']);
};