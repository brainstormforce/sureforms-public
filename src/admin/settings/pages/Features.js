import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Button, Switch, Tabs, toast } from '@bsf/force-ui';
import {
	Bot,
	Calculator,
	CircleCheck,
	Crown,
	CreditCard,
	Database,
	FileText,
	GitBranch,
	GraduationCap,
	Hourglass,
	LayoutGrid,
	MailCheck,
	MessageCircle,
	Newspaper,
	Package,
	Save,
	Sparkles,
	UserPlus,
	Webhook,
	ChartColumn,
} from 'lucide-react';
import { useGlobalSettingsTabSaveAdapter } from '../tab-save-adapter';

const ICONS = {
	entries: Database,
	ai_builder: Sparkles,
	ottokit: Bot,
	payments: CreditCard,
	quiz: GraduationCap,
	survey: ChartColumn,
	pdf: FileText,
	partial_entries: Hourglass,
	save_resume: Save,
	conversational: MessageCircle,
	calculations: Calculator,
	conditional_logic: GitBranch,
	conditional_emails: MailCheck,
	conditional_confirmations: CircleCheck,
	post_feeds: Newspaper,
	native_integrations: Webhook,
	user_registration: UserPlus,
};

/**
 * Each row's own saved switch, keyed by feature key.
 *
 * @param {Object} rows Rows from Features::for_js().
 * @return {Object} Map of key to its switch.
 */
const switchesOf = ( rows ) =>
	Object.fromEntries(
		Object.entries( rows ).map( ( [ key, row ] ) => [ key, row.switch ] )
	);

/**
 * Names of the post statuses a form in the in-use list can have, other than published.
 */
const STATUS_LABELS = {
	draft: __( 'draft', 'sureforms' ),
	pending: __( 'pending review', 'sureforms' ),
	future: __( 'scheduled', 'sureforms' ),
	private: __( 'private', 'sureforms' ),
};

/**
 * Why a form is listed before a feature is turned off: what it uses and,
 * when it is not published, its status.
 *
 * @param {Object} form  Row from Features::forms_using().
 * @param {string} label Name of the feature being turned off.
 * @return {string} e.g. "Payments: has a payment field, draft".
 */
const inUseNote = ( form, label ) =>
	[
		form.reason ? `${ label }: ${ form.reason }` : label,
		STATUS_LABELS[ form.status ],
	]
		.filter( Boolean )
		.join( ', ' );

/**
 * One feature card: icon, name, description and its switch.
 *
 * @param {Object}      props
 * @param {string}      props.featureKey  Feature key, picks the icon.
 * @param {string}      props.label       Feature name.
 * @param {string}      props.description What turning it off does.
 * @param {JSX.Element} props.control     The switch.
 */
const FeatureCell = ( {
	featureKey,
	label,
	description,
	control,
} ) => {
	const Icon = ICONS[ featureKey ] || LayoutGrid;
	return (
		<div className="flex items-start gap-3 p-4 bg-background-primary">
			<div className="flex items-center justify-center shrink-0 size-9 rounded-lg border border-solid border-border-subtle bg-background-secondary">
				<Icon className="size-5 text-icon-secondary" />
			</div>
			<div className="flex-1 min-w-0">
				<p className="m-0 text-sm font-semibold text-text-primary">
					{ label }
				</p>
				<p
					id={ `srfm-feature-${ featureKey }-description` }
					className="m-0 mt-0.5 text-xs leading-5 text-field-helper"
				>
					{ description }
				</p>
			</div>
			{ control }
		</div>
	);
};

/**
 * Settings → Features: switch whole features off and on.
 *
 * Rows come from `window.srfmFeatures` (Features::for_js()), so Pro rows show
 * up on a Premium tab without any Pro JS; without Pro there are no tabs. The
 * header Save button saves in place; menus and
 * the editor pick the change up on their next load, because a feature that
 * is off is not loaded at all.
 */
