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
import { registerBlocks } from '@Blocks/register-block';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter, applyFilters } from '@wordpress/hooks';
import { useDeviceType } from '@Controls/getPreviewType';
import { BlockControls } from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';
import { getBlockTypes } from '@Blocks/util';
import { __, sprintf } from '@wordpress/i18n';
import ConditionalLogic from '@Components/conditional-logic';

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
