import { useState, useEffect } from '@wordpress/element';
import { Dialog, Input, Label, TextArea, Text, Button } from '@bsf/force-ui';
import { __, sprintf } from '@wordpress/i18n';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { refundPayment } from './apiCalls';
import { formatAmount, amountToStripeFormat } from './utils';

/**
 * RefundDialog Component
 * Reusable refund dialog for both single payments and subscription payments
 *
 * @param {Object}   props           Component props
 * @param {boolean}  props.isOpen    Whether the dialog is open
 * @param {Function} props.setIsOpen Function to control dialog open state
 * @param {Object}   props.payment   Payment object containing payment details
 * @param {string}   props.queryKey  Query key to invalidate after successful refund
 * @return {JSX.Element} RefundDialog component
 */
const RefundDialog = ( { isOpen, setIsOpen, payment, queryKey } ) => {
	const queryClient = useQueryClient();
	const [ refundAmount, setRefundAmount ] = useState( '' );
	const [ refundNotes, setRefundNotes ] = useState( '' );

	// Calculate refundable amount
	const totalAmount = payment ? parseFloat( payment.total_amount ) : 0;
	const alreadyRefunded = payment
		? parseFloat( payment.refunded_amount || 0 )
		: 0;
	const refundableAmount = totalAmount - alreadyRefunded;

	// Set default refund amount when dialog opens
	useEffect( () => {
		if ( isOpen && payment ) {
			setRefundAmount( refundableAmount.toFixed( 2 ) );
		}
	}, [ isOpen, payment, refundableAmount ] );

	// Refund mutation
	const refundMutation = useMutation( {
		mutationFn: refundPayment,
		onSuccess: () => {
			// Refresh payment data
			queryClient.invalidateQueries( queryKey );
			queryClient.invalidateQueries( [ 'payments' ] );
			closeDialog();
		},
		onError: ( error ) => {
			console.error( 'Refund failed:', error );
			alert( __( 'Refund failed. Please try again.', 'sureforms' ) );
		},
	} );

	const closeDialog = () => {
		setIsOpen( false );
		setRefundAmount( '' );
		setRefundNotes( '' );
	};

	const processRefund = () => {
		if ( ! payment || ! refundAmount ) {
			return;
		}

		const requestedAmount = parseFloat( refundAmount );

		// Auto-determine refund type based on amount
		const isFullRefund =
			Math.abs( requestedAmount - refundableAmount ) < 0.01; // Allow for small floating point differences
		const refundType = isFullRefund ? 'full' : 'partial';

		// Convert to Stripe's smallest currency unit (cents for USD, whole for JPY/KRW)
		const finalRefundAmount = amountToStripeFormat(
			requestedAmount,
			payment.currency
		);

		refundMutation.mutate( {
			paymentId: payment.id,
			transactionId: payment.transaction_id,
			refundAmount: finalRefundAmount,
			refundType,
			refundNotes,
		} );
	};

	// Generate dynamic refund message based on input amount
	const getRefundMessage = () => {
		if ( ! payment || ! refundAmount ) {
			return '';
		}

		const requestedAmount = parseFloat( refundAmount );

		// Validation checks
		if ( isNaN( requestedAmount ) || requestedAmount <= 0 ) {
			return {
				type: 'error',
				message: __(
					'Please enter a valid refund amount.',
					'sureforms'
				),
			};
		}

		if ( requestedAmount > refundableAmount ) {
			return {
				type: 'error',
				message: sprintf(
					/* translators: %s: maximum refundable amount */
					__( 'Amount cannot exceed %s.', 'sureforms' ),
					formatAmount( refundableAmount, payment.currency )
				),
			};
		}

		// Check if it's a full refund (allowing for small floating point differences)
		const isFullRefund =
			Math.abs( requestedAmount - refundableAmount ) < 0.01;

		if ( isFullRefund ) {
			return {
				type: 'info',
				message: sprintf(
					/* translators: %s: refund amount */
					__(
						'This will issue a complete refund of %s. The entire payment will be refunded.',
						'sureforms'
					),
					formatAmount( requestedAmount, payment.currency )
				),
			};
		}

		const remainingBalance = refundableAmount - requestedAmount;
		return {
			type: 'warning',
			message: sprintf(
				/* translators: 1: partial refund amount, 2: remaining balance */
				__(
					'This will issue a partial refund of %1$s. Remaining balance of %2$s will still be valid.',
					'sureforms'
				),
				formatAmount( requestedAmount, payment.currency ),
				formatAmount( remainingBalance, payment.currency )
			),
		};
	};

	// Get dynamic message for current refund amount
	const refundMessage = getRefundMessage();
	const isValidRefund = refundMessage && refundMessage.type !== 'error';

	if ( ! payment ) {
		return null;
	}

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
							{ __( 'Refund Payment', 'sureforms' ) }
						</Dialog.Title>
						<Dialog.CloseButton onClick={ closeDialog } />
					</div>
					<Dialog.Description>
						{ sprintf(
							/* translators: %s: payment ID */
							__(
								"Process refund for payment #%s. The refunded amount will be sent to the customer's original payment method.",
								'sureforms'
							),
							payment.id
						) }
					</Dialog.Description>
				</Dialog.Header>
				<Dialog.Body>
					<div className="space-y-4">
						<div>
							<Label className="text-sm font-medium">
								{ __( 'Refund Amount', 'sureforms' ) }
							</Label>
							<Input
								type="number"
								value={ refundAmount }
								onChange={ setRefundAmount }
								placeholder={ sprintf(
									/* translators: %s: maximum refundable amount */
									__( 'Max: %s', 'sureforms' ),
									refundableAmount.toFixed( 2 )
								) }
								max={ refundableAmount }
								min="0.01"
								step="0.01"
								className="mt-1"
							/>
							<Text className="text-xs text-text-secondary mt-1">
								{ sprintf(
									/* translators: %s: maximum refundable amount */
									__(
										'Maximum refundable amount: %s',
										'sureforms'
									),
									formatAmount(
										refundableAmount,
										payment.currency
									)
								) }
							</Text>
						</div>

						<div>
							<Label className="text-sm font-medium">
								{ __( 'Refund Notes (Optional)', 'sureforms' ) }
							</Label>
							<TextArea
								value={ refundNotes }
								onChange={ setRefundNotes }
								placeholder={ __(
									'Add a reason or note for this refund…',
									'sureforms'
								) }
								className="mt-1 w-full"
								rows={ 3 }
							/>
							<Text className="text-xs text-text-secondary mt-1">
								{ __(
									'This note will be stored with the refund record for future reference.',
									'sureforms'
								) }
							</Text>
						</div>

						{ /* Dynamic refund message */ }
						{ refundMessage && (
							<div
								className={ `p-3 rounded-md ${
									refundMessage.type === 'error'
										? 'bg-red-50 border border-red-200'
										: refundMessage.type === 'warning'
											? 'bg-yellow-50 border border-yellow-200'
											: 'bg-blue-50 border border-blue-200'
								}` }
							>
								<Text
									className={ `text-sm ${
										refundMessage.type === 'error'
											? 'text-red-700'
											: refundMessage.type === 'warning'
												? 'text-yellow-700'
												: 'text-blue-700'
									}` }
								>
									{ refundMessage.message }
								</Text>
							</div>
						) }

						{ /* Refund history info */ }
						{ alreadyRefunded > 0 && (
							<div className="p-3 border border-border-subtle rounded-md bg-background-secondary">
								<Text className="text-sm text-text-secondary">
									{ sprintf(
										/* translators: %s: already refunded amount */
										__(
											'Already refunded: %s',
											'sureforms'
										),
										formatAmount(
											alreadyRefunded,
											payment.currency
										)
									) }
								</Text>
							</div>
						) }
					</div>
				</Dialog.Body>
				<Dialog.Footer className="flex justify-end gap-2">
					<Button
						variant="outline"
						onClick={ closeDialog }
						disabled={ refundMutation.isPending }
					>
						{ __( 'Cancel', 'sureforms' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ processRefund }
						disabled={
							refundMutation.isPending ||
							! isValidRefund ||
							! refundAmount
						}
					>
						{ refundMutation.isPending
							? __( 'Processing…', 'sureforms' )
							: __( 'Process Refund', 'sureforms' ) }
					</Button>
				</Dialog.Footer>
			</Dialog.Panel>
		</Dialog>
	);
};

export default RefundDialog;
