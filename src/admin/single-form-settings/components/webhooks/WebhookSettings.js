import apiFetch from '@wordpress/api-fetch';
import IntegrationsBox from '../../../dashboard/templates/IntegrationsBox';

const WebhookSettings = () => {
	return (
		<div className="srfm-modal-content">
			<div className="srfm-modal-inner-content">
				<UpsellSureTriggers></UpsellSureTriggers>
				<UpsellSureFormsPro></UpsellSureFormsPro>
			</div>
		</div>
	);
};

const UpsellSureTriggers = () => {
	const handlePluginActionTrigger = ( e ) => {
		const action = e.target.dataset.action;
		const formData = new window.FormData();
		switch ( action ) {
			case 'sureforms_recommended_plugin_activate':
				activatePlugin( e );
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
				formData.append( 'slug', e.target.dataset.slug );

				e.target.innerText = srfm_admin.plugin_installing_text;

				apiFetch( {
					url: srfm_admin.ajax_url,
					method: 'POST',
					body: formData,
				} ).then( ( data ) => {
					if ( data.success ) {
						e.target.innerText = srfm_admin.plugin_installed_text;
						activatePlugin( e );
					}
				} );
				break;

			default:
				// Do nothing.
				break;
		}
	};
	const activatePlugin = ( e ) => {
		const formData = new window.FormData();
		formData.append( 'action', 'sureforms_recommended_plugin_activate' );
		formData.append( 'security', srfm_admin.sfPluginManagerNonce );
		formData.append( 'init', e.target.dataset.init );
		e.target.innerText = srfm_admin.plugin_activating_text;
		apiFetch( {
			url: srfm_admin.ajax_url,
			method: 'POST',
			body: formData,
		} ).then( ( data ) => {
			if ( data.success ) {
				e.target.style.color = '#16A34A';
				e.target.innerText = srfm_admin.plugin_activated_text;
				window.location = e.target.dataset.redirection;
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

	const plugin = srfm_admin?.integrations?.find( ( item ) => {
		return 'suretriggers' === item.slug;
	} );

	return (
		plugin &&
		<div className="srfm-modal-inner-box">
			<div className="srfm-modal-inner-box-content">
				<IntegrationsBox
					icon={ plugin.logo }
					title={ plugin.title }
					descriptions={ plugin.subtitle }
					statusText={
						'Installed' === plugin.status
							? srfm_admin.plugin_activate_text
							: plugin.status
					}
					statusColor={
						plugin.status === 'Installed' ||
							plugin.status === 'Activated'
							? '#16A34A'
							: '#046BD2'
					}
					dataSlug={ plugin.slug }
					init={ plugin.path }
					redirectURL={ plugin.redirection }
					action={ getAction( plugin.status ) }
					handlePluginActionTrigger={
						handlePluginActionTrigger
					}
				/>
			</div>
		</div>
	);
};

const UpsellSureFormsPro = () => {
	return (
		<div className="srfm-modal-inner-box">
			<div className="srfm-modal-inner-box-content">
				Sure Forms Pro
			</div>
		</div>
	);
};

export default WebhookSettings;
