import { RichText } from '@wordpress/block-editor';
import { stripHTML } from '../util';

export default ( { className, attributes } ) => {
	const { name, checked, value, required, label, description } = attributes;

	return (
		<sc-switch
			class={ className || false }
			name={ name || false }
			checked={ checked || false }
			value={ value || false }
			required={ required || false }
		>
			<RichText.Content value={ label } />
			<span slot="description">{ stripHTML( description ) }</span>
		</sc-switch>
	);
};
