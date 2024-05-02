import { SelectControl, PanelRow, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import apiFetch from '@wordpress/api-fetch';
import SingleFormSettingsPopup from '../components/SingleFormSettingPopup';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';

function AdvancedSettings( props ) {
	const { editPost } = useDispatch( editorStore );

	const { defaultKeys } = props;
	// Modal icon
	const modalIcon = parse( svgIcons.modalLogo );

	const [ sureformsV2CheckboxSite, setSureformsV2CheckboxSite ] =
		useState( '' );
	const [ sureformsV2CheckboxSecret, setSureformsV2CheckboxSecret ] =
		useState( '' );
	const [ sureformsV2InvisibleSite, setSureformsV2InvisibleSite ] =
		useState( '' );
	const [ sureformsV2InvisibleSecret, setSureformsV2InvisibleSecret ] =
		useState( '' );
	const [ sureformsV3Site, setSureformsV3Site ] = useState( '' );
	const [ sureformsV3Secret, setSureformsV3Secret ] = useState( '' );
	const [ sureformsCfTurnstileSite, setSureformsCfTurnstileSite ] =
		useState( '' );
	const [ sureformsCfTurnstileSecret, setSureformsCfTurnstileSecret ] =
		useState( '' );
	const [ showRecaptchaConflictNotice, setsShowRecaptchaConflictNotice ] =
		useState( false );

	const [ showErr, setShowErr ] = useState( false );
	const [ isOpen, setOpen ] = useState( false );
	const [ popupTab, setPopupTab ] = useState( false );

	const openModal = ( e ) => {
		const popupTabTarget = e.currentTarget.getAttribute( 'data-popup' );
		setPopupTab( popupTabTarget );
		setOpen( true );
	};
	const closeModal = () => setOpen( false );

	let sureformsKeys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	if ( sureformsKeys && '_srfm_submit_type' in sureformsKeys ) {
		if ( ! sureformsKeys._srfm_submit_type ) {
			sureformsKeys = defaultKeys;
			editPost( {
				meta: sureformsKeys,
			} );
		}
	} else {
		sureformsKeys = defaultKeys;
		editPost( {
			meta: sureformsKeys,
		} );
	}

	function updateMeta( option, value ) {
		const option_array = {};
		option_array[ option ] = value;
		editPost( {
			meta: option_array,
		} );
	}

	// Fetch the reCAPTCHA keys from the Global Settings
	const optionsToFetch = [ 'srfm_security_settings_options' ];

	useEffect( () => {
		const fetchData = async () => {
			try {
				const data = await apiFetch( {
					path: `sureforms/v1/srfm-global-settings?options_to_fetch=${ optionsToFetch }`,
					method: 'GET',
					headers: {
						'content-type': 'application/json',
						'X-WP-Nonce': srfm_admin.global_settings_nonce,
					},
				} );
				if ( data.srfm_security_settings_options ) {
					const {
						srfm_v2_checkbox_site_key,
						srfm_v2_checkbox_secret_key,
						srfm_v2_invisible_site_key,
						srfm_v2_invisible_secret_key,
						srfm_v3_site_key,
						srfm_v3_secret_key,
						srfm_cf_turnstile_site_key,
						srfm_cf_turnstile_secret_key,
					} = data.srfm_security_settings_options;
					setSureformsV2CheckboxSite(
						srfm_v2_checkbox_site_key || ''
					);
					setSureformsV2CheckboxSecret(
						srfm_v2_checkbox_secret_key || ''
					);
					setSureformsV2InvisibleSite(
						srfm_v2_invisible_site_key || ''
					);
					setSureformsV2InvisibleSecret(
						srfm_v2_invisible_secret_key || ''
					);
					setSureformsV3Site( srfm_v3_site_key || '' );
					setSureformsV3Secret( srfm_v3_secret_key || '' );

					// CloudFlare Turnstile
					setSureformsCfTurnstileSite(
						srfm_cf_turnstile_site_key || ''
					);
					setSureformsCfTurnstileSecret(
						srfm_cf_turnstile_secret_key || ''
					);

					// show the notice if 2 recaptcha site and secret keys are not empty.
					const v2_checkbox =
						srfm_v2_checkbox_site_key &&
						srfm_v2_checkbox_secret_key;
					const v2_invisible =
						srfm_v2_invisible_site_key &&
						srfm_v2_invisible_secret_key;
					const v3 = srfm_v3_site_key && srfm_v3_secret_key;

					if (
						( v2_checkbox && v2_invisible ) ||
						( v2_checkbox && v3 ) ||
						( v2_invisible && v3 )
					) {
						setsShowRecaptchaConflictNotice( true );
					}
				}
			} catch ( error ) {
				console.error( 'Error fetching data:', error );
			}
		};

		fetchData();
	}, [] );

	return (
		<>
			<SRFMAdvancedPanelBody
				title={ __( 'Security Settings', 'sureforms' ) }
				initialOpen={ false }
			>
				<SelectControl
					label={ __( 'Security Type', 'sureforms' ) }
					value={ sureformsKeys._srfm_captcha_security_type }
					options={ [
						{
							value: 'none',
							label: __( 'None', 'sureforms' ),
						},
						{
							value: 'g-recaptcha',
							label: __( 'Google reCAPTCHA', 'sureforms' ),
						},
						{
							value: 'cf-turnstile',
							label: __( 'CloudFlare Turnstile', 'sureforms' ),
						},
					] }
					onChange={ ( value ) => {
						if ( value === 'cf-turnstile' ) {
							if (
								sureformsCfTurnstileSite !== '' &&
								sureformsCfTurnstileSecret !== ''
							) {
								setShowErr( false );
								updateMeta(
									'_srfm_captcha_security_type',
									value
								);
							} else {
								setShowErr( true );
							}
						} else {
							setShowErr( false );
							updateMeta( '_srfm_captcha_security_type', value );
						}
					} }
					__nextHasNoMarginBottom
				/>
				{ sureformsKeys._srfm_captcha_security_type ===
					'g-recaptcha' && (
					<>
						{ showRecaptchaConflictNotice && (
							<PanelRow>
								<p className="srfm-form-notice">
									{ __(
										'P.S. Note that If you are using two forms on the same page with the different reCAPTCHA versions (V2 checkbox and V3), it will create conflicts between the versions. Kindly avoid using different versions on same page.',
										'sureforms'
									) }
								</p>
							</PanelRow>
						) }
						<SelectControl
							label={ __(
								'Select the reCAPTCHA Version to Use',
								'sureforms'
							) }
							value={ sureformsKeys._srfm_form_recaptcha }
							options={ [
								{ label: 'None', value: 'none' },
								{
									label: __(
										'reCAPTCHA v2 Checkbox',
										'sureforms'
									),
									value: 'v2-checkbox',
								},
								{
									label: __(
										'reCAPTCHA v2 Invisible',
										'sureforms'
									),
									value: 'v2-invisible',
								},
								{
									label: __( 'reCAPTCHA v3', 'sureforms' ),
									value: 'v3-reCAPTCHA',
								},
							] }
							onChange={ ( value ) => {
								if ( value === 'v2-checkbox' ) {
									if (
										sureformsV2CheckboxSite !== '' &&
										sureformsV2CheckboxSecret !== ''
									) {
										setShowErr( false );
										updateMeta(
											'_srfm_form_recaptcha',
											value
										);
									} else {
										setShowErr( true );
									}
								} else if ( value === 'v2-invisible' ) {
									if (
										sureformsV2InvisibleSecret !== '' &&
										sureformsV2InvisibleSite !== ''
									) {
										setShowErr( false );
										updateMeta(
											'_srfm_form_recaptcha',
											value
										);
									} else {
										setShowErr( true );
									}
								} else if ( value === 'v3-reCAPTCHA' ) {
									if (
										sureformsV3Secret !== '' &&
										sureformsV3Site !== ''
									) {
										setShowErr( false );
										updateMeta(
											'_srfm_form_recaptcha',
											value
										);
									} else {
										setShowErr( true );
									}
								} else {
									setShowErr( false );
									updateMeta( '_srfm_form_recaptcha', value );
								}
							} }
							__nextHasNoMarginBottom
						/>
					</>
				) }

				<p className="components-base-control__help">
					{ __(
						'Before selecting the security type. Please make sure you have configured API keys in the Global Settings - here',
						'sureforms'
					) }
				</p>
				{ showErr && (
					<p style={ { color: 'red' } }>
						{ __(
							'Please configure the API keys correctly',
							'sureforms'
						) }
						<a
							href={ srfm_admin.security_settings_url }
							target="_blank"
							rel="noreferrer"
							style={ {
								display: 'flex',
								alignItems: 'center',
							} }
						>
							{ __( 'Global Settings', 'sureforms' ) }
							<svg
								xmlns="http://www.w3.org/2000/svg"
								viewBox="0 0 24 24"
								width="16"
								height="16"
								aria-hidden="true"
								focusable="false"
							>
								<path d="M19.5 4.5h-7V6h4.44l-5.97 5.97 1.06 1.06L18 7.06v4.44h1.5v-7Zm-13 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3H17v3a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h3V5.5h-3Z"></path>
							</svg>
						</a>
					</p>
				) }
			</SRFMAdvancedPanelBody>
			<MoreSettingsButton
				settingName={ __( 'Email Notification', 'sureforms' ) }
				popupId="email_notification"
				openModal={ openModal }
			/>
			<MoreSettingsButton
				settingName={ __( 'Form Confirmation', 'sureforms' ) }
				popupId="form_confirmation"
				openModal={ openModal }
			/>

			<MoreSettingsButton
				settingName={ __( 'Compliance Settings', 'sureforms' ) }
				popupId="compliance_settings"
				openModal={ openModal }
			/>
			<MoreSettingsButton
				settingName={ __( 'Custom CSS', 'sureforms' ) }
				popupId="form_custom_css"
				openModal={ openModal }
			/>
			{ isOpen && (
				<Modal
					onRequestClose={ closeModal }
					title={ __( 'Single Form Setting', 'sureforms' ) }
					className="srfm-header-settings-modal"
					icon={ modalIcon }
					isFullScreen={ true }
				>
					<SingleFormSettingsPopup
						sureformsKeys={ sureformsKeys }
						targetTab={ popupTab }
					/>
				</Modal>
			) }
		</>
	);
}

const MoreSettingsButton = ( { settingName, popupId, openModal } ) => {
	return (
		<div className="srfm-custom-layout-panel components-panel__body">
			<h2 className="components-panel__body-title">
				<button
					className="components-button components-panel__body-toggle"
					onClick={ openModal }
					data-popup={ popupId }
				>
					<span className="srfm-title">
						<div>{ settingName }</div>
					</span>
					<svg
						width="24"
						height="24"
						viewBox="0 0 24 24"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
					>
						<g id="heroicons-mini/ellipsis-horizontal">
							<g id="Union">
								<path
									d="M3.60156 12.0031C3.60156 11.009 4.40745 10.2031 5.40156 10.2031C6.39567 10.2031 7.20156 11.009 7.20156 12.0031C7.20156 12.9972 6.39567 13.8031 5.40156 13.8031C4.40745 13.8031 3.60156 12.9972 3.60156 12.0031Z"
									fill="#555D66"
								/>
								<path
									d="M10.2016 12.0031C10.2016 11.009 11.0074 10.2031 12.0016 10.2031C12.9957 10.2031 13.8016 11.009 13.8016 12.0031C13.8016 12.9972 12.9957 13.8031 12.0016 13.8031C11.0074 13.8031 10.2016 12.9972 10.2016 12.0031Z"
									fill="#555D66"
								/>
								<path
									d="M18.6016 10.2031C17.6074 10.2031 16.8016 11.009 16.8016 12.0031C16.8016 12.9972 17.6074 13.8031 18.6016 13.8031C19.5957 13.8031 20.4016 12.9972 20.4016 12.0031C20.4016 11.009 19.5957 10.2031 18.6016 10.2031Z"
									fill="#555D66"
								/>
							</g>
						</g>
					</svg>
				</button>
			</h2>
		</div>
	);
};

export default AdvancedSettings;
