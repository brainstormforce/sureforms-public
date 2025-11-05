import { Dialog, Button, Container, Input, Text } from '@bsf/force-ui';
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, renderToString } from '@wordpress/element';

/**
 * ConfirmationDialog component
 * Displays a confirmation dialog for destructive actions
 *
 * @param {Object}                    props                    - Component props
 * @param {boolean}                   props.open               - Whether the dialog is open
 * @param {Function}                  props.setOpen            - Function to control dialog open state
 * @param {Function}                  props.onConfirm          - Callback function when user confirms
 * @param {string}                    props.title              - Dialog title
 * @param {string}                    props.description        - Dialog description text
 * @param {import('react').ReactNode} props.body               - Optional body content (e.g., warning message)
 * @param {string}                    props.confirmLabel       - Label for confirm button (default: "Delete")
 * @param {string}                    props.cancelLabel        - Label for cancel button (default: "Cancel")
 * @param {boolean}                   props.isLoading          - Whether the action is in progress
 * @param {boolean}                   props.destructive        - Whether the action is destructive (default: true)
 * @param {boolean}                   props.enableVerification - Whether to enable text verification (default: false)
 * @param {string}                    props.verificationText   - Text user must type to confirm (default: "confirm")
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
	enableVerification = false,
	verificationText = 'CONFIRM',
} ) => {
	const [ verificationInput, setVerificationInput ] = useState( '' );

	// Reset verification input when dialog opens/closes
	useEffect( () => {
		if ( ! open ) {
			setVerificationInput( '' );
		}
	}, [ open ] );

	const isVerified =
		! enableVerification || verificationInput === verificationText;

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

				{ enableVerification && (
					<Dialog.Body>
						<div className="space-y-2">
							<Text
								as="label"
								size={ 14 }
								color="label"
								htmlFor="srmf-confirmation-verification"
								dangerouslySetInnerHTML={ {
									__html: sprintf(
										// translators: %s: verification text
										__(
											'To confirm, type %s in the box below:',
											'sureforms'
										),
										renderToString(
											<strong>
												{ verificationText }
											</strong>
										)
									),
								} }
							/>
							<Input
								id="srmf-confirmation-verification"
								type="text"
								value={ verificationInput }
								onChange={ ( value ) =>
									setVerificationInput( value )
								}
								placeholder={ sprintf(
									// translators: %s: verification text
									__( 'Type %s', 'sureforms' ), verificationText
								) }
								className="w-full"
								size="md"
								autoComplete="off"
							/>
						</div>
					</Dialog.Body>
				) }

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
							disabled={ isLoading || ! isVerified }
							destructive={ destructive }
						>
							{ isLoading
								? __( 'Deleting…', 'sureforms' )
								: confirmLabel }
						</Button>
					</Container>
				</Dialog.Footer>
			</Dialog.Panel>
		</Dialog>
	);
};

export default ConfirmationDialog;
