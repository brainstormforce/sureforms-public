import { useEffect, useState, useRef } from '@wordpress/element';
import { getPanelIdFromRef } from '@Utils/Helpers';
import { blocksAttributes } from '@Attributes/getBlocksDefaultAttributes';
import { select } from '@wordpress/data';
import { Button, Tooltip, Dashicon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

const SRFMReset = ( props ) => {
	const [ panelNameForHook, setPanelNameForHook ] = useState( null );
	const panelRef = useRef( null );

	const { onReset, attributeNames, setAttributes, isFormSpecific, isValueArray = false } = props;

	const [ refreshPresets, toggleRefreshPresets ] = useState( false );

	const { getSelectedBlock } = select( 'core/block-editor' );

	const blockNameForHook = getSelectedBlock()?.name.split( '/' ).pop(); // eslint-disable-line @wordpress/no-unused-vars-before-return
	useEffect( () => {
		setPanelNameForHook( getPanelIdFromRef( panelRef ) );
	}, [ blockNameForHook ] );

	const allBlocksAttributes = applyFilters(
		'srfm.blocksAttributes',
		blocksAttributes
	);

	const getBlockResetValue = () => {
		const selectedBlockName = isFormSpecific
			? 'form_specific'
			: getSelectedBlock()?.name.split( '/' ).pop();
		let defaultValues = false;

		if (
			attributeNames &&
			'undefined' !== typeof allBlocksAttributes[ selectedBlockName ]
		) {
			attributeNames.map( ( attributeName ) => {
				if ( attributeName ) {
					const blockDefaultAttributeValue =
						'undefined' !==
						typeof allBlocksAttributes[ selectedBlockName ][
							attributeName
						]?.default
							? allBlocksAttributes[ selectedBlockName ][
								attributeName
							  ]?.default
							: '';
					defaultValues = {
						...defaultValues,
						[ attributeName ]: blockDefaultAttributeValue,
					};
				}

				return attributeName;
			} );
		}

		return defaultValues;
	};

	const getResetState = () => {
		const defaultValues = getBlockResetValue();
		const selectedBlockAttributes = getSelectedBlock()?.attributes;
		let resetDisableState = true;

		//for form sepecific settings.
		if ( isFormSpecific ) {
			if ( defaultValues && attributeNames ) {
				// If props.value is an array, check if all the values are same as default values.
				if ( isValueArray ) {
					resetDisableState = attributeNames.every( ( attributeName, index ) => {
						return defaultValues[ attributeName ] === props.value[ index ];
					} );
					return resetDisableState;
				}
				const formSpecificKey = attributeNames[ 0 ];
				return defaultValues[ formSpecificKey ] === props.value;
			}
		}

		attributeNames.map( ( attributeName ) => {
			if (
				selectedBlockAttributes?.[ attributeName ] &&
				selectedBlockAttributes?.[ attributeName ] !==
					defaultValues?.[ attributeName ]
			) {
				resetDisableState = false;
			}
			return attributeName;
		} );

		return resetDisableState;
	};

	const resetDisableState = getResetState();

	const resetHandler = () => {
		const defaultValues = getBlockResetValue();

		if ( attributeNames ) {
			attributeNames.map( ( attributeName ) => {
				if ( attributeName ) {
					if ( setAttributes ) {
						setAttributes( {
							[ attributeName ]: defaultValues?.[ attributeName ],
						} );
					}
				}
				toggleRefreshPresets( ! refreshPresets );
				return attributeName;
			} );
		}

		if ( onReset ) {
			onReset( defaultValues );
		}
	};

	const controlName = 'reset'; // there is no label props that's why keep hard coded label
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
		<Tooltip
			text={ __( 'Reset', 'sureforms' ) }
			key={ 'reset' }
			ref={ panelRef }
		>
			{ controlBeforeDomElement }
			<Button
				className="srfm-reset"
				isSecondary
				isSmall
				onClick={ ( e ) => {
					e.preventDefault();
					resetHandler();
				} }
				disabled={ resetDisableState }
			>
				<Dashicon icon="image-rotate" />
			</Button>
			{ controlAfterDomElement }
		</Tooltip>
	);
};

export default SRFMReset;
