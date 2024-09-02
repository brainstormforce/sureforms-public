module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended-with-formatting' ],
	rules: {
		camelcase: 'off',
		'no-console': 'off',
		'no-alert': 'off',
		'react-hooks/exhaustive-deps': 'off',
		'@wordpress/no-unsafe-wp-apis': 'off',
		'jsx-a11y/label-has-associated-control': 'off',
		'space-before-function-paren': 'off',
		'no-mixed-spaces-and-tabs': 'off',
		'import/no-unresolved': 'off',
		'react/jsx-indent-props': 'off',
		'no-nested-ternary': 'off',
		'react/jsx-indent': 'off',
		'jsx-a11y/click-events-have-key-events': 'off',
		'jsx-a11y/no-static-element-interactions': 'off',
		'jsx-a11y/label-has-for': 'off',
		'jsdoc/check-tag-names': [ 'error', { definedTags: [ 'jsx' ] } ],
		'@wordpress/i18n-text-domain': [
			'error',
			{ allowedTextDomain: 'sureforms' },
		],
		'react/no-unknown-property': [ 'error', { ignore: [ 'css' ] } ],
	},
	parserOptions: {
		requireConfigFile: false,
		babelOptions: {
			presets: [ '@wordpress/babel-preset-default' ],
		},
	},
	globals: {
		alert: true,
		srfm_block_data: true,
		srfm_fields_preview: true,
		confirm: true,
		elementor: true,
		ajaxurl: true,
		formSubmitData: true,
		srfm_blocks_info: true,
		srfm_admin_react: true,
		SRFM_Block_Icons: true,
		__webpack_public_path__: true,
		srfm_deactivate_blocks: true,
		srfm_react: true,
		srfm_data: true,
		bodymovin: true,
		srfm_forms_data: true,
		define: true,
		Cookies: true,
		SRFMTableOfContents: true,
		localStorage: true,
		srfm_timeline_data: true,
		srfm_countdown_data: true,
		SRFMModal: true,
		CustomEvent: true,
		fetch: true,
		Headers: true,
		FormData: true,
		SRFMCounter: true,
		location: true,
		IntersectionObserver: true,
		navigator: true,
		srfm_popup_builder_admin: true,
		SRFMLottie: true,
		AOS: true,
		SRFMForms: true,
		SRFMCountdown: true,
		srfm_carousel_height: true,
		scroll: true,
		srfm_admin: true,
		getComputedStyle: true,
		srfm_spec_blocks_info: true,
		SureForms: true,
		srfm_quick_sidebar_blocks: true,
		srfm_backend: true,
		TomSelect: true,
		root: true,
		srfmElementorData: true,
		elementorFrontend: true,
		loadPageBreak: true,
		screen: true,
		SureTriggers: true,
	},
};
