import { useState, useMemo } from '@wordpress/element';

/**
 * Custom hook to manage entries selection state
 *
 * @param {Array} entries - Array of entry objects
 * @return {Object} Selection states and handlers
 */
export const useEntriesSelection = ( entries = [] ) => {
	const [ selectedEntries, setSelectedEntries ] = useState( [] );

	const handleChangeRowCheckbox = ( checked, item ) => {
		if ( checked ) {
			setSelectedEntries( ( prev ) => [ ...prev, item.id ] );
		} else {
			setSelectedEntries( ( prev ) =>
				prev.filter( ( entryId ) => entryId !== item.id )
			);
		}
	};

	const handleToggleAll = ( checked ) => {
		if ( checked ) {
			setSelectedEntries( entries.map( ( entry ) => entry.id ) );
		} else {
			setSelectedEntries( [] );
		}
	};

	const clearSelection = () => {
		setSelectedEntries( [] );
	};

	const indeterminate = useMemo( () => {
		return (
			selectedEntries.length > 0 &&
			selectedEntries.length < entries.length
		);
	}, [ selectedEntries, entries.length ] );

	return {
		selectedEntries,
		handleChangeRowCheckbox,
		handleToggleAll,
		clearSelection,
		indeterminate,
	};
};
