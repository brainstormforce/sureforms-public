import * as sfForm from '@Blocks/sureforms-form';
import * as text from '@Blocks/input';
import * as number from '@Blocks/number';
import * as email from '@Blocks/email';
import * as textarea from '@Blocks/textarea';
import * as checkbox from '@Blocks/checkbox';
import * as gdpr from '@Blocks/gdpr';
import * as multiChoice from '@Blocks/multi-choice';
import * as phone from '@Blocks/phone';
import * as select from '@Blocks/dropdown';
import * as address from '@Blocks/address';
import * as url from '@Blocks/url';
import * as inlineButton from '@Blocks/inline-button';
import * as payment from '@Blocks/payment';
import { registerBlocks } from '@Blocks/register-block';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter, applyFilters } from '@wordpress/hooks';
import SlugControl from '@Components/slug-control';
import { getWithoutSlugBlocks } from '@Utils/Helpers';
import { useDeviceType } from '@Controls/getPreviewType';
import { BlockControls } from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarButton, PanelBody } from '@wordpress/components';
import { getBlockTypes } from '@Blocks/util';
import { __, sprintf } from '@wordpress/i18n';
import ConditionalLogic from '@Components/conditional-logic';
import {
	EnhancedMultiChoiceOptions,
	EnhancedDropdownOptions,
} from '@Admin/payment/enhanced-options';

// Register store.
import '../store/store.js';

const registerBlock = [
	text,
	email,
	url,
	textarea,
	multiChoice,
	checkbox,
	gdpr,
	number,
	sfForm,
	phone,
	select,
	address,
	inlineButton,
	payment,
];

if ( 'sureforms_form' === srfm_block_data?.current_screen?.id ) {
	registerBlocks( registerBlock );
	// change the category of the core paragraph block in SureForms post type.
	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'srfm/filter-paragraph-category',
		changeCoreParagraphCategory
	);
} else {
	registerBlocks( [ sfForm ] );

	// Open block inserter and show tooltip on Form block when redirected from Learn section.
	if (
		new URLSearchParams( window.location.search ).get( 'source' ) ===
		'learn'
	) {
		wp.domReady( () => {
			// Give the editor time to fully initialize before opening the inserter.
			setTimeout( () => {
				wp.data
					.dispatch( 'core/edit-post' )
					.setIsInserterOpened( true );

				// Poll for the SureForms Form block item in the inserter.
				let attempts = 0;
				const maxAttempts = 50;
				const interval = setInterval( () => {
					attempts++;
					// Find block items in the inserter and look for the SureForms Form block.
					const blockItems = document.querySelectorAll(
						'.block-editor-block-types-list__item'
					);
					let formBlockItem = null;

					blockItems.forEach( ( item ) => {
						const titleEl = item.querySelector(
							'.block-editor-block-types-list__item-title'
						);
						if (
							titleEl &&
							titleEl.textContent.trim() === 'Form'
						) {
							formBlockItem = item;
						}
					} );

					if ( formBlockItem || attempts >= maxAttempts ) {
						clearInterval( interval );

						if ( formBlockItem ) {
							formBlockItem.style.position = 'relative';

							const tip = document.createElement( 'div' );
							tip.id = 'srfm-form-block-learn-tip';
							tip.style.cssText =
								'position:absolute;top:50%;left:100%;transform:translateY(-50%);margin-left:10px;z-index:99999999;pointer-events:none;';
							tip.innerHTML = `
								<div style="position:absolute;top:50%;left:-4px;transform:translateY(-50%) rotate(45deg);width:8px;height:8px;background:#1e1e1e;"></div>
								<div style="background:#1e1e1e;color:#fff;font-size:13px;padding:6px 12px;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,0.15);white-space:nowrap;">
									${ __( 'Click here to insert a form', 'sureforms' ) }
								</div>
							`;

							formBlockItem.appendChild( tip );

							// Auto-dismiss after 5 seconds.
							setTimeout( () => tip.remove(), 5000 );
						}
					}
				}, 100 );
			}, 500 );
		} );
	}
}

// Width feature for all sureforms blocks.
const blockWidthWrapperProps = createHigherOrderComponent(
	( BlockListBlock ) => {
		return ( props ) => {
			const { attributes, name } = props;

			const wrapperProps = {
				...props.wrapperProps,
			};

			const allowedBlocks = getBlockTypes();

			if ( allowedBlocks.includes( name ) ) {
				const fieldWidth = attributes?.fieldWidth
					? String( attributes.fieldWidth )
					: '100';
				const width = fieldWidth
					? fieldWidth.replace( '.', '-' )
					: '100';
				const slug = name.replace( 'srfm/', '' );

				return (
					<BlockListBlock
						{ ...props }
						wrapperProps={ wrapperProps }
						className={
							attributes?.fieldWidth &&
							'Mobile' !== useDeviceType()
								? `srfm-block srfm-block-single srfm-${ slug }-block-wrap srfm-block-width-${ width }`
								: ''
						}
					/>
				);
			}
			return <BlockListBlock { ...props } />;
		};
	},
	'blockWidthWrapperProps'
);

