/**
 * SureForms → SMTP: why to set up email delivery, and the one button that
 * does it (install SureMail, then connect SureContact in SureMail).
 *
 * Shown while SureMail is not active; once it is, the menu item goes straight
 * to SureMail (see Admin::add_suremail_page()). Layout follows the
 * "Final flow for SureMail + SureContact" Figma (Dashboard).
 *
 * @since 1.7.1
 */

import { __, _n, sprintf } from '@wordpress/i18n';
import {
	useState,
	useEffect,
	useRef,
	createRoot,
} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Loader } from '@bsf/force-ui';
import {
	Check,
	ChevronRight,
	ClipboardList,
	Clock,
	Gift,
	MailCheck,
	Play,
	RefreshCw,
	Loader as Sparkle,
	Users,
	X,
	Zap,
} from 'lucide-react';
import { installAndActivatePlugin, getFreeEmails } from '@Utils/Helpers';
import sureContactIcon from '@Image/onboarding/email-delivery/surecontact.svg';
import freeEmailsVisual from '@Image/onboarding/email-delivery/surecontact-free-emails.svg';
import './tw-base.scss';

const VIDEO_ID = 'fFKJfbWLif4';
const VIDEO_MINUTES = 6;

const SUREMAIL_PLUGIN = {
	slug: 'suremails',
	path: 'suremails/suremails.php',
};

const SureFormsLogo = ( { size } ) => (
	<svg
		width={ size }
		height={ size }
		viewBox="0 0 24 24"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
		aria-hidden="true"
	>
		<path d="M24 0H0V24H24V0Z" fill="#D54407" />
		<path
			d="M6.8501 5.14209H17.1358V8.57068H8.56439L6.8501 10.285V8.57068V5.14209Z"
			fill="white"
		/>
		<path
			d="M6.8501 10.2866H15.4215V13.7152H8.56439L6.8501 15.4294V13.7152V10.2866Z"
			fill="white"
		/>
		<path d="M6.8501 15.4272H11.9929V18.8558H6.8501V15.4272Z" fill="white" />
	</svg>
);

const SureMailLogo = ( { size } ) => (
	<svg
		width={ size }
		height={ size }
		viewBox="0 0 48 48"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
		aria-hidden="true"
	>
		<path d="M48 0H0V48H48V0Z" fill="#0D7EE8" />
		<path
			d="M12.8058 23.327C13.2233 23.6289 13.816 23.5298 14.0891 23.1236C14.391 22.7062 14.2918 22.1134 13.8857 21.8403L9.86562 18.9603C9.72275 18.8501 9.7352 18.7122 9.74142 18.6433C9.74772 18.5744 9.8003 18.4541 9.97325 18.3863L36.7218 11.5667C36.8772 11.5391 36.9687 11.603 37.0314 11.6781C37.0941 11.7533 37.1568 11.8285 37.0866 11.9889L26.952 37.6323C26.8818 37.7928 26.7552 37.8091 26.6976 37.8317C26.6286 37.8254 26.4908 37.8129 26.4055 37.6802L23.3421 32.0652C23.2455 31.9036 23.1666 31.7018 23.07 31.5402C21.871 28.8185 21.5684 26.8103 23.8186 24.832L29.6121 19.4706C30.0054 19.1171 30.0441 18.5368 29.7018 18.1722C29.3484 17.7788 28.7681 17.7401 28.4034 18.0824L22.3186 23.327C19.2527 26.0237 19.7632 29.3781 21.7032 32.9731L24.7665 38.588C25.1578 39.3323 25.9537 39.7519 26.8047 39.7178C27.029 39.6965 27.2709 39.635 27.4726 39.5559C28.0202 39.3415 28.4487 38.9077 28.6941 38.3462L38.8289 12.7028C39.1332 11.9519 38.9901 11.0772 38.4485 10.4583C37.9068 9.83934 37.0809 9.59769 36.2864 9.77589L9.509 16.6068C8.69697 16.8251 8.08302 17.4645 7.91037 18.2967C7.73765 19.1289 8.09382 19.9535 8.78577 20.447L12.8058 23.327Z"
			fill="white"
		/>
		<path
			d="M11.5395 31.0511C11.6148 31.0216 11.7053 30.9572 11.7708 30.9027L15.8196 27.349C16.1621 27.0412 16.1958 26.536 15.8978 26.2186C15.59 25.8761 15.0849 25.8424 14.7675 26.1404L10.7186 29.6941C10.3761 30.0019 10.3425 30.5071 10.6404 30.8245C10.8685 31.1113 11.2133 31.1788 11.5395 31.0511Z"
			fill="white"
		/>
		<path
			d="M11.0218 36.7353C11.0971 36.706 11.1879 36.6419 11.2534 36.5875L18.1346 30.5614C18.478 30.2546 18.5131 29.7496 18.216 29.4313C17.9092 29.0879 17.4041 29.0528 17.0859 29.3499L10.2047 35.376C9.86136 35.6828 9.82626 36.1879 10.1233 36.5061C10.3409 36.7685 10.7203 36.8524 11.0218 36.7353Z"
			fill="white"
		/>
	</svg>
);

