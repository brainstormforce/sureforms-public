/**
 * BLOCK: Buttons - Save Block
 */

import classnames from 'classnames';
import renderSVG from '@Controls/renderIcon';
import { RichText } from '@wordpress/block-editor';

export default function save( props ) {
	const {
		block_id,
		nextStepButtonTitle,
		nextStepButtonSubTitle,
		icon,
		iconPosition,
		borderStyle,
	} = props.attributes;

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
				props.className,
				'wp-block-wcfb-next-step-button',
				`cf-block-${ block_id }`,
				borderStyle !== 'none' ? 'is-style-outline' : ''
			) }
		>
			<div className="wpcf__next-step-button">
				<div className="wpcf__next-step-button-wrap">
					<a
						href="?class=wcf-next-step"
						className="wpcf__next-step-button-link"
					>
						{ iconPosition === 'before_title_sub_title' &&
							icon_html }
						<span className="wpcf__next-step-button-content-wrap">
							<div className="wpcf__next-step-button-title-wrap">
								{ iconPosition === 'before_title' && icon_html }
								<RichText.Content
									value={ nextStepButtonTitle }
									tagName="span"
									className="wpcf__next-step-button-title"
								/>
								{ iconPosition === 'after_title' && icon_html }
							</div>
							{ nextStepButtonSubTitle && (
								<RichText.Content
									value={ nextStepButtonSubTitle }
									tagName="div"
									className="wpcf__next-step-button-sub-title"
								/>
							) }
						</span>
						{ iconPosition === 'after_title_sub_title' &&
							icon_html }
					</a>
				</div>
			</div>
		</div>
	);
}
