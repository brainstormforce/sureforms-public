import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CircleCheck } from 'lucide-react';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Loader,
	Label,
	RadioButton,
	Select,
	toast,
	Dialog,
	Input,
} from '@bsf/force-ui';
import ContentSection from '@Admin/settings/components/ContentSection';
import { currencies, AlertForFee } from '../components/utils';

const Payments = ( {
	loading,
	paymentsSettings,
	updateGlobalSettings,
	setPaymentsSettings,
} ) => {
	const [ isConnecting, setIsConnecting ] = useState( false );
	const [ isDisconnecting, setIsDisconnecting ] = useState( false );
	const [ isCreatingWebhook, setIsCreatingWebhook ] = useState( false );
	const [ isDisconnectDialogOpen, setIsDisconnectDialogOpen ] =
		useState( false );
	const [ confirmationInput, setConfirmationInput ] = useState( '' );

	// Load settings on mount
	useEffect( () => {
		// Check URL parameters for OAuth callback status
		const urlParams = new URLSearchParams( window.location.search );

		if ( urlParams.get( 'connected' ) === '1' ) {
			toast.success(
				__( 'Successfully connected to Stripe!', 'sureforms' )
			);
			// Clean URL
			window.history.replaceState(
				{},
				'',
				window.location.pathname +
					'?page=sureforms_form_settings&tab=payments-settings'
			);
		}

		if ( urlParams.get( 'error' ) ) {
			const errorMessage = decodeURIComponent( urlParams.get( 'error' ) );
			toast.error( errorMessage );
			// Clean URL
			window.history.replaceState(
				{},
				'',
				window.location.pathname +
					'?page=sureforms_form_settings&tab=payments-settings'
			);
		}
	}, [] );

	// Handle Stripe Connect
	const handleStripeConnect = async () => {
		setIsConnecting( true );
		try {
			const response = await apiFetch( {
				path: '/sureforms/v1/payments/stripe-connect',
				method: 'GET',
			} );

			// Validate response
			if ( ! response || ! response.url ) {
				throw new Error(
					__(
						'Invalid response from server. Please try again.',
						'sureforms'
					)
				);
			}

			// Redirect to Stripe OAuth
			window.location.href = response.url;
		} catch ( error ) {
			const errorMessage =
				error.message ||
				__( 'Failed to connect to Stripe.', 'sureforms' );
			toast.error( errorMessage );
			setIsConnecting( false );
		}
	};

	// Handle Stripe Disconnect - Open confirmation dialog
	const handleStripeDisconnect = () => {
		setIsDisconnectDialogOpen( true );
	};

	// Handle dialog close - Reset confirmation input
	const handleDialogClose = () => {
		setIsDisconnectDialogOpen( false );
		setConfirmationInput( '' );
	};

	// Confirm and process Stripe disconnect
	const confirmStripeDisconnect = async () => {
		setIsDisconnecting( true );
		try {
			const response = await apiFetch( {
				path: '/sureforms/v1/payments/stripe-disconnect',
				method: 'POST',
			} );

			// Validate response
			if ( ! response || ! response.success ) {
				throw new Error(
					response?.message ||
						__(
							'Failed to disconnect Stripe account.',
							'sureforms'
						)
				);
			}

			// Update local state
			const newSettings = {
				...paymentsSettings,
				stripe_connected: false,
				stripe_account_id: '',
				stripe_account_email: '',
				stripe_live_publishable_key: '',
				stripe_live_secret_key: '',
				stripe_test_publishable_key: '',
				stripe_test_secret_key: '',
				webhook_test_secret: '',
				webhook_test_url: '',
				webhook_test_id: '',
				webhook_live_secret: '',
				webhook_live_url: '',
				webhook_live_id: '',
			};

			// Update global settings first to ensure parent component syncs
			if ( updateGlobalSettings ) {
				// Update each critical field in global settings
				Object.keys( newSettings ).forEach( ( key ) => {
					updateGlobalSettings(
						key,
						newSettings[ key ],
						'payments-settings'
					);
				} );
			}

			// Update local state after global settings
			setPaymentsSettings( newSettings );

			toast.success(
				__( 'Stripe account disconnected successfully.', 'sureforms' )
			);
			handleDialogClose();
		} catch ( error ) {
			const errorMessage =
				error.message ||
				__( 'Failed to disconnect Stripe account.', 'sureforms' );
			toast.error( errorMessage );
		} finally {
			setIsDisconnecting( false );
		}
	};

	// Handle Webhook Creation
	const handleWebhookCreation = async () => {
		setIsCreatingWebhook( true );
		try {
			const response = await apiFetch( {
				path: '/sureforms/v1/payments/create-payment-webhook',
				method: 'POST',
			} );

			if ( response.success ) {
				toast.success(
					response.message ||
						__( 'Webhook created successfully!', 'sureforms' )
				);

				// Update local state with webhook details from API response
				const testDetails = response.webhook_details?.test || {};
				const liveDetails = response.webhook_details?.live || {};

				setPaymentsSettings( {
					...paymentsSettings,
					webhook_test_secret:
						testDetails.webhook_secret || 'created',
					webhook_test_url:
						testDetails.webhook_url ||
						`${ window.location.origin }/wp-json/sureforms/webhook`,
					webhook_test_id: testDetails.webhook_id || 'created',
					webhook_live_secret:
						liveDetails.webhook_secret || 'created',
					webhook_live_url:
						liveDetails.webhook_url ||
						`${ window.location.origin }/wp-json/sureforms/webhook`,
					webhook_live_id: liveDetails.webhook_id || 'created',
				} );
			} else {
				toast.error(
					response.message ||
						__( 'Failed to create webhook.', 'sureforms' )
				);
			}
		} catch ( error ) {
			const errorMessage =
				error.message || __( 'Failed to create webhook.', 'sureforms' );
			toast.error( errorMessage );
		} finally {
			setIsCreatingWebhook( false );
		}
	};

	// Handle settings change with auto-save
	const handleSettingChange = ( key, value ) => {
		// Update local state immediately for UI responsiveness
		setPaymentsSettings( {
			...paymentsSettings,
			[ key ]: value,
		} );

		// Trigger auto-save via global settings with debouncing
		if ( updateGlobalSettings ) {
			updateGlobalSettings( key, value, 'payments-settings' );
		}
	};

	// Get current currency label
	const getCurrentCurrencyLabel = () => {
		const currency = currencies.find(
			( c ) => c.value === paymentsSettings.currency
		);
		return currency ? currency.label : currencies[ 0 ].label;
	};

	const selectCountry = (
		<div className="flex flex-col gap-2">
			<Select
				value={ paymentsSettings.currency }
				onChange={ ( value ) =>
					handleSettingChange( 'currency', value )
				}
			>
				<Select.Button
					type="button"
					label={ __( 'Select Currency', 'sureforms' ) }
					size="md"
				>
					{ getCurrentCurrencyLabel() }
				</Select.Button>
				<Select.Portal id="srfm-settings-container">
					<Select.Options>
						{ currencies.map( ( currency ) => (
							<Select.Option
								key={ currency.value }
								value={ currency.value }
							>
								{ currency.label }
							</Select.Option>
						) ) }
					</Select.Options>
				</Select.Portal>
			</Select>
			<p className="text-sm text-field-helper">
				{ __(
					'Select the default currency for payment forms.',
					'sureforms'
				) }
			</p>
		</div>
	);

	const paymentMode = (
		<div className="flex flex-col gap-2">
			<Label size="sm" variant="neutral">
				{ __( 'Payment Mode', 'sureforms' ) }
			</Label>
			<RadioButton.Group
				columns={ 2 }
				value={ paymentsSettings.payment_mode }
				onChange={ ( value ) =>
					handleSettingChange( 'payment_mode', value )
				}
				size="sm"
				className="w-fit"
			>
				<RadioButton.Button
					borderOn
					label={ {
						heading: __( 'Live Mode', 'sureforms' ),
					} }
					value="live"
				/>
				<RadioButton.Button
					borderOn
					label={ {
						heading: __( 'Test Mode', 'sureforms' ),
					} }
					value="test"
				/>
			</RadioButton.Group>
		</div>
	);

	const accountDetails = (
		<div className="flex flex-col gap-2">
			<Label size="sm" variant="neutral">
				{ __( 'Connection Status', 'sureforms' ) }
			</Label>
			<div className="flex items-center justify-between p-4 rounded-lg border-0.5 border-border-subtle border-solid shadow-sm">
				<div className="flex items-center gap-2">
					<CircleCheck className="text-support-success" />
					<div>
						<p className="text-sm text-text-tertiary">
							{ paymentsSettings?.account_data?.settings
								?.dashboard?.display_name ||
								paymentsSettings.stripe_account_id }
						</p>
					</div>
				</div>

				<Button
					onClick={ handleStripeDisconnect }
					disabled={ isDisconnecting }
					icon={ isDisconnecting && <Loader /> }
					iconPosition="left"
					variant="link"
					size="xs"
					className="text-red-600 hover:text-red-700 no-underline hover:no-underline"
				>
					{ isDisconnecting
						? __( 'Disconnecting…', 'sureforms' )
						: __( 'Disconnect', 'sureforms' ) }
				</Button>
			</div>
		</div>
	);

	const isLiveMode = paymentsSettings.payment_mode === 'live';
	const currentWebhookSecret = isLiveMode
		? paymentsSettings.webhook_live_secret
		: paymentsSettings.webhook_test_secret;

	const webhookManagement = (
		<div className="flex flex-col gap-2">
			<Label size="sm" variant="neutral">
				{ __( 'Webhook', 'sureforms' ) }
			</Label>

			{ currentWebhookSecret ? (
				<div className="flex items-center p-4 rounded-lg border-0.5 border-border-subtle border-solid shadow-sm gap-2">
					<CircleCheck className="text-support-success" />
					<div>
						<p className="text-sm text-text-secondary font-normal">
							{ __(
								'Webhook successfully connected, all Stripe events are being tracked.',
								'sureforms'
							) }
						</p>
					</div>
				</div>
			) : (
				<div className="flex items-center justify-between gap-2">
					<p className="text-sm text-text-secondary font-normal max-w-[70%]">
						{ __(
							'Webhooks let Stripe notify SureForms about payment events like successful charges or subscription updates, keeping your data in sync automatically.',
							'sureforms'
						) }
					</p>
					<Button
						onClick={ handleWebhookCreation }
						disabled={ isCreatingWebhook || loading }
						icon={ isCreatingWebhook && <Loader /> }
						iconPosition="left"
						variant="outline"
						size="xs"
					>
						{ isCreatingWebhook
							? __( 'Creating…', 'sureforms' )
							: __( 'Auto Create Webhook', 'sureforms' ) }
					</Button>
				</div>
			) }
		</div>
	);

	// Disconnect Confirmation Dialog
	const disconnectDialog = (
		<Dialog
			open={ isDisconnectDialogOpen }
			setOpen={ setIsDisconnectDialogOpen }
			design="simple"
			exitOnEsc
			scrollLock
		>
			<Dialog.Backdrop />
			<Dialog.Panel>
				<Dialog.Header>
					<div className="flex items-center justify-between">
						<Dialog.Title>
							{ __( 'Disconnect Stripe Account', 'sureforms' ) }
						</Dialog.Title>
						<Dialog.CloseButton
							onClick={ handleDialogClose }
						/>
					</div>
					<Dialog.Description>
						{ __(
							'Are you sure you want to disconnect your Stripe account? This will stop all active payments, subscriptions, and form transactions connected to this account.',
							'sureforms'
						) }
					</Dialog.Description>
					<Dialog.Description>
						{ __( 'To confirm, type "confirm" in the box below.', 'sureforms' ) }
					</Dialog.Description>
					<Input
						type="text"
						value={ confirmationInput }
						onChange={ ( value ) => setConfirmationInput( value ) }
						placeholder={ __( 'Type "confirm"', 'sureforms' ) }
						disabled={ isDisconnecting }
						size="sm"
					/>
				</Dialog.Header>
				<Dialog.Footer className="flex justify-end gap-2">
					<Button
						variant="outline"
						onClick={ handleDialogClose }
						disabled={ isDisconnecting }
					>
						{ __( 'Cancel', 'sureforms' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ () => {
							if (
								typeof confirmationInput === 'string' &&
								confirmationInput.trim().toLowerCase() === 'confirm'
							) {
								confirmStripeDisconnect();
							}
						} }
						disabled={ isDisconnecting }
						icon={ isDisconnecting && <Loader /> }
						iconPosition="left"
						className="bg-red-600 hover:bg-red-700"
					>
						{ isDisconnecting
							? __( 'Disconnecting…', 'sureforms' )
							: __( 'Disconnect', 'sureforms' ) }
					</Button>
				</Dialog.Footer>
			</Dialog.Panel>
		</Dialog>
	);

	// Content for Payment Settings section
	const paymentSettingsContent = (
		<div className="flex flex-col gap-6 px-2">
			{ /* Currency Selection */ }
			{ selectCountry }
			{ /* Payment Mode */ }
			{ paymentMode }
			{ /* Account Details */ }
			{ accountDetails }
			{ /* Webhook Management */ }
			{ webhookManagement }
			<AlertForFee />
		</div>
	);

	const stripeConnectContent = (
		<div className="flex flex-col gap-[8px] px-2">
			<Label size="sm" variant="neutral">
				{ __(
					'Connect your Stripe account to start accepting payments through your forms.',
					'sureforms'
				) }
			</Label>
			<Button
				onClick={ handleStripeConnect }
				disabled={ isConnecting || loading }
				variant="primary"
				size="sm"
				className="w-fit"
			>
				{ isConnecting
					? __( 'Connecting…', 'sureforms' )
					: __( 'Connect to Stripe', 'sureforms' ) }
			</Button>
			<p className="text-field-helper text-sm">
				{ __(
					'Securely connect to Stripe with just a few clicks to begin accepting payments! ',
					'sureforms'
				) }
				<a
					href="https://sureforms.com/docs/stripe-connect"
					target="_blank"
					rel="noopener noreferrer"
					className="text-link-primary no-underline"
				>
					{ __( 'Learn More', 'sureforms' ) }
				</a>
			</p>
			<AlertForFee />
		</div>
	);

	return (
		<div className="srfm-payment-wrapper">
			{ paymentsSettings?.stripe_connected ? (
				<ContentSection
					loading={ loading }
					title={ __( 'Payment Settings', 'sureforms' ) }
					content={ paymentSettingsContent }
				/>
			) : (
				<ContentSection
					loading={ loading }
					title={ __( 'Stripe Connect', 'sureforms' ) }
					content={ stripeConnectContent }
				/>
			) }
			{ disconnectDialog }
		</div>
	);
};

export default Payments;
