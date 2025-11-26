import { __ } from '@wordpress/i18n';
import { Button, Container, Text, Title } from '@bsf/force-ui';
import {
	getPluginStatusText,
	handlePluginActionTrigger as externalHandlePluginActionTrigger,
} from '@Utils/Helpers';
import { Dot, Plus } from 'lucide-react';
import ottoKitImage from '@Image/ottokit-integration.svg';
import LoadingSkeleton from '@Admin/components/LoadingSkeleton';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';

const OttoKitPage = ( { loading, isFormSettings = false, setSelectedTab, pluginConnected, setPluginConnected } ) => {
	const features = [
		__(
			'Push entries to Slack, Mailchimp, Google Sheets, or hundreds of other apps.',
			'sureforms'
		),
		__(
			'Create automatic workflows for every time someone submits a form.',
			'sureforms'
		),
		__( 'Keep your tools updated, without lifting a finger.', 'sureforms' ),
	];
	const plugin = srfm_admin?.integrations?.sure_triggers;

	// Add state management for connection functionality (reused from integrations/index.js)
	const [ btnDisabled, setBtnDisabled ] = useState( false );
	const [ buttonText, setButtonText ] = useState( '' );
	const [ action, setAction ] = useState( '' );
	const [ CTA, setCTA ] = useState( '' );
	const [ loadingData, setLoadingData ] = useState( false );

	// Reuse the connection logic from integrations/index.js
	const integrateWithSureTriggers = () => {
		const formData = new window.FormData();
		formData.append( 'action', 'sureforms_integration' );
		formData.append( 'formId', srfm_admin.form_id );
		formData.append( 'security', srfm_admin.suretriggers_nonce );

		return apiFetch( {
			url: srfm_admin.ajax_url,
			method: 'POST',
			body: formData,
		} ).then( ( response ) => {
			if ( response.success ) {
				window.SureTriggersConfig = response.data.data;
				if ( setSelectedTab ) {
					setSelectedTab( 'suretriggers' );
				}
			} else {
				if ( response.data.code ) {
					if ( 'invalid_secret_key' === response.data.code ) {
						const windowDimension = { width: 800, height: 720 };
						const positioning = {
							left: ( screen.width - windowDimension.width ) / 2,
							top: ( screen.height - windowDimension.height ) / 2,
						};
						const sureTriggersAuthenticationWindow = window.open(
							plugin.connection_url,
							'',
							`width=${ windowDimension.width },height=${ windowDimension.height },top=${ positioning.top },left=${ positioning.left },scrollbars=0`
						);

						let iterations = 0;

						const suretriggersAuthInterval = setInterval( () => {
							setBtnDisabled( true );
							setButtonText( __( 'Connecting…', 'sureforms' ) );
							setCTA( __( 'Connecting…', 'sureforms' ) );
							apiFetch( {
								url: srfm_admin.ajax_url,
								method: 'POST',
								body: formData,
							} ).then( ( authResponse ) => {
								if ( authResponse.success ) {
									window.SureTriggersConfig =
										authResponse.data.data;
									sureTriggersAuthenticationWindow.close();
									clearInterval( suretriggersAuthInterval );
									setPluginConnected( true );
									if ( setSelectedTab ) {
										setSelectedTab( 'suretriggers' );
									}
									setButtonText(
										getButtonText( 'Activated', true )
									);
									setCTA( getCTA( 'Activated' ) );
								} else {
									iterations++;
								}
							} );

							if (
								iterations >= 240 ||
								sureTriggersAuthenticationWindow.closed
							) {
								if (
									! sureTriggersAuthenticationWindow.closed
								) {
									sureTriggersAuthenticationWindow.close();
								}
								clearInterval( suretriggersAuthInterval );
								setButtonText(
									getButtonText(
										'Activated',
										pluginConnected || plugin.connected
									)
								);
								setCTA( getCTA( 'Activated' ) );
								setBtnDisabled( false );
							}
						}, 500 );
					}
				}
				console.error( response.data.message );
			}
		} );
	};

	// Complete plugin lifecycle management from integrations/index.js
	const handlePluginActionTrigger = ( event ) => {
		// For global settings: always use external helper
		if ( ! isFormSettings ) {
			// if plugin is activated, go to its settings page
			if ( plugin.status === 'Activated' ) {
				window.location.href = plugin.connection_url;
				return;
			}

			externalHandlePluginActionTrigger( { plugin, event } );
			return;
		}

		// For form settings: handle full lifecycle using action-based logic like index.js
		switch ( action ) {
			case 'sureforms_recommended_plugin_activate':
				activatePlugin();
				break;

			case 'sureforms_recommended_plugin_install':
				installPlugin();
				break;

			default:
				// When action is empty (plugin activated), integrate with SureTriggers
				integrateWithSureTriggers();
				break;
		}
	};

	const installPlugin = () => {
		const formData = new window.FormData();
		formData.append( 'action', 'sureforms_recommended_plugin_install' );
		formData.append( '_ajax_nonce', srfm_admin.plugin_installer_nonce );
		formData.append( 'slug', plugin.slug );

		setCTA( srfm_admin.plugin_installing_text );
		setButtonText( srfm_admin.plugin_installing_text );

		apiFetch( {
			url: srfm_admin.ajax_url,
			method: 'POST',
			body: formData,
		} ).then( ( data ) => {
			if ( data.success ) {
				setAction( 'sureforms_recommended_plugin_activate' );
				setCTA( srfm_admin.plugin_installed_text );
				setButtonText( srfm_admin.plugin_installed_text );
				activatePlugin();
			} else {
				setAction( 'sureforms_recommended_plugin_install' );
				setCTA( __( 'Install', 'sureforms' ) );
				setButtonText( __( 'Install', 'sureforms' ) );
				alert(
					__(
						`Plugin Installation failed, Please try again later.`,
						'sureforms'
					)
				);
			}
		} );
	};

	const activatePlugin = () => {
		const formData = new window.FormData();
		formData.append( 'action', 'sureforms_recommended_plugin_activate' );
		formData.append( 'security', srfm_admin.sfPluginManagerNonce );
		formData.append( 'init', plugin.path );
		setCTA( srfm_admin.plugin_activating_text );
		setButtonText( srfm_admin.plugin_activating_text );
		apiFetch( {
			url: srfm_admin.ajax_url,
			method: 'POST',
			body: formData,
		} ).then( ( data ) => {
			if ( data.success ) {
				setCTA( srfm_admin.plugin_activated_text );
				setButtonText( srfm_admin.plugin_activated_text );
				setAction( '' );
				setTimeout( () => {
					setAction( 'sureforms_integrate_with_suretriggers' );
					setCTA( getCTA( 'Activated' ) );
					setButtonText(
						getButtonText(
							'Activated',
							pluginConnected || plugin.connected
						)
					);
					if ( pluginConnected ) {
						integrateWithSureTriggers();
					}
				}, 2000 );
			} else {
				alert(
					__(
						'Plugin activation failed, Please try again later.',
						'sureforms'
					)
				);
				setCTA( srfm_admin.plugin_activate_text );
				setButtonText( srfm_admin.plugin_activate_text );
			}
		} );
	};

	const getAction = ( status ) => {
		if ( status === 'Activated' ) {
			return '';
		} else if ( status === 'Installed' ) {
			return 'sureforms_recommended_plugin_activate';
		}
		return 'sureforms_recommended_plugin_install';
	};

	const getCTA = ( status ) => {
		if ( status === 'Activated' ) {
			if ( isFormSettings ) {
				if ( pluginConnected || plugin.connected ) {
					return __( 'Get Started', 'sureforms' );
				}
				return __( 'Connect with OttoKit', 'sureforms' );
			}
			return __( 'Go to OttoKit Settings', 'sureforms' );
		} else if ( status === 'Installed' ) {
			return __( 'Activate', 'sureforms' );
		}
		return __( 'Install & Activate', 'sureforms' );
	};

	// Optimized button text logic from integrations/index.js
	const getButtonText = ( status, connected = false ) => {
		if ( status === 'Activated' ) {
			if ( isFormSettings ) {
				return connected
					? __( 'Get Started', 'sureforms' )
					: __( 'Connect with OttoKit', 'sureforms' );
			}
			return __( 'Activated', 'sureforms' );
		}

		if ( status === 'Installed' ) {
			return __( 'Activate', 'sureforms' );
		}

		return __( 'Install & Activate', 'sureforms' );
	};

	// Initialize button text and states on component mount
	useEffect( () => {
		if ( null === pluginConnected ) {
			setPluginConnected( plugin.connected );
		}

		// NEW: If plugin is already connected, fetch actual data immediately
		if ( pluginConnected || plugin.connected ) {
			setLoadingData( true );
			integrateWithSureTriggers().finally( () =>
				setLoadingData( false )
			);
		}

		if ( ! action ) {
			setAction( getAction( plugin.status ) );
			setCTA( getCTA( plugin.status ) );
		} else if ( pluginConnected || plugin.connected ) {
			setCTA( getCTA( plugin.status ) );
		}

		setButtonText(
			getButtonText( plugin?.status, pluginConnected || plugin.connected )
		);
	}, [ plugin, pluginConnected, action ] );

	return (
		<>
			{ loading || loadingData ? (
				<div>
					<LoadingSkeleton count={ 6 } className="h-6 rounded-sm" />
				</div>
			) : (
				<Container className="flex bg-background-primary rounded-xl">
					<Container className="p-2 rounded-lg bg-background-secondary gap-2 w-full">
						<Container className="p-6 gap-6 rounded-md bg-background-primary w-full">
							<Container className="items-start">
								<img
									src={ ottoKitImage }
									alt={ __( 'OttoKit', 'sureforms' ) }
									className="w-[300px] h-300px]"
								/>
							</Container>
							<Container className="gap-8 items-start">
								<div className="space-y-2">
									<Title
										tag="h3"
										title={ __(
											'Automate What Happens After Someone Submits Your Form',
											'sureforms'
										) }
										size="md"
									/>
									<Text
										size={ 16 }
										weight={ 400 }
										color="secondary"
									>
										{ __(
											'Every time someone fills out a form, something needs to happen next: a Slack alert for your team, a lead added to your CRM, a follow-up email, a new row in Google Sheets…',
											'sureforms'
										) }
									</Text>
									<Text
										size={ 16 }
										weight={ 400 }
										color="secondary"
									>
										{ __(
											"Doing all that manually? That's where OttoKit comes in. With OttoKit, you can:",
											'sureforms'
										) }
									</Text>
									{ features.map( ( feature, index ) => (
										<Container
											key={ index }
											className="flex items-start gap-1.5"
										>
											<Dot className="text-icon-secondary" />
											<Text
												size={ 16 }
												weight={ 400 }
												color="secondary"
											>
												{ feature }
											</Text>
										</Container>
									) ) }
									<Text
										size={ 16 }
										weight={ 400 }
										color="secondary"
									>
										{ __(
											'OttoKit turns your forms into powerful workflows. Set it up once, and let automation do the rest.',
											'sureforms'
										) }
									</Text>
									<Container className="p-2 gap-3">
										<Button
											size="md"
											variant={ 'primary' }
											onClick={
												handlePluginActionTrigger
											}
											disabled={ btnDisabled }
											icon={
												plugin?.status === 'Install' ? (
													<Plus className="size-5" />
												) : null
											}
										>
											{ CTA ||
												buttonText ||
												getPluginStatusText( plugin ) }
										</Button>
									</Container>
								</div>
							</Container>
						</Container>
					</Container>
				</Container>
			) }
		</>
	);
};

export default OttoKitPage;
