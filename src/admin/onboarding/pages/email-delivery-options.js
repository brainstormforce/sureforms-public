/**
 * The two ways to send email offered by the onboarding email step, as
 * single-open accordion cards: SureContact (one click) and another SMTP
 * provider connected in SureMail.
 *
 * Sizes and colours follow the "Final flow for SureMail + SureContact" Figma
 * (Sureforms / Onboarding / Connect).
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	createInterpolateElement,
	useEffect,
	useRef,
} from '@wordpress/element';
import { Checkbox } from '@bsf/force-ui';
import {
	Check,
	CheckCircle2,
	ChevronDown,
	ChevronRight,
} from 'lucide-react';
import { cn, getFreeEmails } from '@Utils/Helpers';
// Emitted as files (see the images/onboarding rule in webpack.config.js), so
// the step does not carry them in its bundle.
import sureContactIcon from '@Image/onboarding/email-delivery/surecontact.svg';
import sureMailIcon from '@Image/onboarding/email-delivery/suremail.svg';
// Animated with CSS keyframes, so it is shown through <object>: svgr/svgo
// would strip them if inlined. Its two captions are HTML, for translation.
import freeEmailsVisual from '@Image/onboarding/email-delivery/surecontact-mails-free.svg';
import gmailLogo from '@Image/onboarding/email-delivery/gmail.svg';
import outlookLogo from '@Image/onboarding/email-delivery/outlook.svg';
import postmarkLogo from '@Image/onboarding/email-delivery/postmark.svg';
import sendgridLogo from '@Image/onboarding/email-delivery/sendgrid.svg';
import brevoLogo from '@Image/onboarding/email-delivery/brevo.svg';
import mailgunLogo from '@Image/onboarding/email-delivery/mailgun.svg';
import sesLogo from '@Image/onboarding/email-delivery/amazon-ses.svg';
import netcoreLogo from '@Image/onboarding/email-delivery/netcore.svg';
import smtpLogo from '@Image/onboarding/email-delivery/smtp.svg';

export const SURECONTACT_PRIVACY_URL =
	'https://surecontact.com/privacy-policy/';
export const SURECONTACT_TERMS_URL =
	'https://surecontact.com/smtp-terms-of-service/';

const linkClass = 'text-inherit underline hover:text-text-secondary';

// Stroke of the open card, in the colour of the product it sets up:
// SureContact purple, SureMail blue.
const OPEN_BORDER = {
	surecontact: 'border-[#C3A4FA]',
	other_smtp: 'border-[#0D7EE8]',
};

const RecommendedBadge = () => (
	<span className="inline-flex items-center rounded-full bg-badge-background-green px-1.5 py-1 text-xs font-medium leading-4 text-badge-color-green">
		{ __( 'Recommended', 'sureforms' ) }
	</span>
);

/**
 * One option card, a radio in the group of two. Its header is the radio: the
 * same button whether the card is open or closed, so keyboard focus stays on
 * it when it opens. The details show only while it is the selected option.
 *
 * @param {Object}      props
 * @param {string}      props.id       Option id.
 * @param {boolean}     props.open     Whether this option is selected.
 * @param {Function}    props.onSelect Called with the id to select it.
 * @param {Function}    props.onArrow  Called with the key to move selection.
 * @param {boolean}     props.disabled Whether the choice is locked.
 * @param {JSX.Element} props.icon     Option icon.
 * @param {string}      props.title    Option name.
 * @param {JSX.Element} props.badge    Optional badge after the name.
 * @param {JSX.Element} props.children Details, shown while open.
 * @return {JSX.Element} Option card.
 */
const OptionCard = ( {
	id,
	open,
	onSelect,
	onArrow,
	disabled,
	icon,
	title,
	badge,
	children,
} ) => {
	const bodyId = `srfm-email-option-${ id }`;

	return (
		<div
			className={ cn(
				'flex w-full flex-col rounded-md border border-solid bg-background-primary',
				open ? [ 'gap-5 p-5', OPEN_BORDER[ id ] ] : 'border-border-disabled p-4',
				disabled && 'opacity-80'
			) }
		>
			<button
				type="button"
				role="radio"
				id={ `srfm-email-option-${ id }-radio` }
				aria-checked={ open }
				aria-controls={ open ? bodyId : undefined }
				// Roving tabindex: the group is one tab stop, arrows move within it.
				tabIndex={ open ? 0 : -1 }
				disabled={ disabled }
				onClick={ () => onSelect( id ) }
				onKeyDown={ ( event ) => {
					if (
						[ 'ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight' ].includes(
							event.key
						)
					) {
						event.preventDefault();
						onArrow();
					}
				} }
				className={ cn(
					'flex w-full items-center gap-1 rounded border-0 bg-transparent p-0 text-left',
					'focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-4',
					disabled ? 'cursor-not-allowed' : 'cursor-pointer'
				) }
			>
				<span className="flex flex-1 items-center gap-2">
					{ icon }
					<span className="text-base font-semibold leading-6 text-text-primary">
						{ title }
					</span>
					{ badge }
				</span>
				{ open ? (
					<CheckCircle2
						className="size-5 shrink-0 text-brand-800"
						aria-hidden="true"
					/>
				) : (
					<ChevronDown
						className="size-5 shrink-0 text-icon-secondary"
						aria-hidden="true"
					/>
				) }
			</button>
			{ open && (
				<div
					id={ bodyId }
					role="region"
					aria-labelledby={ `srfm-email-option-${ id }-radio` }
					className="flex flex-col gap-5"
				>
					{ children }
				</div>
			) }
		</div>
	);
};

