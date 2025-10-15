import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	ArrowLeft,
	ArrowRight,
	FileSearch,
	Plus,
	Trash2,
} from 'lucide-react';
import { Button, Text, TextArea } from '@bsf/force-ui';
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
 * @param {Object} props.entryData - The entry data object
 */
const NotesSection = ( { entryData } ) => {
	const [ isAddingNote, setIsAddingNote ] = useState( false );
	const [ noteText, setNoteText ] = useState( '' );

	// Mock data for now - replace with actual notes data
	const notes = [
		/* {
			id: 1,
			author: 'Aaditya',
			date: '4th may 2024',
			content:
				'Suspendisse egestas amet in varius commodo in in morbi. Quis viverra in quis vitae aliquam viverra tellus phasellus.',
		},
		{
			id: 2,
			author: 'Aaditya',
			date: '4th may 2024',
			content:
				'Suspendisse egestas amet in varius commodo in in morbi. Quis viverra in quis vitae aliquam viverra tellus phasellus.',
		},
		{
			id: 3,
			author: 'Aaditya',
			date: '4th may 2024',
			content:
				'Suspendisse egestas amet in varius commodo in in morbi. Quis viverra in quis vitae aliquam viverra tellus phasellus.',
		}, */
	];

	const handleAddNote = () => {
		setIsAddingNote( true );
	};

	const handleCancelNote = () => {
		setIsAddingNote( false );
		setNoteText( '' );
	};

	const handleSubmitNote = () => {
		// TODO: Implement submit note functionality
		console.log( 'Submit note:', noteText );
		setIsAddingNote( false );
		setNoteText( '' );
	};

	const handleDeleteNote = ( noteId ) => {
		// TODO: Implement delete note functionality
		console.log( 'Delete note', noteId );
	};

	return (
		<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
			<div className="pb-0 px-4 pt-4">
				<div className="flex items-center justify-between">
					<h3 className="text-base font-semibold text-text-primary">
						{ __( 'Notes', 'sureforms' ) }
					</h3>
					<AddEntryButton />
					{ /* <Button
						variant="link"
						size="xs"
						icon={
							isAddingNote ? (
								<X className="w-4 h-4" />
							) : (
								<Plus className="w-4 h-4" />
							)
						}
						iconPosition="left"
						onClick={
							isAddingNote ? handleCancelNote : handleAddNote
						}
						className="text-link-primary hover:text-link-primary-hover"
					>
						{ isAddingNote
							? __( 'Cancel Note', 'sureforms' )
							: __( 'Add Note', 'sureforms' ) }
					</Button> */ }
				</div>
			</div>
			<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
				{ isAddingNote && (
					<div className="p-3 relative mb-3">
						<div className="space-y-3">
							<TextArea
								className="w-full"
								value={ noteText }
								onChange={ ( value ) => setNoteText( value ) }
								placeholder={ __(
									'Enter your note here…',
									'sureforms'
								) }
								rows={ 5 }
							/>
							<div className="flex justify-end">
								<Button
									variant="primary"
									size="sm"
									onClick={ handleSubmitNote }
									disabled={ ! noteText.trim() }
								>
									{ __( 'Submit Note', 'sureforms' ) }
								</Button>
							</div>
						</div>
					</div>
				) }
				{ /* Empty State */ }
				{ ! notes?.length && ! isAddingNote && (
					<div className="relative flex items-center justify-center py-6 px-1 gap-2.5">
						<FileSearch className="text-icon-secondary size-4" />
						<Text color="secondary" size={ 12 } weight={ 400 }>
							{ __( 'Add an internal note.', 'sureforms' ) }
						</Text>
					</div>
				) }
				{ /* Render notes */ }
				{ !! notes?.length &&
					notes.map( ( note ) => (
						<div
							key={ note.id }
							className="bg-background-primary rounded-md p-3 relative shadow-sm"
						>
							<div className="flex justify-between items-start gap-4">
								<div className="flex-1 space-y-2">
									<div className="text-sm font-semibold text-text-primary">
										{ sprintf(
											// translators: %1$s is author name, %2$s is date
											__(
												'Submitted by %1$s - %2$s',
												'sureforms'
											),
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
									onClick={ () =>
										handleDeleteNote( note.id )
									}
									className="text-icon-secondary hover:text-icon-primary flex-shrink-0"
								/>
							</div>
						</div>
					) ) }
			</div>
			{ /* Pagination */ }
			<div className="w-full flex items-center justify-end space-x-2 pb-3 pr-3">
				<Button
					variant="ghost"
					size="xs"
					icon={ <ArrowLeft /> }
					iconPosition="left"
				>
					{ __( 'Previous', 'sureforms' ) }
				</Button>
				<Button
					variant="ghost"
					size="xs"
					icon={ <ArrowRight /> }
					iconPosition="right"
				>
					{ __( 'Next', 'sureforms' ) }
				</Button>
			</div>
		</div>
	);
};

export default NotesSection;
