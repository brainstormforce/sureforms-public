import { Button, Text, TextArea } from '@bsf/force-ui';
import {
	Plus,
	X,
	Trash,
	FileSearch,
	ArrowLeft,
	ArrowRight,
} from 'lucide-react';
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { formatDateTime2 } from './utils';

const PaymentNotes = ( {
	notes,
	isAddingNote,
	newNoteText,
	setNewNoteText,
	handleAddNoteClick,
	handleSaveNote,
	handleCancelNote,
	handleDeleteNote,
	addNoteMutation,
	deleteNoteMutation,
	onConfirmation,
} ) => {
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const itemsPerPage = 3;

	// Calculate pagination
	const totalPages = Math.ceil( ( notes?.length || 0 ) / itemsPerPage );
	const startIndex = ( currentPage - 1 ) * itemsPerPage;
	const endIndex = startIndex + itemsPerPage;
	const paginatedNotes = notes?.slice( startIndex, endIndex ) || [];

	// Reset to page 1 when a new note is added
	useEffect( () => {
		if ( addNoteMutation.isSuccess && ! addNoteMutation.isPending ) {
			setCurrentPage( 1 );
		}
	}, [ addNoteMutation.isSuccess, addNoteMutation.isPending ] );

	// Adjust current page if it exceeds total pages after deletion
	useEffect( () => {
		if ( notes?.length > 0 ) {
			const maxPossiblePage = Math.ceil( notes.length / itemsPerPage );
			if ( currentPage > maxPossiblePage ) {
				setCurrentPage( maxPossiblePage );
			}
		}
	}, [ notes?.length, currentPage, itemsPerPage ] );

	const handlePreviousPage = () => {
		setCurrentPage( ( prev ) => Math.max( prev - 1, 1 ) );
	};

	const handleNextPage = () => {
		setCurrentPage( ( prev ) => Math.min( prev + 1, totalPages ) );
	};

	const handleKeyDown = ( event ) => {
		// Submit on Cmd+Enter (Mac) or Ctrl+Enter (Windows/Linux)
		if ( ( event.metaKey || event.ctrlKey ) && event.key === 'Enter' ) {
			event.preventDefault();
			handleSaveNote( event );
		}
	};

	const handleDeleteNoteConfirmation = ( noteIndex ) => {
		onConfirmation( {
			title: __( 'Delete Note?', 'sureforms' ),
			description: __(
				'This action cannot be undone. The note will be permanently deleted.',
				'sureforms'
			),
			confirmLabel: __( 'Delete Note', 'sureforms' ),
			onConfirm: () => handleDeleteNote( noteIndex ),
			isLoading: deleteNoteMutation.isPending,
			destructive: true,
		} );
	};

	const selfFocus = useCallback( ( node ) => {
		if ( ! node ) {
			return;
		}
		node.focus();
	}, [] );

	const addNewComponent = () => {
		return (
			<div className="bg-background-primary rounded-md p-3 relative shadow-sm">
				<TextArea
					ref={ selfFocus }
					value={ newNoteText }
					onChange={ ( value ) => setNewNoteText( value ) }
					placeholder={ __( 'Add an internal note…', 'sureforms' ) }
					required
					size="md"
					rows={ 5 }
					className="w-full"
					onKeyDown={ handleKeyDown }
				/>
				<div className="flex gap-2 mt-2 justify-end">
					<Button
						variant="primary"
						size="sm"
						onClick={ handleSaveNote }
						disabled={
							addNoteMutation.isPending || ! newNoteText.trim()
						}
					>
						{ __( 'Submit Note', 'sureforms' ) }
					</Button>
				</div>
			</div>
		);
	};

	const isNotesAvailable = notes && notes.length > 0;

	return (
		<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
			<div className="pb-0 px-4 pt-4">
				<div className="flex items-center justify-between">
					<h3 className="text-base font-semibold text-text-primary">
						{ __( 'Notes', 'sureforms' ) }
					</h3>
					<Button
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
							isAddingNote ? handleCancelNote : handleAddNoteClick
						}
						disabled={
							addNoteMutation.isPending ||
							deleteNoteMutation.isPending
						}
						className="text-link-primary hover:text-link-primary-hover"
					>
						{ isAddingNote
							? __( 'Cancel Note', 'sureforms' )
							: __( 'Add Note', 'sureforms' ) }
					</Button>
				</div>
			</div>
			<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
				{ isAddingNote && addNewComponent() }
				{ isNotesAvailable
					? paginatedNotes.map( ( note, index ) => {
						// Calculate the actual index in the original notes array
						const actualIndex = startIndex + index;
						return (
							<div
								key={ index }
								className="bg-background-primary rounded-md p-2 relative shadow-sm flex items-start justify-between gap-3"
							>
								<div className="flex-1 flex flex-col gap-2">
									<Text className="text-sm font-semibold text-text-secondary">
										{ sprintf(
											// translators: %1$s - user name, %2$s - date.
											__(
												'Submitted by %1$s - %2$s',
												'sureforms'
											),
											note.created_by_user_name,
											formatDateTime2(
												note.created_at
											)
										) }
									</Text>
									<Text
										size={ 14 }
										weight={ 400 }
										color="primary"
										className="[overflow-wrap:anywhere]"
									>
										{ note.text }
									</Text>
								</div>
								<Button
									variant="ghost"
									size="xs"
									icon={ <Trash className="w-4 h-4" /> }
									onClick={ () =>
										handleDeleteNoteConfirmation(
											actualIndex
										)
									}
									disabled={
										deleteNoteMutation.isPending
									}
									className="text-icon-secondary hover:text-icon-primary flex-shrink-0"
									aria-label={ __(
										'Delete note',
										'sureforms'
									) }
								/>
							</div>
						);
					  } )
					: ! isAddingNote && (
						<div className="relative flex items-center justify-center py-6 px-1 gap-2.5">
							<FileSearch className="text-icon-secondary size-4" />
							<Text
								color="secondary"
								size={ 12 }
								weight={ 400 }
							>
								{ __(
									'Add an internal note.',
									'sureforms'
								) }
							</Text>
						</div>
					  ) }
			</div>
			{ /* Pagination */ }
			{ isNotesAvailable && totalPages > 1 && (
				<div className="w-full flex items-center justify-end pb-3 pr-3 pl-3">
					<div className="flex items-center gap-2">
						<Button
							variant="ghost"
							size="xs"
							icon={ <ArrowLeft /> }
							iconPosition="left"
							onClick={ handlePreviousPage }
							disabled={ currentPage === 1 }
						>
							{ __( 'Previous', 'sureforms' ) }
						</Button>
						<Button
							variant="ghost"
							size="xs"
							icon={ <ArrowRight /> }
							iconPosition="right"
							onClick={ handleNextPage }
							disabled={ currentPage === totalPages }
						>
							{ __( 'Next', 'sureforms' ) }
						</Button>
					</div>
				</div>
			) }
		</div>
	);
};

export default PaymentNotes;
