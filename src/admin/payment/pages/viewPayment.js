import { useState, useContext, useEffect } from '@wordpress/element';
import {
	Button,
	Badge,
	Container,
	Label,
	Text,
	Table,
	Dialog,
	Input,
} from '@bsf/force-ui';
import { ArrowUpRight, RotateCcw } from 'lucide-react';
import { __, sprintf } from '@wordpress/i18n';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { PaymentContext } from '../components/context';
import {
	fetchSinglePayment,
	refundPayment,
	addPaymentNote,
	deletePaymentNote,
	deletePaymentLog,
} from '../components/apiCalls';
import {
	getStatusVariant,
	formatAmount,
	formatDateTime,
	getStatusLabel,
	PartialAmount,
} from '../components/utils';
import PaymentNotes from '../components/paymentNotes';
import PaymentLogs from '../components/paymentLogs';
import PaymentHeader from '../components/paymentHeader';
import PaymentLoadingSkeleton from '../components/paymentLoadingSkeleton';

const ViewPayment = () => {
	const { viewSinglePayment, setViewSinglePayment } =
		useContext( PaymentContext );
	const queryClient = useQueryClient();

	// Handler to navigate back to payment list
	const handleBackToList = () => {
		setViewSinglePayment( false );
	};

	// Fetch single payment data
	const {
		data: paymentData,
		isLoading,
		error,
	} = useQuery( {
		queryKey: [ 'payment', viewSinglePayment ],
		queryFn: () => fetchSinglePayment( viewSinglePayment ),
		enabled: !! viewSinglePayment,
		staleTime: 5 * 60 * 1000, // 5 minutes
	} );

	// Refund mutation
	const refundMutation = useMutation( {
		mutationFn: refundPayment,
		onSuccess: () => {
			// Refresh payment data and payments list
			queryClient.invalidateQueries( [ 'payment', viewSinglePayment ] );
			queryClient.invalidateQueries( [ 'payments' ] );
			setIsRefundDialogOpen( false );
		},
		onError: ( refundError ) => {
			// Use a different parameter name to avoid shadowing the outer 'error'
			console.error( 'Refund failed:', refundError );
			alert( __( 'Refund failed. Please try again.', 'sureforms' ) );
		},
	} );

	const [ notes, setNotes ] = useState( [] );
	const [ logs, setLogs ] = useState( [] );
	const [ isRefundDialogOpen, setIsRefundDialogOpen ] = useState( false );
	const [ refundAmount, setRefundAmount ] = useState( '' );
	const [ isAddingNote, setIsAddingNote ] = useState( false );
	const [ newNoteText, setNewNoteText ] = useState( '' );

	// Sync notes from paymentData when it loads
	useEffect( () => {
		if ( paymentData?.notes ) {
			setNotes( paymentData.notes );
		}
	}, [ paymentData?.notes ] );

	// Sync logs from paymentData when it loads
	useEffect( () => {
		if ( paymentData?.logs ) {
			setLogs( paymentData.logs );
		}
	}, [ paymentData?.logs ] );

	// Add note mutation
	const addNoteMutation = useMutation( {
		mutationFn: ( { paymentId, noteText } ) =>
			addPaymentNote( paymentId, noteText ),
		onSuccess: ( updatedNotes ) => {
			setNotes( updatedNotes );
			setNewNoteText( '' );
			setIsAddingNote( false );
			queryClient.invalidateQueries( [ 'payment', viewSinglePayment ] );
		},
		onError: () => {
			alert( __( 'Failed to add note. Please try again.', 'sureforms' ) );
		},
	} );

	// Delete note mutation
	const deleteNoteMutation = useMutation( {
		mutationFn: ( { paymentId, noteIndex } ) =>
			deletePaymentNote( paymentId, noteIndex ),
		onSuccess: ( updatedNotes ) => {
			setNotes( updatedNotes );
			queryClient.invalidateQueries( [ 'payment', viewSinglePayment ] );
		},
		onError: () => {
			alert(
				__( 'Failed to delete note. Please try again.', 'sureforms' )
			);
		},
	} );

	// Delete log mutation
	const deleteLogMutation = useMutation( {
		mutationFn: ( { paymentId, logIndex } ) =>
			deletePaymentLog( paymentId, logIndex ),
		onSuccess: ( updatedLogs ) => {
			setLogs( updatedLogs );
			queryClient.invalidateQueries( [ 'payment', viewSinglePayment ] );
		},
		onError: () => {
			alert(
				__( 'Failed to delete log. Please try again.', 'sureforms' )
			);
		},
	} );

	// Set default refund amount when dialog opens
	const openRefundDialog = () => {
		if ( paymentData ) {
			const totalAmount = parseFloat( paymentData.total_amount );
			const alreadyRefunded = parseFloat(
				paymentData.refunded_amount || 0
			);
			const refundableAmount = totalAmount - alreadyRefunded;
			setRefundAmount( refundableAmount.toFixed( 2 ) );
		}
		setIsRefundDialogOpen( true );
	};

	// Loading, error, not found states
	if ( isLoading || error || ! paymentData ) {
		return (
			<PaymentLoadingSkeleton
				loading={ isLoading }
				error={ error }
				notFound={ ! paymentData }
				setViewSinglePayment={ setViewSinglePayment }
			/>
		);
	}

	const handleRefund = () => {
		openRefundDialog();
	};

	const handleAddNoteClick = () => {
		setIsAddingNote( true );
	};

	const handleSaveNote = () => {
		if ( ! newNoteText.trim() ) {
			return;
		}
		addNoteMutation.mutate( {
			paymentId: paymentData.id,
			noteText: newNoteText,
		} );
	};

	const handleCancelNote = () => {
		setNewNoteText( '' );
		setIsAddingNote( false );
	};

	const handleDeleteNote = ( noteIndex ) => {
		deleteNoteMutation.mutate( {
			paymentId: paymentData.id,
			noteIndex,
		} );
	};

	const handleDeleteLog = ( logIndex ) => {
		deleteLogMutation.mutate( {
			paymentId: paymentData.id,
			logIndex,
		} );
	};

	const handleViewEntry = () => {
		if ( paymentData?.entry_id ) {
			window.location.href = `${ window.location.origin }/wp-admin/admin.php?page=sureforms_entries&entry_id=${ paymentData.entry_id }&view=details`;
		}
	};

	const handleViewInStripe = () => {
		if ( paymentData?.transaction_id ) {
			const isTestMode = paymentData.mode === 'test';
			const baseUrl = isTestMode
				? 'https://dashboard.stripe.com/test/payments'
				: 'https://dashboard.stripe.com/payments';
			const stripeUrl = `${ baseUrl }/${ paymentData.transaction_id }`;
			window.open( stripeUrl, '_blank' );
		}
	};

	const closeRefundDialog = () => {
		setIsRefundDialogOpen( false );
	};

	const processRefund = () => {
		if ( ! paymentData || ! refundAmount ) {
			return;
		}

		const totalAmount = parseFloat( paymentData.total_amount );
		const alreadyRefunded = parseFloat( paymentData.refunded_amount || 0 );
		const refundableAmount = totalAmount - alreadyRefunded;
		const requestedAmount = parseFloat( refundAmount );

		// Auto-determine refund type based on amount
		const isFullRefund =
			Math.abs( requestedAmount - refundableAmount ) < 0.01; // Allow for small floating point differences
		const refundType = isFullRefund ? 'full' : 'partial';

		// Convert to cents for backend
		const finalRefundAmount = Math.round( requestedAmount * 100 );

		refundMutation.mutate( {
			paymentId: paymentData.id,
			transactionId: paymentData.transaction_id,
			refundAmount: finalRefundAmount,
			refundType,
		} );
	};

	// Generate dynamic refund message based on input amount
	const getRefundMessage = () => {
		if ( ! paymentData || ! refundAmount ) {
			return '';
		}

		const totalAmount = parseFloat( paymentData.total_amount );
		const alreadyRefunded = parseFloat( paymentData.refunded_amount || 0 );
		const refundableAmount = totalAmount - alreadyRefunded;
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
					formatAmount( refundableAmount, paymentData.currency )
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
					formatAmount( requestedAmount, paymentData.currency )
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
				formatAmount( requestedAmount, paymentData.currency ),
				formatAmount( remainingBalance, paymentData.currency )
			),
		};
	};

	// Billing data from real payment data
	const billingData = [
		{
			id: paymentData.id,
			amount_paid: parseFloat( paymentData.total_amount ),
			status: paymentData.status,
			date_time: paymentData.created_at,
			refunded_amount: parseFloat( paymentData.refunded_amount || 0 ),
		},
	];

	// Calculate refundable amount
	const totalAmount = parseFloat( paymentData.total_amount );
	const alreadyRefunded = parseFloat( paymentData.refunded_amount || 0 );
	const refundableAmount = totalAmount - alreadyRefunded;

	// Get dynamic message for current refund amount
	const refundMessage = getRefundMessage();
	const isValidRefund = refundMessage.type !== 'error';

	const refundDialog = (
		<Dialog
			open={ isRefundDialogOpen }
			setOpen={ setIsRefundDialogOpen }
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
						<Dialog.CloseButton onClick={ closeRefundDialog } />
					</div>
					<Dialog.Description>
						{ sprintf(
							/* translators: %s: payment ID */
							__(
								"Process refund for payment #%s. The refunded amount will be sent to the customer's original payment method.",
								'sureforms'
							),
							paymentData.id
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
								placeholder={ `Max: ${ refundableAmount.toFixed(
									2
								) }` }
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
										paymentData.currency
									)
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
											paymentData.currency
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
						onClick={ closeRefundDialog }
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

	const billingDetails = (
		<div className="overflow-hidden bg-background-primary">
			<div className="overflow-x-auto">
				<Table className="w-full">
					<Table.Head>
						<Table.HeadCell>
							{ __( 'Amount', 'sureforms' ) }
						</Table.HeadCell>
						<Table.HeadCell>
							{ __( 'Status', 'sureforms' ) }
						</Table.HeadCell>
						<Table.HeadCell>
							{ __( 'Transaction Date', 'sureforms' ) }
						</Table.HeadCell>
						<Table.HeadCell className="w-1/6 text-right">
							{ __( 'Action', 'sureforms' ) }
						</Table.HeadCell>
					</Table.Head>
					<Table.Body>
						{ billingData.map( ( row ) => (
							<Table.Row key={ row.id }>
								<Table.Cell>
									{ row.refunded_amount > 0 ? (
										<PartialAmount
											amount={ row.amount_paid }
											partialAmount={
												row.amount_paid -
												row.refunded_amount
											}
											currency={ paymentData.currency }
										/>
									) : (
										formatAmount(
											row.amount_paid,
											paymentData.currency
										)
									) }
								</Table.Cell>
								<Table.Cell>
									<Badge
										variant={ getStatusVariant(
											row.status
										) }
										size="xs"
										label={ getStatusLabel( row.status ) }
										type="pill"
										className="w-fit"
									/>
								</Table.Cell>
								<Table.Cell>
									{ formatDateTime( row.date_time ) }
								</Table.Cell>
								<Table.Cell className="flex justify-end">
									<Button
										icon={
											<RotateCcw className="!size-3" />
										}
										size="xs"
										variant="outline"
										onClick={ () => handleRefund( row.id ) }
									>
										{ __( 'Refund', 'sureforms' ) }
									</Button>
								</Table.Cell>
							</Table.Row>
						) ) }
					</Table.Body>
				</Table>
			</div>
		</div>
	);

	const paymentInfoData = [
		{
			id: 'payment-id',
			title: __( 'Payment ID', 'sureforms' ),
			value: `#${ paymentData.id }`,
		},
		{
			id: 'form-name',
			title: __( 'Form Name', 'sureforms' ),
			value:
				(
					<span className="text-link-primary">
						{ paymentData.form_title }
					</span>
				) || __( 'Unknown Form', 'sureforms' ),
		},
		{
			id: 'payment-mode',
			title: __( 'Payment Mode', 'sureforms' ),
			value:
				'live' === paymentData.mode ? (
					<Badge
						variant="green"
						label={ __( 'Live Mode', 'sureforms' ) }
					/>
				) : (
					<Badge
						variant="yellow"
						label={ __( 'Test Mode', 'sureforms' ) }
					/>
				),
		},
		{
			id: 'payment-type',
			title: __( 'Payment Type', 'sureforms' ),
			value: paymentData.payment_type || __( 'One Time', 'sureforms' ),
		},
		{
			id: 'transaction-id',
			title: __( 'Transaction ID', 'sureforms' ),
			value: paymentData.transaction_id || __( 'N/A', 'sureforms' ),
		},
		{
			id: 'customer-name',
			title: __( 'Customer Name', 'sureforms' ),
			value: paymentData.customer_name || __( 'Guest', 'sureforms' ),
		},
		{
			id: 'customer-email',
			title: __( 'Customer Email', 'sureforms' ),
			value: paymentData.customer_email || __( 'N/A', 'sureforms' ),
		},
		{
			id: 'received-on',
			title: __( 'Received On', 'sureforms' ),
			value: formatDateTime( paymentData.created_at ),
		},
	];

	// Payment info component
	const paymentInfo = paymentInfoData.map( ( item, index ) => {
		const { title, value } = item; // Get the first key-value pair
		return (
			<div
				key={ `payment-info-${ index }` }
				className="flex gap-1 items-center p-3"
			>
				<Text
					as="p"
					color="secondary"
					lineHeight={ 20 }
					size={ 14 }
					weight={ 400 }
					className="w-[131px]"
				>
					{ title }:
				</Text>
				<Text
					as="p"
					color="secondary"
					lineHeight={ 20 }
					size={ 14 }
					weight={ 400 }
				>
					{ value }
				</Text>
			</div>
		);
	} );

	const PAYMENT_SECTION_COLUMN_1 = (
		<>
			<Container
				className="w-full bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-3 gap-2 shadow-sm"
				direction="column"
			>
				<Container
					className="p-1 gap-2"
					align="center"
					justify="between"
				>
					<Label size="md" className="font-semibold">
						{ __( 'Billing Details', 'sureforms' ) }
					</Label>
				</Container>
				<Container className="flex flex-col bg-background-secondary gap-1 p-1 rounded-lg">
					{ billingDetails }
				</Container>
			</Container>
			{ /* Payment Info */ }
			<Container
				className="w-full bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-3 gap-2 shadow-sm"
				direction="column"
			>
				<Container
					className="p-1 gap-2"
					align="center"
					justify="between"
				>
					<Label size="sm" className="font-semibold">
						{ __( 'Payment Information', 'sureforms' ) }
					</Label>
					<Button
						icon={ <ArrowUpRight className="!size-4" /> }
						iconPosition="right"
						variant="link"
						size="sm"
						className="h-full text-link-primary text-sm font-semibold no-underline hover:no-underline hover:text-link-primary-hover px-1 content-center [box-shadow:none] focus:[box-shadow:none] focus:outline-none"
						onClick={ handleViewInStripe }
						disabled={ ! paymentData?.transaction_id }
					>
						{ __( 'View In Stripe', 'sureforms' ) }
					</Button>
				</Container>
				<Container className="flex flex-col gap-1 p-1 rounded-lg">
					{ paymentInfo }
				</Container>
			</Container>
		</>
	);

	const PAYMENT_SECTION_COLUMN_2 = (
		<>
			<PaymentNotes
				notes={ notes }
				isAddingNote={ isAddingNote }
				newNoteText={ newNoteText }
				setNewNoteText={ setNewNoteText }
				handleAddNoteClick={ handleAddNoteClick }
				handleSaveNote={ handleSaveNote }
				handleCancelNote={ handleCancelNote }
				handleDeleteNote={ handleDeleteNote }
				addNoteMutation={ addNoteMutation }
				deleteNoteMutation={ deleteNoteMutation }
			/>
			<PaymentLogs
				logs={ logs }
				handleDeleteLog={ handleDeleteLog }
				deleteLogMutation={ deleteLogMutation }
			/>
		</>
	);

	return (
		<div className="srfm-single-payment-wrapper min-h-screen bg-background-secondary p-8">
			<Container
				containerType="flex"
				direction="column"
				className="w-full h-full gap-[24px]"
			>
				<PaymentHeader
					title={ __( 'Order ID', 'sureforms' ) }
					paymentData={ paymentData }
					handleViewEntry={ handleViewEntry }
					onBack={ handleBackToList }
				/>
				<Container
					className="w-full gap-6"
					containerType="grid"
					cols={ 12 }
				>
					<div className="flex flex-col gap-6 col-span-12 xl:col-span-8">
						{ PAYMENT_SECTION_COLUMN_1 }
					</div>
					<div className="flex flex-col gap-4 col-span-12 xl:col-span-4">
						{ PAYMENT_SECTION_COLUMN_2 }
					</div>
				</Container>
			</Container>
			{ refundDialog }
		</div>
	);
};

export default ViewPayment;
