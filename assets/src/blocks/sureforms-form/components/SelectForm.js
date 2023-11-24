/** @jsx jsx */

import { jsx } from '@emotion/react';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Select from 'react-select';
import { addQueryArgs } from '@wordpress/url';

export default ( { setForm, setFormId } ) => {
	const [ formsData, setFormsData ] = useState( [] );
	const [ loading, setLoading ] = useState( false );

	useEffect( () => {
		fetchForms();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	const fetchForms = async () => {
		let response;
		try {
			setLoading( true );
			response = await apiFetch( {
				path: 'sureforms/v1/forms-data',
			} );
			setFormsData( response );
		} finally {
			setLoading( false );
		}
	};

	const getFormMarkup = async ( queryParams ) => {
		let response;
		try {
			setLoading( true );
			response = await apiFetch( {
				path: addQueryArgs(
					'sureforms/v1/generate-form-markup',
					queryParams
				),
			} );
			return response;
		} finally {
			setLoading( false );
		}
	};

	const selectFormStyles = {
		control: ( provided, isSelected ) => ( {
			...provided,
			width: isSelected && '250px',
		} ),
	};

	return (
		<Select
			styles={ selectFormStyles }
			loading={ loading }
			isSearchable
			options={ ( formsData || [] ).map( ( formEntry ) => {
				const originalTitle = formEntry.title;
				const replacedTitle = originalTitle.replace( /&#8211;/g, '-' ); // Replace "&#8211;" with "-"

				return {
					value: formEntry.id,
					label:
						originalTitle === '' ? 'Untitled Form' : replacedTitle,
				};
			} ) }
			placeholder={ __( 'Choose a form', 'sureforms' ) }
			onChange={ ( value ) => {
				const queryParams = { id: value.value };
				const formMarkup = getFormMarkup( queryParams );
				setFormId( value.value );
				setForm( formMarkup );
			} }
			className="srfm-select-form"
			classNamePrefix="srfm-select"
		/>
	);
};
