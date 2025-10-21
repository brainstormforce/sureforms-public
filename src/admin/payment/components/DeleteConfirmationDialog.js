import { Dialog, Button } from '@bsf/force-ui';
import { __, sprintf } from '@wordpress/i18n';

/**
 * DeleteConfirmationDialog component for confirming payment deletion.
 *
 * @param {Object}   props                        - Component props.
 * @param {boolean}  props.isOpen                 - Whether the dialog is open.
 * @param {Function} props.setIsOpen              - Function to set dialog open state.
 * @param {Array}    props.deletePaymentIds       - Array of payment IDs to delete.
 * @param {Function} props.onConfirm              - Callback when delete is confirmed.
 * @param {Function} props.onCancel               - Callback when delete is canceled.
 * @param {boolean}  props.isDeleting             - Whether deletion is in progress.
 * @return {JSX.Element} The DeleteConfirmationDialog component.
 */
const DeleteConfirmationDialog = ( {
	isOpen,
	setIsOpen,
	deletePaymentIds,
	onConfirm,
	onCancel,
	isDeleting = false,
} ) => {
	return (
		<Dialog
			open={ isOpen }
			setOpen={ setIsOpen }
			design="simple"
			exitOnEsc
			scrollLock
		>
			<Dialog.Backdrop />
			<Dialog.Panel>
				<Dialog.Header>
					<div className="flex items-center justify-between">
						<Dialog.Title>
							{ __( 'Delete Payments', 'sureforms' ) }
						</Dialog.Title>
						<Dialog.CloseButton onClick={ onCancel } />
					</div>
					<Dialog.Description>
						{ deletePaymentIds.length === 1
							? __(
									'Are you sure you want to delete this payment? This action cannot be undone.',
									'sureforms'
							  )
							: sprintf(
									/* translators: %d: number of payments */
									__(
										'Are you sure you want to delete %d payments? This action cannot be undone.',
										'sureforms'
									),
									deletePaymentIds.length
							  ) }
					</Dialog.Description>
				</Dialog.Header>
				<Dialog.Body>
					<div className="p-3 border border-red-200 rounded-md bg-red-50">
						<p className="text-sm text-red-700">
							{ __(
								'Warning: Deleting payments will permanently remove all associated data including notes, logs, and transaction information.',
								'sureforms'
							) }
						</p>
					</div>
				</Dialog.Body>
				<Dialog.Footer className="flex justify-end gap-2">
					<Button
						variant="outline"
						onClick={ onCancel }
						disabled={ isDeleting }
					>
						{ __( 'Cancel', 'sureforms' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ onConfirm }
						disabled={ isDeleting }
						className="bg-red-600 hover:bg-red-700 border-red-600 hover:border-red-700"
					>
						{ isDeleting
							? __( 'Deleting…', 'sureforms' )
							: __( 'Delete Payments', 'sureforms' ) }
					</Button>
				</Dialog.Footer>
			</Dialog.Panel>
		</Dialog>
	);
};

export default DeleteConfirmationDialog;
