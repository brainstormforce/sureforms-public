import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import styles from './editor.lazy.scss';
import {
	useLayoutEffect,
	useEffect,
	useState,
	useRef,
	memo,
} from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { select, dispatch } from '@wordpress/data';
import classnames from 'classnames';
import { getIdFromString, getPanelIdFromRef } from '@Utils/Helpers';
import SRFMReset from '../reset';
import SRFMHelpText from '@Components/help-text';

const SRFMPresets = ( props ) => {
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

	const {
		setAttributes,
		presets,
		presetInputType,
		label,
		className,
		help = false,
	} = props;

	const resetAttributes = [];

	if ( presets ) {
		presets.map( ( preset ) => {
			if ( preset?.attributes ) {
				for ( const attribute of preset?.attributes ) {
					if ( ! resetAttributes.includes( attribute?.label ) ) {
						resetAttributes.push( attribute?.label );
					}
				}
			}

			return preset;
		} );
	}

	const [ selectedPresetState, setPreset ] = useState( '' );

	const onReset = () => {
		setPreset( '' );
		resetChildBlockAttributes();
	};

	const updatePresets = ( selectedPreset ) => {
		setPreset( selectedPreset );
		if ( presets ) {
			presets.map( ( preset ) => {
				if ( preset.value ) {
					if (
						'default' !== selectedPreset &&
						'default' === preset.value &&
						preset.attributes
					) {
						preset.attributes.map( ( presetItem ) => {
							setAttributes( {
								[ presetItem.label ]: presetItem.value,
							} );
							return presetItem;
						} );
					}
					if (
						preset.value &&
						preset.value === selectedPreset &&
						preset.attributes
					) {
						presets[ 1 ]?.defaultPresetAttributes?.map(
							( presetItem ) => {
								setAttributes( {
									[ presetItem.label ]:
										presets[ 0 ]?.defaultAttributes[
											presetItem.label
										]?.default,
								} );
								return presetItem;
							}
						);
						preset.attributes.map( ( presetItem ) => {
							setAttributes( {
								[ presetItem.label ]: presetItem.value,
							} );
							return presetItem;
						} );

						if ( preset.childAttributes ) {
							updateChildBlockAttributes( preset );
						}
					}
				}
				return preset;
			} );
		}
	};

	const updateChildBlockAttributes = ( preset ) => {
		let childBlocks = [];

		if ( getSelectedBlock().innerBlocks ) {
			childBlocks = getSelectedBlock().innerBlocks;
		}

		const childBlocksClientIds = [];

		childBlocks.map( ( childBlock ) => {
			if ( childBlock.clientId ) {
				childBlocksClientIds.push( childBlock.clientId );
			}
			return childBlock;
		} );

		const childBlocksAttributes = {};

		preset.childAttributes.map( ( attr ) => {
			childBlocksAttributes[ attr.label ] = attr.value;
			return attr;
		} );

		childBlocksClientIds.map( ( clientId ) => {
			dispatch( 'core/block-editor' ).updateBlockAttributes(
				clientId,
				childBlocksAttributes
			);
			return clientId;
		} );
	};

	const resetChildBlockAttributes = () => {
		let childBlocks = [];

		if ( getSelectedBlock().innerBlocks ) {
			childBlocks = getSelectedBlock().innerBlocks;
		}

		const childBlocksClientIds = [];

		childBlocks.map( ( childBlock ) => {
			if ( childBlock.clientId ) {
				childBlocksClientIds.push( childBlock.clientId );
			}
			return childBlock;
		} );

		const childBlocksAttributes = {};

		presets.map( ( preset ) => {
			if ( preset?.childAttributes ) {
				preset?.childAttributes.map( ( attr ) => {
					if (
						presets[ 1 ]?.defaultChildAttributes &&
						presets[ 1 ]?.defaultChildAttributes[ attr.label ] &&
						undefined !==
							presets[ 1 ]?.defaultChildAttributes[ attr.label ]
								.default
					) {
						childBlocksAttributes[ attr.label ] =
							presets[ 1 ]?.defaultChildAttributes[
								attr.label
							].default;
					}
					return attr;
				} );
			}
			return preset;
		} );

		childBlocksClientIds.map( ( clientId ) => {
			dispatch( 'core/block-editor' ).updateBlockAttributes(
				clientId,
				childBlocksAttributes
			);
			return clientId;
		} );
	};
	const presetRadioImageOptions = presets.map( ( preset ) => {
		if ( ! preset.value ) {
			return '';
		}

		const key = preset.value;
		const checked = selectedPresetState === key ? true : false;
		return (
			<>
				<input
					key={ key }
					className="srfm-presets-radio-input"
					type="radio"
					value={ key }
					checked={ checked }
					onChange={ () => updatePresets( key ) }
					onClick={ () => updatePresets( key ) }
				/>

				{ /* eslint-disable-next-line jsx-a11y/label-has-associated-control, jsx-a11y/no-noninteractive-element-interactions */ }
				<label
					htmlFor={ key }
					className="srfm-presets-radio-input-label"
					// eslint-disable-next-line react/no-danger
					dangerouslySetInnerHTML={ {
						__html: preset.icon,
					} }
					onClick={ () => updatePresets( key ) }
				></label>
			</>
		);
	} );

	const presetDropdown = (
		<SelectControl
			className="srfm-presets-dropdown"
			onChange={ updatePresets }
			options={ presets }
			label={ label }
		/>
	);

	const presetRadioImage = (
		<>
			<div className="srfm-presets-radio-image-wrap">
				{ presetRadioImageOptions }
			</div>
		</>
	);

	const controlName = getIdFromString( label );
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
			<div
				className={ classnames( className, 'srfm-presets-main-wrap' ) }
			>
				<div className="srfm-presets-label-reset-wrap">
					<label
						htmlFor="srfm-presets-label"
						className="srfm-presets-label"
					>
						{ label }
					</label>
					<SRFMReset
						attributeNames={ resetAttributes }
						setAttributes={ setAttributes }
						onReset={ onReset }
					/>
				</div>
				{ 'dropdown' === presetInputType && presetDropdown }
				{ 'radioImage' === presetInputType && presetRadioImage }
				<SRFMHelpText text={ help } />
			</div>
			{ controlAfterDomElement }
		</div>
	);
};

SRFMPresets.defaultProps = {
	presetInputType: 'dropdown',
	label: __( 'Select Preset', 'sureforms' ),
};

export default memo( SRFMPresets );
