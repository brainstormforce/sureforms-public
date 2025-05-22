/* eslint-disable jsx-a11y/label-has-for */
import {
	useLayoutEffect,
	useEffect,
	useState,
	useRef,
} from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ResponsiveToggle from '../responsive-toggle';
import styles from './editor.lazy.scss';
import classnames from 'classnames';
import { getIdFromString, getPanelIdFromRef } from '@Utils/Helpers';
import SRFMReset from '../reset';
import SRFMHelpText from '@Components/help-text';
import { applyFilters } from '@wordpress/hooks';
import EditorSmartTagList from '@Components/misc/EditorSmartTagList';

const SRFMTextControl = ( props ) => {
	const [ panelNameForHook, setPanelNameForHook ] = useState( null );
	const panelRef = useRef( null );
	const [ inputData, setInputData ] = useState( props?.value );
	// Add and remove the CSS on the drop and remove of the component.

	useLayoutEffect( () => {
		styles.use();
		return () => {
			styles.unuse();
		};
	}, [] );

	const selectedBlock = useSelect( ( select ) => {
		return select( 'core/block-editor' ).getSelectedBlock();
	}, [] );

	const blockNameForHook = selectedBlock?.name.split( '/' ).pop(); // eslint-disable-line @wordpress/no-unused-vars-before-return

	useEffect( () => {
		setPanelNameForHook( getPanelIdFromRef( panelRef ) );
	}, [ blockNameForHook ] );
	useEffect( () => {
		setInputData( props?.value );
	}, [ props ] );

	const registerTextExtender =
		props.enableDynamicContent && props.name
			? applyFilters(
				'srfm.registerTextExtender',
				'',
				selectedBlock?.name,
				props.name,
				props.dynamicContentType
			  )
			: null;

	const isEnableDynamicContent = () => {
		if ( ! props.enableDynamicContent || ! props.name ) {
			return false;
		}
		const dynamicContent = selectedBlock?.attributes?.dynamicContent;
		if (
			dynamicContent &&
			dynamicContent?.[ props.name ]?.enable === true
		) {
			return true;
		}
		return false;
	};

	const handleOnChange = ( newValue ) => {
		if ( props.name && props?.setAttributes ) {
			props.setAttributes( { [ props.name ]: newValue } );
		} else if ( props?.setAttributes ) {
			props?.setAttributes( {
				[ props?.data?.label ]: newValue,
			} );
		}

		if ( props?.onChange ) {
			props?.onChange( newValue );
			setInputData( newValue );
		}
	};
	const handleOnBlur = ( newValue ) => {
		if ( props?.onBlur ) {
			props.onBlur( newValue );
		}
	};

	const resetValues = ( defaultValues ) => {
		if ( props?.onChange ) {
			props?.onChange( defaultValues[ props?.data?.label ] );
			setInputData( defaultValues[ props?.data?.label ] );
		}
	};

	const HeaderControls = () => {
		return (
			<div className="srfm-control__header">
				<ResponsiveToggle label={ props?.label } />
				<div className="srfm-number-control__actions srfm-control__actions">
					<SRFMReset
						onReset={ resetValues }
						value={ inputData }
						attributeNames={ [ props?.data?.label ] }
						setAttributes={ props?.setAttributes }
						isFormSpecific={ props?.isFormSpecific }
					/>
				</div>
			</div>
		);
	};

	const controlName = getIdFromString( props.label ); // there is no label props that's why keep hard coded label
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

	const genericSmartTags = window.srfm_block_data?.smart_tags_array
		? Object.entries( window.srfm_block_data.smart_tags_array )
		: [];

	return (
		<div ref={ panelRef } className="components-base-control">
			{ controlBeforeDomElement }
			<div
				className={ classnames(
					'components-base-control srfm-text-control srfm-size-type-field-tabs',
					isEnableDynamicContent()
						? ' srfm-text-control--open-dynamic-content'
						: '',
					props.className
				) }
			>
				{ props?.variant !== 'inline' && props?.showHeaderControls && (
					<HeaderControls />
				) }
				<div
					className={ classnames(
						'srfm-text-control__controls',
						'srfm-text-control__controls-' + props?.variant
					) }
				>
					{ ! isEnableDynamicContent() && (
						<>
							{ props?.variant !== 'textarea' && (
								<TextControl
									label={
										props?.variant === 'inline' ||
										( props?.variant !== 'inline' &&
											! props?.showHeaderControls )
											? props?.label
											: false
									}
									type={ props?.type }
									value={ inputData }
									onChange={ handleOnChange }
									onBlur={ handleOnBlur }
									disabled={ props?.disabled }
									autoComplete={ props?.autoComplete }
									readOnly={ isEnableDynamicContent() }
									placeholder={ props?.placeholder }
								/>
							) }
							{ props?.variant === 'textarea' && (
								<TextareaControl
									label={
										! props?.showHeaderControls
											? props?.label
											: false
									}
									value={ inputData }
									onChange={ handleOnChange }
									autoComplete={ props?.autoComplete }
									readOnly={ isEnableDynamicContent() }
								/>
							) }

							{ props?.withSmartTagDropdown === true && (
								<EditorSmartTagList
									tagsArray={ [
										{
											tags: genericSmartTags,
											label: __(
												'Generic tags',
												'sureforms'
											),
										},
									] }
									setTargetData={ ( tag ) =>
										handleOnChange( inputData + tag )
									}
								/>
							) }
						</>
					) }
					{ isEnableDynamicContent() &&
						props?.variant === 'inline' && (
						<div className="components-base-control">
							<div className="components-base-control__field">
								<label className="components-base-control__label">
									{ props.label }
								</label>
							</div>
						</div>
					) }
					{ registerTextExtender }
				</div>

				<SRFMHelpText text={ props.help } />
			</div>
			{ controlAfterDomElement }
		</div>
	);
};

SRFMTextControl.defaultProps = {
	label: '',
	type: 'text',
	className: '',
	allowReset: true,
	resetFallbackValue: '',
	placeholder: null,
	variant: 'full-width',
	autoComplete: 'off',
	showHeaderControls: true,
	dynamicContentType: 'url', // url | text
	enableDynamicContent: false,
	help: false,
	isFormSpecific: false,
	withSmartTagDropdown: false,
	disabled: false,
};

export default SRFMTextControl;
