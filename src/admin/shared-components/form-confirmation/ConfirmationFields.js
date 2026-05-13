import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Input, Label, Select } from '@bsf/force-ui';
import RadioGroup from '@Admin/components/RadioGroup';
import Editor from '@Admin/single-form-settings/components/QuillEditor';
import LoadingSkeleton from '@Admin/components/LoadingSkeleton';

/**
 * After submission options for success message type.
 */
const AFTER_SUBMISSION_OPTIONS = [
	{
		label: __( 'Hide Form', 'sureforms' ),
		value: 'hide form',
	},
	{
		label: __( 'Reset Form', 'sureforms' ),
		value: 'reset form',
	},
];

/**
 * Shared Confirmation Fields Component
 *
 * Context-aware component for rendering form confirmation settings.
 * Renders fields based on the selected confirmation type.
 *
 * @param {Object}   props
 * @param {string}   props.context           - 'form' | 'global'
 * @param {Object}   props.data              - Current confirmation data
 * @param {Function} props.onChange          - Handler: (fieldKey, value) => void
 * @param {Array}    props.pageOptions       - WordPress pages for dropdown
 * @param {boolean}  props.loadingPages      - Loading state for pages
 * @param {string}   props.errorMessage      - Validation error message
 * @param {Function} props.setErrorMessage   - Error message setter
 * @param {boolean}  props.showQueryParams   - Show query params UI (form only)
 * @param {Function} props.renderQueryParams - Render function for query params
 */
const ConfirmationFields = ( {
	context,
	data,
	onChange,
	pageOptions,
	loadingPages = false,
	errorMessage = null,
	setErrorMessage,
	showQueryParams = false,
	renderQueryParams,
} ) => {
	const [ canDisplayError, setCanDisplayError ] = useState( false );

	// Reset error display state when confirmation type changes.
	useEffect( () => {
		setCanDisplayError( false );
	}, [ data?.confirmation_type ] );

	const handleEditorChange = ( newContent ) => {
		onChange( 'message', newContent );
	};

	/**
	 * Render page selector based on context.
	 * Global uses Portal, form doesn't.
	 */
	const renderPageSelector = () => {
		if ( loadingPages ) {
			return <LoadingSkeleton count={ 1 } className="h-10 rounded-md" />;
		}

		if ( context === 'global' ) {
			// Global context: use Portal for dropdown.
			return (
				<Select
					value={ data?.page_url }
					onChange={ ( value ) => onChange( 'page_url', value ) }
					combobox
					searchPlaceholder={ __( 'Search for a page', 'sureforms' ) }
				>
					<Select.Button
						id="select-page"
						placeholder={ __( 'Select a page', 'sureforms' ) }
					>
						{
							pageOptions?.find(
								( option ) => option.value === data?.page_url
							)?.label
						}
					</Select.Button>
					<Select.Portal id="srfm-settings-container">
						<Select.Options>
							{ pageOptions?.map( ( option ) => (
								<Select.Option
									key={ option.value }
									value={ option.value }
									selected={ option.value === data?.page_url }
								>
									{ option.label }
								</Select.Option>
							) ) }
						</Select.Options>
					</Select.Portal>
				</Select>
			);
		}

		// Form context: simple Select without Portal.
		return (
			<Select
				options={ pageOptions }
				value={ data?.page_url }
				onChange={ ( value ) => {
					setCanDisplayError( true );
					if ( setErrorMessage ) {
						setErrorMessage( null );
					}
					onChange( 'page_url', value );
				} }
				combobox
				searchPlaceholder={ __( 'Search for a page', 'sureforms' ) }
			>
				<Select.Button
					id="select-page"
					placeholder={ __( 'Select a page', 'sureforms' ) }
				>
					{
						pageOptions?.find(
							( option ) => option.value === data?.page_url
						)?.label
					}
				</Select.Button>
				<Select.Options>
					{ pageOptions?.map( ( option ) => (
						<Select.Option
							key={ option.value }
							value={ option.value }
							selected={ option.value === data?.page_url }
						>
							{ option.label }
						</Select.Option>
					) ) }
				</Select.Options>
			</Select>
		);
	};

	return (
		<>
			{ /* Same Page: Success Message + After Submission */ }
			{ data?.confirmation_type === 'same page' && (
				<>
					<div>
						<Editor
							handleContentChange={ handleEditorChange }
							content={ data?.message }
							/* SF-2815 start: forward context so global shows generic tags only. */
							context={ context }
							/* SF-2815 end. */
						/>
					</div>
					<div className="space-y-2">
						<Label>
							{ __( 'After Form Submission', 'sureforms' ) }
						</Label>
						<RadioGroup cols={ 2 }>
							{ AFTER_SUBMISSION_OPTIONS.map(
								( option ) => (
									<RadioGroup.Option
										key={ option.value }
										label={ option.label }
										value={ option.value }
										checked={
											data?.submission_action ===
											option.value
										}
										onChange={ () =>
											onChange(
												'submission_action',
												option.value
											)
										}
									/>
								)
							) }
						</RadioGroup>
					</div>
				</>
			) }

			{ /* Different Page: Page Selector + Optional Query Params */ }
			{ data?.confirmation_type === 'different page' && (
				<>
					<div className="space-y-6">
						<div className="space-y-1.5">
							<Label htmlFor="select-page">
								{ __( 'Select Page to redirect', 'sureforms' ) }
							</Label>
							{ renderPageSelector() }
						</div>
					</div>
					{ showQueryParams &&
						renderQueryParams &&
						renderQueryParams() }
				</>
			) }

			{ /* Custom URL: URL Input + Optional Query Params */ }
			{ data?.confirmation_type === 'custom url' && (
				<>
					<div className="space-y-1.5">
						<Label htmlFor="custom-url-input" required>
							{ __( 'Custom URL', 'sureforms' ) }
						</Label>
						<Input
							id="custom-url-input"
							value={ data?.custom_url ?? '' }
							onChange={ ( value ) => {
								setCanDisplayError( true );
								onChange( 'custom_url', value );
							} }
							size="md"
							placeholder={
								context === 'global'
									? 'https://example.com'
									: undefined
							}
						/>
						{ context === 'form' &&
							canDisplayError &&
							errorMessage && (
							<Label variant="error" size="sm">
								{ errorMessage }
							</Label>
						) }
					</div>
					{ showQueryParams &&
						renderQueryParams &&
						renderQueryParams() }
				</>
			) }
		</>
	);
};

export default ConfirmationFields;
