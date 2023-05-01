/** @jsx jsx */

import { jsx } from '@emotion/react';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
// import throttle from 'lodash/throttle';
import Select from 'react-select';

export default ( { setForm } ) => {
	const [ formsData, setFormsData ] = useState( [] );
	const query = '';
	const [ loading, setLoading ] = useState( false );

	useEffect( () => {
		fetchForms();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ query ] );

	// const findForm = throttle(
	// 	( value ) => {
	// 		setQuery( value );
	// 	},
	// 	750,
	// 	{ leading: false }
	// );

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
				return {
					value: formEntry.id,
					label: formEntry.title.rendered,
				};
			} ) }
			placeholder={ __( 'Choose a form', 'sureforms' ) }
			// onSearch={ ( value ) => findForm( value.detail ) }
			onChange={ ( value ) => {
				const formData = formsData.find(
					( formEntry ) => formEntry.id === parseInt( value.value )
				);
				setForm( formData );
			} }
		/>
		// <ScSelect
		// 	searchPlaceholder={ __( 'Search for a form…', 'sureforms' ) }
		// 	onScSearch={ ( e ) => findForm( e.detail ) }
		// 	onScChange={ ( e ) => {
		// 		const formData = formsData.find(
		// 			( formEntry ) => formEntry.id === parseInt( e.target.value )
		// 		);
		// 		setForm( formData );
		// 	} }
		// />
	);
};
