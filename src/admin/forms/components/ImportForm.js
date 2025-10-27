import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, Container, Text, Dialog } from '@bsf/force-ui';
import { CloudUpload, File, Trash } from 'lucide-react';
import apiFetch from '@wordpress/api-fetch';

/**
 * ImportForm Component
 * Dialog for importing forms from JSON file
 */
const ImportForm = ( { open, setOpen, onImportSuccess } ) => {
	const [ selectedFile, setSelectedFile ] = useState( null );
	const [ isImporting, setIsImporting ] = useState( false );
	const [ error, setError ] = useState( null );

	// Format file size
	const formatFileSize = ( bytes ) => {
		if ( bytes === 0 ) return '0 Bytes';
		const k = 1024;
		const sizes = [ 'Bytes', 'KB', 'MB', 'GB' ];
		const i = Math.floor( Math.log( bytes ) / Math.log( k ) );
		return parseFloat( ( bytes / Math.pow( k, i ) ).toFixed( 2 ) ) + ' ' + sizes[ i ];
	};

	// Handle file selection
	const handleFileChange = ( event ) => {
		const file = event.target.files[ 0 ];
		if ( file ) {
			// Validate file type
			if ( file.type !== 'application/json' && ! file.name.endsWith( '.json' ) ) {
				setError( __( 'Please select a valid JSON file.', 'sureforms' ) );
				return;
			}
			setSelectedFile( file );
			setError( null );
		}
	};

	// Handle file removal
	const handleFileRemove = () => {
		setSelectedFile( null );
		setError( null );
		// Reset file input
		const fileInput = document.getElementById( 'import-file-input' );
		if ( fileInput ) {
			fileInput.value = '';
		}
	};

	// Handle import
	const handleImport = async () => {
		if ( ! selectedFile ) {
			setError( __( 'Please select a file to import.', 'sureforms' ) );
			return;
		}

		setIsImporting( true );
		setError( null );

		try {
			// Read file content
			const fileContent = await new Promise( ( resolve, reject ) => {
				const reader = new FileReader();
				reader.onload = ( e ) => {
					try {
						const data = JSON.parse( e.target.result );
						resolve( data );
					} catch ( parseError ) {
						reject( new Error( __( 'Invalid JSON file format.', 'sureforms' ) ) );
					}
				};
				reader.onerror = () => reject( new Error( __( 'Failed to read file.', 'sureforms' ) ) );
				reader.readAsText( selectedFile );
			} );

			// Call import API
			const response = await apiFetch( {
				path: '/sureforms/v1/forms/import',
				method: 'POST',
				data: {
					forms_data: fileContent,
					default_status: 'draft',
				},
			} );

			if ( response.success ) {
				onImportSuccess?.( response );
				setOpen( false );
				// Reset state
				setSelectedFile( null );
				setError( null );
			} else {
				throw new Error( response.message || __( 'Import failed.', 'sureforms' ) );
			}
		} catch ( error ) {
			console.error( 'Import error:', error );
			setError( error.message || __( 'An error occurred during import.', 'sureforms' ) );
		} finally {
			setIsImporting( false );
		}
	};

	// Handle close
	const handleClose = () => {
		setSelectedFile( null );
		setError( null );
		setIsImporting( false );
		setOpen( false );
	};

	// Handle drag and drop
	const handleDragOver = ( e ) => {
		e.preventDefault();
	};

	const handleDrop = ( e ) => {
		e.preventDefault();
		const file = e.dataTransfer.files[ 0 ];
		if ( file ) {
			if ( file.type !== 'application/json' && ! file.name.endsWith( '.json' ) ) {
				setError( __( 'Please select a valid JSON file.', 'sureforms' ) );
				return;
			}
			setSelectedFile( file );
			setError( null );
		}
	};

	return (
		<Dialog
			design="simple"
			exitOnEsc
			scrollLock
			open={ open }
			setOpen={ setOpen }
			className="z-999999"
		>
			<Dialog.Backdrop />
			<Dialog.Panel className="max-w-lg">
				<Dialog.Header>
					<div className="flex items-center justify-between">
						<Dialog.Title>{ __( 'Import Forms', 'sureforms' ) }</Dialog.Title>
						<Dialog.CloseButton />
					</div>
					<Dialog.Description>
						{ __( 'Select the SureForms export file (.json) that you wish to import.', 'sureforms' ) }
					</Dialog.Description>
				</Dialog.Header>

				<Dialog.Body>
				<Container className="gap-3 flex-col">
					{ /* File Upload Area - Always visible */ }
					<div
						className="border border-dashed rounded-lg p-3 cursor-pointer border-field-color-disabled hover:border-border-interactive transition-colors"
						onDragOver={ handleDragOver }
						onDrop={ handleDrop }
						onClick={ () => document.getElementById( 'import-file-input' ).click() }
					>
						<Container direction="row" className="gap-3">
							<CloudUpload className="size-5" style={ { color: '#DC4809' } } />
							<Container className="gap-1 flex-col items-start">
								<Text size={ 14 } weight={ 500 } className='text-field-label'>
									{ __( 'Drag and drop or browse files', 'sureforms' ) }
								</Text>
								<Text size={ 12 } className='text-field-helper'>
									{ __( 'Drop the form (.json) file here', 'sureforms' ) }
								</Text>
							</Container>
						</Container>
					</div>

					{ /* Selected File Display - Shows below upload area */ }
					{ selectedFile && (
						<div className="border border-field-border rounded-lg p-2 bg-background-tertiary">
							<Container direction="row" justify="between" align="start" className="w-full">
								<Container direction="row" className="gap-3">
									<File className="size-5 text-text-secondary" />
									<Container className="gap-0 flex-col items-start">
										<Text size={ 14 } weight={ 500 } color="primary">
											{ selectedFile.name }
										</Text>
										<Text size={ 12 } color="tertiary">
											{ formatFileSize( selectedFile.size ) }
										</Text>
									</Container>
								</Container>
								<Button
									variant="ghost"
									size="xs"
									icon={ <Trash className="w-4 h-4" /> }
									onClick={ handleFileRemove }
									className="p-1 text-text-tertiary hover:text-text-error hover:bg-background-transparent self-start"
								/>
							</Container>
						</div>
					) }

					{ /* Hidden File Input */ }
					<input
						id="import-file-input"
						type="file"
						accept=".json,application/json"
						onChange={ handleFileChange }
						className="hidden"
					/>

					{ /* Error Message */ }
					{ error && (
						<div className="bg-background-error border border-border-error rounded-lg p-3">
							<Text size={ 14 } color="error">
								{ error }
							</Text>
						</div>
					) }
				</Container>
				</Dialog.Body>

				<Dialog.Footer className="border-t border-b-0 border-x-0 border-solid border-border-subtle">
					<Container gap="sm" justify="end">
						<Button
							variant="ghost"
							onClick={ handleClose }
							disabled={ isImporting }
						>
							{ __( 'Cancel', 'sureforms' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleImport }
							disabled={ ! selectedFile || isImporting }
						>
							{ isImporting ? __( 'Importing...', 'sureforms' ) : __( 'Import Form', 'sureforms' ) }
						</Button>
					</Container>
				</Dialog.Footer>
			</Dialog.Panel>
		</Dialog>
	);
};

export default ImportForm;