// An envelope with a notification badge: the email that arrives.
const InboxIcon = () => (
	<svg
		width="66"
		height="56"
		viewBox="0 0 66 56"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
		aria-hidden="true"
	>
		<rect x="0" y="10" width="56" height="40" rx="3" fill="#0D7EE8" />
		<path
			d="M2 13L28 31L54 13"
			stroke="white"
			strokeWidth="2.5"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
		<circle cx="52" cy="14" r="13" fill="#D54407" stroke="white" strokeWidth="2" />
		<path
			d="M47 17.5H57M48.5 17.5V13.5C48.5 11.57 50.07 10 52 10C53.93 10 55.5 11.57 55.5 13.5V17.5M50.5 19.5C50.8 20.1 51.37 20.5 52 20.5C52.63 20.5 53.2 20.1 53.5 19.5"
			stroke="white"
			strokeWidth="1.6"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
	</svg>
);

const Operator = ( { children } ) => (
	<span
		className="text-2xl font-semibold leading-8 text-text-primary"
		aria-hidden="true"
	>
		{ children }
	</span>
);

/**
 * The page's one action: install and activate SureMail, then hand over to
 * SureMail's own screen to connect SureContact. The account is created there,
 * behind SureMail's consent, never from this page.
 *
 * One state for the whole page: both buttons run the same install, so while
 * one runs the other is busy too, and a second install never starts.
 *
 * @param {string}   status  'not_installed' | 'installed'.
 * @param {Function} onError Called with a message on failure ('' to clear).
 * @return {Object} { progress, busy, label, run }.
 */
const useSureMailSetup = ( status, onError ) => {
	const [ progress, setProgress ] = useState( '' );
	const running = useRef( false );

	const label = () => {
		if ( 'installing' === progress ) {
			return __( 'Installing SureMail…', 'sureforms' );
		}
		if ( 'activating' === progress ) {
			return __( 'Activating SureMail…', 'sureforms' );
		}
		if ( 'redirecting' === progress ) {
			return __( 'Opening SureMail…', 'sureforms' );
		}
		return 'installed' === status
			? __( 'Activate SureMail and Set Up SureContact', 'sureforms' )
			: __( 'Install SureMail and Set Up SureContact', 'sureforms' );
	};

	const run = async () => {
		// A ref, not the state: two clicks in the same tick both see the old
		// state, and each would start an install.
		if ( running.current ) {
			return;
		}
		running.current = true;
		onError( '' );
		try {
			await installAndActivatePlugin(
				{
					...SUREMAIL_PLUGIN,
					status: 'installed' === status ? 'Installed' : 'Install',
				},
				setProgress
			);
			setProgress( 'redirecting' );
			// SureMail skips its welcome redirect for an AJAX activation, so ask
			// where its setup lives now that it is loaded.
			let url = '';
			try {
				const next = await apiFetch( {
					path: '/sureforms/v1/email-delivery/status',
				} );
				url = next?.action?.url || next?.links?.setup || '';
			} catch ( e ) {
				// Fall back to SureMail's own screen below.
			}
			window.location.href =
				url ||
				`${ window.srfm_admin?.admin_url }options-general.php?page=suremail#/dashboard`;
		} catch ( error ) {
			running.current = false;
			setProgress( '' );
			onError(
				error?.message ||
					__(
						'SureMail could not be installed. Please try again, or install it from Plugins → Add New.',
						'sureforms'
					)
			);
		}
	};

	return { progress, busy: '' !== progress, label, run };
};

