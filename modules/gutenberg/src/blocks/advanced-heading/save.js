/**
 * BLOCK: Advanced Heading - Save Block
 */

import classnames from 'classnames';

import { RichText } from '@wordpress/block-editor';

export default function save( props ) {
	const {
		block_id,
		headingTitleToggle,
		headingTitle,
		headingDesc,
		headingDescToggle,
		headingTag,
		separatorStyle,
		headingId,
		separatorPosition,
		headingDescPosition,
	} = props.attributes;

	let seprator = '';
	if ( separatorStyle !== 'none' ) {
		seprator = <div className="uagb-separator"></div>;
	}
	let headingText = '';
	if ( headingTitle ) {
		headingText = (
			<>
				{ separatorPosition === 'above-heading' ? seprator : '' }
				<RichText.Content
					tagName={ headingTag }
					value={ headingTitle }
					className="uagb-heading-text"
					id={ headingId }
				/>
				{ separatorPosition === 'below-heading' ? seprator : '' }
			</>
		);
	}
	let descText = '';

	if ( headingDesc ) {
		descText = (
			<>
				{ separatorPosition === 'above-sub-heading' ? seprator : '' }
				<RichText.Content
					tagName="p"
					value={ headingDesc }
					className="uagb-desc-text"
				/>
				{ separatorPosition === 'below-sub-heading' ? seprator : '' }
			</>
		);
	}

	return (
		<div
			className={ classnames(
				props.className,
				`wp-block-uagb-advanced-heading`,
				'uagb-block',
				`uagb-block-${ block_id }`
			) }
		>
			{ headingDescToggle && 'above-heading' === headingDescPosition
				? descText
				: '' }
			{ headingTitleToggle && headingText }
			{ headingDescToggle && 'below-heading' === headingDescPosition
				? descText
				: '' }
			{ ! headingDescToggle && ! headingTitleToggle ? seprator : '' }
		</div>
	);
}
