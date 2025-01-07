/** @jsx jsx */
import { css, jsx } from '@emotion/react';
import {
	ScCard,
	ScDashboardModule,
	ScStackedList,
	ScStackedListRow,
} from '@surecart/components-react';
import { __ } from '@wordpress/i18n';
import IntegrationsBox from './templates/IntegrationsBox';
import TrainingTextBox from './templates/TrainingTextBox';
import apiFetch from '@wordpress/api-fetch';

export default () => {
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
					} else {
						e.target.innerText = __( 'Install', 'sureforms' );
						alert( __( `Plugin Installation failed, Please try again later.`, 'sureforms' ) );
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
			} else {
				alert( __( 'Plugin activation failed, Please try again later.', 'sureforms' ) );
				e.target.innerText = srfm_admin.plugin_activate_text;
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

	return (
		<ScDashboardModule
			css={ css`
				width: 35%;
				margin-top: 2em;
				@media screen and ( max-width: 782px ) {
					width: 100%;
				}
				@media screen and ( max-width: 1260px ) {
					margin-top: 0;
					max-width: 450px;
				}
				.dashboard-module {
					display: none;
				}
			` }
			style={ { '--sc-flex-column-gap': '1em' } }
		>
			<ScCard noPadding>
				<ScStackedList>
					<ScStackedListRow
						style={ {
							'--columns': '1',
							overflow: 'hidden',
							'--sc-flex-column-gap': '1em',
						} }
						css={ css`
							padding: 8px;
						` }
					>
						<p
							css={ css`
								color: #0f172a;
								font-size: 18px;
								font-weight: 600;
								line-height: 28px;
								margin: 0;
							` }
						>
							{ __( 'Integrations', 'sureforms' ) }
						</p>
					</ScStackedListRow>
					{ srfm_admin?.integrations?.map( ( plugin, index ) => {
						return (
							<IntegrationsBox
								key={ index }
								icon={ plugin.logo }
								title={ plugin.title }
								descriptions={ plugin.subtitle }
								statusText={ ( () => {
									switch ( plugin.status ) {
										case 'Installed':
											return srfm_admin.plugin_activate_text;
										case 'Install':
											return __( 'Install', 'sureforms' );
										case 'Activated':
											return srfm_admin.plugin_activated_text;
										default:
											return plugin.status;
									}
								} )() }
								statusColor={
									plugin.status === 'Installed' ||
									plugin.status === 'Activated'
										? '#16A34A'
										: '#D54407'
								}
								dataSlug={ plugin.slug }
								init={ plugin.path }
								redirectURL={ plugin.redirection }
								action={ getAction( plugin.status ) }
								handlePluginActionTrigger={
									handlePluginActionTrigger
								}
							/>
						);
					} ) }
				</ScStackedList>
			</ScCard>
			{ /* Training & Help, Rate Us TrainingTextBoxes will be used in the future */ }
			{ /* <TrainingTextBox
				title={ __( 'Training & Help', 'sureforms' ) }
				description={ __(
					'Learn everything you need to know about the form builder with our comprehensive video based training and knowledge base.',
					'sureforms'
				) }
				urlText={ __( 'Visit our training center', 'sureforms' ) }
			/> */ }
			<TrainingTextBox
				title={ __( 'Rate Us', 'sureforms' ) }
				description={ __(
					'We love to hear from you, we would appreciate every single review.',
					'sureforms'
				) }
				urlText={ __( 'Submit a Review', 'sureforms' ) }
			/>
		</ScDashboardModule>
	);
};
