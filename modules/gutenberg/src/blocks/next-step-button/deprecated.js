/**
 * BLOCK: Button Child - Deprecated Block
 */

import classnames from 'classnames';
import { RichText } from '@wordpress/block-editor';
import renderSVG from '@Controls/deprecatedRenderIcon';

const attributes = {
	borderWidth: {
		type: 'number',
		default: 1,
	},
	borderRadius: {
		type: 'number',
		default: 2,
	},
	borderStyle: {
		type: 'string',
		default: 'solid',
	},
	borderColor: {
		type: 'string',
		default: '#333',
	},
	borderHColor: {
		type: 'string',
		default: '#333',
	},
};

const deprecated = [
	{
		attributes, // eslint-disable-line
		save: ( props ) => {
			const { attributes, className } = props; // eslint-disable-line

			const { block_id, target, link, label } = attributes;

			return (
				<div
					className={ classnames(
						className,
						'uagb-buttons__outer-wrap',
						`uagb-block-${ block_id }`
					) }
				>
					<div className="uagb-button__wrapper">
						<div className="uagb-buttons-repeater">
							<RichText.Content
								value={ label }
								tagName="a"
								className="uagb-button__link"
								href={ link }
								rel="noopener noreferrer"
								target={ target }
							/>
						</div>
					</div>
				</div>
			);
		},
	},
	{
		attributes, // eslint-disable-line
		save: ( props ) => {
			const { attributes, className } = props; // eslint-disable-line

			const {
				block_id,
				target,
				link,
				label,
				inheritFromTheme,
				icon,
				iconPosition,
			} = attributes;

			const iconHtml = ( curr_position ) => {
				if ( '' !== icon && curr_position === iconPosition ) {
					return (
						<span
							className={ classnames(
								'uagb-button__icon',
								`uagb-button__icon-position-${ iconPosition }`
							) }
						>
							{ renderSVG( icon ) }
						</span>
					);
				}
				return null;
			};

			return (
				<div
					className={ classnames(
						className,
						'uagb-buttons__outer-wrap',
						`uagb-block-${ block_id }`,
						inheritFromTheme ? 'wp-block-button' : null
					) }
				>
					<div className="uagb-button__wrapper">
						<a
							className={ classnames(
								'uagb-buttons-repeater',
								inheritFromTheme
									? 'wp-block-button__link'
									: null
							) }
							href={ link }
							rel="noopener noreferrer"
							target={ target }
						>
							{ iconHtml( 'before' ) }
							<RichText.Content
								value={ label }
								tagName="div"
								className="uagb-button__link"
							/>
							{ iconHtml( 'after' ) }
						</a>
					</div>
				</div>
			);
		},
	},
];

export default deprecated;
