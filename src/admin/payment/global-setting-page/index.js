import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CreditCard, ExternalLink, X } from 'lucide-react';
import apiFetch from '@wordpress/api-fetch';
import {
	Badge,
	Button,
	Loader,
	RadioButton,
	Select,
	toast,
	Container,
	Label,
	Alert,
} from '@bsf/force-ui';
import ContentSection from '@Admin/settings/components/ContentSection';
import { currencies, AlertForFee } from '../components/utils';

const Payments = ( {
	loading,
	paymentsSettings: initialSettings,
	updateGlobalSettings,
} ) => {
	const [ paymentsSettings, setPaymentsSettings ] = useState(
		initialSettings || {
			stripe_connected: false,
			stripe_account_id: '',
			stripe_account_email: '',
			currency: 'USD',
			payment_mode: 'test',
		}
	);
	const [ isConnecting, setIsConnecting ] = useState( false );
	const [ isDisconnecting, setIsDisconnecting ] = useState( false );
	const [ isCreatingWebhook, setIsCreatingWebhook ] = useState( false );
	const [ isDeletingWebhook, setIsDeletingWebhook ] = useState( false );

	// Update local state when props change
	useEffect( () => {
		if ( initialSettings ) {
			setPaymentsSettings( initialSettings );
		}
	}, [ initialSettings ] );

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

			if ( response.url ) {
				window.location.href = response.url;
			}
		} catch ( error ) {
			toast.error( __( 'Failed to connect to Stripe.', 'sureforms' ) );
			setIsConnecting( false );
		}
	};

	// Handle Stripe Disconnect
	const handleStripeDisconnect = async () => {
		if (
			! confirm(
				__(
					'Are you sure you want to disconnect your Stripe account?',
					'sureforms'
				)
			)
		) {
			return;
		}

		setIsDisconnecting( true );
		try {
			await apiFetch( {
				path: '/sureforms/v1/payments/stripe-disconnect',
				method: 'POST',
			} );

			setPaymentsSettings( {
				...paymentsSettings,
				stripe_connected: false,
				stripe_account_id: '',
				stripe_account_email: '',
			} );

			toast.success(
				__( 'Stripe account disconnected successfully.', 'sureforms' )
			);
		} catch ( error ) {
			toast.error(
				__( 'Failed to disconnect Stripe account.', 'sureforms' )
			);
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

			console.log( 'response webhook:', response );

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

	// Handle Webhook Deletion
	const handleWebhookDeletion = async () => {
		if (
			! confirm(
				__(
					'Are you sure you want to delete the webhook? This will stop webhook notifications from Stripe.',
					'sureforms'
				)
			)
		) {
			return;
		}

		setIsDeletingWebhook( true );
		try {
			const response = await apiFetch( {
				path: '/sureforms/v1/payments/delete-payment-webhook',
				method: 'POST',
				data: {
					mode: paymentsSettings.payment_mode,
				},
			} );

			if ( response.success ) {
				toast.success(
					response.message ||
						__( 'Webhook deleted successfully!', 'sureforms' )
				);

				// Update local state to reflect webhook deletion for current mode only
				const isLiveMode = paymentsSettings.payment_mode === 'live';
				if ( isLiveMode ) {
					setPaymentsSettings( {
						...paymentsSettings,
						webhook_live_secret: '',
						webhook_live_url: '',
						webhook_live_id: '',
					} );
				} else {
					setPaymentsSettings( {
						...paymentsSettings,
						webhook_test_secret: '',
						webhook_test_url: '',
						webhook_test_id: '',
					} );
				}
			} else {
				toast.error(
					response.message ||
						__( 'Failed to delete webhook.', 'sureforms' )
				);
			}
		} catch ( error ) {
			const errorMessage =
				error.message || __( 'Failed to delete webhook.', 'sureforms' );
			toast.error( errorMessage );
		} finally {
			setIsDeletingWebhook( false );
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
		<div>
			<Select
				value={ paymentsSettings.currency }
				onChange={ ( value ) =>
					handleSettingChange( 'currency', value )
				}
			>
				<Select.Button
					type="button"
					label={ __( 'Default Currency', 'sureforms' ) }
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
			<p className="mt-1 text-sm text-text-tertiary">
				{ __(
					'Select the default currency for payment forms.',
					'sureforms'
				) }
			</p>
		</div>
	);

	const paymentMode = (
		<div>
			<div className="mb-3">
				<h3 className="text-sm font-medium text-text-primary mb-1">
					{ __( 'Payment Mode', 'sureforms' ) }
				</h3>
			</div>
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
					badgeItem={
						<Badge
							className="mr-2"
							size="sm"
							type="rounded"
							variant="red"
						/>
					}
					borderOn
					label={ {
						heading: __( 'Live Mode', 'sureforms' ),
					} }
					value="live"
				/>
				<RadioButton.Button
					badgeItem={
						<Badge
							className="mr-2"
							size="sm"
							type="rounded"
							variant="yellow"
						/>
					}
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
		<div className="flex items-center justify-between p-4 bg-background-secondary rounded-lg">
			<div className="flex items-center space-x-3">
				<div className="flex items-center justify-center w-10 h-10 bg-badge-background-green rounded-full">
					<CreditCard className="w-5 h-5 text-badge-text-green" />
				</div>
				<div>
					<p className="font-medium text-badge-text-green">
						{ __( 'Connected', 'sureforms' ) }
					</p>
					<p className="text-sm text-text-tertiary">
						{ paymentsSettings.stripe_account_id }
					</p>
				</div>
			</div>

			<Button
				onClick={ handleStripeDisconnect }
				disabled={ isDisconnecting || loading }
				icon={ isDisconnecting && <Loader /> }
				iconPosition="left"
				variant="outline"
				size="xs"
				className="text-red-600 border-red-200 hover:border-red-300 hover:text-red-700"
			>
				{ isDisconnecting ? (
					__( 'Disconnecting…', 'sureforms' )
				) : (
					<>
						<X className="w-3.5 h-3.5 mr-1.5" />
						{ __( 'Disconnect', 'sureforms' ) }
					</>
				) }
			</Button>
		</div>
	);

	const isLiveMode = paymentsSettings.payment_mode === 'live';
	const currentWebhookSecret = isLiveMode
		? paymentsSettings.webhook_live_secret
		: paymentsSettings.webhook_test_secret;

	const webhookActionButton = currentWebhookSecret ? (
		<Button
			onClick={ handleWebhookDeletion }
			disabled={ isDeletingWebhook || loading }
			icon={ isDeletingWebhook && <Loader /> }
			iconPosition="left"
			variant="outline"
			size="xs"
			className="text-red-600 border-red-200 hover:border-red-300 hover:text-red-700"
		>
			{ isDeletingWebhook ? (
				__( 'Deleting…', 'sureforms' )
			) : (
				<>
					<X className="w-3.5 h-3.5 mr-1.5" />
					{ __( 'Delete Webhook', 'sureforms' ) }
				</>
			) }
		</Button>
	) : (
		<Button
			onClick={ handleWebhookCreation }
			disabled={ isCreatingWebhook || loading }
			icon={ isCreatingWebhook && <Loader /> }
			iconPosition="left"
			variant="primary"
			size="xs"
			className="bg-blue-600 hover:bg-blue-700 text-white border-blue-600 hover:border-blue-700"
		>
			{ isCreatingWebhook
				? __( 'Creating…', 'sureforms' )
				: __( 'Create Webhook Automatically', 'sureforms' ) }
		</Button>
	);

	const webhookManagement = (
		<div>
			{ /* Webhook Details */ }
			{ ( paymentsSettings.webhook_test_secret ||
				paymentsSettings.webhook_live_secret ) && (
				<div className="mt-4 p-4 bg-background-secondary rounded-lg border">
					<div className="flex items-center justify-between">
						<h4 className="text-sm font-medium text-text-primary mb-3">
							{ __( 'Webhook Details', 'sureforms' ) }
						</h4>
						<div>{ webhookActionButton }</div>
					</div>

					<div className="space-y-3">
						{ /* Current Mode Webhook Details */ }
						{ ( () => {
							const currentWebhookUrl = isLiveMode
								? paymentsSettings.webhook_live_url
								: paymentsSettings.webhook_test_url;
							const currentWebhookId = isLiveMode
								? paymentsSettings.webhook_live_id
								: paymentsSettings.webhook_test_id;

							if ( ! currentWebhookSecret ) {
								return null;
							}

							return (
								<>
									<div>
										<label className="block text-xs font-medium text-text-tertiary mb-1">
											{ __( 'Webhook URL', 'sureforms' ) }
										</label>
										<div className="flex items-center space-x-2">
											<code className="flex-1 text-xs bg-background-primary p-2 rounded border font-mono text-text-secondary">
												{ currentWebhookUrl ||
													`${ window.location.origin }/wp-json/sureforms/webhook` }
											</code>
											<Button
												onClick={ () => {
													const url =
														currentWebhookUrl ||
														`${ window.location.origin }/wp-json/sureforms/webhook`;
													navigator.clipboard.writeText(
														url
													);
													toast.success(
														__(
															'Webhook URL copied to clipboard',
															'sureforms'
														)
													);
												} }
												variant="outline"
												size="xs"
												className="text-text-tertiary hover:text-text-primary"
											>
												{ __( 'Copy', 'sureforms' ) }
											</Button>
										</div>
									</div>
									{ currentWebhookId &&
										currentWebhookId !== 'created' && (
										<div>
											<label className="block text-xs font-medium text-text-tertiary mb-1">
												{ __(
													'Webhook ID',
													'sureforms'
												) }
											</label>
											<div className="flex items-center space-x-2">
												<code className="flex-1 text-xs bg-background-primary p-2 rounded border font-mono text-text-secondary">
													{ currentWebhookId }
												</code>
												<Button
													onClick={ () => {
														navigator.clipboard.writeText(
															currentWebhookId
														);
														toast.success(
															__(
																'Webhook ID copied to clipboard',
																'sureforms'
															)
														);
													} }
													variant="outline"
													size="xs"
													className="text-text-tertiary hover:text-text-primary"
												>
													{ __(
														'Copy',
														'sureforms'
													) }
												</Button>
											</div>
										</div>
									) }
								</>
							);
						} )() }
					</div>
				</div>
			) }
		</div>
	);

	// Content for Payment Settings section
	const paymentSettingsContent = (
		<div className="space-y-6">
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
		<div className="space-y-6">
			<div className="space-y-4">
				<p className="text-text-tertiary">
					{ __(
						'Connect your Stripe account to start accepting payments through your forms.',
						'sureforms'
					) }
				</p>

				<Button
					onClick={ handleStripeConnect }
					disabled={ isConnecting || loading }
					variant="primary"
					size="sm"
				>
					{ isConnecting ? (
						__( 'Connecting…', 'sureforms' )
					) : (
						<>{ __( 'Connect to Stripe', 'sureforms' ) }</>
					) }
				</Button>
				<p className="text-text-primary">
					Securely connect to Stripe with just a few clicks to begin
					accepting payments!
					<a
						href="https://sureforms.com/docs/stripe-connect"
						target="_blank"
						rel="noopener noreferrer"
					>
						Learn More
					</a>
				</p>
				<AlertForFee />
			</div>
		</div>
	);

	return (
		<div className="space-y-6">
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
		</div>
	);
};

export default Payments;