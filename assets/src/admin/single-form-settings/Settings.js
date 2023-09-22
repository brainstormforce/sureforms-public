import { ToggleControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import UAGTextControl from '@Components/text-control';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';

function Settings( props ) {
	const { editPost } = useDispatch( editorStore );

	const { default_keys } = props;

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

	const [ showErr, setShowErr ] = useState( false );

	let sureforms_keys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	if (
		sureforms_keys &&
		'_sureforms_sender_notification' in sureforms_keys
	) {
		if ( ! sureforms_keys._sureforms_sender_notification ) {
			sureforms_keys = default_keys;
			editPost( {
				meta: sureforms_keys,
			} );
		}
	} else {
		sureforms_keys = default_keys;
		editPost( {
			meta: sureforms_keys,
		} );
	}

	function updateMeta( option, value ) {
		const option_array = {};
		option_array[ option ] = value;
		editPost( {
			meta: option_array,
		} );
	}

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
				}
			} catch ( error ) {
				console.error( 'Error fetching data:', error );
			}
		};

		fetchData();
	}, [] );

	return (
		<>
			<UAGAdvancedPanelBody
				title={ __( 'Notification Settings', 'sureforms' ) }
				initialOpen={ true }
			>
				<UAGTextControl
					label={ __(
						'Customize the email address on which you want to send the notifications',
						'sureforms'
					) }
					help={ __(
						'Please add the emails separated by , if you want to send emails to multiple email addresses',
						'sureforms'
					) }
					placeholder={ __( 'E-mail addres', 'sureforms' ) }
					data={ {
						value: sureforms_keys._sureforms_email,
						label: '_sureforms_email',
					} }
					value={ sureforms_keys._sureforms_email }
					isFormSpecific={ true }
					onChange={ ( value ) => {
						updateMeta( '_sureforms_email', value );
					} }
				/>
				<ToggleControl
					label={ __(
						"Turn toggle on to send notification to sender's email address",
						'sureforms'
					) }
					checked={
						'on' === sureforms_keys._sureforms_sender_notification
					}
					onChange={ ( value ) => {
						updateMeta(
							'_sureforms_sender_notification',
							value ? 'on' : 'off'
						);
					} }
				/>
				<p className="components-base-control__help">
					{ __(
						'Please note that this setting will only work when an email field is present in the form',
						'sureforms'
					) }
				</p>
			</UAGAdvancedPanelBody>

			<UAGAdvancedPanelBody
				title={ __( 'Success Message Settings', 'sureforms' ) }
				initialOpen={ false }
			>
				<ToggleControl
					label={ __(
						'Turn toggle on to redirect to a URL',
						'sureforms'
					) }
					checked={ 'url' === sureforms_keys._sureforms_submit_type }
					onChange={ ( value ) => {
						updateMeta(
							'_sureforms_submit_type',
							value ? 'url' : 'message'
						);
					} }
				/>
				<p className="components-base-control__help">
					{ 'url' === sureforms_keys._sureforms_submit_type
						? __( 'Redirect', 'sureforms' )
						: __( 'Message', 'sureforms' ) }
				</p>
				{ 'message' === sureforms_keys._sureforms_submit_type ? (
					<UAGTextControl
						variant="textarea"
						data={ {
							value: sureforms_keys._sureforms_thankyou_message,
							label: '_sureforms_thankyou_message',
						} }
						label={ __(
							'Customize the Successfull Form Submission message',
							'sureforms'
						) }
						placeholder={ __(
							'Form submitted successfully.',
							'sureforms'
						) }
						value={ sureforms_keys._sureforms_thankyou_message }
						onChange={ ( value ) => {
							updateMeta( '_sureforms_thankyou_message', value );
						} }
						isFormSpecific={ true }
					/>
				) : (
					<UAGTextControl
						label={ __(
							'Customize the Thankyou page URL',
							'sureforms'
						) }
						value={ sureforms_keys._sureforms_submit_url }
						onChange={ ( value ) => {
							updateMeta( '_sureforms_submit_url', value );
						} }
						placeholder={ __(
							'https://example.com/',
							'sureforms'
						) }
						data={ {
							value: sureforms_keys._sureforms_submit_url,
							label: '_sureforms_submit_url',
						} }
						isFormSpecific={ true }
					/>
				) }
			</UAGAdvancedPanelBody>
			<UAGAdvancedPanelBody
				title={ __( 'Security Settings', 'sureforms' ) }
				initialOpen={ false }
			>
				<SelectControl
					label={ __(
						'Select the reCAPTCHA Version to use',
						'sureforms'
					) }
					value={ sureforms_keys._sureforms_form_recaptcha }
					options={ [
						{ label: 'None', value: 'none' },
						{
							label: 'reCAPTCHA v2 Checkbox',
							value: 'v2-checkbox',
						},
						{
							label: 'reCAPTCHA v2 Invisible',
							value: 'v2-invisible',
						},
						{
							label: 'reCAPTCHA v3',
							value: 'v3-reCAPTCHA',
						},
					] }
					onChange={ ( value ) => {
						if ( value === 'v2-checkbox' ) {
							if (
								sureformsV2CheckboxSite !== '' &&
								sureformsV2CheckboxSecret !== ''
							) {
								setShowErr( false );
								updateMeta(
									'_sureforms_form_recaptcha',
									value
								);
							} else {
								setShowErr( true );
							}
						} else if ( value === 'v2-invisible' ) {
							if (
								sureformsV2InvisibleSecret !== '' &&
								sureformsV2InvisibleSite !== ''
							) {
								setShowErr( false );
								updateMeta(
									'_sureforms_form_recaptcha',
									value
								);
							} else {
								setShowErr( true );
							}
						} else if ( value === 'v3-reCAPTCHA' ) {
							if (
								sureformsV3Secret !== '' &&
								sureformsV3Site !== ''
							) {
								setShowErr( false );
								updateMeta(
									'_sureforms_form_recaptcha',
									value
								);
							} else {
								setShowErr( true );
							}
						} else {
							setShowErr( false );
							updateMeta( '_sureforms_form_recaptcha', value );
						}
					} }
					__nextHasNoMarginBottom
				/>
				{ showErr && (
					<p style={ { color: 'red' } }>
						{ __(
							'Please configure the reCAPTCHA keys correctly',
							'sureforms'
						) }
					</p>
				) }
				<p className="components-base-control__help">
					{ __(
						'Before selecting the reCAPTCHA version. Please make sure you have configured reCAPTCHA in the Global Settings',
						'sureforms'
					) }
				</p>
			</UAGAdvancedPanelBody>
		</>
	);
}

export default Settings;
