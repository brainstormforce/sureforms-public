import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { useLocation } from 'react-router-dom';
import { Container, Input, Label, Switch, TextArea } from '@bsf/force-ui';
import LoadingSkeleton from '@Admin/components/LoadingSkeleton';
import RadioGroup from '@Admin/components/RadioGroup';
import { ComplianceFields } from '@Admin/shared-components/compliance';
import { EmailNotificationFields } from '@Admin/shared-components/email-notification';
import { ConfirmationFields } from '@Admin/shared-components/form-confirmation';
import {
	CONFIRMATION_TYPE_OPTIONS,
	REDIRECT_SUB_OPTIONS,
} from '@Admin/shared-components/form-confirmation/constants';
import ComponentKeyValueUI from '@Components/misc/ComponentKeyValueUI';
import useWordPressPages from '../hooks/useWordPressPages';

/**
 * Debounced Number Input Component
 * Maintains local state to prevent focus loss on parent re-renders.
 * Only syncs to parent on blur.
 * @param {Object}   root0
 * @param {*}        root0.value
 * @param {Function} root0.onChange
 * @param {string}   root0.label
 * @param {number}   root0.min
 */
const DebouncedNumberInput = ( {
	value,
	onChange,
	label,
	min = 0,
	...props
} ) => {
	const [ localValue, setLocalValue ] = useState( value );

	// Sync local state when external value changes.
	useEffect( () => {
		setLocalValue( value );
	}, [ value ] );

	const handleBlur = () => {
		let parsedValue = parseInt( localValue, 10 );
		if ( isNaN( parsedValue ) || parsedValue < min ) {
			parsedValue = min;
			setLocalValue( min );
		}
		onChange( parsedValue );
	};

	return (
		<Input
			type="number"
			value={ localValue }
			label={ label }
			onChange={ setLocalValue }
			onBlur={ handleBlur }
			{ ...props }
		/>
	);
};

/**
 * Debounced TextArea Component
 * Maintains local state to prevent focus loss on parent re-renders.
 * Only syncs to parent on blur.
 * @param {Object}   root0
 * @param {string}   root0.value
 * @param {Function} root0.onChange
 */
const DebouncedTextArea = ( { value, onChange, ...props } ) => {
	const [ localValue, setLocalValue ] = useState( value );

	// Sync local state when external value changes.
	useEffect( () => {
		setLocalValue( value );
	}, [ value ] );

	const handleBlur = () => {
		onChange( localValue );
	};

	return (
		<TextArea
			value={ localValue }
			onChange={ setLocalValue }
			onBlur={ handleBlur }
			{ ...props }
		/>
	);
};

/**
 * Renders the Form Restrictions subpage content.
 *
 * Free renders the Maximum Entries subsection. Pro extends this view with
 * additional subsections (IP, Country, Keyword, etc.) via the
 * `srfm.settings.formRestriction.additionalSettings` filter.
 *
 * @param {Object}   root0
 * @param {Object}   root0.formRestrictionOptions   - Current form restriction settings.
 * @param {Function} root0.updateRestrictionSetting - Callback to update a restriction field.
 * @param {Object}   root0.toast                    - Toast singleton forwarded into the
 *                                                  filter so pro shares free's Toaster.
 */
