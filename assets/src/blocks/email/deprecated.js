export default [
	{
		attributes: {
			value: {
				type: 'string',
				default: '',
			},
			placeholder: {
				type: 'string',
				default: '',
			},
			disabled: {
				type: 'boolean',
				default: false,
			},
			size: {
				type: 'string',
				default: '',
			},
			label: {
				type: 'string',
				default: 'Email',
			},
			help: {
				type: 'string',
			},
			inputmode: {
				type: 'string',
			},
			max: {
				type: ['string', 'number'],
			},
			maxlength: {
				type: 'number',
			},
			name: {
				type: 'string',
			},
		},
		save({ attributes, className }) {
			const { label, help, autofocus, placeholder, showLabel, size } =
				attributes;

			return (
				<sc-customer-email
					class={className || false}
					label={label || false}
					help={help || false}
					autofocus={autofocus || false}
					autocomplete={'email'}
					inputmode={'email'}
					placeholder={placeholder || false}
					showLabel={showLabel || false}
					size={size || false}
					required="1"
				></sc-customer-email>
			);
		},
	},
];
