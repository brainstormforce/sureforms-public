module.exports = {
	content: [ './assets/src/**/*.@(js|jsx)' ],
	theme: {
		extend: {
			colors: {
				wpcolor: '#2271b1',
				wphovercolor: '#135e96',
				wphoverbgcolor: '#2271b117',
				wpcolorfaded: '#2271b120',
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
};
