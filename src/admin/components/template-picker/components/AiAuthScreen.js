import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import aiAuthPlaceholder from '@Image/ai-auth.svg';
import { MdArrowForward, MdKeyboardBackspace } from 'react-icons/md';
import { Link } from 'react-router-dom';
import Header from './Header';

const TemplateScreen = () => {
	const [ buttonLabel, setButtonLabel ] = useState(
		__( "Let's Get Started. It's Free", 'sureforms' )
	);
	const [ showErr, setShowErr ] = useState( false );

	// Function: Authorize Zip AI.
	const authorizeZipAI = ( event ) => {
		event.preventDefault();
		// window.location.assign( uag_react.zip_ai_auth_middleware );

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

		// Create an object with the security property.
		const data = {
			// security: srfm_admin.zip_ai_verify_authenticity_nonce,
			action: 'sureforms_zip_ai_verify_authenticity',
		};

		// Set a counter for timeout.
		let iterations = 0;

		// Update the texts.
		setButtonLabel( __( "Let's Get Started. It's Free", 'sureforms' ) );

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
				setButtonLabel(
					__( "Let's Get Started. It's Free", 'sureforms' )
				);
				authButtons.forEach( ( authButton ) => {
					authButton.disabled = false;
				} );
			}

			// Call the getApiData function with the required parameters. ajax
			// const getApiFetchData = apiFetch( {
			// 	url: srfm_admin.ajax_url,
			// 	method: 'POST',
			// 	data,
			// } );

			// // Make the AJAX request to check if the option was updated.
			// getApiFetchData

			apiFetch( {
				url:
					srfm_admin.ajax_url +
					'?action=sureforms_zip_ai_verify_authenticity',
				method: 'POST',
				// body: data,
			} ).then( ( response ) => {
				if ( response?.success && response?.data?.is_authorized ) {
					authWindow.close();
					// localStorage.setItem( 'zipAiAuthorizationStatus', true );
					clearInterval( authVerificationInterval );
					window.location.reload();
					setButtonLabel(
						__( "Let's Get Started. It's Free", 'sureforms' )
					);
				}
			} );
			iterations++;
		}, 500 );
	};

	return (
		<>
			<Header />
			<div className="srfm-ts-main-container srfm-content-section">
				<div
					style={ {
						display: 'flex',
						flexWrap: 'wrap',
						alignItems: 'center',
						justifyContent: 'center',
						padding: '50px 1.25em',
						position: 'absolute',
						top: '50%',
						left: '50%',
						transform: 'translate(-50%, -50%)',
						width: '100%',
						height: '100%',
					} }
				>
					<div
						style={ {
							display: 'flex',
							width: '1044px',
							height: '364px',
							top: '350px',
							left: '438px',
							gap: '32px',
							justifyContent: 'center',
						} }
					>
						<div
							style={ {
								display: 'flex',
								flexDirection: 'column',
								gap: '24px',
							} }
						>
							<h1>
								{ __(
									'Building  Forms with AI ',
									'sureforms'
								) }
							</h1>
							<p
								style={ {
									fontSize: '16px',
									fontWeight: '600',
									lineHeight: '24px',
									color: '#64748B',
									margin: '0',
								} }
							>
								{ __(
									'Here is how the AI Form Builder Works:',
									'sureforms'
								) }
							</p>
							<ol
								style={ {
									fontWeight: '400',
									fontSize: '16px',
									lineHeight: '24px',
									color: '#64748B',
									margin: '0',
									paddingInlineStart: '20px',
								} }
							>
								<li>
									{ __(
										'Create a free account on ZipWP platform.',
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
										'Watch as AI crafts your form instantly.',
										'sureforms'
									) }
								</li>
								<li>
									{ __(
										'Refine the form with an easy drag & drop builder.',
										'sureforms'
									) }
								</li>
								<li>{ __( 'Launch.', 'sureforms' ) }</li>
							</ol>
							<Button
								onClick={ authorizeZipAI }
								style={ {
									display: 'flex',
									alignItems: 'center',
									justifyContent: 'space-between',
									backgroundColor: '#D54407',
									color: 'white',
									width: '271px',
									height: '48px',
									padding: '12px 20px 12px 24px',
									gap: '7px',
									borderRadius: '6px',
									border: 'none',
									cursor: 'pointer',
								} }
							>
								<span
									style={ {
										fontSize: '16px',
										fontWeight: '600',
										lineHeight: '24px',
									} }
								>
									{ buttonLabel }
								</span>
								<MdArrowForward color="white" size={ 20 } />
							</Button>
							{ showErr && (
								<p
									style={ {
										fontSize: '16px',
										fontWeight: '600',
										lineHeight: '24px',
										color: '#D54407',
										margin: '0',
									} }
								>
									{ __(
										'An error occurred. Please try again.',
										'sureforms'
									) }
								</p>
							) }
							<Link
								className="srfm-tp-breadcrumb-url"
								to={ {
									location: `${ srfm_admin.site_url }/wp-admin/admin.php`,
									search: `?page=add-new-form`,
								} }
								style={ {
									display: 'flex',
									alignItems: 'center',
									justifyContent: 'center',
									width: '66px',
									height: '24px',
									gap: '8px',
									color: '#64748B',
									boxShadow: 'none',
								} }
							>
								<MdKeyboardBackspace size={ 20 } />
								<span
									style={ {
										fontSize: '16px',
										fontWeight: '500',
										lineHeight: '24px',
									} }
								>
									{ __( 'Back', 'sureforms' ) }
								</span>
							</Link>
						</div>
						<div>
							<img src={ aiAuthPlaceholder } alt="AI Auth" />
						</div>
					</div>
				</div>
			</div>
		</>
	);
};

export default TemplateScreen;