/**
 * A button for the page's action.
 *
 * @param {Object}  props
 * @param {Object}  props.setup    The shared setup state (useSureMailSetup).
 * @param {boolean} props.withIcon Whether to show the gift icon.
 * @return {JSX.Element} Button.
 */
const SetupButton = ( { setup, withIcon = false } ) => (
	<button
		type="button"
		onClick={ setup.run }
		disabled={ setup.busy }
		aria-busy={ setup.busy }
		className="inline-flex cursor-pointer items-center justify-center gap-2 rounded-md border-0 bg-[#0D7EE8] px-4 py-3 text-base font-semibold leading-6 text-white shadow-sm transition-colors hover:bg-[#0B6FCC] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0D7EE8] focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-80"
	>
		{ setup.busy && <Loader size="sm" variant="secondary" /> }
		{ ! setup.busy && withIcon && (
			<Gift className="size-5 shrink-0" aria-hidden="true" />
		) }
		{ setup.label() }
	</button>
);

/**
 * The introduction video, over the page. Escape, the backdrop and the close
 * button dismiss it; focus moves in, Tab stays inside, and focus returns to
 * the opener. Once the player itself has focus its keys belong to YouTube's
 * frame (cross-origin), so Escape there is the player's; the backdrop and the
 * close button still work.
 *
 * @param {Object}   props
 * @param {Function} props.onClose Called to close.
 * @return {JSX.Element} Modal.
 */
const VideoModal = ( { onClose } ) => {
	const closeRef = useRef( null );
	const dialogRef = useRef( null );

	useEffect( () => {
		const opener = closeRef.current?.ownerDocument?.activeElement;
		closeRef.current?.focus();
		const onKey = ( event ) => {
			if ( 'Escape' === event.key ) {
				onClose();
				return;
			}
			// Keep Tab inside the dialog: the close button and the player.
			if ( 'Tab' === event.key && dialogRef.current ) {
				const focusables = [
					...dialogRef.current.querySelectorAll( 'button, iframe' ),
				];
				const first = focusables[ 0 ];
				const last = focusables[ focusables.length - 1 ];
				const active = dialogRef.current.ownerDocument.activeElement;
				if ( event.shiftKey && active === first ) {
					event.preventDefault();
					last.focus();
				} else if (
					! event.shiftKey &&
					( active === last || ! dialogRef.current.contains( active ) )
				) {
					event.preventDefault();
					first.focus();
				}
			}
		};
		document.addEventListener( 'keydown', onKey );
		return () => {
			document.removeEventListener( 'keydown', onKey );
			opener?.focus?.();
		};
	}, [] );

	return (
		// eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions -- Escape is handled on the document; the close button is the keyboard path.
		<div
			className="fixed inset-0 z-[100000] flex items-center justify-center bg-[#111827]/75 p-4"
			onClick={ ( event ) => event.target === event.currentTarget && onClose() }
		>
			<div
				ref={ dialogRef }
				role="dialog"
				aria-modal="true"
				aria-label={ __( 'How SureMail and SureContact work', 'sureforms' ) }
				className="relative w-full max-w-[800px]"
			>
				<button
					ref={ closeRef }
					type="button"
					onClick={ onClose }
					aria-label={ __( 'Close video', 'sureforms' ) }
					className="absolute -top-10 right-0 flex size-8 cursor-pointer items-center justify-center rounded-full border-0 bg-white/10 text-white hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
				>
					<X className="size-5" />
				</button>
				<div className="aspect-video w-full overflow-hidden rounded-lg bg-black shadow-2xl">
					<iframe
						src={ `https://www.youtube-nocookie.com/embed/${ VIDEO_ID }?autoplay=1&rel=0` }
						title={ __( 'SureMail introduction video', 'sureforms' ) }
						className="block size-full border-0"
						allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
						allowFullScreen
					/>
				</div>
			</div>
		</div>
	);
};

