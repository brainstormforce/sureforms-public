const deprecated = [
	{
		save({ attributes, className }) {
			const { name, checked, value, required, label } = attributes;

			return (
				<sc-checkbox
					class={className || false}
					name={name || false}
					checked={checked || false}
					value={value || false}
					required={required || false}
				>
					{label}
				</sc-checkbox>
			);
		},
	},
];

export default deprecated;
