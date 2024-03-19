import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';
const Integration = () => {
	return (
		<div className="srfm-modal-content">
			<div className="srfm-modal-inner-content">
				<div className="srfm-modal-inner-heading">
					<span className="srfm-modal-inner-heading-text">
						<h4>{ __( 'Integrations', 'sureforms' ) }</h4>
					</span>
				</div>
				<div className="srfm-modal-inner-box">
					<div className="srfm-modal-inner-box-text">
						<h5>{ __( 'All Integraions', 'sureforms' ) }</h5>
					</div>
					<div className="srfm-modal-separator"></div>
					<div className="srfm-modal-inner-box-content">
						<UpsellSureFormsPro></UpsellSureFormsPro>
					</div>
				</div>
				<UpsellSureTriggers></UpsellSureTriggers>
			</div>
		</div>
	);
};

const UpsellSureFormsPro = () => {
	const lockIcon = parse( svgIcons.lock );
	return (
		<div className="srfm-modal-info-box">
			<div>
				{ lockIcon }
			</div>
			<div className="srfm-modal-upsell-message">
				<div>
					<h6>{ __( 'This is a Pro Feature', 'sureforms' ) }</h6>
				</div>
				<div>
					<p>{ __( 'To use the integrations you need to upgrade to SureForms Pro', 'sureforms' ) }</p>
				</div>
				<div className="srfm-modal-cta">
					<button className="srfm-modal-primary-cta"	>
						{ __( 'Upgarade', 'sureforms' ) }
					</button>
					{ /* temporarily disable eslint */ }
					{ /* eslint-disable jsx-a11y/anchor-is-valid */ }
					<a
						href="#"
						className="srfm-modal-tertiary-cta">
						{ __( 'Learn More', 'sureforms' ) }
					</a>
					{ /* eslint-enable jsx-a11y/anchor-is-valid */ }
				</div>
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
				window.location = e.target.dataset.redirection;
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

	const getCTA = ( status ) => {
		if ( status === 'Activated' || status === 'Installed' ) {
			return __( 'Go to Dashboard', 'sureforms' );
		}
		return __( 'Install & Authenticate', 'sureforms' );
	};

	const plugin = srfm_admin?.integrations?.find( ( item ) => {
		return 'suretriggers' === item.slug;
	} );

	return (
		plugin && <div className="srfm-modal-inner-content">
			<div className="srfm-modal-inner-heading">
				<div className="srfm-modal-inner-heading-text">
					<h4>{ __( 'You Can also use our Free Integration platform', 'sureforms' ) }</h4>
				</div>
			</div>
			<div className="srfm-modal-inner-box">
				<div className="srfm-modal-inner-box-content">
					<div className="srfm-modal-upsell-message">
						<div>
							<img height="24px" src={ plugin.logo_full } alt="logo" />
						</div>
						<div>
							<p>{ plugin.description }</p>
						</div>
						<div className="srfm-modal-cta">
							<button
								className="srfm-modal-primary-cta"
								onClick={ handlePluginActionTrigger }
								data-slug={ plugin.slug }
								data-init={ plugin.path }
								data-redirection={ plugin.redirection }
								data-action={ getAction( plugin.status ) }
							>
								{ getCTA( plugin.status ) }
							</button>
							{ /* temporarily disable eslint */ }
							{ /* eslint-disable jsx-a11y/anchor-is-valid */ }
							<a
								href="#"
								className="srfm-modal-secondary-cta">
								{ __( 'Learn More', 'sureforms' ) }
							</a>
							{ /* eslint-enable jsx-a11y/anchor-is-valid */ }
						</div>
					</div>
				</div >
			</div >
		</div >
	);
};

export default Integration;