const ConnectCard = ( { icon, title, description, highlight = false } ) => (
	<div
		className={ `flex flex-1 flex-col items-center gap-1.5 rounded-lg border border-solid px-3 py-4 text-center ${
			highlight
				? 'border-[#DBEAFE] bg-[#F5F9FF]'
				: 'border-border-subtle bg-background-primary'
		}` }
	>
		{ icon }
		<span className="flex flex-col">
			<span className="text-sm font-semibold leading-5 text-text-primary">
				{ title }
			</span>
			<span className="text-xs leading-4 text-text-secondary">
				{ description }
			</span>
		</span>
	</div>
);

const ConnectArrow = () => (
	<ChevronRight
		className="hidden size-4 shrink-0 text-[#D1D5DB] sm:block"
		aria-hidden="true"
	/>
);

const HowTheyConnect = () => (
	<div className="mt-7 flex w-full flex-col gap-4 rounded-xl border border-solid border-border-subtle bg-background-primary p-5">
		<div className="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center">
			<ConnectCard
				icon={ <SureFormsLogo size={ 32 } /> }
				title={ __( 'SureForms', 'sureforms' ) }
				description={ __( 'Collects the entry', 'sureforms' ) }
			/>
			<ConnectArrow />
			<ConnectCard
				highlight
				icon={ <SureMailLogo size={ 32 } /> }
				title={ __( 'SureMail', 'sureforms' ) }
				description={ __( 'The plugin that connects the two', 'sureforms' ) }
			/>
			<ConnectArrow />
			<ConnectCard
				icon={ <img src={ sureContactIcon } alt="" className="size-8" /> }
				title={ __( 'SureContact', 'sureforms' ) }
				description={ __( 'Sends it and keeps the lead', 'sureforms' ) }
			/>
		</div>
		<p className="m-0 text-left text-sm leading-5 text-text-secondary">
			{ __(
				'SureContact is the service configured by default, not the only option. Once SureMail is installed you can keep it, connect a provider you already use, or run both so one covers the other if it fails.',
				'sureforms'
			) }
		</p>
	</div>
);

const VersusCard = ( { label, labelIcon: LabelIcon, title, items, quick } ) => (
	<div
		className={ `flex flex-1 flex-col rounded-xl border border-solid p-5 text-left ${
			quick
				? 'border-[#DFD2F8] bg-[linear-gradient(135deg,#FCF9FF_0%,#EDE5FB_100%)]'
				: 'border-border-subtle bg-background-primary'
		}` }
	>
		<span
			className={ `flex items-center gap-1.5 text-xs font-semibold uppercase leading-4 tracking-[0.05em] ${
				quick ? 'text-[#8345DD]' : 'text-text-secondary'
			}` }
		>
			<LabelIcon className="size-3.5 shrink-0" aria-hidden="true" />
			{ label }
		</span>
		<span
			className={ `mt-2 text-[19px] font-semibold leading-[19px] tracking-[-0.015em] ${
				quick ? 'text-[#8345DD]' : 'text-[#0F172A]'
			}` }
		>
			{ title }
		</span>
		<ol className="m-0 mt-4 flex list-none flex-col gap-2.5 p-0">
			{ items.map( ( item, index ) => (
				<li key={ item } className="m-0 flex items-center gap-3">
					{ quick ? (
						<span
							className="flex size-5 shrink-0 items-center justify-center rounded-full bg-[#8345DD]"
							aria-hidden="true"
						>
							<Check
								className="size-3 text-white"
								strokeWidth={ 3 }
							/>
						</span>
					) : (
						<span
							className="flex size-5 shrink-0 items-center justify-center rounded-full bg-background-tertiary text-[11px] font-medium leading-none text-[#6B7280]"
							aria-hidden="true"
						>
							{ index + 1 }
						</span>
					) }
					<span className="text-sm leading-5 text-text-secondary">
						{ item }
					</span>
				</li>
			) ) }
		</ol>
	</div>
);

