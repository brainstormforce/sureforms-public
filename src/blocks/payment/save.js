/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Save Component
 */
export default function save() {
	const blockProps = useBlockProps.save();

	return <div { ...blockProps }></div>;
}