const FormRestrictionsContent = ( {
	formRestrictionOptions,
	updateRestrictionSetting,
	toast,
} ) => {
	const maxEntriesData = formRestrictionOptions?.max_entries || {};

	return (
		<div className="space-y-6">
			{ /* Maximum Number of Entries - matches single form settings */ }
			<Container direction="column" className="gap-6">
				<Switch
					size="sm"
					label={ {
						heading: __( 'Maximum Number of Entries', 'sureforms' ),
						description: __(
							'Set the total number of submissions allowed for this form.',
							'sureforms'
						),
					} }
					checked={ maxEntriesData?.status ?? false }
					onChange={ ( checked ) =>
						updateRestrictionSetting(
							'max_entries',
							'status',
							checked
						)
					}
				/>

				{ maxEntriesData?.status && (
					<>
						<div className="flex gap-2 w-full">
							<div className="w-full">
								<DebouncedNumberInput
									size="md"
									className="w-full"
									value={ maxEntriesData?.maxEntries ?? 0 }
									label={ __(
										'Maximum Entries',
										'sureforms'
									) }
									min={ 0 }
									onChange={ ( value ) =>
										updateRestrictionSetting(
											'max_entries',
											'maxEntries',
											value
										)
									}
								/>
							</div>
						</div>

						<div>
							<Label className="mb-1.5">
								{ __(
									'Response Description After Maximum Entries',
									'sureforms'
								) }
							</Label>
							<DebouncedTextArea
								size="md"
								className="w-full"
								value={
									maxEntriesData?.message ??
									__(
										"This form is now closed as we've received all the entries.",
										'sureforms'
									)
								}
								onChange={ ( value ) =>
									updateRestrictionSetting(
										'max_entries',
										'message',
										value
									)
								}
							/>
						</div>
					</>
				) }
			</Container>

			{ /*
			 * Pro extension slot — IP / Country / Keyword (and any future
			 * paid restriction subsections) are injected here. Free renders
			 * nothing for this slot when Pro is inactive.
			 */ }
			{ applyFilters(
				'srfm.settings.formRestriction.additionalSettings',
				null,
				{ formRestrictionOptions, updateRestrictionSetting, toast }
			) }

		</div>
	);
};

/**
 * Renders the Compliance Settings subpage content.
 * Uses shared ComplianceFields component for consistency with form-level settings.
 *
 * @param {Object}   root0
 * @param {Object}   root0.complianceOptions    - Current compliance settings.
 * @param {Function} root0.updateGlobalSettings - Settings update handler.
 */
const ComplianceSettingsContent = ( {
	complianceOptions,
	updateGlobalSettings,
} ) => (
	<div className="space-y-6">
		<ComplianceFields
			context="global"
			values={ complianceOptions }
			onChange={ ( key, value ) =>
				updateGlobalSettings( key, value, 'compliance-settings' )
			}
		/>
	</div>
);

