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

const OttoKitPage = ( { loading, isFormSettings = false, setSelectedTab } ) => {
	const features = [
		__(
			'Instantly send form entries to Slack, Mailchimp, or other apps',
			'sureforms'
		),
		__(
			'Set up alerts, notifications, or actions based on form submissions',
			'sureforms'
		),
		__(
			'Automate tasks like follow-ups, lead assignments, or data sync',
			'sureforms'
		),
	];
	const plugin = srfm_admin?.integrations?.sure_triggers;

	// Add state management for connection functionality (reused from integrations/index.js)
	const [ btnDisabled, setBtnDisabled ] = useState( false );
	const [ buttonText, setButtonText ] = useState( '' );
	const [ pluginConnected, setPluginConnected ] = useState( null );
	const [ action, setAction ] = useState( '' );
	const [ CTA, setCTA ] = useState( '' );

	// Reuse the connection logic from integrations/index.js
	const integrateWithSureTriggers = () => {
		const formData = new window.FormData();
		formData.append( 'action', 'sureforms_integration' );
		formData.append( 'formId', srfm_admin.form_id );
		formData.append( 'security', srfm_admin.suretriggers_nonce );

		apiFetch( {
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
						const windowDimension = {
							width: 800,
							height: 720,
						};
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
			return __( 'Activated', 'sureforms' );
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
			{ loading ? (
				<div>
					<LoadingSkeleton count={ 6 } className="h-6 rounded-sm" />
				</div>
			) : (
				<Container className="flex bg-background-primary rounded-xl">
					<Container className="p-2 rounded-lg bg-background-secondary gap-2 w-full">
						<Container className="p-6 gap-6 rounded-md bg-background-primary w-full">
							<Container>
								<img
									src={ ottoKitImage }
									alt={ __( 'OttoKit', 'sureforms' ) }
									className="w-[280px] h-[280px]"
								/>
							</Container>
							<Container className="gap-8">
								<div className="space-y-2">
									<Title
										tag="h3"
										title={ __(
											'Setup Integration via OttoKit',
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
											'OttoKit connects with SureForms to help you send form data to your favorite tools and trigger automated workflows, no code needed.',
											'sureforms'
										) }
									</Text>
									{ features.map( ( feature, index ) => (
										<Container
											key={ index }
											className="flex items-center gap-1.5"
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
									<Container className="p-2 gap-3">
										<Button
											size="md"
											className={
												! isFormSettings &&
												plugin?.status === 'Activated'
													? 'bg-badge-background-green hover:bg-badge-background-green'
													: ''
											}
											variant={
												! isFormSettings &&
												plugin?.status === 'Activated'
													? 'outline'
													: 'primary'
											}
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
