const withTW = require( '@bsf/force-ui/withTW' );

module.exports = withTW( {
	content: [ './src/**/*.{js,jsx}' ],
	theme: {},
	variants: {
		extend: {
			borderWidth: [ 'last' ],
		},
	},
	plugins: [ require( '@tailwindcss/forms' ) ],
	corePlugins: {
		preflight: false,
	},
	important: '#srfm-dashboard-container',
} );
