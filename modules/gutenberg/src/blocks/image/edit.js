import { useEffect, useMemo } from '@wordpress/element';
import scrollBlockToView from '@Controls/scrollBlockToView';
import styling from './styling';
import responsiveConditionPreview from '@Controls/responsiveConditionPreview';
import Settings from './settings';
import Render from './render';
//  Import CSS.
import './style.scss';
import DynamicCSSLoader from '@Components/dynamic-css-loader';
import { compose } from '@wordpress/compose';
import { getLoopImage } from './getLoopImage';
import AddStaticStyles from '@Controls/AddStaticStyles';
import addInitialAttr from '@Controls/addInitialAttr';
import { useBlockProps } from '@wordpress/block-editor';

function UAGBImageEdit( props ) {
	const {
		isSelected,
		attributes,
		name,
		attributes: { UAGHideDesktop, UAGHideTab, UAGHideMob },
		deviceType,
		context,
		setAttributes,
		clientId,
		hasDynamicContent,
	} = props;

	const blockProps = useBlockProps();

	useEffect( () => {
		if ( hasDynamicContent && ! attributes?.context ) {
			setAttributes( { context } );
		}
	}, [ context ] );

	useEffect( () => {
		scrollBlockToView();
	}, [ deviceType ] );

	const blockStyling = useMemo(
		() => styling( attributes, clientId, name, deviceType ),
		[ attributes, deviceType ]
	);

	useEffect( () => {
		responsiveConditionPreview( props );
	}, [ UAGHideDesktop, UAGHideTab, UAGHideMob, deviceType ] );

	return (
		<div { ...blockProps }>
			<DynamicCSSLoader { ...{ blockStyling } } />
			{ isSelected && <Settings { ...props } /> }
			<Render { ...props } />
		</div>
	);
}
export default compose(
	getLoopImage,
	addInitialAttr,
	AddStaticStyles
)( UAGBImageEdit );
