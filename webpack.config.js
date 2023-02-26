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
		editor: path.resolve( __dirname, 'assets/src/admin/editor-scripts.js' ),
	},
	resolve: {
		alias: {
			...defaultConfig.resolve.alias,
			'@Admin': path.resolve( __dirname, 'assets/src/admin/' ),
		},
	},
	output: {
		...defaultConfig.output,
		filename: '[name].js',
		path: path.resolve( __dirname, 'assets/build' ),
	},
	plugins: [
		...defaultConfig.plugins
	],
};
