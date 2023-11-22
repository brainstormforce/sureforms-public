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
		},
		className,
		deviceType,
	} = props;

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
		<div
			className={ uagbClassNames( [
				className,
				`wp-block-uagb-advanced-heading`,
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
		</div>
	);
};
export default memo( Render );
