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
import * as addressCompact from '@Blocks/address-compact';
import * as url from '@Blocks/url';
import * as inlineButton from '@Blocks/inline-button';
import { registerBlocks } from '@Blocks/register-block';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter, applyFilters } from '@wordpress/hooks';
import { useDeviceType } from '@Controls/getPreviewType';
import { BlockControls } from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';
import parse from 'html-react-parser';
import svgIcons from '@Svg/svgs.json';
import { getBlockTypes } from '@Blocks/util';

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
	addressCompact,
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
								? `srfm-block-single srfm-${ slug }-block-wrap srfm-block-width-${ width }`
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
		const { name, setAttributes } = props;

		const excludeBlocks = applyFilters(
			'srfm.ExcludeWithToolbarButton',
			''
		);

		const allowedBlocks = getBlockTypes( excludeBlocks );

		const oneColIcon = parse( svgIcons.width_full );
		const twoColIcon = parse( svgIcons.with_two_col );
		const threeColIcon = parse( svgIcons.width_three_col );
		const fourColIcon = parse( svgIcons.width_four_col );

		if ( allowedBlocks.includes( name ) ) {
			return (
				<>
					<BlockControls>
						<ToolbarGroup>
							<ToolbarButton
								icon={ oneColIcon }
								label="Full Width"
								onClick={ () => {
									setAttributes( {
										fieldWidth: Number( 100 ),
									} );
								} }
							/>
							<ToolbarButton
								icon={ twoColIcon }
								label="Two Columns"
								onClick={ () => {
									setAttributes( {
										fieldWidth: Number( 50 ),
									} );
								} }
							/>
							<ToolbarButton
								icon={ threeColIcon }
								label="Three Columns"
								onClick={ () => {
									setAttributes( {
										fieldWidth: Number( 33.33 ),
									} );
								} }
							/>
							<ToolbarButton
								icon={ fourColIcon }
								label="Four Columns"
								onClick={ () => {
									setAttributes( {
										fieldWidth: Number( 25 ),
									} );
								} }
							/>
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
		};
	}
	return settings;
}
