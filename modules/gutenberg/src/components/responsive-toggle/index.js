/**
 * Internal & External dependencies.
 */
import { useDeviceType } from '@Controls/getPreviewType';
import { useEffect, useState, useRef } from '@wordpress/element';
import { select } from '@wordpress/data';
import { getIdFromString, getPanelIdFromRef } from '@Utils/Helpers';
import UAGHelpText from '@Components/help-text';
import { applyFilters } from '@wordpress/hooks';

const ResponsiveToggle = ( props ) => {
	const { label, help = false } = props;
	const deviceType = useDeviceType();
	const [ panelNameForHook, setPanelNameForHook ] = useState( null );
	const panelRef = useRef( null );

	const { getSelectedBlock } = select( 'core/block-editor' );
	const blockNameForHook = getSelectedBlock()?.name.split( '/' ).pop(); // eslint-disable-line @wordpress/no-unused-vars-before-return
	useEffect( () => {
		setPanelNameForHook( getPanelIdFromRef( panelRef ) );
	}, [ blockNameForHook ] );

	// In the Widget editor, the device type is always set to desktop.
	if ( ! deviceType ) {
		return null;
	}

	const controlName = getIdFromString( props.label );
	const controlBeforeDomElement = applyFilters(
		`spectra.${ blockNameForHook }.${ panelNameForHook }.${ controlName }.before`,
		'',
		blockNameForHook
	);
	const controlAfterDomElement = applyFilters(
		`spectra.${ blockNameForHook }.${ panelNameForHook }.${ controlName }`,
		'',
		blockNameForHook
	);

	return (
		<div ref={ panelRef } className="uag-responsive-label-wrap">
			{ controlBeforeDomElement }

			{ label && <span className="uag-control-label">{ label }</span> }
			<UAGHelpText text={ help } />
			{ controlAfterDomElement }
		</div>
	);
};

export default ResponsiveToggle;
