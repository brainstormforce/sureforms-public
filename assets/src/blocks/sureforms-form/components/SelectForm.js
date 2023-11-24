import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useRef, useState } from '@wordpress/element';

const SelectForm = ( { label, id, selectedVal, handleChange, setForm } ) => {
	const [ formsData, setFormsData ] = useState( [] );
	const [ query, setQuery ] = useState( '' );
	const [ isOpen, setIsOpen ] = useState( false );
	const inputRef = useRef( null );

	// Fetch forms data.
	const fetchForms = async () => {
		let response;
		try {
			response = await apiFetch( {
				path: 'sureforms/v1/forms-data',
			} );
			setFormsData( response );
		} catch ( error ) {
			console.log( error );
		}
	};

	useEffect( () => {
		fetchForms();
		document.addEventListener( 'click', toggle );
		return () => document.removeEventListener( 'click', toggle );
	}, [] );

	const selectOption = ( option ) => {
		setQuery( () => '' );
		handleChange( option[ label ] );
		setIsOpen( () => ! isOpen );
	};

	function toggle( e ) {
		setIsOpen( e && e.target === inputRef.current );
	}

	const getDisplayValue = () => {
		if ( query ) {
			return query;
		}
		if ( selectedVal ) {
			return selectedVal;
		}
		return '';
	};

	const filter = () => {
		return formsData.filter(
			( option ) =>
				option[ label ].toLowerCase().indexOf( query.toLowerCase() ) >
				-1
		);
	};

	return (
		<div className="srfm-form-select-dropdown">
			<div>
				<div className="srfm-form-select-value">
					<input
						ref={ inputRef }
						type="text"
						value={ getDisplayValue() }
						name="searchTerm"
						onChange={ ( e ) => {
							setQuery( e.target.value );
							handleChange( null );
						} }
						onClick={ toggle }
						placeholder={ __( 'Select a SureForm', 'sureforms' ) }
					/>
				</div>
				<div
					className={ `srfm-form-select-arrow ${
						isOpen ? 'srfm-form-arrow-open' : ''
					}` }
				></div>
			</div>

			<div
				className={ `srfm-form-select-options ${
					isOpen ? 'srfm-form-arrow-open' : ''
				}` }
			>
				{ filter( formsData ).map( ( option, index ) => {
					return (
						<div
							onClick={ () => {
								selectOption( option );
								const formData = formsData.find(
									( formEntry ) =>
										formEntry.id === parseInt( option.id )
								);
								setForm( formData );
							} }
							className={ `srfm-form-single-option ${
								option[ label ] === selectedVal
									? 'srfm-form-selected'
									: ''
							}` }
							key={ `${ id }-${ index }` }
						>
							{ option[ label ] }
						</div>
					);
				} ) }
			</div>
		</div>
	);
};

export default SelectForm;
