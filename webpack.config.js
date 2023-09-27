/* eslint-disable no-unused-vars */
// Load the default @wordpress/scripts config object
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const CopyPlugin = require( 'copy-webpack-plugin' );

const wp_rules = defaultConfig.module.rules.filter( function ( item ) {
	if ( String( item.test ) === String( /\.jsx?$/ ) ) {
		return true;
	}

	if ( String( item.test ) === String( /\.(sc|sa)ss$/ ) ) {
		item.exclude = [ /node_modules/, /editor/ ];
		return true;
	}
	return false;
} );

module.exports = {
	...defaultConfig,
	mode: 'production',
	optimization: {
		usedExports: true,
	},
	plugins: [
		...defaultConfig.plugins,
		new CopyPlugin( {
			patterns: [
				{
					from: path.resolve(
						__dirname,
						'node_modules/@surecart/components/dist/surecart/icon-assets'
					),
					to: path.resolve( __dirname, 'assets/build/icon-assets' ),
				},
			],
		} ),
	],
	entry: {
		formEditor: path.resolve(
			__dirname,
			'assets/src/admin/single-form-settings/Editor.js'
		),
		editor: path.resolve( __dirname, 'assets/src/admin/editor-scripts.js' ),
		tailwindElements: path.resolve(
			__dirname,
			'assets/src/public/scripts/elements.js'
		),
		form_archive_styles: path.resolve(
			__dirname,
			'assets/src/public/styles/form-archive-styles.scss'
		),
		sureforms_frontend_styles: path.resolve(
			__dirname,
			'assets/src/public/styles/sureforms-frontend-ui-styles.scss'
		),
		block_styles: path.resolve(
			__dirname,
			'assets/src/admin/block-styles.scss'
		),
		settings: path.resolve(
			__dirname,
			'assets/src/admin/settings/settings.js'
		),
		page_header: path.resolve(
			__dirname,
			'assets/src/admin/components/PageHeader.js'
		),
		dashboard: path.resolve(
			__dirname,
			'assets/src/admin/dashboard/index.js'
		),
		blocks: path.resolve( __dirname, 'assets/src/blocks/blocks.js' ),
	},
	resolve: {
		alias: {
			...defaultConfig.resolve.alias,
			'@Admin': path.resolve( __dirname, 'assets/src/admin/' ),
			'@Blocks': path.resolve( __dirname, 'assets/src/blocks/' ),
			'@Controls': path.resolve( __dirname, 'assets/src/uagb-controls/' ),
			'@Components': path.resolve( __dirname, 'assets/src/components/' ),
			'@Utils': path.resolve( __dirname, 'assets/src/utils/' ),
			'@Attributes': path.resolve(
				__dirname,
				'assets/src/blocks-attributes/'
			),
		},
	},
	module: {
		rules: [
			//...wp_rules,
			...defaultConfig.module.rules,
			{
				test: /\.(scss|css)$/,
				exclude: [ /node_modules/, /style/, /admin.scss/ ],
				use: [
					{
						loader: 'style-loader',
						options: {
							injectType: 'lazySingletonStyleTag',
							attributes: { id: 'sureforms-editor-styles' },
						},
					},
					'css-loader',
					'sass-loader',
				],
			},
		],
	},
	output: {
		...defaultConfig.output,
		filename: '[name].js',
		path: path.resolve( __dirname, 'assets/build' ),
	},
};
