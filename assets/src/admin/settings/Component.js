import { __ } from '@wordpress/i18n';
import { GoSettings } from 'react-icons/go';
import { BaseControl } from '@wordpress/components';
import { useState, useEffect, Fragment } from '@wordpress/element';
import axios from 'axios';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

const Component = ( { path } ) => {
	const [ googleCaptchaSecret, setGoogleCaptchaSecret ] = useState( '' );
	const [ googleCaptchaKey, setGoogleCaptchaKey ] = useState( '' );

	const [ formData, setFormData ] = useState( {} );
	const [ honeyPot, setHoneyPot ] = useState( false );

	const handleChange = ( e ) => {
		const { name, value, type, checked } = e.target;
		const newValue = type === 'checkbox' ? checked : value;

		if ( name === 'google_captcha_secret_key' ) {
			setGoogleCaptchaSecret( newValue );
			setFormData( () => ( {
				google_captcha_site_key: googleCaptchaKey,
				honeypot_toggle: honeyPot,
				[ name ]: newValue,
			} ) );
		} else if ( name === 'google_captcha_site_key' ) {
			setGoogleCaptchaKey( newValue );
			setFormData( () => ( {
				google_captcha_secret_key: googleCaptchaSecret,
				honeypot_toggle: honeyPot,
				[ name ]: newValue,
			} ) );
		} else if ( name === 'honeypot_toggle' ) {
			setHoneyPot( ! honeyPot );
			setFormData( () => ( {
				google_captcha_site_key: googleCaptchaKey,
				google_captcha_secret_key: googleCaptchaSecret,
				[ name ]: newValue,
			} ) );
		}
	};
	// Used Axios for now to simplify things & toast will change to default WordPress Notices.
	const handleSubmit = async ( e ) => {
		e.preventDefault();

		try {
			await axios.post(
				'/wp-json/sureforms/v1/sureforms-settings',
				formData
			);
			toast( __( 'Settings Saved Successfully!', 'sureforms' ) );
		} catch ( error ) {
			toast( __( 'Error Saving Settings!', 'sureforms' ) );
			console.error( error );
		}
	};

	useEffect( () => {
		const fetchData = async () => {
			try {
				const response = await axios.get(
					'/wp-json/sureforms/v1/sureforms-settings'
				);
				const data = response.data;

				if ( data ) {
					setGoogleCaptchaSecret(
						data.secret_key && data.secret_key
					);
					setGoogleCaptchaKey( data.sitekey && data.sitekey );
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
			<div className="flex justify-center w-[100%]">
				<ToastContainer />
				<div className="w-full p-8 bg-[#FBFBFC] rounded-md m-4 h-3/4 overflow-scroll shadow-md mb-8">
					<div
						className="flex gap-2 text-left text-[17.6px] text-[#111827] pb-4"
						style={ {
							borderBottom: '1px solid rgba(229, 231, 235, 1)',
						} }
					>
						<GoSettings />
						<span className="font-semibold">
							{ __( 'General Settings', 'sureforms' ) }
						</span>
					</div>
					<form onSubmit={ handleSubmit }>
						<div className="mt-4">
							{ /* Google ReCaptcha Settings */ }
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
											'Please add the API key for Google ReCaptcha',
											'sureforms'
										) }
									>
										<h3 className="text-base font-semibold text-gray-90">
											{ __(
												'Google ReCaptcha Settings',
												'sureforms'
											) }
										</h3>
									</BaseControl>
								</div>
								<div className="w-[600px] mt-4">
									<Fragment>
										<label
											htmlFor="google_captcha_site_key"
											className="block text-sm font-medium text-[#828282] mb-1"
										>
											{ __( 'Site key', 'sureforms' ) }
										</label>
										<div className="mb-4 ">
											<input
												type="text"
												name="google_captcha_site_key"
												id="google_captcha_site_key"
												className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
												placeholder="6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-"
												onChange={ handleChange }
												value={ googleCaptchaKey }
											/>
										</div>
									</Fragment>
									<Fragment>
										<label
											htmlFor="google_captcha_secret_key"
											className="block text-sm font-medium text-[#828282] mb-1"
										>
											{ __( 'Secret key', 'sureforms' ) }
										</label>
										<div className="mb-4">
											<input
												type="text"
												name="google_captcha_secret_key"
												id="google_captcha_secret_key"
												className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
												placeholder="6Le-wvkSAAAAAB3hT6bXlRTaRhfMxJ1vjRog4UIR"
												onChange={ handleChange }
												value={ googleCaptchaSecret }
											/>
										</div>
									</Fragment>
								</div>
							</div>
							{ /* Honeypot Spam Protection Settings might be used later*/ }
							<div className="mb-4 flex items-start gap-10">
								<div className="max-w-[250px]">
									<BaseControl
										help={ __( 'Enable SPAM Protection' ) }
									>
										<h3 className="text-base font-semibold text-gray-90">
											{ __(
												'Honeypot Spam Protection Settings',
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
	return (
		<div className="flex justify-center w-[100%]">
			<ToastContainer />
			<div className="w-full p-8 bg-[#FBFBFC] rounded-md m-4 h-3/4 overflow-scroll shadow-md mb-8">
				<div
					className="flex gap-2 text-left text-[17.6px] text-[#111827] pb-4"
					style={ {
						borderBottom: '1px solid rgba(229, 231, 235, 1)',
					} }
				>
					<GoSettings />
					<span className="font-semibold">
						{ __( 'General Settings', 'sureforms' ) }
					</span>
				</div>
				<form onSubmit={ handleSubmit }>
					<div className="mt-4">
						{ /* Google ReCaptcha Settings */ }
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
										'Please add the API key for Google ReCaptcha',
										'sureforms'
									) }
								>
									<h3 className="text-base font-semibold text-gray-90">
										{ __(
											'Google ReCaptcha Settings',
											'sureforms'
										) }
									</h3>
								</BaseControl>
							</div>
							<div className="w-[600px] mt-4">
								<Fragment>
									<label
										htmlFor="google_captcha_site_key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Site key', 'sureforms' ) }
									</label>
									<div className="mb-4 ">
										<input
											type="text"
											name="google_captcha_site_key"
											id="google_captcha_site_key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-"
											onChange={ handleChange }
											value={ googleCaptchaKey }
										/>
									</div>
								</Fragment>
								<Fragment>
									<label
										htmlFor="google_captcha_secret_key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Secret key', 'sureforms' ) }
									</label>
									<div className="mb-4">
										<input
											type="text"
											name="google_captcha_secret_key"
											id="google_captcha_secret_key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAB3hT6bXlRTaRhfMxJ1vjRog4UIR"
											onChange={ handleChange }
											value={ googleCaptchaSecret }
										/>
									</div>
								</Fragment>
							</div>
						</div>
						{ /* Honeypot Spam Protection Settings might be used later*/ }
						<div className="mb-4 flex items-start gap-10">
							<div className="max-w-[250px]">
								<BaseControl
									help={ __( 'Enable SPAM Protection' ) }
								>
									<h3 className="text-base font-semibold text-gray-90">
										{ __(
											'Honeypot Spam Protection Settings',
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
};

export default Component;
