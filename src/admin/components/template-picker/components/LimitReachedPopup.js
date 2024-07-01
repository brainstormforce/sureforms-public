import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import Header from './Header.js';
import ICONS from './icons.js';
import AiFormBuilder from './AiFormBuilder.js';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const LimitReachedPopup = ( { setShowLimitReachedPopup } ) => {
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

	return (
		<>
			<Header />
			<div className="srfm-popup-overlay" />
			<div className="srfm-limit-reached-popup">
				<div className="srfm-popup-header">
					<span className="srfm-popup-icon">{ ICONS.warning }</span>
					<span className="srfm-popup-title">
						{ __( 'Limit Reached', 'sureforms' ) }
					</span>
					<div
						className="srfm-ai-limit-reached-close"
						onClick={ () => setShowLimitReachedPopup( false ) }
					>
						{ ICONS.close }
					</div>
				</div>
				<div className="srfm-limit-reached-popup-content">
					<span className="srfm-limit-reached-popup-text">
						{ __(
							'You have reached the maximum number of form generations.',
							'sureforms'
						) }
					</span>
					<span>
						{ __(
							'Please connect your website with SureForms AI to create 20 more forms with AI.',
							'sureforms'
						) }
					</span>
				</div>
				<div className="srfm-limit-reached-popup-content">
					<Button
						className="srfm-limit-reached-more-credits-btn"
						onClick={ authorizeZipAI }
						disabled={ disableAuthButton }
					>
						{ __( 'Connect Now', 'sureforms' ) }
					</Button>
				</div>
			</div>
			<AiFormBuilder />
		</>
	);
};

export default LimitReachedPopup;
