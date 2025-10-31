import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { SquarePen } from 'lucide-react';
import { Button } from '@bsf/force-ui';
import UpgradeTooltip from '@Admin/entries/components/UpgradeTooltip';

/**
 * EditEntryButton Component for editing entries
 *
 * @param {*}        props
 * @param {Function} props.onClick  - Click handler for the button
 * @param {boolean}  props.disabled - Whether the button is disabled
 * @return {JSX.Element} EditEntryButton component
 */
const EditEntryButton = ( { onClick, disabled } ) => {
	const button = (
		<Button
			variant="outline"
			size="xs"
			icon={ <SquarePen className="w-4 h-4" /> }
			iconPosition="left"
			onClick={ onClick }
			disabled={ disabled }
		>
			{ __( 'Edit Entries', 'sureforms' ) }
		</Button>
	);

	if ( ! disabled ) {
		return button;
	}

	return (
		<UpgradeTooltip
			heading={ __( 'Unlock Edit Form Entires', 'sureforms' ) }
			content={ __(
				'With the SureForms Starter plan, you can easily edit your entries to suit your needs.',
				'sureforms'
			) }
			placement="top"
			utmMedium="edit_entry"
		>
			{ button }
		</UpgradeTooltip>
	);
};

/**
 * EntryEdit Component to handle entry editing functionality
 *
 * @param {Object} props
 * @param {Object} props.entry - The entry object to be edited
 * @return {JSX.Element} EntryEdit component
 */
const EntryEdit = ( { entry } ) => {
	const [ isOpen, setIsOpen ] = useState( false );

	const EditEntryModal = applyFilters(
		'srfm-pro.entry-details.render-edit-entry-modal'
	);

	const handleClickEdit = () => {
		if ( ! EditEntryModal ) {
			return;
		}
		setIsOpen( true );
	};

	return (
		<>
			<EditEntryButton
				onClick={ handleClickEdit }
				disabled={ ! EditEntryModal }
			/>
			{ !! EditEntryModal && (
				<EditEntryModal
					open={ isOpen }
					setOpen={ setIsOpen }
					entry={ entry }
				/>
			) }
		</>
	);
};

export default EntryEdit;
