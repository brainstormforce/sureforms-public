/**
 * WordPress dependencies
 */
import { Spinner, Placeholder } from '@wordpress/components';

import Empty from './components/Empty';
import Edit from './components/Edit';
import { FieldsPreview } from '../FieldsPreview.jsx';

export default ( { attributes, setAttributes } ) => {
	// TODO: Let's store a unique hash in both meta and attribute to find.
	const { id, preview, loading } = attributes;

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = fieldsPreview.sureforms_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	if ( loading ) {
		return (
			<Placeholder>
				<Spinner />
			</Placeholder>
		);
	}

	if ( ! id ) {
		return (
			<Empty attributes={ attributes } setAttributes={ setAttributes } />
		);
	}

	return <Edit attributes={ attributes } setAttributes={ setAttributes } />;
};
