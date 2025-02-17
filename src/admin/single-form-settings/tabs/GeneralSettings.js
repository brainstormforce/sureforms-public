import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import { useDeviceType } from '@Controls/getPreviewType';
import svgIcons from '@Image/single-form-logo.json';
import {
	Modal,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import parse from 'html-react-parser';
import FormBehaviorPopupButton from '../../components/FormBehaviorPopupButton';
import SingleFormSettingsPopup from '../components/SingleFormSettingPopup';

let prevMetaHash = '';

function GeneralSettings( props ) {
	const { createNotice, removeNotice } = useDispatch( 'core/notices' );

	const { editPost } = useDispatch( editorStore );
	const { defaultKeys, isPageBreak } = props;
	let sureformsKeys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	const xx = wp.hooks.applyFilters( 'srfm.store.defaultStateOne', {a:1,b:2,} );

	console.log('xx', xx);

	let sf = useSelect( ( select ) => {
			const sureforms = select( 'sureforms' );
			return {
				geta : sureforms.getA(),
			}
		}, []
	);
	const sfD = useDispatch( 'sureforms' );

	console.log('sf', sf);

	const pageBreakSettings = sureformsKeys?._srfm_page_break_settings || {};

	const deviceType = useDeviceType();
	const [ rootContainer, setRootContainer ] = useState(
		document.getElementById( 'srfm-form-container' )
	);
	const root = document.documentElement.querySelector( 'body' );
	const [ isOpen, setOpen ] = useState( false );
	const [ popupTab, setPopupTab ] = useState( false );
	const [ hasValidationErrors, setHasValidationErrors ] = useState( false );

	const openModal = ( e ) => {
		const popupTabTarget = e.currentTarget.getAttribute( 'data-popup' );
		setPopupTab( popupTabTarget );
		setOpen( true );
		prevMetaHash = btoa( JSON.stringify( sureformsKeys ) );
	};
	const closeModal = () => {
		if (
			hasValidationErrors &&
			! confirm(
				__(
					'Are you sure you want to close? Your unsaved changes will be lost as you have some validation errors.',
					'sureforms'
				)
			)
		) {
			return;
		}

		setOpen( false );

		if ( btoa( JSON.stringify( sureformsKeys ) ) !== prevMetaHash ) {
			createNotice(
				'warning',
				__( 'There are few unsaved changes. Please save your changes to reflect the updates.', 'sureforms' ),
				{
					id: 'srfm-unsaved-changes-warning',
					isDismissible: true,
				}
			);
		}
	};

	useSelect( ( select ) => {
		if ( ! select( 'core/editor' ).isSavingPost() ) {
			return;
		}

		if ( select( 'core/notices' ).getNotices()?.filter( ( notice ) => 'srfm-unsaved-changes-warning' === notice?.id ).length > 0 ) {
			// Remove SRFM unsaved changes notice if user is saving the current form.
			removeNotice( 'srfm-unsaved-changes-warning' );
		}
	}, [] );

	const modalIcon = parse( svgIcons.modalLogo );

	// if device type is desktop then
	useEffect( () => {
		setTimeout( () => {
			const tabletPreview =
				document.getElementsByClassName( 'is-tablet-preview' );
			const mobilePreview =
				document.getElementsByClassName( 'is-mobile-preview' );
			if ( tabletPreview.length !== 0 || mobilePreview.length !== 0 ) {
				const preview = tabletPreview[ 0 ] || mobilePreview[ 0 ];
				if ( preview ) {
					const iframe = preview.querySelector( 'iframe' );
					const iframeDocument =
						iframe?.contentWindow.document ||
						iframe?.contentDocument;
					const iframeBody = iframeDocument
						?.querySelector( 'html' )
						?.querySelector( 'body' );

					setRootContainer(
						iframeBody.querySelector( '.is-root-container' )
					);
				}
			} else {
				setRootContainer(
					document.getElementById( 'srfm-form-container' )
				);
			}
		}, 100 );
	}, [ deviceType, rootContainer ] );

	if ( sureformsKeys ) {
		// Font Size
		root.style.setProperty(
			'--srfm-font-size',
			sureformsKeys._srfm_fontsize
				? sureformsKeys._srfm_fontsize + 'px'
				: 'none'
		);
	} else {
		sureformsKeys = defaultKeys;
		editPost( {
			meta: sureformsKeys,
		} );
	}

	/*
	 * function to update post metas.
	 */
	function updateMeta( option, value ) {
		let value_id = 0;
		let key_id = '';

		// Form Container
		if ( option === '_srfm_bg_image' ) {
			if ( value ) {
				value_id = value.id;
				value = value.sizes.full.url;
			}
			key_id = option + '_id';
		}

		// Header Background Image
		if ( option === '_srfm_cover_image' ) {
			if ( value ) {
				value_id = value.id;
				value = value.sizes.full.url;
			}
			key_id = option + '_id';
		}

		const option_array = {};

		if ( key_id ) {
			option_array[ key_id ] = value_id;
		}
		option_array[ option ] = value;
		editPost( {
			meta: option_array,
		} );
	}

	const singleSettings = [
		{
			id: 'form_confirmation',
			title: __( 'Form Confirmation', 'sureforms' ),
		},
		{
			id: 'email_notification',
			title: __( 'Email Notification', 'sureforms' ),
		},
		{
			id: 'compliance_settings',
			title: __( 'Compliance Settings', 'sureforms' ),
		},
		{
			id: 'integrations',
			title: __( 'Integrations', 'sureforms' ),
		},
	];

	function updatePageBreakSettings( option, value ) {
		editPost( {
			meta: {
				_srfm_page_break_settings: { ...pageBreakSettings, [ option ]: value },
			},
		} );
	}

	return (
		<>
			<SRFMAdvancedPanelBody
				title={ __( 'General', 'sureforms' ) }
				initialOpen={ true }
			>
				<ToggleControl
					label={ __( 'Use Labels as Placeholders', 'sureforms' ) }
					checked={ sureformsKeys._srfm_use_label_as_placeholder }
					onChange={ ( value ) => {
						updateMeta( '_srfm_use_label_as_placeholder', value );
					} }
				/>
					{/* <button onClick={()=>{
						const increase1 = sf.geta + 1;
						sfD.updateA(increase1);
					}}>Increate a {sf.geta}</button> */}

				<p className="components-base-control__help">
					{ __(
						'Above setting will place the labels inside the fields as placeholders (where possible). This setting takes effect only on the live page, not in the editor preview.',
						'sureforms'
					) }
				</p>
			</SRFMAdvancedPanelBody>
			{ isPageBreak && (
				<SRFMAdvancedPanelBody
					title={ __( 'Page Break', 'sureforms' ) }
					initialOpen={ false }
				>
					{ pageBreakSettings?.progress_indicator_type !== 'none' && (
						<>
							<ToggleControl
								label={ __( 'Show Labels', 'sureforms' ) }
								checked={ pageBreakSettings?.toggle_label }
								onChange={ ( value ) => {
									updatePageBreakSettings( 'toggle_label', value );
								} }
							/>
							<SRFMTextControl
								label={ __( 'First Page Label', 'sureforms' ) }
								value={ pageBreakSettings?.first_page_label }
								data={ {
									value: pageBreakSettings?.first_page_label,
									label: 'first_page_label',
								} }
								onChange={ ( value ) =>
									updatePageBreakSettings( 'first_page_label', value )
								}
							/>
						</>
					) }
					<SelectControl
						label={ __( 'Progress Indicator', 'sureforms' ) }
						value={
							pageBreakSettings?.progress_indicator_type
						}
						className="srfm-progress-control"
						options={ [
							{ label: __( 'None', 'sureforms' ), value: 'none' },
							{
								label: __( 'Progress Bar', 'sureforms' ),
								value: 'progress-bar',
							},
							{
								label: __( 'Connector', 'sureforms' ),
								value: 'connector',
							},
							{
								label: __( 'Steps', 'sureforms' ),
								value: 'steps',
							},
						] }
						onChange={ ( value ) =>
							updatePageBreakSettings( 'progress_indicator_type', value )
						}
						__nextHasNoMarginBottom
					/>
					<SRFMTextControl
						data={ {
							value: pageBreakSettings?.next_button_text,
							label: 'next_button_text',
						} }
						label={ __( 'Next Button Text', 'sureforms' ) }
						value={ pageBreakSettings?.next_button_text }
						onChange={ ( value ) => {
							updatePageBreakSettings( 'next_button_text', value );
						} }
						isFormSpecific={ true }
					/>
					<SRFMTextControl
						data={ {
							value: pageBreakSettings?.back_button_text,
							label: 'back_button_text',
						} }
						label={ __( 'Back Button Text', 'sureforms' ) }
						value={ pageBreakSettings?.back_button_text }
						onChange={ ( value ) => {
							updatePageBreakSettings( 'back_button_text', value );
						} }
						isFormSpecific={ true }
					/>
				</SRFMAdvancedPanelBody>
			) }

			{
				singleSettings.map( ( set ) => {
					return (
						<FormBehaviorPopupButton
							key={ set.id }
							settingName={ set.title }
							popupId={ set.id }
							openModal={ openModal }
						/>
					);
				} )
			}

			{ isOpen && (
				<Modal
					onRequestClose={ closeModal }
					title={ __( 'Form Behavior', 'sureforms' ) }
					className="srfm-settings-modal"
					icon={ modalIcon }
					isFullScreen={ true }
				>
					<SingleFormSettingsPopup
						sureformsKeys={ sureformsKeys }
						targetTab={ popupTab }
						setHasValidationErrors={ setHasValidationErrors }
					/>
				</Modal>
			) }
		</>
	);
}

export default GeneralSettings;
