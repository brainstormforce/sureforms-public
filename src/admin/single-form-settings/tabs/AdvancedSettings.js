import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import apiFetch from '@wordpress/api-fetch';
import FormBehaviorPopupButton from '../../components/FormBehaviorPopupButton';
import Dialog from '../components/dialog/Dialog';
import { FormRestrictionProvider } from '../components/form-restrictions/context';

let prevMetaHash = '';

function AdvancedSettings( props ) {
	const [ hasValidationErrors, setHasValidationErrors ] = useState( false );
	const { editPost } = useDispatch( editorStore );

	const { createNotice } = useDispatch( 'core/notices' );

	const { defaultKeys } = props;

	const [ isOpen, setOpen ] = useState( false );
	const [ popupTab, setPopupTab ] = useState( false );

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
				__(
					'There are few unsaved changes. Please save your changes to reflect the updates.',
					'sureforms'
				),
				{
					id: 'srfm-unsaved-changes-warning',
					isDismissible: true,
				}
			);
		}
	};

	let sureformsKeys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	if ( sureformsKeys && '_srfm_submit_type' in sureformsKeys ) {
		if ( ! sureformsKeys._srfm_submit_type ) {
			sureformsKeys = defaultKeys;
			editPost( {
				meta: sureformsKeys,
			} );
		}
	} else {
		sureformsKeys = defaultKeys;
		editPost( {
			meta: sureformsKeys,
		} );
	}

	function updateMeta( option, value ) {
		const option_array = {};
		option_array[ option ] = value;
		editPost( {
			meta: option_array,
		} );
	}

	return (
		<>
			<FormBehaviorPopupButton
				settingName={ __( 'Custom CSS', 'sureforms' ) }
				popupId={ 'form_custom_css' }
				openModal={ openModal }
			/>
			<FormRestrictionProvider>
				<Dialog
					open={ isOpen }
					setOpen={ setOpen }
					close={ closeModal }
					sureformsKeys={ sureformsKeys }
					targetTab={ popupTab }
					setHasValidationErrors={ setHasValidationErrors }
				/>
			</FormRestrictionProvider>
		</>
	);
}

export default AdvancedSettings;
