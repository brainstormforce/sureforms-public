/**
 * The two ways to send email offered by the onboarding email step, as
 * single-open accordion cards: SureContact (one click) and another SMTP
 * provider connected in SureMail.
 *
 * Sizes and colours follow the "Final flow for SureMail + SureContact" Figma
 * (Sureforms / Onboarding / Connect).
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Checkbox } from '@bsf/force-ui';
import {
	Check,
	CheckCircle2,
	ChevronDown,
	ChevronRight,
	Settings,
} from 'lucide-react';
import { cn } from '@Utils/Helpers';
// Emitted as files (see the images/onboarding rule in webpack.config.js), so
// the step does not carry them in its bundle.
import sureContactIcon from '@Image/onboarding/email-delivery/surecontact.svg';
import sureMailIcon from '@Image/onboarding/email-delivery/suremail.svg';
// Animated with CSS keyframes, so it is shown through <object>: svgr/svgo
// would strip them if inlined. Its two captions are HTML, for translation.
import freeEmailsVisual from '@Image/onboarding/email-delivery/surecontact-free-emails.svg';
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

// Border of the open card, per option: SureContact's purple, SureMail's blue.
const OPEN_BORDER = {
	surecontact: 'border-[#C3A4FA]',
	other_smtp: 'border-[#0D7EE8]',
};

/**
 * Number of free SureContact emails, as localized by the server.
 *
 * @return {number} Free emails, 0 when the offer is not shown.
 */
export const getFreeEmails = () =>
	Number( srfm_admin?.email_delivery_offer?.free_emails ?? 0 );

const RecommendedBadge = () => (
	<span className="inline-flex items-center rounded-full bg-badge-background-green px-1.5 py-1 text-xs font-medium leading-4 text-badge-color-green">
		{ __( 'Recommended', 'sureforms' ) }
	</span>
);

/**
 * One accordion card. Closed, it is a single row that opens it; open, it
 * holds the option's details and shows it is the one selected.
 *
 * @param {Object}      props
 * @param {string}      props.id         Option id.
 * @param {boolean}     props.open       Whether this option is selected.
 * @param {Function}    props.onSelect   Called with the id to select it.
 * @param {JSX.Element} props.icon       Icon shown while open.
 * @param {JSX.Element} props.closedIcon Icon shown while closed.
 * @param {string}      props.title      Option name.
 * @param {JSX.Element} props.badge      Optional badge after the name.
 * @param {JSX.Element} props.children   Details, shown while open.
 * @return {JSX.Element} Option card.
 */
