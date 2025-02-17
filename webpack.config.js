/* eslint-disable no-unused-vars */
// Load the default @wordpress/scripts config object
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const CopyPlugin = require( 'copy-webpack-plugin' );
const webpack = require( 'webpack' );

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
		new webpack.optimize.LimitChunkCountPlugin( {
			maxChunks: 1,
		} ),
	],
	entry: {
		formEditor: path.resolve(
			__dirname,
			'src/admin/single-form-settings/Editor.js'
		),
		formSubmit: path.resolve(
			__dirname,
			'assets/js/unminified/form-submit.js'
		),
		quickActionSidebar: path.resolve(
			__dirname,
			'./modules/quick-action-sidebar/index.js'
		),
		editor: path.resolve( __dirname, 'src/admin/editor-scripts.js' ),
		settings: path.resolve( __dirname, 'src/admin/settings/settings.js' ),
		templatePicker: path.resolve(
			__dirname,
			'src/admin/components/template-picker/TemplatePicker.js'
		),
		page_header: path.resolve(
			__dirname,
			'src/admin/components/PageHeader.js'
		),
		dashboard: path.resolve( __dirname, 'src/admin/dashboard/index.js' ),
		blocks: path.resolve( __dirname, 'src/blocks/blocks.js' ),
		entries: path.resolve( __dirname, 'src/admin/entries/index.js' ),
	},
	resolve: {
		alias: {
			...defaultConfig.resolve.alias,
			'@Admin': path.resolve( __dirname, 'src/admin/' ),
			'@Blocks': path.resolve( __dirname, 'src/blocks/' ),
			'@Controls': path.resolve( __dirname, 'src/srfm-controls/' ),
			'@Components': path.resolve( __dirname, 'src/components/' ),
			'@Utils': path.resolve( __dirname, 'src/utils/' ),
			'@Svg': path.resolve( __dirname, 'assets/svg/' ),
			'@Attributes': path.resolve( __dirname, 'src/blocks-attributes/' ),
			'@Image': path.resolve( __dirname, 'images/' ),
			'@IncBlocks': path.resolve( __dirname, 'inc/blocks/' ),
		},
	},
	module: {
		rules: [
			//...wp_rules,
			...defaultConfig.module.rules,
			{
				test: /\.(scss|css)$/,
				exclude: [ /node_modules/, /style/, /admin.scss/, /tw-base.scss/ ],
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
		path: path.resolve( __dirname, 'assets/build' ),
	},
};
