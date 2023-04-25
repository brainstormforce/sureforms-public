/** @jsx jsx */

import { jsx } from '@emotion/react';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

import throttle from 'lodash/throttle';

import { ScSelect } from '@surecart/components-react';

export default ( { form, setForm } ) => {
	const [ formsData, setFormsData ] = useState( [] );
	const [ query, setQuery ] = useState( '' );
	const [ loading, setLoading ] = useState( false );

	useEffect( () => {
		fetchForms();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ query ] );

	const findForm = throttle(
		( value ) => {
			setQuery( value );
		},
		750,
		{ leading: false }
	);

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

	return (
		<ScSelect
			value={ form?.id }
			loading={ loading }
			placeholder={ __( 'Choose a form', 'sureforms' ) }
			searchPlaceholder={ __( 'Search for a form…', 'sureforms' ) }
			search
			onScSearch={ ( e ) => findForm( e.detail ) }
			onScChange={ ( e ) => {
				const formData = formsData.find(
					( formEntry ) => formEntry.id === parseInt( e.target.value )
				);
				setForm( formData );
			} }
			choices={ ( formsData || [] ).map( ( formEntry ) => {
				return {
					value: formEntry.id,
					label: formEntry.title.rendered,
				};
			} ) }
		/>
	);
};
