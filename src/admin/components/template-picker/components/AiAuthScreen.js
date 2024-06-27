import { __ } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import aiAuthPlaceholder from '@Image/ai-auth.svg';
import { MdArrowForward, MdKeyboardBackspace } from 'react-icons/md';
import { Link } from 'react-router-dom';
import Header from './Header';

const AiAuthScreen = () => {
	const [ disableAuthButton, setDisableAuthButton ] = useState( false );
	const [ isAuthorized, setIsAuthorized ] = useState( false );

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
			<div className="srfm-ts-main-container srfm-content-section">
				{ isAuthorized ? (
					<Spinner className="srfm-ts-loader" />
				) : (
					<div className="srfm-ai-auth-ctn">
						<div className="srfm-ai-auth-ctn-inner">
							<div className="srfm-ai-auth-txt-ctn">
								<div className="srfm-ai-auth-header">
									<h1 className="srfm-ai-auth-title">
										{ __(
											'Building Forms with AI',
											'sureforms'
										) }
									</h1>
									<p className="srfm-ai-auth-title-description">
										{ __(
											' SureForms, along with ZipWP offers AI form building capabilities to help you build your forms 10x faster.',
											'sureforms'
										) }
									</p>
								</div>
								<p className="srfm-ai-auth-sub-title">
									{ __(
										'Here is how the AI Form Builder Works:',
										'sureforms'
									) }
								</p>
								<ol className="srfm-ai-auth-list">
									<li>
										{ __(
											'Create a free account on ZipWP to connect with our AI.',
											'sureforms'
										) }
									</li>
									<li>
										{ __(
											'Describe the form you want to create in words.',
											'sureforms'
										) }
									</li>
									<li>
										{ __(
											'Watch as our AI crafts your form instantly.',
											'sureforms'
										) }
									</li>
									<li>
										{ __(
											'Refine the form with our easy drag & drop builder.',
											'sureforms'
										) }
									</li>
									<li>{ __( 'Launch.', 'sureforms' ) }</li>
								</ol>
								<Button
									onClick={ authorizeZipAI }
									className="srfm-ai-auth-btn"
									disabled={ disableAuthButton }
								>
									<span className="srfm-ai-auth-btn-txt">
										{ __(
											`Let's Get Started. It's Free`,
											'sureforms'
										) }
									</span>
									<MdArrowForward color="white" size={ 20 } />
								</Button>
								<Link
									to={ {
										location: `${ srfm_admin.site_url }/wp-admin/admin.php`,
										search: `?page=add-new-form`,
									} }
									className="srfm-ai-auth-back-btn"
								>
									<MdKeyboardBackspace size={ 20 } />
									<span className="srfm-ai-auth-back-btn-txt">
										{ __( 'Back', 'sureforms' ) }
									</span>
								</Link>
							</div>
							<div>
								<img src={ aiAuthPlaceholder } alt="AI Auth" />
							</div>
						</div>
					</div>
				) }
			</div>
		</>
	);
};

export default AiAuthScreen;