addFilter(
	'editor.BlockListBlock',
	'srfm/with-block-with-wrapper-props',
	blockWidthWrapperProps
);

const withToolbarButton = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { name, setAttributes, attributes } = props;

		const excludeBlocks = applyFilters(
			'srfm.ExcludeWithToolbarButton',
			''
		);

		const allowedBlocks = getBlockTypes( excludeBlocks );

		if ( allowedBlocks.includes( name ) ) {
			return (
				<>
					<BlockControls>
						<ToolbarGroup>
							{ [ 100, 75, 66.66, 50, 33.33, 25 ].map(
								( width ) => {
									let labelText;
									if ( width === 33.33 ) {
										labelText = '33%';
									} else if ( width === 66.66 ) {
										labelText = '67%';
									} else {
										labelText = `${ width }%`;
									}
									const labelWithText = sprintf(
										// translators: %s: Width of the block
										__( '%s Width', 'sureforms' ),
										labelText
									);

									const selectedClass =
										attributes?.fieldWidth === width
											? 'is-selected srfm-toolbar-width-setting-button'
											: 'srfm-toolbar-width-setting-button';

									return (
										<ToolbarButton
											key={ width }
											className={ selectedClass }
											icon={
												<span className="srfm-toolbar-width-setting-icon">
													{ labelText }
												</span>
											}
											label={ labelWithText }
											onClick={ () =>
												setAttributes( {
													fieldWidth: Number( width ),
												} )
											}
										/>
									);
								}
							) }
						</ToolbarGroup>
					</BlockControls>
					<BlockEdit { ...props } />
				</>
			);
		}
		return <BlockEdit { ...props } />;
	};
}, 'withToolbarButton' );

wp.hooks.addFilter(
	'editor.BlockEdit',
	'srfm/with-toolbar-button',
	withToolbarButton
);

function changeCoreParagraphCategory( settings, name ) {
	if ( name === 'core/paragraph' ) {
		return {
			...settings,
			category: 'sureforms',
			// modify additional classnames to add a default class to target in the frontend.
			attributes: {
				...settings.attributes,
				className: {
					type: 'string',
					default: 'srfm-custom-wp-paragraph',
				},
			},
		};
	}
	return settings;
}

// Disable the responsive toggle for SureForms.
addFilter(
	'srfm.enable.responsiveToggle',
	'srfm/disable-responsive-toggle',
	() => false
);

/**
 * Add conditional logic to the advanced heading settings.
 */
addFilter(
	'srfm.advanced-heading.settings.advance',
	'srfm/advanced-heading-settings-advance',
	( items, props ) => {
		const { setAttributes, attributes } = props;
		return [
			...items,
			{
				id: 'conditional-logic',
				content: (
					<ConditionalLogic { ...{ setAttributes, attributes } } />
				),
			},
		];
	}
);

/**
 * Temporary filter hooks for enhancing MultiChoice and Dropdown block options to support "Show Value" functionality in free version.
 *
 * Note:
 * - These filters were originally present only in the PRO version (SureForms PRO) to handle calculations.
 * - Now, "Show Value" is needed in the free version as part of the payment feature, so these filters have been added to the free plugin.
 * - Ideally, "Show Value" should be a part of the block implementation itself, not added via filters.
 * - In the future, these filters should be removed from PRO, as this logic will reside directly in the blocks provided in the free plugin.
 * - This is a temporary bridge for backwards compatibility and migration from PRO to free feature parity.
 */
addFilter(
	'srfm.blocks.multichoice.options.enhance',
	'srfm/multi-choice/options',
	EnhancedMultiChoiceOptions
);

addFilter(
	'srfm.blocks.dropdown.options.enhance',
	'srfm/dropdown/options',
	EnhancedDropdownOptions
);

// Register filter to inject the Field Slug panel after the Attributes panel.
addFilter(
	'srfm.block.after.attributes.panel.body',
	'sureforms/field-slug-panel',
	( panels, props ) => {
		if ( getWithoutSlugBlocks().includes( props?.name ) ) {
			return panels;
		}
		return [
			...panels,
			{
				id: 'field-slug',
				component: (
					<PanelBody
						key="field-slug"
						title={ __( 'Field Slug', 'sureforms' ) }
						initialOpen={ false }
					>
						<SlugControl
							slug={ props?.attributes?.slug ?? '' }
							setAttributes={ props?.setAttributes }
							clientId={ props?.clientId }
						/>
					</PanelBody>
				),
			},
		];
	}
);