/**
 * Two-column list of short "label + hint" rows.
 *
 * @param {Object}   props
 * @param {string}   props.heading Lead-in line.
 * @param {Object[]} props.items   { label, hint } rows.
 * @param {Function} props.marker  Renders the leading mark of a row.
 * @param {string}   props.gap     Gap between the mark and the text.
 * @return {JSX.Element} List.
 */
const DetailList = ( { heading, items, marker, gap } ) => (
	<div className="flex flex-col gap-4">
		<p className="m-0 text-sm font-medium leading-5 text-text-primary">
			{ heading }
		</p>
		<ul className="m-0 grid max-w-[568px] list-none grid-cols-1 gap-4 p-0 sm:grid-cols-2">
			{ items.map( ( { label, hint } ) => (
				<li key={ label } className={ cn( 'm-0 flex items-start', gap ) }>
					{ marker }
					<span className="flex flex-col gap-0.5">
						<span className="text-sm font-medium leading-5 text-field-label">
							{ label }
						</span>
						<span className="text-xs leading-4 text-[#6B7280]">
							{ hint }
						</span>
					</span>
				</li>
			) ) }
		</ul>
	</div>
);

const GreyCheck = (
	<span className="flex shrink-0 p-0.5" aria-hidden="true">
		<span className="flex size-4 items-center justify-center rounded bg-[#9CA3AF]">
			<Check className="size-3 text-text-on-color" strokeWidth={ 3 } />
		</span>
	</span>
);

const PathArrow = (
	<ChevronRight
		className="size-5 shrink-0 text-icon-secondary"
		aria-hidden="true"
	/>
);

/**
 * The offer: one-click sending, and the free emails that come with it.
 *
 * @param {Object}  props
 * @param {boolean} props.localSite Whether the site looks local or private.
 * @return {JSX.Element} SureContact details.
 */
const SureContactDetails = ( { localSite } ) => {
	const freeEmails = getFreeEmails();

	return (
		<>
			<div className="flex max-w-[568px] flex-col items-center gap-9 rounded-lg border border-solid border-[#DACFF9] bg-[linear-gradient(90deg,#FEFDFF_0%,#E8DDFC_100%)] p-4 sm:flex-row">
				<div className="flex flex-1 flex-col gap-2">
					<p className="m-0 text-base font-semibold leading-6 text-text-primary">
						{ __(
							'Start sending in one click, no setup needed.',
							'sureforms'
						) }
					</p>
					<p className="m-0 text-sm leading-5 text-text-secondary">
						{ __(
							'SureContact SMTP is our email sending service, set up inside SureMail for you. Start sending your WordPress emails without manually configuring an SMTP provider.',
							'sureforms'
						) }
					</p>
				</div>
				{ freeEmails > 0 && (
					<div className="relative h-[163px] w-[169px] shrink-0">
						<object
							type="image/svg+xml"
							data={ freeEmailsVisual }
							className="pointer-events-none block size-full"
							aria-label={ __(
								'A gift box with emails rising out of it',
								'sureforms'
							) }
							tabIndex={ -1 }
						/>
						<p className="absolute inset-x-0 top-[112px] m-0 text-center text-base font-semibold leading-6 text-text-primary">
							{ sprintf(
								/* translators: %d: number of free emails. */
								_n(
									'%d Mail Free',
									'%d Mails Free',
									freeEmails,
									'sureforms'
								),
								freeEmails
							) }
						</p>
						<p className="absolute inset-x-0 top-[136px] m-0 text-center text-[9px] leading-3 text-text-secondary">
							{ __( 'One time setup, No card required', 'sureforms' ) }
						</p>
					</div>
				) }
			</div>

			{ localSite && (
				<p
					className="m-0 max-w-[568px] rounded-md border border-solid border-alert-border-warning bg-alert-background-warning px-3 py-2 text-sm leading-5 text-text-primary"
					role="note"
				>
					{ __(
						'This site looks local or private. SureContact has to reach a site to confirm it is yours, so one-click sign-up usually fails here. Use another provider for now, and switch to SureContact once the site is live.',
						'sureforms'
					) }
				</p>
			) }

			<div className="flex flex-col gap-4">
				<p className="m-0 text-sm font-medium leading-5 text-text-primary">
					{ __( 'We’ll take care of the technical stuff.', 'sureforms' ) }
				</p>
				<ul className="m-0 flex list-none flex-wrap gap-x-7 gap-y-2 p-0">
					{ [
						__( 'Account creation', 'sureforms' ),
						__( 'SMTP configuration', 'sureforms' ),
						__( 'Ready to send', 'sureforms' ),
					].map( ( label ) => (
						<li key={ label } className="m-0 flex items-center gap-2">
							{ GreyCheck }
							<span className="text-sm font-medium leading-5 text-field-label">
								{ label }
							</span>
						</li>
					) ) }
				</ul>
			</div>
		</>
	);
};

