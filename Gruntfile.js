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
				silenceDeprecations: [
					'legacy-js-api',
					'import',
					'global-builtin',
				],
			},
			dist: {
				files: [
					{
						expand: true,
						cwd: 'sass/',
						// excluding components folder as all its files are combined in frontend.css
						// excluding utils folder as all it gets imported in multiple file wherever required.
						src: [ '**/*.scss', '!blocks/default/components/**/*.scss', '!blocks/default/utils/**/*.scss' ],
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
						overrideBrowserslist: [
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
				src: [ 'assets/css/unminified/**/*.css' ],
			},
		},
		cssmin: {
			options: {
				keepSpecialComments: 0,
			},
			css: {
				files: [
					// Generated '.min.css' files from '.css' files.
					{
						expand: true,
						src: [ '**/*.css' ],
						dest: 'assets/css/minified',
						cwd: 'assets/css/unminified',
						ext: '.css',
						rename( dest, src ) {
							if ( src.indexOf( '-rtl.css' ) !== -1 ) {
								return dest + '/' + src.replace( '-rtl.css', '.min-rtl.css' );
							}
							return dest + '/' + src.replace( '.css', '.min.css' );
						},
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
						src: [ '**/*.css', '!**/*-rtl.css' ],
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

					// Dotfiles and directories
					'!.claude/**',
					'!.abilities-audit/**',
					'!.git/**',
					'!.github/**',
					'!.wordpress-org/**',
					'!.vscode/**',
					'!.scripts/**',
					'!.playwright-mcp/**',
					'!.gitignore',
					'!.gitattributes',
					'!.distignore',
					'!.editorconfig',
					'!.gitlab-ci.yml',
					'!.travis.yml',
					'!.DS_Store',
					'!.eslintrc.js',
					'!.eslintignore',
					'!.prettierrc',
					'!.prettierrc.js',
					'!.prettierignore',
					'!.browserslistrc',
					'!.stylelintrc',
					'!.stylelintrc.json',
					'!.stylelintignore',
					'!.wp-env.json',
					'!.babelrc',
					'!.nvmrc',
					'!.phpunit.result.cache',
					'!.dccache',
					'!.env',
					'!.env.*',

					// Recursive wildcard patterns (match at any depth)
					'!**/*.sh',
					'!**/*.zip',
					'!**/*.md',
					'!**/*.sql',
					'!**/*.tar.gz',
					'!**/*.map',
					'!**/*.neon',
					'!**/*.config.js',

					// Config and build files
					'!eslintrc.json',
					'!Gruntfile.js',
					'!**/package.json',
					'!**/package-lock.json',
					'!**/yarn.lock',
					'!**/composer.json',
					'!**/composer.lock',
					'!phpcs.xml',
					'!phpcs.xml.dist',
					'!phpcs-report.xml',
					'!phpunit.xml',
					'!phpunit.xml.dist',
					'!multisite.xml',
					'!multisite.xml.dist',
					'!wp-cli.local.yml',
					'!codesniffer.ruleset.xml',
					'!jsconfig.json',
					'!Thumbs.db',
					'!phpinsights.php',
					'!auth.json',
					'!credentials.json',

					// Directories
					'!node_modules/**',
					'!vendor/**',
					'!tests/**',
					'!scripts/**',
					'!config/**',
					'!bin/**',
					'!artifact/**',
					'!artifacts/**',
					'!sass/**',
					'!src/**',
					'!docs/**',
					'!internal-docs/**',
					'!admin/assets/src/**',
					'!coverage-report/**',
					'!playwright-setup/**',
					'!playwright-report/**',
					'!**/unminified/**',

					// Gutenberg module dev files
					'!modules/gutenberg/scripts/**',
					'!modules/gutenberg/src/**',
					'!modules/gutenberg/node_modules/**',
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
						src: [ '**/*.js' ],
						dest: 'assets/js/minified/',
						cwd: 'assets/js/unminified/',
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
