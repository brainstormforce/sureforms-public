/**
 * BLOCK: Multi Buttons
 */

import styling from './styling';
import React, { useEffect } from 'react';
import addBlockEditorDynamicStyles from '@Controls/addBlockEditorDynamicStyles';

const { withSelect } = wp.data;

import Settings from './settings';
import Render from './render';

const OrderDetailForm = ( props ) => {
	useEffect( () => {
		// Replacement for componentDidMount.

		props.setAttributes( { isHtml: false } );
		// Assigning block_id in the attribute.
		props.setAttributes( {
			block_id: props.clientId.substr( 0, 8 ),
		} );

		// Assigning block_id in the attribute.
		props.setAttributes( { classMigrate: true } );
	}, [] );

	useEffect( () => {
		// Replacement for componentDidUpdate.

		if (
			props.attributes.formJson &&
			document.querySelector(
				'#wcf-thankyou-wrap .woocommerce-thankyou-order-received'
			)
		) {
			const thanyouTextEditor =
				props.attributes.thanyouText !== ''
					? props.attributes.thanyouText
					: props.attributes.formJson.data.thankyouText;
			document.querySelector(
				'#wcf-thankyou-wrap .woocommerce-thankyou-order-received'
			).innerHTML = thanyouTextEditor;
		}

		addBlockEditorDynamicStyles(
			// 'CF_block-cartflows-frotend-style-css',
			'wpcf-order-detail-form-style' + props.clientId.substr( 0, 8 ),
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

	if ( ! isHtml ) {
		jQuery.ajax( {
			url: cf_blocks_info.ajax_url,
			data: {
				action: 'wpcf_order_detail_form_shortcode',
				nonce: cf_blocks_info.wpcf_ajax_nonce,
				thanyouText: props.attributes.thanyouText,
				id: formId,
				cartflows_gb: true,
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
} )( OrderDetailForm );
