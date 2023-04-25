module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended-with-formatting' ],
	rules: {
		camelcase: 'off',
		'no-console': 'off',
		'no-alert': 'off',
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
	},
	parserOptions: {
		requireConfigFile: false,
		babelOptions: {
			presets: [ '@wordpress/babel-preset-default' ],
		},
	},
	globals: {
		alert: true,
		sfBlockData: true,
		confirm: true,
		jQuery: true,
		elementor: true,
		ajaxurl: true,
		navigator: true,
		upload_field: true,
	},
};
