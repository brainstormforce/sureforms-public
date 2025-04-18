import { __ } from '@wordpress/i18n';
import { TextControl, TabPanel, ExternalLink, ToggleControl } from '@wordpress/components';

import ContentSection from '../components/ContentSection';

const SecurityPage = ( {
	loading,
	securitytabOptions,
	updateGlobalSettings,
} ) => {
	const onSelect = () => {
		return false;
	};

	const sureformsURL = 'https://sureforms.com';

	const buttonData = [
		{ mode: 'auto', label: __( 'Auto', 'sureforms' ) },
		{ mode: 'light', label: __( 'Light', 'sureforms' ) },
		{ mode: 'dark', label: __( 'Dark', 'sureforms' ) },
	];

	const captchaContent = () => {
		const is_ver_lower_than_6_7 = srfm_admin?.is_ver_lower_than_6_7;

		return (
			<>
				<TabPanel
					className="srfm-style-1-tabs"
					activeClass="active-tab"
					onSelect={ onSelect }
					tabs={ [
						{
							name: 'srfm-captcha-tab-1',
							title: __( 'reCAPTCHA', 'sureforms' ),
							className: 'srfm-captcha-tab-1',
						},
						{
							name: 'srfm-captcha-tab-2',
							title: __( 'Turnstile', 'sureforms' ),
							className: 'srfm-captcha-tab-2',
						},
						{
							name: 'srfm-captcha-tab-3',
							title: __( 'hCaptcha', 'sureforms' ),
							className: 'srfm-captcha-tab-3',
						},
						{
							name: 'srfm-honeypot-tab-4',
							title: __( 'Honeypot', 'sureforms' ),
							className: 'srfm-honeypot-tab-4',
						},
					] }
				>
					{ ( securityTab ) => {
						switch ( securityTab.name ) {
							case 'srfm-captcha-tab-1':
								return (
									<>
										<div className="srfm-sub-section-heading">
											<h2>
												{ __(
													'Google reCAPTCHA',
													'sureforms'
												) }
											</h2>
											<p>
												{
													__(
														'To enable Google reCAPTCHA, please add your site key and secret key. Configure these settings within the individual form. ',
														'sureforms'
													)
												}
												<ExternalLink href={ `${ sureformsURL }/docs/enable-google-recaptcha/` } target="_blank" rel="noreferrer" className="srfm-block-url" >
													{
														__(
															'Learn more', 'sureforms'
														)
													}
												</ExternalLink>
											</p>
											<div className="srfm-link">
												<a
													href="https://www.google.com/recaptcha/admin/create"
													target="_blank"
													rel="noreferrer"
												>
													{ __(
														'Get Keys',
														'sureforms'
													) }
												</a>
												<a
													href="https://developers.google.com/recaptcha/intro"
													target="_blank"
													rel="noreferrer"
												>
													{ __(
														'Documentation',
														'sureforms'
													) }
												</a>
											</div>
										</div>
										<div className="srfm-sub-section-content">
											<div
												className="components-base-control srfm-components-input-control css-qy3gpb ej5x27r4"
												style={ {
													marginBottom: is_ver_lower_than_6_7 ? '0' : '.43em',
													fontWeight: is_ver_lower_than_6_7 ? 'normal' : '500',
												} }
											>
												<div className="components-base-control__field">
													<label
														className="components-base-control__label css-1v57ksj ej5x27r2"
														htmlFor="srfm-style-2-tabs"
													>
														{ __(
															'reCAPTCHA Type',
															'sureforms'
														) }
													</label>
												</div>
											</div>
											<TabPanel
												className="srfm-style-2-tabs"
												activeClass="active-tab"
												onSelect={ onSelect }
												tabs={ [
													{
														name: 'srfm-captcha-tab-1',
														title: __(
															'v2 Checkbox',
															'sureforms'
														),
														className:
															'srfm-captcha-tab-1',
													},
													{
														name: 'srfm-captcha-tab-2',
														title: __(
															'v2 Invisible',
															'sureforms'
														),
														className:
															'srfm-captcha-tab-2',
													},
													{
														name: 'srfm-captcha-tab-3',
														title: __(
															'v3 reCAPTCHA',
															'sureforms'
														),
														className:
															'srfm-captcha-tab-3',
													},
												] }
											>
												{ ( recaptchaTab ) => {
													switch (
														recaptchaTab.name
													) {
														case 'srfm-captcha-tab-1':
															return (
																<>
																	<TextControl
																		label={ __(
																			'Site Key',
																			'sureforms'
																		) }
																		type="text"
																		className="srfm-components-input-control"
																		value={
																			securitytabOptions.srfm_v2_checkbox_site_key
																		}
																		placeholder={ __(
																			'Enter your site key here',
																			'sureforms'
																		) }
																		onChange={ (
																			value
																		) => {
																			updateGlobalSettings(
																				'srfm_v2_checkbox_site_key',
																				value,
																				'security-settings'
																			);
																		} }
																	/>
																	<TextControl
																		label={ __(
																			'Secret Key',
																			'sureforms'
																		) }
																		type="password"
																		className="srfm-components-input-control"
																		value={
																			securitytabOptions.srfm_v2_checkbox_secret_key
																		}
																		onChange={ (
																			value
																		) => {
																			updateGlobalSettings(
																				'srfm_v2_checkbox_secret_key',
																				value,
																				'security-settings'
																			);
																		} }
																		placeholder={ __(
																			'Enter your secret key here',
																			'sureforms'
																		) }
																	/>
																</>
															);
														case 'srfm-captcha-tab-2':
															return (
																<>
																	<TextControl
																		label={ __(
																			'Site Key',
																			'sureforms'
																		) }
																		type="text"
																		className="srfm-components-input-control"
																		value={
																			securitytabOptions.srfm_v2_invisible_site_key
																		}
																		onChange={ (
																			value
																		) => {
																			updateGlobalSettings(
																				'srfm_v2_invisible_site_key',
																				value,
																				'security-settings'
																			);
																		} }
																		placeholder={ __(
																			'Enter your site key here',
																			'sureforms'
																		) }
																	/>
																	<TextControl
																		label={ __(
																			'Secret Key',
																			'sureforms'
																		) }
																		type="password"
																		className="srfm-components-input-control"
																		value={
																			securitytabOptions.srfm_v2_invisible_secret_key
																		}
																		onChange={ (
																			value
																		) => {
																			updateGlobalSettings(
																				'srfm_v2_invisible_secret_key',
																				value,
																				'security-settings'
																			);
																		} }
																		placeholder={ __(
																			'Enter your secret key here',
																			'sureforms'
																		) }
																	/>
																</>
															);
														case 'srfm-captcha-tab-3':
															return (
																<>
																	<TextControl
																		label={ __(
																			'Site Key',
																			'sureforms'
																		) }
																		type="text"
																		className="srfm-components-input-control"
																		value={
																			securitytabOptions.srfm_v3_site_key
																		}
																		onChange={ (
																			value
																		) => {
																			updateGlobalSettings(
																				'srfm_v3_site_key',
																				value,
																				'security-settings'
																			);
																		} }
																		placeholder={ __(
																			'Enter your site key here',
																			'sureforms'
																		) }
																	/>
																	<TextControl
																		label={ __(
																			'Secret Key',
																			'sureforms'
																		) }
																		type="password"
																		className="srfm-components-input-control"
																		value={
																			securitytabOptions.srfm_v3_secret_key
																		}
																		onChange={ (
																			value
																		) => {
																			updateGlobalSettings(
																				'srfm_v3_secret_key',
																				value,
																				'security-settings'
																			);
																		} }
																		placeholder={ __(
																			'Enter your secret key here',
																			'sureforms'
																		) }
																	/>
																</>
															);
													}
												} }
											</TabPanel>
										</div>
									</>
								);
							case 'srfm-captcha-tab-2':
								return (
									<>
										<div className="srfm-sub-section-heading">
											<h2>
												{ __(
													'Cloudflare Turnstile',
													'sureforms'
												) }
											</h2>
											<p>
												{
													__(
														'To enable Cloudflare Turnstile, please add your site key and secret key. Configure these settings within the individual form. ',
														'sureforms'
													)
												}

												<ExternalLink href={ `${ sureformsURL }/docs/cloudflare-turnstile/` } target="_blank" rel="noreferrer" className="srfm-block-url" >
													{
														__(
															'Learn more', 'sureforms'
														)
													}
												</ExternalLink>
											</p>
											<div className="srfm-link">
												<a
													href="https://www.cloudflare.com/en-gb/products/turnstile/"
													target="_blank"
													rel="noreferrer"
												>
													{ __(
														'Get Keys',
														'sureforms'
													) }
												</a>
												<a
													href="https://developers.cloudflare.com/turnstile/get-started/"
													target="_blank"
													rel="noreferrer"
												>
													{ __(
														'Documentation',
														'sureforms'
													) }
												</a>
											</div>
										</div>
										<div className="srfm-sub-section-content">
											<div className="components-base-control srfm-components-input-control css-qy3gpb ej5x27r4" style={ {
												marginBottom: is_ver_lower_than_6_7 ? '0' : '.43em',
												fontWeight: is_ver_lower_than_6_7 ? 'normal' : '500',
											} }>
												<div className="components-base-control__field ej5x27r3"style={ {
													marginBottom: is_ver_lower_than_6_7 ? '0' : '.43em',
													fontWeight: is_ver_lower_than_6_7 ? 'normal' : '500',
												} }>
													<label
														className="components-base-control__label css-1v57ksj ej5x27r2"
														htmlFor="inspector-text-control-2"
													>
														{ __(
															'Appearance Mode',
															'sureforms'
														) }
													</label>

												</div>
												<div className="srfm-style-2-tabs">
													<div
														role="tablist"
														aria-orientation="horizontal"
														className="components-tab-panel__tabs"
													>
														{ buttonData.map(
															(
																button,
																index
															) => (
																<button
																	key={
																		index
																	}
																	type="button"
																	aria-selected={
																		button.mode ===
																			securitytabOptions.srfm_cf_appearance_mode
																	}
																	id={ `tab-panel-2-${ button.mode }` }
																	role="tab"
																	aria-controls={ `tab-panel-2-${ button.mode }-view` }
																	className={ `components-button components-tab-panel__tabs-item srfm-captcha-tab-${
																		index +
																			1
																	} ${
																		button.mode ===
																			securitytabOptions.srfm_cf_appearance_mode
																			? 'active-tab'
																			: ''
																	}` }
																	onClick={ () => {
																		updateGlobalSettings(
																			'srfm_cf_appearance_mode',
																			button.mode,
																			'security-settings'
																		);
																	} }
																>
																	{
																		button.label
																	}
																</button>
															)
														) }
													</div>
													<div
														id={ `tab-panel-2-${ securitytabOptions.srfm_cf_appearance_mode }-view` }
														role="tabpanel"
														aria-labelledby={ `tab-panel-2-${ securitytabOptions.srfm_cf_appearance_mode }` }
														className="components-tab-panel__tab-content"
														tabIndex="0"
													></div>
												</div>
											</div>

											<TextControl
												label={ __(
													'Site Key',
													'sureforms'
												) }
												type="text"
												className="srfm-components-input-control"
												value={
													securitytabOptions.srfm_cf_turnstile_site_key
												}
												onChange={ ( value ) => {
													updateGlobalSettings(
														'srfm_cf_turnstile_site_key',
														value,
														'security-settings'
													);
												} }
												placeholder={ __(
													'Enter your site key here',
													'sureforms'
												) }
											/>
											<TextControl
												label={ __(
													'Secret Key',
													'sureforms'
												) }
												type="password"
												className="srfm-components-input-control"
												value={
													securitytabOptions.srfm_cf_turnstile_secret_key
												}
												onChange={ ( value ) => {
													updateGlobalSettings(
														'srfm_cf_turnstile_secret_key',
														value,
														'security-settings'
													);
												} }
												placeholder={ __(
													'Enter your secret key here',
													'sureforms'
												) }
											/>
										</div>
									</>
								);
							case 'srfm-captcha-tab-3':
								return (
									<>
										<div className="srfm-sub-section-heading">
											<h2>
												{ __(
													'hCaptcha',
													'sureforms'
												) }
											</h2>
											<p>
												{
													__(
														'To enable hCAPTCHA, please add your site key and secret key. Configure these settings within the individual form. ',
														'sureforms'
													)
												}
												<ExternalLink href={ `${ sureformsURL }/docs/hcaptcha/` } target="_blank" rel="noreferrer" className="srfm-block-url" >
													{
														__(
															'Learn more', 'sureforms'
														)
													}
												</ExternalLink>
											</p>
											<div className="srfm-link">
												<a
													href="https://dashboard.hcaptcha.com/overview"
													target="_blank"
													rel="noreferrer"
												>
													{ __(
														'Get Keys',
														'sureforms'
													) }
												</a>
												<a
													href="https://docs.hcaptcha.com/"
													target="_blank"
													rel="noreferrer"
												>
													{ __(
														'Documentation',
														'sureforms'
													) }
												</a>
											</div>
										</div>
										<div className="srfm-sub-section-content">
											<TextControl
												label={ __(
													'Site Key',
													'sureforms'
												) }
												type="text"
												className="srfm-components-input-control"
												value={
													securitytabOptions.srfm_hcaptcha_site_key
												}
												onChange={ ( value ) => {
													updateGlobalSettings(
														'srfm_hcaptcha_site_key',
														value,
														'security-settings'
													);
												} }
												placeholder={ __(
													'Enter your site key here',
													'sureforms'
												) }
											/>
											<TextControl
												label={ __(
													'Secret Key',
													'sureforms'
												) }
												type="password"
												className="srfm-components-input-control"
												value={
													securitytabOptions.srfm_hcaptcha_secret_key
												}
												onChange={ ( value ) => {
													updateGlobalSettings(
														'srfm_hcaptcha_secret_key',
														value,
														'security-settings'
													);
												} }
												placeholder={ __(
													'Enter your secret key here',
													'sureforms'
												) }
											/>
										</div>
									</>
								);
							case 'srfm-honeypot-tab-4':
								return (
									<>
										<div className="srfm-sub-section-heading">
											<h2>
												{ __(
													'Honeypot',
													'sureforms'
												) }
											</h2>
										</div>
										<div className="srfm-sub-section-content">
											<ToggleControl
												label={ __( 'Enable Honeypot Security', 'sureforms' ) }
												help={ __(
													'Enable Honeypot Security for better spam protection',
													'sureforms'
												) }
												checked={ securitytabOptions.srfm_honeypot }
												onChange={ ( value ) =>
													updateGlobalSettings(
														'srfm_honeypot',
														value,
														'security-settings'
													)
												}
											/>
										</div>
									</>
								);
						}
					} }
				</TabPanel>
			</>
		);
	};

	return (
		<ContentSection
			loading={ loading }
			title={ __( 'Captcha', 'sureforms' ) }
			content={ captchaContent() }
		/>
	);
};

export default SecurityPage;
