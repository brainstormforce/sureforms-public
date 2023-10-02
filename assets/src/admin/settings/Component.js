import { __ } from '@wordpress/i18n';
// settings icons.
import GeneralIcon from './settingsIcon.js';
import { BaseControl, TabPanel } from '@wordpress/components';
import { useState, useEffect, Fragment } from '@wordpress/element';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

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
			await fetch( '/wp-json/sureforms/v1/sureforms-settings', {
				method: 'POST',
				body: JSON.stringify( formData ),
				headers: {
					'Content-Type': 'application/json',
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
				const response = await fetch(
					'/wp-json/sureforms/v1/sureforms-settings'
				);
				const data = await response.json();

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
				console.error( 'Error fetching data:', error );
			}
		};

		fetchData();
	}, [] );

	if ( 'general-settings' === path ) {
		return (
			<div className="flex justify-center lg:w-[100%] md:w-[80%] w-[70%]">
				<ToastContainer />
				<div className="w-full p-8 bg-[#FBFBFC] rounded-md m-4 h-3/4 overflow-scroll shadow-md mb-8">
					<div
						className="flex gap-2 text-left text-[17.6px] text-[#111827] pb-4"
						style={ {
							borderBottom: '1px solid rgba(229, 231, 235, 1)',
						} }
					>
						<GeneralIcon />
						<span className="font-semibold">
							{ __( 'General Settings', 'sureforms' ) }
						</span>
					</div>
					<form onSubmit={ handleSubmit }>
						<div className="mt-4">
							{ /* Google reCAPTCHA Settings */ }
							<div
								className="mb-4 flex items-start gap-10"
								style={ {
									borderBottom:
										'1px solid rgba(229, 231, 235, 1)',
								} }
							>
								<div className="max-w-[250px]">
									<BaseControl
										help={ __(
											'A CAPTCHA serves as an anti-spam measure, safeguarding your website against spam and misuse.',
											'sureforms'
										) }
									>
										<h3 className="text-base font-semibold text-gray-90">
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
												name: 'sureforms-recaptcha-v2-checkbox',
												title: 'v2 Checkbox',
												className: 'recaptcha-tab',
											},
											{
												name: 'sureforms-recaptcha-v2-invisible',
												title: 'v2 Invisible',
												className: 'recaptcha-tab',
											},
											{
												name: 'sureforms-recaptcha-v3',
												title: 'v3 reCAPTCHA',
												className: 'recaptcha-tab',
											},
										] }
									>
										{ ( tab ) => {
											switch ( tab.title ) {
												case 'v2 Checkbox':
													return (
														<div className="w-[600px] mt-4">
															<Fragment>
																<div className="mb-4 ">
																	<input
																		type="text"
																		name="sureforms_v2_checkbox_site"
																		id="sureforms_v2_checkbox_site"
																		className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
																		placeholder={ __(
																			'Site Key v2 Checkbox',
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
																<div className="mb-4">
																	<input
																		type="text"
																		name="sureforms_v2_checkbox_secret"
																		id="sureforms_v2_checkbox_secret"
																		className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
																		placeholder={ __(
																			'Secret Key v2 Checkbox',
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
														<div className="w-[600px] mt-4">
															<Fragment>
																<div className="mb-4 ">
																	<input
																		type="text"
																		name="sureforms_v2_invisible_site"
																		id="sureforms_v2_invisible_site"
																		className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
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
																<div className="mb-4">
																	<input
																		type="text"
																		name="sureforms_v2_invisible_secret"
																		id="sureforms_v2_invisible_secret"
																		className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
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
														<div className="w-[600px] mt-4">
															<Fragment>
																<div className="mb-4 ">
																	<input
																		type="text"
																		name="sureforms_v3_site"
																		id="sureforms_v3_site"
																		className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
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
																<div className="mb-4">
																	<input
																		type="text"
																		name="sureforms_v3_secret"
																		id="sureforms_v3_secret"
																		className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
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
									<h3 className="text-sm font-normal text-[#64748B]">
										{ __(
											'To enable reCAPTCHA for your form, please follow the steps mentioned ',
											'sureforms'
										) }
										<a
											target="_blank"
											href="https://www.google.com/recaptcha/admin/create"
										>
											here
										</a>
										.
									</h3>
								</div>
							</div>

							{ /* Honeypot Spam Protection Settings might be used later*/ }
							<div className="mb-4 flex items-start gap-10">
								<div className="max-w-[250px]">
									<BaseControl
										help={ __(
											'This adds a hidden field that if filled out prevents the form from submitting.',
											'sureforms'
										) }
									>
										<h3 className="text-base font-semibold text-gray-90">
											{ __(
												'Honeypot Spam Protection',
												'sureforms'
											) }
										</h3>
									</BaseControl>
								</div>
								<div className="w-[600px] mt-4">
									<Fragment>
										<div className="mb-4 ">
											<label
												htmlFor="honeypot-checkbox-input"
												className="toggle-button"
											>
												<input
													id="honeypot-checkbox-input"
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
