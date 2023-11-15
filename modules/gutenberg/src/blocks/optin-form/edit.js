/**
 * BLOCK: Multi Buttons
 */

import styling from './styling';
import React, { useEffect, useRef } from 'react';
import addBlockEditorDynamicStyles from '@Controls/addBlockEditorDynamicStyles';

const { withSelect } = wp.data;

import Settings from './settings';
import Render from './render';

const OptinForm = ( props ) => {
	const { attributes, setAttributes } = props;

	function usePrevious( value ) {
		const ref = useRef();
		useEffect( () => {
			ref.current = value;
		}, [ props ] );
		return ref.current;
	}

	const prevProps = usePrevious( { attributes, setAttributes } );

	useEffect( () => {
		// Replacement for componentDidMount.

		props.setAttributes( { isHtml: false } );
		// Assigning block_id in the attribute.
		props.setAttributes( {
			block_id: props.clientId.substr( 0, 8 ),
		} );

		// const {
		// 	inputFieldBorderHoverColor,

		// 	submitButtonBorderHoverColor,
		// } = props.attributes;

		// if (
		// 	inputFieldBorderWidth ||
		// 	inputFieldBorderRadius ||
		// 	inputFieldBorderColor ||
		// 	inputFieldBorderHoverColor ||
		// 	inputFieldBorderStyle
		// ) {
		// 	const migrationFieldsAttributes = migrateBorderAttributes(
		// 		'inputField',
		// 		{
		// 			label: 'inputFieldBorderWidth',
		// 			value: inputFieldBorderWidth,
		// 		},
		// 		{
		// 			label: 'inputFieldBorderRadius',
		// 			value: inputFieldBorderRadius,
		// 		},
		// 		{
		// 			label: 'inputFieldBorderColor',
		// 			value: inputFieldBorderColor,
		// 		},
		// 		{
		// 			label: 'inputFieldBorderHoverColor',
		// 			value: inputFieldBorderHoverColor,
		// 		},
		// 		{
		// 			label: 'inputFieldBorderStyle',
		// 			value: inputFieldBorderStyle,
		// 		}
		// 	);
		// 	props.setAttributes( migrationFieldsAttributes );
		// }

		// if (
		// 	submitButtonBorderWidth ||
		// 	submitButtonBorderRadius ||
		// 	submitButtonBorderColor ||
		// 	submitButtonBorderHoverColor ||
		// 	submitButtonBorderStyle
		// ) {
		// 	const migrationButtonAttributes = migrateBorderAttributes(
		// 		'submitButton',
		// 		{
		// 			label: 'submitButtonBorderWidth',
		// 			value: submitButtonBorderWidth,
		// 		},
		// 		{
		// 			label: 'submitButtonBorderRadius',
		// 			value: submitButtonBorderRadius,
		// 		},
		// 		{
		// 			label: 'submitButtonBorderColor',
		// 			value: submitButtonBorderColor,
		// 		},
		// 		{
		// 			label: 'submitButtonBorderHoverColor',
		// 			value: submitButtonBorderHoverColor,
		// 		},
		// 		{
		// 			label: 'submitButtonBorderStyle',
		// 			value: submitButtonBorderStyle,
		// 		}
		// 	);
		// 	props.setAttributes( migrationButtonAttributes );
		// }

		// Assigning block_id in the attribute.
		props.setAttributes( { classMigrate: true } );
	}, [] );

	useEffect( () => {
		// Replacement for componentDidUpdate.
		if ( undefined !== prevProps ) {
			if (
				prevProps.attributes.input_skins !==
				props.attributes.input_skins
			) {
				props.setAttributes( { isHtml: false } );
			}
		}

		if (
			props.attributes.formJson &&
			document.querySelector( '#place_order' )
		) {
			const submitButtonTextEditor =
				props.attributes.formJson.data.buttonText;
			document.querySelector( '#place_order' ).innerHTML =
				submitButtonTextEditor;
		}

		addBlockEditorDynamicStyles(
			// 'wcf-optin-template-css',
			'wpcf-optin-form-style-' + props.clientId.substr( 0, 8 ),
			styling( props )
		);
	}, [ props ] );

	return (
		<>
			<Settings parentProps={ props } />
			<Render parentProps={ props } />
		</>
	);
};

export default withSelect( ( select, props ) => {
	const { setAttributes } = props;
	const { isHtml } = props.attributes;
	const formId = cf_blocks_info.ID;
	let json_data = '';
	const { __GetPreviewDeviceType = null } = select( 'core/edit-post' );
	const device_type = __GetPreviewDeviceType
		? __GetPreviewDeviceType()
		: null;

	if ( formId && -1 !== formId && 0 !== formId && ! isHtml ) {
		jQuery.ajax( {
			url: cf_blocks_info.ajax_url,
			data: {
				action: 'wpcf_optin_form_shortcode',
				nonce: cf_blocks_info.wpcf_ajax_nonce,
				id: formId,
				cartflows_gb: true,
				input_skins: props.attributes.input_skins,
			},
			dataType: 'json',
			type: 'POST',
			success( data ) {
				setAttributes( { isHtml: true } );
				setAttributes( { formJson: data } );
				json_data = data;
			},
		} );
	}

	return {
		formHTML: json_data,
		deviceType: device_type,
	};
} )( OptinForm );
