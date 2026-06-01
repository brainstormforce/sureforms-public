import { ConfirmationFields } from '@Admin/shared-components/form-confirmation';

/**
 * Form-Level Default Confirmation Types Component
 *
 * Uses shared ConfirmationFields component for consistency with global settings.
 * Adds form-specific features like query parameters.
 *
 * @param {Object}   props
 * @param {Object}   props.data              - Current confirmation data
 * @param {Function} props.setData           - Data setter
 * @param {Array}    props.pageOptions       - WordPress pages for dropdown
 * @param {string}   props.errorMessage      - Validation error message
 * @param {Function} props.setErrorMessage   - Error message setter
 * @param {Function} props.keyValueComponent - Query params UI renderer
 */
const DefaultConfirmationTypes = ( {
	data,
	setData,
	pageOptions,
	setErrorMessage,
	errorMessage,
	keyValueComponent,
} ) => {
	/**
	 * Handle field change and update parent state.
	 * @param {string} key
	 * @param {*}      value
	 */
	const handleChange = ( key, value ) => {
		setData( { ...data, [ key ]: value } );
	};

	return (
		<ConfirmationFields
			context="form"
			data={ data }
			onChange={ handleChange }
			pageOptions={ pageOptions }
			errorMessage={ errorMessage }
			setErrorMessage={ setErrorMessage }
			showQueryParams={ true }
			renderQueryParams={ keyValueComponent }
		/>
	);
};

export default DefaultConfirmationTypes;
