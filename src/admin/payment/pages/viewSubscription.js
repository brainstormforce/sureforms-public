import { useState, useContext, useEffect } from '@wordpress/element';
import {
	Button,
	Badge,
	Container,
	Label,
	Text,
	Table,
	DropdownMenu,
	Dialog,
	Input,
} from '@bsf/force-ui';
import { ArrowUpRight, EllipsisVertical } from 'lucide-react';
import { __, sprintf } from '@wordpress/i18n';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { PaymentContext } from '../components/context';
import {
	fetchSubscription,
	refundPayment,
	cancelSubscription,
	pauseSubscription,
	addPaymentNote,
	deletePaymentNote,
	deletePaymentLog,
} from '../components/apiCalls';
import {
	getStatusVariant,
	getStatusLabel,
	formatAmount,
	formatDateTimeDetailed,
	formatLogTimestamp,
} from '../components/utils';
import PaymentNotes from '../components/paymentNotes';
import PaymentLogs from '../components/paymentLogs';
import PaymentHeader from '../components/paymentHeader';
import PaymentLoadingSkeleton from '../components/paymentLoadingSkeleton';

const ViewSubscription = () => {
	const {
		viewSinglePayment: viewSingleSubscription,
		setViewSinglePayment: setViewSingleSubscription,
	} = useContext( PaymentContext );
	const queryClient = useQueryClient();

	// Handler to navigate back to payment list
	const handleBackToList = () => {
		setViewSingleSubscription( false );
	};

	// Fetch subscription data (includes subscription info + billing history)
	const {
		data: subscriptionApiData,
		isLoading,
		error,
	} = useQuery( {
		queryKey: [ 'subscription', viewSingleSubscription ],
		queryFn: () => fetchSubscription( viewSingleSubscription ),
		enabled: !! viewSingleSubscription,
		staleTime: 5 * 60 * 1000, // 5 minutes
	} );

	// Extract subscription data and billing data from API response
	const subscriptionData = subscriptionApiData?.subscription;
	const billingData = subscriptionApiData?.payments || [];

	// Refund mutation for individual subscription payments
	const refundMutation = useMutation( {
		mutationFn: refundPayment,
		onSuccess: () => {
			// Refresh subscription data (includes billing history)
			queryClient.invalidateQueries( [
				'subscription',
				viewSingleSubscription,
			] );
			queryClient.invalidateQueries( [ 'payments' ] );
			setIsRefundDialogOpen( false );
		},
		onError: ( refundError ) => {
			console.error( 'Refund failed:', refundError );
			alert( __( 'Refund failed. Please try again.', 'sureforms' ) );
		},
	} );

	// Cancel subscription mutation
	const cancelMutation = useMutation( {
		mutationFn: cancelSubscription,
		onSuccess: () => {
			// Refresh subscription data
			queryClient.invalidateQueries( [
				'subscription',
				viewSingleSubscription,
			] );
			queryClient.invalidateQueries( [ 'payments' ] );
			setIsCancelDialogOpen( false );
		},
		onError: ( cancelError ) => {
			console.error( 'Cancel failed:', cancelError );
			alert(
				__(
					'Failed to cancel subscription. Please try again.',
					'sureforms'
				)
			);
		},
	} );

	// Pause subscription mutation
	const pauseMutation = useMutation( {
		mutationFn: pauseSubscription,
		onSuccess: () => {
			// Refresh subscription data
			queryClient.invalidateQueries( [
				'subscription',
				viewSingleSubscription,
			] );
			queryClient.invalidateQueries( [ 'payments' ] );
			setIsPauseDialogOpen( false );
		},
		onError: ( pauseError ) => {
			console.error( 'Pause failed:', pauseError );
			alert(
				__(
					'Failed to pause subscription. Please try again.',
					'sureforms'
				)
			);
		},
	} );

	// Add note mutation
	const addNoteMutation = useMutation( {
		mutationFn: ( { paymentId, noteText } ) =>
			addPaymentNote( paymentId, noteText ),
		onSuccess: ( updatedNotes ) => {
			setNotes( updatedNotes );
			setNewNoteText( '' );
			setIsAddingNote( false );
			queryClient.invalidateQueries( [
				'subscription',
				viewSingleSubscription,
			] );
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
			queryClient.invalidateQueries( [
				'subscription',
				viewSingleSubscription,
			] );
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
			queryClient.invalidateQueries( [
				'subscription',
				viewSingleSubscription,
			] );
		},
		onError: () => {
			alert(
				__( 'Failed to delete log. Please try again.', 'sureforms' )
			);
		},
	} );

	const [ notes, setNotes ] = useState( [] );
	const [ logs, setLogs ] = useState( [] );
	const [ isRefundDialogOpen, setIsRefundDialogOpen ] = useState( false );
	const [ isCancelDialogOpen, setIsCancelDialogOpen ] = useState( false );
	const [ isPauseDialogOpen, setIsPauseDialogOpen ] = useState( false );
	const [ refundAmount, setRefundAmount ] = useState( '' );
	const [ selectedPaymentForRefund, setSelectedPaymentForRefund ] =
		useState( null );
	const [ isAddingNote, setIsAddingNote ] = useState( false );
	const [ newNoteText, setNewNoteText ] = useState( '' );

	// Sync notes from subscriptionData when it loads
	useEffect( () => {
		if ( subscriptionData?.notes ) {
			setNotes( subscriptionData.notes );
		}
	}, [ subscriptionData?.notes ] );

	// Sync logs from subscriptionData when it loads
	useEffect( () => {
		if ( subscriptionData?.logs ) {
			setLogs( subscriptionData.logs );
		}
	}, [ subscriptionData?.logs ] );

	// Set default refund amount when dialog opens for individual payment
	const openRefundDialog = ( payment ) => {
		if ( payment ) {
			setSelectedPaymentForRefund( payment );
			const totalAmount = parseFloat( payment.total_amount );
			const alreadyRefunded = parseFloat( payment.refunded_amount || 0 );
			const refundableAmount = totalAmount - alreadyRefunded;
			setRefundAmount( refundableAmount.toFixed( 2 ) );
		}
		setIsRefundDialogOpen( true );
	};

	// Open cancel subscription dialog
	const openCancelDialog = () => {
		setIsCancelDialogOpen( true );
	};

	// Loading, error, not found states
	if ( isLoading || error || ! subscriptionData ) {
		return (
			<PaymentLoadingSkeleton
				loading={ isLoading }
				error={ error }
				notFound={ ! subscriptionData }
				setViewSinglePayment={ setViewSingleSubscription }
			/>
		);
	}

	const handleAddNoteClick = () => {
		setIsAddingNote( true );
	};

	const handleSaveNote = () => {
		if ( ! newNoteText.trim() ) {
			return;
		}
		addNoteMutation.mutate( {
			paymentId: subscriptionData.id,
			noteText: newNoteText,
		} );
	};

	const handleCancelNote = () => {
		setNewNoteText( '' );
		setIsAddingNote( false );
	};

	const handleDeleteNote = ( noteIndex ) => {
		deleteNoteMutation.mutate( {
			paymentId: subscriptionData.id,
			noteIndex,
		} );
	};

	const handleDeleteLog = ( logIndex ) => {
		deleteLogMutation.mutate( {
			paymentId: subscriptionData.id,
			logIndex,
		} );
	};

	const handleViewEntry = () => {
		if ( subscriptionData?.entry_id ) {
			window.location.href = `${ window.location.origin }/wp-admin/admin.php?page=sureforms_entries&entry_id=${ subscriptionData.entry_id }&view=details`;
		}
	};

	const handleViewInStripe = () => {
		if ( subscriptionData?.stripe_subscription_id ) {
			const isTestMode = subscriptionData.mode === 'test';
			const baseUrl = isTestMode
				? 'https://dashboard.stripe.com/test/subscriptions'
				: 'https://dashboard.stripe.com/subscriptions';
			const stripeUrl = `${ baseUrl }/${ subscriptionData.stripe_subscription_id }`;
			window.open( stripeUrl, '_blank' );
		}
	};

	const handleRefundLatestEMI = () => {
		// Find the latest (most recent) paid EMI transaction
		const latestPaidEMI = subscriptionBillingData
			.filter(
				( payment ) =>
					payment.status === 'paid' ||
					payment.status === 'succeeded' ||
					payment.status === 'partially_refunded'
			)
			.sort(
				( a, b ) => new Date( b.date_time ) - new Date( a.date_time )
			)[ 0 ];

		if ( ! latestPaidEMI ) {
			alert( __( 'No paid EMI found to refund.', 'sureforms' ) );
			return;
		}

		// Open refund dialog with latest EMI
		openRefundDialog( latestPaidEMI );
	};

	const openPauseDialog = () => {
		setIsPauseDialogOpen( true );
	};

	const closeRefundDialog = () => {
		setIsRefundDialogOpen( false );
	};

	const processRefund = () => {
		if ( ! selectedPaymentForRefund || ! refundAmount ) {
			return;
		}

		const totalAmount = parseFloat( selectedPaymentForRefund.total_amount );
		const alreadyRefunded = parseFloat(
			selectedPaymentForRefund.refunded_amount || 0
		);
		const refundableAmount = totalAmount - alreadyRefunded;
		const requestedAmount = parseFloat( refundAmount );

		// Auto-determine refund type based on amount
		const isFullRefund =
			Math.abs( requestedAmount - refundableAmount ) < 0.01; // Allow for small floating point differences
		const refundType = isFullRefund ? 'full' : 'partial';

		// Convert to cents for backend
		const finalRefundAmount = Math.round( requestedAmount * 100 );

		refundMutation.mutate( {
			paymentId: selectedPaymentForRefund.id,
			transactionId: selectedPaymentForRefund.transaction_id,
			refundAmount: finalRefundAmount,
			refundType,
		} );
	};

	// Generate dynamic refund message based on input amount
	const getRefundMessage = () => {
		if ( ! selectedPaymentForRefund || ! refundAmount ) {
			return '';
		}

		const totalAmount = parseFloat( selectedPaymentForRefund.total_amount );
		const alreadyRefunded = parseFloat(
			selectedPaymentForRefund.refunded_amount || 0
		);
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
					formatAmount(
						refundableAmount,
						selectedPaymentForRefund.currency
					)
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
					formatAmount(
						requestedAmount,
						selectedPaymentForRefund.currency
					)
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
				formatAmount(
					requestedAmount,
					selectedPaymentForRefund.currency
				),
				formatAmount(
					remainingBalance,
					selectedPaymentForRefund.currency
				)
			),
		};
	};

	// Subscription billing history data - use fetched billing data or empty array
	const subscriptionBillingData = billingData || [];

	// Calculate refundable amount for selected payment
	const totalAmount = selectedPaymentForRefund
		? parseFloat( selectedPaymentForRefund.total_amount )
		: 0;
	const alreadyRefunded = selectedPaymentForRefund
		? parseFloat( selectedPaymentForRefund.refunded_amount || 0 )
		: 0;
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
						{ selectedPaymentForRefund &&
							sprintf(
								/* translators: %s: payment ID */
								__(
									"Process refund for payment #%s. The refunded amount will be sent to the customer's original payment method.",
									'sureforms'
								),
								selectedPaymentForRefund.id
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
								{ selectedPaymentForRefund &&
									sprintf(
										/* translators: %s: maximum refundable amount */
										__(
											'Maximum refundable amount: %s',
											'sureforms'
										),
										formatAmount(
											refundableAmount,
											selectedPaymentForRefund.currency
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
									{ selectedPaymentForRefund &&
										sprintf(
											/* translators: %s: already refunded amount */
											__(
												'Already refunded: %s',
												'sureforms'
											),
											formatAmount(
												alreadyRefunded,
												selectedPaymentForRefund.currency
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

	// Cancel subscription dialog
	const cancelDialog = (
		<Dialog
			open={ isCancelDialogOpen }
			setOpen={ setIsCancelDialogOpen }
			design="simple"
			exitOnEsc
			scrollLock
		>
			<Dialog.Backdrop />
			<Dialog.Panel>
				<Dialog.Header>
					<div className="flex items-center justify-between">
						<Dialog.Title>
							{ __( 'Cancel Subscription', 'sureforms' ) }
						</Dialog.Title>
						<Dialog.CloseButton
							onClick={ () => setIsCancelDialogOpen( false ) }
						/>
					</div>
					<Dialog.Description>
						{ sprintf(
							/* translators: %s: subscription ID */
							__(
								'Are you sure you want to cancel subscription #%s? This action cannot be undone and the customer will lose access to their subscription benefits.',
								'sureforms'
							),
							subscriptionData.id
						) }
					</Dialog.Description>
				</Dialog.Header>
				<Dialog.Body>
					<div className="space-y-4">
						<div className="p-3 border border-red-200 rounded-md bg-red-50">
							<Text className="text-sm text-red-700">
								{ __(
									'Warning: Canceling this subscription will immediately stop all future billing and the customer will lose access to subscription benefits.',
									'sureforms'
								) }
							</Text>
						</div>
					</div>
				</Dialog.Body>
				<Dialog.Footer className="flex justify-end gap-2">
					<Button
						variant="outline"
						onClick={ () => setIsCancelDialogOpen( false ) }
						disabled={ cancelMutation.isPending }
					>
						{ __( 'Keep Subscription', 'sureforms' ) }
					</Button>
					<Button
						variant="danger"
						onClick={ () =>
							cancelMutation.mutate( viewSingleSubscription )
						}
						disabled={ cancelMutation.isPending }
					>
						{ cancelMutation.isPending
							? __( 'Canceling…', 'sureforms' )
							: __( 'Cancel Subscription', 'sureforms' ) }
					</Button>
				</Dialog.Footer>
			</Dialog.Panel>
		</Dialog>
	);

	// Pause subscription dialog
	const pauseDialog = (
		<Dialog
			open={ isPauseDialogOpen }
			setOpen={ setIsPauseDialogOpen }
			design="simple"
			exitOnEsc
			scrollLock
		>
			<Dialog.Backdrop />
			<Dialog.Panel>
				<Dialog.Header>
					<div className="flex items-center justify-between">
						<Dialog.Title>
							{ __( 'Pause Subscription', 'sureforms' ) }
						</Dialog.Title>
						<Dialog.CloseButton
							onClick={ () => setIsPauseDialogOpen( false ) }
						/>
					</div>
					<Dialog.Description>
						{ sprintf(
							/* translators: %s: subscription ID */
							__(
								'Are you sure you want to pause subscription #%s? The customer will not be charged until you resume the subscription.',
								'sureforms'
							),
							subscriptionData.id
						) }
					</Dialog.Description>
				</Dialog.Header>
				<Dialog.Body>
					<div className="space-y-4">
						<div className="p-3 border border-yellow-200 rounded-md bg-yellow-50">
							<Text className="text-sm text-yellow-700">
								{ __(
									'Note: Pausing this subscription will stop billing temporarily. You can resume it later from the subscription details.',
									'sureforms'
								) }
							</Text>
						</div>
					</div>
				</Dialog.Body>
				<Dialog.Footer className="flex justify-end gap-2">
					<Button
						variant="outline"
						onClick={ () => setIsPauseDialogOpen( false ) }
						disabled={ pauseMutation.isPending }
					>
						{ __( 'Cancel', 'sureforms' ) }
					</Button>
					<Button
						variant="warning"
						onClick={ () =>
							pauseMutation.mutate( viewSingleSubscription )
						}
						disabled={ pauseMutation.isPending }
					>
						{ pauseMutation.isPending
							? __( 'Pausing…', 'sureforms' )
							: __( 'Pause Subscription', 'sureforms' ) }
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
							{ __( 'Amount Paid', 'sureforms' ) }
						</Table.HeadCell>
						<Table.HeadCell>
							{ __( 'Status', 'sureforms' ) }
						</Table.HeadCell>
						<Table.HeadCell>
							{ __( 'Transaction Date', 'sureforms' ) }
						</Table.HeadCell>
					</Table.Head>
					<Table.Body>
						{ subscriptionBillingData.map( ( row ) => (
							<Table.Row key={ row.id }>
								<Table.Cell className="font-medium">
									{ row.refunded_amount > 0 ? (
										<span
											style={ {
												display: 'flex',
												gap: '8px',
											} }
										>
											<span
												style={ {
													textDecoration:
														'line-through',
													color: '#6c757d',
												} }
											>
												{ formatAmount(
													row.total_amount,
													subscriptionData.currency
												) }
											</span>
											<strong>
												{ formatAmount(
													row.total_amount -
														row.refunded_amount,
													subscriptionData.currency
												) }
											</strong>
										</span>
									) : (
										formatAmount(
											row.total_amount,
											subscriptionData.currency
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
									{ formatDateTimeDetailed( row.date_time ) }
								</Table.Cell>
							</Table.Row>
						) ) }
					</Table.Body>
				</Table>
			</div>
		</div>
	);

	const subscriptionDetailsData = [
		{
			title: __( 'Subscription Id', 'sureforms' ),
			value: `#${ subscriptionData.id }`,
		},
		{
			title: __( 'Form Name', 'sureforms' ),
			value:
				subscriptionData.form_title ||
				__( 'Unknown Form', 'sureforms' ),
		},
		{
			title: __( 'Payment Mode', 'sureforms' ),
			value: subscriptionData.mode || __( 'Unknown', 'sureforms' ),
		},
		{
			title: __( 'Payment Type', 'sureforms' ),
			value:
				subscriptionData.payment_type ||
				__( 'Subscription', 'sureforms' ),
		},
		{
			title: __( 'Billing Cycle', 'sureforms' ),
			value: subscriptionData.interval || __( 'N/A', 'sureforms' ),
		},
		{
			title: __( 'Amount per Cycle', 'sureforms' ),
			value: formatAmount(
				subscriptionData.amount_per_cycle ||
					subscriptionData.total_amount,
				subscriptionData.currency
			),
		},
		{
			title: __( 'Stripe Subscription ID', 'sureforms' ),
			value:
				subscriptionData.stripe_subscription_id ||
				__( 'N/A', 'sureforms' ),
		},
		{
			title: __( 'Customer ID', 'sureforms' ),
			value: subscriptionData.customer_id || __( 'Guest', 'sureforms' ),
		},
		{
			title: __( 'Received On', 'sureforms' ),
			value: formatDateTimeDetailed( subscriptionData.created_at ),
		},
	];

	// Subscription details component
	const subscriptionDetails = subscriptionDetailsData.map(
		( item, index ) => {
			const { title, value } = item;
			return (
				<div
					key={ `payment-info-${ index }` }
					className="flex gap-1 items-center p-3"
				>
					<Text
						as="p"
						color="primary"
						lineHeight={ 20 }
						size={ 14 }
						weight={ 600 }
						className="w-[160px]"
					>
						{ title }:
					</Text>
					<Text
						as="p"
						color="secondary"
						lineHeight={ 20 }
						size={ 14 }
						weight={ 500 }
					>
						{ value }
					</Text>
				</div>
			);
		}
	);

	const PAYMENT_SECTION_COLUMN_1 = (
		<>
			<Container
				className="w-full bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-3 gap-2 shadow-sm"
				direction="column"
			>
				<Container
					className="p-1 gap-2 relative z-10"
					align="center"
					justify="between"
				>
					<Label size="md" className="font-semibold">
						{ __( 'Subscription Details', 'sureforms' ) }
					</Label>
					<DropdownMenu
						placement="bottom-start"
						className="min-w-fit"
					>
						<DropdownMenu.Trigger>
							<Button
								icon={
									<EllipsisVertical className="!size-4" />
								}
								iconPosition="right"
								size="xs"
								variant="outline"
							>
								{ __( 'Actions', 'sureforms' ) }
							</Button>
						</DropdownMenu.Trigger>
						<DropdownMenu.ContentWrapper>
							<DropdownMenu.Content className="w-60">
								<DropdownMenu.List>
									<DropdownMenu.Item
										onClick={ openCancelDialog }
										className="text-sm"
									>
										{ __(
											'Cancel Subscription',
											'sureforms'
										) }
									</DropdownMenu.Item>
									<DropdownMenu.Item
										onClick={ openPauseDialog }
										className="text-sm"
									>
										{ __(
											'Pause Subscription',
											'sureforms'
										) }
									</DropdownMenu.Item>
									<DropdownMenu.Item
										onClick={ handleRefundLatestEMI }
										className="text-sm"
									>
										{ __(
											'Refund The Last Charge',
											'sureforms'
										) }
									</DropdownMenu.Item>
								</DropdownMenu.List>
							</DropdownMenu.Content>
						</DropdownMenu.ContentWrapper>
					</DropdownMenu>
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
						disabled={ ! subscriptionData?.stripe_subscription_id }
					>
						{ __( 'View In Stripe', 'sureforms' ) }
					</Button>
				</Container>
				<Container className="flex flex-col gap-1 p-1 rounded-lg">
					{ subscriptionDetails }
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
				formatLogTimestamp={ formatLogTimestamp }
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
					paymentData={ subscriptionData }
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
			{ cancelDialog }
			{ pauseDialog }
		</div>
	);
};

export default ViewSubscription;
