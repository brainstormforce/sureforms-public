/** @jsx jsx */

import { jsx } from '@emotion/react';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import Select from 'react-select';

export default ( { setForm } ) => {
	const [ formsData, setFormsData ] = useState( [] );
	const query = '';
	const [ loading, setLoading ] = useState( false );

	useEffect( () => {
		fetchForms();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ query ] );

	const fetchForms = async () => {
		let response;
		try {
			setLoading( true );
			response = await apiFetch( {
				path: addQueryArgs( 'wp/v2/sureforms_form', {
					search: query,
				} ),
			} );
			setFormsData( response );
		} finally {
			setLoading( false );
		}
	};

	const customStyles = {
		control: ( provided ) => ( {
			...provided,
			width: 'fit-content',
		} ),
	};

	return (
		<Select
			styles={ customStyles }
			loading={ loading }
			isSearchable
			options={ ( formsData || [] ).map( ( formEntry ) => {
				const originalTitle = formEntry.title.rendered;
				const replacedTitle = originalTitle.replace( /&#8211;/g, '-' ); // Replace "&#8211;" with "-"

				return {
					value: formEntry.id,
					label:
						originalTitle === '' ? 'Untitled Form' : replacedTitle,
				};
			} ) }
			placeholder={ __( 'Choose a form', 'sureforms' ) }
			onChange={ ( value ) => {
				const formData = formsData.find(
					( formEntry ) => formEntry.id === parseInt( value.value )
				);
				setForm( formData );
			} }
			className="sureforms-select-form"
			classNamePrefix="sureforms-select"
		/>
	);
};
