/**
 * External dependencies
 */
import {
	useEffect,
	useState,
	useRef,
	useLayoutEffect,
} from '@wordpress/element';

import { getPanelIdFromRef } from '@Utils/Helpers';
import { useDeviceType } from '@Controls/getPreviewType';
import ResponsiveToggle from '../responsive-toggle';
import { __ } from '@wordpress/i18n';
import { FocalPointPicker } from '@wordpress/components';
import { select } from '@wordpress/data';
import styles from './editor.lazy.scss';
import SRFMHelpText from '@Components/help-text';
import { applyFilters } from '@wordpress/hooks';

const ResponsiveSRFMFocalPointPicker = ( props ) => {
	const [ panelNameForHook, setPanelNameForHook ] = useState( null );
	const panelRef = useRef( null );

	const { backgroundPosition, backgroundImage, setAttributes } = props;

	const { getSelectedBlock } = select( 'core/block-editor' );
	const blockNameForHook = getSelectedBlock()?.name.split( '/' ).pop(); // eslint-disable-line @wordpress/no-unused-vars-before-return
	useEffect( () => {
		setPanelNameForHook( getPanelIdFromRef( panelRef ) );
	}, [ blockNameForHook ] );

	const responsive = true;

	const deviceType = useDeviceType();
	const device = deviceType.toLowerCase();

	useLayoutEffect( () => {
		styles.use();
		return () => {
			styles.unuse();
		};
	}, [] );

	const output = {};
	const url = backgroundImage[ device ]?.value?.url;
	const value = backgroundPosition[ device ]?.value;

	output.Desktop = (
		<FocalPointPicker
			url={ url }
			value={ value }
			onChange={ ( focalPoint ) => {
				setAttributes( {
					[ backgroundPosition[ device ]?.label ]: focalPoint,
				} );
			} }
		/>
	);
	output.Tablet = (
		<FocalPointPicker
			url={ url ? url : backgroundImage.desktop?.value?.url }
			value={ value }
			onChange={ ( focalPoint ) => {
				setAttributes( {
					[ backgroundPosition[ device ]?.label ]: focalPoint,
				} );
			} }
		/>
	);
	output.Mobile = (
		<FocalPointPicker
			url={ url ? url : backgroundImage.desktop?.value?.url }
			value={ value }
			onChange={ ( focalPoint ) => {
				setAttributes( {
					[ backgroundPosition[ device ]?.label ]: focalPoint,
				} );
			} }
		/>
	);

	const controlName = 'position'; // There is no label props that's why keep hard coded label
	const controlBeforeDomElement = applyFilters(
		`srfm.${ blockNameForHook }.${ panelNameForHook }.${ controlName }.before`,
		'',
		blockNameForHook
	);
	const controlAfterDomElement = applyFilters(
		`srfm.${ blockNameForHook }.${ panelNameForHook }.${ controlName }`,
		'',
		blockNameForHook
	);

	return (
		<div ref={ panelRef } className="components-base-control">
			{ controlBeforeDomElement }
			<div className="srfm-responsive-select-control">
				<div className="srfm-size-type-field-tabs">
					<div className="srfm-control__header">
						<ResponsiveToggle
							label={ __( 'Position', 'sureforms' ) }
							responsive={ responsive }
						/>
					</div>
					{ output[ deviceType ]
						? output[ deviceType ]
						: output.Desktop }
				</div>
				<SRFMHelpText text={ props.help } />
			</div>
			{ controlAfterDomElement }
		</div>
	);
};

export default ResponsiveSRFMFocalPointPicker;
