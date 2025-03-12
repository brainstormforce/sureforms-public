import { memo } from '@wordpress/element';
import { uagbClassNames } from '@Utils/Helpers';
import Renderer from './renderer';
import RendererDesc from './renderer-desc';

const Render = ( props ) => {
	const {
		attributes: {
			block_id,
			headingTitleToggle,
			headingDescToggle,
			separatorStyle,
			separatorPosition,
			headingDescPosition,
			headingWrapper,
		},
		className,
		deviceType,
	} = props;

	const Element = headingWrapper || 'div';
	const separator = separatorStyle !== 'none' && (
		<div className="uagb-separator-wrap">
			<div className="uagb-separator"></div>
		</div>
	);

	const headingText = (
		<>
			{ separatorPosition === 'above-heading' ? separator : '' }
			<Renderer { ...props } />
			{ separatorPosition === 'below-heading' ? separator : '' }
		</>
	);

	const descText = (
		<>
			{ separatorPosition === 'above-sub-heading' ? separator : '' }
			<RendererDesc { ...props } />
			{ separatorPosition === 'below-sub-heading' ? separator : '' }
		</>
	);

	return (
		<Element
			className={ uagbClassNames( [
				className,
				`wp-block-uagb-advanced-heading`,
				'uagb-block',
				`uagb-editor-preview-mode-${ deviceType.toLowerCase() }`,
				`uagb-block-${ block_id }`,
			] ) }
		>
			{ headingDescToggle && 'above-heading' === headingDescPosition
				? descText
				: '' }
			{ headingTitleToggle && headingText }
			{ headingDescToggle && 'below-heading' === headingDescPosition
				? descText
				: '' }
			{ ! headingDescToggle && ! headingTitleToggle ? separator : '' }
		</Element>
	);
};
export default memo( Render );
