/**
 * FormRestrictionContext and Provider for managing form restriction meta data in the editor.
 * Used in the SureForms Business plugin for configuring entry limits and related logic.
 */

import { useState, createContext } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { decodeJson, deepCopy } from '@Utils/Helpers';

// React context for form restriction meta state
export const FormRestrictionContext = createContext( null );

/**
 * Custom hook to read the form restriction meta from the post editor.
 *
 * @return {Object} Decoded _srfm_form_restriction meta object or empty object.
 */
export const useFormRestrictionMeta = () => {
	const meta = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'meta' )
	);
	return decodeJson( meta?._srfm_form_restriction ) || {};
};

/**
 * React Provider to manage and expose form restriction meta state and actions.
 *
 * @param {Object}      props          - Provider component props
 * @param {JSX.Element} props.children - Children components consuming the context
 * @return {JSX.Element} Provider wrapper with context value
 */
export const FormRestrictionProvider = ( { children } ) => {
	const rawMeta = useFormRestrictionMeta(); // Raw meta from post
	const { editPost } = useDispatch( 'core/editor' ); // WP editor dispatcher
	const [ preserveMetaData, setPreserveMetaData ] = useState( null ); // Temporary buffer for editing
	const [ newMeta, setNewMeta ] = useState( false ); // Flag to indicate we're creating a new restriction

	/**
	 * Loads the raw meta data into the preserve buffer for editing.
	 */
	const editMeta = () => {
		setPreserveMetaData( deepCopy( rawMeta ) );
		setNewMeta( false );
	};

	/**
	 * Updates a key in the preserve (temporary) meta state.
	 *
	 * @param {string} key   - Field name to update
	 * @param {*}      value - New value for the field
	 * @return {boolean} True if the update was applied, false otherwise
	 */
	const updatePreserveMeta = ( key, value ) => {
		if ( ! preserveMetaData ) {
			return false;
		}

		const updated = deepCopy( preserveMetaData );
		updated[ key ] = value;

		setPreserveMetaData( updated );
		return true;
	};

	/**
	 * Updates a key in the actual form meta (post-level).
	 * Skips update if we're in "new template" creation mode.
	 *
	 * @param {string} key   - Field name to update
	 * @param {*}      value - New value to assign
	 */
	const updateMeta = ( key, value ) => {
		const shouldUpdate = updatePreserveMeta( key, value );

		if ( ! shouldUpdate || newMeta ) {
			return;
		}

		const updated = deepCopy( rawMeta );
		if ( updated ) {
			updated[ key ] = value;

			editPost( {
				meta: { _srfm_form_restriction: JSON.stringify( updated ) },
			} );
		}
	};

	/**
	 * Context value provided to consumers.
	 */
	const contextValue = {
		rawMeta,
		updateMeta,
		editMeta,
		preserveMetaData,
		setPreserveMetaData,
		newMeta,
		setNewMeta,

		/**
		 * Resets the temporary state (used to cancel editing or creation).
		 */
		resetPreserveMetaData: () => {
			setPreserveMetaData( null );
			setNewMeta( false );
		},
	};

	return (
		<FormRestrictionContext.Provider value={ contextValue }>
			{ children }
		</FormRestrictionContext.Provider>
	);
};