const Versus = () => (
	<div className="mt-[30px] flex w-full flex-col gap-4 md:flex-row">
		<VersusCard
			label={ __( 'Setting up SMTP yourself', 'sureforms' ) }
			labelIcon={ Clock }
			title={ __( 'About 20 minutes', 'sureforms' ) }
			items={ [
				__( 'Choose a provider and make an account', 'sureforms' ),
				__( 'Hunt down the API key or password', 'sureforms' ),
				__( 'Add SPF and DKIM records to your DNS', 'sureforms' ),
				__( 'Wait for those records to propagate', 'sureforms' ),
				__( 'Send a test, then debug what failed', 'sureforms' ),
			] }
		/>
		<VersusCard
			quick
			label={ __( 'With SureContact', 'sureforms' ) }
			labelIcon={ Zap }
			title={ __( 'About 10 seconds', 'sureforms' ) }
			items={ [
				__( 'Click the button, nothing to choose', 'sureforms' ),
				__( 'No API key or password to hunt for', 'sureforms' ),
				__( 'Authentication is handled for you', 'sureforms' ),
				__( 'Nothing to wait for, it works now', 'sureforms' ),
				__( 'Send a test and watch it arrive', 'sureforms' ),
			] }
		/>
	</div>
);

const FEATURES = [
	{
		icon: Gift,
		title: __( 'Multiple Backup Connections', 'sureforms' ),
		description: __(
			'If one route fails, SureMail reroutes through a backup provider automatically.',
			'sureforms'
		),
	},
	{
		icon: ClipboardList,
		title: __( 'Email Logs & Resending', 'sureforms' ),
		description: __(
			'A record of every email your site sent, with one click to send any of them again.',
			'sureforms'
		),
	},
	{
		icon: Users,
		title: __( 'Every Lead Becomes A Contact', 'sureforms' ),
		description: __(
			'Submissions become real contact records you can tag, segment and search, not just table rows.',
			'sureforms'
		),
	},
	{
		icon: Sparkle,
		title: __( 'Automated Follow Ups', 'sureforms' ),
		description: __(
			'Send a welcome note, a quote or a reminder automatically, triggered by the form they filled in.',
			'sureforms'
		),
	},
	{
		icon: MailCheck,
		title: __( 'Built-In Email Testing Tool', 'sureforms' ),
		description: __(
			'Send a test before anything goes live, so you never find out from a missed lead.',
			'sureforms'
		),
	},
	{
		icon: RefreshCw,
		title: __( 'Retries And Any SMTP', 'sureforms' ),
		description: __(
			'Failed sends retry automatically, and you can swap to your own SMTP provider any time.',
			'sureforms'
		),
	},
];

