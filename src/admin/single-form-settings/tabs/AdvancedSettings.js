import { SelectControl, PanelRow, ExternalLink } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import apiFetch from '@wordpress/api-fetch';
import FormBehaviorPopupButton from '../../components/FormBehaviorPopupButton';
import Dialog from '../components/dialog/Dialog';
import { FormRestrictionProvider } from '../components/form-restrictions/context';

let prevMetaHash = '';

function AdvancedSettings( props ) {
	const [ hasValidationErrors, setHasValidationErrors ] = useState( false );
	const { editPost } = useDispatch( editorStore );

	const { createNotice } = useDispatch( 'core/notices' );

	const { defaultKeys } = props;

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
	const [ sureformsHCaptchaSite, setSureformsHCaptchaSite ] = useState( '' );
	const [ sureformsHCaptchaSecret, setSureformsHCaptchaSecret ] =
		useState( '' );

	const [ showErr, setShowErr ] = useState( false );

	const [ isOpen, setOpen ] = useState( false );
	const [ popupTab, setPopupTab ] = useState( false );

	const openModal = ( e ) => {
		const popupTabTarget = e.currentTarget.getAttribute( 'data-popup' );
		setPopupTab( popupTabTarget );
		setOpen( true );
		prevMetaHash = btoa( JSON.stringify( sureformsKeys ) );
	};
	const closeModal = () => {
		if (
			hasValidationErrors &&
			! confirm(
				__(
					'Are you sure you want to close? Your unsaved changes will be lost as you have some validation errors.',
					'sureforms'
				)
			)
		) {
			return;
		}

		setOpen( false );

		if ( btoa( JSON.stringify( sureformsKeys ) ) !== prevMetaHash ) {
			createNotice(
				'warning',
				__(
					'There are few unsaved changes. Please save your changes to reflect the updates.',
					'sureforms'
				),
				{
					id: 'srfm-unsaved-changes-warning',
					isDismissible: true,
				}
			);
		}
	};

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
						srfm_hcaptcha_site_key,
						srfm_hcaptcha_secret_key,
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

					// hCaptcha
					setSureformsHCaptchaSite( srfm_hcaptcha_site_key || '' );
					setSureformsHCaptchaSecret(
						srfm_hcaptcha_secret_key || ''
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
				title={ __( 'Anti-Spam Settings', 'sureforms' ) }
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
						{
							value: 'hcaptcha',
							label: __( 'hCaptcha', 'sureforms' ),
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
						} else if ( value === 'hcaptcha' ) {
							if (
								sureformsHCaptchaSite !== '' &&
								sureformsHCaptchaSecret !== ''
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
								{
									label: __( 'None', 'sureforms' ),
									value: 'none',
								},
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
						'Before selecting the security type, please make sure you have configured the API keys ',
						'sureforms'
					) }
					<ExternalLink
						href={ `${ srfm_admin.security_settings_url }&subpage=recaptcha` }
					>
						{ __( 'here', 'sureforms' ) }
					</ExternalLink>
				</p>
				{ showErr && (
					<p style={ { color: 'red' } }>
						{ __(
							'Please configure the API keys correctly in the ',
							'sureforms'
						) }
						<ExternalLink href={ srfm_admin.security_settings_url }>
							{ __( 'Global Settings', 'sureforms' ) }
						</ExternalLink>
					</p>
				) }
			</SRFMAdvancedPanelBody>
			<FormBehaviorPopupButton
				settingName={ __( 'Custom CSS', 'sureforms' ) }
				popupId={ 'form_custom_css' }
				openModal={ openModal }
			/>
			<FormRestrictionProvider>
				<Dialog
					open={ isOpen }
					setOpen={ setOpen }
					close={ closeModal }
					sureformsKeys={ sureformsKeys }
					targetTab={ popupTab }
					setHasValidationErrors={ setHasValidationErrors }
				/>
			</FormRestrictionProvider>
		</>
	);
}

export default AdvancedSettings;
