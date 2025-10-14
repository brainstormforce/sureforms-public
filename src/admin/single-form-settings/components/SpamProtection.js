import { __ } from '@wordpress/i18n';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { Select, Label, Container } from '@bsf/force-ui';
import TabContentWrapper from '@Components/tab-content-wrapper';
import RadioGroup from '@Admin/components/RadioGroup';
import apiFetch from '@wordpress/api-fetch';
import { Info } from "lucide-react";
import svg from '@Svg/svgs.json';
import parse from 'html-react-parser';

const SpamProtection = () => {
	const { editPost } = useDispatch( editorStore );

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

	const sureformsKeys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

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

	const securityTypeOptions = [
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
	];

	const recaptchaVersionOptions = [
		{
			label: __( 'reCAPTCHA v2', 'sureforms' ),
			value: 'v2-checkbox',
		},
		{
			label: __( 'reCAPTCHA v2 Invisible', 'sureforms' ),
			value: 'v2-invisible',
		},
		{
			label: __( 'reCAPTCHA v3', 'sureforms' ),
			value: 'v3-reCAPTCHA',
		},
	];

	const handleSecurityTypeChange = ( value ) => {
		if ( value === 'cf-turnstile' ) {
			if (
				sureformsCfTurnstileSite !== '' &&
				sureformsCfTurnstileSecret !== ''
			) {
				setShowErr( false );
				updateMeta( '_srfm_captcha_security_type', value );
			} else {
				setShowErr( true );
			}
		} else if ( value === 'hcaptcha' ) {
			if (
				sureformsHCaptchaSite !== '' &&
				sureformsHCaptchaSecret !== ''
			) {
				setShowErr( false );
				updateMeta( '_srfm_captcha_security_type', value );
			} else {
				setShowErr( true );
			}
		} else {
			setShowErr( false );
			updateMeta( '_srfm_captcha_security_type', value );
		}
	};

	const handleRecaptchaVersionChange = ( value ) => {
		if ( value === 'v2-checkbox' ) {
			if (
				sureformsV2CheckboxSite !== '' &&
				sureformsV2CheckboxSecret !== ''
			) {
				setShowErr( false );
				updateMeta( '_srfm_form_recaptcha', value );
			} else {
				setShowErr( true );
			}
		} else if ( value === 'v2-invisible' ) {
			if (
				sureformsV2InvisibleSecret !== '' &&
				sureformsV2InvisibleSite !== ''
			) {
				setShowErr( false );
				updateMeta( '_srfm_form_recaptcha', value );
			} else {
				setShowErr( true );
			}
		} else if ( value === 'v3-reCAPTCHA' ) {
			if ( sureformsV3Secret !== '' && sureformsV3Site !== '' ) {
				setShowErr( false );
				updateMeta( '_srfm_form_recaptcha', value );
			} else {
				setShowErr( true );
			}
		} else {
			setShowErr( false );
			updateMeta( '_srfm_form_recaptcha', value );
		}
	};

	return (
		<TabContentWrapper title={ __( 'Spam Protection', 'sureforms' ) }
		shouldShowAutoSaveText={ true }
		autoSaveHelpText={ __( 'Enable reCAPTCHA, hCaptcha, Turnstile, or Honeypot to block spam submissions.', 'sureforms' ) }
		shouldAddHelpTextPadding={false}
		>
			<div className="space-y-6">
				<div className="space-y-1.5">
					<div className="flex items-center justify-between gap-3 p-3 bg-[#E0F2FE] border-solid border-[#7DD3FC] rounded-lg text-sm text-gray-900 mb-3">
						<div className='flex items-center gap-2'>
						<Info color='#0284C7' size={20} />
						<span>
							Before selecting the security type, please make sure you have configured the API keys{" "}
						</span>
						</div>
							<a href={srfm_admin.security_settings_url} className="text-orange-600 font-medium no-underline">
							Here
							</a>
					</div>
					<Label htmlFor="security-type">
						{ __( 'Span Protection Type', 'sureforms' ) }
					</Label>
					<Select
						value={
							sureformsKeys._srfm_captcha_security_type || 'none'
						}
						onChange={ ( value ) => handleSecurityTypeChange( value ) }
						options={ securityTypeOptions }
					>
						<Select.Button
							id="security-type"
							placeholder={ __( 'Select Security Type', 'sureforms' ) }
						>
							{
								securityTypeOptions?.find(
									( option ) =>
										option.value ===
										sureformsKeys._srfm_captcha_security_type
								)?.label || __( 'None', 'sureforms' )
							}
						</Select.Button>
						<Select.Options>
							{ securityTypeOptions?.map( ( option ) => (
								<Select.Option
									key={ option.value }
									value={ option.value }
									selected={
										option.value ===
										sureformsKeys._srfm_captcha_security_type
									}
								>
									{ option.label }
								</Select.Option>
							) ) }
						</Select.Options>
					</Select>
				<Label size="sm" variant="help" className="font-normal">
					{ __( 'Select a spam protection service from the list above. Make sure API keys are configured before enabling.', 'sureforms' ) }
				</Label>
				</div>

				{ sureformsKeys._srfm_captcha_security_type ===
					'g-recaptcha' && (
					<>
						{ showRecaptchaConflictNotice && (
									<Container className="w-full p-3 gap-2 border border-solid border-alert-border-warning bg-alert-background-warning rounded-lg">
			<span className="size-5">{ parse( svg?.warning ) }</span>
			<span className="text-sm font-normal">			{ __(
										'Note: Using different reCAPTCHA versions (V2 checkbox and V3) on the same page will create conflicts between the versions. Kindly avoid using different versions on the same page.',
										'sureforms'
									) }
							</span>
		</Container>
						) }
						<div className="space-y-2">
							<Label>
								{ __(
									'Select Version',
									'sureforms'
								) }
							</Label>
							<RadioGroup cols={ 3 } className={"max-w-full"}>
								{ recaptchaVersionOptions.map( ( option ) => (
									<RadioGroup.Option
										key={ option.value }
										label={ option.label }
										value={ option.value }
										checked={
											sureformsKeys._srfm_form_recaptcha ===
											option.value
										}
										onChange={ () =>
											handleRecaptchaVersionChange( option.value )
										}
									/>
								) ) }
							</RadioGroup>
						</div>
					</>
				) }

				<div className="space-y-1.5">
					{ showErr && (
						<div className="flex items-center justify-between gap-3 p-3 bg-[#FEE2E2] border-solid border-[#FCA5A5] rounded-lg text-sm text-gray-900">
						<div className='flex items-center gap-2'>
						<Info color='#DC2626' size={20} />
						<span>
							{ __( 'Please configure the API keys correctly from the settings', 'sureforms' ) }
						</span>
						</div>
						<div className='flex items-center gap-2'>
						<span>
							<a href={ srfm_admin.security_settings_url } className="text-orange-600 font-medium no-underline">
								{ __( 'Global Settings', 'sureforms' ) }
							</a>
						</span>
						</div>
					</div>
					) }
				</div>
			</div>
		</TabContentWrapper>
	);
};

export default SpamProtection;
