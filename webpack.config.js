// Load the default @wordpress/scripts config object
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	mode: 'production',
	optimization: {
		usedExports: true,
	},
	entry: {
		formEditor: path.resolve(
			__dirname,
			'assets/src/admin/form/editor.js'
		),
		editor: path.resolve( __dirname, 'assets/src/admin/editor-scripts.js' ),
		admin: path.resolve( __dirname, 'assets/src/admin/admin.scss' ),
		block_styles: path.resolve(
			__dirname,
			'assets/src/admin/block-styles.scss'
		),
		settings: path.resolve(
			__dirname,
			'assets/src/admin/settings/settings.js'
		),
		blocks: path.resolve( __dirname, 'assets/src/blocks/blocks.js' ),
	},
	resolve: {
		alias: {
			...defaultConfig.resolve.alias,
			'@Admin': path.resolve( __dirname, 'assets/src/admin/' ),
			'@Blocks': path.resolve( __dirname, 'assets/src/blocks/' ),
		},
	},
	output: {
		...defaultConfig.output,
		filename: '[name].js',
		path: path.resolve( __dirname, 'assets/build' ),
	},
	plugins: [ ...defaultConfig.plugins ],
};