const PROVIDERS = [
	[ gmailLogo, 'Gmail' ],
	[ sesLogo, 'Amazon SES' ],
	[ outlookLogo, 'Microsoft 365 / Outlook' ],
	[ postmarkLogo, 'Postmark' ],
	[ sendgridLogo, 'SendGrid' ],
	[ brevoLogo, 'Brevo' ],
	[ mailgunLogo, 'Mailgun' ],
	[ netcoreLogo, 'Netcore' ],
	[ smtpLogo, __( 'Other SMTP', 'sureforms' ) ],
];

/**
 * Connecting a provider the owner already uses, in SureMail.
 *
 * @return {JSX.Element} Other SMTP details.
 */
const OtherSmtpDetails = () => (
	<>
		<div className="flex max-w-[568px] flex-col gap-[19px] rounded-lg border border-solid border-[#FBFBFB] bg-[#F9F9F9] px-4 py-5">
			<p className="m-0 text-sm font-medium leading-5 text-text-primary">
				{ __( 'Works with the provider you already have', 'sureforms' ) }
			</p>
			<div className="flex flex-wrap items-center gap-5 px-2 py-[5px]">
				{ PROVIDERS.map( ( [ logo, name ] ) => (
					<img
						key={ name }
						src={ logo }
						alt={ name }
						title={ name }
						className="size-6 shrink-0"
					/>
				) ) }
				<span className="text-xs font-medium leading-4 text-text-secondary">
					{ __( 'And many more…', 'sureforms' ) }
				</span>
			</div>
		</div>

		<DetailList
			heading={ __( 'Here’s the path', 'sureforms' ) }
			marker={ PathArrow }
			gap="gap-0.5"
			items={ [
				{
					label: __( 'Your SMTP plugin opens', 'sureforms' ),
					hint: __( 'SureMail, or the one you already use', 'sureforms' ),
				},
				{
					label: __( 'Add your provider details', 'sureforms' ),
					hint: __( 'Host, port, username and password', 'sureforms' ),
				},
				{
					label: __( 'Send a test email', 'sureforms' ),
					hint: __( 'Confirms it reaches an inbox, not spam', 'sureforms' ),
				},
				{
					label: __( 'Come back and finish', 'sureforms' ),
					hint: __( 'Around two minutes in total', 'sureforms' ),
				},
			] }
		/>
	</>
);

/**
 * Both options as a radio group, one open at a time.
 *
 * @param {Object}   props
 * @param {string}   props.option    Selected option id.
 * @param {Function} props.onSelect  Called with the option id to select.
 * @param {boolean}  props.localSite Whether the site looks local or private.
 * @param {boolean}  props.disabled  Lock the choice (while installing or
 *                                   creating the account).
 * @return {JSX.Element} Options.
 */
