import { useState, useEffect } from '@wordpress/element';
import { Button, Badge, Text, Table, Dialog } from '@bsf/force-ui';
import { ArrowUpRight, Eye } from 'lucide-react';
import { __, sprintf } from '@wordpress/i18n';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
	fetchSubscription,
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
	formatDateTime,
	PartialAmount,
} from '../components/utils';
import PaymentNotes from '../components/paymentNotes';
import PaymentLogs from '../components/paymentLogs';
import PaymentHeader from '../components/paymentHeader';
import PaymentLoadingSkeleton from '../components/paymentLoadingSkeleton';
import RefundDialog from '../components/RefundDialog';
import ConfirmationDialog from '@Admin/components/ConfirmationDialog';

const ViewSubscription = () => {
	const { id } = useParams();
	const navigate = useNavigate();
	const queryClient = useQueryClient();

	// Parse subscription ID from URL params
	const viewSingleSubscription = id ? parseInt( id ) : null;

	// Handler to navigate back to payment list
	const handleBackToList = () => {
		navigate( '/' );
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
	const [ selectedPaymentForRefund, setSelectedPaymentForRefund ] =
		useState( null );
	const [ isAddingNote, setIsAddingNote ] = useState( false );
	const [ newNoteText, setNewNoteText ] = useState( '' );

	// State for confirmation dialog
	const [ confirmationDialog, setConfirmationDialog ] = useState( {
		open: false,
		title: '',
		description: '',
		confirmLabel: '',
		onConfirm: null,
		isLoading: false,
		destructive: true,
	} );

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

	// Set payment for refund when dialog opens for individual payment
	const openRefundDialog = ( payment ) => {
		if ( payment ) {
			setSelectedPaymentForRefund( payment );
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
				setViewSinglePayment={ () => navigate( '/' ) }
			/>
		);
	}

	/**
	 * Handler function for triggering confirmation dialogs from child components
	 *
	 * @param {Object}   config              - Configuration object
	 * @param {string}   config.title        - Dialog title
	 * @param {string}   config.description  - Dialog description
	 * @param {string}   config.confirmLabel - Confirm button label
	 * @param {Function} config.onConfirm    - Function to call on confirm
	 * @param {boolean}  config.isLoading    - Whether action is loading
	 * @param {boolean}  config.destructive  - Whether action is destructive
	 */
	const handleConfirmation = ( config ) => {
		setConfirmationDialog( {
			open: true,
			title: config.title,
			description: config.description,
			confirmLabel: config.confirmLabel,
			onConfirm: async () => {
				try {
					await config.onConfirm();
					// Close the dialog after successful action
					setConfirmationDialog( ( prev ) => ( {
						...prev,
						open: false,
					} ) );
				} catch ( confirmationError ) {
					// Keep dialog open if there's an error
					console.error(
						'Confirmation action failed:',
						confirmationError
					);
				}
			},
			isLoading: config.isLoading || false,
			destructive: config.destructive !== false, // Default to true
		} );
	};

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
		return new Promise( ( resolve, reject ) => {
			deleteNoteMutation.mutate(
				{
					paymentId: subscriptionData.id,
					noteIndex,
				},
				{
					onSuccess: () => {
						resolve();
					},
					onError: ( deletingNoteError ) => {
						reject( deletingNoteError );
					},
				}
			);
		} );
	};

	const handleDeleteLog = ( logIndex ) => {
		return new Promise( ( resolve, reject ) => {
			deleteLogMutation.mutate(
				{
					paymentId: subscriptionData.id,
					logIndex,
				},
				{
					onSuccess: () => {
						resolve();
					},
					onError: ( deletingError ) => {
						reject( deletingError );
					},
				}
			);
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

	const handleViewRenewalPayment = ( paymentId ) => {
		if ( paymentId ) {
			navigate( `/payment/${ paymentId }` );
		}
	};

	const handleRefundLatestEMI = () => {
		// Find the first (initial/linked) paid EMI transaction
		// Note: 'active' status is used for subscription records, 'succeeded' for one-time payments
		const firstPaidEMI = subscriptionBillingData
			.filter(
				( payment ) =>
					payment.status === 'active' ||
					payment.status === 'paid' ||
					payment.status === 'succeeded' ||
					payment.status === 'partially_refunded'
			)
			.sort(
				( a, b ) => new Date( a.created_at ) - new Date( b.created_at )
			)[ 0 ];

		if ( ! firstPaidEMI ) {
			alert( __( 'No paid EMI found to refund.', 'sureforms' ) );
			return;
		}

		// Check if the first EMI is fully refunded
		const isFullyRefunded =
			parseFloat( firstPaidEMI.total_amount ) ===
				parseFloat( firstPaidEMI.refunded_amount || 0 ) ||
			firstPaidEMI.status === 'refunded';

		if ( isFullyRefunded ) {
			alert(
				__(
					'This payment has already been fully refunded.',
					'sureforms'
				)
			);
			return;
		}

		// Open refund dialog with first EMI (linked transaction)
		openRefundDialog( firstPaidEMI );
	};

	const openPauseDialog = () => {
		setIsPauseDialogOpen( true );
	};

	// Subscription billing history data - use fetched billing data or empty array
	const subscriptionBillingData = billingData || [];

	// Check if the first paid EMI is fully refunded (for disabling refund button)
	const firstPaidEMI = subscriptionBillingData
		.filter(
			( payment ) =>
				payment.status === 'active' ||
				payment.status === 'paid' ||
				payment.status === 'succeeded' ||
				payment.status === 'partially_refunded'
		)
		.sort(
			( a, b ) => new Date( a.created_at ) - new Date( b.created_at )
		)[ 0 ];

	const isFirstEMIFullyRefunded = firstPaidEMI
		? parseFloat( firstPaidEMI.total_amount ) ===
				parseFloat( firstPaidEMI.refunded_amount || 0 ) ||
		  firstPaidEMI.status === 'refunded'
		: false;

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
		<div className="overflow-x-auto bg-background-primary rounded-md shadow-sm">
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
					<Table.HeadCell className="w-20 text-right">
						{ __( 'Action', 'sureforms' ) }
					</Table.HeadCell>
				</Table.Head>
				<Table.Body>
					{ subscriptionBillingData.map( ( row ) => (
						<Table.Row key={ row.id }>
							<Table.Cell className="font-medium">
								{ row.refunded_amount > 0 ? (
									<PartialAmount
										amount={ row.total_amount }
										partialAmount={
											row.total_amount -
											row.refunded_amount
										}
										currency={ subscriptionData.currency }
									/>
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
										'active' === row.status
											? 'succeeded'
											: row.status
									) }
									size="xs"
									label={ getStatusLabel(
										'active' === row.status
											? 'succeeded'
											: row.status
									) }
									type="pill"
									className="w-fit"
								/>
							</Table.Cell>
							<Table.Cell>
								{ formatDateTime( row.created_at, true ) }
							</Table.Cell>
							<Table.Cell className="text-right flex justify-center">
								<Button
									icon={ <Eye className="!size-4" /> }
									size="xs"
									variant="ghost"
									onClick={ () =>
										handleViewRenewalPayment( row.id )
									}
								/>
							</Table.Cell>
						</Table.Row>
					) ) }
				</Table.Body>
			</Table>
		</div>
	);

	const subscriptionDetailsData = [
		{
			title: __( 'Subscription ID', 'sureforms' ),
			value: `#${ subscriptionData.id }`,
		},
		{
			title: __( 'Form Name', 'sureforms' ),
			value: subscriptionData.form_url ? (
				<Button
					icon={ <ArrowUpRight className="!size-4" /> }
					iconPosition="right"
					variant="link"
					size="sm"
					className="h-full text-link-primary text-sm font-semibold no-underline hover:no-underline hover:text-link-primary-hover px-1 content-center [box-shadow:none] focus:[box-shadow:none] focus:outline-none"
					onClick={ () =>
						window.open(
							subscriptionData.form_url,
							'_blank',
							'noopener,noreferrer'
						)
					}
				>
					{ subscriptionData.form_title ||
						__( 'Unknown Form', 'sureforms' ) }
				</Button>
			) : (
				subscriptionData.form_title || __( 'Unknown Form', 'sureforms' )
			),
		},
		{
			id: 'payment-mode',
			title: __( 'Payment Mode', 'sureforms' ),
			value:
				'live' === subscriptionData.mode ? (
					<Badge
						className="w-fit"
						variant="green"
						size="xs"
						label={ __( 'Live Mode', 'sureforms' ) }
					/>
				) : (
					<Badge
						className="w-fit"
						variant="yellow"
						size="xs"
						label={ __( 'Test Mode', 'sureforms' ) }
					/>
				),
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
			id: 'customer-name',
			title: __( 'Customer Name', 'sureforms' ),
			value: subscriptionData.customer_name || __( 'Guest', 'sureforms' ),
		},
		{
			id: 'customer-email',
			title: __( 'Customer Email', 'sureforms' ),
			value: subscriptionData.customer_email || __( 'N/A', 'sureforms' ),
		},
		{
			title: __( 'Customer ID', 'sureforms' ),
			value: subscriptionData.customer_id || __( 'Guest', 'sureforms' ),
		},
		{
			title: __( 'Received On', 'sureforms' ),
			value: formatDateTime( subscriptionData.created_at, true ),
		},
	];

	// Subscription details component - using Table structure
	const subscriptionDetails = (
		<div className="overflow-x-auto bg-background-primary rounded-md shadow-sm">
			<Table className="w-full">
				<Table.Body>
					{ subscriptionDetailsData.map( ( field, index ) => (
						<Table.Row key={ index }>
							<Table.Cell className="w-1/3 font-medium text-text-primary">
								{ field.title }
							</Table.Cell>
							<Table.Cell className="text-text-secondary">
								{ typeof field?.render === 'function'
									? field.render( field.value )
									: field.value }
							</Table.Cell>
						</Table.Row>
					) ) }
				</Table.Body>
			</Table>
		</div>
	);

	const PAYMENT_SECTION_COLUMN_1 = (
		<>
			<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
				<div className="pb-0 px-4 pt-4">
					<div className="flex items-center justify-between">
						<div className="flex items-center gap-2">
							<h3 className="text-base font-semibold text-text-primary">
								{ __( 'Subscription Details', 'sureforms' ) }
							</h3>
							<Badge
								label={ getStatusLabel(
									subscriptionData.subscription_status
								) }
								variant={ getStatusVariant(
									subscriptionData.subscription_status
								) }
								size="xs"
								className="max-w-fit"
								disableHover
							/>
						</div>
						<div className="flex gap-2">
							<Button
								onClick={ openCancelDialog }
								size="xs"
								variant="outline"
								disabled={
									subscriptionData.subscription_status ===
										'cancelled' ||
									subscriptionData.subscription_status ===
										'canceled'
								}
							>
								{ __( 'Cancel', 'sureforms' ) }
							</Button>
							<Button
								onClick={ openPauseDialog }
								size="xs"
								variant="outline"
								disabled={
									subscriptionData.subscription_status ===
										'paused' ||
									subscriptionData.subscription_status ===
										'cancelled' ||
									subscriptionData.subscription_status ===
										'canceled'
								}
							>
								{ __( 'Pause', 'sureforms' ) }
							</Button>
							<Button
								onClick={ handleRefundLatestEMI }
								size="xs"
								variant="outline"
								disabled={
									isFirstEMIFullyRefunded || ! firstPaidEMI
								}
							>
								{ __( 'Refund', 'sureforms' ) }
							</Button>
						</div>
					</div>
				</div>
				<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
					{ billingDetails }
				</div>
			</div>
			{ /* Payment Info */ }
			<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
				<div className="pb-0 px-4 pt-4">
					<div className="flex items-center justify-between">
						<h3 className="text-sm font-semibold text-text-primary">
							{ __( 'Payment Information', 'sureforms' ) }
						</h3>
						<Button
							icon={ <ArrowUpRight className="!size-4" /> }
							iconPosition="right"
							variant="link"
							size="xs"
							className="text-link-primary hover:text-link-primary-hover"
							onClick={ handleViewInStripe }
							disabled={
								! subscriptionData?.stripe_subscription_id
							}
						>
							{ __( 'View In Stripe', 'sureforms' ) }
						</Button>
					</div>
				</div>
				<div className="p-4">{ subscriptionDetails }</div>
			</div>
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
				onConfirmation={ handleConfirmation }
			/>
			<PaymentLogs
				logs={ logs }
				handleDeleteLog={ handleDeleteLog }
				deleteLogMutation={ deleteLogMutation }
				onConfirmation={ handleConfirmation }
			/>
		</>
	);

	return (
		<>
			{ /* Header */ }
			<PaymentHeader
				title={ __( 'Order ID', 'sureforms' ) }
				paymentData={ subscriptionData }
				handleViewEntry={ handleViewEntry }
				onBack={ handleBackToList }
			/>
			<div className="mx-auto">
				<div className="space-y-6">
					<div className="space-y-6">
						{ /* Main Content Grid */ }
						<div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
							{ /* Left Column */ }
							<div className="lg:col-span-2 space-y-6">
								{ PAYMENT_SECTION_COLUMN_1 }
							</div>

							{ /* Right Column */ }
							<div className="space-y-4">
								{ PAYMENT_SECTION_COLUMN_2 }
							</div>
						</div>
					</div>
				</div>
			</div>
			<RefundDialog
				isOpen={ isRefundDialogOpen }
				setIsOpen={ setIsRefundDialogOpen }
				payment={ selectedPaymentForRefund }
				queryKey={ [ 'subscription', viewSingleSubscription ] }
			/>
			{ cancelDialog }
			{ pauseDialog }

			<ConfirmationDialog
				isOpen={ confirmationDialog.open }
				onCancel={ () =>
					setConfirmationDialog( ( prev ) => ( {
						...prev,
						open: false,
					} ) )
				}
				onConfirm={ confirmationDialog.onConfirm }
				title={ confirmationDialog.title }
				description={ confirmationDialog.description }
				confirmButtonText={ confirmationDialog.confirmLabel }
				destructiveConfirmButton={ confirmationDialog.destructive }
			/>
		</>
	);
};

export default ViewSubscription;
