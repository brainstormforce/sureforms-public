import { __ } from '@wordpress/i18n';
import { GoSettings } from 'react-icons/go';
import { ToggleControl, BaseControl } from '@wordpress/components';
import { useState, Fragment } from '@wordpress/element';

const Component = ( { path } ) => {
	const [ enabled, setEnabled ] = useState( false );

	if ( 'general-settings' === path ) {
		return (
			<div className="flex justify-center w-[100%]">
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
					<div className="mt-4">
						{ /* Form Width */ }
						<div
							className="mb-4 flex items-start gap-20"
							style={ {
								borderBottom:
									'1px solid rgba(229, 231, 235, 1)',
							} }
						>
							<div className="max-w-[250px]">
								<BaseControl
									help={ __(
										'Toggle to switch to Full Width Layout',
										'sureforms'
									) }
								>
									<h3 className="text-base font-semibold text-gray-90">
										{ __( 'Form Width', 'sureforms' ) }
									</h3>
								</BaseControl>
							</div>

							<div className="w-[600px] mt-4">
								<div className="mb-4 ">
									<ToggleControl
										className="focus:!outline-[#94d4f5]"
										label="Full Width"
										checked={ enabled }
										onChange={ () => {
											setEnabled( ( state ) => ! state );
										} }
									/>
								</div>
							</div>
						</div>
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
										'Please add a the API key for Google ReCaptcha Please add a the API key for Google ReCaptcha Please add a the API key for Google ReCaptcha',
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
										htmlFor="google-captcha-site-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Site key', 'sureforms' ) }
									</label>
									<div className="mb-4 ">
										<input
											type="text"
											name="google-captcha-site-key"
											id="google-captcha-site-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-"
										/>
									</div>
								</Fragment>
								<Fragment>
									<label
										htmlFor="google-captcha-secret-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Secret key', 'sureforms' ) }
									</label>
									<div className="mb-4">
										<input
											type="password"
											name="google-captcha-secret-key"
											id="google-captcha-secret-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAB3hT6bXlRTaRhfMxJ1vjRog4UIR"
										/>
									</div>
								</Fragment>
							</div>
						</div>
						{ /* Honeypot Spam Protection Settings */ }
						<div className="mb-4 flex items-start gap-10">
							<div className="max-w-[250px]">
								<BaseControl
									help={ __(
										'Please add a the API key for Honeypot Spam Protection Please add a the API key for Honeypot Spam Protection Please add a the API key for Honeypot Spam Protection',
										'sureforms'
									) }
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
									<label
										htmlFor="honeypot-site-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Site key', 'sureforms' ) }
									</label>
									<div className="mb-4 ">
										<input
											type="text"
											name="honeypot-site-key"
											id="honeypot-site-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm placeholder:text-gray-400
											sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-"
										/>
									</div>
								</Fragment>
								<Fragment>
									<label
										htmlFor="honeypot-secret-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Secret key', 'sureforms' ) }
									</label>
									<div className="mb-4">
										<input
											type="password"
											name="honeypot-secret-key"
											id="honeypot-secret-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAB3hT6bXlRTaRhfMxJ1vjRog4UIR"
										/>
									</div>
								</Fragment>
							</div>
						</div>
					</div>
				</div>
			</div>
		);
	} else if ( 'menu-option-2' === path ) {
		return (
			<div className="flex justify-center w-[100%]">
				<div className="w-full p-8 bg-[#FBFBFC] rounded-md m-4 h-3/4 overflow-scroll shadow-md mb-8">
					<div
						className="flex gap-2 text-left text-[17.6px] text-[#111827] pb-4"
						style={ {
							borderBottom: '1px solid rgba(229, 231, 235, 1)',
						} }
					>
						<GoSettings />
						<span className="font-semibold">
							{ __( 'Menu Option 2', 'sureforms' ) }
						</span>
					</div>
					<div className="mt-4">
						{ /* Form Width */ }
						<div
							className="mb-4 flex items-start gap-20"
							style={ {
								borderBottom:
									'1px solid rgba(229, 231, 235, 1)',
							} }
						>
							<div className="max-w-[250px]">
								<BaseControl
									help={ __(
										'Toggle to switch to Full Width Layout',
										'sureforms'
									) }
								>
									<h3 className="text-base font-semibold text-gray-90">
										{ __( 'Form Width', 'sureforms' ) }
									</h3>
								</BaseControl>
							</div>

							<div className="w-[600px] mt-4">
								<div className="mb-4 ">
									<ToggleControl
										className="focus:!outline-[#94d4f5]"
										label="Full Width"
										checked={ enabled }
										onChange={ () => {
											setEnabled( ( state ) => ! state );
										} }
									/>
								</div>
							</div>
						</div>
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
										'Please add a the API key for Google ReCaptcha Please add a the API key for Google ReCaptcha Please add a the API key for Google ReCaptcha',
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
										htmlFor="google-captcha-site-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Site key', 'sureforms' ) }
									</label>
									<div className="mb-4 ">
										<input
											type="text"
											name="google-captcha-site-key"
											id="google-captcha-site-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-"
										/>
									</div>
								</Fragment>
								<Fragment>
									<label
										htmlFor="google-captcha-secret-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Secret key', 'sureforms' ) }
									</label>
									<div className="mb-4">
										<input
											type="password"
											name="google-captcha-secret-key"
											id="google-captcha-secret-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAB3hT6bXlRTaRhfMxJ1vjRog4UIR"
										/>
									</div>
								</Fragment>
							</div>
						</div>
						{ /* Honeypot Spam Protection Settings */ }
						<div className="mb-4 flex items-start gap-10">
							<div className="max-w-[250px]">
								<BaseControl
									help={ __(
										'Please add a the API key for Honeypot Spam Protection Please add a the API key for Honeypot Spam Protection Please add a the API key for Honeypot Spam Protection',
										'sureforms'
									) }
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
									<label
										htmlFor="honeypot-site-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Site key', 'sureforms' ) }
									</label>
									<div className="mb-4 ">
										<input
											type="text"
											name="honeypot-site-key"
											id="honeypot-site-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm placeholder:text-gray-400
											sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-"
										/>
									</div>
								</Fragment>
								<Fragment>
									<label
										htmlFor="honeypot-secret-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Secret key', 'sureforms' ) }
									</label>
									<div className="mb-4">
										<input
											type="password"
											name="honeypot-secret-key"
											id="honeypot-secret-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAB3hT6bXlRTaRhfMxJ1vjRog4UIR"
										/>
									</div>
								</Fragment>
							</div>
						</div>
					</div>
				</div>
			</div>
		);
	} else if ( 'menu-option-3' === path ) {
		return (
			<div className="flex justify-center w-[100%]">
				<div className="w-full p-8 bg-[#FBFBFC] rounded-md m-4 h-3/4 overflow-scroll shadow-md mb-8">
					<div
						className="flex gap-2 text-left text-[17.6px] text-[#111827] pb-4"
						style={ {
							borderBottom: '1px solid rgba(229, 231, 235, 1)',
						} }
					>
						<GoSettings />
						<span className="font-semibold">
							{ __( 'Menu Option 3', 'sureforms' ) }
						</span>
					</div>
					<div className="mt-4">
						{ /* Form Width */ }
						<div
							className="mb-4 flex items-start gap-20"
							style={ {
								borderBottom:
									'1px solid rgba(229, 231, 235, 1)',
							} }
						>
							<div className="max-w-[250px]">
								<BaseControl
									help={ __(
										'Toggle to switch to Full Width Layout',
										'sureforms'
									) }
								>
									<h3 className="text-base font-semibold text-gray-90">
										{ __( 'Form Width', 'sureforms' ) }
									</h3>
								</BaseControl>
							</div>

							<div className="w-[600px] mt-4">
								<div className="mb-4 ">
									<ToggleControl
										className="focus:!outline-[#94d4f5]"
										label="Full Width"
										checked={ enabled }
										onChange={ () => {
											setEnabled( ( state ) => ! state );
										} }
									/>
								</div>
							</div>
						</div>
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
										'Please add a the API key for Google ReCaptcha Please add a the API key for Google ReCaptcha Please add a the API key for Google ReCaptcha',
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
										htmlFor="google-captcha-site-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Site key', 'sureforms' ) }
									</label>
									<div className="mb-4 ">
										<input
											type="text"
											name="google-captcha-site-key"
											id="google-captcha-site-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-"
										/>
									</div>
								</Fragment>
								<Fragment>
									<label
										htmlFor="google-captcha-secret-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Secret key', 'sureforms' ) }
									</label>
									<div className="mb-4">
										<input
											type="password"
											name="google-captcha-secret-key"
											id="google-captcha-secret-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAB3hT6bXlRTaRhfMxJ1vjRog4UIR"
										/>
									</div>
								</Fragment>
							</div>
						</div>
						{ /* Honeypot Spam Protection Settings */ }
						<div className="mb-4 flex items-start gap-10">
							<div className="max-w-[250px]">
								<BaseControl
									help={ __(
										'Please add a the API key for Honeypot Spam Protection Please add a the API key for Honeypot Spam Protection Please add a the API key for Honeypot Spam Protection',
										'sureforms'
									) }
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
									<label
										htmlFor="honeypot-site-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Site key', 'sureforms' ) }
									</label>
									<div className="mb-4 ">
										<input
											type="text"
											name="honeypot-site-key"
											id="honeypot-site-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm placeholder:text-gray-400
											sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-"
										/>
									</div>
								</Fragment>
								<Fragment>
									<label
										htmlFor="honeypot-secret-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Secret key', 'sureforms' ) }
									</label>
									<div className="mb-4">
										<input
											type="password"
											name="honeypot-secret-key"
											id="honeypot-secret-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAB3hT6bXlRTaRhfMxJ1vjRog4UIR"
										/>
									</div>
								</Fragment>
							</div>
						</div>
					</div>
				</div>
			</div>
		);
	} else if ( 'menu-option-4' === path ) {
		return (
			<div className="flex justify-center w-[100%]">
				<div className="w-full p-8 bg-[#FBFBFC] rounded-md m-4 h-3/4 overflow-scroll shadow-md mb-8">
					<div
						className="flex gap-2 text-left text-[17.6px] text-[#111827] pb-4"
						style={ {
							borderBottom: '1px solid rgba(229, 231, 235, 1)',
						} }
					>
						<GoSettings />
						<span className="font-semibold">
							{ __( 'Menu Option 4', 'sureforms' ) }
						</span>
					</div>
					<div className="mt-4">
						{ /* Form Width */ }
						<div
							className="mb-4 flex items-start gap-20"
							style={ {
								borderBottom:
									'1px solid rgba(229, 231, 235, 1)',
							} }
						>
							<div className="max-w-[250px]">
								<BaseControl
									help={ __(
										'Toggle to switch to Full Width Layout',
										'sureforms'
									) }
								>
									<h3 className="text-base font-semibold text-gray-90">
										{ __( 'Form Width', 'sureforms' ) }
									</h3>
								</BaseControl>
							</div>

							<div className="w-[600px] mt-4">
								<div className="mb-4 ">
									<ToggleControl
										className="focus:!outline-[#94d4f5]"
										label="Full Width"
										checked={ enabled }
										onChange={ () => {
											setEnabled( ( state ) => ! state );
										} }
									/>
								</div>
							</div>
						</div>
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
										'Please add a the API key for Google ReCaptcha Please add a the API key for Google ReCaptcha Please add a the API key for Google ReCaptcha',
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
										htmlFor="google-captcha-site-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Site key', 'sureforms' ) }
									</label>
									<div className="mb-4 ">
										<input
											type="text"
											name="google-captcha-site-key"
											id="google-captcha-site-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-"
										/>
									</div>
								</Fragment>
								<Fragment>
									<label
										htmlFor="google-captcha-secret-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Secret key', 'sureforms' ) }
									</label>
									<div className="mb-4">
										<input
											type="password"
											name="google-captcha-secret-key"
											id="google-captcha-secret-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAB3hT6bXlRTaRhfMxJ1vjRog4UIR"
										/>
									</div>
								</Fragment>
							</div>
						</div>
						{ /* Honeypot Spam Protection Settings */ }
						<div className="mb-4 flex items-start gap-10">
							<div className="max-w-[250px]">
								<BaseControl
									help={ __(
										'Please add a the API key for Honeypot Spam Protection Please add a the API key for Honeypot Spam Protection Please add a the API key for Honeypot Spam Protection',
										'sureforms'
									) }
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
									<label
										htmlFor="honeypot-site-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Site key', 'sureforms' ) }
									</label>
									<div className="mb-4 ">
										<input
											type="text"
											name="honeypot-site-key"
											id="honeypot-site-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm placeholder:text-gray-400
											sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-"
										/>
									</div>
								</Fragment>
								<Fragment>
									<label
										htmlFor="honeypot-secret-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Secret key', 'sureforms' ) }
									</label>
									<div className="mb-4">
										<input
											type="password"
											name="honeypot-secret-key"
											id="honeypot-secret-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAB3hT6bXlRTaRhfMxJ1vjRog4UIR"
										/>
									</div>
								</Fragment>
							</div>
						</div>
					</div>
				</div>
			</div>
		);
	} else if ( 'menu-option-5' === path ) {
		return (
			<div className="flex justify-center w-[100%]">
				<div className="w-full p-8 bg-[#FBFBFC] rounded-md m-4 h-3/4 overflow-scroll shadow-md mb-8">
					<div
						className="flex gap-2 text-left text-[17.6px] text-[#111827] pb-4"
						style={ {
							borderBottom: '1px solid rgba(229, 231, 235, 1)',
						} }
					>
						<GoSettings />
						<span className="font-semibold">
							{ __( 'Menu Option 5', 'sureforms' ) }
						</span>
					</div>
					<div className="mt-4">
						{ /* Form Width */ }
						<div
							className="mb-4 flex items-start gap-20"
							style={ {
								borderBottom:
									'1px solid rgba(229, 231, 235, 1)',
							} }
						>
							<div className="max-w-[250px]">
								<BaseControl
									help={ __(
										'Toggle to switch to Full Width Layout',
										'sureforms'
									) }
								>
									<h3 className="text-base font-semibold text-gray-90">
										{ __( 'Form Width', 'sureforms' ) }
									</h3>
								</BaseControl>
							</div>

							<div className="w-[600px] mt-4">
								<div className="mb-4 ">
									<ToggleControl
										className="focus:!outline-[#94d4f5]"
										label="Full Width"
										checked={ enabled }
										onChange={ () => {
											setEnabled( ( state ) => ! state );
										} }
									/>
								</div>
							</div>
						</div>
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
										'Please add a the API key for Google ReCaptcha Please add a the API key for Google ReCaptcha Please add a the API key for Google ReCaptcha',
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
										htmlFor="google-captcha-site-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Site key', 'sureforms' ) }
									</label>
									<div className="mb-4 ">
										<input
											type="text"
											name="google-captcha-site-key"
											id="google-captcha-site-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-"
										/>
									</div>
								</Fragment>
								<Fragment>
									<label
										htmlFor="google-captcha-secret-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Secret key', 'sureforms' ) }
									</label>
									<div className="mb-4">
										<input
											type="password"
											name="google-captcha-secret-key"
											id="google-captcha-secret-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAB3hT6bXlRTaRhfMxJ1vjRog4UIR"
										/>
									</div>
								</Fragment>
							</div>
						</div>
						{ /* Honeypot Spam Protection Settings */ }
						<div className="mb-4 flex items-start gap-10">
							<div className="max-w-[250px]">
								<BaseControl
									help={ __(
										'Please add a the API key for Honeypot Spam Protection Please add a the API key for Honeypot Spam Protection Please add a the API key for Honeypot Spam Protection',
										'sureforms'
									) }
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
									<label
										htmlFor="honeypot-site-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Site key', 'sureforms' ) }
									</label>
									<div className="mb-4 ">
										<input
											type="text"
											name="honeypot-site-key"
											id="honeypot-site-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm placeholder:text-gray-400
										sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-"
										/>
									</div>
								</Fragment>
								<Fragment>
									<label
										htmlFor="honeypot-secret-key"
										className="block text-sm font-medium text-[#828282] mb-1"
									>
										{ __( 'Secret key', 'sureforms' ) }
									</label>
									<div className="mb-4">
										<input
											type="password"
											name="honeypot-secret-key"
											id="honeypot-secret-key"
											className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
											placeholder="6Le-wvkSAAAAAB3hT6bXlRTaRhfMxJ1vjRog4UIR"
										/>
									</div>
								</Fragment>
							</div>
						</div>
					</div>
				</div>
			</div>
		);
	}
	return (
		<div className="flex justify-center w-[100%]">
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
				<div className="mt-4">
					{ /* Form Width */ }
					<div
						className="mb-4 flex items-start gap-20"
						style={ {
							borderBottom: '1px solid rgba(229, 231, 235, 1)',
						} }
					>
						<div className="max-w-[250px]">
							<BaseControl
								help={ __(
									'Toggle to switch to Full Width Layout',
									'sureforms'
								) }
							>
								<h3 className="text-base font-semibold text-gray-90">
									{ __( 'Form Width', 'sureforms' ) }
								</h3>
							</BaseControl>
						</div>

						<div className="w-[600px] mt-4">
							<div className="mb-4 ">
								<ToggleControl
									className="focus:!outline-[#94d4f5]"
									label="Full Width"
									checked={ enabled }
									onChange={ () => {
										setEnabled( ( state ) => ! state );
									} }
								/>
							</div>
						</div>
					</div>
					{ /* Google ReCaptcha Settings */ }
					<div
						className="mb-4 flex items-start gap-10"
						style={ {
							borderBottom: '1px solid rgba(229, 231, 235, 1)',
						} }
					>
						<div className="max-w-[250px]">
							<BaseControl
								help={ __(
									'Please add a the API key for Google ReCaptcha Please add a the API key for Google ReCaptcha Please add a the API key for Google ReCaptcha',
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
									htmlFor="google-captcha-site-key"
									className="block text-sm font-medium text-[#828282] mb-1"
								>
									{ __( 'Site key', 'sureforms' ) }
								</label>
								<div className="mb-4 ">
									<input
										type="text"
										name="google-captcha-site-key"
										id="google-captcha-site-key"
										className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
										placeholder="6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-"
									/>
								</div>
							</Fragment>
							<Fragment>
								<label
									htmlFor="google-captcha-secret-key"
									className="block text-sm font-medium text-[#828282] mb-1"
								>
									{ __( 'Secret key', 'sureforms' ) }
								</label>
								<div className="mb-4">
									<input
										type="password"
										name="google-captcha-secret-key"
										id="google-captcha-secret-key"
										className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
										placeholder="6Le-wvkSAAAAAB3hT6bXlRTaRhfMxJ1vjRog4UIR"
									/>
								</div>
							</Fragment>
						</div>
					</div>
					{ /* Honeypot Spam Protection Settings */ }
					<div className="mb-4 flex items-start gap-10">
						<div className="max-w-[250px]">
							<BaseControl
								help={ __(
									'Please add a the API key for Honeypot Spam Protection Please add a the API key for Honeypot Spam Protection Please add a the API key for Honeypot Spam Protection',
									'sureforms'
								) }
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
								<label
									htmlFor="honeypot-site-key"
									className="block text-sm font-medium text-[#828282] mb-1"
								>
									{ __( 'Site key', 'sureforms' ) }
								</label>
								<div className="mb-4 ">
									<input
										type="text"
										name="honeypot-site-key"
										id="honeypot-site-key"
										className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm placeholder:text-gray-400
										sm:text-sm sm:leading-6"
										placeholder="6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-"
									/>
								</div>
							</Fragment>
							<Fragment>
								<label
									htmlFor="honeypot-secret-key"
									className="block text-sm font-medium text-[#828282] mb-1"
								>
									{ __( 'Secret key', 'sureforms' ) }
								</label>
								<div className="mb-4">
									<input
										type="password"
										name="honeypot-secret-key"
										id="honeypot-secret-key"
										className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
										placeholder="6Le-wvkSAAAAAB3hT6bXlRTaRhfMxJ1vjRog4UIR"
									/>
								</div>
							</Fragment>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default Component;
