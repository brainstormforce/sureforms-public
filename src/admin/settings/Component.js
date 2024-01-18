import { __ } from '@wordpress/i18n';
// settings icons.
import GeneralIcon from './settingsIcon.js';
import { BaseControl, TabPanel } from '@wordpress/components';
import { useState, useEffect, Fragment } from '@wordpress/element';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import apiFetch from '@wordpress/api-fetch';

const Component = ( { path } ) => {
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

	const [ formData, setFormData ] = useState( {} );
	const [ honeyPot, setHoneyPot ] = useState( false );

	const onSelect = () => {};

	const handleChange = ( e ) => {
		const { name, value, type, checked } = e.target;
		const newValue = type === 'checkbox' ? checked : value;

		if ( name === 'sureforms_v2_checkbox_secret' ) {
			setSureformsV2CheckboxSecret( newValue );
			setFormData( () => ( {
				sureforms_v2_checkbox_site: sureformsV2CheckboxSite,
				sureforms_v2_invisible_site: sureformsV2InvisibleSite,
				sureforms_v2_invisible_secret: sureformsV2InvisibleSecret,
				sureforms_v3_site: sureformsV3Site,
				sureforms_v3_secret: sureformsV3Secret,
				honeypot_toggle: honeyPot,
				[ name ]: newValue,
			} ) );
		} else if ( name === 'sureforms_v2_checkbox_site' ) {
			setSureformsV2CheckboxSite( newValue );
			setFormData( () => ( {
				sureforms_v2_checkbox_secret: sureformsV2CheckboxSecret,
				sureforms_v2_invisible_site: sureformsV2InvisibleSite,
				sureforms_v2_invisible_secret: sureformsV2InvisibleSecret,
				sureforms_v3_site: sureformsV3Site,
				sureforms_v3_secret: sureformsV3Secret,
				honeypot_toggle: honeyPot,
				[ name ]: newValue,
			} ) );
		} else if ( name === 'sureforms_v2_invisible_secret' ) {
			setSureformsV2InvisibleSecret( newValue );
			setFormData( () => ( {
				sureforms_v2_invisible_site: sureformsV2InvisibleSite,
				sureforms_v2_checkbox_site: sureformsV2CheckboxSite,
				sureforms_v2_checkbox_secret: sureformsV2CheckboxSecret,
				sureforms_v3_site: sureformsV3Site,
				sureforms_v3_secret: sureformsV3Secret,
				honeypot_toggle: honeyPot,
				[ name ]: newValue,
			} ) );
		} else if ( name === 'sureforms_v2_invisible_site' ) {
			setSureformsV2InvisibleSite( newValue );
			setFormData( () => ( {
				sureforms_v2_invisible_secret: sureformsV2InvisibleSecret,
				sureforms_v2_checkbox_site: sureformsV2CheckboxSite,
				sureforms_v2_checkbox_secret: sureformsV2CheckboxSecret,
				sureforms_v3_site: sureformsV3Site,
				sureforms_v3_secret: sureformsV3Secret,
				honeypot_toggle: honeyPot,
				[ name ]: newValue,
			} ) );
		} else if ( name === 'sureforms_v3_secret' ) {
			setSureformsV3Secret( newValue );
			setFormData( () => ( {
				sureforms_v2_invisible_secret: sureformsV2InvisibleSecret,
				sureforms_v2_checkbox_site: sureformsV2CheckboxSite,
				sureforms_v2_checkbox_secret: sureformsV2CheckboxSecret,
				sureforms_v3_site: sureformsV3Site,
				sureforms_v2_invisible_site: sureformsV2InvisibleSite,
				honeypot_toggle: honeyPot,
				[ name ]: newValue,
			} ) );
		} else if ( name === 'sureforms_v3_site' ) {
			setSureformsV3Site( newValue );
			setFormData( () => ( {
				sureforms_v2_invisible_secret: sureformsV2InvisibleSecret,
				sureforms_v2_checkbox_site: sureformsV2CheckboxSite,
				sureforms_v2_checkbox_secret: sureformsV2CheckboxSecret,
				sureforms_v3_secret: sureformsV3Secret,
				sureforms_v2_invisible_site: sureformsV2InvisibleSite,
				honeypot_toggle: honeyPot,
				[ name ]: newValue,
			} ) );
		} else if ( name === 'honeypot_toggle' ) {
			setHoneyPot( ! honeyPot );
			setFormData( () => ( {
				sureforms_v2_invisible_site: sureformsV2InvisibleSite,
				sureforms_v2_invisible_secret: sureformsV2InvisibleSecret,
				sureforms_v2_checkbox_site: sureformsV2CheckboxSite,
				sureforms_v2_checkbox_secret: sureformsV2CheckboxSecret,
				sureforms_v3_site: sureformsV3Site,
				sureforms_v3_secret: sureformsV3Secret,
				[ name ]: newValue,
			} ) );
		}
	};

	const handleSubmit = async ( e ) => {
		e.preventDefault();
		try {
			await apiFetch( {
				path: 'sureforms/v1/srfm-settings',
				method: 'POST',
				body: JSON.stringify( formData ),
				headers: {
					'content-type': 'application/json',
				},
			} );
			toast.success( __( 'Settings Saved Successfully!', 'sureforms' ), {
				position: 'bottom-right',
				hideProgressBar: true,
			} );
		} catch ( error ) {
			toast.error( __( 'Error Saving Settings!', 'sureforms' ), {
				position: 'bottom-right',
				hideProgressBar: true,
			} );
			console.error( error );
		}
	};

	useEffect( () => {
		const fetchData = async () => {
			try {
				const data = await apiFetch( {
					path: 'sureforms/v1/srfm-settings',
					method: 'GET',
					headers: {
						'content-type': 'application/json',
					},
				} );

				if ( data ) {
					setSureformsV2CheckboxSecret(
						data.sureforms_v2_checkbox_secret &&
							data.sureforms_v2_checkbox_secret
					);
					setSureformsV2CheckboxSite(
						data.sureforms_v2_checkbox_site &&
							data.sureforms_v2_checkbox_site
					);
					setSureformsV2InvisibleSecret(
						data.sureforms_v2_invisible_secret &&
							data.sureforms_v2_invisible_secret
					);
					setSureformsV2InvisibleSite(
						data.sureforms_v2_invisible_site &&
							data.sureforms_v2_invisible_site
					);
					setSureformsV3Secret(
						data.sureforms_v3_secret && data.sureforms_v3_secret
					);
					setSureformsV3Site(
						data.sureforms_v3_site && data.sureforms_v3_site
					);
					setHoneyPot( data.honeypot && data.honeypot );
				}
			} catch ( error ) {
				console.error( 'Error fetching datates:', error );
			}
		};

		fetchData();
	}, [] );

	if ( 'general-settings' === path ) {
		return (
			<div className="srfm-flex srfm-justify-center lg:srfm-w-[100%] md:srfm-w-[80%] srfm-w-[70%]">
				<ToastContainer />
				<div className="srfm-w-full srfm-p-8 srfm-bg-[#FBFBFC] srfm-rounded-md srfm-m-4 srfm-h-3/4 srfm-overflow-scroll srfm-shadow-md srfm-mb-8">
					<div
						className="srfm-flex srfm-gap-2 srfm-text-left srfm-text-[17.6px] srfm-text-[#111827] srfm-pb-4"
						style={ {
							borderBottom: '1px solid rgba(229, 231, 235, 1)',
						} }
					>
						<GeneralIcon />
						<span className="srfm-font-semibold">
							{ __( 'General Settings', 'sureforms' ) }
						</span>
					</div>
					<form onSubmit={ handleSubmit }>
						<div className="srfm-mt-4">
							{ /* Google reCAPTCHA Settings */ }
							<div
								className="srfm-mb-4 srfm-flex srfm-items-start srfm-gap-10"
								style={ {
									borderBottom:
										'1px solid rgba(229, 231, 235, 1)',
								} }
							>
								<div className="srfm-max-w-[250px]">
									<BaseControl
										help={ __(
											'A CAPTCHA serves as an anti-spam measure, safeguarding your website against spam and misuse.',
											'sureforms'
										) }
									>
										<h3 className="srfm-text-base srfm-font-semibold srfm-text-gray-90">
											{ __(
												'Google reCAPTCHA Settings',
												'sureforms'
											) }
										</h3>
									</BaseControl>
								</div>
								<div>
									<TabPanel
										activeClass="active-recaptcha"
										onSelect={ ( tab ) => onSelect( tab ) }
										tabs={ [
											{
												name: 'srfm-recaptcha-v2-checkbox',
												title: 'v2 Checkbox',
												className: 'recaptcha-tab',
											},
											{
												name: 'srfm-recaptcha-v2-invisible',
												title: 'v2 Invisible',
												className: 'recaptcha-tab',
											},
											{
												name: 'srfm-recaptcha-v3',
												title: 'v3 reCAPTCHA',
												className: 'recaptcha-tab',
											},
										] }
									>
										{ ( tab ) => {
											switch ( tab.title ) {
												case 'v2 Checkbox':
													return (
														<div className="srfm-w-[600px] srfm-mt-4">
															<Fragment>
																<div className="srfm-mb-4 ">
																	<input
																		type="text"
																		name="sureforms_v2_checkbox_site"
																		id="srfm_v2_checkbox_site"
																		className="srfm-block srfm-w-full srfm-rounded-md srfm-border-0 srfm-py-1.5 srfm-text-gray-900 srfm-shadow-sm srfm-ring-1 srfm-ring-inset srfm-ring-gray-300 placeholder:srfm-text-gray-400 focus:srfm-ring-2 focus:srfm-ring-inset focus:srfm-ring-indigo-600 sm:srfm-text-sm sm:srfm-leading-6"
																		placeholder={ __(
																			'Site Key v2 checkbox',
																			'sureforms'
																		) }
																		onChange={
																			handleChange
																		}
																		value={
																			sureformsV2CheckboxSite
																		}
																	/>
																</div>
															</Fragment>
															<Fragment>
																<div className="srfm-mb-4">
																	<input
																		type="text"
																		name="sureforms_v2_checkbox_secret"
																		id="srfm_v2_checkbox_secret"
																		className="srfm-block srfm-w-full srfm-rounded-md srfm-border-0 srfm-py-1.5 srfm-text-gray-900 srfm-shadow-sm srfm-ring-1 srfm-ring-inset srfm-ring-gray-300 placeholder:srfm-text-gray-400 focus:srfm-ring-2 focus:srfm-ring-inset focus:srfm-ring-indigo-600 sm:srfm-text-sm sm:srfm-leading-6"
																		placeholder={ __(
																			'Secret Key v2 checkbox',
																			'sureforms'
																		) }
																		onChange={
																			handleChange
																		}
																		value={
																			sureformsV2CheckboxSecret
																		}
																	/>
																</div>
															</Fragment>
														</div>
													);
												case 'v2 Invisible':
													return (
														<div className="srfm-w-[600px] srfm-mt-4">
															<Fragment>
																<div className="srfm-mb-4 ">
																	<input
																		type="text"
																		name="sureforms_v2_invisible_site"
																		id="srfm_v2_invisible_site"
																		className="srfm-block srfm-w-full srfm-rounded-md srfm-border-0 srfm-py-1.5 srfm-text-gray-900 srfm-shadow-sm srfm-ring-1 srfm-ring-inset srfm-ring-gray-300 placeholder:srfm-text-gray-400 focus:srfm-ring-2 focus:srfm-ring-inset focus:srfm-ring-indigo-600 sm:srfm-text-sm sm:srfm-leading-6"
																		placeholder={ __(
																			'Site Key v2 Invisible',
																			'sureforms'
																		) }
																		onChange={
																			handleChange
																		}
																		value={
																			sureformsV2InvisibleSite
																		}
																	/>
																</div>
															</Fragment>
															<Fragment>
																<div className="srfm-mb-4">
																	<input
																		type="text"
																		name="sureforms_v2_invisible_secret"
																		id="srfm_v2_invisible_secret"
																		className="srfm-block srfm-w-full srfm-rounded-md srfm-border-0 srfm-py-1.5 srfm-text-gray-900 srfm-shadow-sm srfm-ring-1 srfm-ring-inset srfm-ring-gray-300 placeholder:srfm-text-gray-400 focus:srfm-ring-2 focus:srfm-ring-inset focus:srfm-ring-indigo-600 sm:srfm-text-sm sm:srfm-leading-6"
																		placeholder={ __(
																			'Secret Key v2 Invisible',
																			'sureforms'
																		) }
																		onChange={
																			handleChange
																		}
																		value={
																			sureformsV2InvisibleSecret
																		}
																	/>
																</div>
															</Fragment>
														</div>
													);
												case 'v3 reCAPTCHA':
													return (
														<div className="srfm-w-[600px] srfm-mt-4">
															<Fragment>
																<div className="srfm-mb-4 ">
																	<input
																		type="text"
																		name="sureforms_v3_site"
																		id="srfm_v3_site"
																		className="srfm-block srfm-w-full srfm-rounded-md srfm-border-0 srfm-py-1.5 srfm-text-gray-900 srfm-shadow-sm srfm-ring-1 srfm-ring-inset srfm-ring-gray-300 placeholder:srfm-text-gray-400 focus:srfm-ring-2 focus:srfm-ring-inset focus:srfm-ring-indigo-600 sm:srfm-text-sm sm:srfm-leading-6"
																		placeholder={ __(
																			'Site Key v3',
																			'sureforms'
																		) }
																		onChange={
																			handleChange
																		}
																		value={
																			sureformsV3Site
																		}
																	/>
																</div>
															</Fragment>
															<Fragment>
																<div className="srfm-mb-4">
																	<input
																		type="text"
																		name="sureforms_v3_secret"
																		id="srfm_v3_secret"
																		className="srfm-block srfm-w-full srfm-rounded-md srfm-border-0 srfm-py-1.5 srfm-text-gray-900 srfm-shadow-sm srfm-ring-1 srfm-ring-inset srfm-ring-gray-300 placeholder:srfm-text-gray-400 focus:srfm-ring-2 focus:srfm-ring-inset focus:srfm-ring-indigo-600 sm:srfm-text-sm sm:srfm-leading-6"
																		placeholder={ __(
																			'Secret Key v3',
																			'sureforms'
																		) }
																		onChange={
																			handleChange
																		}
																		value={
																			sureformsV3Secret
																		}
																	/>
																</div>
															</Fragment>
														</div>
													);

												default:
													return null;
											}
										} }
									</TabPanel>
									<h3 className="srfm-text-sm srfm-font-normal srfm-text-[#64748B]">
										{ __(
											'To enable reCAPTCHA for your form, please follow the steps mentioned ',
											'sureforms'
										) }
										<a
											target="_blank"
											href="https://www.google.com/recaptcha/admin/create" rel="noreferrer"
										>
											here
										</a>
										.
									</h3>
								</div>
							</div>

							{ /* Honeypot Spam Protection Settings might be used later*/ }
							<div className="srfm-mb-4 srfm-flex srfm-items-start srfm-gap-10">
								<div className="srfm-max-w-[250px]">
									<BaseControl
										help={ __(
											'This adds a hidden field that if filled out prevents the form from submitting.',
											'sureforms'
										) }
									>
										<h3 className="srfm-text-base srfm-font-semibold srfm-text-gray-90">
											{ __(
												'Honeypot Spam Protection',
												'sureforms'
											) }
										</h3>
									</BaseControl>
								</div>
								<div className="srfm-w-[600px] srfm-mt-4">
									<Fragment>
										<div className="srfm-mb-4 ">
											<label
												htmlFor="srfm-honeypot-checkbox-input"
												className="toggle-button"
											>
												<input
													id="srfm-honeypot-checkbox-input"
													type="checkbox"
													name="honeypot_toggle"
													checked={ honeyPot }
													onChange={ handleChange }
												/>
												<span className="slider"></span>
											</label>
										</div>
									</Fragment>
								</div>
							</div>
						</div>
						<button type="submit" className="button-primary">
							{ __( ' Save', 'sureforms' ) }
						</button>
					</form>
				</div>
			</div>
		);
	}
	return null;
};

export default Component;
