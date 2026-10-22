import { __, _n, sprintf } from '@wordpress/i18n';
import { createInterpolateElement, useRef } from '@wordpress/element';
import {
	AlertTriangle,
	Check,
	CornerUpLeft,
	MailX,
	Plus,
	RotateCcw,
} from 'lucide-react';
import { cn } from '@Utils/Helpers';

// Smart tags the template messages use, including the SureForms Pro results
// (quiz, calculation, payment and poll).
const TAG_PATTERN =
	/\{(form:[a-z0-9_-]+|form-payment:[a-z0-9_-]+:[a-z-]+|quiz_[a-z_]+|poll_results|calculation-result|site_title|entry_id|current_page_url|admin_email)\}/gi;

// Paragraphs in the visitor email's text are separated by a blank line.
const PARAGRAPH_SPLIT = /\n\s*\n/;

// Answers listed in email previews; the rest are counted.
const ANSWERS_SHOWN = 4;

// Shown for {entry_id} in previews.
const SAMPLE_ENTRY_ID = '1042';

const ringClass =
	'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-border-interactive';

const inputClass =
	'w-full px-3 py-2 m-0 text-sm leading-5 rounded-md border border-solid border-border-subtle bg-background-primary text-text-primary shadow-none focus:outline-none focus:border-border-interactive focus:ring-2 focus:ring-border-interactive';

/**
 * Whether a template has messages that can be previewed.
 *
 * @since x.x.x
 * @param {Object} template Template.
 * @return {boolean} Has messages.
 */
export const hasMessages = ( template ) =>
	Boolean( template?.messages?.confirmation && template?.messages?.email );

/**
 * Split the visitor email's text into its lines (paragraphs).
 *
 * @param {string} text Text with paragraphs separated by blank lines.
 * @return {string[]} Lines.
 */
const toLines = ( text ) =>
	String( text || '' )
		.split( PARAGRAPH_SPLIT )
		.map( ( line ) => line.trim() )
		.filter( Boolean );

/**
 * The template's own text for every editable message, by key.
 *
 * @since x.x.x
 * @param {Object} messages `template.messages`.
 * @return {Object} Values by key.
 */
export const getMessageDefaults = ( messages ) => {
	const autoresponder = messages?.autoresponder;
	return {
		'confirmation.heading': messages?.confirmation?.heading || '',
		'confirmation.text': messages?.confirmation?.text || '',
		email_subject: messages?.email?.subject || '',
		email_intro: messages?.email?.intro || '',
		email_all_data: false !== messages?.email?.all_data,
		'autoresponder.enabled': true,
		'autoresponder.subject': autoresponder?.subject || '',
		'autoresponder.greeting': autoresponder?.greeting || '',
		'autoresponder.lines': ( autoresponder?.lines || [] ).join( '\n\n' ),
	};
};

/**
 * What the user changed, in the shape the create endpoint takes. Unchanged
 * values are left out; nothing changed gives undefined.
 *
 * @since x.x.x
 * @param {Object} messages `template.messages`.
 * @param {Object} values   Current values by key.
 * @return {Object|undefined} Changes.
 */
export const buildMessageChanges = ( messages, values ) => {
	if ( ! messages ) {
		return undefined;
	}
	const defaults = getMessageDefaults( messages );
	const changed = ( key ) => values[ key ] !== defaults[ key ];
	const changes = {};

	const confirmation = {};
	if ( changed( 'confirmation.heading' ) ) {
		confirmation.heading = values[ 'confirmation.heading' ];
	}
	if ( changed( 'confirmation.text' ) ) {
		confirmation.text = values[ 'confirmation.text' ];
	}
	if ( Object.keys( confirmation ).length ) {
		changes.confirmation = confirmation;
	}
	if ( changed( 'email_subject' ) ) {
		changes.email_subject = values.email_subject;
	}
	if ( changed( 'email_intro' ) ) {
		changes.email_intro = values.email_intro;
	}
	if ( changed( 'email_all_data' ) ) {
		changes.email_all_data = values.email_all_data;
	}

	if ( messages.autoresponder ) {
		const autoresponder = {};
		if ( changed( 'autoresponder.enabled' ) ) {
			autoresponder.enabled = values[ 'autoresponder.enabled' ];
		}
		if ( changed( 'autoresponder.subject' ) ) {
			autoresponder.subject = values[ 'autoresponder.subject' ];
		}
		if ( changed( 'autoresponder.greeting' ) ) {
			autoresponder.greeting = values[ 'autoresponder.greeting' ];
		}
		const lines = toLines( values[ 'autoresponder.lines' ] );
		if (
			JSON.stringify( lines ) !==
			JSON.stringify( messages.autoresponder.lines || [] )
		) {
			autoresponder.lines = lines;
		}
		if ( Object.keys( autoresponder ).length ) {
			changes.autoresponder = autoresponder;
		}
	}

	return Object.keys( changes ).length ? changes : undefined;
};

