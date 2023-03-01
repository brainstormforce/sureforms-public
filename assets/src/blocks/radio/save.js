import { RichText } from '@wordpress/block-editor';

export default ({ className, attributes }) => {
	const { name, checked, value, label } = attributes;

	return (
		<sc-radio
			class={className || false}
			name={name || false}
			checked={checked || false}
			value={value || false}
		>
			<RichText.Content value={label} />
		</sc-radio>
	);
};
