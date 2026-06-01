import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ComponentKeyValueUI from '@Components/misc/ComponentKeyValueUI';
import { applyFilters } from '@wordpress/hooks';
import DefaultConfirmationTypes from './DefaultConfirmationTypes';
import { Label } from '@bsf/force-ui';
import RadioGroup from '@Admin/components/RadioGroup';
import TabContentWrapper from '@Components/tab-content-wrapper';
import { getWordPressPages } from '@Utils/Helpers';
import { srfmEditFormMeta } from '@Components/tab-content-wrapper/edit-form-meta';
import { STORE_NAME as SRFM_STORE_NAME } from '@Store/constants';

const FormConfirmSetting = ( { setHasValidationErrors } ) => {
	const sureforms_keys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	// `data` is the live editing state; `prevData` is the last-saved baseline.
	// Both start from the post-meta value on mount so isDirty reads false
	// until the user edits a field. `onSaveSuccess` re-baselines after a
	// successful POST.
	const [ data, setData ] = useState( {} );
	const [ prevData, setPrevData ] = useState( {} );
	const [ pageOptions, setPageOptions ] = useState( [] );
	const [ errorMessage, setErrorMessage ] = useState( null );
	// Tab-owned in-flight save flag. TabContentWrapper toggles this via
	// `onSavingChange` around the POST so the Save button locks during
	// the network call.
	const [ isSaving, setIsSaving ] = useState( false );

	const isDirty = useMemo(
		() => JSON.stringify( data ) !== JSON.stringify( prevData ),
		[ data, prevData ]
	);

	// Push the local dirty signal into the store so the dialog's
	// unsaved-changes guard (tab switch / X / Esc / backdrop / beforeunload)
	// can read it without holding a reference to this component.
	const { setSingleFormSettingUnsave } = useDispatch( SRFM_STORE_NAME );
	useEffect( () => {
		setSingleFormSettingUnsave( isDirty );
	}, [ isDirty, setSingleFormSettingUnsave ] );

	// Unmount cleanup — covers every exit path (dialog close, tab switch
	// via Discard & continue). Without this, the central flag would stay
	// `true` and mis-fire the guard on the next tab click.
	useEffect( () => {
		return () => {
			setSingleFormSettingUnsave( false );
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Listen for the discard signal dispatched by the dialog's "Discard &
	// continue" branch. Each bump of `discardCounter` is one discard event;
	// the ref skips the initial render so the tab doesn't reset itself on
	// mount.
	const discardCounter = useSelect(
		( s ) =>
			s( SRFM_STORE_NAME )?.selectSingleFormSettingDiscardCounter?.() ||
			0,
		[]
	);
	const lastDiscardCounter = useRef( discardCounter );
	useEffect( () => {
		if ( discardCounter === lastDiscardCounter.current ) {
			return;
		}
		lastDiscardCounter.current = discardCounter;
		setData( prevData );
	}, [ discardCounter ] );

	// Clear any stale form-level validation error flag when the user edits.
	// Validation runs at Save click (`validateBeforeSave`), not on every
	// keystroke.
	useEffect( () => {
		setHasValidationErrors( false );
	}, [ data ] );

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
				validation = __(
					'Please provide a custom URL.',
					'sureforms'
				);
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
							'URL is missing Top Level Domain (TLD).',
							'sureforms'
						);
					} else {
						validation = '';
					}
				} catch ( error ) {
					validation = __( 'Please enter a valid URL.', 'sureforms' );
				}
			}
		}
		return validation;
	};

	// Returns null on valid; an error string for TabContentWrapper to
	// surface as a toast otherwise. Also stages the in-flight `data` into
	// Redux `values` so `handleSave` reads the right payload, mirroring
	// the EmailConfirmation pattern where `handleConfirmEmail` stages on
	// validation success.
	const validateBeforeSave = () => {
		const error = validateForm();
		setErrorMessage( error );
		if ( error !== '' ) {
			setHasValidationErrors( true );
			return error;
		}
		srfmEditFormMeta( '_srfm_form_confirmation', [ data ] );
		return null;
	};

	const onSaveSuccess = () => setPrevData( data );

	useEffect( () => {
		const formConfirmationData = sureforms_keys?._srfm_form_confirmation;

		// Fetch the first page of options and include saved value for label hydration.
		getWordPressPages( setPageOptions, {
			selectedUrl: formConfirmationData?.[ 0 ]?.page_url || '',
		} );

		if ( formConfirmationData?.[ 0 ] ) {
			setData( formConfirmationData[ 0 ] );
			setPrevData( formConfirmationData[ 0 ] );
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
						setPageOptions={ setPageOptions }
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
						setPageOptions={ setPageOptions }
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
	const confirmationOption = confirmationTypeInputs.find(
		( option ) =>
			option.value === data?.confirmation_type ||
			option.subOptions?.some(
				( subOption ) => subOption.value === data?.confirmation_type
			)
	);

	// Check if the option or any of its sub-options matches the confirmation type.
	const isOptionActive = ( option, confirmationType ) => {
		return (
			option.value === confirmationType ||
			option.subOptions?.some(
				( subOption ) => subOption.value === confirmationType
			)
		);
	};

	// Set the default confirmation type if the selected option is no longer available.
	useEffect( () => {
		if ( ! confirmationOption && data?.confirmation_type ) {
			setData( { ...data, confirmation_type: 'same page' } );
		}
	}, [ data ] );

	return (
		<TabContentWrapper
			title={ __( 'Form Confirmation', 'sureforms' ) }
			titleHelpText={ __(
				'Customize the confirmation message or redirect the users after submitting the form.',
				'sureforms'
			) }
			showTitleHelpText={ true }
			tabId="form_confirmation"
			showSaveButton={ true }
			validate={ validateBeforeSave }
			isDirty={ isDirty }
			isSaving={ isSaving }
			onSavingChange={ setIsSaving }
			onSaveSuccess={ onSaveSuccess }
		>
			<div className="space-y-6">
				<div className="space-y-2">
					<Label>{ __( 'Confirmation Type', 'sureforms' ) }</Label>
					<div>
						<RadioGroup>
							{ confirmationTypeInputs.map( ( option, index ) => {
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
												confirmation_type: option.value,
											} )
										}
										value={ option.value }
										checked={ isActive }
									/>
								);
							} ) }
						</RadioGroup>
					</div>
				</div>
				{ confirmationOption?.subOptionLabel && (
					<div className="space-y-2">
						<Label>{ confirmationOption?.subOptionLabel }</Label>
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