/**
 * Answers sorted for the insert chips: required ones first.
 *
 * @param {Array} answers `template.answers`.
 * @return {Array} Sorted answers.
 */
const sortAnswers = ( answers ) => [
	...( answers || [] ).filter( ( answer ) => answer.required ),
	...( answers || [] ).filter( ( answer ) => ! answer.required ),
];

/**
 * A site value localized for the admin screens: the template library's
 * `srfm_admin`, or the block editor's `srfm_block_data`.
 *
 * @param {string} key Key.
 * @return {string} Value, '' when missing.
 */
const siteData = ( key ) =>
	( typeof srfm_admin !== 'undefined' && srfm_admin?.[ key ] ) ||
	( typeof srfm_block_data !== 'undefined' && srfm_block_data?.[ key ] ) ||
	'';

/**
 * Sample values for the smart tags, for previews.
 *
 * @param {Object} template Template: its `answers` and `messages.tag_samples`.
 * @return {Object} { samples, tagSamples, siteTitle, siteUrl, adminEmail }.
 */
const getSampleContext = ( template ) => ( {
	samples: Object.fromEntries(
		( template?.answers || [] ).map( ( answer ) => [
			answer.slug,
			answer.sample,
		] )
	),
	// Lowercase tag ( `{quiz_score}` ) to its sample.
	tagSamples: Object.fromEntries(
		Object.entries( template?.messages?.tag_samples || {} ).map(
			( [ tag, sample ] ) => [ tag.toLowerCase(), sample ]
		)
	),
	siteTitle:
		siteData( 'site_title' ) ||
		// The block editor doesn't localize the title; the admin bar has it.
		document
			.querySelector( '#wp-admin-bar-site-name > .ab-item' )
			?.textContent?.trim() ||
		__( 'Your Site', 'sureforms' ),
	siteUrl: siteData( 'site_url' ) || window.location.origin,
	adminEmail: siteData( 'admin_email' ) || 'admin@example.com',
} );

/**
 * Text with its smart tags replaced by sample values, which are marked so
 * it is clear they change per submission.
 *
 * @param {string} text    Raw text.
 * @param {Object} context From getSampleContext().
 * @return {Array} Nodes.
 */
const renderWithSamples = ( text, context ) => {
	const nodes = [];
	let last = 0;
	String( text || '' ).replace( TAG_PATTERN, ( match, tag, offset ) => {
		nodes.push( text.slice( last, offset ) );
		last = offset + match.length;
		const name = tag.toLowerCase();
		// A SureForms Pro result (e.g. the quiz score) has its own sample.
		let value = context.tagSamples?.[ `{${ name }}` ];
		if ( undefined === value && name.startsWith( 'form:' ) ) {
			value = context.samples[ name.slice( 5 ) ];
		} else if ( undefined === value ) {
			value = {
				site_title: context.siteTitle,
				entry_id: SAMPLE_ENTRY_ID,
				current_page_url: context.siteUrl,
				admin_email: context.adminEmail,
			}[ name ];
		}
		nodes.push(
			undefined === value ? (
				<span key={ offset } className="opacity-60">
					{ match }
				</span>
			) : (
				<span
					key={ offset }
					className="underline decoration-dotted decoration-1 underline-offset-2"
					title={ match }
				>
					{ value }
				</span>
			)
		);
		return match;
	} );
	nodes.push( String( text || '' ).slice( last ) );
	return nodes;
};

/**
 * Optional answers a text uses, which could be blank.
 *
 * @param {string} text    Raw text.
 * @param {Array}  answers `template.answers`.
 * @return {Array} Optional answers used.
 */
const optionalAnswersIn = ( text, answers ) =>
	( answers || [] ).filter(
		( answer ) =>
			! answer.required &&
			String( text || '' )
				.toLowerCase()
				.includes( `{form:${ answer.slug }}` )
	);

/**
 * Where a tag goes in a text: at the selection, but never inside another
 * tag (it moves past that tag's closing brace), with a space added when it
 * would otherwise touch a word.
 *
 * @since x.x.x
 * @param {string} value Text.
 * @param {number} start Selection start.
 * @param {number} end   Selection end.
 * @param {string} tag   Tag to insert.
 * @return {{before: string, after: string, text: string}} Parts.
 */
