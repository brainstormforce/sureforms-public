/**
 * External dependencies
 */
import {
	useLayoutEffect,
	useEffect,
	useState,
	useRef,
} from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { useDeviceType } from '@Controls/getPreviewType';
import ResponsiveToggle from '../responsive-toggle';
import { select } from '@wordpress/data';
import { getIdFromString, getPanelIdFromRef } from '@Utils/Helpers';
import styles from './editor.lazy.scss';
import SRFMHelpText from '@Components/help-text';
import { applyFilters } from '@wordpress/hooks';

const ResponsiveSelectControl = ( props ) => {
	const [ panelNameForHook, setPanelNameForHook ] = useState( null );
	const panelRef = useRef( null );

	// Add and remove the CSS on the drop and remove of the component.
	useLayoutEffect( () => {
		styles.use();
		return () => {
			styles.unuse();
		};
	}, [] );

	const { getSelectedBlock } = select( 'core/block-editor' );

	const blockNameForHook = getSelectedBlock()?.name.split( '/' ).pop(); // eslint-disable-line @wordpress/no-unused-vars-before-return
	useEffect( () => {
		setPanelNameForHook( getPanelIdFromRef( panelRef ) );
	}, [ blockNameForHook ] );

	const { label, data, setAttributes, options, help = false } = props;

	const responsive = true;

	const deviceType = useDeviceType();

	const output = {};
	output.Desktop = (
		<SelectControl
			value={ data.desktop.value }
			onChange={ ( value ) =>
				setAttributes( { [ data.desktop.label ]: value } )
			}
			options={ options.desktop }
		/>
	);
	output.Tablet = (
		<SelectControl
			value={ data.tablet.value }
			onChange={ ( value ) =>
				setAttributes( { [ data.tablet.label ]: value } )
			}
			options={ options.tablet || options.desktop }
		/>
	);
	output.Mobile = (
		<SelectControl
			value={ data.mobile.value }
			onChange={ ( value ) =>
				setAttributes( { [ data.mobile.label ]: value } )
			}
			options={ options.mobile || options.desktop }
		/>
	);

	const controlName = getIdFromString( props.label );
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
		<div
			ref={ panelRef }
			className="srfm-responsive-select-control components-base-control"
		>
			{ controlBeforeDomElement }
			<div className="srfm-size-type-field-tabs">
				<div className="srfm-control__header">
					<ResponsiveToggle
						label={ label }
						responsive={ responsive }
					/>
				</div>
				{ output[ deviceType ] ? output[ deviceType ] : output.Desktop }
			</div>
			<SRFMHelpText text={ help } />
			{ controlAfterDomElement }
		</div>
	);
};

export default ResponsiveSelectControl;