export const EmailOptions = ( { option, onSelect, localSite, disabled } ) => {
	// Arrow keys move the selection and the focus with it, as in any radio
	// group; with two options either arrow means "the other one". The focus
	// moves after the new selection has rendered, in the effect below.
	const focusAfterSelect = useRef( '' );
	useEffect( () => {
		if ( focusAfterSelect.current === option ) {
			focusAfterSelect.current = '';
			document
				.getElementById( `srfm-email-option-${ option }-radio` )
				?.focus();
		}
	}, [ option ] );
	const selectOther = () => {
		const next = 'surecontact' === option ? 'other_smtp' : 'surecontact';
		focusAfterSelect.current = next;
		onSelect( next );
	};

	return (
		<div
			className="flex flex-col gap-4"
			role="radiogroup"
			aria-label={ __( 'How your form emails are sent', 'sureforms' ) }
			aria-disabled={ disabled || undefined }
		>
			<OptionCard
				id="surecontact"
				open={ 'surecontact' === option }
				onSelect={ onSelect }
				onArrow={ selectOther }
				disabled={ disabled }
				icon={
					<img src={ sureContactIcon } alt="" className="size-5 shrink-0" />
				}
				title={ __( 'SureContact', 'sureforms' ) }
				badge={ <RecommendedBadge /> }
			>
				<SureContactDetails localSite={ localSite } />
			</OptionCard>
			<OptionCard
				id="other_smtp"
				open={ 'other_smtp' === option }
				onSelect={ onSelect }
				onArrow={ selectOther }
				disabled={ disabled }
				icon={ <img src={ sureMailIcon } alt="" className="size-5 shrink-0" /> }
				title={ __( 'Other SMTP Provider', 'sureforms' ) }
			>
				<OtherSmtpDetails />
			</OptionCard>
		</div>
	);
};

/**
 * What the owner agrees to before SureContact is set up: the account, which
 * address it uses, and what SureContact then receives.
 *
 * @param {Object}   props
 * @param {boolean}  props.checked  Whether the box is ticked.
 * @param {Function} props.onChange Called with the new checked value.
 * @param {boolean}  props.installs Whether continuing also installs SureMail.
 * @param {string}   props.email    Address the account is created for.
 * @param {boolean}  props.named    Whether the profile has a name, which
 *                                  SureMail then sends as the From name.
 * @param {boolean}  props.disabled Lock the box while the account is being
 *                                  set up: unticking then would not stop it.
 * @return {JSX.Element} Consent checkbox.
 */
export const ConsentCheckbox = ( {
	checked,
	onChange,
	installs,
	email,
	named = false,
	disabled,
} ) => {
	// The address is underlined so the owner checks it is the right one; it
	// goes in as a tag, never parsed as markup itself.
	const heading = createInterpolateElement(
		installs
			? sprintf(
				/* translators: %s: the current user's email address. */
				__(
					'I agree to install SureMail and create a free SureContact account for %s.',
					'sureforms'
				),
				'<email/>'
			  )
			: sprintf(
				/* translators: %s: the current user's email address. */
				__(
					'I agree to create a free SureContact account for %s.',
					'sureforms'
				),
				'<email/>'
			  ),
		{ email: <span className="underline">{ email }</span> }
	);

	// SureMail sets the From name only when it is given a name.
	const disclosure = named
		? sprintf(
			/* translators: %s: the current user's email address. */
			__(
				'Outgoing emails, including recipients and content, are sent through SureContact. Every email from this site is sent from %s and your name, replacing the From it used before. To create the account, SureContact receives your name and email, this site\'s name and address, and its WordPress and SureMail versions. You can disconnect from SureMail settings at any time. <privacy>Privacy Policy</privacy> · <terms>Terms</terms>',
				'sureforms'
			),
			email
		  )
		: sprintf(
			/* translators: %s: the current user's email address. */
			__(
				'Outgoing emails, including recipients and content, are sent through SureContact. Every email from this site is sent from %s, replacing the From address it used before. To create the account, SureContact receives your email, this site\'s name and address, and its WordPress and SureMail versions. You can disconnect from SureMail settings at any time. <privacy>Privacy Policy</privacy> · <terms>Terms</terms>',
				'sureforms'
			),
			email
		  );

	return (
		<Checkbox
			id="srfm-email-delivery-consent"
			checked={ checked }
			onChange={ onChange }
			disabled={ disabled }
			size="sm"
			// An element rather than { heading, description }: Force UI sets the
			// heading at 16px line height, the design at 20px, and its text
			// would not toggle the box.
			label={
				<label
					htmlFor="srfm-email-delivery-consent"
					className={ cn(
						'flex flex-col gap-0.5',
						disabled ? 'cursor-not-allowed' : 'cursor-pointer'
					) }
				>
					<span className="text-sm font-medium leading-5 text-field-label">
						{ heading }
					</span>
					<span className="text-xs leading-[19px] text-[#6B7280]">
						{ createInterpolateElement(
							disclosure,
							{
								privacy: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content -- Text comes from the interpolated string.
									<a
										href={ SURECONTACT_PRIVACY_URL }
										target="_blank"
										rel="noopener noreferrer"
										className={ linkClass }
									/>
								),
								terms: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content -- Text comes from the interpolated string.
									<a
										href={ SURECONTACT_TERMS_URL }
										target="_blank"
										rel="noopener noreferrer"
										className={ linkClass }
									/>
								),
							}
						) }
					</span>
				</label>
			}
		/>
	);
};