export const placeTag = ( value, start, end, tag ) => {
	let from = start;
	let to = end;
	// Selection edges inside a {…} token move to just after it.
	const tokenEnd = ( index ) => {
		const open = value.lastIndexOf( '{', index - 1 );
		const close = value.lastIndexOf( '}', index - 1 );
		if ( open > close ) {
			const next = value.indexOf( '}', index );
			if ( -1 !== next && ! value.slice( index, next ).includes( '{' ) ) {
				return next + 1;
			}
		}
		return index;
	};
	from = tokenEnd( from );
	to = Math.max( from, tokenEnd( to ) );
	const before = value.slice( 0, from );
	const after = value.slice( to );
	const touchesWord = ( char ) => /[\w}]/.test( char || '' );
	const text =
		( touchesWord( before.slice( -1 ) ) ? ' ' : '' ) +
		tag +
		( touchesWord( after.charAt( 0 ) ) || '{' === after.charAt( 0 ) ? ' ' : '' );
	return { before, after, text };
};

/**
 * Inserting answers at the caret of the field last focused. Each group of
 * fields has its own chips: when the last focused field is not in the
 * group, the tag goes at the end of the group's last field.
 *
 * @param {Object}   values   Current values by key.
 * @param {Function} setValue Called with ( key, value ).
 * @return {Object} { fieldProps( key ), insert( tag, keys ) }.
 */
const useAnswerInsertion = ( values, setValue ) => {
	const fields = useRef( {} );
	const lastKey = useRef( '' );

	const fieldProps = ( key ) => ( {
		ref: ( element ) => {
			fields.current[ key ] = element;
		},
		onFocus: () => {
			lastKey.current = key;
		},
	} );

	const insert = ( tag, keys ) => {
		const hasCaret = keys.includes( lastKey.current );
		const key = hasCaret ? lastKey.current : keys[ keys.length - 1 ];
		const element = fields.current[ key ];
		const value = String( values[ key ] || '' );
		const start =
			hasCaret && element
				? element.selectionStart ?? value.length
				: value.length;
		const end = hasCaret && element ? element.selectionEnd ?? start : start;
		const { before, after, text } = placeTag( value, start, end, tag );
		setValue( key, before + text + after );
		lastKey.current = key;
		const caret = before.length + text.length;
		window.requestAnimationFrame( () => {
			if ( element?.isConnected ) {
				element.focus();
				element.setSelectionRange?.( caret, caret );
			}
		} );
	};

	return { fieldProps, insert };
};

/**
 * Chips that insert an answer, or a SureForms Pro result such as the quiz
 * score, into the field being edited.
 *
 * @param {Object}   props          Props.
 * @param {Array}    props.answers  `template.answers`.
 * @param {Array}    props.results  `template.messages.result_tags`: { tag, label }.
 * @param {Function} props.onInsert Called with the tag to insert.
 */
const AnswerChips = ( { answers, results = [], onInsert } ) => {
	// Results (a calculated total, say) are offered in their own group.
	const resultTags = results.map( ( result ) => result.tag );
	const sorted = sortAnswers( answers ).filter(
		( answer ) => ! resultTags.includes( `{form:${ answer.slug }}` )
	);
	if ( ! sorted.length && ! results.length ) {
		return null;
	}
	const firstOptional = sorted.findIndex( ( answer ) => ! answer.required );

	return (
		<div
			role="group"
			aria-label={ __( 'Insert answer', 'sureforms' ) }
		>
			<div className="flex flex-col gap-2 p-3 rounded-md bg-background-secondary">
				<div className="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5">
					<span className="text-xs font-semibold text-text-primary">
						{ __( 'Insert answer', 'sureforms' ) }
					</span>
					<span className="text-xs text-text-tertiary">
						{ __(
							'Added where your cursor was in the field you last edited.',
							'sureforms'
						) }
					</span>
				</div>
				<div className="flex flex-wrap items-center gap-1.5">
					{ sorted.map( ( answer, index ) => (
						<span key={ answer.slug } className="contents">
							{ index === firstOptional && (
								<span className="ml-1 text-[11px] text-text-tertiary">
									{ __(
										'(optional – may be blank)',
										'sureforms'
									) }
								</span>
							) }
							<button
								type="button"
								// Keep focus and the caret in the field.
								onMouseDown={ ( event ) =>
									event.preventDefault()
								}
								onClick={ () =>
									onInsert( `{form:${ answer.slug }}` )
								}
								aria-label={
									answer.required
										? sprintf(
											/* translators: %s: field label. */
											__( 'Insert %s', 'sureforms' ),
											answer.label
										  )
										: sprintf(
											/* translators: %s: field label. */
											__(
												'Insert %s (optional – may be blank)',
												'sureforms'
											),
											answer.label
										  )
								}
								title={ `{form:${ answer.slug }}` }
								className={ cn(
									'flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full border cursor-pointer transition-colors motion-reduce:transition-none',
									ringClass,
									answer.required
										? 'border-solid border-border-subtle bg-background-secondary text-text-primary hover:border-border-interactive'
										: 'border-dashed border-border-subtle bg-transparent text-text-secondary hover:text-text-primary hover:border-border-interactive'
								) }
							>
								<Plus className="size-3" aria-hidden="true" />
								{ answer.label }
							</button>
						</span>
					) ) }
				</div>
				{ results.length > 0 && (
					<div
						className="flex flex-wrap items-center gap-1.5"
						role="group"
						aria-label={ __( 'Insert result', 'sureforms' ) }
					>
						<span className="mr-0.5 text-[11px] font-semibold text-text-secondary">
							{ __( 'Results', 'sureforms' ) }
						</span>
						{ results.map( ( result ) => (
							<button
								key={ result.tag }
								type="button"
								onMouseDown={ ( event ) => event.preventDefault() }
								onClick={ () => onInsert( result.tag ) }
								aria-label={ sprintf(
									/* translators: %s: result name, e.g. "Quiz score". */
									__( 'Insert result: %s', 'sureforms' ),
									result.label
								) }
								title={ result.tag }
								className={ cn(
									'flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full border border-solid border-brand-border-300 bg-brand-background-50 text-brand-800 cursor-pointer transition-colors hover:border-border-interactive motion-reduce:transition-none',
									ringClass
								) }
							>
								<Plus className="size-3" aria-hidden="true" />
								{ result.label }
							</button>
						) ) }
					</div>
				) }
			</div>
		</div>
	);
};

