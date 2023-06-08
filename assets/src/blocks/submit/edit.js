/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	ButtonGroup,
	Button,
	TextControl,
	ToggleControl,
	BaseControl,
} from '@wordpress/components';

export default ( { attributes, setAttributes } ) => {
	const { text, full, buttonAlignment } = attributes;

	const buttonStyles = {
		width: full && '100%',
	};

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Attributes', 'sureforms' ) }>
					<PanelRow>
						<TextControl
							label={ __( 'Button Text', 'sureforms' ) }
							value={ text }
							onChange={ ( value ) =>
								setAttributes( { text: value } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={ __( 'Full', 'sureforms' ) }
							checked={ full }
							onChange={ ( checked ) =>
								setAttributes( { full: checked } )
							}
						/>
					</PanelRow>
					{ full === false && (
						<BaseControl
							label={ __( 'Button Alignment', 'sureforms' ) }
							id="button-alignment"
						>
							<PanelRow>
								<ButtonGroup>
									<Button
										isPressed={ buttonAlignment === 'left' }
										onClick={ () =>
											setAttributes( {
												buttonAlignment: 'left',
											} )
										}
										icon="editor-alignleft"
									></Button>
									<Button
										isPressed={
											buttonAlignment === 'center'
										}
										onClick={ () =>
											setAttributes( {
												buttonAlignment: 'center',
											} )
										}
										icon="editor-aligncenter"
									></Button>
									<Button
										isPressed={
											buttonAlignment === 'right'
										}
										onClick={ () =>
											setAttributes( {
												buttonAlignment: 'right',
											} )
										}
										icon="editor-alignright"
									></Button>
								</ButtonGroup>
							</PanelRow>
						</BaseControl>
					) }
				</PanelBody>
			</InspectorControls>
			<div style={ { textAlign: buttonAlignment } }>
				<button className="sureforms-button" style={ buttonStyles }>
					{ text }
				</button>
			</div>
		</div>
	);
};
