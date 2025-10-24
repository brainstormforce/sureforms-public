import { Dialog, Button, Container } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';

/**
 * ConfirmationDialog component
 * Displays a confirmation dialog for destructive actions
 */
const ConfirmationDialog = ( {
	open,
	setOpen,
	onConfirm,
	title = __( 'Confirm Action', 'sureforms' ),
	description = __( 'Are you sure you want to proceed?', 'sureforms' ),
	body = null,
	confirmLabel = __( 'Delete', 'sureforms' ),
	cancelLabel = __( 'Cancel', 'sureforms' ),
	isLoading = false,
	destructive = true,
} ) => {
	const handleConfirm = () => {
		onConfirm();
		setOpen( false );
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
			<Dialog.Panel className="max-w-md">
				<Dialog.Header>
					<div className="flex items-center justify-between">
						<Dialog.Title>{ title }</Dialog.Title>
						<Dialog.CloseButton />
					</div>
					{ description && (
						<Dialog.Description>{ description }</Dialog.Description>
					) }
				</Dialog.Header>

				{ body && <Dialog.Body>{ body }</Dialog.Body> }
				
				<Dialog.Footer className="border-t border-b-0 border-x-0 border-solid border-border-subtle">
					<Container gap="sm" justify="end">
						<Button
							variant="ghost"
							onClick={ () => setOpen( false ) }
							disabled={ isLoading }
						>
							{ cancelLabel }
						</Button>
						<Button
							variant="primary"
							onClick={ handleConfirm }
							disabled={ isLoading }
							destructive={ destructive }
						>
							{ isLoading
								? __( 'Processing…', 'sureforms' )
								: confirmLabel }
						</Button>
					</Container>
				</Dialog.Footer>
			</Dialog.Panel>
		</Dialog>
	);
};

export default ConfirmationDialog;