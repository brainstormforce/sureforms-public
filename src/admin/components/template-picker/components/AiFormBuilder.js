import {
	__,
	sprintf,
} from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';
import { handleAddNewPost, initiateAuth } from '@Utils/Helpers';
import Header from './Header.js';
import LimitReachedPopup from './LimitReachedPopup.js';
import ErrorPopup from './ErrorPopup.js';
import { AuthErrorPopup } from './AuthErrorPopup.js';
import { Toaster } from 'react-hot-toast';
import { applyFilters } from '@wordpress/hooks';
import AiFormBuilderNew from '@Admin/dashboard/AiFormBuilderNew.js';
import AiFormProgressPage from '@Admin/dashboard/AiFormProgressPage.js';

const AiFormBuilder = () => {
	const [ message, setMessage ] = useState(
		__( 'Connecting with AI…', 'sureforms' )
	);
	const [ isBuildingForm, setIsBuildingForm ] = useState( false );
	const [ percentBuild, setPercentBuild ] = useState( 0 );
	const [ showEmptyError, setShowEmptyError ] = useState( false );
	const [ showFormCreationErr, setShowFormCreationErr ] = useState( false );
	const [ showAuthErrorPopup, setShowAuthErrorPopup ] = useState( false );
	const urlParams = new URLSearchParams( window.location.search );
	const accessKey = urlParams.get( 'access_key' );
	const [ formTypeObj, setFormTypeObj ] = useState( {} );

	const handleCreateAiForm = async (
		userCommand,
		previousMessages,
		useSystemMessage
	) => {
		setPercentBuild( 0 );
		// Check if the user has permission to create posts.
		if ( '1' !== srfm_admin.capability ) {
			console.error( 'User does not have permission to create posts' );
			return;
		}

		// Check if the user has entered a prompt in textarea.
		if ( ! userCommand ) {
			setShowEmptyError( true );
			return;
		}

		// Prepare the data to be sent to the API.
		const messageArray =
			previousMessages?.map( ( chat ) => ( {
				role: chat.role,
				content: chat.message,
			} ) ) || [];
		messageArray.push( { role: 'user', content: userCommand } );
		const formType = applyFilters(
			'srfm.aiFormScreen.formType',
			'',
			formTypeObj
		);
		const postData = {
			message_array: messageArray,
			use_system_message: useSystemMessage,
			is_conversional: formTypeObj?.isConversationalForm,
			form_type: formType,
		};

		// add a pause of 2 seconds and set percentBuild to 25 without using setTimeout
		setPercentBuild( 50 );
		setMessage( __( 'Generating Fields…', 'sureforms' ) );

		try {
			const response = await apiFetch( {
				path: 'sureforms/v1/generate-form',
				method: 'POST',
				data: postData,
			} );

			if ( response ) {
				setMessage(
					__( 'Just doing some final touches…', 'sureforms' )
				);
				setPercentBuild( 75 );

				if ( response?.success === false ) {
					setShowFormCreationErr( true );
					return;
				}

				const content = response?.data;

				if ( ! content ) {
					setShowFormCreationErr( true );
					return;
				}

				const postContent = await apiFetch( {
					path: 'sureforms/v1/map-fields',
					method: 'POST',
					data: { form_data: content, is_conversional: formTypeObj?.isConversationalForm },
				} );

				if ( postContent ) {
					setMessage( __( 'Redirecting to Editor', 'sureforms' ) );
					setPercentBuild( 100 );
					const formTitle = content?.form?.formTitle;
					const metasToUpdate = applyFilters(
						'srfm.aiFormScreen.metasToUpdate',
						{},
						formTypeObj,
						content
					);
					handleAddNewPost( postContent, formTitle, metasToUpdate, formTypeObj?.isConversationalForm, formType );
				} else {
					setShowFormCreationErr( true );
				}
			} else {
				setShowFormCreationErr( true );
				console.error(
					'Error creating sureforms form using AI: ',
					response.message
				);
				return;
			}
		} catch ( error ) {
			console.log( error );
		}
	};

	const handleAccessKey = async () => {
		// if access key is present, handle it by decrypting it and redirecting to form builder
		const response = await apiFetch( {
			path: '/sureforms/v1/handle-access-key',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': srfm_admin.template_picker_nonce,
			},
			method: 'POST',
			body: JSON.stringify( {
				accessKey,
			} ),
		} );

		if ( response?.success ) {
			window.location.href =
				srfm_admin.site_url +
				`/wp-admin/admin.php?page=add-new-form&method=ai`;
		} else {
			setShowAuthErrorPopup( true );
			console.error( 'Error handling access key: ', response.message );
		}
	};

	// Handle access key on component mount
	useEffect( () => {
		if ( accessKey ) {
			handleAccessKey();
		}
	}, [ accessKey ] );

	// shows while the form is being built
	if ( isBuildingForm ) {
		return (
			<>
				<div className="srfm-ts-main-container srfm-content-section">
					<AiFormProgressPage message={ message } percentBuild={ percentBuild } />
				</div>
				{ showFormCreationErr && <ErrorPopup /> }
			</>
		);
	}

	// show auth error popup when access key is not present while authenticating
	if ( showAuthErrorPopup ) {
		return <AuthErrorPopup initiateAuth={ initiateAuth } />;
	}

	return (
		<>
			<Toaster position="bottom-right" />
			<Header />
			<AiFormBuilderNew
				handleCreateAiForm={ handleCreateAiForm }
				setIsBuildingForm={ setIsBuildingForm }
				formTypeObj={ formTypeObj }
				showEmptyError={ showEmptyError }
				setShowEmptyError={ setShowEmptyError }
				setFormTypeObj={ setFormTypeObj }
			/>
		</>
	);
};