/**
 * A labelled text input or textarea for a message, with an optional warning.
 *
 * @param {Object}   props           Props.
 * @param {string}   props.id        Element id.
 * @param {string}   props.label     Label.
 * @param {string}   props.value     Value.
 * @param {Function} props.onChange  Called with the new value.
 * @param {boolean}  props.multiline Textarea instead of an input.
 * @param {number}   props.rows      Textarea rows.
 * @param {string}   props.help      Help text.
 * @param {string}   props.warning   Warning text.
 * @param {Object}   props.field     From useAnswerInsertion().fieldProps().
 */
const MessageField = ( {
	id,
	label,
	value,
	onChange,
	multiline = false,
	rows = 3,
	help,
	warning,
	field,
} ) => {
	const Tag = multiline ? 'textarea' : 'input';
	const describedBy =
		[ help && `${ id }-help`, warning && `${ id }-warning` ]
			.filter( Boolean )
			.join( ' ' ) || undefined;
	return (
		<div className="flex flex-col gap-1.5">
			<label
				htmlFor={ id }
				className="text-xs font-medium text-text-secondary"
			>
				{ label }
			</label>
			<Tag
				id={ id }
				type={ multiline ? undefined : 'text' }
				rows={ multiline ? rows : undefined }
				value={ value }
				onChange={ ( event ) => onChange( event.target.value ) }
				aria-describedby={ describedBy }
				spellCheck
				className={ cn( inputClass, multiline && 'resize-y min-h-16' ) }
				{ ...field }
			/>
			{ help && (
				<span
					id={ `${ id }-help` }
					className="text-xs text-text-tertiary"
				>
					{ help }
				</span>
			) }
			{ warning && (
				<span
					id={ `${ id }-warning` }
					className="flex items-start gap-1 text-xs text-badge-color-yellow"
				>
					<AlertTriangle
						className="size-3.5 shrink-0 mt-px"
						aria-hidden="true"
					/>
					{ warning }
				</span>
			) }
		</div>
	);
};

/**
 * Warning for a subject that uses optional answers.
 *
 * @param {string} text    Subject.
 * @param {Array}  answers `template.answers`.
 * @return {string} Warning, '' for none.
 */
const subjectWarning = ( text, answers ) => {
	const optional = optionalAnswersIn( text, answers );
	if ( ! optional.length ) {
		return '';
	}
	return sprintf(
		/* translators: %s: field labels, comma separated. */
		__(
			'Uses %s, which is optional, so this part of the subject may be blank.',
			'sureforms'
		),
		optional.map( ( answer ) => answer.label ).join( ', ' )
	);
};

/**
 * "Reset to template text" link.
 *
 * @param {Object}   props         Props.
 * @param {Function} props.onReset Reset.
 */
