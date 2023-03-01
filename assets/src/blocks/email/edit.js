/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Component Dependencies
 */
import { ScInput } from '@surecart/components-react';

import Settings from './settings';

export default ({ className, attributes, setAttributes }) => {
	const { label, placeholder, help } = attributes;

	return (
		<>
		<Settings attributes={attributes} setAttributes={setAttributes} />
		<div className={className}>
			<ScInput
				type="email"
				label={label}
				placeholder={placeholder}
				help={help}
				required
			></ScInput>
		</div>
		</>
	);
};
