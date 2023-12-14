/** @jsx jsx */
import { css, jsx } from '@emotion/react';
import {
	ScBreadcrumb,
	ScBreadcrumbs,
	ScDrawer,
} from '@surecart/components-react';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Logo from '../dashboard/templates/Logo';

export default () => {
	const [ showNotifications, setShowNotifications ] = useState( false );
	const siteUrl = sureforms_admin.site_url;

	return (
		<>
			<div
				css={ css`
					position: sticky;
					background-color: rgba( 255, 255, 255, 0.75 );
					backdrop-filter: blur( 5px );
					top: 32px;
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
							` }
						>
							<div
								css={ css`
									display: flex;
									align-items: center;
								` }
							>
								<ScBreadcrumbs>
									<ScBreadcrumb>
										<Logo display="block" />
									</ScBreadcrumb>
									{ sureforms_admin?.breadcrumbs &&
										sureforms_admin.breadcrumbs.length >
											0 &&
										sureforms_admin.breadcrumbs.map(
											( breadcrumb, index ) => (
												<ScBreadcrumb
													key={ index }
													href={ breadcrumb.link }
												>
													{ breadcrumb.title }
												</ScBreadcrumb>
											)
										) }
									{ sureforms_admin.breadcrumbs[ 0 ].title &&
										sureforms_admin.breadcrumbs[ 0 ]
											.title === 'Forms' && (
										<a
											href={ `${ siteUrl }/wp-admin/admin.php?page=add-new-form` }
										>
											<button className="srfm-add-form-btn">
												{ __(
													'Add New',
													'sureforms'
												) }
											</button>
										</a>
									) }
								</ScBreadcrumbs>
							</div>
						</h1>
					</div>
					<div
						css={ css`
							display: flex;
							align-items: center;
							gap: 15px;
						` }
					>
						{ sureforms_admin.breadcrumbs[ 0 ].title &&
							sureforms_admin.breadcrumbs[ 0 ].title ===
								'Forms' && (
							<button className="srfm-import-btn">
								{ __( 'Import Form', 'sureforms' ) }
							</button>
						) }
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
							{ 'V:' + sureforms_admin?.plugin_version }
							<span
								css={ css`
									padding: 2px 5px 3px 6px;
									border-radius: 4px;
									border: 1px solid #e2e8f0;
									margin-left: 12px;
								` }
							>
								Core
							</span>
						</article>
						<div
							css={ css`
								width: 1px;
								background: #e2e8f0;
								height: 20px;
							` }
						></div>
						<span
							css={ css`
								display: flex;
								align-items: center;
							` }
						>
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="24"
								height="24"
								viewBox="0 0 24 24"
								fill="none"
							>
								<path
									d="M9.87891 7.51884C11.0505 6.49372 12.95 6.49372 14.1215 7.51884C15.2931 8.54397 15.2931 10.206 14.1215 11.2312C13.9176 11.4096 13.6917 11.5569 13.4513 11.6733C12.7056 12.0341 12.0002 12.6716 12.0002 13.5V14.25M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12ZM12 17.25H12.0075V17.2575H12V17.25Z"
									stroke="#9CA3AF"
									strokeWidth="1.5"
									strokeLinecap="round"
									strokeLinejoin="round"
								/>
							</svg>
						</span>

						<span
							onClick={ () =>
								setShowNotifications( ! showNotifications )
							}
							css={ css`
								display: flex;
								align-items: center;
								cursor: pointer;
							` }
						>
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="20"
								height="20"
								viewBox="0 0 20 20"
								fill="none"
							>
								<path
									d="M9.16667 4.90182V16.0335C9.16667 16.8434 8.51008 17.5 7.70015 17.5C7.08038 17.5 6.52752 17.1104 6.31907 16.5267L4.53039 11.4024M15 10.8333C16.3807 10.8333 17.5 9.71404 17.5 8.33333C17.5 6.95262 16.3807 5.83333 15 5.83333M4.53039 11.4024C3.33691 10.8951 2.5 9.71194 2.5 8.33333C2.5 6.49238 3.99238 5 5.83333 5H7.36007C10.7773 5 13.7141 3.97159 15 2.5L15 14.1667C13.7141 12.6951 10.7773 11.6667 7.36007 11.6667L5.83331 11.6667C5.37098 11.6667 4.93064 11.5725 4.53039 11.4024Z"
									stroke="#475569"
									strokeWidth="1.4"
									strokeLinecap="round"
									strokeLinejoin="round"
								/>
							</svg>
						</span>
					</div>
				</div>
			</div>
			{ /* side panel */ }
			<ScDrawer
				noHeader={ true }
				style={ { '--sc-drawer-size': '500px' } }
				open={ showNotifications }
			>
				<div>
					<span
						onClick={ () =>
							setShowNotifications( ! showNotifications )
						}
						css={ css`
							position: absolute;
							right: 15px;
							top: 15px;
							cursor: pointer;
						` }
						className="sc-drawer-close-button"
					>
						<svg
							xmlns="http://www.w3.org/2000/svg"
							width="24"
							height="24"
							viewBox="0 0 10 10"
							fill="none"
						>
							<g clipPath="url(#clip0_251_2118)">
								<path
									d="M7.91683 2.5875L7.32933 2L5.00016 4.32917L2.671 2L2.0835 2.5875L4.41266 4.91667L2.0835 7.24583L2.671 7.83333L5.00016 5.50417L7.32933 7.83333L7.91683 7.24583L5.58766 4.91667L7.91683 2.5875Z"
									fill="#555D66"
								/>
							</g>
							<defs>
								<clipPath id="clip0_251_2118">
									<rect width="10" height="10" fill="white" />
								</clipPath>
							</defs>
						</svg>
					</span>
					<article
						css={ css`
							font-size: 18px;
							font-weight: 500;
							line-height: 20px;
							color: #000;
							margin-bottom: 40px;
							padding: 20px;
							padding-bottom: 30px;
							border-bottom: 1px solid rgb( 229, 231, 235 );
						` }
					>
						{ __( 'Notifications', 'sureforms' ) }
					</article>
					<div
						css={ css`
							padding: 0 20px;
							border-bottom: 1px solid rgb( 229, 231, 235 );
						` }
					>
						<article
							css={ css`
								line-height: 20px;
								font-style: italic;
								margin-top: 20px;
							` }
						>
							{ __(
								'All caught up!! Check back for new notifications later.',
								'sureforms'
							) }
						</article>
						<article
							css={ css`
								font-size: 14px;
								font-weight: 500;
								line-height: 20px;
								color: rgb( 100, 116, 139 );
								padding-bottom: 30px;
							` }
						></article>
					</div>
				</div>
			</ScDrawer>
		</>
	);
};
