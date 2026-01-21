import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CircleCheck } from 'lucide-react';
import apiFetch from '@wordpress/api-fetch';
import { Button, Loader, Label, toast, Text, Badge } from '@bsf/force-ui';
import ContentSection from '@Admin/settings/components/ContentSection';
import ConfirmationDialog from '@Admin/components/ConfirmationDialog';
import { AlertForFee, WebhookAutoConnectManage } from '../../components/utils';
import parse from 'html-react-parser';

/**
 * Stripe-specific payment settings component
 * Handles Stripe Connect, webhooks, and Stripe-specific configuration
 * @param {Object} props - Stripe settings props object
 */
const StripeSettings = ( props ) => {
	const {
		paymentsSettings,
		setPaymentsSettings,
		updateGlobalSettings,
		loading,
	} = props;

	const [ isConnecting, setIsConnecting ] = useState( false );
	const [ isDisconnecting, setIsDisconnecting ] = useState( false );
	const [ isCreatingWebhook, setIsCreatingWebhook ] = useState( false );
	const [ webhookTestConnected, setWebhookTestConnected ] = useState( false );
	const [ webhookLiveConnected, setWebhookLiveConnected ] = useState( false );

	// State for confirmation dialog
	const [ confirmationDialog, setConfirmationDialog ] = useState( {
		open: false,
		title: '',
		description: '',
		confirmLabel: '',
		onConfirm: null,
		isLoading: false,
		destructive: true,
		enableVerification: false,
		verificationText: '',
	} );

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
					'?page=sureforms_form_settings&tab=payments-settings&subpage=payment-methods&gateway=stripe'
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
					'?page=sureforms_form_settingstab=payments-settings&subpage=payment-methods&gateway=stripe'
			);
		}
	}, [] );

	// Initialize webhook connection states from localized data
	useEffect( () => {
		const payments = window.srfm_admin?.payments;
		if ( payments ) {
			setWebhookTestConnected( payments.webhook_test_connected || false );
			setWebhookLiveConnected( payments.webhook_live_connected || false );
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
		setConfirmationDialog( {
			open: true,
			title: __( 'Disconnect Stripe Account', 'sureforms' ),
			description: __(
				'Are you sure you want to disconnect your Stripe account? This will stop all active payments, subscriptions, and form transactions connected to this account.',
				'sureforms'
			),
			confirmLabel: __( 'Disconnect', 'sureforms' ),
			onConfirm: confirmStripeDisconnect,
			isLoading: isDisconnecting,
			destructive: true,
			enableVerification: true,
			verificationText: 'confirm',
		} );
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

			// Update local state with nested stripe object
			const newSettings = {
				...paymentsSettings,
				stripe: {
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
					payment_mode: paymentsSettings.payment_mode || 'test',
				},
			};

			// Update global settings first to ensure parent component syncs
			if ( updateGlobalSettings ) {
				// Update each critical field in global settings
				Object.keys( newSettings.stripe ).forEach( ( key ) => {
					updateGlobalSettings(
						`stripe.${ key }`,
						newSettings.stripe[ key ],
						'payments-settings'
					);
				} );
			}

			// Update local state after global settings
			setPaymentsSettings( newSettings );

			// Reset webhook connection states
			setWebhookTestConnected( false );
			setWebhookLiveConnected( false );

			toast.success(
				__( 'Stripe account disconnected successfully.', 'sureforms' )
			);
			setConfirmationDialog( ( prev ) => ( { ...prev, open: false } ) );
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
				data: {
					mode: paymentsSettings.payment_mode, // Pass current mode (test/live)
				},
			} );

			if ( response.success ) {
				toast.success(
					response.message ||
						__( 'Webhook created successfully!', 'sureforms' )
				);

				// Get the current mode that was used for webhook creation
				const currentMode = paymentsSettings.payment_mode;

				// Get webhook details for the created mode
				const webhookDetails =
					response.webhook_details?.[ currentMode ] || {};

				// Only update if webhook was actually created (has valid secret and ID)
				if (
					webhookDetails.webhook_secret &&
					webhookDetails.webhook_id
				) {
					const updatedStripeSettings = {
						...paymentsSettings.stripe,
					};

					if ( currentMode === 'live' ) {
						updatedStripeSettings.webhook_live_secret =
							webhookDetails.webhook_secret;
						updatedStripeSettings.webhook_live_url =
							webhookDetails.webhook_url ||
							`${ window.location.origin }/wp-json/sureforms/webhook`;
						updatedStripeSettings.webhook_live_id =
							webhookDetails.webhook_id;
						setWebhookLiveConnected( true ); // Update live state
					} else {
						updatedStripeSettings.webhook_test_secret =
							webhookDetails.webhook_secret;
						updatedStripeSettings.webhook_test_url =
							webhookDetails.webhook_url ||
							`${ window.location.origin }/wp-json/sureforms/webhook`;
						updatedStripeSettings.webhook_test_id =
							webhookDetails.webhook_id;
						setWebhookTestConnected( true ); // Update test state
					}

					setPaymentsSettings( {
						...paymentsSettings,
						stripe: updatedStripeSettings,
					} );
				}
			} else {
				const errorMessage = response.message
					? parse( response.message )
					: __( 'Failed to create webhook.', 'sureforms' );
				toast.error( errorMessage );
			}
		} catch ( error ) {
			const errorMessage =
				error.message || __( 'Failed to create webhook.', 'sureforms' );
			toast.error( errorMessage );
		} finally {
			setIsCreatingWebhook( false );
		}
	};

	const isLiveMode = paymentsSettings.payment_mode === 'live';
	const isWebhookConnected = isLiveMode
		? webhookLiveConnected
		: webhookTestConnected;

	const accountDetails = (
		<div className="flex flex-col gap-2">
			<div className="flex items-end justify-between gap-2">
				<Label size="sm" variant="neutral">
					{ __( 'Connection Status', 'sureforms' ) }
				</Label>
				{ isLiveMode ? (
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
				) }
			</div>
			<div className="flex items-center justify-between p-4 rounded-lg border-0.5 border-border-subtle border-solid shadow-sm">
				<div className="flex items-center gap-2">
					<CircleCheck className="text-support-success" />
					<div>
						<Text
							size={ 16 }
							weight={ 500 }
							as="span"
							className="text-text-secondary"
						>
							{ paymentsSettings?.stripe?.account_name ||
								paymentsSettings?.stripe?.stripe_account_id }
						</Text>
					</div>
				</div>

				<Button
					onClick={ handleStripeDisconnect }
					disabled={ isDisconnecting }
					icon={ isDisconnecting && <Loader /> }
					iconPosition="left"
					variant="link"
					size="md"
					className="no-underline hover:no-underline"
				>
					{ isDisconnecting
						? __( 'Disconnecting…', 'sureforms' )
						: __( 'Disconnect', 'sureforms' ) }
				</Button>
			</div>
		</div>
	);

	// Construct Stripe webhook dashboard URL
	const getStripeWebhookDashboardUrl = () => {
		const accountId = paymentsSettings?.stripe?.stripe_account_id;
		if ( ! accountId ) {
			return '';
		}
		const modeSegment = isLiveMode ? '' : 'test/';
		return `https://dashboard.stripe.com/${ accountId }/${ modeSegment }workbench/webhooks`;
	};

	const webhookManagement = (
		<div className="flex flex-col gap-2">
			<Label size="sm" variant="neutral">
				{ __( 'Webhook', 'sureforms' ) }
			</Label>

			{ isWebhookConnected ? (
				<div className="flex items-center p-4 rounded-lg border-0.5 border-border-subtle border-solid shadow-sm gap-2">
					<CircleCheck className="text-support-success" />
					<div>
						<Text
							size={ 16 }
							weight={ 500 }
							as="span"
							className="text-text-secondary"
						>
							{ __(
								'Webhook successfully connected, all Stripe events are being tracked.',
								'sureforms'
							) }
						</Text>
					</div>
				</div>
			) : (
				<WebhookAutoConnectManage
					getStripeWebhookDashboardUrl={
						getStripeWebhookDashboardUrl
					}
					handleWebhookCreation={ handleWebhookCreation }
					isCreatingWebhook={ isCreatingWebhook }
					loading={ loading }
					stripeAccountId={
						paymentsSettings?.stripe?.stripe_account_id
					}
				/>
			) }
		</div>
	);

	// Content for Payment Settings section
	const paymentSettingsContent = (
		<div className="flex flex-col gap-6 px-2">
			{ /* Account Details */ }
			{ accountDetails }
			{ /* Webhook Management */ }
			{ webhookManagement }
			<AlertForFee />
		</div>
	);

	const stripeConnectContent = (
		<div className="flex flex-col gap-[24px] px-2">
			<div className="flex flex-col gap-[12px]">
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
			</div>
			<AlertForFee />
		</div>
	);

	return (
		<>
			{ paymentsSettings?.stripe?.stripe_connected ? (
				<ContentSection
					loading={ loading }
					title={ __( 'Stripe Settings', 'sureforms' ) }
					content={ paymentSettingsContent }
				/>
			) : (
				<ContentSection
					loading={ loading }
					title={ __( 'Connect to Stripe', 'sureforms' ) }
					content={ stripeConnectContent }
				/>
			) }

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
				requireConfirmation={ confirmationDialog.enableVerification }
				verificationText={ confirmationDialog.verificationText }
			/>
		</>
	);
};

export default StripeSettings;
