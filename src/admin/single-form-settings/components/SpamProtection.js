import { __ } from '@wordpress/i18n';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { Select, Label, Container } from '@bsf/force-ui';
import TabContentWrapper from '@Components/tab-content-wrapper';
import RadioGroup from '@Admin/components/RadioGroup';
import apiFetch from '@wordpress/api-fetch';
import { Info } from 'lucide-react';
import svg from '@Svg/svgs.json';
import parse from 'html-react-parser';

const SpamProtection = () => {
	const { editPost } = useDispatch( editorStore );
	const sureformsKeys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	const [ keys, setKeys ] = useState( {
		v2Checkbox: { site: '', secret: '' },
		v2Invisible: { site: '', secret: '' },
		v3: { site: '', secret: '' },
		turnstile: { site: '', secret: '' },
		hcaptcha: { site: '', secret: '' },
	} );

	const [ showRecaptchaConflictNotice, setShowRecaptchaConflictNotice ] =
		useState( false );
	const [ showErr, setShowErr ] = useState( false );

	const updateMeta = ( option, value ) => {
		editPost( { meta: { [ option ]: value } } );
	};

	const validateKeys = ( type ) => {
		const { site, secret } = keys[ type ] || {};
		return site && secret;
	};

	useEffect( () => {
		const fetchData = async () => {
			try {
				const data = await apiFetch( {
					path: `sureforms/v1/srfm-global-settings?options_to_fetch=srfm_security_settings_options`,
					method: 'GET',
					headers: {
						'content-type': 'application/json',
						'X-WP-Nonce': srfm_admin.global_settings_nonce,
					},
				} );

				const opts = data?.srfm_security_settings_options || {};
				const keyMap = {
					v2Checkbox: {
						site: opts.srfm_v2_checkbox_site_key,
						secret: opts.srfm_v2_checkbox_secret_key,
					},
					v2Invisible: {
						site: opts.srfm_v2_invisible_site_key,
						secret: opts.srfm_v2_invisible_secret_key,
					},
					v3: {
						site: opts.srfm_v3_site_key,
						secret: opts.srfm_v3_secret_key,
					},
					turnstile: {
						site: opts.srfm_cf_turnstile_site_key,
						secret: opts.srfm_cf_turnstile_secret_key,
					},
					hcaptcha: {
						site: opts.srfm_hcaptcha_site_key,
						secret: opts.srfm_hcaptcha_secret_key,
					},
				};
				setKeys( keyMap );

				// Detect reCAPTCHA version conflicts
				const { v2Checkbox, v2Invisible, v3 } = keyMap;
				if (
					( v2Checkbox.site &&
						v2Checkbox.secret &&
						v2Invisible.site &&
						v2Invisible.secret ) ||
					( v2Checkbox.site &&
						v2Checkbox.secret &&
						v3.site &&
						v3.secret ) ||
					( v2Invisible.site &&
						v2Invisible.secret &&
						v3.site &&
						v3.secret )
				) {
					setShowRecaptchaConflictNotice( true );
				}
			} catch ( error ) {
				console.error( 'Error fetching data:', error );
			}
		};

		fetchData();
	}, [] );

	const handleSecurityTypeChange = ( value ) => {
		const keyTypeMap = {
			'cf-turnstile': 'turnstile',
			hcaptcha: 'hcaptcha',
		};

		if ( keyTypeMap[ value ] && ! validateKeys( keyTypeMap[ value ] ) ) {
			setShowErr( true );
			return;
		}

		setShowErr( false );
		updateMeta( '_srfm_captcha_security_type', value );
	};

	const handleRecaptchaVersionChange = ( value ) => {
		const keyTypeMap = {
			'v2-checkbox': 'v2Checkbox',
			'v2-invisible': 'v2Invisible',
			'v3-reCAPTCHA': 'v3',
		};

		if ( keyTypeMap[ value ] && ! validateKeys( keyTypeMap[ value ] ) ) {
			setShowErr( true );
			return;
		}

		setShowErr( false );
		updateMeta( '_srfm_form_recaptcha', value );
	};

	const securityTypeOptions = [
		{ value: 'none', label: __( 'None', 'sureforms' ) },
		{ value: 'g-recaptcha', label: __( 'Google reCAPTCHA', 'sureforms' ) },
		{
			value: 'cf-turnstile',
			label: __( 'CloudFlare Turnstile', 'sureforms' ),
		},
		{ value: 'hcaptcha', label: __( 'hCaptcha', 'sureforms' ) },
	];

	const recaptchaVersionOptions = [
		{ value: 'v2-checkbox', label: __( 'reCAPTCHA v2', 'sureforms' ) },
		{
			value: 'v2-invisible',
			label: __( 'reCAPTCHA v2 Invisible', 'sureforms' ),
		},
		{ value: 'v3-reCAPTCHA', label: __( 'reCAPTCHA v3', 'sureforms' ) },
	];

	return (
		<TabContentWrapper
			title={ __( 'Spam Protection', 'sureforms' ) }
			shouldShowAutoSaveText
			autoSaveHelpText={ __(
				'Enable reCAPTCHA, hCaptcha, Turnstile, or Honeypot to block spam submissions.',
				'sureforms'
			) }
			shouldAddHelpTextPadding={ false }
		>
			<div className="space-y-6">
				{ /* Security Type */ }
				<div className="space-y-1.5 flex flex-col gap-2">
					<div className="flex flex-col gap-1.5">
						<Label htmlFor="security-type">
							{ __( 'Spam Protection Type', 'sureforms' ) }
						</Label>
						<Select
							value={
								sureformsKeys._srfm_captcha_security_type ||
								'none'
							}
							onChange={ handleSecurityTypeChange }
							options={ securityTypeOptions }
						>
							<Select.Button
								id="security-type"
								placeholder={ __(
									'Select Security Type',
									'sureforms'
								) }
							>
								{ securityTypeOptions.find(
									( o ) =>
										o.value ===
										sureformsKeys._srfm_captcha_security_type
								)?.label || __( 'None', 'sureforms' ) }
							</Select.Button>
							<Select.Options>
								{ securityTypeOptions.map( ( option ) => (
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
					</div>
					<Label size="sm" variant="help" className="font-normal">
						{ __(
							'Select a spam protection service from the list above. Make sure API keys are configured before enabling.',
							'sureforms'
						) }
					</Label>
				</div>

				{ /* reCAPTCHA versions */ }
				{ sureformsKeys._srfm_captcha_security_type ===
					'g-recaptcha' && (
					<>
						{ showRecaptchaConflictNotice && (
							<Container className="w-full p-3 gap-2 border border-alert-border-warning bg-alert-background-warning rounded-lg">
								<span className="size-5">
									{ parse( svg?.warning ) }
								</span>
								<span className="text-sm font-normal">
									{ __(
										'Note: Using different reCAPTCHA versions (V2 checkbox and V3) on the same page will create conflicts between the versions. Kindly avoid using different versions on the same page.',
										'sureforms'
									) }
								</span>
							</Container>
						) }
						<div className="space-y-2">
							<Label>
								{ __( 'Select Version', 'sureforms' ) }
							</Label>
							<RadioGroup cols={ 3 } className="max-w-full">
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
											handleRecaptchaVersionChange(
												option.value
											)
										}
									/>
								) ) }
							</RadioGroup>
						</div>
					</>
				) }

				{ /* Error message */ }
				{ showErr && (
					<div className="flex items-center justify-between gap-3 p-3 bg-[#FEE2E2] border border-[#FCA5A5] rounded-lg text-sm text-gray-900">
						<div className="flex items-center gap-2">
							<Info color="#DC2626" size={ 20 } />
							<span>
								{ __(
									'Please configure the API keys correctly from the settings',
									'sureforms'
								) }
							</span>
						</div>
						<a
							href={ srfm_admin.security_settings_url }
							className="text-[#DC2626] font-medium no-underline"
							target="_blank" rel="noreferrer"
						>
							{ __( 'Global Settings', 'sureforms' ) }
						</a>
					</div>
				) }
			</div>
		</TabContentWrapper>
	);
};

export default SpamProtection;
