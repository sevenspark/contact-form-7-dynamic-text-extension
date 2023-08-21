module.exports = function(grunt) {

    grunt.initConfig({

        // Read package data
        pkg: grunt.file.readJSON('package.json'),
        clean: {
            build: {
                src: [
                    // Delete everything in the build folder
                    './_build',
                    // Delete minified files and source maps from working directory
                    './assets/scripts/*.min.js', './assets/scripts/*.js.map',
                    './assets/styles/*.min.css', './assets/styles/*.css.map'
                ]
            }
        },
        cssmin: {
            /**
             * Minify & Compress Stylesheet Files
             *
             * @see https://github.com/gruntjs/grunt-contrib-cssmin
             */
            options: {
                report: 'min', // Minimal reporting
                sourceMap: false // Disable sourcemap creation
            },
            target: {
                files: [{
                    expand: true, // Enable dynamic expansion
                    cwd: 'assets/styles/', // Src matches are relative to this path
                    src: ['*.css', '!*.min.css'], // Actual pattern(s) to match (all CSS except minified files)
                    dest: 'assets/styles/', // Destination path prefix - generate it in working file, it will be copied to build
                    ext: '.min.css' // Dest filepaths will have this extension
                }]
            }
        },
        uglify: {
            /**
             * Minify & Compress JavaScript Files
             *
             * @see https://www.npmjs.com/package/grunt-contrib-uglify
             * @see https://github.com/mishoo/UglifyJS#compress-options
             */
            options: {
                banner: '/*! Do not edit, this file is generated automatically - <%= grunt.template.today("yyyy-mm-dd HH:mm:ss Z") %> */',
                compress: {
                    drop_console: true, // discard calls to the browser console
                    unused: false // Keep unreferenced functions and variables
                },
                mangle: {
                    eval: true, // Mangle variable names in scopes where `eval` or `with` are used
                    toplevel: false // Do not mangle names declared in teh top level scope
                },
                sourceMap: false // Disable sourcemap creation
            },
            build: {
                files: [{
                    expand: true, // Enable dynamic expansion
                    cwd: 'assets/scripts/', // Src matches are relative to this path
                    src: ['*.js', '!*.min.js'], // Actual pattern(s) to match (all JS except minified files)
                    dest: 'assets/scripts/', // Destination path prefix - generate it in working file, it will be copied to build
                    ext: '.min.js' // Dest filepaths will have this extension
                }]
            }
        },
        replace: {
            /**
             * Update Version Number
             *
             * @see https://www.npmjs.com/package/grunt-text-replace
             */
            build: {
                src: ['_build/readme.txt', '_build/<%= pkg.name %>.php'], // Source files array (supports minimatch)
                overwrite: true, // Overwrite matched source files
                replacements: [{
                    from: 'VERSION_PLACEHOLDER',
                    to: "<%= pkg.version %>"
                }]
            }
        },
        copy: {
            build: {
                // Copy files from working folder to build folder
                expand: true, // Enable dynamic expansion
                src: [
                    // Copy everything to the build folder except...
                    '**',
                    // Exclude GIT
                    '!**/__/**', '!**/node_modules/**', '!.gitignore', '!package.json', '!package-lock.json', '!README.md', '!Gruntfile.js',
                    // Exclude WordPress Assets
                    '!**/_wp-assets/**',
                    // Exclude any zip files
                    '!*.zip'
                ],
                dest: './_build'
            }
        },
        wp_deploy: {
            /**
             * Deploy to the WordPress Repository
             *
             * @see https://github.com/stephenharris/grunt-wp-deploy
             */
            deploy: {
                options: {
                    plugin_slug: '<%= pkg.name %>', // Plugin's slug as indicated by its repository url in https://wordpress.org/plugins/
                    //svn_user: 'tessawatkinsllc', // WordPress repository username
                    build_dir: './_build', // Relative path to build directory
                    assets_dir: './_wp-assets', // Relative path to assets directory (i.e. banners and screenshots)
                    tmp_dir: '../../_svn', // Location where SVN repository is checked out to (Note: Before the the repository is checked out `<tmp_dir>/<plugin_slug>` is deleted.)
                    deploy_trunk: true, // Set to false to skip committing to the trunk directory (i.e. if only committing to assets)
                    deploy_tag: true // Set to false to skip creating a new tag (i.e. if only committing to trunk or assets)
                }
            }
        }
    });

    // Load Tasks
    grunt.loadNpmTasks('grunt-contrib-clean'); // Delete files
    grunt.loadNpmTasks('grunt-contrib-cssmin'); // Minify CSS files
    grunt.loadNpmTasks('grunt-contrib-uglify'); // Minify JS files
    grunt.loadNpmTasks('grunt-text-replace'); // Replace version in files
    grunt.loadNpmTasks('grunt-contrib-copy'); // Copy files
    grunt.loadNpmTasks('grunt-wp-deploy'); // Deploy to WordPress SVN

    // Register Tasks
    grunt.registerTask('build', ['clean:build', 'cssmin', 'uglify:build', 'copy:build', 'replace:build']); // Register build task, usage: `grunt build`
    grunt.registerTask('deploy', ['wp_deploy:deploy']); // Register deploy task, usage `grunt wp_deploy`
};