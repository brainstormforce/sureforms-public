import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import ICONS from './icons';
import Breadcrumbs from './Breadcrumbs';
import { useLocation, Link } from 'react-router-dom';
import { BsLightningCharge } from 'react-icons/bs';
import RevokeConfirmationPopup from './RevokeConfirmationPopup.js';
import CreditDetailsPopup from './CreditDetailsPopup.js';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const Header = () => {
	const [ showRevokePopover, setShowRevokePopover ] = useState( false );
	const [ showRevokeConfirmation, setShowRevokeConfirmation ] =
		useState( false );

	const [ disableAuthButton, setDisableAuthButton ] = useState( false );
	const [ isAuthorized, setIsAuthorized ] = useState( false ); // eslint-disable-line no-unused-vars

	// Function: Authorize Zip AI.
	const authorizeZipAI = ( event ) => {
		event.preventDefault();

		setDisableAuthButton( true );

		// Get all the auth buttons and disable them.
		const authButtons = document.querySelectorAll(
			'.srfm-ai-features-authorization'
		);
		authButtons.forEach( ( authButton ) => {
			authButton.disabled = true;
		} );

		// Create the window positioning to be centered.
		const positioning = {
			left: ( window.screen.width - 480 ) / 2,
			top: ( window.screen.height - 720 ) / 2,
		};

		// Redirect to the Zip AI Authorization URL.
		const authWindow = window.open(
			srfm_admin.zip_ai_auth_middleware,
			'SureFormsAiFeaturesAuthorization',
			`width=480,height=720,top=${ positioning.top },left=${ positioning.left },scrollbars=0`
		);

		// Set a counter for timeout.
		let iterations = 0;

		// Set an interval to check if the option was updated.
		const authVerificationInterval = setInterval( () => {
			// Clear the interval if the window was closed, or automatically after 5 minutes.
			if ( authWindow.closed || 300 === iterations ) {
				// Close the auth window if it wasn't closed.
				if ( ! authWindow.closed ) {
					authWindow.close();
				}
				// Reset the texts and enable the button.
				clearInterval( authVerificationInterval );

				authButtons.forEach( ( authButton ) => {
					authButton.disabled = false;
				} );
			}

			// Create a new FormData object. Append the action and nonce.
			const formData = new FormData();
			formData.append( 'action', 'sureforms_zip_ai_verify_authenticity' );
			formData.append(
				'nonce',
				srfm_admin.zip_ai_verify_authenticity_nonce
			);

			// Call the getApiData function with the required parameters. fetch
			apiFetch( {
				url: ajaxurl,
				method: 'POST',
				body: formData,
			} ).then( ( response ) => {
				if ( response?.success && response?.data?.is_authorized ) {
					setIsAuthorized( true );
					authWindow.close();
					clearInterval( authVerificationInterval );
					window.location.reload();
				}
			} );
			iterations++;
		}, 500 );
	};

	function useQuery() {
		return new URLSearchParams( useLocation().search );
	}

	const query = useQuery();

	const page = query.get( 'page' );
	const method = query.get( 'method' );

	// if the methods is ai then hide the the scrollbar from body
	useEffect( () => {
		if ( method === 'template' ) {
			document.body.style.overflow = 'auto';
		} else {
			document.body.style.overflow = 'hidden';
		}
	}, [ method ] );

	const aiFormCreationCount = parseInt(
		srfm_admin?.zip_ai_form_creation_count
	);
	const totalFormCount = srfm_admin?.is_authorized ? 25 : 5;
	let formCreationleft;
	if ( aiFormCreationCount < totalFormCount ) {
		formCreationleft = totalFormCount - aiFormCreationCount;
	} else {
		formCreationleft = 0;
	}

	if ( showRevokeConfirmation ) {
		return (
			<RevokeConfirmationPopup
				setShowRevokeConfirmation={ setShowRevokeConfirmation }
			/>
		);
	}

	return (
		<div
			style={ {
				display: 'flex',
				flexDirection: 'column',
				gap: '0',
			} }
		>
			<div
				className="srfm-tp-header"
				style={ {
					zIndex: '9999999',
				} }
			>
				<div className="srfm-tp-header-items">
					{ /** Logo & Breadcrumbs */ }
					<div className="srfm-tp-main-title">
						<Breadcrumbs />
					</div>
				</div>

				{ /* if the user is authorized and the page is add-new-form and the method is ai then show the credits left in the account
				 */ }
				{ page === 'add-new-form' && method === 'ai' ? (
					<div className="srfm-tp-header-credits-ctn">
						<Button
							style={ {
								// if popover is open, change background color
								background: showRevokePopover
									? '#F3F4F6'
									: 'white',
							} }
							className="srfm-tp-header-credits"
							onClick={ () => {
								setShowRevokePopover( ! showRevokePopover );
							} }
						>
							<span className="srfm-tp-header-credits-left">
								{ wp.i18n.sprintf(
									/* translators: %s: number of AI form generations left */
									__(
										'%s AI form generations left',
										'sureforms'
									),
									formCreationleft
								) }
							</span>
							<div className="srfm-tp-header-bolt-icon">
								<BsLightningCharge />
							</div>
						</Button>
						{ showRevokePopover && (
							<CreditDetailsPopup
								setShowRevokePopover={ setShowRevokePopover }
								setShowRevokeConfirmation={
									setShowRevokeConfirmation
								}
							/>
						) }
						<div
							className="srfm-tp-header-close"
							onClick={ () => {
								window.location.href =
									'/wp-admin/admin.php?page=sureforms_menu';
							} }
						>
							<div style={ { height: '24px' } }>
								{ ICONS.close }
							</div>
						</div>
					</div>
				) : (
					<div
						className="srfm-tp-header-close"
						onClick={ () => {
							window.location.href =
								'/wp-admin/admin.php?page=sureforms_menu';
						} }
					>
						<div style={ { height: '24px' } }>{ ICONS.close }</div>
					</div>
				) }
			</div>
			{ ! srfm_admin.is_authorized && method === 'ai' && (
				<div
					className="srfm-tp-header"
					style={ {
						top: '71px',
						height: '52px',
						background: '#FDF1E2',
						padding: '16px 48px 16px 48px',
						justifyContent: 'center',
						zIndex: '100',
					} }
				>
					<p className="srfm-ai-sub-header">
						{ __(
							'Connect your website with SureForms AI to get 20 more AI form generations. ',
							'sureforms'
						) }
						<span>
							<Link
								onClick={ authorizeZipAI }
								style={ {
									boxShadow: 'none',
									border: 'none',
								} }
							>
								<span
									className="srfm-ai-sub-header-auth-url"
									disabled={ disableAuthButton }
								>
									{ __( 'Connect Now', 'sureforms' ) }
								</span>
							</Link>
						</span>
					</p>
				</div>
			) }
		</div>
	);
};

export default Header;
