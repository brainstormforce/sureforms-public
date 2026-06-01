import { __ } from '@wordpress/i18n';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch, useSelect, select as wpSelect } from '@wordpress/data';
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { Select, Label, Container } from '@bsf/force-ui';
import TabContentWrapper from '@Components/tab-content-wrapper';
import RadioGroup from '@Admin/components/RadioGroup';
import apiFetch from '@wordpress/api-fetch';
import { Info } from 'lucide-react';
import svg from '@Svg/svgs.json';
import parse from 'html-react-parser';
import { srfmEditFormMeta } from '@Components/tab-content-wrapper/edit-form-meta';
import { STORE_NAME as SRFM_STORE_NAME } from '@Store/constants';

const DEFAULT_DATA = {
	_srfm_captcha_security_type: 'none',
	_srfm_form_recaptcha: '',
};

const SpamProtection = () => {
	const sureformsKeys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	// `data` is the live editing state; `prevData` is the last-saved
	// baseline. Both start at the defaults on mount; the load useEffect
	// below syncs them from `sureformsKeys` so isDirty reads false until
	// the user edits.
	const [ data, setData ] = useState( DEFAULT_DATA );
	const [ prevData, setPrevData ] = useState( DEFAULT_DATA );
	const [ isSaving, setIsSaving ] = useState( false );

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

	const isDirty = useMemo(
		() => JSON.stringify( data ) !== JSON.stringify( prevData ),
		[ data, prevData ]
	);

	// Push the local dirty signal into the store so the dialog's
	// unsaved-changes guard can read it without holding a reference to
	// this component.
	const { setSingleFormSettingUnsave } = useDispatch( SRFM_STORE_NAME );
	useEffect( () => {
		setSingleFormSettingUnsave( isDirty );
	}, [ isDirty, setSingleFormSettingUnsave ] );

	// Unmount cleanup — covers every exit path so the central flag
	// doesn't leak past a discard / tab switch.
	useEffect( () => {
		return () => {
			setSingleFormSettingUnsave( false );
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Listen for the discard signal dispatched by the dialog's "Discard
	// & continue" branch. Each bump of `discardCounter` is one discard
	// event; the ref skips the initial render so the tab doesn't reset
	// itself on mount.
	const discardCounter = useSelect(
		( s ) =>
			s( SRFM_STORE_NAME )?.selectSingleFormSettingDiscardCounter?.() ||
			0,
		[]
	);
	const lastDiscardCounter = useRef( discardCounter );
	useEffect( () => {
		if ( discardCounter === lastDiscardCounter.current ) {
			return;
		}
		lastDiscardCounter.current = discardCounter;
		setData( prevData );
		setShowErr( false );
	}, [ discardCounter ] );

	const validateKeys = ( type ) => {
		const { site, secret } = keys[ type ] || {};
		return site && secret;
	};

	useEffect( () => {
		const fetchData = async () => {
			try {
				const fetched = await apiFetch( {
					path: `sureforms/v1/srfm-global-settings?options_to_fetch=srfm_security_settings_options`,
					method: 'GET',
					headers: {
						'content-type': 'application/json',
						'X-WP-Nonce': srfm_admin.global_settings_nonce,
					},
				} );

				const opts = fetched?.srfm_security_settings_options || {};
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

	// Load existing per-form meta into local state on mount. Both `data`
	// and `prevData` start from the same loaded value so isDirty is
	// false until the user edits.
	useEffect( () => {
		const loaded = {
			_srfm_captcha_security_type:
				sureformsKeys?._srfm_captcha_security_type || 'none',
			_srfm_form_recaptcha:
				sureformsKeys?._srfm_form_recaptcha || '',
		};
		setData( loaded );
		setPrevData( loaded );
	}, [] );

	const handleSecurityTypeChange = ( value ) => {
		setShowErr( false );
		setData( ( prev ) => ( {
			...prev,
			_srfm_captcha_security_type: value,
		} ) );
	};

	const handleRecaptchaVersionChange = ( value ) => {
		setShowErr( false );
		setData( ( prev ) => ( {
			...prev,
			_srfm_form_recaptcha: value,
		} ) );
	};

	// Returns null on valid; an error string for TabContentWrapper to
	// surface as a toast otherwise. Also stages the in-flight `data`
	// into Redux `values` so `handleSave` reads the right payload.
	const validateBeforeSave = () => {
		const type = data._srfm_captcha_security_type;
		let error = '';

		if ( type === 'cf-turnstile' && ! validateKeys( 'turnstile' ) ) {
			error = __(
				'Cloudflare Turnstile API keys are not configured. Set them in Global Settings.',
				'sureforms'
			);
		} else if ( type === 'hcaptcha' && ! validateKeys( 'hcaptcha' ) ) {
			error = __(
				'hCaptcha API keys are not configured. Set them in Global Settings.',
				'sureforms'
			);
		} else if ( type === 'g-recaptcha' ) {
			const version = data._srfm_form_recaptcha;
			const keyTypeMap = {
				'v2-checkbox': 'v2Checkbox',
				'v2-invisible': 'v2Invisible',
				'v3-reCAPTCHA': 'v3',
			};
			if ( ! version ) {
				error = __(
					'Please select a reCAPTCHA version.',
					'sureforms'
				);
			} else if ( ! validateKeys( keyTypeMap[ version ] ) ) {
				error = __(
					'reCAPTCHA API keys for the selected version are not configured. Set them in Global Settings.',
					'sureforms'
				);
			}
		}

		if ( error ) {
			setShowErr( true );
			return error;
		}
		setShowErr( false );
		srfmEditFormMeta(
			'_srfm_captcha_security_type',
			data._srfm_captcha_security_type
		);
		srfmEditFormMeta( '_srfm_form_recaptcha', data._srfm_form_recaptcha );
		return null;
	};

	const onSaveSuccess = () => {
		// Re-baseline from the entity record's `current` meta (synced by
		// `receiveEntityRecords` in TabContentWrapper.handleSave) rather
		// than the editor's edited buffer (which still holds the user's
		// pre-sanitize typed value pushed by `srfmEditFormMeta`).
		// `_srfm_captcha_security_type` and `_srfm_form_recaptcha` use
		// `sanitize_text_field` today — no-op for the known enum values,
		// but reading the canonical post-save value keeps the pattern
		// aligned with FormCustomCss (which IS mutated by `wp_kses_post`)
		// so future sanitizer changes can't quietly re-introduce a
		// save → dirty flip.
		const savedMeta =
			wpSelect( editorStore ).getCurrentPostAttribute( 'meta' ) || {};
		const next = {
			_srfm_captcha_security_type:
				savedMeta._srfm_captcha_security_type ??
				data._srfm_captcha_security_type,
			_srfm_form_recaptcha:
				savedMeta._srfm_form_recaptcha ?? data._srfm_form_recaptcha,
		};
		setData( next );
		setPrevData( next );
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
			tabId="spam_protection"
			showSaveButton={ true }
			validate={ validateBeforeSave }
			isDirty={ isDirty }
			isSaving={ isSaving }
			onSavingChange={ setIsSaving }
			onSaveSuccess={ onSaveSuccess }
		>
			<div className="space-y-6">
				{ /* Security Type */ }
				<div className="space-y-1.5 flex flex-col gap-2">
					<div className="flex flex-col gap-1.5">
						<Label htmlFor="security-type">
							{ __( 'Spam Protection Type', 'sureforms' ) }
						</Label>
						<Select
							value={ data._srfm_captcha_security_type }
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
										data._srfm_captcha_security_type
								)?.label || __( 'None', 'sureforms' ) }
							</Select.Button>
							<Select.Options>
								{ securityTypeOptions.map( ( option ) => (
									<Select.Option
										key={ option.value }
										value={ option.value }
										selected={
											option.value ===
											data._srfm_captcha_security_type
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
							'Select a spam protection service. Configure API keys in Global Settings before enabling.',
							'sureforms'
						) }
					</Label>
				</div>

				{ /* reCAPTCHA versions */ }
				{ data._srfm_captcha_security_type === 'g-recaptcha' && (
					<>
						{ showRecaptchaConflictNotice && (
							<Container className="w-full p-3 gap-2 border border-solid border-alert-border-warning bg-alert-background-warning rounded-lg">
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
											data._srfm_form_recaptcha ===
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
					<div className="flex items-center justify-between gap-3 p-3 bg-[#FEE2E2] border border-solid border-[#FCA5A5] rounded-lg text-sm text-gray-900">
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
							target="_blank"
							rel="noreferrer"
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
