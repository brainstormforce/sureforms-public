import { __, sprintf } from '@wordpress/i18n';
import {
	CalendarDays,
	Clock,
	Gauge,
	ListOrdered,
	Mail,
	Paperclip,
	Reply,
	ShieldCheck,
	Star,
} from 'lucide-react';
import { cn } from '@Utils/Helpers';

/**
 * What a template ships with, keyed by the ids in a template's `features` and
 * `pro_features`. Listed in the order cards pick from: the most telling first,
 * since almost every template sends a notification.
 *
 * @since x.x.x
 * @return {Object<string, {label: string, short: string, Icon: Function}>} Features by id.
 */
const getFeatureMap = () => ( {
	multi_step: {
		label: __( 'Multi-step', 'sureforms' ),
		short: __( 'Multi-step', 'sureforms' ),
		Icon: ListOrdered,
	},
	file_upload: {
		label: __( 'File upload', 'sureforms' ),
		short: __( 'File upload', 'sureforms' ),
		Icon: Paperclip,
	},
	autoresponder: {
		label: __( 'Auto-reply email', 'sureforms' ),
		short: __( 'Auto-reply', 'sureforms' ),
		Icon: Reply,
	},
	nps: {
		label: __( 'NPS score', 'sureforms' ),
		short: __( 'NPS', 'sureforms' ),
		Icon: Gauge,
	},
	rating: {
		label: __( 'Rating', 'sureforms' ),
		short: __( 'Rating', 'sureforms' ),
		Icon: Star,
	},
	date_picker: {
		label: __( 'Date picker', 'sureforms' ),
		short: __( 'Date', 'sureforms' ),
		Icon: CalendarDays,
	},
	time_picker: {
		label: __( 'Time picker', 'sureforms' ),
		short: __( 'Time', 'sureforms' ),
		Icon: Clock,
	},
	consent: {
		label: __( 'Consent checkbox', 'sureforms' ),
		short: __( 'Consent', 'sureforms' ),
		Icon: ShieldCheck,
	},
	notification: {
		label: __( 'Email notification', 'sureforms' ),
		short: __( 'Notification', 'sureforms' ),
		Icon: Mail,
	},
} );

let featureMap = null;

/**
 * Known features of a list of ids, most telling first. Unknown ids are skipped.
 *
 * @since x.x.x
 * @param {string[]} ids Feature ids.
 * @return {Array<{id: string, label: string, short: string, Icon: Function}>} Features.
 */
export const getFeatures = ( ids ) => {
	if ( ! Array.isArray( ids ) || ! ids.length ) {
		return [];
	}
	featureMap = featureMap || getFeatureMap();
	return Object.keys( featureMap )
		.filter( ( id ) => ids.includes( id ) )
		.map( ( id ) => ( { id, ...featureMap[ id ] } ) );
};

/**
 * Whether SureForms Pro is active, on the admin screens or in the editor.
 *
 * @since x.x.x
 * @return {boolean} Pro is active.
 */
export const isProActive = () =>
	Boolean(
		window.srfm_admin?.is_pro_active ||
			window.srfm_block_data?.is_pro_active
	);

/**
 * Features the template gains with Pro, when the site doesn't have it.
 *
 * @since x.x.x
 * @param {Object} template Template.
 * @return {Array} Features.
 */
export const getProFeatures = ( template ) =>
	isProActive() ? [] : getFeatures( template?.pro_features );

/**
 * Small chip naming one feature.
 *
 * @since x.x.x
 * @param {Object}   props           Props.
 * @param {string}   props.label     Feature name.
 * @param {string}   props.title     Full name, as a tooltip, when the label is short.
 * @param {Function} props.Icon      Feature icon.
 * @param {string}   props.className Extra classes.
 */
export const FeatureChip = ( { label, title, Icon, className } ) => (
	<span
		title={ title }
		className={ cn(
			'inline-flex items-center gap-1 px-1.5 py-0.5 text-[11px] leading-4 font-medium whitespace-nowrap rounded bg-background-secondary text-text-secondary',
			className
		) }
	>
		<Icon className="size-3 shrink-0" aria-hidden="true" />
		{ label }
	</span>
);

/**
 * "Included" list and the quiet "More with Pro" hint for the full preview.
 *
 * @since x.x.x
 * @param {Object} props          Props.
 * @param {Object} props.template Template.
 */
export const IncludedFeatures = ( { template } ) => {
	const features = getFeatures( template?.features );
	const proFeatures = getProFeatures( template );

	if ( ! features.length && ! proFeatures.length ) {
		return null;
	}

	return (
		<div className="flex flex-col gap-1.5 min-w-0">
			{ features.length > 0 && (
				<div className="flex items-start gap-2 min-w-0">
					<span
						id="srfm-template-included-label"
						className="shrink-0 text-xs leading-5 font-semibold text-text-primary"
					>
						{ __( 'Included', 'sureforms' ) }
					</span>
					<ul
						className="flex flex-wrap gap-1.5 m-0 p-0 list-none min-w-0"
						aria-labelledby="srfm-template-included-label"
					>
						{ features.map( ( feature ) => (
							<li key={ feature.id } className="m-0">
								<FeatureChip
									label={ feature.label }
									Icon={ feature.Icon }
									className="bg-background-tertiary"
								/>
							</li>
						) ) }
					</ul>
				</div>
			) }
			{ proFeatures.length > 0 && (
				<p className="m-0 text-xs text-text-tertiary">
					{ sprintf(
						/* translators: %s: comma-separated feature names, e.g. "File upload, Rating". */
						__( 'More with Pro: %s', 'sureforms' ),
						proFeatures
							.map( ( feature ) => feature.label )
							.join( ', ' )
					) }
				</p>
			) }
		</div>
	);
};
