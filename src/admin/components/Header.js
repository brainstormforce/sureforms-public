/** @jsx jsx */
import { css, jsx } from '@emotion/react';
import {
	ScBreadcrumb,
	ScBreadcrumbs,
} from '@surecart/components-react';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Logo from '../dashboard/templates/Logo';
import useWhatsNewRSS from '../../lib/whats-new/useWhatsNewRSS';

export default () => {
	const [ showNotifications, setShowNotifications ] = useState( false );
	const [ isLicenseActive, setIsLicenseActive ] = useState( srfm_admin?.is_license_active || false );

	const currentPage = new URL( window.location.href ).searchParams.get( 'page' );

	const handleUnlicensedRedirection = () => {
		if ( currentPage === 'sureforms_form_settings' ) {
			// in future if the tab slug is not account-settings, will have to change this.
			document.querySelector( 'a[href*="tab=account-settings"].srfm-settings-sidebar-category' )?.click();
		} else {
			window.location.href = `${ srfm_admin.site_url }/wp-admin/admin.php?page=sureforms_form_settings&tab=account-settings`;
		}
	};

	useEffect( () => {
		window.addEventListener( 'srfm_license_status_updated', ( event ) => {
			if ( event?.detail ) {
				setIsLicenseActive( event.detail.is_license_active );
			}
		} );
	}, [] );

	useWhatsNewRSS( {
		rssFeedURL: 'https://sureforms.com/whats-new/feed/',
		selector: '#srfm_whats_new',
		flyout: {
			title: __( "What's New?", 'sureforms' ),
			className: 'srfm_whats_new_flyout',
			onOpen: () => {
				setShowNotifications( true );
			},
			onClose: () => {
				setShowNotifications( false );
			},
		},

	} );

	return (
		<>
			<div
				css={ css`
					position: sticky;
					background-color: rgba( 255, 255, 255, 0.75 );
					backdrop-filter: blur( 5px );
					top: ${ showNotifications &&
					'toplevel_page_sureforms_menu' !==
						srfm_admin.current_screen_id
						? '0'
						: '32px' };
					width: 100%;
					z-index: 4;
					@media screen and ( max-width: 782px ) {
						top: 46px;
					}
					@media screen and ( max-width: 460px ) {
						top: 0px;
					}
					border-bottom: 1px solid rgb( 229, 231, 235 );
				` }
			>
				<div
					css={ css`
						padding: 20px;
						display: flex;
						align-items: center;
						justify-content: space-between;
					` }
				>
					<div
						css={ css`
							display: flex;
							align-items: center;
							column-gap: 1em;
						` }
					>
						<h1
							css={ css`
								margin: 0;
								font-size: var( --sc-font-size-large );
								min-height: 24px;
							` }
						>
							<ScBreadcrumbs>
								<ScBreadcrumb>
									<Logo display="block" />
								</ScBreadcrumb>
								{ srfm_admin?.breadcrumbs &&
									srfm_admin.breadcrumbs.length > 0 &&
									srfm_admin.breadcrumbs.map(
										( breadcrumb, index ) => (
											<ScBreadcrumb
												key={ index }
												href={ breadcrumb.link }
											>
												{ breadcrumb.title }
											</ScBreadcrumb>
										)
									) }
							</ScBreadcrumbs>
						</h1>
					</div>
					<div
						css={ css`
							display: flex;
							align-items: center;
							gap: 15px;
						` }
					>
						<article
							css={ css`
								color: #94a3b8;
								font-size: 14px;
								font-weight: 400;
								line-height: 20px;
								display: flex;
								align-items: center;
							` }
						>
							{ ' ' }
							{ srfm_admin?.plugin_version }
							<span
								css={ css`
									padding: 2px 5px 3px 6px;
									border-radius: 4px;
									border: 1px solid #e2e8f0;
									margin-left: 12px;
									font-weight: 500;
									line-height: 11px;
									font-size: 11px;
								` }
							>
								{ __( 'Core', 'sureforms' ) }
							</span>
						</article>
						{ srfm_admin?.is_pro_active && (
							<>
								<div
									css={ css`
										width: 1px;
										background: #e2e8f0;
										height: 20px;
									` }
								></div>

								<article
									css={ css`
										color: #94a3b8;
										font-size: 14px;
										font-weight: 500;
										line-height: 20px;
										display: flex;
										align-items: center;
									` }
								>
									{ ' ' }
									{ srfm_admin?.pro_plugin_version }
									<span
										css={ css`
											background-color: #0F172A;
											color: #ffffff;
											padding: 2px 5px 3px 6px;
											border-radius: 4px;
											border: 1px solid #0F172A;
											margin-left: 12px;
											font-weight: 500;
											line-height: 11px;
											font-size: 11px;
										` }
									>
										{ srfm_admin?.pro_plugin_name.split( ' ' )[ 1 ] }
									</span>
								</article>
								<div
									css={ css`
										width: 1px;
										background: #e2e8f0;
										height: 20px;
									` }
								></div>
								<article
									css={ css`
										color: ${ isLicenseActive ? '#16A34A' : '#DC2626' };
										font-size: 14px;
										font-weight: 400;
										line-height: 20px;
										display: flex;
										align-items: center;
									` }
								>
									{ ' ' }
									{ isLicenseActive ? ( __( 'Licensed', 'sureforms' ) ) : (
										<>
											<span
												css={ css`
													cursor: pointer;
												` }
												onClick={ handleUnlicensedRedirection }>{ __( 'Unlicensed', 'sureforms' ) }</span>
										</>
									) }
								</article>
							</>
						) }
						<div
							css={ css`
								width: 1px;
								background: #e2e8f0;
								height: 20px;
							` }
						></div>
						<div id="srfm_whats_new"></div>
					</div>
				</div>
			</div>
		</>
	);
};
