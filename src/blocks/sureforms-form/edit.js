/**
 * WordPress dependencies
 */
import { Spinner, Placeholder } from '@wordpress/components';

import Empty from './components/Empty';
import Edit from './components/Edit';

export default ( { attributes, setAttributes } ) => {
	const { id, loading } = attributes;

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
