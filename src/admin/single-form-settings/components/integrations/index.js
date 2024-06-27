import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import apiFetch from '@wordpress/api-fetch';
import { useEffect } from '@wordpress/element';
import './webhooks';
import IntegrationIcons from '@Image/integration-icons.js';

const Integrations = ( { setSelectedTab, action, setAction, CTA, setCTA } ) => {
	const cards = [
		{
			title: __( 'All Integrations', 'sureforms' ),
			component: <AllIntegrations setSelectedTab={ setSelectedTab } />,
		},
		{
			title: __( 'Integrations via SureTriggers', 'sureforms' ),
			component: <UpsellSureTriggers
				{ ...{
					setSelectedTab,
					action,
					setAction,
					CTA,
					setCTA,
				}
				}
			/>,
		},
	];
	return (
		<div className="srfm-modal-content">
			<div className="srfm-modal-inner-content">
				<div className="srfm-modal-inner-heading">
					<span className="srfm-modal-inner-heading-text">
						<h4>{ __( 'Integrations', 'sureforms' ) }</h4>
					</span>
				</div>
				{
					cards.map( ( cardItem, cardIndex ) => (
						<div key={ cardIndex } className="srfm-modal-inner-box">
							<div className="srfm-modal-inner-box-text">
								<h5>{ cardItem.title }</h5>
							</div>
							<div className="srfm-modal-separator" />
							<div className="srfm-modal-inner-box-content">
								{ cardItem.component }
							</div>
						</div>
					) )
				}
			</div>
		</div>
	);
};

const AllIntegrations = ( { setSelectedTab } ) => {
	const integrationCards = applyFilters(
		'srfm.formSettings.integrations.cards',
		[],
		setSelectedTab
	);
	if ( 0 === integrationCards.length ) {
		return <EnableIntegrations />;
	}
	return (
		<>
			{
				integrationCards.map( ( card ) => ( card.component ) )
			}
		</>
	);
};

const EnableIntegrations = () => {
	return (
		<>
			<div className="srfm-modal-card-content">
				<div className="srfm-modal-card-content-inner">
					<div>
						<div className="srfm-modal-card-title">
							{ __( 'No Integrations Enabled', 'sureforms' ) }
						</div>
						<p className="srfm-modal-card-description">
							{ __( 'Please enable Integrations from Global Settings.', 'sureforms' ) }
						</p>
					</div>
				</div>
				<div className="srfm-buttons">
					<button
						className="srfm-button-primary"
						onClick={ () => {
							window.open( 'admin.php?page=sureforms_form_settings&tab=integration-settings' );
						} }

					>
						{
							__( 'Enable from Settings', 'sureforms' )
						}
					</button>
				</div>

			</div>
		</>
	);
};

const UpsellSureTriggers = ( { setSelectedTab, action, setAction, CTA, setCTA } ) => {
	const plugin = srfm_admin?.integrations?.find( ( item ) => {
		return 'suretriggers' === item.slug;
	} );

	const handlePluginActionTrigger = () => {
		const formData = new window.FormData();
		switch ( action ) {
			case 'sureforms_recommended_plugin_activate':
				activatePlugin();
				break;

			case 'sureforms_recommended_plugin_install':
				formData.append(
					'action',
					'sureforms_recommended_plugin_install'
				);
				formData.append(
					'_ajax_nonce',
					srfm_admin.plugin_installer_nonce
				);
				formData.append( 'slug', plugin.slug );

				setCTA( srfm_admin.plugin_installing_text );

				apiFetch( {
					url: srfm_admin.ajax_url,
					method: 'POST',
					body: formData,
				} ).then( ( data ) => {
					if ( data.success ) {
						setAction( 'sureforms_recommended_plugin_activate' );
						setCTA( srfm_admin.plugin_installed_text );
						activatePlugin();
					}
				} );
				break;

			default:
				integrateWithSureTriggers();
				break;
		}
	};

	const integrateWithSureTriggers = () => {
		const formData = new window.FormData();
		formData.append( 'action', 'sureforms_integration' );
		formData.append( 'formId', srfm_admin.form_id );
		formData.append( 'security', srfm_admin.suretriggers_nonce );

		apiFetch( {
			url: srfm_admin.ajax_url,
			method: 'POST',
			body: formData,
		} ).then( ( data ) => {
			if ( data.success ) {
				window.SureTriggersConfig = data.data.data;
				setSelectedTab( 'suretriggers' );
			} else {
				console.error( data.data.message );
			}
		} );
	};

	const activatePlugin = () => {
		const formData = new window.FormData();
		formData.append( 'action', 'sureforms_recommended_plugin_activate' );
		formData.append( 'security', srfm_admin.sfPluginManagerNonce );
		formData.append( 'init', plugin.path );
		setCTA( srfm_admin.plugin_activating_text );
		apiFetch( {
			url: srfm_admin.ajax_url,
			method: 'POST',
			body: formData,
		} ).then( ( data ) => {
			if ( data.success ) {
				setCTA( srfm_admin.plugin_activated_text );
				setAction( '' );
				setTimeout( () => {
					setAction( 'sureforms_integrate_with_suretriggers' );
					setCTA( getCTA( 'Activated' ) );
					integrateWithSureTriggers();
				}, 2000 );
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
			return __( 'Integrate with SureTriggers', 'sureforms' );
		} else if ( status === 'Installed' ) {
			return __( 'Activate', 'sureforms' );
		}
		return __( 'Install & Activate', 'sureforms' );
	};

	useEffect( () => {
		if ( ! action ) {
			setAction( getAction( plugin.status ) );
			setCTA( getCTA( plugin.status ) );
		}
	}, [] );

	useEffect( () => {
		console.log( action );
	}, [ action ] );

	return (
		plugin && (
			<div className="srfm-modal-upsell-message">
				<div className="srfm-modal-upsell-message-content">
					<img height="24px" src={ plugin.logo_full } alt="logo" />
					<p>
						{ __(
							'SureTriggers lets you connect your forms to over 600+ apps. With this integration you can automatically send form entries to your CRM, add subscribers to you email marketing platform, etc. Whatever you want SureForms and SureTriggers has you covered.',
							'sureforms'
						) }
					</p>
					<div className="srfm-buttons">
						{
							<button
								className="srfm-button-primary"
								onClick={ handlePluginActionTrigger }
							>
								{ CTA }
							</button>
						}
					</div>
				</div>
				<div>
					<IntegrationIcons />
				</div>
			</div>
		)
	);
};

export default Integrations;
