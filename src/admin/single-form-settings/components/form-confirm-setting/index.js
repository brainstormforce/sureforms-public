import apiFetch from '@wordpress/api-fetch';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ComponentKeyValueUI from '@Components/misc/ComponentKeyValueUI';
import { useDebouncedCallback } from 'use-debounce';
import { applyFilters } from '@wordpress/hooks';
import DefaultConfirmationTypes from './DefaultConfirmationTypes';

const FormConfirmSetting = ( { toast, setHasValidationErrors } ) => {
	const sureforms_keys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	const { editPost } = useDispatch( editorStore );
	const [ data, setData ] = useState( {} );
	const [ pageOptions, setPageOptions ] = useState( [] );
	const [ errorMessage, setErrorMessage ] = useState( null );
	const [ showSuccess, setShowSuccess ] = useState( null );

	const handleSaveChanges = () => {
		const validationStatus = validateForm();

		setErrorMessage( validationStatus );
		if ( '' !== validationStatus ) {
			setHasValidationErrors( true );
			toast.dismiss();
			return false;
		}
		updateMeta( '_srfm_form_confirmation', [ data ] );
		setShowSuccess( true );
		toast.dismiss();
	};

	const debounced = useDebouncedCallback( handleSaveChanges, 500 );

	useEffect( () => {
		if ( null !== errorMessage ) {
			setErrorMessage( validateForm() );
		}

		setHasValidationErrors( false );
		debounced( data );
	}, [ data ] );
	useEffect( () => {
		if ( true === showSuccess ) {
			setTimeout( () => {
				setShowSuccess( false );
				toast.dismiss();
			}, 500 );
		}
	}, [ showSuccess ] );

	const handleQueryParamsChange = ( queryParams ) => {
		setData( { ...data, query_params: queryParams } );
	};

	const handleEnableQueryParams = ( checked ) => {
		setData( { ...data, enable_query_params: checked } );
	};

	const keyValueComponent = () => {
		return (
			<ComponentKeyValueUI
				data={ data?.query_params ?? [ { '': '' } ] }
				enabled={ data?.enable_query_params ?? false }
				setEnabled={ handleEnableQueryParams }
				label={ __( 'Add Query Parameters', 'sureforms' ) }
				helpText={ __(
					'Select if you want to add key-value pairs for form fields to include in query parameters',
					'sureforms'
				) }
				withSmartTags={ true }
				header={ __( 'Query Parameters', 'sureforms' ) }
				handleOnChange={ handleQueryParamsChange }
			/>
		);
	};

	const validateForm = () => {
		let validation = '';
		if (
			'different page' === data?.confirmation_type &&
			! data?.page_url
		) {
			validation = __( 'Please select a page.', 'sureforms' );
		}
		if ( 'custom url' === data?.confirmation_type ) {
			if ( ! data?.custom_url ) {
				validation = __( 'This field is required', 'sureforms' );
			} else {
				try {
					const newURL = new URL( data?.custom_url );
					if ( newURL.protocol !== 'https:' ) {
						validation = __(
							'Suggestion: URL should use HTTPS',
							'sureforms'
						);
					} else if (
						! (
							'localhost' !== newURL.hostname &&
							newURL.hostname.includes( '.' ) &&
							newURL.hostname.split( '.' ).pop().length > 1
						)
					) {
						validation = __(
							'URL is missing Top Level Domain (TLD)',
							'sureforms'
						);
					} else {
						validation = '';
					}
				} catch ( error ) {
					validation = __( 'Please enter a valid URL', 'sureforms' );
				}
			}
		}
		return validation;
	};

	function updateMeta( option, value ) {
		const option_array = {};
		option_array[ option ] = value;
		editPost( {
			meta: option_array,
		} );
	}

	useEffect( () => {
		apiFetch( { path: '/wp/v2/pages' } )
			.then( ( pages ) => {
				if ( pages ) {
					const createFormat = pages.map( ( page ) => {
						let label;
						if ( page.title?.rendered ) {
							label = page.title?.rendered;
						} else {
							label = page.id.toString();
						}
						const value = page.link;
						return { label, value };
					} );
					setPageOptions( createFormat );
				}
			} )
			.catch( ( error ) => console.error( 'Error:', error ) );
		const formConfirmationData = sureforms_keys._srfm_form_confirmation;
		if ( formConfirmationData ) {
			setData( formConfirmationData[ 0 ] );
		}
	}, [] );

	// Added filter so that the additional confirmation types can be added.
	const confirmationTypeInputs = applyFilters( 'srfm.formConfirmation.confirmationType.inputs', [
		{
			label: __( 'Success Message', 'sureforms' ),
			value: 'same page',
			component: <DefaultConfirmationTypes data={ data } setData={ setData } pageOptions={ pageOptions } errorMessage={ errorMessage } setErrorMessage={ setErrorMessage } keyValueComponent={ keyValueComponent } />,
		},
		{
			label: __( 'Redirect', 'sureforms' ),
			value: 'different page',
			subOptionLabel: __( 'Redirect to', 'sureforms' ),
			subOptions: [
				{
					label: __( 'Page', 'sureforms' ),
					value: 'different page',
				},
				{
					label: __( 'Custom URL', 'sureforms' ),
					value: 'custom url',
				},
			],
			component: <DefaultConfirmationTypes data={ data } setData={ setData } pageOptions={ pageOptions } errorMessage={ errorMessage } setErrorMessage={ setErrorMessage } keyValueComponent={ keyValueComponent } />,
		},
	], {
		data,
		setData,
		errorMessage,
		setErrorMessage,
	}
	);

	// Find the selected confirmation type and sub type (if any).
	const confirmationOption = confirmationTypeInputs.find( ( option ) =>
		option.value === data?.confirmation_type ||
		option.subOptions?.some( ( subOption ) => subOption.value === data?.confirmation_type )
	);

	// Check if the option or any of its sub-options matches the confirmation type.
	const isOptionActive = ( option, confirmationType ) => {
		return option.value === confirmationType ||
			option.subOptions?.some( ( subOption ) => subOption.value === confirmationType );
	};

	// Set the default confirmation type if the selected option is no longer available.
	useEffect( () => {
		if ( ! confirmationOption && data?.confirmation_type ) {
			setData( { ...data, confirmation_type: 'same page' } );
		}
	}, [ data ] );

	return (
		<div className="srfm-modal-content">
			<div className="srfm-modal-inner-content">
				<div className="srfm-modal-inner-heading">
					<div className="srfm-modal-inner-heading-text">
						<h4>{ __( 'Form Confirmation', 'sureforms' ) }</h4>
					</div>
				</div>
				<div className="srfm-modal-inner-box">
					<div className="srfm-modal-inner-box-text">
						<h5>{ __( 'Confirmation', 'sureforms' ) }</h5>
					</div>
					<div className="srfm-modal-separator" />
					<div className="srfm-modal-inner-box-content">
						<div className="srfm-modal-option-box">
							<div className="srfm-modal-label">
								<label>
									{ __( 'Confirmation Type', 'sureforms' ) }
								</label>
							</div>
							<div className="srfm-options-wrapper">
								{
									confirmationTypeInputs.map( ( option, index ) => {
										const isActive = isOptionActive( option, data?.confirmation_type );
										return (
											<label
												className="srfm-option-label"
												htmlFor={ `confirm-type-${ index }` }
												key={ index }
											>
												<div
													className={ `srfm-option ${ isActive ? 'srfm-active-conf-type' : '' }` }
												>
													<input
														className="srfm-option-input"
														value={ option.value }
														checked={ isActive }
														onChange={ ( e ) => setData( { ...data, confirmation_type: e.target.value } ) }
														type="radio"
														id={ `confirm-type-${ index }` }
														name="confirm-type"
													/>
													{ option.label }
												</div>
											</label>
										);
									} )
								}
							</div>
						</div>
						{
							confirmationOption?.subOptionLabel && (
								<div className="srfm-modal-option-box">
									<div className="srfm-modal-label">
										<label>
											{ confirmationOption?.subOptionLabel }
										</label>
									</div>
									<div className="srfm-options-wrapper">
										{
										// render the suboptions based on the selected confirmation type.
											confirmationOption?.subOptions?.map( ( subOption, index ) => (
												<label
													className="srfm-option-label"
													htmlFor={ `suboptions-type-${ index }` }
													key={ index }
												>
													<div
														className={ `srfm-option ${ data?.confirmation_type === subOption.value ? 'srfm-active-conf-type' : '' }` }
													>
														<input
															className="srfm-option-input"
															value={ subOption.value }
															checked={ data?.confirmation_type === subOption.value }
															onChange={ ( e ) => setData( { ...data, confirmation_type: e.target.value } ) }
															type="radio"
															id={ `suboptions-type-${ index }` }
															name="suboptions-type"
														/>
														{ subOption.label }
													</div>
												</label>
											) )
										}
									</div>
								</div>
							)
						}
						{
							// Render the associated component based on the selected confirmation type.
							confirmationOption?.component
						}
					</div>
				</div>
			</div>
		</div>
	);
};

export default FormConfirmSetting;