const FeaturesPage = () => {
	const [ features, setFeatures ] = useState( window.srfmFeatures || {} );
	const [ switches, setSwitches ] = useState( () => switchesOf( features ) );
	const [ activeTab, setActiveTab ] = useState( 'core' );
	const [ inUse, setInUse ] = useState( null );
	const [ saving, setSaving ] = useState( false );

	const isDirty = Object.keys( switches ).some(
		( key ) => switches[ key ] !== features[ key ].switch
	);

	// Whether every feature this one needs is switched on, using unsaved state.
	const parentsOn = ( key, seen = [] ) =>
		( features[ key ]?.depends_on || [] ).every(
			( parent ) =>
				seen.includes( parent ) ||
				( switches[ parent ] !== false &&
					parentsOn( parent, [ ...seen, key ] ) )
		);

	const save = ( confirm = false ) => {
		setSaving( true );
		apiFetch( {
			path: '/sureforms/v1/srfm-global-settings',
			method: 'POST',
			data: {
				srfm_tab: 'features-settings',
				features: switches,
				confirm,
			},
		} )
			.then( ( res ) => {
				if ( res?.needs_confirmation ) {
					setInUse( res.in_use );
					setSaving( false );
					return;
				}
				// Settings tabs and isFeatureEnabled() read this global.
				window.srfmFeatures = res.features;
				setFeatures( res.features );
				setSwitches( switchesOf( res.features ) );
				setInUse( null );
				setSaving( false );
				toast.success( __( 'Settings saved.', 'sureforms' ) );
			} )
			.catch( ( err ) => {
				setSaving( false );
				toast.error(
					err?.message || __( 'Could not save the features.', 'sureforms' )
				);
			} );
	};

	// Saved from the page header's Save button, like the other settings tabs.
	useGlobalSettingsTabSaveAdapter( 'features-settings', {
		isDirty: isDirty && ! inUse,
		isSaving: saving && ! inUse,
		onSave: () => save(),
	} );

	const proKeys = Object.keys( features ).filter(
		( key ) => 'pro' === features[ key ].group
	);
	const coreKeys = Object.keys( features ).filter(
		( key ) => 'pro' !== features[ key ].group
	);
	// Without Pro there is only the Core list, so no tabs.
	const hasPro = proKeys.length > 0;
	const keys = hasPro && 'premium' === activeTab ? proKeys : coreKeys;

	const cells = keys.map( ( key ) => {
		const row = features[ key ];
		const available = parentsOn( key );
		const needs = row.depends_on
			.map( ( parent ) => features[ parent ]?.label )
			.filter( Boolean )
			.join( ', ' );
		const dependents = Object.keys( features )
			.filter( ( other ) => features[ other ].depends_on?.includes( key ) )
			.map( ( other ) => features[ other ].label )
			.join( ', ' );
		const notes = [
			row.description,
			! available &&
				sprintf(
					/* translators: %s: names of the features this one needs. */
					__( 'Needs %s.', 'sureforms' ),
					needs
				),
			dependents &&
				sprintf(
					/* translators: %s: names of the features that need this one. */
					__( 'Turning this off also turns off %s.', 'sureforms' ),
					dependents
				),
		].filter( Boolean );
		return (
			<FeatureCell
				key={ key }
				featureKey={ key }
				label={ row.label }
				description={ notes.join( ' ' ) }
				control={
					<Switch
						size="sm"
						disabled={ ! available }
						value={ available && false !== switches[ key ] }
						aria-label={ row.label }
						// Announces the description, including why a switch is unavailable.
						aria-describedby={ `srfm-feature-${ key }-description` }
						onChange={ ( value ) => {
							setInUse( null );
							setSwitches( { ...switches, [ key ]: value } );
						} }
					/>
				}
			/>
		);
	} );

	return (
		<div className="space-y-4">
			{ inUse && (
				<div
					role="alert"
					className="p-4 rounded-lg border border-solid border-alert-border-warning bg-alert-background-warning space-y-2"
				>
					<p className="text-sm font-semibold m-0">
						{ __(
							'These forms use something you are turning off:',
							'sureforms'
						) }
					</p>
					<ul className="m-0 pl-5 list-disc text-sm">
						{ Object.entries( inUse ).map( ( [ key, forms ] ) =>
							forms.map( ( form ) => (
								<li key={ `${ key }-${ form.id }` }>
									{ form.edit_url ? (
										<a
											href={ form.edit_url }
											target="_blank"
											rel="noreferrer"
										>
											{ form.title ||
												__( '(no title)', 'sureforms' ) }
										</a>
									) : (
										form.title ||
										__( '(no title)', 'sureforms' )
									) }{ ' ' }
									({ inUseNote( form, features[ key ]?.label || key ) })
								</li>
							) )
						) }
					</ul>
					<div className="flex gap-2">
						<Button
							variant="primary"
							size="sm"
							loading={ saving }
							// force-ui's loading state does not disable the button.
							disabled={ saving }
							onClick={ () => save( true ) }
						>
							{ __( 'Turn off anyway', 'sureforms' ) }
						</Button>
						<Button
							variant="outline"
							size="sm"
							// Back to the saved switches, so nothing is left to save by mistake.
							onClick={ () => {
								setInUse( null );
								setSwitches( switchesOf( features ) );
							} }
						>
							{ __( 'Cancel', 'sureforms' ) }
						</Button>
					</div>
				</div>
			) }

			{ hasPro && (
				<Tabs activeItem={ activeTab }>
					<Tabs.Group
						variant="rounded"
						size="md"
						width="full"
						onChange={ ( { value: { slug } } ) => setActiveTab( slug ) }
					>
						<Tabs.Tab
							slug="core"
							icon={ <Package /> }
							text={ __( 'Core', 'sureforms' ) }
						/>
						<Tabs.Tab
							slug="premium"
							icon={ <Crown /> }
							text={ __( 'Premium', 'sureforms' ) }
						/>
					</Tabs.Group>
				</Tabs>
			) }

			<div className="grid grid-cols-1 md:grid-cols-2 gap-px overflow-hidden rounded-lg border border-solid border-border-subtle bg-border-subtle">
				{ cells }
				{ /* Fills the empty half so the last odd card keeps its width. */ }
				{ cells.length % 2 === 1 && (
					<div className="hidden md:block bg-background-primary" />
				) }
			</div>
		</div>
	);
};

export default FeaturesPage;
