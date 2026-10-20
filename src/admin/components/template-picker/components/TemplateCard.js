import { __, sprintf } from '@wordpress/i18n';
import { useRef, useState } from '@wordpress/element';
import { Button, Tooltip } from '@bsf/force-ui';
import { Crown, ExternalLink } from 'lucide-react';
import { cn } from '@Utils/Helpers';
import {
	getLockLabels,
	isPremiumTemplate,
	openTemplateUnlock,
} from './template-features.js';
import { resolveLook } from '../template-colors.js';

// Rows the tile has room for before the form runs off its bottom edge.
const MAX_ROWS = 4;

const CHOICE_TYPES = [ 'multi-choice', 'rating', 'nps' ];
const CONSENT_TYPES = [ 'gdpr', 'checkbox' ];

/**
 * Name of a paid plan, as SureForms sells it.
 *
 * @param {string} tier `starter`, `pro` or `business`.
 * @return {string} Plan name.
 */
const getPlanName = ( tier ) =>
	( {
		starter: __( 'Starter', 'sureforms' ),
		pro: __( 'Pro', 'sureforms' ),
		business: __( 'Business', 'sureforms' ),
	} )[ tier ] || __( 'Premium', 'sureforms' );

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
 * instead. Decorative; the card's text names the template.
 *
 * @param {Object} props          Props.
 * @param {Object} props.template Template.
 * @param {Object} props.look     The template's look, from resolveLook().
 */
export const CardPicture = ( { template, look } ) => {
	const [ failed, setFailed ] = useState( false );
	if ( ! template.thumbnail ) {
		return <MiniForm template={ template } look={ look } />;
	}
	if ( failed ) {
		return null;
	}
	return (
		<img
			src={ template.thumbnail }
			alt=""
			loading="lazy"
			decoding="async"
			// Twice the tile's size at its widest; the tile's fixed height
			// keeps the grid from shifting while it loads.
			width="760"
			height="520"
			onError={ () => setFailed( true ) }
			className="absolute inset-0 size-full object-cover object-top"
		/>
	);
};

/**
 * Gradient crown on a Premium template, naming its plan.
 *
 * @param {Object} props          Props.
 * @param {Object} props.template Template.
 */
const PremiumCrown = ( { template } ) => {
	const label = sprintf(
		/* translators: %s: plan name, e.g. "Business". */
		__( 'Premium: %s plan', 'sureforms' ),
		getPlanName( template.requires )
	);
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
			<span className="absolute top-2.5 end-2.5 z-[7] flex items-center justify-center size-6 rounded-full text-white bg-[linear-gradient(135deg,#7C3AED_0%,#DB2777_100%)] cursor-help">
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
 * A template in the library: a picture of its form, with its name and
 * description.
 *
 * A template this site can use is one button that opens the preview. A
 * locked one isn't: hovering or focusing it shows its plan and a "See plans"
 * button, which is its only control.
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
	const panelRef = useRef( null );
	const hiddenTap = useRef( false );

	if ( template.is_locked ) {
		const plan = getPlanName( template.requires );
		const needsUpdate = 'update' === template.lock_reason;
		return (
			<div
				hidden={ hidden }
				className={ cn(
					cardClass,
					// Lets mobile Safari show the panel on a tap, as on hover.
					'[@media(hover:none)]:cursor-pointer',
					hidden && 'hidden'
				) }
			>
				<span className={ tileClass } style={ tileStyle }>
					<CardPicture template={ template } look={ look } />
					<TileShade />
					<span
						className={ cn(
							'absolute inset-0 z-[6] flex flex-col items-center justify-center gap-3 p-[22px] text-center bg-[#FFF3EE] rounded-t-[9px]',
							'opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity duration-[220ms] ease-out motion-reduce:transition-none'
						) }
						ref={ panelRef }
					>
						<span className="inline-flex items-center gap-1.5 py-1 px-[11px] text-[11px] leading-4 font-bold uppercase tracking-[.02em] text-[#A8320A] bg-white border border-solid border-[#FAD0B8] rounded-full">
							<Crown className="size-[13px]" aria-hidden="true" />
							{ plan }
						</span>
						<span
							className="text-[15px] leading-[1.3] font-bold text-[#5C2410]"
							aria-hidden="true"
						>
							{ template.title }
						</span>
						<span
							id={ `srfm-template-plan-${ template.id }` }
							className="max-w-[250px] text-[12.5px] leading-[1.5] text-[#8A5438]"
						>
							{ needsUpdate && getLockLabels( template ).notice }
							{ ! needsUpdate &&
								'business' === template.requires &&
								sprintf(
									/* translators: %s: plan name, e.g. "Business". */
									__( 'Part of the SureForms %s plan.', 'sureforms' ),
									plan
								) }
							{ ! needsUpdate &&
								'business' !== template.requires &&
								sprintf(
									/* translators: %s: plan name, e.g. "Starter". */
									__( 'Part of SureForms %s and higher plans.', 'sureforms' ),
									plan
								) }
						</span>
						<Button
							variant="primary"
							size="md"
							icon={ <ExternalLink aria-hidden="true" /> }
							iconPosition="right"
							onPointerDown={ ( event ) => {
								// A touch screen has no hover: the tap that lands on the
								// still hidden button only shows the panel.
								hiddenTap.current =
									'touch' === event.pointerType &&
									Number( window.getComputedStyle( panelRef.current ).opacity ) < 0.5;
							} }
							onClick={ ( event ) => {
								if ( hiddenTap.current ) {
									hiddenTap.current = false;
									// Focused, the panel stays open for the next tap.
									event.currentTarget.focus();
									return;
								}
								openTemplateUnlock( template );
							} }
							aria-describedby={ `srfm-template-title-${ template.id } srfm-template-plan-${ template.id }` }
							className="h-9 px-[15px] gap-[7px] text-[13.5px] rounded-[7px] bg-[#D54407] hover:bg-[#B83A06] shadow-[0_6px_16px_-8px_rgba(213,68,7,.6)] [&>svg]:size-[13px]"
						>
							{ needsUpdate
								? getLockLabels( template ).button
								: __( 'See plans', 'sureforms' ) }
							<span className="sr-only">
								{ __( '(opens in a new tab)', 'sureforms' ) }
							</span>
						</Button>
					</span>
					<PremiumCrown template={ template } />
				</span>
				<CardBody template={ template } />
			</div>
		);
	}

	return (
		<button
			type="button"
			hidden={ hidden }
			onClick={ () => onSelect( template.id ) }
			aria-label={ sprintf(
				/* translators: %s: template name. */
				__( 'Preview the %s template', 'sureforms' ),
				template.title
			) }
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
				{ isPremiumTemplate( template ) && <PremiumCrown template={ template } /> }
			</span>
			<CardBody template={ template } />
		</button>
	);
};

export default TemplateCard;
