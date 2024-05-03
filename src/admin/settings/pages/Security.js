import { __ } from '@wordpress/i18n';
import { TextControl, TabPanel } from '@wordpress/components';

import ContentSection from '../components/ContentSection';

const SecurityPage = ( {
	loading,
	securitytabOptions,
	updateGlobalSettings,
} ) => {
	const onSelect = () => {
		return false;
	};

	const buttonData = [
		{ mode: 'auto', label: __( 'Auto', 'sureforms' ) },
		{ mode: 'light', label: __( 'Light', 'sureforms' ) },
		{ mode: 'dark', label: __( 'Dark', 'sureforms' ) },
	];

	const captchaContent = () => {
		return (
			<>
				<TabPanel
					className="srfm-style-1-tabs"
					activeClass="active-tab"
					onSelect={ onSelect }
					tabs={ [
						{
							name: 'srfm-captcha-tab-1',
							title: __( 'reCaptcha', 'sureforms' ),
							className: 'srfm-captcha-tab-1',
						},
						{
							name: 'srfm-captcha-tab-2',
							title: __( 'Turnstile', 'sureforms' ),
							className: 'srfm-captcha-tab-2',
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
													'Google reCaptcha',
													'sureforms'
												) }
											</h2>
											<p>
												{ __(
													'To enable the reCAPTCHA feature on your SureForms, Please select the Security type as Google reCAPTCHA in the form settings and select the version you want to use. Add Google reCAPTCHA secret and site key here. reCAPTCHA will be added to your page on the front end.',
													'sureforms'
												) }
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
															'v3 reCaptcha',
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
																			'Enter your site key here',
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
																			'Enter your site key here',
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
																			'Enter your site key here',
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
												{ __(
													'To enable the Turnstile feature on your SureForms, Please select the Security type as Cloudflare Turnstile in the form settings. Add Cloudflare Turnstile secret and site key here. Turnstile will be added to your page on the front end.',
													'sureforms'
												) }
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
											<div className="components-base-control srfm-components-input-control css-qy3gpb ej5x27r4">
												<div className="components-base-control__field css-1t5ousf ej5x27r3">
													<label
														className="components-base-control__label css-1v57ksj ej5x27r2"
														htmlFor="inspector-text-control-2"
													>
														{ __(
															'Appearance Mode',
															'sureforms'
														) }
													</label>
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
													'Enter your site key here',
													'sureforms'
												) }
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