const OptionCard = ( {
	id,
	open,
	onSelect,
	icon,
	closedIcon,
	title,
	badge,
	children,
} ) => {
	const bodyId = `srfm-email-option-${ id }`;

	if ( ! open ) {
		return (
			<button
				type="button"
				onClick={ () => onSelect( id ) }
				aria-expanded={ false }
				aria-controls={ bodyId }
				className={ cn(
					'flex w-full cursor-pointer items-center gap-1 rounded-md border border-solid bg-background-primary p-4 text-left',
					'focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2',
					'surecontact' === id
						? 'border-[#F3F4F6]'
						: 'border-border-disabled'
				) }
			>
				<span className="flex flex-1 items-center gap-2">
					{ closedIcon }
					<span className="text-base font-semibold leading-6 text-text-primary">
						{ title }
					</span>
					{ badge }
				</span>
				<ChevronDown
					className="size-5 shrink-0 text-icon-secondary"
					aria-hidden="true"
				/>
			</button>
		);
	}

	return (
		<div
			className={ cn(
				'flex w-full flex-col gap-5 rounded-md border border-solid bg-background-primary p-5',
				OPEN_BORDER[ id ]
			) }
		>
			<div
				className="flex items-center gap-1"
				aria-expanded={ true }
				aria-controls={ bodyId }
			>
				<span className="flex flex-1 items-center gap-2">
					{ icon }
					<span className="text-base font-semibold leading-6 text-text-primary">
						{ title }
					</span>
					{ badge }
				</span>
				<CheckCircle2
					className="size-5 shrink-0 text-brand-800"
					aria-label={ __( 'Selected', 'sureforms' ) }
				/>
			</div>
			<div id={ bodyId } className="flex flex-col gap-5">
				{ children }
			</div>
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

const GreenCheck = (
	<span className="flex shrink-0 p-0.5" aria-hidden="true">
		<span className="flex size-4 items-center justify-center rounded bg-badge-color-green">
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
					<p className="m-0 text-lg font-semibold leading-7 text-text-primary">
						{ __( 'Start sending in one click, no setup', 'sureforms' ) }
					</p>
					<p className="m-0 text-sm leading-5 text-text-secondary">
						{ __(
							'Our own sending service, set up inside SureMail for you. It handles every email your site sends, and doubles as a CRM for following up with the people who fill your forms.',
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
								__( '%d free emails', 'sureforms' ),
								freeEmails
							) }
						</p>
						<p className="absolute inset-x-0 top-[136px] m-0 text-center text-[9px] leading-3 text-text-secondary">
							{ __( 'One time, no card needed', 'sureforms' ) }
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

			<DetailList
				heading={ __( 'We’ll take care of the technical stuff.', 'sureforms' ) }
				marker={ GreenCheck }
				gap="gap-2"
				items={ [
					{
						label: __( 'Account creation', 'sureforms' ),
						hint: __(
							'Using your admin email, no password to pick',
							'sureforms'
						),
					},
					{
						label: __( 'SMTP configuration', 'sureforms' ),
						hint: __( 'No host, port or credentials to enter', 'sureforms' ),
					},
					{
						label: __( 'Sender ready', 'sureforms' ),
						hint: __(
							'Your forms can send the moment this finishes',
							'sureforms'
						),
					},
					{
						label: __( 'Delivery logs on', 'sureforms' ),
						hint: __(
							'See what sent and what failed, in one place',
							'sureforms'
						),
					},
				] }
			/>
		</>
	);
};

const PROVIDERS = [
	[ gmailLogo, 'Gmail' ],
	[ outlookLogo, 'Microsoft 365 / Outlook' ],
	[ postmarkLogo, 'Postmark' ],
	[ sendgridLogo, 'SendGrid' ],
	[ brevoLogo, 'Brevo' ],
	[ mailgunLogo, 'Mailgun' ],
	[ sesLogo, 'Amazon SES' ],
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
 * Both options, one open at a time.
 *
 * @param {Object}   props
 * @param {string}   props.option    Selected option id.
 * @param {Function} props.onSelect  Called with the option id to select.
 * @param {boolean}  props.localSite Whether the site looks local or private.
 * @return {JSX.Element} Options.
 */
export const EmailOptions = ( { option, onSelect, localSite } ) => (
	<div className="flex flex-col gap-4" role="group">
		<OptionCard
			id="surecontact"
			open={ 'surecontact' === option }
			onSelect={ onSelect }
			icon={
				<img src={ sureContactIcon } alt="" className="size-5 shrink-0" />
			}
			closedIcon={
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
			icon={ <img src={ sureMailIcon } alt="" className="size-5 shrink-0" /> }
			closedIcon={
				<Settings
					className="size-5 shrink-0 text-icon-secondary"
					aria-hidden="true"
				/>
			}
			title={ __( 'Other SMTP Provider', 'sureforms' ) }
		>
			<OtherSmtpDetails />
		</OptionCard>
	</div>
);

/**
 * What the owner agrees to before SureContact is set up: the account, which
 * address it uses, and what SureContact then receives.
 *
 * @param {Object}   props
 * @param {boolean}  props.checked  Whether the box is ticked.
 * @param {Function} props.onChange Called with the new checked value.
 * @param {boolean}  props.installs Whether continuing also installs SureMail.
 * @param {string}   props.email    Address the account is created for.
 * @return {JSX.Element} Consent checkbox.
 */
export const ConsentCheckbox = ( { checked, onChange, installs, email } ) => {
	const heading = installs
		? sprintf(
			/* translators: %s: the current user's email address. */
			__(
				'I agree to install SureMail and create a free SureContact account for %s.',
				'sureforms'
			),
			email
		  )
		: sprintf(
			/* translators: %s: the current user's email address. */
			__(
				'I agree to create a free SureContact account for %s.',
				'sureforms'
			),
			email
		  );

	return (
		<Checkbox
			id="srfm-email-delivery-consent"
			checked={ checked }
			onChange={ onChange }
			size="sm"
			// An element rather than { heading, description }: Force UI sets the
			// heading at 16px line height, the design at 20px, and its text
			// would not toggle the box.
			label={
				<label
					htmlFor="srfm-email-delivery-consent"
					className="flex cursor-pointer flex-col gap-0.5"
				>
					<span className="text-sm font-medium leading-5 text-field-label">
						{ heading }
					</span>
					<span className="text-sm leading-5 text-[#6B7280]">
						{ createInterpolateElement(
							__(
								'Outgoing emails, including recipients and content, are sent through SureContact. Your address is used only to create the account, and you can disconnect from SureMail settings at any time. <privacy>Privacy Policy</privacy> · <terms>Terms</terms>',
								'sureforms'
							),
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
