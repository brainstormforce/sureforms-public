import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CreditCard, ExternalLink, X } from 'lucide-react';
import apiFetch from '@wordpress/api-fetch';
import { Button, Loader, Select, Switch, toast } from '@bsf/force-ui';
import ContentSection from '@Admin/settings/components/ContentSection';

const Payments = ( { loading, paymentsSettings: initialSettings, updateGlobalSettings } ) => {
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
			toast.success( __( 'Successfully connected to Stripe!', 'sureforms' ) );
			// Clean URL
			window.history.replaceState( {}, '', window.location.pathname + '?page=sureforms_form_settings&tab=payments-settings' );
		}
		
		if ( urlParams.get( 'error' ) ) {
			const errorMessage = decodeURIComponent( urlParams.get( 'error' ) );
			toast.error( errorMessage );
			// Clean URL
			window.history.replaceState( {}, '', window.location.pathname + '?page=sureforms_form_settings&tab=payments-settings' );
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
		if ( ! confirm( __( 'Are you sure you want to disconnect your Stripe account?', 'sureforms' ) ) ) {
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

			toast.success( __( 'Stripe account disconnected successfully.', 'sureforms' ) );
		} catch ( error ) {
			toast.error( __( 'Failed to disconnect Stripe account.', 'sureforms' ) );
		} finally {
			setIsDisconnecting( false );
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

	// Get available currencies as array for Select component
	const currencies = [
		{ value: 'USD', label: __( 'USD - US Dollar', 'sureforms' ) },
		{ value: 'EUR', label: __( 'EUR - Euro', 'sureforms' ) },
		{ value: 'GBP', label: __( 'GBP - British Pound', 'sureforms' ) },
		{ value: 'JPY', label: __( 'JPY - Japanese Yen', 'sureforms' ) },
		{ value: 'AUD', label: __( 'AUD - Australian Dollar', 'sureforms' ) },
		{ value: 'CAD', label: __( 'CAD - Canadian Dollar', 'sureforms' ) },
		{ value: 'CHF', label: __( 'CHF - Swiss Franc', 'sureforms' ) },
		{ value: 'CNY', label: __( 'CNY - Chinese Yuan', 'sureforms' ) },
		{ value: 'SEK', label: __( 'SEK - Swedish Krona', 'sureforms' ) },
		{ value: 'NZD', label: __( 'NZD - New Zealand Dollar', 'sureforms' ) },
		{ value: 'MXN', label: __( 'MXN - Mexican Peso', 'sureforms' ) },
		{ value: 'SGD', label: __( 'SGD - Singapore Dollar', 'sureforms' ) },
		{ value: 'HKD', label: __( 'HKD - Hong Kong Dollar', 'sureforms' ) },
		{ value: 'NOK', label: __( 'NOK - Norwegian Krone', 'sureforms' ) },
		{ value: 'KRW', label: __( 'KRW - South Korean Won', 'sureforms' ) },
		{ value: 'TRY', label: __( 'TRY - Turkish Lira', 'sureforms' ) },
		{ value: 'RUB', label: __( 'RUB - Russian Ruble', 'sureforms' ) },
		{ value: 'INR', label: __( 'INR - Indian Rupee', 'sureforms' ) },
		{ value: 'BRL', label: __( 'BRL - Brazilian Real', 'sureforms' ) },
		{ value: 'ZAR', label: __( 'ZAR - South African Rand', 'sureforms' ) },
		{ value: 'AED', label: __( 'AED - UAE Dirham', 'sureforms' ) },
		{ value: 'PHP', label: __( 'PHP - Philippine Peso', 'sureforms' ) },
		{ value: 'IDR', label: __( 'IDR - Indonesian Rupiah', 'sureforms' ) },
		{ value: 'MYR', label: __( 'MYR - Malaysian Ringgit', 'sureforms' ) },
		{ value: 'THB', label: __( 'THB - Thai Baht', 'sureforms' ) },
	];

	// Get current currency label
	const getCurrentCurrencyLabel = () => {
		const currency = currencies.find( c => c.value === paymentsSettings.currency );
		return currency ? currency.label : currencies[0].label;
	};

	// Content for Stripe Connect section
	const StripeConnectContent = () => (
		<div className="space-y-6">
			{ ! paymentsSettings.stripe_connected ? (
				<div className="space-y-4">
					<p className="text-text-tertiary">
						{ __( 'Connect your Stripe account to start accepting payments through your forms.', 'sureforms' ) }
					</p>
					
					<Button
						onClick={ handleStripeConnect }
						disabled={ isConnecting || loading }
						icon={ isConnecting && <Loader /> }
						iconPosition="left"
						variant="primary"
						size="md"
						className="bg-[#635bff] hover:bg-[#5043d7] text-white border-[#635bff] hover:border-[#5043d7]"
					>
						{ isConnecting ? (
							__( 'Connecting...', 'sureforms' )
						) : (
							<>
								<ExternalLink className="w-4 h-4 mr-2" />
								{ __( 'Connect with Stripe', 'sureforms' ) }
							</>
						) }
					</Button>
				</div>
			) : (
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
							__( 'Disconnecting...', 'sureforms' )
						) : (
							<>
								<X className="w-3.5 h-3.5 mr-1.5" />
								{ __( 'Disconnect', 'sureforms' ) }
							</>
						) }
					</Button>
				</div>
			) }
		</div>
	);

	// Content for Payment Settings section
	const PaymentSettingsContent = () => (
		<div className="space-y-6">
			{/* Currency Selection */}
			<div>
				<Select
					value={ paymentsSettings.currency }
					onChange={ ( value ) => handleSettingChange( 'currency', value ) }
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
								<Select.Option key={ currency.value } value={ currency.value }>
									{ currency.label }
								</Select.Option>
							) ) }
						</Select.Options>
					</Select.Portal>
				</Select>
				<p className="mt-1 text-sm text-text-tertiary">
					{ __( 'Select the default currency for payment forms.', 'sureforms' ) }
				</p>
			</div>

			{/* Payment Mode */}
			<div>
				<Switch
					label={ {
						heading: __( 'Live Mode', 'sureforms' ),
						description: __( 'Toggle between test and live payment processing. Use test mode to test payments without processing real transactions.', 'sureforms' )
					} }
					value={ paymentsSettings.payment_mode === 'live' }
					onChange={ ( value ) => handleSettingChange( 'payment_mode', value ? 'live' : 'test' ) }
				/>
			</div>
		</div>
	);

	return (
		<div className="space-y-6">
			{/* Stripe Connect Section */}
			<ContentSection
				loading={ loading }
				title={ __( 'Stripe Connect', 'sureforms' ) }
				content={ <StripeConnectContent /> }
			/>

			{/* Payment Settings Section - Only show if connected */}
			{ paymentsSettings.stripe_connected && (
				<ContentSection
					loading={ loading }
					title={ __( 'Payment Settings', 'sureforms' ) }
					content={ <PaymentSettingsContent /> }
				/>
			) }
		</div>
	);
};

export default Payments;