module.exports = {
	content: [
		'./assets/src/**/*.@(js|jsx)',
		'./inc/blocks/**/*.php',
		'./templates/single-form.php',,
		'./node_modules/tw-elements/dist/js/**/*.js',
	],
	theme: {
		extend: {
			colors: {
				wpprimary: 'var(--wp-admin-theme-color)',
				wpcolor: '#2271b1',
				wphovercolor: '#135e96',
				wphoverbgcolor: '#2271b117',
				wpcolorfaded: '#2271b120',
				primary_color: 'var(--primary-color)',
				secondary_color: 'var(--secondary-color)',
				primary_text_color: 'var(--primary-textcolor)',
				required_icon_color: '#EF4444',
			},
			fontFamily: {
				inter: [ '"Inter"', 'sans-serif' ],
			},
			screens: {
				'600px': { min: '600px', max: '781px' },
			},
		},
	},
	variants: {
		extend: {
			borderWidth: [ 'last' ],
		},
	},
	plugins: [
		require( '@tailwindcss/forms' ),
		require( 'tw-elements/dist/plugin.cjs' ),
	],
};
