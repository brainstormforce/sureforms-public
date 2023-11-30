module.exports = function ( grunt ) {
	// Project configuration.
	const sass = require( 'sass' );
	const autoprefixer = require( 'autoprefixer' );

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		sass: {
			options: {
				implementation: sass,
				sourcemap: 'none',
				outputStyle: 'expanded',
				linefeed: 'lf',
			},
			dist: {
				files: [
					/* Common Style */
					{
						expand: true,
						cwd: 'sass/',
						src: [ '**.scss' ],
						dest: 'assets/css/unminified',
						ext: '.css',
					},
				],
			},
		},
		postcss: {
			options: {
				map: false,
				processors: [
					autoprefixer( {
						browsers: [
							'> 1%',
							'ie >= 11',
							'last 1 Android versions',
							'last 1 ChromeAndroid versions',
							'last 2 Chrome versions',
							'last 2 Firefox versions',
							'last 2 Safari versions',
							'last 2 iOS versions',
							'last 2 Edge versions',
							'last 2 Opera versions',
						],
						cascade: false,
					} ),
				],
			},
			style: {
				expand: true,
				src: [ 'assets/css/unminified/*.css' ],
			},
		},
		cssmin: {
			options: {
				keepSpecialComments: 0,
			},
			css: {
				files: [
					// Generated '.min.css' files from '.css' files.
					// NOTE: Avoided '-rtl.css' files.
					{
						expand: true,
						src: [ '**/*.css', '!**/*-rtl.css' ],
						dest: 'assets/css/minified',
						cwd: 'assets/css/unminified',
						ext: '.min.css',
					},

					// Generating RTL files from '/unminified/' into '/minified/'
					// NOTE: Not possible to generate bulk .min-rtl.css files from '.min.css'
					{
						src: 'assets/css/unminified/block-styles-rtl.css',
						dest: 'assets/css/minified/block-styles.min-rtl.css',
					},
					{
						src: 'assets/css/unminified/sureforms-frontend-ui-styles-rtl.css',
						dest: 'assets/css/minified/sureforms-frontend-ui-styles.min-rtl.css',
					},
					{
						src: 'assets/css/unminified/srfm_theme_styles-rtl.css',
						dest: 'assets/css/minified/srfm_theme_styles.min-rtl.css',
					},
					{
						src: 'assets/css/unminified/form-archive-styles-rtl.css',
						dest: 'assets/css/minified/form-archive-styles.min-rtl.css',
					},
					{
						src: 'assets/css/unminified/header-styles-rtl.css',
						dest: 'assets/css/minified/header-styles.min-rtl.css',
					},
					{
						src: 'assets/css/unminified/srfm-form-selector-rtl.css',
						dest: 'assets/css/minified/srfm-form-selector.min-rtl.css',
					},
				],
			},
		},
		rtlcss: {
			options: {
				// rtlcss options
				config: {
					preserveComments: true,
					greedy: true,
				},
				// generate source maps
				map: false,
			},
			dist: {
				files: [
					{
						expand: true,
						cwd: 'assets/css/unminified/',
						src: [ '*.css', '!*-rtl.css' ],
						dest: 'assets/css/unminified',
						ext: '-rtl.css',
					},
				],
			},
		},
		copy: {
			main: {
				options: {
					mode: true,
				},
				src: [
					'**',
					'!.git/**',
					'!.gitignore',
					'!.gitattributes',
					'!*.sh',
					'!*.zip',
					'!eslintrc.json',
					'!README.md',
					'!Gruntfile.js',
					'!package.json',
					'!package-lock.json',
					'!composer.json',
					'!composer.lock',
					'!phpcs.xml',
					'!phpcs.xml.dist',
					'!phpunit.xml.dist',
					'!node_modules/**',
					'!vendor/**',
					'!tests/**',
					'!scripts/**',
					'!config/**',
					'!tests/**',
					'!bin/**',
				],
				dest: 'sureforms/',
			},
		},
		wp_readme_to_markdown: {
			your_target: {
				files: {
					'README.md': 'readme.txt',
				},
			},
		},
		compress: {
			main: {
				options: {
					archive: 'sureforms-<%= pkg.version %>.zip',
					mode: 'zip',
				},
				files: [
					{
						src: [ './sureforms/**' ],
					},
				],
			},
		},
		clean: {
			main: [ 'sureforms' ],
			zip: [ '*.zip' ],
		},
		uglify: {
			js: {
				files: [
					{
						// all .js to min.js
						expand: true,
						src: [ '**.js' ],
						dest: 'assets/src/public/scripts/minified/blocks',
						cwd: 'assets/src/public/scripts/unminified/blocks',
						ext: '.min.js',
					},
				],
			},
		},
	} );

	/* Load Tasks */
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-compress' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-sass' );
	grunt.loadNpmTasks( '@lodder/grunt-postcss' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-rtlcss' );

	/* Read File Generation task */
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );

	// Generate Read me file
	grunt.registerTask( 'readme', [ 'wp_readme_to_markdown' ] );

	// rtlcss
	grunt.registerTask( 'rtl', [ 'rtlcss' ] );

	// SASS compile
	grunt.registerTask( 'scss', [ 'sass' ] );

	// Style
	grunt.registerTask( 'style', [ 'scss', 'postcss:style', 'rtl' ] );

	// min all
	grunt.registerTask( 'minify', [ 'style', 'cssmin:css', 'uglify:js' ] );

	/* Register task started */
	grunt.registerTask( 'release', [
		'clean:zip',
		'copy',
		'compress',
		'clean:main',
	] );
};
