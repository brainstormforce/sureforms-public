import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button } from '@bsf/force-ui';
import { Crown, ExternalLink } from 'lucide-react';
import { cn } from '@Utils/Helpers';
import {
	getLockLabels,
	openTemplateUnlock,
} from './template-features.js';
import { resolveLook } from '../template-colors.js';

// Rows the tile has room for before the form runs off its bottom edge.
const MAX_ROWS = 4;

const CHOICE_TYPES = [ 'multi-choice', 'rating', 'nps' ];
const CONSENT_TYPES = [ 'gdpr', 'checkbox' ];

const boxClass =
	'flex items-center px-[7px] text-[9px] text-[#9CA3AF] border border-solid border-[#E5E7EB] rounded-[3px] truncate';

/**
 * One field of the drawn form.
 *
 * @param {Object} props       Props.
 * @param {Object} props.field Card field from the payload: `t`, `l`, `p`, `o`, `w`.
 * @param {Object} props.look  The template's look, from resolveLook().
 */
const MiniField = ( { field, look } ) => {
	// Filled like the real form's selected option and button.
	const filled = { backgroundColor: look.primary, borderColor: look.primary, color: look.onPrimary };
	if ( CONSENT_TYPES.includes( field.t ) ) {
		return (
			<span className="flex-1 min-w-0 flex gap-1.5 text-[8px] leading-[1.3] text-[#6B7280]">
				<span className="flex-none size-2.5 border border-solid border-[#D1D5DB] rounded-sm" />
				<span className="line-clamp-2">{ field.l }</span>
			</span>
		);
	}

	let control;
	if ( 'textarea' === field.t ) {
		control = (
			<span className={ cn( boxClass, 'items-start h-[38px] py-[5px]' ) }>
				{ field.p }
			</span>
		);
	} else if ( CHOICE_TYPES.includes( field.t ) ) {
		const options = field.o?.length ? field.o : [ '1', '2', '3' ];
		control = (
			<span className="flex gap-[5px]">
				{ options.map( ( option, index ) => (
					<span
						key={ index }
						className={ cn(
							'flex-1 min-w-0 h-6 px-1 flex items-center justify-center text-[9px] border border-solid rounded truncate',
							0 === index
								? 'font-semibold'
								: 'border-[#E5E7EB] text-[#374151]'
						) }
						style={ 0 === index ? filled : undefined }
					>
						<span className="truncate">{ option }</span>
					</span>
				) ) }
			</span>
		);
	} else if ( 'inline-button' === field.t ) {
		control = (
			<span
				className="flex items-center justify-center h-[22px] px-[7px] text-[9px] font-semibold rounded-[3px] truncate"
				style={ filled }
			>
				{ field.l }
			</span>
		);
	} else {
		control = (
			<span className={ cn( boxClass, 'h-[22px]' ) }>{ field.p }</span>
		);
	}

	return (
		<span className="flex-1 min-w-0 flex flex-col gap-1">
			{ 'inline-button' !== field.t && (
				<span className="text-[9px] font-medium text-[#374151] whitespace-nowrap overflow-hidden">
					{ field.l }
				</span>
			) }
			{ control }
		</span>
	);
};

/**
 * Fields in rows: two half-width fields in a row share it.
 *
 * @param {Array} fields Card fields.
 * @return {Array<Array>} Rows of one or two fields.
 */
export const getMiniRows = ( fields ) => {
	const rows = [];
	for ( let i = 0; i < fields.length && rows.length < MAX_ROWS; i++ ) {
		if ( 'half' === fields[ i ].w && 'half' === fields[ i + 1 ]?.w ) {
			rows.push( [ fields[ i ], fields[ i + 1 ] ] );
			i++;
		} else {
			rows.push( [ fields[ i ] ] );
		}
	}
	return rows;
};

/**
 * The template's first fields drawn as a small form on its page colour, in
 * the colors the form starts with: the card's picture when the template has
 * no screenshot.
 *
 * @param {Object} props          Props.
 * @param {Object} props.template Template.
 * @param {Object} props.look     The template's look, from resolveLook().
 */
const MiniForm = ( { template, look } ) => (
	<span
		className="absolute start-6 end-6 top-[22px] flex flex-col gap-[9px] p-3.5 text-start leading-[1.45] bg-white border border-b-0 border-solid border-[#E5E7EB] rounded-t-lg"
		aria-hidden="true"
	>
		<span className="text-[13px] font-semibold text-[#111827] truncate">
			{ template.title }
		</span>
		{ getMiniRows( template.card_fields || [] ).map( ( row, index ) => (
			<span key={ index } className="flex gap-2">
				{ row.map( ( field, fieldIndex ) => (
					<MiniField key={ fieldIndex } field={ field } look={ look } />
				) ) }
			</span>
		) ) }
	</span>
);

