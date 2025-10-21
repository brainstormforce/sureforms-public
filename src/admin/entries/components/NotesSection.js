import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { FileSearch, Plus } from 'lucide-react';
import { Button, Text } from '@bsf/force-ui';
import UpgradeTooltip from './UpgradeTooltip';

const AddEntryButton = () => {
	return (
		<UpgradeTooltip
			heading={ __( 'Unlock Add Note', 'sureforms' ) }
			content={ __(
				'With the SureForms Starter plan, enhance your submitted form entries by adding personalized notes for better clarity and tracking.',
				'sureforms'
			) }
			placement="top-start"
			utmMedium="add_note"
		>
			<Button
				variant="link"
				size="xs"
				icon={ <Plus className="w-4 h-4" /> }
				iconPosition="left"
				className="text-link-primary hover:text-link-primary-hover"
				disabled
			>
				{ __( 'Add Note', 'sureforms' ) }
			</Button>
		</UpgradeTooltip>
	);
};

/**
 * NotesSection Component
 * Displays notes for an entry with add note functionality
 *
 * @param {Object} props
 * @param {number} props.entryId - The entry ID for fetching notes
 * @return {JSX.Element} NotesSection component
 */
const NotesSection = ( { entryId } ) => {
	const NoteComponent = applyFilters(
		'srfm-pro.entry-details.render-notes-section'
	);

	if ( !! NoteComponent ) {
		return <NoteComponent entryId={ entryId } />;
	}

	return (
		<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
			<div className="pb-0 px-4 pt-4">
				<div className="flex items-center justify-between">
					<h3 className="text-base font-semibold text-text-primary">
						{ __( 'Notes', 'sureforms' ) }
					</h3>
					<AddEntryButton />
				</div>
			</div>
			<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
				{ /* Empty State */ }
				<div className="relative flex items-center justify-center py-6 px-1 gap-2.5">
					<FileSearch className="text-icon-secondary size-4" />
					<Text color="secondary" size={ 12 } weight={ 400 }>
						{ __( 'Add an internal note.', 'sureforms' ) }
					</Text>
				</div>
			</div>
		</div>
	);
};

export default NotesSection;