const ResetLink = ( { onReset } ) => (
	<button
		type="button"
		onClick={ onReset }
		className={ cn(
			'flex items-center gap-1 p-0 text-xs text-text-secondary bg-transparent border-0 cursor-pointer hover:text-text-primary',
			ringClass
		) }
	>
		<RotateCcw className="size-3" aria-hidden="true" />
		{ __( 'Reset to template text', 'sureforms' ) }
	</button>
);

/**
 * Card holding the fields that edit one message.
 *
 * @param {Object} props           Props.
 * @param {string} props.title     Heading.
 * @param {*}      props.action    Shown beside the heading, e.g. a reset link.
 * @param {string} props.className Extra classes.
 * @param {*}      props.children  Fields.
 */
const EditCard = ( { title, action, className, children } ) => (
	<div
		className={ cn(
			'flex flex-col gap-4 p-4 sm:p-5 rounded-xl bg-background-primary border border-solid border-border-subtle shadow-[0_1px_2px_rgba(17,24,39,.04)]',
			className
		) }
	>
		<div className="flex items-center justify-between gap-3 min-h-5">
			<span className="text-sm font-semibold text-text-primary">
				{ title }
			</span>
			{ action }
		</div>
		{ children }
	</div>
);

/**
 * A labelled on/off switch.
 *
 * @param {Object}   props          Props.
 * @param {string}   props.label    Label, before the switch.
 * @param {boolean}  props.checked  Whether it is on.
 * @param {Function} props.onChange Called with the new state.
 */
const Switch = ( { label, checked, onChange } ) => (
	<button
		type="button"
		role="switch"
		aria-checked={ checked }
		onClick={ () => onChange( ! checked ) }
		className={ cn(
			'flex items-center gap-2 p-0 text-xs font-medium text-text-secondary bg-transparent border-0 cursor-pointer',
			ringClass
		) }
	>
		{ label }
		<span
			className={ cn(
				'relative inline-flex w-8 h-[18px] shrink-0 rounded-full transition-colors duration-150 motion-reduce:transition-none',
				checked ? 'bg-toggle-on' : 'bg-toggle-off-hover'
			) }
			aria-hidden="true"
		>
			<span
				className={ cn(
					'absolute top-0.5 left-0.5 size-3.5 rounded-full bg-white shadow-sm transition-transform duration-150 motion-reduce:transition-none',
					checked ? 'translate-x-3.5' : 'translate-x-0'
				) }
			/>
		</span>
	</button>
);

/**
 * Two-column list of the answers, like the {all_data} table in emails.
 *
 * @param {Object} props         Props.
 * @param {Array}  props.answers `template.answers`.
 */
const AnswersTable = ( { answers } ) => {
	const list = answers || [];
	const shown = list.slice( 0, ANSWERS_SHOWN );
	const more = list.length - shown.length;
	return (
		<div className="flex flex-col gap-1.5">
			<dl className="m-0 flex flex-col text-[13px]">
				{ shown.map( ( answer ) => (
					<div
						key={ answer.slug }
						className="flex gap-2.5 py-[7px] border-0 border-t border-solid border-border-subtle"
					>
						<dt className="m-0 basis-[38%] shrink-0 font-semibold text-text-primary break-words">
							{ answer.label }
						</dt>
						<dd className="m-0 min-w-0 text-text-secondary break-words whitespace-pre-line">
							{ answer.sample || '—' }
						</dd>
					</div>
				) ) }
			</dl>
			{ more > 0 && (
				<span className="text-xs text-text-tertiary">
					{ sprintf(
						/* translators: %d: number of answers not shown. */
						_n(
							'+ %d more answer',
							'+ %d more answers',
							more,
							'sureforms'
						),
						more
					) }
				</span>
			) }
		</div>
	);
};

/**
 * Paragraphs of text with sample values.
 *
 * @param {Object} props         Props.
 * @param {string} props.text    Raw text, paragraphs separated by blank lines.
 * @param {Object} props.context From getSampleContext().
 */
const Paragraphs = ( { text, context } ) =>
	toLines( text ).map( ( line, index ) => (
		<p
			key={ index }
			className="m-0 text-text-primary whitespace-pre-line"
		>
			{ renderWithSamples( line, context ) }
		</p>
	) );

/**
 * The "Thank-you page" tab: the message shown after submitting, as the
 * visitor will see it, and (when editable) the fields to change it.
 *
 * @since x.x.x
 * @param {Object}   props          Props.
 * @param {Object}   props.template Template.
 * @param {Object}   props.values   Message values by key.
 * @param {Function} props.setValue Called with ( key, value ).
 * @param {Object}   props.look     From resolveLook(): the form's colors.
 * @param {boolean}  props.editable Show the fields to edit the message.
 * @param {string}   props.device   'desktop' or 'mobile'.
 */