/**
 * The card's picture: a screenshot of the template's preview, the white form
 * on its page color, cropped to the tile from the top. A template without
 * one (added through `srfm_form_templates`) gets its first fields drawn
 * instead. Its alt text is the template's name, already translated, as on
 * Starter Templates' cards; screen readers name the card by its button's
 * aria-label.
 *
 * @param {Object} props          Props.
 * @param {Object} props.template Template.
 * @param {Object} props.look     The template's look, from resolveLook().
 */
export const CardPicture = ( { template, look } ) => {
	const [ failed, setFailed ] = useState( false );
	if ( ! template.thumbnail || failed ) {
		return <MiniForm template={ template } look={ look } />;
	}
	return (
		<img
			src={ template.thumbnail }
			alt={ template.title }
			loading="lazy"
			decoding="async"
			// Twice the tile's width at its widest; the tile's fixed height
			// keeps the grid from shifting while it loads.
			width="760"
			height="520"
			onError={ () => setFailed( true ) }
			// Full width from the top, never zoomed: a short form (a one-line
			// sign-up) ends on the tile's tint instead of being blown up to
			// fill it.
			className="absolute inset-x-0 top-0 w-full h-auto"
		/>
	);
};

/**
 * Gradient crown on a Premium template this site can't use yet. Decorative:
 * the card's label and its hover panel already say it needs Premium.
 */
const PremiumCrown = () => (
	<span
		className="absolute top-2.5 end-2.5 z-[7] flex items-center justify-center size-6 rounded-full text-white bg-[linear-gradient(135deg,#7C3AED_0%,#DB2777_100%)]"
		aria-hidden="true"
	>
		<Crown className="size-[13px]" />
	</span>
);

const tileClass =
	'relative block h-[260px] overflow-hidden rounded-t-[9px] isolate';

// Darkens the tile's bottom edge, where the form is cut off.
const TileShade = () => (
	<span
		className="absolute inset-x-0 bottom-0 z-[1] h-[72px] pointer-events-none bg-[linear-gradient(to_top,rgba(17,24,39,.09)_0%,rgba(17,24,39,.03)_45%,rgba(17,24,39,0)_100%)]"
		aria-hidden="true"
	/>
);

const cardClass =
	'group relative flex flex-col bg-background-primary border border-solid border-[#E5E7EB] rounded-[10px] transition-[border-color,box-shadow] duration-150 ease-out hover:border-[#D1D5DB] hover:shadow-[0_2px_8px_rgba(17,24,39,.06)] motion-reduce:transition-none';

/**
 * Title and one-line description under the tile.
 *
 * @param {Object} props          Props.
 * @param {Object} props.template Template.
 */
const CardBody = ( { template } ) => (
	<span className="flex flex-col gap-[5px] pt-[15px] px-4 pb-4 text-start leading-[1.45] border-0 border-t border-solid border-[#E5E7EB]">
		<span
			id={ `srfm-template-title-${ template.id }` }
			className="text-[15px] font-semibold text-[#111827] truncate"
		>
			{ template.title }
		</span>
		<span
			id={ `srfm-template-description-${ template.id }` }
			className="text-[13px] text-[#6B7280] truncate"
		>
			{ template.description }
		</span>
	</span>
);

/**
 * Accessible name of the button that opens a template's preview.
 *
 * @param {Object} template Template.
 * @return {string} Label.
 */
export const getPreviewLabel = ( template ) =>
	sprintf(
		/* translators: %s: template name. */
		__( 'Preview the %s template', 'sureforms' ),
		template.title
	);

/**
 * Accessible name of a locked card, which opens the pricing page, or the
 * update screen when only a newer SureForms Pro is missing.
 *
 * @param {Object} template Template.
 * @return {string} Label.
 */
export const getUnlockLabel = ( template ) =>
	'update' === template.lock_reason
		? sprintf(
			/* translators: %s: template name. */
			__( 'Update SureForms Premium to use the %s template (opens in a new tab)', 'sureforms' ),
			template.title
		  )
		: sprintf(
			/* translators: %s: template name. */
			__( 'See plans for the %s template (Premium, opens in a new tab)', 'sureforms' ),
			template.title
		  );

