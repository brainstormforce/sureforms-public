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
	fetchSinglePayment,
	refundPayment,
	addPaymentNote,
	deletePaymentNote,
	deletePaymentLog,
} from '../components/apiCalls';
import { currencies } from '../components/utils';

const ViewPayment = () => {
	const { viewSinglePayment, setViewSinglePayment } =
		useContext( PaymentContext );
	const queryClient = useQueryClient();

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
		onError: ( error ) => {
			console.error( 'Refund failed:', error );
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
			queryClient.invalidateQueries( [ 'payment', viewSinglePayment ] );
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
			queryClient.invalidateQueries( [ 'payment', viewSinglePayment ] );
		},
		onError: ( error ) => {
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

	// Loading state
	if ( isLoading ) {
		return (
			<div className="srfm-single-payment-wrapper min-h-screen bg-background-secondary p-8">
				<div className="flex items-center justify-center h-96">
					<Text>
						{ __( 'Loading payment details…', 'sureforms' ) }
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
								'Error loading payment details',
								'sureforms'
							) }
						</Text>
						<Button
							variant="outline"
							onClick={ () => setViewSinglePayment( false ) }
						>
							{ __( 'Back to Payments', 'sureforms' ) }
						</Button>
					</div>
				</div>
			</div>
		);
	}

	// No payment data
	if ( ! paymentData ) {
		return (
			<div className="srfm-single-payment-wrapper min-h-screen bg-background-secondary p-8">
				<div className="flex items-center justify-center h-96">
					<div className="text-center">
						<Text className="mb-4">
							{ __( 'Payment not found', 'sureforms' ) }
						</Text>
						<Button
							variant="outline"
							onClick={ () => setViewSinglePayment( false ) }
						>
							{ __( 'Back to Payments', 'sureforms' ) }
						</Button>
					</div>
				</div>
			</div>
		);
	}

	const handleViewInStripe = () => {
		console.log( 'Open Stripe dashboard' );
	};

	const handleResendEmail = () => {
		console.log( 'Resend email notification' );
	};

	const handleAddNote = () => {
		console.log( 'Add new note' );
	};

	const handleRefundPayment = () => {
		console.log( 'Refund payment' );
	};

	const handleDeleteLogEntry = ( id ) => {
		console.log( 'Delete log entry:', id );
	};

	const handleRefund = ( id ) => {
		openRefundDialog();
	};

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
			paymentId: paymentData.id,
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
				paymentId: paymentData.id,
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
				paymentId: paymentData.id,
				logIndex,
			} );
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

	// Get currency symbol from currency code
	const getCurrencySymbol = ( currencyCode ) => {
		const upperCurrencyCode = currencyCode?.toUpperCase();
		const currency = currencies.find( ( c ) => c.value === upperCurrencyCode );
		return currency ? currency.symbol : currencyCode;
	};

	// Format amount with currency symbol (no space between symbol and amount)
	const formatAmount = ( amount, currency = 'USD' ) => {
		const symbol = getCurrencySymbol( currency );
		return `${ symbol }${ parseFloat( amount ).toFixed( 2 ) }`;
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
				message: __(
					`Amount cannot exceed ${ formatAmount(
						refundableAmount,
						paymentData.currency
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
						paymentData.currency
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
					paymentData.currency
				) }. Remaining balance of ${ formatAmount(
					remainingBalance,
					paymentData.currency
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
			<h1>
				{ formatAmount(
					paymentData.total_amount,
					paymentData.currency
				) } From { paymentData.customer }
			</h1>
		</Container>
	);

	// Customer info data from real payment data
	const customerInfoData = [
		{
			'Customer Name': paymentData.customer || __( 'Guest', 'sureforms' ),
		},
		{
			Email: paymentData.customer_email || __( 'N/A', 'sureforms' ),
		},
		{
			Amount: formatAmount(
				paymentData.total_amount,
				paymentData.currency
			),
		},
	];

	// Customer info component
	const customerInfo = customerInfoData.map( ( item, index ) => {
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

	// Format date time for billing table
	const formatDateTime = ( datetime ) => {
		const date = new Date( datetime );
		return date.toLocaleString( 'en-US', {
			month: 'short',
			day: 'numeric',
			year: 'numeric',
			hour: 'numeric',
			minute: '2-digit',
			hour12: true,
		} );
	};

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
						{ __(
							`Process refund for payment #${ paymentData.id }. The refunded amount will be sent to the customer's original payment method.`,
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
								{ __(
									`Maximum refundable amount: ${ formatAmount(
										refundableAmount,
										paymentData.currency
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
									{ __(
										`Already refunded: ${ formatAmount(
											alreadyRefunded,
											paymentData.currency
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
						<Table.HeadCell className='w-1/10'>
							{ __( 'Action', 'sureforms' ) }
						</Table.HeadCell>
					</Table.Head>
					<Table.Body>
						{ billingData.map( ( row ) => (
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
													row.amount_paid,
													paymentData.currency
												) }
											</span>
											<strong>
												{ formatAmount(
													row.amount_paid -
														row.refunded_amount,
													paymentData.currency
												) }
											</strong>
										</span>
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
										label={
											row.status === 'succeeded'
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
								<Table.Cell>
									<DropdownMenu placement="bottom-end">
										<DropdownMenu.Trigger>
											<EllipsisVertical />
											<span className="sr-only">
												{ __(
													'Open Menu',
													'sureforms'
												) }
											</span>
										</DropdownMenu.Trigger>
										<DropdownMenu.ContentWrapper>
											<DropdownMenu.Content className="w-60">
												<DropdownMenu.List>
													<DropdownMenu.Item
														onClick={ () =>
															handleRefund(
																row.id
															)
														}
													>
														{ __(
															'Refund',
															'sureforms'
														) }
													</DropdownMenu.Item>
													<DropdownMenu.Item
														onClick={ () =>
															handleCancelRefund(
																row.id
															)
														}
													>
														{ __(
															'Cancel Payment',
															'sureforms'
														) }
													</DropdownMenu.Item>
												</DropdownMenu.List>
											</DropdownMenu.Content>
										</DropdownMenu.ContentWrapper>
									</DropdownMenu>
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
			'Payment Id': `#${ paymentData.id }`,
		},
		{
			'Form Name':
				paymentData.form_title || __( 'Unknown Form', 'sureforms' ),
		},
		{
			'Payment Method':
				paymentData.gateway || __( 'Unknown', 'sureforms' ),
		},
		{
			'Payment Mode': paymentData.mode || __( 'Unknown', 'sureforms' ),
		},
		{
			'Payment Type':
				paymentData.payment_type || __( 'One Time', 'sureforms' ),
		},
		{
			'Transaction ID':
				paymentData.transaction_id || __( 'N/A', 'sureforms' ),
		},
		{
			'Customer ID':
				paymentData.customer_id || __( 'Guest', 'sureforms' ),
		},
		{
			'Submitted On': formatDateTime( paymentData.created_at ),
		},
	];

	// Payment info component
	const paymentInfo = paymentInfoData.map( ( item, index ) => {
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
	} );

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
						{ __( 'Form Submission Details', 'sureforms' ) }
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
					{ customerInfo }
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
						{ __( 'Billing Details', 'sureforms' ) }
					</Label>
					<Badge variant="success">
						{ __( 'Status: Paid', 'sureforms' ) }
					</Badge>
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
					{ paymentInfo }
				</Container>
			</Container>
		</>
	);

	// Format timestamp from unix timestamp to readable date
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

	const notesSection = (
		<>
			{ /* Existing notes list */ }
			{ notes && notes.length > 0 ? (
				notes.map( ( note, index ) => (
					<div
						key={ index }
						className="w-full flex justify-between items-start gap-2 p-3 bg-background-primary rounded-lg border border-border-subtle"
					>
						<div className="flex-1">
							<Text className="text-sm whitespace-pre-wrap break-words">
								{ note.text || note }
							</Text>
							{ note.created_at && (
								<Text className="text-xs text-text-tertiary mt-1">
									{ new Date(
										note.created_at
									).toLocaleString() }
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
						{ __(
							'Add an internal note about this payment',
							'sureforms'
						) }
					</Text>
				)
			) }

			{ /* Add note textarea */ }
			{ isAddingNote && (
				<div className="w-full p-3 bg-background-primary rounded-lg border border-border-subtle">
					<TextArea
						value={ newNoteText }
						onChange={ ( value ) => setNewNoteText( value ) }
						placeholder={ __(
							'Enter your note here...',
							'sureforms'
						) }
						size="sm"
						className='w-full'
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
							disabled={
								addNoteMutation.isPending ||
								! newNoteText.trim()
							}
						>
							{ addNoteMutation.isPending
								? __( 'Adding...', 'sureforms' )
								: __( 'Add Note', 'sureforms' ) }
						</Button>
					</div>
				</div>
			) }
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
					{notesSection}
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
		</div>
	);
};

export default ViewPayment;
