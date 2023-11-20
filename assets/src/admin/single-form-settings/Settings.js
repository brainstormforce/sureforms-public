import { ToggleControl, SelectControl, PanelRow } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import SRFMTextControl from '@Components/text-control';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import apiFetch from '@wordpress/api-fetch';

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

	if ( sureforms_keys && '_srfm_sender_notification' in sureforms_keys ) {
		if ( ! sureforms_keys._srfm_sender_notification ) {
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
				}
			} catch ( error ) {
				console.error( 'Error fetching data:', error );
			}
		};

		fetchData();
	}, [] );

	return (
		<>
			<SRFMAdvancedPanelBody
				title={ __( 'Notification Settings', 'sureforms' ) }
				initialOpen={ true }
			>
				<SRFMTextControl
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
						value: sureforms_keys._srfm_email,
						label: '_srfm_email',
					} }
					value={ sureforms_keys._srfm_email }
					isFormSpecific={ true }
					onChange={ ( value ) => {
						updateMeta( '_srfm_email', value );
					} }
				/>
				<ToggleControl
					label={ __(
						"Turn toggle on to send notification to sender's email address",
						'sureforms'
					) }
					checked={
						'on' === sureforms_keys._srfm_sender_notification
					}
					onChange={ ( value ) => {
						updateMeta(
							'_srfm_sender_notification',
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
			</SRFMAdvancedPanelBody>
			<SRFMAdvancedPanelBody
				title={ __( 'Success Message Settings', 'sureforms' ) }
				initialOpen={ false }
			>
				<ToggleControl
					label={ __(
						'Turn toggle on to redirect to a URL',
						'sureforms'
					) }
					checked={ 'url' === sureforms_keys._srfm_submit_type }
					onChange={ ( value ) => {
						updateMeta(
							'_srfm_submit_type',
							value ? 'url' : 'message'
						);
					} }
				/>
				<p className="components-base-control__help">
					{ 'url' === sureforms_keys._srfm_submit_type
						? __( 'Redirect', 'sureforms' )
						: __( 'Message', 'sureforms' ) }
				</p>
				{ 'message' === sureforms_keys._srfm_submit_type ? (
					<>
						<SRFMTextControl
							data={ {
								value: sureforms_keys._srfm_thankyou_message_title,
								label: '_srfm_thankyou_message_title',
							} }
							label={ __(
								'Form Submission Success Message Title',
								'sureforms'
							) }
							placeholder={ __( 'Thank you', 'sureforms' ) }
							value={
								sureforms_keys._srfm_thankyou_message_title
							}
							onChange={ ( value ) => {
								updateMeta(
									'_srfm_thankyou_message_title',
									value
								);
							} }
							isFormSpecific={ true }
						/>
						<p className="components-base-control__help" />
						<SRFMTextControl
							variant="textarea"
							data={ {
								value: sureforms_keys._srfm_thankyou_message,
								label: '_srfm_thankyou_message',
							} }
							label={ __(
								'Form Submission Success Message Description',
								'sureforms'
							) }
							placeholder={ __(
								'Form submitted successfully.',
								'sureforms'
							) }
							value={ sureforms_keys._srfm_thankyou_message }
							onChange={ ( value ) => {
								updateMeta( '_srfm_thankyou_message', value );
							} }
							isFormSpecific={ true }
						/>
					</>
				) : (
					<SRFMTextControl
						label={ __(
							'Customize the Thankyou page URL',
							'sureforms'
						) }
						value={ sureforms_keys._srfm_submit_url }
						onChange={ ( value ) => {
							updateMeta( '_srfm_submit_url', value );
						} }
						placeholder={ __(
							'https://example.com/',
							'sureforms'
						) }
						data={ {
							value: sureforms_keys._srfm_submit_url,
							label: '_srfm_submit_url',
						} }
						isFormSpecific={ true }
					/>
				) }
			</SRFMAdvancedPanelBody>
			<SRFMAdvancedPanelBody
				title={ __( 'Security Settings', 'sureforms' ) }
				initialOpen={ false }
			>
				<PanelRow>
					<p className="srfm-form-notice">
						{ __(
							'P.S. Note that If you are using two forms on the same page with the different reCAPTCHA versions (V2 checkbox and V3), it will create conflicts between the versions. Kindly avoid using different versions on same page.',
							'sureforms'
						) }
					</p>
				</PanelRow>
				<SelectControl
					label={ __(
						'Select the reCAPTCHA Version to use',
						'sureforms'
					) }
					value={ sureforms_keys._srfm_form_recaptcha }
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
								updateMeta( '_srfm_form_recaptcha', value );
							} else {
								setShowErr( true );
							}
						} else if ( value === 'v2-invisible' ) {
							if (
								sureformsV2InvisibleSecret !== '' &&
								sureformsV2InvisibleSite !== ''
							) {
								setShowErr( false );
								updateMeta( '_srfm_form_recaptcha', value );
							} else {
								setShowErr( true );
							}
						} else if ( value === 'v3-reCAPTCHA' ) {
							if (
								sureformsV3Secret !== '' &&
								sureformsV3Site !== ''
							) {
								setShowErr( false );
								updateMeta( '_srfm_form_recaptcha', value );
							} else {
								setShowErr( true );
							}
						} else {
							setShowErr( false );
							updateMeta( '_srfm_form_recaptcha', value );
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
			</SRFMAdvancedPanelBody>
		</>
	);
}

export default Settings;
