/**
 * WordPress dependencies.
 */
import { InnerBlocks } from '@wordpress/block-editor';

export default ({ attributes }) => {
	const { label, required } = attributes;
	return (
		<sc-radio-group label={label} required={required ? '1' : null}>
			<InnerBlocks.Content />
		</sc-radio-group>
	);
};
