import { Container, Label, Button, Text, TextArea, Tooltip } from '@bsf/force-ui';
import { Plus, Trash2, FileSearch2 } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

const paymentNotes = ( {
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
				<Label size="sm" className="font-semibold">
					{ __( 'Notes', 'sureforms' ) }
				</Label>
				<Button
					icon={ <Plus className="!size-5" /> }
					iconPosition="left"
					variant="link"
					size="sm"
					className="h-full text-link-primary text-sm font-semibold no-underline hover:no-underline hover:text-link-primary-hover px-1 content-center [box-shadow:none] focus:[box-shadow:none] focus:outline-none"
					onClick={ handleAddNoteClick }
					disabled={ isAddingNote }
				>
					{ __( 'Add Note', 'sureforms' ) }
				</Button>
			</Container>
			<Container className="flex flex-col items-center justify-center bg-background-secondary gap-1 p-1 rounded-lg min-h-[89px]">
				{ isAddingNote && addNewComponent() }
				{ notes && notes.length > 0
					? notes.map( ( note, index ) => (
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
									<Container direction="column" className="gap-2">
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
												onClick={ () => setShowDeletePopup( null ) }
												className="px-3"
											>
												{ __( 'Cancel', 'sureforms' ) }
											</Button>
											<Button
												variant="primary"
												size="xs"
												onClick={ () => {
													handleDeleteNote( index );
													setShowDeletePopup( null );
												} }
												className="px-2 ml-2 bg-red-600 hover:bg-red-700 border-red-600 hover:border-red-700"
											>
												{ __( 'Confirm', 'sureforms' ) }
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
								open={ showDeletePopup === index }
								setOpen={ () => setShowDeletePopup( index ) }
							>
								<Button
									variant="ghost"
									size="xs"
									icon={ <Trash2 className="!size-4" /> }
									onClick={ () => setShowDeletePopup( index ) }
									disabled={ deleteNoteMutation.isPending }
									className="text-icon-secondary hover:text-red-700"
								/>
							</Tooltip>
						</div>
					  ) )
					: ! isAddingNote && (
						<Text className="text-sm text-text-secondary p-3 text-center flex items-center justify-center gap-2">
							<FileSearch2 className="!size-5" />
							{ __(
								'Add an internal note about this subscription',
								'sureforms'
							) }
						</Text>
					  ) }
			</Container>
		</Container>
	);
};

export default paymentNotes;
