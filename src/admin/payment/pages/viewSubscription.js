import { useState, useContext, useEffect } from '@wordpress/element';
import {
	Card,
	Button,
	Badge,
	Avatar,
	Container,
	Label,
	Text,
	Table,
	DropdownMenu,
	Dialog,
	Input,
	TextArea,
} from '@bsf/force-ui';
import {
	ChevronLeft,
	ChevronRight,
	ArrowUpRight,
	ExternalLink,
	Plus,
	Trash2,
	User,
	EllipsisVertical,
	FileSearch2,
} from 'lucide-react';
import { __ } from '@wordpress/i18n';
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

const ViewSubscription = () => {
	const {
		viewSinglePayment: viewSingleSubscription,
		setViewSinglePayment: setViewSingleSubscription,
	} = useContext( PaymentContext );
	const queryClient = useQueryClient();

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
		onError: ( error ) => {
			console.error( 'Refund failed:', error );
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
		onError: ( error ) => {
			console.error( 'Cancel failed:', error );
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
		onError: ( error ) => {
			console.error( 'Pause failed:', error );
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
			queryClient.invalidateQueries( [ 'subscription', viewSingleSubscription ] );
		},
		onError: ( error ) => {
			alert( __( 'Failed to add note. Please try again.', 'sureforms' ) );
		},
	} );

	// Delete note mutation
	const deleteNoteMutation = useMutation( {
		mutationFn: ( { paymentId, noteIndex } ) =>
			deletePaymentNote( paymentId, noteIndex ),
		onSuccess: ( updatedNotes ) => {
			setNotes( updatedNotes );
			queryClient.invalidateQueries( [ 'subscription', viewSingleSubscription ] );
		},
		onError: ( error ) => {
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
			queryClient.invalidateQueries( [ 'subscription', viewSingleSubscription ] );
		},
		onError: ( error ) => {
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

	// Utility function to format date and time
	const formatDateTime = ( dateString ) => {
		if ( ! dateString ) return 'N/A';
		const date = new Date( dateString );
		const formattedDate = date.toLocaleDateString( 'en-US', {
			month: 'short',
			day: 'numeric',
			year: 'numeric',
		} );
		const formattedTime = date.toLocaleTimeString( 'en-US', {
			hour: '2-digit',
			minute: '2-digit',
			hour12: true,
		} );
		return `${ formattedDate } at ${ formattedTime }`;
	};

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

	// Loading state
	if ( isLoading ) {
		return (
			<div className="srfm-single-payment-wrapper min-h-screen bg-background-secondary p-8">
				<div className="flex items-center justify-center h-96">
					<Text>
						{ __( 'Loading subscription details…', 'sureforms' ) }
					</Text>
				</div>
			</div>
		);
	}

	// Error state
	if ( error ) {
		return (
			<div className="srfm-single-payment-wrapper min-h-screen bg-background-secondary p-8">
				<div className="flex items-center justify-center h-96">
					<div className="text-center">
						<Text className="text-red-600 mb-4">
							{ __(
								'Error loading subscription details',
								'sureforms'
							) }
						</Text>
						<Button
							variant="outline"
							onClick={ () => setViewSingleSubscription( false ) }
						>
							{ __( 'Back to Payments', 'sureforms' ) }
						</Button>
					</div>
				</div>
			</div>
		);
	}

	// No subscription data
	if ( ! subscriptionData ) {
		return (
			<div className="srfm-single-payment-wrapper min-h-screen bg-background-secondary p-8">
				<div className="flex items-center justify-center h-96">
					<div className="text-center">
						<Text className="mb-4">
							{ __( 'Subscription not found', 'sureforms' ) }
						</Text>
						<Button
							variant="outline"
							onClick={ () => setViewSingleSubscription( false ) }
						>
							{ __( 'Back to Payments', 'sureforms' ) }
						</Button>
					</div>
				</div>
			</div>
		);
	}

	// TODO: Implement navigation to previous payment
	const handlePrevious = () => {
		console.log( 'Navigate to previous payment' );
	};

	// TODO: Implement navigation to next payment
	const handleNext = () => {
		console.log( 'Navigate to next payment' );
	};

	// TODO: Implement Stripe dashboard integration
	const handleViewInStripe = () => {
		console.log( 'Open Stripe dashboard' );
	};

	// TODO: Implement email resend functionality
	const handleResendEmail = () => {
		console.log( 'Resend email notification' );
	};

	// TODO: Implement add note functionality
	const handleAddNote = () => {
		console.log( 'Add new note' );
	};

	// TODO: Implement refund payment functionality
	const handleRefundPayment = () => {
		console.log( 'Refund payment' );
	};

	// TODO: Implement delete log entry functionality
	const handleDeleteLogEntry = ( id ) => {
		console.log( 'Delete log entry:', id );
	};

	const handleRefund = ( paymentId ) => {
		// Find the payment in the billing data
		const payment = subscriptionBillingData.find(
			( p ) => p.id === paymentId
		);
		if ( payment ) {
			openRefundDialog( payment );
		}
	};

	// TODO: Implement cancel refund functionality
	const handleCancelRefund = ( id ) => {
		console.log( 'Cancel refund:', id );
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
		if (
			confirm(
				__( 'Are you sure you want to delete this note?', 'sureforms' )
			)
		) {
			deleteNoteMutation.mutate( {
				paymentId: subscriptionData.id,
				noteIndex,
			} );
		}
	};

	const handleDeleteLog = ( logIndex ) => {
		if (
			confirm(
				__( 'Are you sure you want to delete this log entry?', 'sureforms' )
			)
		) {
			deleteLogMutation.mutate( {
				paymentId: subscriptionData.id,
				logIndex,
			} );
		}
	};

	const formatLogTimestamp = ( timestamp ) => {
		// Validate timestamp
		if ( ! timestamp || isNaN( timestamp ) || timestamp <= 0 ) {
			return __( 'N/A', 'sureforms' );
		}

		const date = new Date( timestamp * 1000 );

		// Check if date is valid
		if ( isNaN( date.getTime() ) ) {
			return __( 'Invalid Date', 'sureforms' );
		}

		return date.toLocaleString( 'en-US', {
			year: 'numeric',
			month: '2-digit',
			day: '2-digit',
			hour: '2-digit',
			minute: '2-digit',
			second: '2-digit',
			hour12: false,
		} );
	};

	const handleRefundLatestEMI = () => {
		// Find the latest (most recent) paid EMI transaction
		const latestPaidEMI = subscriptionBillingData
			.filter(
				( payment ) =>
					payment.status === 'paid' ||
					payment.status === 'succeeded'
			)
			.sort(
				( a, b ) =>
					new Date( b.date_time ) - new Date( a.date_time )
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

	const formatAmount = ( amount, currency = 'USD' ) => {
		const symbol = currency === 'USD' ? '$' : currency + ' ';
		return `${ symbol }${ parseFloat( amount ).toFixed( 2 ) }`;
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
				message: __(
					`Amount cannot exceed ${ formatAmount(
						refundableAmount,
						selectedPaymentForRefund.currency
					) }.`,
					'sureforms'
				),
			};
		}

		// Check if it's a full refund (allowing for small floating point differences)
		const isFullRefund =
			Math.abs( requestedAmount - refundableAmount ) < 0.01;

		if ( isFullRefund ) {
			return {
				type: 'info',
				message: __(
					`This will issue a complete refund of ${ formatAmount(
						requestedAmount,
						selectedPaymentForRefund.currency
					) }. The entire payment will be refunded.`,
					'sureforms'
				),
			};
		}
		const remainingBalance = refundableAmount - requestedAmount;
		return {
			type: 'warning',
			message: __(
				`This will issue a partial refund of ${ formatAmount(
					requestedAmount,
					selectedPaymentForRefund.currency
				) }. Remaining balance of ${ formatAmount(
					remainingBalance,
					selectedPaymentForRefund.currency
				) } will still be valid.`,
				'sureforms'
			),
		};
	};

	const header = (
		<Container
			containerType="flex"
			direction="row"
			gap="xs"
			className="w-full justify-between"
		>
			<div>
				<h1>{ __( 'Subscription Details', 'sureforms' ) }</h1>
				<Text color="secondary" size={ 14 }>
					{ __( 'Subscription ID:', 'sureforms' ) } #
					{ subscriptionData.id } |{ __( 'Customer:', 'sureforms' ) }{ ' ' }
					{ subscriptionData.customer || __( 'Guest', 'sureforms' ) }
				</Text>
			</div>
			<div className="flex gap-2">
				<Button
					icon={ <ChevronLeft aria-label="icon" role="img" /> }
					iconPosition="left"
					size="sm"
					variant="outline"
					onClick={ () => setViewSingleSubscription( false ) }
				>
					{ __( 'Back', 'sureforms' ) }
				</Button>
			</div>
		</Container>
	);

	// Subscription info data
	const subscriptionInfoData = [
		{
			'Customer Name':
				subscriptionData.customer || __( 'Guest', 'sureforms' ),
		},
		{
			Email: subscriptionData.customer_email || __( 'N/A', 'sureforms' ),
		},
		{
			Status: subscriptionData.status || __( 'Unknown', 'sureforms' ),
		},
		{
			'Billing Cycle':
				subscriptionData.interval || __( 'N/A', 'sureforms' ),
		},
		{
			'Next Payment': subscriptionData.next_payment_date
				? formatDateTime( subscriptionData.next_payment_date )
				: __( 'N/A', 'sureforms' ),
		},
		{
			'Amount per Cycle': formatAmount(
				subscriptionData.amount_per_cycle ||
					subscriptionData.total_amount,
				subscriptionData.currency
			),
		},
	];

	// Subscription info component
	const subscriptionInfo = subscriptionInfoData.map( ( item, index ) => {
		const [ key, value ] = Object.entries( item )[ 0 ]; // Get the first key-value pair
		return (
			<div
				key={ `customer-info-${ index }` }
				className="flex gap-1 items-center p-2 border-b last:border-b-0 bg-background-primary rounded-xl shadow-sm"
			>
				<Text
					as="p"
					color="primary"
					lineHeight={ 20 }
					size={ 14 }
					weight={ 600 }
					className="w-[160px]"
				>
					{ key }:
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
	} );

	// Subscription billing history data - use fetched billing data or empty array
	const subscriptionBillingData = billingData || [];

	// Get status badge variant for billing table
	const getStatusVariant = ( status ) => {
		switch ( status ) {
			case 'paid':
				return 'success';
			case 'pending':
				return 'warning';
			case 'failed':
				return 'danger';
			case 'refunded':
				return 'secondary';
			default:
				return 'secondary';
		}
	};

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
							__(
								`Process refund for payment #${ selectedPaymentForRefund.id }. The refunded amount will be sent to the customer's original payment method.`,
								'sureforms'
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
								{ selectedPaymentForRefund &&
									__(
										`Maximum refundable amount: ${ formatAmount(
											refundableAmount,
											selectedPaymentForRefund.currency
										) }`,
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
									{ selectedPaymentForRefund &&
										__(
											`Already refunded: ${ formatAmount(
												alreadyRefunded,
												selectedPaymentForRefund.currency
											) }`,
											'sureforms'
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
						{ __(
							`Are you sure you want to cancel subscription #${ subscriptionData.id }? This action cannot be undone and the customer will lose access to their subscription benefits.`,
							'sureforms'
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
							cancelMutation.mutate( subscriptionData.id )
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
						{ __(
							`Are you sure you want to pause subscription #${ subscriptionData.id }? The customer will not be charged until you resume the subscription.`,
							'sureforms'
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
							pauseMutation.mutate( subscriptionData.id )
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
							{ __( 'Date & Time', 'sureforms' ) }
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
										label={
											row.status === 'paid'
												? __( 'Paid', 'sureforms' )
												: row.status
										}
										type="pill"
										className='w-fit'
									/>
								</Table.Cell>
								<Table.Cell>
									{ formatDateTime( row.date_time ) }
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
			'Subscription Id': `#${ subscriptionData.id }`,
		},
		{
			'Form Name':
				subscriptionData.form_title ||
				__( 'Unknown Form', 'sureforms' ),
		},
		{
			'Payment Method':
				subscriptionData.gateway || __( 'Unknown', 'sureforms' ),
		},
		{
			'Payment Mode':
				subscriptionData.mode || __( 'Unknown', 'sureforms' ),
		},
		{
			'Subscription Status':
				subscriptionData.status || __( 'Unknown', 'sureforms' ),
		},
		{
			'Stripe Subscription ID':
				subscriptionData.stripe_subscription_id ||
				__( 'N/A', 'sureforms' ),
		},
		{
			'Customer ID':
				subscriptionData.customer_id || __( 'Guest', 'sureforms' ),
		},
		{
			'Created On': formatDateTime( subscriptionData.created_at ),
		},
		{
			'Next Payment Date': subscriptionData.next_payment_date
				? formatDateTime( subscriptionData.next_payment_date )
				: __( 'N/A', 'sureforms' ),
		},
	];

	// Subscription details component
	const subscriptionDetails = subscriptionDetailsData.map(
		( item, index ) => {
			const [ key, value ] = Object.entries( item )[ 0 ]; // Get the first key-value pair
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
						{ key }:
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
			{ /* customer info */ }
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
						{ __( 'Subscription Information', 'sureforms' ) }
					</Label>
					<Button
						icon={ null }
						iconPosition="left"
						size="xs"
						variant="outline"
					>
						{ __( 'Edit Entry', 'sureforms' ) }
					</Button>
				</Container>
				<Container className="flex flex-col bg-background-secondary gap-1 p-1 rounded-lg">
					{ subscriptionInfo }
				</Container>
			</Container>
			{ /* billing details  */ }
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
						{ __( 'Subscription Details', 'sureforms' ) }
					</Label>
					<Badge
						variant={
							subscriptionData.status === 'active'
								? 'success'
								: subscriptionData.status === 'canceled'
									? 'danger'
									: 'warning'
						}
					>
						{ __( 'Status:', 'sureforms' ) }{ ' ' }
						{ subscriptionData.status ||
							__( 'Unknown', 'sureforms' ) }
					</Badge>
					<DropdownMenu placement="right-end">
						<DropdownMenu.Trigger>
							<Button
								icon={<EllipsisVertical className="!size-5" />}
								iconPosition="right"
								size="sm"
								variant="outline"
							>
								{__('Actions', 'sureforms')}
							</Button>
						</DropdownMenu.Trigger>
						<DropdownMenu.ContentWrapper>
							<DropdownMenu.Content className="w-60">
								<DropdownMenu.List>
									<DropdownMenu.Item
										onClick={openCancelDialog}
									>
										{__('Cancel Subscription', 'sureforms')}
									</DropdownMenu.Item>
									<DropdownMenu.Item
										onClick={openPauseDialog}
									>
										{__('Pause Subscription', 'sureforms')}
									</DropdownMenu.Item>
									<DropdownMenu.Item
										onClick={handleRefundLatestEMI}
									>
										{__('Refund Latest EMI', 'sureforms')}
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
						{ __( 'Payment Info', 'sureforms' ) }
					</Label>
					<Button
						icon={ <ArrowUpRight className="!size-5" /> }
						iconPosition="right"
						variant="link"
						size="sm"
						className="h-full text-link-primary text-sm font-semibold no-underline hover:no-underline hover:text-link-primary-hover px-1 content-center [box-shadow:none] focus:[box-shadow:none] focus:outline-none"
						onClick={ () => {} }
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
					{ notes && notes.length > 0 ? (
						notes.map( ( note, index ) => (
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
											{ new Date( note.created_at ).toLocaleString() }
										</Text>
									) }
								</div>
								<Button
									variant="ghost"
									size="xs"
									icon={ <Trash2 className="!size-4" /> }
									onClick={ () => handleDeleteNote( index ) }
									disabled={ deleteNoteMutation.isPending }
									className="text-icon-secondary hover:text-red-700"
								/>
							</div>
						) )
					) : (
						! isAddingNote && (
							<Text className="text-sm text-text-secondary p-3 text-center flex items-center justify-center gap-2">
								<FileSearch2 className="!size-5" />
								{ __( 'Add an internal note about this subscription', 'sureforms' ) }
							</Text>
						)
					) }

					{ isAddingNote && (
						<div className="w-full p-3 bg-background-primary rounded-lg border border-border-subtle">
							<TextArea
								value={ newNoteText }
								onChange={ ( value ) => setNewNoteText( value ) }
								placeholder={ __( 'Enter your note here...', 'sureforms' ) }
								size="sm"
								className="w-full"
								autoFocus
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
									disabled={ addNoteMutation.isPending || ! newNoteText.trim() }
								>
									{ addNoteMutation.isPending
										? __( 'Adding...', 'sureforms' )
										: __( 'Add Note', 'sureforms' ) }
								</Button>
							</div>
						</div>
					) }
				</Container>
			</Container>
			{ /* Payment log */ }
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
						{ __( 'Payment Log', 'sureforms' ) }
					</Label>
				</Container>
				<Container className="flex flex-col items-center justify-center bg-background-secondary gap-1 p-1 rounded-lg min-h-[89px]">
					{ logs && logs.length > 0 ? (
						logs.map( ( log, index ) => {
							// Defensive checks for log data
							if ( ! log || typeof log !== 'object' ) {
								return null;
							}

							const logTitle = log.title || __( 'Untitled Log', 'sureforms' );
							const logMessages = Array.isArray( log.messages ) ? log.messages : [];

							return (
								<div
									key={ index }
									className="w-full flex flex-col gap-2 p-3 bg-background-primary rounded-lg border border-border-subtle"
								>
									<div className="flex justify-between items-start gap-2">
										<div className="flex-1">
											<Text className="text-sm font-semibold">
												{ logTitle }
											</Text>
											<Text className="text-xs text-text-tertiary mt-1">
												{ formatLogTimestamp( log.timestamp ) }
											</Text>
										</div>
										<Button
											variant="ghost"
											size="xs"
											icon={ <Trash2 className="!size-4" /> }
											onClick={ () => handleDeleteLog( index ) }
											disabled={ deleteLogMutation.isPending }
											className="text-icon-secondary hover:text-red-700"
										/>
									</div>
									{ logMessages.length > 0 && (
										<div className="flex flex-col gap-1 mt-1">
											{ logMessages.map( ( message, msgIndex ) => (
												<Text
													key={ msgIndex }
													className="text-xs text-text-secondary"
												>
													{ message || '' }
												</Text>
											) ) }
										</div>
									) }
								</div>
							);
						} )
					) : (
						<Text className="text-sm text-text-secondary p-3 text-center">
							{ __( 'No payment logs available', 'sureforms' ) }
						</Text>
					) }
				</Container>
			</Container>
		</>
	);

	return (
		<div className="srfm-single-payment-wrapper min-h-screen bg-background-secondary p-8">
			<Container
				containerType="flex"
				direction="column"
				gap="xs"
				className="w-full h-full"
			>
				{ header }
				<Container
					className="w-full gap-8"
					containerType="grid"
					cols={ 12 }
				>
					<div className="flex flex-col gap-8 col-span-12 xl:col-span-8">
						{ PAYMENT_SECTION_COLUMN_1 }
					</div>
					<div className="flex flex-col gap-8 col-span-12 xl:col-span-4">
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
