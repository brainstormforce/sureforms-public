import { useState, useEffect } from '@wordpress/element';
import { Button, Badge, Table } from '@bsf/force-ui';
import { ArrowUpRight, RotateCcw } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
	fetchSinglePayment,
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
	formatOrderId,
} from '../components/utils';
import PaymentNotes from '../components/paymentNotes';
import PaymentLogs from '../components/paymentLogs';
import PaymentHeader from '../components/paymentHeader';
import PaymentLoadingSkeleton from '../components/paymentLoadingSkeleton';
import RefundDialog from '../components/RefundDialog';
import ConfirmationDialog from '@Admin/components/ConfirmationDialog';

const ViewPayment = () => {
	const { id } = useParams();
	const navigate = useNavigate();
	const queryClient = useQueryClient();

	// Parse payment ID from URL params
	const viewSinglePayment = id ? parseInt( id ) : null;

	// Handler to navigate back to payment list
	const handleBackToList = () => {
		navigate( '/' );
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

	const [ notes, setNotes ] = useState( [] );
	const [ logs, setLogs ] = useState( [] );
	const [ isRefundDialogOpen, setIsRefundDialogOpen ] = useState( false );
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

	// Loading, error, not found states
	if ( isLoading || error || ! paymentData ) {
		return (
			<PaymentLoadingSkeleton
				loading={ isLoading }
				error={ error }
				notFound={ ! paymentData }
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
			onConfirm: config.onConfirm,
			isLoading: config.isLoading || false,
			destructive: config.destructive !== false, // Default to true
		} );
	};

	const handleRefund = () => {
		setIsRefundDialogOpen( true );
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

	const handleViewSubscription = ( parentSubscriptionId ) => {
		// Navigate to parent subscription using the parent_subscription_id field
		// This field contains the database ID of the parent subscription record
		if ( ! parentSubscriptionId ) {
			console.error(
				'handleViewSubscription: No parent subscription ID provided',
				{
					paymentData: {
						id: paymentData?.id,
						parent_subscription_id:
							paymentData?.parent_subscription_id,
						subscription_id: paymentData?.subscription_id,
					},
				}
			);
			return;
		}

		// Convert string to number if it's numeric (for safety)
		const subscriptionId =
			typeof parentSubscriptionId === 'string' &&
			/^\d+$/.test( parentSubscriptionId )
				? parseInt( parentSubscriptionId, 10 )
				: parentSubscriptionId;

		// Validate it's a valid number
		if (
			typeof subscriptionId === 'number' &&
			! isNaN( subscriptionId ) &&
			subscriptionId > 0
		) {
			console.log(
				'Navigating to parent subscription with ID:',
				subscriptionId
			);
			navigate( `/payment/${ subscriptionId }?type=subscription` );
			return;
		}

		// If we got here, the ID is invalid
		console.error(
			'handleViewSubscription: Invalid parent subscription ID',
			{
				received: parentSubscriptionId,
				converted: subscriptionId,
				type: typeof parentSubscriptionId,
			}
		);
		alert(
			__(
				'Cannot navigate to parent subscription. Invalid subscription ID.',
				'sureforms'
			)
		);
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
					<Table.HeadCell className="w-1/6 text-right">
						{ __( 'Action', 'sureforms' ) }
					</Table.HeadCell>
				</Table.Head>
				<Table.Body>
					{ billingData.map( ( row ) => {
						// Check if payment is fully refunded
						const isFullyRefunded =
							parseFloat( row.amount_paid ) ===
								parseFloat( row.refunded_amount ) ||
							row.status === 'refunded';

						return (
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
										disabled={ isFullyRefunded }
									>
										{ __( 'Refund', 'sureforms' ) }
									</Button>
								</Table.Cell>
							</Table.Row>
						);
					} ) }
				</Table.Body>
			</Table>
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
			value: paymentData.form_url ? (
				<Button
					icon={ <ArrowUpRight className="!size-4" /> }
					iconPosition="right"
					variant="link"
					size="sm"
					className="h-full text-link-primary text-sm font-semibold no-underline hover:no-underline hover:text-link-primary-hover px-1 content-center [box-shadow:none] focus:[box-shadow:none] focus:outline-none"
					onClick={ () =>
						window.open(
							paymentData.form_url,
							'_blank',
							'noopener,noreferrer'
						)
					}
				>
					{ paymentData.form_title ||
						__( 'Unknown Form', 'sureforms' ) }
				</Button>
			) : (
				paymentData.form_title || __( 'Unknown Form', 'sureforms' )
			),
		},
		{
			id: 'payment-mode',
			title: __( 'Payment Mode', 'sureforms' ),
			value:
				'live' === paymentData.mode ? (
					<Badge
						className="w-fit"
						variant="green"
						size="md"
						label={ __( 'Live Mode', 'sureforms' ) }
					/>
				) : (
					<Badge
						className="w-fit"
						size="md"
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
		...( paymentData.parent_subscription_id &&
		parseInt( paymentData.parent_subscription_id ) > 0
			? [
				{
					id: 'parent-subscription',
					title: __( 'Parent Subscription', 'sureforms' ),
					value: (
						<Button
							icon={ <ArrowUpRight className="!size-4" /> }
							iconPosition="right"
							variant="link"
							size="sm"
							className="text-link-primary hover:text-link-primary-hover text-sm font-semibold no-underline hover:no-underline px-0 [box-shadow:none] focus:[box-shadow:none] focus:outline-none"
							onClick={ () =>
								handleViewSubscription(
									paymentData.parent_subscription_id
								)
							}
						>
							{ formatOrderId( paymentData ) }
						</Button>
					),
				},
			  ]
			: [] ),
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

	// Payment info component - using Table structure
	const paymentInfo = (
		<div className="overflow-x-auto bg-background-primary rounded-md shadow-sm">
			<Table className="w-full">
				<Table.Body>
					{ paymentInfoData.map( ( field, index ) => (
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
					<h3 className="text-base font-semibold text-text-primary">
						{ __( 'Billing Details', 'sureforms' ) }
					</h3>
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
							disabled={ ! paymentData?.transaction_id }
						>
							{ __( 'View In Stripe', 'sureforms' ) }
						</Button>
					</div>
				</div>
				<div className="p-4">{ paymentInfo }</div>
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
				paymentData={ paymentData }
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
				payment={ paymentData }
				queryKey={ [ 'payment', viewSinglePayment ] }
			/>

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

export default ViewPayment;
