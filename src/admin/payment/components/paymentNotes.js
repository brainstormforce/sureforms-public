import {
	Container,
	Label,
	Button,
	Text,
	TextArea,
	Tooltip,
} from '@bsf/force-ui';
import {
	Plus,
	Trash2,
	FileSearch2,
	ChevronLeft,
	ChevronRight,
} from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

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
} ) => {
	const [ showDeletePopup, setShowDeletePopup ] = useState( null );
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

	const addNewComponent = () => {
		return (
			<div className="w-full p-3 bg-background-primary rounded-lg border border-border-subtle">
				<TextArea
					value={ newNoteText }
					onChange={ ( value ) => setNewNoteText( value ) }
					placeholder={ __( 'Add an internal note…', 'sureforms' ) }
					size="l"
					className="w-full h-[158px]"
				/>
				<div className="flex gap-2 mt-2 justify-end">
					<Button
						variant="outline"
						size="sm"
						onClick={ handleCancelNote }
						disabled={ addNoteMutation.isPending }
					>
						{ __( 'Cancel', 'sureforms' ) }
					</Button>
					<Button
						variant="primary"
						size="sm"
						onClick={ handleSaveNote }
						disabled={
							addNoteMutation.isPending || ! newNoteText.trim()
						}
					>
						{ addNoteMutation.isPending
							? __( 'Adding…', 'sureforms' )
							: __( 'Add Note', 'sureforms' ) }
					</Button>
				</div>
			</div>
		);
	};

	return (
		<Container
			className="w-full bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-3 gap-2 shadow-sm"
			direction="column"
		>
			<Container className="p-1 gap-2" align="center" justify="between">
				<Label size="md" className="font-semibold">
					{ __( 'Notes', 'sureforms' ) }
				</Label>
				<Button
					icon={ <Plus className="!size-5" /> }
					iconPosition="left"
					variant="link"
					size="xs"
					className="h-full text-link-primary no-underline hover:no-underline hover:text-link-primary-hover px-1 content-center [box-shadow:none] focus:[box-shadow:none] focus:outline-none"
					onClick={ handleAddNoteClick }
					disabled={ isAddingNote }
				>
					{ __( 'Add Note', 'sureforms' ) }
				</Button>
			</Container>
			<Container className="flex flex-col items-center justify-center bg-background-secondary gap-1 p-1 rounded-lg min-h-[89px]">
				{ isAddingNote && addNewComponent() }
				{ notes && notes.length > 0
					? paginatedNotes.map( ( note, index ) => {
						// Calculate the actual index in the original notes array
						const actualIndex = startIndex + index;
						return (
							<div
								key={ index }
								className="w-full flex justify-between items-start gap-2 p-3 bg-background-primary rounded-lg border border-border-subtle"
							>
								<div className="flex-1">
									<Text className="text-sm text-text-primary">
										{ note.text || note }
									</Text>
									{ note.created_at && (
										<Text className="text-xs text-text-tertiary mt-1">
											{ new Date(
												note.created_at
											).toLocaleString() }
										</Text>
									) }
								</div>
								<Tooltip
									arrow
									offset={ 20 }
									content={
										<Container
											direction="column"
											className="gap-2"
										>
											<p className="text-[13px] font-normal">
												{ __(
													'Are you sure to delete this?',
													'sureforms'
												) }
											</p>
											<Container className="gap-3">
												<Button
													variant="outline"
													size="xs"
													onClick={ () =>
														setShowDeletePopup(
															null
														)
													}
													className="px-3"
												>
													{ __(
														'Cancel',
														'sureforms'
													) }
												</Button>
												<Button
													variant="primary"
													size="xs"
													onClick={ () => {
														handleDeleteNote(
															actualIndex
														);
														setShowDeletePopup(
															null
														);
													} }
													className="px-2 ml-2 bg-red-600 hover:bg-red-700 border-red-600 hover:border-red-700"
												>
													{ __(
														'Confirm',
														'sureforms'
													) }
												</Button>
											</Container>
										</Container>
									}
									placement="top"
									triggers={ [ 'click', 'focus' ] }
									tooltipPortalId="srfm-settings-container"
									interactive
									className="z-999999"
									variant="light"
									open={ showDeletePopup === actualIndex }
									setOpen={ () =>
										setShowDeletePopup( actualIndex )
									}
								>
									<Button
										variant="ghost"
										size="xs"
										icon={
											<Trash2 className="!size-4" />
										}
										onClick={ () =>
											setShowDeletePopup(
												actualIndex
											)
										}
										disabled={
											deleteNoteMutation.isPending
										}
										className="text-icon-secondary hover:text-red-700"
									/>
								</Tooltip>
							</div>
						);
					  } )
					: ! isAddingNote && (
						<Text className="text-sm text-text-secondary p-3 text-center flex items-center justify-center gap-2">
							<FileSearch2 className="!size-5" />
							{ __(
								'Add an internal note about this transaction',
								'sureforms'
							) }
						</Text>
					  ) }
			</Container>

			{ /* Pagination Controls - Show only if more than 3 notes */ }
			{ notes && notes.length > itemsPerPage && (
				<Container className="flex items-center justify-between px-2 py-1">
					<Text className="text-xs text-text-secondary">
						{ __( 'Page', 'sureforms' ) } { currentPage }{ ' ' }
						{ __( 'of', 'sureforms' ) } { totalPages }
					</Text>
					<Container className="gap-1">
						<Button
							variant="ghost"
							size="xs"
							icon={ <ChevronLeft className="!size-4" /> }
							onClick={ handlePreviousPage }
							disabled={ currentPage === 1 }
							className="text-icon-secondary hover:text-icon-primary"
						/>
						<Button
							variant="ghost"
							size="xs"
							icon={ <ChevronRight className="!size-4" /> }
							onClick={ handleNextPage }
							disabled={ currentPage === totalPages }
							className="text-icon-secondary hover:text-icon-primary"
						/>
					</Container>
				</Container>
			) }
		</Container>
	);
};

export default PaymentNotes;
