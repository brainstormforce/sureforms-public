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
							title: 'reCaptcha',
							className: 'srfm-captcha-tab-1',
						},
						{
							name: 'srfm-captcha-tab-2',
							title: 'Turnstile',
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
													'To enable reCAPTCHA feature on your SureForms, Please enable reCAPTCHA option on your blocks setting and select version. Add google reCAPTCHA secret and site key here. reCAPTCHA will be added to your page on front-end.',
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
												<a href="/" target="_blank">
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
														title: 'v2 Checkbox',
														className:
															'srfm-captcha-tab-1',
													},
													{
														name: 'srfm-captcha-tab-2',
														title: 'v2 Invisible',
														className:
															'srfm-captcha-tab-2',
													},
													{
														name: 'srfm-captcha-tab-3',
														title: 'v3 reCaptcha',
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
																		type="text"
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
																		type="text"
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
																		type="text"
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
													'To enable Turnstile feature on your SureForms, Please enable Turnstile option on your blocks setting. Add Cloudflare Turnstile secret and site key here. Turnstile will be added to your page on front-end.',
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
												<a href="/" target="_blank">
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
												onSelect={ () => {
													updateGlobalSettings(
														'srfm_cf_appearance_mode',
														value,
														'security-settings'
													);
												} }
												tabs={ [
													{
														name: 'srfm-captcha-tab-1',
														title: 'Auto',
														className:
															'srfm-captcha-tab-1',
													},
													{
														name: 'srfm-captcha-tab-2',
														title: 'Light',
														className:
															'srfm-captcha-tab-2',
													},
													{
														name: 'srfm-captcha-tab-3',
														title: 'Dark',
														className:
															'srfm-captcha-tab-3',
													},
												] }
											>
												{ ( appearanceTab ) => {} }
											</TabPanel>
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