export const ThankYouPanel = ( {
	template,
	values,
	setValue,
	look,
	editable,
	device,
} ) => {
	const context = getSampleContext( template );
	const { fieldProps, insert } = useAnswerInsertion( values, setValue );
	const defaults = getMessageDefaults( template.messages );
	const keys = [ 'confirmation.heading', 'confirmation.text' ];
	const isChanged = keys.some( ( key ) => values[ key ] !== defaults[ key ] );

	return (
		<div className="flex flex-col items-center gap-6 w-full">
			{ /* The form card after submitting: its colors, padding and
			corners, with the message centred under a check in its accent. */ }
			<section
				className={ cn(
					'flex flex-col items-center gap-[18px] w-full p-7 rounded-[10px] text-center transition-[max-width] duration-300 motion-reduce:transition-none',
					'mobile' === device ? 'max-w-[375px]' : 'max-w-[460px]'
				) }
				style={ { background: look.background, color: look.text } }
				aria-label={ __( 'Thank-you page preview', 'sureforms' ) }
			>
				<span
					className="flex items-center justify-center size-[52px] rounded-full"
					style={ { background: look.primary, color: look.onPrimary } }
					aria-hidden="true"
				>
					<Check className="size-[26px]" strokeWidth={ 2.4 } />
				</span>
				<h2
					className="m-0 text-xl leading-tight font-semibold break-words"
					style={ { color: look.text } }
				>
					{ renderWithSamples(
						values[ 'confirmation.heading' ],
						context
					) }
				</h2>
				<p
					className="m-0 text-sm leading-relaxed whitespace-pre-line break-words"
					style={ { color: look.text } }
				>
					{ renderWithSamples( values[ 'confirmation.text' ], context ) }
				</p>
			</section>
			{ editable && (
				<EditCard
					className="w-full max-w-[640px]"
					title={ __( 'Thank-you message', 'sureforms' ) }
					action={
						isChanged && (
							<ResetLink
								onReset={ () =>
									keys.forEach( ( key ) =>
										setValue( key, defaults[ key ] )
									)
								}
							/>
						)
					}
				>
					<AnswerChips
						answers={ template.answers }
						results={ template.messages?.result_tags }
						onInsert={ ( tag ) => insert( tag, keys ) }
					/>
					<MessageField
						id="srfm-template-confirmation-heading"
						label={ __( 'Heading', 'sureforms' ) }
						value={ values[ 'confirmation.heading' ] }
						onChange={ ( value ) =>
							setValue( 'confirmation.heading', value )
						}
						field={ fieldProps( 'confirmation.heading' ) }
					/>
					<MessageField
						id="srfm-template-confirmation-text"
						label={ __( 'Message', 'sureforms' ) }
						value={ values[ 'confirmation.text' ] }
						onChange={ ( value ) =>
							setValue( 'confirmation.text', value )
						}
						multiline
						rows={ 3 }
						field={ fieldProps( 'confirmation.text' ) }
					/>
				</EditCard>
			) }
		</div>
	);
};

/**
 * One email as a compact card: who it is from and to, the fields to edit it
 * (when given), then the email itself with sample answers, so each change is
 * visible right below.
 *
 * @param {Object} props          Props.
 * @param {string} props.id       Id of the card's heading.
 * @param {Object} props.look     From resolveLook(): the accent for the sender's avatar.
 * @param {string} props.from     Sender name.
 * @param {string} props.to       Who gets it.
 * @param {string} props.note     Line under the sender, e.g. "Sent automatically".
 * @param {*}      props.action   Shown at the end of the header, e.g. a toggle.
 * @param {*}      props.fields   Edit fields, shown above the email.
 * @param {*}      props.reset    Reset link for the fields.
 * @param {string} props.tag      What the email is, e.g. "Auto-reply".
 * @param {*}      props.subject  Subject, with sample answers.
 * @param {*}      props.children Body.
 */
