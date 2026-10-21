import { __, sprintf } from '@wordpress/i18n';
import { Button, Tooltip } from '@bsf/force-ui';
import { Crown, ExternalLink } from 'lucide-react';
import { cn } from '@Utils/Helpers';
import {
	getLockLabels,
	isPremiumTemplate,
	openTemplateUnlock,
} from './template-features.js';

// Tile colour per catalog theme. Midnight gets a light slate: the drawn form stays light.
const TILE_COLORS = {
	ocean: '#E3EBFB',
	mint: '#DEF2E8',
	lavender: '#EAE5FA',
	sunset: '#FAE6DD',
	sand: '#F8EED6',
	midnight: '#E4E8EF',
};

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
 */
const MiniField = ( { field } ) => {
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
								? 'bg-[#D54407] border-[#D54407] text-white font-semibold'
								: 'border-[#E5E7EB] text-[#374151]'
						) }
					>
						<span className="truncate">{ option }</span>
					</span>
				) ) }
			</span>
		);
	} else if ( 'inline-button' === field.t ) {
		control = (
			<span className="flex items-center justify-center h-[22px] px-[7px] text-[9px] font-semibold text-white bg-[#D54407] rounded-[3px] truncate">
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
 * The card's picture: the template's first fields drawn as a small form on
 * its theme colour. Decorative; the card's text names the template.
 *
 * @param {Object} props          Props.
 * @param {Object} props.template Template.
 */
const MiniForm = ( { template } ) => (
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
					<MiniField key={ fieldIndex } field={ field } />
				) ) }
			</span>
		) ) }
	</span>
);

/**
 * Gradient crown on a Premium template.
 */
const PremiumCrown = () => {
	// Users only ever see "Premium", whichever plan the template needs.
	const label = __( 'Premium', 'sureforms' );
	return (
		<Tooltip
			content={ label }
			placement="bottom-end"
			arrow
			triggers={ [ 'hover' ] }
			// Rendered outside the screen's root, in the container the admin
			// styles also apply to, and above the editor's Change template dialog.
			tooltipPortalId="srfm-dashboard-container-tooltip"
			className="z-[999999]"
		>
			<span className="absolute top-2.5 end-2.5 z-[7] pointer-events-auto flex items-center justify-center size-6 rounded-full text-white bg-[linear-gradient(135deg,#7C3AED_0%,#DB2777_100%)] cursor-help">
				<Crown className="size-[13px]" aria-hidden="true" />
				<span className="sr-only">{ label }</span>
			</span>
		</Tooltip>
	);
};

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
 * Accessible name of the button that opens a template's preview. The button's
 * label replaces the crown's hidden text, so Premium is named here.
 *
 * @param {Object} template Template.
 * @return {string} Label.
 */
export const getPreviewLabel = ( template ) =>
	isPremiumTemplate( template )
		? sprintf(
			/* translators: %s: template name. */
			__( 'Preview the %s template (Premium)', 'sureforms' ),
			template.title
		  )
		: sprintf(
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
			__( 'Update SureForms Pro to use the %s template (opens in a new tab)', 'sureforms' ),
			template.title
		  )
		: sprintf(
			/* translators: %s: template name. */
			__( 'See plans for the %s template (Premium, opens in a new tab)', 'sureforms' ),
			template.title
		  );

/**
 * A template in the library: its first fields drawn as a small form, with
 * its name and description.
 *
 * A template this site can use is one button that opens the preview. A
 * locked one is one button that opens the pricing page (or the update
 * screen); Premium templates aren't previewed before they're bought.
 * Hovering or focusing it shows "Premium" and "See plans".
 *
 * @param {Object}   props          Props.
 * @param {Object}   props.template Template.
 * @param {boolean}  props.hidden   Filtered out.
 * @param {Function} props.onSelect Open the template's preview.
 */
const TemplateCard = ( { template, hidden, onSelect } ) => {
	const tileStyle = {
		backgroundColor: TILE_COLORS[ template.theme ] || TILE_COLORS.ocean,
	};

	if ( template.is_locked ) {
		const needsUpdate = 'update' === template.lock_reason;
		return (
			<button
				type="button"
				hidden={ hidden }
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
					<MiniForm template={ template } />
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
							className="h-9 px-[15px] gap-[7px] text-[13.5px] rounded-[7px] shadow-[0_6px_16px_-8px_rgba(213,68,7,.6)] [&>svg]:size-[13px]"
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
				<MiniForm template={ template } />
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
				{ isPremiumTemplate( template ) && <PremiumCrown /> }
			</span>
			<CardBody template={ template } />
		</button>
	);
};

export default TemplateCard;
