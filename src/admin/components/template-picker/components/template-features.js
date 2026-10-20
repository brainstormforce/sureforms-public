import { __, sprintf } from '@wordpress/i18n';
import {
	Calculator,
	CalendarDays,
	ChartColumn,
	Clock,
	CreditCard,
	FilePlus,
	FileText,
	Gauge,
	GitBranch,
	GraduationCap,
	History,
	ListOrdered,
	Lock,
	Mail,
	MailCheck,
	MailPlus,
	MessageSquareText,
	MessagesSquare,
	Paperclip,
	PenTool,
	Repeat,
	Reply,
	Save,
	ShieldCheck,
	SlidersHorizontal,
	Star,
	UserPlus,
} from 'lucide-react';
import { cn } from '@Utils/Helpers';
import { trackTemplateAction } from '@Utils/form-templates';

/**
 * What a template ships with, keyed by the ids in a template's `features` and
 * `pro_features`. Listed in the order cards pick from: the most telling first,
 * since almost every template sends a notification.
 *
 * @since x.x.x
 * @return {Object<string, {label: string, Icon: Function}>} Features by id.
 */
const getFeatureMap = () => ( {
	quiz: {
		label: __( 'Quiz scoring', 'sureforms' ),
		Icon: GraduationCap,
	},
	calculation: {
		label: __( 'Calculations', 'sureforms' ),
		Icon: Calculator,
	},
	payment: {
		label: __( 'Payments', 'sureforms' ),
		Icon: CreditCard,
	},
	conversational: {
		label: __( 'Conversational form', 'sureforms' ),
		Icon: MessagesSquare,
	},
	survey: {
		label: __( 'Survey reports', 'sureforms' ),
		Icon: ChartColumn,
	},
	conditional_logic: {
		label: __( 'Conditional logic', 'sureforms' ),
		Icon: GitBranch,
	},
	conditional_confirmations: {
		label: __( 'Thank-you message per answer', 'sureforms' ),
		Icon: MessageSquareText,
	},
	conditional_emails: {
		label: __( 'Conditional emails', 'sureforms' ),
		Icon: MailCheck,
	},
	user_registration: {
		label: __( 'User registration', 'sureforms' ),
		Icon: UserPlus,
	},
	post_submission: {
		label: __( 'Creates a post', 'sureforms' ),
		Icon: FilePlus,
	},
	signature: {
		label: __( 'Signature', 'sureforms' ),
		Icon: PenTool,
	},
	pdf: {
		label: __( 'PDF copy', 'sureforms' ),
		Icon: FileText,
	},
	save_resume: {
		label: __( 'Save and resume', 'sureforms' ),
		Icon: Save,
	},
	partial_entries: {
		label: __( 'Partial entries', 'sureforms' ),
		Icon: History,
	},
	repeater: {
		label: __( 'Repeatable fields', 'sureforms' ),
		Icon: Repeat,
	},
	slider: {
		label: __( 'Slider', 'sureforms' ),
		Icon: SlidersHorizontal,
	},
	multi_step: {
		label: __( 'Multi-step', 'sureforms' ),
		Icon: ListOrdered,
	},
	file_upload: {
		label: __( 'File upload', 'sureforms' ),
		Icon: Paperclip,
	},
	email_attachments: {
		label: __( 'Uploads attached to emails', 'sureforms' ),
		Icon: MailPlus,
	},
	autoresponder: {
		label: __( 'Auto-reply email', 'sureforms' ),
		Icon: Reply,
	},
	nps: {
		label: __( 'NPS score', 'sureforms' ),
		Icon: Gauge,
	},
	rating: {
		label: __( 'Rating', 'sureforms' ),
		Icon: Star,
	},
	date_picker: {
		label: __( 'Date picker', 'sureforms' ),
		Icon: CalendarDays,
	},
	time_picker: {
		label: __( 'Time picker', 'sureforms' ),
		Icon: Clock,
	},
	consent: {
		label: __( 'Consent checkbox', 'sureforms' ),
		Icon: ShieldCheck,
	},
	notification: {
		label: __( 'Email notification', 'sureforms' ),
		Icon: Mail,
	},
} );