const EmailCard = ( {
	id,
	look,
	from,
	to,
	note,
	action,
	fields,
	reset,
	tag,
	subject,
	children,
} ) => (
	<article
		className="flex flex-col rounded-xl bg-background-primary border border-solid border-border-subtle shadow-[0_1px_2px_rgba(17,24,39,.04)] overflow-hidden"
		aria-labelledby={ id }
	>
		<header className="flex flex-wrap items-center gap-x-2.5 gap-y-2 px-4 sm:px-[18px] py-3.5 bg-[#FAFAFB] border-0 border-b border-solid border-border-subtle">
			<span
				className="flex items-center justify-center size-8 shrink-0 rounded-full text-[13px] font-bold uppercase"
				style={ { background: look.primary, color: look.onPrimary } }
				aria-hidden="true"
			>
				{ ( from || 'S' ).trim().charAt( 0 ) }
			</span>
			<span
				id={ id }
				className="flex-1 min-w-0 text-xs leading-normal text-text-tertiary break-words"
			>
				{ createInterpolateElement(
					sprintf(
						/* translators: 1: sender, e.g. the site title, 2: recipient, e.g. an email address. */
						__( '<b>%1$s</b> to %2$s', 'sureforms' ),
						from,
						to
					),
					{ b: <b className="font-semibold text-text-primary" /> }
				) }
				<br />
				{ note }
			</span>
			{ action }
		</header>
		{ fields && (
			<div className="flex flex-col gap-4 px-4 sm:px-[18px] py-4 border-0 border-b border-solid border-border-subtle">
				{ reset && <div className="flex justify-end -mb-2">{ reset }</div> }
				{ fields }
			</div>
		) }
		<div className="flex flex-col gap-2 px-4 sm:px-[18px] pt-4 pb-[18px] text-sm leading-relaxed text-text-primary">
			<div className="flex flex-wrap items-center gap-2">
				<span className="inline-block px-2 py-0.5 text-[11px] font-semibold rounded-full bg-background-tertiary text-text-tertiary">
					{ tag }
				</span>
				{ fields && (
					<span className="text-[11px] text-text-tertiary">
						{ __( 'Preview with sample answers', 'sureforms' ) }
					</span>
				) }
			</div>
			{ subject && (
				<h4 className="m-0 text-base leading-snug font-semibold text-text-primary break-words">
					{ subject }
				</h4>
			) }
			<div className="flex flex-col gap-3 text-[13.5px] leading-relaxed">
				{ children }
			</div>
		</div>
	</article>
);

/**
 * The "Emails" tab: the notification to the site owner and, when the
 * template has one, the confirmation to the visitor, with the fields to
 * change them when editable.
 *
 * @since x.x.x
 * @param {Object}   props          Props.
 * @param {Object}   props.template Template.
 * @param {Object}   props.values   Message values by key.
 * @param {Function} props.setValue Called with ( key, value ).
 * @param {Object}   props.look     From resolveLook(): the form's colors.
 * @param {boolean}  props.editable Show the fields to edit the emails.
 * @param {boolean}  props.held     The visitor email is held back because the
 *                                  site has no spam protection.
 */
