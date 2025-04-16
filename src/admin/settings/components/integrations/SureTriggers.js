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
	const getPluginStatusText = ( plugin ) => {
		const statusTextMap = {
			Installed: plugin.plugin_activate_text,
			Install: __( 'Install & Activate', 'sureforms' ),
			Activated: plugin.plugin_activated_text,
		};

		return statusTextMap[ plugin.status ] || plugin.status;
	};

	const activatePlugin = useCallback( ( { plugin, event } ) => {
		const formData = new window.FormData();
		formData.append( 'action', ACTIONS.ACTIVATE );
		formData.append( 'security', srfm_admin.sfPluginManagerNonce );
		formData.append( 'init', plugin.path );
		formData.append( 'slug', plugin.slug );

		event.target.innerText = plugin.plugin_activating_text;

		performApiAction( {
			url: srfm_admin.ajax_url,
			formData,
			successCallback: () => {
				event.target.style.color = '#FFFFFF';
				event.target.innerText = plugin.plugin_activated_text;
				window.location = plugin.redirection;
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
				event.target.innerText = plugin.plugin_activate_text;
			},
		} );
	}, [] );

	const handlePluginActionTrigger = useCallback(
		( { plugin, event } ) => {
			const action = getAction( plugin.status );
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
				formData.append( 'slug', plugin.slug );

				event.target.innerText = plugin.plugin_installing_text;

				performApiAction( {
					url: srfm_admin.ajax_url,
					formData,
					successCallback: () => {
						event.target.innerText = plugin.plugin_installed_text;
						activatePlugin( { plugin, event } );
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
				activatePlugin( { plugin, event } );
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
								plugin,
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
