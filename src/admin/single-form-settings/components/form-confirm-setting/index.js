import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ComponentKeyValueUI from '@Components/misc/ComponentKeyValueUI';
import { useDebouncedCallback } from 'use-debounce';
import { applyFilters } from '@wordpress/hooks';
import DefaultConfirmationTypes from './DefaultConfirmationTypes';
import { Label } from '@bsf/force-ui';
import RadioGroup from '@Admin/components/RadioGroup';
import TabContentWrapper from '@Components/tab-content-wrapper';
import { getWordPressPages } from '@Utils/Helpers';

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
		// Fetch the page options.
		getWordPressPages( setPageOptions );
		const formConfirmationData = sureforms_keys._srfm_form_confirmation;
		if ( formConfirmationData ) {
			setData( formConfirmationData[ 0 ] );
		}
	}, [] );

	// Added filter so that the additional confirmation types can be added.
	const confirmationTypeInputs = applyFilters(
		'srfm.formConfirmation.confirmationType.inputs',
		[
			{
				label: __( 'Success Message', 'sureforms' ),
				value: 'same page',
				component: (
					<DefaultConfirmationTypes
						data={ data }
						setData={ setData }
						pageOptions={ pageOptions }
						errorMessage={ errorMessage }
						setErrorMessage={ setErrorMessage }
						keyValueComponent={ keyValueComponent }
					/>
				),
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
				component: (
					<DefaultConfirmationTypes
						data={ data }
						setData={ setData }
						pageOptions={ pageOptions }
						errorMessage={ errorMessage }
						setErrorMessage={ setErrorMessage }
						keyValueComponent={ keyValueComponent }
					/>
				),
			},
		],
		{
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
		<TabContentWrapper title={ __( 'Form Confirmation', 'sureforms' ) }>
			<div className="space-y-6">
				<div className="space-y-2">
					<Label>
						{ __( 'Confirmation Type', 'sureforms' ) }
					</Label>
					<div>
						<RadioGroup>
							{ confirmationTypeInputs.map(
								( option, index ) => {
									const isActive = isOptionActive(
										option,
										data?.confirmation_type
									);
									return (
										<RadioGroup.Option
											key={ index }
											label={ option.label }
											onChange={ () =>
												setData( {
													...data,
													confirmation_type:
															option.value,
												} )
											}
											value={ option.value }
											checked={ isActive }
										/>
									);
								}
							) }
						</RadioGroup>
					</div>
				</div>
				{ confirmationOption?.subOptionLabel && (
					<div className="space-y-2">
						<Label>
							{ confirmationOption?.subOptionLabel }
						</Label>
						<RadioGroup cols={ 2 }>
							{ confirmationOption?.subOptions?.map(
								( subOption, index ) => {
									return (
										<RadioGroup.Option
											key={ index }
											label={ subOption.label }
											value={ subOption.value }
											checked={
												data?.confirmation_type ===
													subOption.value
											}
											onChange={ () =>
												setData( {
													...data,
													confirmation_type:
															subOption.value,
												} )
											}
										/>
									);
								}
							) }
						</RadioGroup>
					</div>
				) }
				{
					// Render the associated component based on the selected confirmation type.
					confirmationOption?.component
				}
			</div>
		</TabContentWrapper>
	);
};

export default FormConfirmSetting;
