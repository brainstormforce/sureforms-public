/** @jsx jsx */

import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	InnerBlocks,
	useInnerBlocksProps as __stableUseInnerBlocksProps,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseInnerBlocksProps,
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { css, jsx } from '@emotion/react';
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
	const blockID = useBlockProps().id;

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
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Attributes', 'sureforms' ) }>
					<PanelRow>
						<TextControl
							label={ __( 'Label Name', 'sureforms' ) }
							value={ label }
							onChange={ ( value ) =>
								setAttributes( { label: value } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={ __( 'Required', 'sureforms' ) }
							checked={ required }
							onChange={ ( checked ) =>
								setAttributes( { required: checked } )
							}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div>
				<label htmlFor={ 'multi-choice-block-' + blockID }>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<ScRadioGroup
					id={ 'multi-choice-block-' + blockID }
					required={ required }
					{ ...innerBlocksProps }
				></ScRadioGroup>
			</div>
		</div>
	);
};