/**
 * A template in the library: a picture of its form, with its name and
 * description.
 *
 * A template this site can use is one button that opens the preview; a
 * Premium one it has the plan for looks like any other, with no crown. A
 * locked one is one button that opens the pricing page (or the update
 * screen); Premium templates aren't previewed before they're bought.
 * It carries the crown, and hovering or focusing it shows "Premium" and
 * "See plans".
 *
 * @param {Object}   props          Props.
 * @param {Object}   props.template Template.
 * @param {Array}    props.presets  Style presets; defaults to the admin page's.
 * @param {boolean}  props.hidden   Filtered out.
 * @param {Function} props.onSelect Open the template's preview.
 */
const TemplateCard = ( { template, presets, hidden, onSelect } ) => {
	// The look the form starts with: the tile is its page, the drawing its form.
	const look = resolveLook(
		{},
		template.theme,
		presets || window.srfm_admin?.form_template_themes
	);
	const tileStyle = { backgroundColor: look.page };

	if ( template.is_locked ) {
		const needsUpdate = 'update' === template.lock_reason;
		return (
			<button
				type="button"
				hidden={ hidden }
				data-template-id={ template.id }
				onClick={ () => openTemplateUnlock( template ) }
				aria-label={ getUnlockLabel( template ) }
				aria-describedby={
					template.description
						? `srfm-template-description-${ template.id }`
						: undefined
				}
				className={ cn(
					cardClass,
					'w-full p-0 m-0 text-start cursor-pointer font-[inherit]',
					'focus:outline-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#DC4809]',
					hidden && 'hidden'
				) }
			>
				<span className={ tileClass } style={ tileStyle }>
					<CardPicture template={ template } look={ look } />
					<TileShade />
					<span
						className={ cn(
							'absolute inset-0 z-[6] flex flex-col items-center justify-center gap-3 p-[22px] text-center bg-[#FFF3EE] rounded-t-[9px]',
							'opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100 transition-opacity duration-200 ease-out motion-reduce:transition-none'
						) }
						aria-hidden="true"
					>
						<span className="inline-flex items-center gap-1.5 py-1 px-[11px] text-[11px] leading-4 font-bold uppercase tracking-[.02em] text-[#A8320A] bg-white border border-solid border-[#FAD0B8] rounded-full">
							<Crown className="size-[13px]" />
							{ __( 'Premium', 'sureforms' ) }
						</span>
						<span className="text-[15px] leading-[1.3] font-bold text-[#5C2410]">
							{ template.title }
						</span>
						<span className="max-w-[250px] text-[12.5px] leading-[1.5] text-[#8A5438]">
							{ needsUpdate
								? getLockLabels( template ).notice
								: __( 'Available with SureForms Premium.', 'sureforms' ) }
						</span>
						{ /* A label inside the card's button, not a second control. */ }
						<Button
							tag="span"
							variant="primary"
							size="md"
							icon={ <ExternalLink /> }
							iconPosition="right"
							className="h-9 px-[15px] gap-[7px] text-[13.5px] rounded-[7px] bg-[#D54407] hover:bg-[#B83A06] shadow-[0_6px_16px_-8px_rgba(213,68,7,.6)] [&>svg]:size-[13px]"
						>
							{ needsUpdate
								? getLockLabels( template ).button
								: __( 'See plans', 'sureforms' ) }
						</Button>
					</span>
					<PremiumCrown />
				</span>
				<CardBody template={ template } />
			</button>
		);
	}

	return (
		<button
			type="button"
			hidden={ hidden }
			data-template-id={ template.id }
			onClick={ () => onSelect( template.id ) }
			aria-label={ getPreviewLabel( template ) }
			aria-describedby={
				template.description
					? `srfm-template-description-${ template.id }`
					: undefined
			}
			className={ cn(
				cardClass,
				'w-full p-0 m-0 text-start cursor-pointer font-[inherit]',
				'focus:outline-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#DC4809]',
				hidden && 'hidden'
			) }
		>
			<span className={ cn( tileClass, 'w-full group/tile' ) } style={ tileStyle }>
				<CardPicture template={ template } look={ look } />
				<TileShade />
				<span
					className={ cn(
						'absolute inset-0 z-[2] flex items-center justify-center bg-[rgba(17,24,39,.42)]',
						'opacity-0 group-hover/tile:opacity-100 group-focus-visible:opacity-100 transition-opacity duration-150 ease-out motion-reduce:transition-none'
					) }
					aria-hidden="true"
				>
					<span className="inline-flex items-center h-9 px-[13px] text-[13px] font-semibold text-white bg-[#D54407] rounded-md">
						{ __( 'Use this template', 'sureforms' ) }
					</span>
				</span>
			</span>
			<CardBody template={ template } />
		</button>
	);
};

export default TemplateCard;
