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
					{
						expand: true,
						cwd: 'sass/',
						src: [ '**.scss' ],
						dest: 'assets/css/unminified',
						ext: '.css',
					},
					{
						expand: true,
						cwd: 'sass/fontend',
						src: [ '**.scss' ],
						dest: 'assets/css/unminified/frontend',
						ext: '.css',
					},
					{
						expand: true,
						cwd: 'sass/backend',
						src: [ '**.scss' ],
						dest: 'assets/css/unminified/backend',
						ext: '.css',
					},
					{
						expand: true,
						cwd: 'sass/blocks/default',
						src: [ '**.scss' ],
						dest: 'assets/css/unminified/blocks/default',
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
				src: [ 'assets/css/unminified/*.css', 'assets/css/unminified/frontend/*.css', 'assets/css/unminified/backend/*.css', 'assets/css/unminified/blocks/default/*.css' ],
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
					{
						src: 'assets/css/unminified/single-rtl.css',
						dest: 'assets/css/minified/single.min-rtl.css',
					},
					{
						src: 'assets/css/unminified/common-rtl.css',
						dest: 'assets/css/minified/common.min-rtl.css',
					},
					{
						src: 'assets/css/unminified/single-form-setting-rtl.css',
						dest: 'assets/css/minified/single-form-setting.min-rtl.css',
					},

					// Generated '.min.css' files from '.css' files.
					// NOTE: Avoided '-rtl.css' files.
					{
						expand: true,
						src: [ '**/*.css', '!**/*-rtl.css' ],
						dest: 'assets/css/minified/blocks/default',
						cwd: 'assets/css/unminified/blocks/default',
						ext: '.min.css',
					},

					// Generating RTL files from '/unminified/' into '/minified/'
					// NOTE: Not possible to generate bulk .min-rtl.css files from '.min.css'
					{
						src: 'assets/css/unminified/blocks/default/frontend-rtl.css',
						dest: 'assets/css/minified/blocks/default/frontend.min-rtl.css',
					},
					{
						src: 'assets/css/unminified/blocks/default/backend-rtl.css',
						dest: 'assets/css/minified/blocks/default/backend.min-rtl.css',
					},
					{
						src: 'assets/css/unminified/template-picker-rtl.css',
						dest: 'assets/css/minified/template-picker.min-rtl.css',
					},
					// Generated '.min.css' files from '.css' files.
					// NOTE: Avoided '-rtl.css' files.
					{
						expand: true,
						src: [ '**/*.css', '!**/*-rtl.css' ],
						dest: 'assets/css/minified/frontend',
						cwd: 'assets/css/unminified/frontend',
						ext: '.min.css',
					},

					// Generating RTL files from '/unminified/' into '/minified/'
					// NOTE: Not possible to generate bulk .min-rtl.css files from '.min.css'
					{
						src: 'assets/css/unminified/frontend/form-rtl.css',
						dest: 'assets/css/minified/frontend/form.min-rtl.css',
					},
					// Generated '.min.css' files from '.css' files.
					// NOTE: Avoided '-rtl.css' files.
					{
						expand: true,
						src: [ '**/*.css', '!**/*-rtl.css' ],
						dest: 'assets/css/minified/backend',
						cwd: 'assets/css/unminified/backend',
						ext: '.min.css',
					},

					// Generating RTL files from '/unminified/' into '/minified/'
					// NOTE: Not possible to generate bulk .min-rtl.css files from '.min.css'
					{
						src: 'assets/css/unminified/backend/editor-rtl.css',
						dest: 'assets/css/minified/backend/editor.min-rtl.css',
					},
					{
						src: 'assets/css/unminified/backend/admin-rtl.css',
						dest: 'assets/css/minified/backend/admin.min-rtl.css',
					},
					{
						src: 'assets/css/unminified/backend/settings-rtl.css',
						dest: 'assets/css/minified/backend/settings.min-rtl.css',
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
					{
						expand: true,
						cwd: 'assets/css/unminified/frontend',
						src: [ '*.css', '!*-rtl.css' ],
						dest: 'assets/css/unminified/frontend',
						ext: '-rtl.css',
					},
					{
						expand: true,
						cwd: 'assets/css/unminified/backend',
						src: [ '*.css', '!*-rtl.css' ],
						dest: 'assets/css/unminified/backend',
						ext: '-rtl.css',
					},
					{
						expand: true,
						cwd: 'assets/css/unminified/blocks/default/',
						src: [ '*.css', '!*-rtl.css' ],
						dest: 'assets/css/unminified/blocks/default',
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
					'!**/README.md',
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
					'!phpstan-baseline.neon',
					'!phpstan.neon',
					'!postcss.config.js',
					'!tailwind.config.js',
					'!webpack.config.js',
					'!node_modules/**',
					'!vendor/**',
					'!tests/**',
					'!scripts/**',
					'!config/**',
					'!tests/**',
					'!bin/**',
					'!artifact/**',
					'!sass/**',
					'!assets/css/unminified/**',
					'!assets/js/unminified/**',
					'!src/**',
					'!modules/gutenberg/scripts/**',
					'!modules/gutenberg/src/**',
					'!modules/gutenberg/node_modules/**',
					'!modules/gutenberg/gutenberg-webpack.config.js',
					'!modules/gutenberg/package-lock.json',
					'!modules/gutenberg/package.json',
					'!modules/gutenberg/postcss.config.js',
					'!modules/gutenberg/readme.txt',
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
						dest: 'assets/js/minified/',
						cwd: 'assets/js/unminified/',
						ext: '.min.js',
					},
					{
						// all .js to min.js
						expand: true,
						src: [ '**.js' ],
						dest: 'assets/js/minified/blocks',
						cwd: 'assets/js/unminified/blocks',
						ext: '.min.js',
					},
				],
			},
		},
		bumpup: {
			options: {
				updateProps: {
					pkg: 'package.json',
				},
			},
			file: 'package.json',
		},
		replace: {
			stable_tag: {
				src: [ 'readme.txt' ],
				overwrite: true,
				replacements: [
					{
						from: /Stable tag:\ .*/g,
						to: 'Stable tag: <%= pkg.version %>',
					},
				],
			},
			plugin_const: {
				src: [ 'sureforms.php' ],
				overwrite: true,
				replacements: [
					{
						from: /UAGB_VER', '.*?'/g,
						to: "SRFM_VER', '<%= pkg.version %>'",
					},
				],
			},
			plugin_function_comment: {
				src: [
					'*.php',
					'**/*.php',
					'!node_modules/**',
					'!php-tests/**',
					'!bin/**',
					'!vendor/**',
				],
				overwrite: true,
				replacements: [
					{
						from: /x.x.x/ig,
						to: '<%=pkg.version %>',
					},
				],
			},
			plugin_main: {
				src: [ 'sureforms.php' ],
				overwrite: true,
				replacements: [
					{
						from: /Version: \bv?(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)(?:-[\da-z-A-Z-]+(?:\.[\da-z-A-Z-]+)*)?(?:\+[\da-z-A-Z-]+(?:\.[\da-z-A-Z-]+)*)?\b/g,
						to: 'Version: <%= pkg.version %>',
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

	/* Version Bump Task */
	grunt.loadNpmTasks( 'grunt-bumpup' );
	grunt.loadNpmTasks( 'grunt-text-replace' );

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

	// Grunt release no clean
	grunt.registerTask( 'release-no-clean', [
		'clean:main',
		'clean:zip',
		'copy:main',
	] );

	// Version Bump `grunt version-bump --ver=<version-number>`
	grunt.registerTask( 'version-bump', function () {
		let newVersion = grunt.option( 'ver' );

		if ( newVersion ) {
			newVersion = newVersion ? newVersion : 'patch';

			grunt.task.run( 'bumpup:' + newVersion );
			grunt.task.run( 'replace' );
		}
	} );
};