export const EmailsPanel = ( {
	template,
	values,
	setValue,
	look,
	editable,
	held = false,
} ) => {
	const context = getSampleContext( template );
	const { fieldProps, insert } = useAnswerInsertion( values, setValue );
	const defaults = getMessageDefaults( template.messages );
	const answers = template.answers || [];
	const autoresponder = template.messages?.autoresponder;
	const isSent = ! held && ( ! editable || values[ 'autoresponder.enabled' ] );
	const visitorEmail =
		answers.find( ( answer ) => 'email' === answer.slug )?.sample ||
		'visitor@example.com';
	const reset = ( keys ) =>
		keys.forEach( ( key ) => setValue( key, defaults[ key ] ) );
	const isChanged = ( keys ) =>
		keys.some( ( key ) => values[ key ] !== defaults[ key ] );

	const ownerKeys = [ 'email_subject', 'email_intro' ];
	const ownerResetKeys = [ ...ownerKeys, 'email_all_data' ];
	const allData = ! editable || values.email_all_data;
	const visitorKeys = [
		'autoresponder.subject',
		'autoresponder.greeting',
		'autoresponder.lines',
	];

	const ownerFields = editable && (
		<>
			<AnswerChips
				answers={ answers }
				results={ template.messages?.result_tags }
				onInsert={ ( tag ) => insert( tag, ownerKeys ) }
			/>
			<MessageField
				id="srfm-template-email-subject"
				label={ __( 'Subject', 'sureforms' ) }
				value={ values.email_subject }
				onChange={ ( value ) => setValue( 'email_subject', value ) }
				warning={ subjectWarning( values.email_subject, answers ) }
				field={ fieldProps( 'email_subject' ) }
			/>
			<MessageField
				id="srfm-template-email-intro"
				label={ __( 'Intro', 'sureforms' ) }
				value={ values.email_intro }
				onChange={ ( value ) => setValue( 'email_intro', value ) }
				multiline
				rows={ 2 }
				help={
					allData
						? __( 'All answers are listed below the intro.', 'sureforms' )
						: __(
							'Only the intro is sent. Every answer stays in Entries.',
							'sureforms'
						  )
				}
				field={ fieldProps( 'email_intro' ) }
			/>
			<Switch
				label={ __( 'Include all answers', 'sureforms' ) }
				checked={ values.email_all_data }
				onChange={ ( value ) => setValue( 'email_all_data', value ) }
			/>
		</>
	);

	const visitorFields = editable && isSent && (
		<>
			<AnswerChips
				answers={ answers }
				results={ template.messages?.result_tags }
				onInsert={ ( tag ) => insert( tag, visitorKeys ) }
			/>
			<MessageField
				id="srfm-template-autoresponder-subject"
				label={ __( 'Subject', 'sureforms' ) }
				value={ values[ 'autoresponder.subject' ] }
				onChange={ ( value ) =>
					setValue( 'autoresponder.subject', value )
				}
				warning={ subjectWarning(
					values[ 'autoresponder.subject' ],
					answers
				) }
				field={ fieldProps( 'autoresponder.subject' ) }
			/>
			<MessageField
				id="srfm-template-autoresponder-greeting"
				label={ __( 'Greeting', 'sureforms' ) }
				value={ values[ 'autoresponder.greeting' ] }
				onChange={ ( value ) =>
					setValue( 'autoresponder.greeting', value )
				}
				field={ fieldProps( 'autoresponder.greeting' ) }
			/>
			<MessageField
				id="srfm-template-autoresponder-lines"
				label={ __( 'Message', 'sureforms' ) }
				value={ values[ 'autoresponder.lines' ] }
				onChange={ ( value ) =>
					setValue( 'autoresponder.lines', value )
				}
				multiline
				rows={ 4 }
				help={ __(
					'Leave a blank line between paragraphs.',
					'sureforms'
				) }
				field={ fieldProps( 'autoresponder.lines' ) }
			/>
		</>
	);

	const sendToggle = editable && ! held && (
		<Switch
			label={ __( 'Send a confirmation email', 'sureforms' ) }
			checked={ isSent }
			onChange={ ( value ) => setValue( 'autoresponder.enabled', value ) }
		/>
	);

	return (
		<div className="flex flex-col gap-4 w-full max-w-[640px] mx-auto">
			<EmailCard
				id="srfm-template-email-owner"
				look={ look }
				from={ context.siteTitle }
				to={ context.adminEmail }
				note={ __( 'New entry received', 'sureforms' ) }
				fields={ ownerFields }
				reset={
					editable &&
					isChanged( ownerResetKeys ) && (
						<ResetLink onReset={ () => reset( ownerResetKeys ) } />
					)
				}
				tag={ __( 'Email notification', 'sureforms' ) }
				subject={ renderWithSamples( values.email_subject, context ) }
			>
				<Paragraphs text={ values.email_intro } context={ context } />
				{ allData && <AnswersTable answers={ answers } /> }
				{ autoresponder && (
					<p className="m-0 flex items-center gap-1.5 text-xs text-text-tertiary">
						<CornerUpLeft className="size-3.5" aria-hidden="true" />
						{ __( 'Reply goes straight to the visitor', 'sureforms' ) }
					</p>
				) }
			</EmailCard>

			{ autoresponder && (
				<EmailCard
					id="srfm-template-email-visitor"
					look={ look }
					from={ context.siteTitle }
					to={ visitorEmail }
					note={ __( 'Sent automatically', 'sureforms' ) }
					action={ sendToggle }
					fields={ visitorFields }
					reset={
						editable &&
						isSent &&
						isChanged( visitorKeys ) && (
							<ResetLink onReset={ () => reset( visitorKeys ) } />
						)
					}
					tag={ __( 'Auto-reply', 'sureforms' ) }
					subject={
						isSent &&
						renderWithSamples(
							values[ 'autoresponder.subject' ],
							context
						)
					}
				>
					{ isSent ? (
						<>
							<p className="m-0 text-text-primary">
								{ renderWithSamples(
									values[ 'autoresponder.greeting' ],
									context
								) }
							</p>
							<Paragraphs
								text={ values[ 'autoresponder.lines' ] }
								context={ context }
							/>
							<p className="m-0 text-text-primary">
								{ __( 'Thanks,', 'sureforms' ) }
								<br />
								{ context.siteTitle }
							</p>
						</>
					) : (
						<p className="m-0 flex items-center gap-2 text-text-secondary">
							<MailX
								className="size-4 shrink-0 text-icon-secondary"
								aria-hidden="true"
							/>
							{ held
								? __(
									'Off until spam protection is on, so bots cannot use this form to send email to anyone. Turn on spam protection to send it.',
									'sureforms'
								  )
								: __(
									"Visitors won't get an email after submitting this form.",
									'sureforms'
								  ) }
						</p>
					) }
				</EmailCard>
			) }
		</div>
	);
};