const Features = () => (
	<div className="mt-9 grid w-full grid-cols-1 text-left md:grid-cols-2">
		{ FEATURES.map( ( { icon: Icon, title, description } ) => (
			<div key={ title } className="flex flex-col p-5">
				<Icon
					className="size-6 text-[#0D7EE8]"
					strokeWidth={ 1.25 }
					aria-hidden="true"
				/>
				<span className="mt-1.5 text-lg font-semibold leading-7 text-text-primary">
					{ title }
				</span>
				<span className="mt-2 text-sm leading-5 text-text-secondary">
					{ description }
				</span>
			</div>
		) ) }
	</div>
);

const FreeEmailsCard = ( { freeEmails } ) => (
	<div className="mt-8 flex w-full flex-col items-center gap-6 rounded-xl border border-solid border-[#DACFF9] bg-[linear-gradient(90deg,#FEFDFF_0%,#E8DDFC_100%)] px-8 py-4 text-left md:flex-row">
		<div className="flex flex-1 flex-col">
			<span className="text-lg font-semibold leading-7 text-text-primary">
				{ sprintf(
					/* translators: %d: number of free emails. */
					_n(
						'Start with %d free email',
						'Start with %d free emails',
						freeEmails,
						'sureforms'
					),
					freeEmails
				) }
			</span>
			<span className="mt-2 text-sm leading-5 text-text-secondary">
				{ __(
					'A one time welcome credit on your new SureContact account. Enough to set everything up, test it properly and cover your first run of submissions.',
					'sureforms'
				) }
			</span>
			<span className="mt-3.5 flex flex-wrap gap-2">
				{ [
					__( 'No credit card', 'sureforms' ),
					__( 'No expiry', 'sureforms' ),
					__( 'Top up only if you need more', 'sureforms' ),
				].map( ( chip ) => (
					<span
						key={ chip }
						className="rounded-full border border-solid border-[#DFD2F8] bg-white px-3 py-[3px] text-xs leading-4 text-[#5F3D9E]"
					>
						{ chip }
					</span>
				) ) }
			</span>
		</div>
		<div className="relative h-[163px] w-[169px] shrink-0">
			<object
				type="image/svg+xml"
				data={ freeEmailsVisual }
				className="pointer-events-none block size-full"
				aria-label={ __( 'A gift box with emails rising out of it', 'sureforms' ) }
				tabIndex={ -1 }
			/>
			<span className="absolute inset-x-0 top-[112px] text-center text-base font-semibold leading-6 text-text-primary">
				{ sprintf(
					/* translators: %d: number of free emails. */
					_n( '%d free email', '%d free emails', freeEmails, 'sureforms' ),
					freeEmails
				) }
			</span>
			<span className="absolute inset-x-0 top-[136px] text-center text-[9px] leading-3 text-text-secondary">
				{ __( 'One time, no card needed', 'sureforms' ) }
			</span>
		</div>
	</div>
);

