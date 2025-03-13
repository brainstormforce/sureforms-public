import { __ } from '@wordpress/i18n';
import { Button, Container, Label, Toaster, toast } from '@bsf/force-ui';
import apiFetch from '@wordpress/api-fetch';
import { useCallback } from '@wordpress/element';
import { cn } from '@Utils/Helpers';

export default () => {
	// Constants for action types
	const ACTIONS = {
		ACTIVATE: 'sureforms_recommended_plugin_activate',
		INSTALL: 'sureforms_recommended_plugin_install',
	};

	// Helper function for API requests
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
			Installed: srfm_admin.plugin_activate_text,
			Install: __( 'Install & Activate', 'sureforms' ),
			Activated: srfm_admin.plugin_activated_text,
		};

		return statusTextMap[ plugin.status ] || plugin.status;
	};

	const activatePlugin = useCallback( ( { plugin, event } ) => {
		const formData = new window.FormData();
		formData.append( 'action', ACTIONS.ACTIVATE );
		formData.append( 'security', srfm_admin.sfPluginManagerNonce );
		formData.append( 'init', plugin.path );

		event.target.innerText = srfm_admin.plugin_activating_text;

		performApiAction( {
			url: srfm_admin.ajax_url,
			formData,
			successCallback: () => {
				event.target.style.color = '#16A34A';
				event.target.innerText = srfm_admin.plugin_activated_text;
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
				event.target.innerText = srfm_admin.plugin_activate_text;
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

				event.target.innerText = srfm_admin.plugin_installing_text;

				performApiAction( {
					url: srfm_admin.ajax_url,
					formData,
					successCallback: () => {
						event.target.innerText =
							srfm_admin.plugin_installed_text;
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
		<Container
			className="bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-3 gap-2 shadow-sm-blur-1"
			containerType="flex"
			direction="column"
		>
			<Toaster
				position="bottom-right"
				design="stack"
				theme="light"
				autoDismiss={ true }
				dismissAfter={ 5000 }
			/>
			<Container.Item>
				<Label size="sm" className="flex font-semibold p-1 gap-2">
					{ __( 'Extend Your Website', 'sureforms' ) }
				</Label>
			</Container.Item>
			<Container.Item>
				<Container className="flex flex-wrap p-1 gap-1 bg-background-secondary rounded-lg p-1 gap-1">
					{ srfm_admin?.integrations?.map( ( plugin, index ) => (
						<Container.Item
							key={ index }
							className="flex gap-1 p-2 shadow-sm-blur-2 rounded-md bg-background-primary flex-1 min-w-[calc(50%-0.5rem)]"
						>
							<Container className="flex-1 flex flex-col justify-between">
								<Container.Item className="flex flex-col">
									<Container className="flex gap-1.5 p-1 items-center">
										<img
											className="w-5 h-5"
											src={ plugin.logo }
											alt={ plugin.title }
										/>
										<Label size="sm">
											{ plugin.title }
										</Label>
									</Container>
									<Label
										size="sm"
										variant="help"
										className="font-normal p-1 gap-0.5"
									>
										{ plugin.subtitle }
									</Label>
								</Container.Item>
								<Container.Item className="flex gap-0.5 p-1">
									<Button
										className={ cn(
											'w-fit focus:[box-shadow:none]',
											plugin.status === 'Activated' &&
												'bg-badge-background-green hover:bg-badge-background-green'
										) }
										variant="outline"
										onClick={ ( event ) =>
											handlePluginActionTrigger( {
												plugin,
												event,
											} )
										}
										size="xs"
									>
										{ getPluginStatusText( plugin ) }
									</Button>
								</Container.Item>
							</Container>
						</Container.Item>
					) ) }
				</Container>
			</Container.Item>
		</Container>
	);
};
