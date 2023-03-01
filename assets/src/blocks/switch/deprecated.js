/**
 * WordPress dependencies.
 */

export default [
	{
		attributes: {
			required: {
				type: 'boolean',
			},
			name: {
				type: 'string',
				default: 'switch',
			},
			value: {
				type: 'string',
				default: 'on',
			},
			label: {
				type: 'string',
				default: 'Switch',
			},
			description: {
				type: 'string',
			},
			help: {
				type: 'string',
			},
			checked: {
				type: 'boolean',
			},
		},
		save({ attributes }) {
			const { name, checked, required, label, description, className } =
				attributes;

			return (
				<sc-switch
					class={className || false}
					name={name || false}
					checked={checked || false}
					required={required || false}
				>
					{label}
					<span slot="description">{description}</span>
				</sc-switch>
			);
		},
	},
];
