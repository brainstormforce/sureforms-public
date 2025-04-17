import IntegrationCard from './Card';
import SureTriggersIcon from '@Image/suretriggers.svg';
import { __ } from '@wordpress/i18n';
import { Badge, Button, toast } from '@bsf/force-ui';
import { useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const SureTriggers = () => {
	const plugin = srfm_admin?.integrations;

	// Constants for action types
	const ACTIONS = {
		ACTIVATE: 'sureforms_recommended_plugin_activate',
		INSTALL: 'sureforms_recommended_plugin_install',
	};

	// // Helper function for API requests
	const performApiAction = async ( {
		url,
		formData,
		successCallback,
		errorCallback,
	} ) => {
		try {
			const response = await apiFetch( {
				url,
				method: 'POST',
				body: formData,
			} );

			if ( response.success ) {
				successCallback( response );
			} else {
				errorCallback( response );
			}
		} catch ( error ) {
			console.error( 'API Error:', error );
			toast.error(
				__( 'An error occurred. Please try again later.', 'sureforms' ),
				{
					duration: 5000,
				}
			);
		}
	};

	// Get action type based on plugin status
	const getAction = ( status ) => {
		return status === 'Installed'
			? ACTIONS.ACTIVATE
			: status === 'Activated'
				? ''
				: ACTIONS.INSTALL;
	};

	// Get plugin button text
	const getPluginStatusText = ( plugin_data ) => {
		const statusTextMap = {
			Installed: plugin_data.plugin_activate_text,
			Install: __( 'Install & Activate', 'sureforms' ),
			Activated: plugin_data.plugin_activated_text,
		};

		return statusTextMap[ plugin_data.status ] || plugin_data.status;
	};

	const activatePlugin = useCallback( ( { plugin_data, event } ) => {
		const formData = new window.FormData();
		formData.append( 'action', ACTIONS.ACTIVATE );
		formData.append( 'security', srfm_admin.sfPluginManagerNonce );
		formData.append( 'init', plugin_data.path );
		formData.append( 'slug', plugin_data.slug );

		event.target.innerText = plugin_data.plugin_activating_text;

		performApiAction( {
			url: srfm_admin.ajax_url,
			formData,
			successCallback: () => {
				event.target.style.color = '#FFFFFF';
				event.target.innerText = plugin_data.plugin_activated_text;
				window.location = plugin_data.redirection;
			},
			errorCallback: () => {
				toast.error(
					__(
						'Plugin activation failed, Please try again later.',
						'sureforms'
					),
					{
						duration: 5000,
					}
				);
				event.target.innerText = plugin_data.plugin_activate_text;
			},
		} );
	}, [] );

	const handlePluginActionTrigger = useCallback(
		( { plugin_data, event } ) => {
			const action = getAction( plugin_data.status );
			if ( ! action ) {
				return;
			}

			const formData = new window.FormData();

			if ( action === ACTIONS.INSTALL ) {
				formData.append( 'action', ACTIONS.INSTALL );
				formData.append(
					'_ajax_nonce',
					srfm_admin.plugin_installer_nonce
				);
				formData.append( 'slug', plugin_data.slug );

				event.target.innerText = plugin_data.plugin_installing_text;

				performApiAction( {
					url: srfm_admin.ajax_url,
					formData,
					successCallback: () => {
						event.target.innerText = plugin_data.plugin_installed_text;
						activatePlugin( { plugin_data, event } );
					},
					errorCallback: () => {
						event.target.innerText = __( 'Install', 'sureforms' );
						alert(
							__(
								'Plugin Installation failed, Please try again later.',
								'sureforms'
							)
						);
					},
				} );
			} else if ( action === ACTIONS.ACTIVATE ) {
				activatePlugin( { plugin_data, event } );
			}
		},
		[ activatePlugin ]
	);

	return (
		<IntegrationCard>
			<IntegrationCard.Header>
				<div className="inline-grid place-items-center">
					<img
						src={ SureTriggersIcon }
						className="size-6"
						alt={ __( 'OttoKit', 'sureforms' ) }
					/>
				</div>
				<div>
					<Badge
						label={ __( 'Free', 'sureforms' ) }
						variant="green"
						disableHover
					/>
				</div>
			</IntegrationCard.Header>
			<IntegrationCard.Content>
				<IntegrationCard.Title title={ __( 'OttoKit', 'sureforms' ) } />
				<IntegrationCard.Description
					description={ __(
						'Effortlessly connect your forms to hundreds of apps, automating tasks like sending entries to your favourite CRM.',
						'sureforms'
					) }
				/>
				<IntegrationCard.CTA>
					<Button
						size="xs"
						onClick={ ( event ) =>
							handlePluginActionTrigger( {
								plugin_data: plugin,
								event,
							} )
						}
					>
						{ getPluginStatusText( plugin ) }
					</Button>
				</IntegrationCard.CTA>
			</IntegrationCard.Content>
		</IntegrationCard>
	);
};

export default SureTriggers;
