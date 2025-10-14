import { __, sprintf } from '@wordpress/i18n';
import { Plus, Trash2 } from 'lucide-react';
import { Button } from '@bsf/force-ui';

/**
 * NotesSection Component
 * Displays notes for an entry with add note functionality
 *
 * @param {Object} props
 * @param {Object} props.entryData - The entry data object
 */
const NotesSection = ( { entryData } ) => {
	// Mock data for now - replace with actual notes data
	const notes = [
		{
			id: 1,
			author: 'Aaditya',
			date: '4th may 2024',
			content: 'Suspendisse egestas amet in varius commodo in in morbi. Quis viverra in quis vitae aliquam viverra tellus phasellus.',
		},
		{
			id: 2,
			author: 'Aaditya',
			date: '4th may 2024',
			content: 'Suspendisse egestas amet in varius commodo in in morbi. Quis viverra in quis vitae aliquam viverra tellus phasellus.',
		},
		{
			id: 3,
			author: 'Aaditya',
			date: '4th may 2024',
			content: 'Suspendisse egestas amet in varius commodo in in morbi. Quis viverra in quis vitae aliquam viverra tellus phasellus.',
		},
	];

	const handleAddNote = () => {
		// TODO: Implement add note functionality
		console.log( 'Add note clicked' );
	};

	const handleDeleteNote = ( noteId ) => {
		// TODO: Implement delete note functionality
		console.log( 'Delete note', noteId );
	};

	return (
		<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
			<div className="p-4">
				<div className="flex items-center justify-between">
					<h3 className="text-base font-semibold text-text-primary">
						{ __( 'Notes', 'sureforms' ) }
					</h3>
					<Button
						variant="link"
						size="xs"
						icon={ <Plus className="w-4 h-4" /> }
						iconPosition="left"
						onClick={ handleAddNote }
						className="text-link-primary hover:text-link-primary-hover"
					>
						{ __( 'Add Note', 'sureforms' ) }
					</Button>
				</div>
			</div>
			<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
				{ notes.map( ( note ) => (
					<div
						key={ note.id }
						className="bg-background-primary rounded-md p-3 relative z-10 shadow-sm"
					>
						<div className="flex justify-between items-start gap-4">
							<div className="flex-1 space-y-2">
								<div className="text-sm font-semibold text-text-primary">
									{ sprintf(
										// translators: %1$s is author name, %2$s is date
										__( 'Submitted by %1$s - %2$s', 'sureforms' ),
										note.author,
										note.date
									) }
								</div>
								<div className="text-xs font-normal text-text-tertiary">
									{ note.content }
								</div>
							</div>
							<Button
								variant="ghost"
								size="xs"
								icon={ <Trash2 className="w-4 h-4" /> }
								onClick={ () => handleDeleteNote( note.id ) }
								className="text-icon-secondary hover:text-icon-primary flex-shrink-0"
							/>
						</div>
					</div>
				) ) }
			</div>
		</div>
	);
};

export default NotesSection;
