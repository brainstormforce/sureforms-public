/**
 * External dependencies
 */
import { useEffect, useState, useRef } from '@wordpress/element';
import { getPanelIdFromRef } from '@Utils/Helpers';
import { useDeviceType } from '@Controls/getPreviewType';
import ResponsiveToggle from '../responsive-toggle';
import SRFMMediaPicker from '@Components/image';
import { select } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import SRFMHelpText from '@Components/help-text';
import { applyFilters } from '@wordpress/hooks';

const ResponsiveSRFMImage = ( props ) => {
	const [ panelNameForHook, setPanelNameForHook ] = useState( null );
	const panelRef = useRef( null );
	const { backgroundImage, setAttributes, help = false } = props;
	const { getSelectedBlock } = select( 'core/block-editor' );

	const blockNameForHook = getSelectedBlock()?.name.split( '/' ).pop(); // eslint-disable-line @wordpress/no-unused-vars-before-return
	useEffect( () => {
		setPanelNameForHook( getPanelIdFromRef( panelRef ) );
	}, [ blockNameForHook ] );

	const responsive = true;
	const deviceType = useDeviceType();
	const device = deviceType.toLowerCase();

	/*
	 * Event to set Image as while adding.
	 */
	const onSelectImage = ( media ) => {
		if ( ! media || ! media.url ) {
			setAttributes( { [ backgroundImage[ device ].label ]: null } );
			return;
		}

		if ( ! media.type || 'image' !== media.type ) {
			setAttributes( { [ backgroundImage[ device ].label ]: null } );
			return;
		}

		setAttributes( { [ backgroundImage[ device ].label ]: media } );
	};

	/*
	 * Event to set Image as null while removing.
	 */
	const onRemoveImage = () => {
		setAttributes( { [ backgroundImage[ device ].label ]: '' } );
	};

	const output = {};
	output.Desktop = (
		<SRFMMediaPicker
			onSelectImage={ onSelectImage }
			backgroundImage={ backgroundImage.desktop.value }
			onRemoveImage={ onRemoveImage }
			disableLabel={ true }
		/>
	);
	output.Tablet = (
		<SRFMMediaPicker
			onSelectImage={ onSelectImage }
			backgroundImage={ backgroundImage.tablet.value }
			onRemoveImage={ onRemoveImage }
			disableLabel={ true }
		/>
	);
	output.Mobile = (
		<SRFMMediaPicker
			onSelectImage={ onSelectImage }
			backgroundImage={ backgroundImage.mobile.value }
			onRemoveImage={ onRemoveImage }
			disableLabel={ true }
		/>
	);

	const controlName = 'image'; // there is no label props that's why keep hard coded label
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
			<div className="srfm-responsive-image-select srfm-responsive-select-control">
				<div className="srfm-size-type-field-tabs">
					<div className="srfm-control__header">
						<ResponsiveToggle
							label={ __( 'Image', 'sureforms' ) }
							responsive={ responsive }
						/>
					</div>
					{ output[ deviceType ]
						? output[ deviceType ]
						: output.Desktop }
				</div>
				<SRFMHelpText text={ help } />
			</div>
			{ controlAfterDomElement }
		</div>
	);
};

export default ResponsiveSRFMImage;