export const getLimitReachedPopup = () => {
	const type = srfm_admin?.srfm_ai_usage_details?.type;
	const formCreationleft = srfm_admin?.srfm_ai_usage_details?.remaining ?? 0;
	const errorCode = srfm_admin?.srfm_ai_usage_details?.code;
	const resetAt = srfm_admin?.srfm_ai_usage_details?.resetAt;

	// shows when the user has encountered an error.
	if ( errorCode ) {
		return (
			<LimitReachedPopup
				title={ srfm_admin?.srfm_ai_usage_details?.title }
				paraOne={ srfm_admin?.srfm_ai_usage_details?.message }
				buttonText={ __( 'Try Again', 'sureforms' ) }
				onclick={ () => {
					window.location.href =
						srfm_admin.site_url +
						'/wp-admin/admin.php?page=add-new-form&method=ai';
				} }
			/>
		);
	}

	// When pro limit is consumed
	if (
		type === 'subscribed' &&
		srfm_admin?.is_pro_active &&
		srfm_admin?.is_pro_license_active &&
		formCreationleft === 0 &&
		resetAt && resetAt > Date.now() / 1000
	) {
		return (
			<LimitReachedPopup
				paraOne={ __(
					'You have reached the FUP limit of AI form generations for the day.',
					'sureforms'
				) }
				paraTwo={ sprintf(
					/* translators: %s: reset time */
					__( 'Please try again after %s.', 'sureforms' ),
					new Date(
						resetAt * 1000
					).toLocaleString()
				) }
				buttonText={ __( 'Try Again', 'sureforms' ) }
				onclick={ () => {
					window.location.href =
						srfm_admin.site_url +
						'/wp-admin/admin.php?page=add-new-form&method=ai';
				} }
			/>
		);
	}

	// When registered limit is consumed
	if ( type === 'registered' && formCreationleft === 0 ) {
		return (
			<LimitReachedPopup
				paraOne={ __(
					'You have reached the maximum number of form generations in your Free Plan.',
					'sureforms'
				) }
				paraTwo={ __(
					'Please upgrade your free plan to keep creating more forms with AI.',
					'sureforms'
				) }
				buttonText={ __( 'Upgrade Plan', 'sureforms' ) }
				onclick={ () => {
					window.open( srfm_admin?.pricing_page_url, '_blank' );
				} }
			/>
		);
	}

	// when initial 5 forms are consumed
	if ( type === 'non-registered' && formCreationleft === 0 ) {
		return (
			<LimitReachedPopup
				paraOne={ __(
					'You have reached the maximum number of form generations.',
					'sureforms'
				) }
				paraTwo={ __(
					'Please connect your website with SureForms AI to create 20 more forms with AI.',
					'sureforms'
				) }
				onclick={ initiateAuth }
			/>
		);
	}

	return <AiFormBuilder />;
};

export default AiFormBuilder;
