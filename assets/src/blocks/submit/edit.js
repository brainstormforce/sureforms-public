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
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faLock } from '@fortawesome/free-solid-svg-icons';

export default ( { attributes, setAttributes } ) => {
	const { text, full, buttonAlignment, showTotal, showIcon } = attributes;

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
							checked={ showTotal }
							onChange={ ( checked ) =>
								setAttributes( { showTotal: checked } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={ __(
								'Show a secure lock icon.',
								'sureforms'
							) }
							checked={ showIcon }
							onChange={ ( checked ) =>
								setAttributes( { showIcon: checked } )
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
				<button style={ buttonStyles }>
					{ showIcon && <FontAwesomeIcon icon={ faLock } /> }
					{ ' ' + text }
					{ showTotal && (
						<span>
							<span> $100</span>
						</span>
					) }
				</button>
			</div>
		</div>
	);
};