const SureMail = () => {
	const status = window.srfm_admin?.suremail_status || 'not_installed';
	const freeEmails = getFreeEmails();
	const [ error, setError ] = useState( '' );
	const setup = useSureMailSetup( status, setError );
	const [ videoOpen, setVideoOpen ] = useState( false );

	const errorNote = error ? (
		<p className="m-0 mt-3 text-sm leading-5 text-text-error" role="alert">
			{ error }
		</p>
	) : null;

	return (
		<div className="my-14 px-4">
			<style>{ `
				.notice, .update-nag, #wpfooter { display: none !important; }
				.wrap { margin: 0 !important; }
			` }</style>
			<div className="mx-auto flex max-w-[896px] flex-col items-center rounded-xl border border-solid border-border-subtle bg-white p-6 text-center shadow-sm md:p-12">
				<div className="flex items-center gap-8">
					<SureFormsLogo size={ 48 } />
					<Operator>+</Operator>
					<SureMailLogo size={ 48 } />
					<Operator>+</Operator>
					<img src={ sureContactIcon } alt="" className="size-12" />
					<Operator>=</Operator>
					<InboxIcon />
				</div>

				<h1 className="m-0 mt-8 p-0 text-xl font-semibold leading-[30px] tracking-[-0.025em] text-text-primary">
					{ freeEmails > 0
						? sprintf(
							/* translators: %d: number of free emails. */
							_n(
								'Every form submission reaches the inbox. Starting with %d free email.',
								'Every form submission reaches the inbox. Starting with %d free emails.',
								freeEmails,
								'sureforms'
							),
							freeEmails
						  )
						: __( 'Every form submission reaches the inbox.', 'sureforms' ) }
				</h1>
				<p className="m-0 mt-1.5 text-base leading-6 text-text-secondary">
					{ freeEmails > 0
						? sprintf(
							/* translators: %d: number of free emails. */
							_n(
								"SureForms collects the entry. SureMail takes over your site's email. SureContact does the sending. One click installs SureMail and opens SureContact's setup, and the first %d email is on us.",
								"SureForms collects the entry. SureMail takes over your site's email. SureContact does the sending. One click installs SureMail and opens SureContact's setup, and the first %d emails are on us.",
								freeEmails,
								'sureforms'
							),
							freeEmails
						  )
						: __(
							"SureForms collects the entry. SureMail takes over your site's email. SureContact does the sending. One click installs SureMail and opens SureContact's setup.",
							'sureforms'
						  ) }
				</p>

				<div className="mt-[30px]">
					<SetupButton setup={ setup } />
				</div>
				{ errorNote }
				<p className="m-0 mt-4 text-sm leading-5 text-[#6B7280]">
					{ __( 'Both are free. No card needed. About 10 seconds.', 'sureforms' ) }
				</p>
				<button
					type="button"
					onClick={ () => setVideoOpen( true ) }
					className="mt-2 inline-flex cursor-pointer items-center gap-2 border-0 bg-transparent p-0 text-sm leading-5 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#8345DD] focus-visible:ring-offset-2"
				>
					<span
						className="flex size-5 items-center justify-center rounded-full bg-[#F1EAFD]"
						aria-hidden="true"
					>
						<Play className="size-2 fill-[#8345DD] text-[#8345DD]" />
					</span>
					<span className="font-medium text-[#8345DD]">
						{ __( 'Watch how it works', 'sureforms' ) }
					</span>
					<span className="text-[#6B7280]">
						{ sprintf(
							/* translators: %d: video length in minutes. */
							_n( '%d minute', '%d minutes', VIDEO_MINUTES, 'sureforms' ),
							VIDEO_MINUTES
						) }
					</span>
				</button>

				<HowTheyConnect />
				<Versus />
				<Features />
				{ freeEmails > 0 && <FreeEmailsCard freeEmails={ freeEmails } /> }

				<h2 className="m-0 mt-7 p-0 text-lg font-semibold leading-7 text-text-primary">
					{ __( 'Forms Submitted. Emails Delivered. Leads Kept.', 'sureforms' ) }
				</h2>
				<div className="mt-4">
					<SetupButton setup={ setup } withIcon />
				</div>
				<ul className="m-0 mt-3 flex list-none flex-wrap items-center justify-center gap-x-5 gap-y-2 p-0">
					{ [
						__( 'No credit card', 'sureforms' ),
						__( 'Disconnect any time', 'sureforms' ),
						__( 'Keep your own SMTP if you prefer', 'sureforms' ),
					].map( ( item ) => (
						<li
							key={ item }
							className="m-0 flex items-center gap-2 text-sm leading-5 text-text-secondary"
						>
							<Check
								className="size-4 text-support-success"
								aria-hidden="true"
							/>
							{ item }
						</li>
					) ) }
				</ul>
			</div>

			{ videoOpen && <VideoModal onClose={ () => setVideoOpen( false ) } /> }
		</div>
	);
};

// Render the SureMail component when the DOM is ready
document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'srfm-suremail-container' );
	if ( container ) {
		const root = createRoot( container );
		root.render( <SureMail /> );
	}
} );

export default SureMail;
