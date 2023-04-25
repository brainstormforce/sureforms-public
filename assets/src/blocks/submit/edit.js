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
	const { text, full, buttonAlignment, show_total, show_icon } = attributes;

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
					<PanelRow>
						<ToggleControl
							label={ __(
								'Show total due in button text.',
								'sureforms'
							) }
							checked={ show_total }
							onChange={ ( checked ) =>
								setAttributes( { show_total: checked } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={ __(
								'Show a secure lock icon.',
								'sureforms'
							) }
							checked={ show_icon }
							onChange={ ( checked ) =>
								setAttributes( { show_icon: checked } )
							}
						/>
					</PanelRow>
					{ /* Might be used later
					<PanelRow>
						<SelectControl
							label={ __( 'Size', 'sureforms' ) }
							value={ size }
							onChange={ ( value ) => {
								setAttributes( { size: value } );
							} }
							options={ [
								{
									value: null,
									label: 'Select a Size',
									disabled: true,
								},
								{
									value: 'small',
									label: __( 'Small', 'sureforms' ),
								},
								{
									value: 'medium',
									label: __( 'Medium', 'sureforms' ),
								},
								{
									value: 'large',
									label: __( 'Large', 'sureforms' ),
								},
							] }
						/>
					</PanelRow> */ }
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
				<button style={ buttonStyles }>
					{ show_icon && (
						<svg
							slot="prefix"
							xmlns="http://www.w3.org/2000/svg"
							width="16"
							height="16"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
						>
							<path
								strokeLinecap="round"
								strokeLinejoin="round"
								strokeWidth={ 2 }
								d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
							/>
						</svg>
					) }
					{ text }
					{ show_total && (
						<span>
							{ '\u00A0' }
							<sc-total></sc-total>
						</span>
					) }
				</button>
			</div>
		</div>
	);
};
