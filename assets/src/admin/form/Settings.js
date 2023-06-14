import {
	TextControl,
	TextareaControl,
	PanelBody,
	BaseControl,
	ClipboardButton,
	PanelRow,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

function Settings() {
	const { editPost } = useDispatch( editorStore );

	const default_keys = {
		_sureforms_thankyou_message: 'Form submitted successfully!',
		_sureforms_email: sfBlockData.admin_email,
	};

	let sureforms_keys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	const [ hasCopied, setHasCopied ] = useState( false );
	const postId = wp.data.select( 'core/editor' ).getCurrentPostId();

	useEffect( () => {
		if ( ! sureforms_keys ) {
			// eslint-disable-next-line react-hooks/exhaustive-deps
			sureforms_keys = default_keys;
			editPost( {
				meta: sureforms_keys,
			} );
		} else if ( '_sureforms_email' in sureforms_keys ) {
			if ( '' === sureforms_keys._sureforms_email ) {
				sureforms_keys = default_keys;
				editPost( {
					meta: sureforms_keys,
				} );
			}
		}
	}, [] );

	function updateMeta( option, value ) {
		const option_array = {};
		option_array[ option ] = value;
		editPost( {
			meta: option_array,
		} );
	}

	return (
		<PanelBody style={ { display: 'flex', flexDirection: 'column' } }>
			<PanelRow>
				<BaseControl
					id="sureforms-admin-email"
					label={ __(
						'Customize the email address on which you want to send the notifications',
						'sureforms'
					) }
					help={ __(
						'Please add the emails separated by , if you want to send emails to multiple email addresses',
						'sureforms'
					) }
				>
					<TextControl
						value={ sureforms_keys._sureforms_email }
						placeholder={ __( 'E-mail address', 'sureforms' ) }
						onChange={ ( value ) => {
							updateMeta( '_sureforms_email', value );
						} }
					/>
				</BaseControl>
			</PanelRow>
			<PanelRow>
				<BaseControl
					id="sureforms-success-message"
					label={ __(
						'Customize the Successfull Form Submission message',
						'sureforms'
					) }
				>
					<TextareaControl
						placeholder={ __(
							'Form submitted successfully.',
							'sureforms'
						) }
						value={ sureforms_keys._sureforms_thankyou_message }
						onChange={ ( value ) => {
							updateMeta( '_sureforms_thankyou_message', value );
						} }
					/>
				</BaseControl>
			</PanelRow>
			<PanelRow>
				<BaseControl
					id="sureforms-form-shortcode"
					label={ __( 'Form Shortcode', 'sureforms' ) }
					help={ __(
						'Paste this shortcode on the page or post to render this form.',
						'sureforms'
					) }
				>
					<div className="sureforms-shortcode">
						<TextControl
							value={ `[sureforms id="${ postId }"]` }
							disabled
						/>
						<ClipboardButton
							onCopy={ () => setHasCopied( true ) }
							onFinishCopy={ () => setHasCopied( false ) }
							icon={ hasCopied ? 'yes' : 'admin-page' }
							text={ `[sureforms id="${ postId }"]` }
						/>
					</div>
				</BaseControl>
			</PanelRow>
		</PanelBody>
	);
}

export default Settings;
