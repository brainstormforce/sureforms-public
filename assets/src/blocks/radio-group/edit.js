/** @jsx jsx */

import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	InnerBlocks,
	useInnerBlocksProps as __stableUseInnerBlocksProps,
	__experimentalUseInnerBlocksProps,
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { css, jsx } from '@emotion/react';
import { Fragment } from '@wordpress/element';
import {
	PanelBody,
	PanelRow,
	TextControl,
	ToggleControl,
} from '@wordpress/components';

/**
 * Component Dependencies
 */
import { ScRadioGroup } from '@surecart/components-react';

import { useSelect } from '@wordpress/data';

export default ( { attributes, setAttributes, isSelected, clientId } ) => {
	const { label, required } = attributes;
	const useInnerBlocksProps = __stableUseInnerBlocksProps
		? __stableUseInnerBlocksProps
		: __experimentalUseInnerBlocksProps;

	const blockProps = useBlockProps( {
		css: css`
			.block-list-appender {
				position: relative;
			}
			.wp-block[data-block] {
				margin-top: 0;
			}
		`,
	} );

	const childIsSelected = useSelect( ( select ) =>
		select( blockEditorStore ).hasSelectedInnerBlock( clientId, true )
	);

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		className: 'sc-radio',
		allowedBlocks: [ 'sureforms/radio' ],
		template: [ [ 'sureforms/radio', {} ] ],
		renderAppender:
			isSelected || childIsSelected
				? InnerBlocks.ButtonBlockAppender
				: false,
	} );

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody title={ __( 'Attributes', 'sureforms' ) }>
					<PanelRow>
						<TextControl
							label={ __( 'Label Name', 'sureforms' ) }
							value={ label }
							onChange={ ( label ) => setAttributes( { label } ) }
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={ __( 'Required', 'sureforms' ) }
							checked={ required }
							onChange={ ( required ) => setAttributes( { required } ) }
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>

			<ScRadioGroup
				label={ label }
				required={ required }
				{ ...innerBlocksProps }
			></ScRadioGroup>
		</Fragment>
	);
};