const GlobalDefaultsPage = ( {
	loading,
	formRestrictionOptions,
	complianceOptions,
	emailNotificationOptions,
	formConfirmationOptions,
	updateGlobalSettings,
	toast,
} ) => {
	const location = useLocation();
	const { pageOptions, loadingPages } = useWordPressPages();

	// Local state for email notification and form confirmation fields.
	// Initialized as null so the skeleton remains visible until the API data
	// has been loaded and synced. This prevents the QuillEditor from mounting
	// with default content (which would trigger ReactQuill's onChange on init
	// and overwrite saved values via the debounced auto-save).
	const [ localEmailOptions, setLocalEmailOptions ] = useState( null );
	const [ localConfirmationOptions, setLocalConfirmationOptions ] =
		useState( null );

	// Sync local state once the initial data load completes (loading → false).
	// With loading initialized as true in Component.js, this fires only after
	// the API data is fetched and stored in the parent state.
	const initialSyncDone = useRef( false );
	useEffect( () => {
		if ( ! loading && ! initialSyncDone.current ) {
			initialSyncDone.current = true;
			setLocalEmailOptions( emailNotificationOptions );
			setLocalConfirmationOptions( formConfirmationOptions );
		}
	}, [ loading, emailNotificationOptions, formConfirmationOptions ] );

	// Refs accumulate all field changes that arrive within the debounce window
	// so that fast multi-field edits are all saved in the same flush.
	const emailPendingRef = useRef( {} );
	const emailTimerRef = useRef( null );
	const confirmPendingRef = useRef( {} );
	const confirmTimerRef = useRef( null );

	// Keep a stable ref to updateGlobalSettings so the setTimeout callbacks
	// always call the latest version (avoids stale-closure bugs).
	const updateGlobalSettingsRef = useRef( updateGlobalSettings );
	useEffect( () => {
		updateGlobalSettingsRef.current = updateGlobalSettings;
	}, [ updateGlobalSettings ] );

	/**
	 * Email notification change handler.
	 * Updates local state immediately (no focus loss) and debounces the
	 * parent state update to avoid re-renders during active typing.
	 *
	 * @param {string} key   Field key.
	 * @param {*}      value New value.
	 */
	const handleEmailChange = ( key, value ) => {
		setLocalEmailOptions( ( prev ) => ( { ...prev, [ key ]: value } ) );
		emailPendingRef.current = {
			...emailPendingRef.current,
			[ key ]: value,
		};
		clearTimeout( emailTimerRef.current );
		emailTimerRef.current = setTimeout( () => {
			const pending = emailPendingRef.current;
			emailPendingRef.current = {};
			// Pass all pending changes as a single batch object so every
			// field is merged in one updateGlobalSettings call, avoiding
			// the stale-state data-loss that occurs when multiple setState
			// calls are queued synchronously.
			if ( Object.keys( pending ).length > 0 ) {
				updateGlobalSettingsRef.current(
					pending,
					null,
					'email-notification-settings'
				);
			}
		}, 500 );
	};

	/**
	 * Form confirmation change handler.
	 * Same debounce strategy as handleEmailChange.
	 *
	 * @param {string} key   Field key.
	 * @param {*}      value New value.
	 */
	const handleConfirmationChange = ( key, value ) => {
		setLocalConfirmationOptions( ( prev ) => ( {
			...prev,
			[ key ]: value,
		} ) );
		confirmPendingRef.current = {
			...confirmPendingRef.current,
			[ key ]: value,
		};
		clearTimeout( confirmTimerRef.current );
		confirmTimerRef.current = setTimeout( () => {
			const pending = confirmPendingRef.current;
			confirmPendingRef.current = {};
			// Same batch strategy as email: one call with all pending fields.
			if ( Object.keys( pending ).length > 0 ) {
				updateGlobalSettingsRef.current(
					pending,
					null,
					'form-confirmation-settings'
				);
			}
		}, 500 );
	};

	/**
	 * Render query parameters UI for confirmation redirect types.
	 */
	const renderQueryParams = () => {
		return (
			<ComponentKeyValueUI
				data={ localConfirmationOptions?.query_params ?? [ { '': '' } ] }
				enabled={ localConfirmationOptions?.enable_query_params ?? false }
				setEnabled={ ( checked ) =>
					handleConfirmationChange( 'enable_query_params', checked )
				}
				label={ __( 'Add Query Parameters', 'sureforms' ) }
				helpText={ __(
					'Select if you want to add key-value pairs for form fields to include in query parameters',
					'sureforms'
				) }
				withSmartTags={ false }
				header={ __( 'Query Parameters', 'sureforms' ) }
				handleOnChange={ ( queryParams ) =>
					handleConfirmationChange( 'query_params', queryParams )
				}
			/>
		);
	};

	const getCurrentSubpage = () => {
		try {
			const searchParams = new URLSearchParams( location.search );
			// Default to 'email-notifications' if no subpage is specified.
			return searchParams.get( 'subpage' ) || 'email-notifications';
		} catch ( error ) {
			return 'email-notifications';
		}
	};

	/**
	 * Updates a specific restriction setting and saves to backend.
	 *
	 * @param {string} restrictionType - The restriction type key (e.g., 'max_entries', 'ip_restriction').
	 * @param {string} field           - The field to update within the restriction.
	 * @param {*}      value           - The new value for the field.
	 */
	const updateRestrictionSetting = ( restrictionType, field, value ) => {
		const updatedRestriction = {
			...( formRestrictionOptions?.[ restrictionType ] || {} ),
			[ field ]: value,
		};
		updateGlobalSettings(
			restrictionType,
			updatedRestriction,
			'form-restriction-settings'
		);
	};

	/**
	 * Check if the current confirmation type is a redirect type.
	 * Uses localConfirmationOptions so radio buttons update instantly.
	 */
	const isRedirectType =
		localConfirmationOptions?.confirmation_type === 'different page' ||
		localConfirmationOptions?.confirmation_type === 'custom url';

	/**
	 * Check if the confirmation option is active (for radio button).
	 * @param {Object} option
	 * @param {string} confirmationType
	 */
	const isOptionActive = ( option, confirmationType ) => {
		return (
			option.value === confirmationType ||
			( option.value === 'different page' &&
				( confirmationType === 'different page' ||
					confirmationType === 'custom url' ) )
		);
	};

	const currentSubpage = getCurrentSubpage();

	// Return null if no valid subpage is selected.
	const validSubpages = [
		'email-notifications',
		'form-confirmation',
		'form-restrictions',
		'compliance-settings',
	];
	if ( ! validSubpages.includes( currentSubpage ) ) {
		return null;
	}

	if (
		loading ||
		localEmailOptions === null ||
		localConfirmationOptions === null
	) {
		return (
			<div className="space-y-6">
				<LoadingSkeleton count={ 6 } className="h-6 rounded-sm" />
			</div>
		);
	}

	// Each subpage is rendered with a direct stable component reference (not an
	// inline-defined wrapper function). This is the key fix for the focus-loss
	// bug: using <InlineComponent /> where the function is re-created on every
	// parent render causes React to unmount+remount the entire subtree on each
	// keystroke, destroying focus. Stable module-level refs update in place.
	return (
		<div className="w-full space-y-6">
			{ currentSubpage === 'email-notifications' && (
				<EmailNotificationFields
					context="global"
					values={ localEmailOptions }
					onChange={ handleEmailChange }
					showNameField={ false }
					showAllDataTag={ true }
					loading={ loading }
					wrapWithContentSection={ true }
				/>
			) }

			{ currentSubpage === 'form-confirmation' && (
				<div className="space-y-6">
					{ /* Confirmation Type Selector */ }
					<div className="space-y-2">
						<Label>
							{ __( 'Confirmation Type', 'sureforms' ) }
						</Label>
						<div>
							<RadioGroup>
								{ CONFIRMATION_TYPE_OPTIONS.map( ( option ) => {
									const isActive = isOptionActive(
										option,
										localConfirmationOptions?.confirmation_type
									);
									return (
										<RadioGroup.Option
											key={ option.value }
											label={ option.label }
											onChange={ () =>
												handleConfirmationChange(
													'confirmation_type',
													option.value
												)
											}
											value={ option.value }
											checked={ isActive }
										/>
									);
								} ) }
							</RadioGroup>
						</div>
					</div>

					{ /* Redirect Sub-options */ }
					{ isRedirectType && (
						<div className="space-y-2">
							<Label>{ __( 'Redirect to', 'sureforms' ) }</Label>
							<RadioGroup cols={ 2 }>
								{ REDIRECT_SUB_OPTIONS.map( ( subOption ) => (
									<RadioGroup.Option
										key={ subOption.value }
										label={ subOption.label }
										value={ subOption.value }
										checked={
											localConfirmationOptions?.confirmation_type ===
											subOption.value
										}
										onChange={ () =>
											handleConfirmationChange(
												'confirmation_type',
												subOption.value
											)
										}
									/>
								) ) }
							</RadioGroup>
						</div>
					) }

					{ /* Confirmation Fields (shared component) */ }
					<ConfirmationFields
						context="global"
						data={ localConfirmationOptions }
						onChange={ handleConfirmationChange }
						pageOptions={ pageOptions }
						loadingPages={ loadingPages }
						showQueryParams={ true }
						renderQueryParams={ renderQueryParams }
					/>
				</div>
			) }

			{ currentSubpage === 'form-restrictions' && (
				<FormRestrictionsContent
					formRestrictionOptions={ formRestrictionOptions }
					updateRestrictionSetting={ updateRestrictionSetting }
					toast={ toast }
				/>
			) }

			{ currentSubpage === 'compliance-settings' && (
				<ComplianceSettingsContent
					complianceOptions={ complianceOptions }
					updateGlobalSettings={ updateGlobalSettings }
				/>
			) }
		</div>
	);
};

export default GlobalDefaultsPage;
