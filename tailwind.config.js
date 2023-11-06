module.exports = {
	content: [
		'./assets/src/**/*.@(js|jsx)',
		'./inc/blocks/**/*.php',
		'./inc/fields/**/*.php',
		'./templates/single-form.php',
		'./inc/fields/**/*.php',
	],
	theme: {
		extend: {
			colors: {
				wpprimary: 'var(--wp-admin-theme-color)',
				wpcolor: '#2271b1',
				wphovercolor: '#135e96',
				wphoverbgcolor: '#2271b117',
				wpcolorfaded: '#2271b120',
				srfm_primary_color: 'var(--srfm-primary-color)',
				srfm_secondary_color: 'var(--srfm-secondary-color)',
				srfm_primary_text_color: 'var(--srfm-primary-text-color)',
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
	],
	// prefix: 'srfm-',
	corePlugins: {
		preflight: false,
	},
};