let featureMap = null;

/**
 * Known features of a list of ids, most telling first. Unknown ids are skipped.
 *
 * @since x.x.x
 * @param {string[]} ids Feature ids.
 * @return {Array<{id: string, label: string, Icon: Function}>} Features.
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
 * Labels for a locked template. The badge always reads "Premium", whatever
 * plan the template needs.
 *
 * @since x.x.x
 * @param {Object} template Template.
 * @return {{badge: string, button: string, notice: string}} Labels; `notice` is '' unless SureForms Pro needs an update.
 */
export const getLockLabels = ( template ) => {
	const needsUpdate = 'update' === template?.lock_reason;
	return {
		badge: __( 'Premium', 'sureforms' ),
		button: needsUpdate
			? __( 'Update SureForms Pro', 'sureforms' )
			: __( 'Upgrade to unlock', 'sureforms' ),
		notice: needsUpdate
			? __( 'Update SureForms Pro to use this template', 'sureforms' )
			: '',
	};
};

/**
 * Whether a template needs a paid plan, whether or not this site has it.
 *
 * @since x.x.x
 * @param {Object} template Template.
 * @return {boolean} Paid template.
 */
export const isPremiumTemplate = ( template ) =>
	Boolean( template?.requires ) && 'free' !== template.requires;

/**
 * Whether a locked template's preview shows its picture instead of the live
 * form, which can't render the blocks it is built on here.
 *
 * @since x.x.x
 * @param {Object} template Template.
 * @return {boolean} Show the picture.
 */
export const showsPreviewImage = ( template ) =>
	Boolean( template?.is_locked && template?.preview_image );

/**
 * Setup steps a template still needs on this site, e.g. connecting Stripe.
 * Only for templates that can be used: a locked one can't be created yet.
 *
 * @since x.x.x
 * @param {Object} template Template.
 * @return {Array<{id: string, label: string, url: string}>} Steps not done.
 */
export const getMissingSetup = ( template ) =>
	template?.is_locked
		? []
		: ( template?.setup || [] ).filter( ( step ) => ! step.done );

/**
 * "Premium" badge on a locked template. Always text, never only an icon.
 *
 * @since x.x.x
 * @param {Object} props           Props.
 * @param {Object} props.template  Template.
 * @param {string} props.className Extra classes.
 */
export const PremiumBadge = ( { template, className } ) => (
	<span
		className={ cn(
			'inline-flex items-center gap-1 px-1.5 py-0.5 text-[11px] leading-4 font-semibold whitespace-nowrap rounded border border-solid border-brand-border-300 bg-brand-background-50 text-brand-800',
			className
		) }
	>
		<Lock className="size-3 shrink-0" aria-hidden="true" />
		{ getLockLabels( template ).badge }
	</span>
);

/**
 * Open where a locked template is unlocked: the pricing page for that
 * template, or the plugin updates when SureForms Pro is too old.
 *
 * @since x.x.x
 * @param {Object} template Template.
 */
export const openTemplateUnlock = ( template ) => {
	if ( template?.id && 'tier' === template.lock_reason ) {
		trackTemplateAction( 'upgrade_click', template.id );
	}
	const url =
		template?.unlock_url ||
		window.srfm_admin?.pricing_page_url ||
		window.srfm_block_data?.upgrade_url;
	if ( url ) {
		window.open( url, '_blank', 'noopener,noreferrer' );
	}
};

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
						__( 'More with Premium: %s', 'sureforms' ),
						proFeatures
							.map( ( feature ) => feature.label )
							.join( ', ' )
					) }
				</p>
			) }
		</div>
	);
};
