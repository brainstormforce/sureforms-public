/**
 * BLOCK: Multi Buttons
 */

import styling from './styling';
import React, { useEffect, useRef } from 'react';
import addBlockEditorDynamicStyles from '@Controls/addBlockEditorDynamicStyles';

const { withSelect } = wp.data;

import Settings from './settings';
import Render from './render';

const CFCheckoutFormEdit = ( props ) => {
	function usePrevious( value ) {
		const ref = useRef();
		useEffect( () => {
			ref.current = value;
		}, [ props ] );
		return ref.current;
	}
	const { attributes, setAttributes } = props;

	const prevProps = usePrevious( { attributes, setAttributes } );

	useEffect( () => {
		// Replacement for componentDidMount.

		props.setAttributes( { showprecheckoutoffer: false } );
		// Assigning block_id in the attribute.
		props.setAttributes( { isHtml: false } );
		props.setAttributes( {
			block_id: props.clientId.substr( 0, 8 ),
		} );
	}, [] );

	useEffect( () => {
		// Replacement for componentDidUpdate.

		if ( undefined !== prevProps ) {
			if ( prevProps.attributes.layout !== props.attributes.layout ) {
				props.setAttributes( { isHtml: false } );
			}
			if (
				prevProps.attributes.sectionposition !==
				props.attributes.sectionposition
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.productOptionsSkin !==
				props.attributes.productOptionsSkin
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.productOptionsImages !==
				props.attributes.productOptionsImages
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.productOptionsSectionTitleText !==
				props.attributes.productOptionsSectionTitleText
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.PreSkipText !==
				props.attributes.PreSkipText
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.PreOrderText !==
				props.attributes.PreOrderText
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.PreProductTitleText !==
				props.attributes.PreProductTitleText
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.preSubTitleText !==
				props.attributes.preSubTitleText
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.preTitleText !==
				props.attributes.preTitleText
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.PreProductDescText !==
				props.attributes.PreProductDescText
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.inputSkins !== props.attributes.inputSkins
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.enableNote !== props.attributes.enableNote
			) {
				setAttributes( { isHtml: false } );
			}

			if ( prevProps.attributes.noteText !== props.attributes.noteText ) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.stepOneTitleText !==
				props.attributes.stepOneTitleText
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.stepOneSubTitleText !==
				props.attributes.stepOneSubTitleText
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.stepTwoTitleText !==
				props.attributes.stepTwoTitleText
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.stepTwoSubTitleText !==
				props.attributes.stepTwoSubTitleText
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.offerButtonTitleText !==
				props.attributes.offerButtonTitleText
			) {
				setAttributes( { isHtml: false } );
			}

			if (
				prevProps.attributes.offerButtonSubTitleText !==
				props.attributes.offerButtonSubTitleText
			) {
				setAttributes( { isHtml: false } );
			}
		}

		addBlockEditorDynamicStyles(
			'cf-checkout-form-style-' + props.clientId.substr( 0, 8 ),
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
				action: 'wpcf_order_checkout_form_shortcode',
				nonce: cf_blocks_info.wpcf_ajax_nonce,
				id: formId,
				cartflows_gb: true,
				layout: props.attributes.layout,
				sectionposition: props.attributes.sectionposition,
				productOptionsSkin: props.attributes.productOptionsSkin,
				productOptionsImages: props.attributes.productOptionsImages,
				productOptionsSectionTitleText:
					props.attributes.productOptionsSectionTitleText,
				PreSkipText: props.attributes.PreSkipText,
				PreOrderText: props.attributes.PreOrderText,
				PreProductTitleText: props.attributes.PreProductTitleText,
				preSubTitleText: props.attributes.preSubTitleText,
				preTitleText: props.attributes.preTitleText,
				PreProductDescText: props.attributes.PreProductDescText,
				inputSkins: props.attributes.inputSkins,
				enableNote: props.attributes.enableNote,
				noteText: props.attributes.noteText,
				stepOneTitleText: props.attributes.stepOneTitleText,
				stepOneSubTitleText: props.attributes.stepOneSubTitleText,
				stepTwoTitleText: props.attributes.stepTwoTitleText,
				stepTwoSubTitleText: props.attributes.stepTwoSubTitleText,
				offerButtonTitleText: props.attributes.offerButtonTitleText,
				offerButtonSubTitleText:
					props.attributes.offerButtonSubTitleText,
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
} )( CFCheckoutFormEdit );
