/**
 * BLOCK: Buttons Child - Edit Class
 */

// Import classes
import classnames from 'classnames';
import renderSVG from '@Controls/renderIcon';
import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';
import React from 'react';
import { useDeviceType } from '@Controls/getPreviewType';

const Render = ( props ) => {
	props = props.parentProps;
	const { attributes, setAttributes } = props;
	let deviceType = useDeviceType();
	deviceType = deviceType.toLowerCase();

	const {
		className,
		nextStepButtonTitle,
		nextStepButtonSubTitle,
		icon,
		iconPosition,
		borderStyle,
	} = attributes;

	let icon_html = '';
	if ( '' !== icon ) {
		icon_html = (
			<div className="wpcf__next-step-button-icon">
				{ renderSVG( icon ) }
			</div>
		);
	}

	return (
		<div
			className={ classnames(
				className,
				'wp-block-wcfb-next-step-button',
				`cf-editor-preview-mode-${ deviceType }`,
				`cf-block-${ props.clientId.substr( 0, 8 ) }`,
				borderStyle !== 'none' ? 'is-style-outline' : ''
			) }
		>
			<div className="wpcf__next-step-button">
				<div className="wpcf__next-step-button-wrap">
					<a href="#" className="wpcf__next-step-button-link">
						{ iconPosition === 'before_title_sub_title' &&
							icon_html }
						<span className="wpcf__next-step-button-content-wrap">
							<div className="wpcf__next-step-button-title-wrap">
								{ iconPosition === 'before_title' && icon_html }
								<RichText
									placeholder={ __(
										'Add text…',
										'cartflows'
									) }
									value={ nextStepButtonTitle }
									tagName="span"
									onChange={ ( value ) => {
										setAttributes( {
											nextStepButtonTitle: value,
										} );
									} }
									className="wpcf__next-step-button-title"
								/>
								{ iconPosition === 'after_title' && icon_html }
							</div>
							<RichText
								placeholder={ __( 'Add text…', 'cartflows' ) }
								value={ nextStepButtonSubTitle }
								tagName="div"
								onChange={ ( value ) => {
									setAttributes( {
										nextStepButtonSubTitle: value,
									} );
								} }
								className="wpcf__next-step-button-sub-title"
							/>
						</span>
						{ iconPosition === 'after_title_sub_title' &&
							icon_html }
					</a>
				</div>
			</div>
		</div>
	);
};

export default React.memo( Render );
