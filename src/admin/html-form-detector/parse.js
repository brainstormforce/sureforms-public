/**
 * SureForms — HTML Form Detector — Parser.
 *
 * Deterministic, dependency-free parser that turns a raw HTML string from a
 * `core/html` block into a structured field schema suitable for handing to
 * the SureForms form-creation API.
 *
 * Hybrid plan: this parser handles the common shapes (text, email, url,
 * number, tel, password, textarea, select, checkbox, radio, file). Anything
 * it cannot confidently map is marked `confidence: 'low'` so the caller can
 * decide to fall back to the AI conversion path on the server.
 */

const TYPE_MAP = {
	text: 'input',
	email: 'email',
	url: 'url',
	tel: 'phone',
	number: 'number',
	password: 'input',
	search: 'input',
	file: 'upload',
	hidden: 'hidden',
};

// Skip the deep parse for forms whose input count exceeds this. The
// O(N^2) label/fieldset resolution would otherwise block the editor
// main thread on every keystroke inside the source block. A real
// contact / signup / quiz form clears 200 fields by a wide margin;
// anything past that is almost always a marketing-page export the
// AI middleware is better-suited to convert anyway.
const FORM_INPUT_SOFT_LIMIT = 200;

/**
 * Read the text content of a `<label>` element that appears immediately
 * before the given element in the same parent — separated only by
 * whitespace or `<br>` elements, never by other content. Captures the
 * common shape where a label is authored as a sibling rather than via
 * `for=` or by wrapping the input:
 *
 *     <label>How satisfied are you?</label><br>
 *     <input type="radio" name="rating" value="1"> 1
 *
 *     <label>What can we improve?</label>
 *     <textarea name="feedback"></textarea>
 *
 * The walk stops at the first non-whitespace text node or any element
 * that is not `<br>` or `<label>` — so an unrelated label further up
 * the tree never bleeds into the wrong field. Labels with an explicit
 * `for` attribute are skipped: those are already tied to a specific
 * field and only that field should claim them (handled separately in
 * `resolveLabel` via `querySelector('label[for="…"]')`).
 *
 * @param {Element} element
 * @return {string} Trimmed label text, or '' when none applies.
 */
function readPrecedingLabelText( element ) {
	let cursor = element.previousSibling;
	while ( cursor ) {
		if ( cursor.nodeType === 1 /* ELEMENT_NODE */ ) {
			if ( cursor.tagName === 'LABEL' ) {
				if ( cursor.getAttribute( 'for' ) ) {
					return '';
				}
				return ( cursor.textContent || '' ).trim();
			}
			if ( cursor.tagName !== 'BR' ) {
				return '';
			}
		} else if ( cursor.nodeType === 3 /* TEXT_NODE */ ) {
			if ( ( cursor.textContent || '' ).trim() !== '' ) {
				return '';
			}
		}
		cursor = cursor.previousSibling;
	}
	return '';
}

/**
 * Read the visible text that immediately follows the given element in
 * its parent — the "<input> 5" / "<input> Yes" pattern where radio
 * options are labelled by adjacent text nodes rather than `<label>`
 * elements. Stops at the next element so we never pull in a
 * neighbouring input's label.
 *
 * @param {Element} element
 * @return {string} Trimmed trailing text, or '' when none applies.
 */
function readTrailingTextLabel( element ) {
	let cursor = element.nextSibling;
	let text = '';
	while ( cursor && cursor.nodeType !== 1 ) {
		if ( cursor.nodeType === 3 ) {
			text += cursor.textContent || '';
		}
		cursor = cursor.nextSibling;
	}
	return text.trim();
}

/**
 * Resolve the label text for a given input element.
 *
 * Prefers an explicit `<label for="id">` association, then a wrapping
 * `<label>` ancestor, then a preceding free-floating `<label>` sibling,
 * then `aria-label` / `placeholder` / `name` as progressively weaker
 * signals. Returns an empty string when nothing usable can be derived
 * — callers treat that as a low-confidence field.
 *
 * Note: this resolver is intentionally NOT used for radio per-option
 * labels — those go through `resolveRadioOptionLabel` which prefers
 * adjacent text and refuses to fall back to `name` (the group key).
 *
 * @param {Element}  element The input/textarea/select element.
 * @param {Document} doc     The owner document (for `getElementById`).
 * @return {string} Best-effort human label, empty string when none.
 */
function resolveLabel( element, doc ) {
	const id = element.getAttribute( 'id' );
	if ( id ) {
		const explicit = doc.querySelector( `label[for="${ window.CSS.escape( id ) }"]` );
		if ( explicit?.textContent ) {
			return explicit.textContent.trim();
		}
	}

	const wrappingLabel = element.closest( 'label' );
	if ( wrappingLabel?.textContent ) {
		return wrappingLabel.textContent.trim();
	}

	const preceding = readPrecedingLabelText( element );
	if ( preceding ) {
		return preceding;
	}

	const aria = element.getAttribute( 'aria-label' );
	if ( aria ) {
		return aria.trim();
	}

	const placeholder = element.getAttribute( 'placeholder' );
	if ( placeholder ) {
		return placeholder.trim();
	}

	const name = element.getAttribute( 'name' );
	if ( name ) {
		// Convert "first_name" or "first-name" → "First Name" as a last resort.
		return name
			.replace( /[_-]+/g, ' ' )
			.replace( /\b\w/g, ( c ) => c.toUpperCase() )
			.trim();
	}

	return '';
}

/**
 * Resolve the per-option label for a radio input. Distinct priority
 * from `resolveLabel` because radios have a different DOM contract:
 *
 *  - The text that visually labels each option usually sits as a text
 *    node *after* the `<input>`, not before it — so trailing text wins
 *    over any other signal.
 *  - The `name` attribute is the group identifier shared by every
 *    option, so using it as a label fallback would yield N identical
 *    options. Never fall through to it here.
 *
 * The group's title (the question being asked) is resolved separately
 * in `parseInput` via `<fieldset><legend>` or `readPrecedingLabelText`.
 *
 * @param {Element}  element The radio input.
 * @param {Document} doc     The owner document.
 * @return {string} Trimmed option label, or '' when none applies.
 */
function resolveRadioOptionLabel( element, doc ) {
	const trailing = readTrailingTextLabel( element );
	if ( trailing ) {
		return trailing;
	}

	const id = element.getAttribute( 'id' );
	if ( id ) {
		const explicit = doc.querySelector( `label[for="${ window.CSS.escape( id ) }"]` );
		if ( explicit?.textContent ) {
			return explicit.textContent.trim();
		}
	}

	const wrappingLabel = element.closest( 'label' );
	if ( wrappingLabel?.textContent ) {
		return wrappingLabel.textContent.trim();
	}

	const value = element.getAttribute( 'value' );
	if ( value ) {
		return value.trim();
	}

	return '';
}

function parseInput( element, doc ) {
	const type = ( element.getAttribute( 'type' ) || 'text' ).toLowerCase();
	if ( type === 'submit' || type === 'button' || type === 'reset' || type === 'image' ) {
		return null;
	}

	if ( type === 'checkbox' ) {
		return {
			fieldType: 'gdpr',
			label: resolveLabel( element, doc ) || 'Checkbox',
			required: element.hasAttribute( 'required' ),
			confidence: 'medium',
		};
	}

	if ( type === 'radio' ) {
		// Two distinct labels live on a radio input and they must not be
		// conflated: the per-option label ("Vegan") sits on / next to the
		// `<input>` itself, while the group's human title ("Dietary
		// Preference") usually sits on a `<fieldset><legend>` wrapping
		// the whole group. We attach the group title to `_groupLabel`
		// so `collapseRadioGroups` can hoist it onto the merged
		// `multi-choice` field; the per-option label stays on `label`
		// and ends up as the option text. Conflating them — as an
		// earlier revision did — produced N options all labelled with
		// the legend instead of the actual option names.
		const fieldset = element.closest( 'fieldset' );
		// Tailwind / Bootstrap-style markup often wraps `<legend>` in a
		// styling `<div>` for layout reasons; `:scope > legend` misses
		// those cases. The first descendant `<legend>` is good enough —
		// nested fieldsets are rare in form payloads and would each
		// have their own `closest('fieldset')` resolution anyway.
		const legend = fieldset?.querySelector( 'legend' );
		// Group title fallback chain: explicit <legend>, then a
		// free-floating <label> sibling that sits above the first
		// radio (e.g. `<label>How satisfied are you?</label><br>
		// <input type="radio">…`). `readPrecedingLabelText` returns ''
		// for radios after the first one in a group — their preceding
		// sibling is the previous radio's trailing text, not a label —
		// so only the first radio actually contributes a group label,
		// which is exactly what `collapseRadioGroups` consumes.
		const groupLabel =
			legend?.textContent?.trim() ||
			readPrecedingLabelText( element ) ||
			'';

		return {
			fieldType: 'multi-choice',
			label: resolveRadioOptionLabel( element, doc ) || 'Choice',
			required: element.hasAttribute( 'required' ),
			singleSelection: true,
			confidence: 'medium',
			_groupName: element.getAttribute( 'name' ) || '',
			_optionValue: element.getAttribute( 'value' ) || '',
			_groupLabel: groupLabel,
		};
	}

	const mapped = TYPE_MAP[ type ];
	return {
		fieldType: mapped || 'input',
		label: resolveLabel( element, doc ) || 'Field',
		required: element.hasAttribute( 'required' ),
		defaultValue: element.getAttribute( 'value' ) || undefined,
		placeholder: element.getAttribute( 'placeholder' ) || undefined,
		confidence: mapped ? 'high' : 'low',
	};
}

function parseTextarea( element, doc ) {
	return {
		fieldType: 'textarea',
		label: resolveLabel( element, doc ) || 'Message',
		required: element.hasAttribute( 'required' ),
		placeholder: element.getAttribute( 'placeholder' ) || undefined,
		confidence: 'high',
	};
}

function parseSelect( element, doc ) {
	// Pull a placeholder out of an empty-value first `<option>` if one is
	// present (`<option value="">Choose one</option>`). That option is a
	// UI hint, not a real choice — promoting its text to `placeholder`
	// keeps it out of the `fieldOptions` list (where it would render as
	// a selectable item) AND lets SureForms surface it as the dropdown's
	// idle placeholder, which is the round-trip the source HTML implied.
	const optionEls = Array.from( element.querySelectorAll( 'option' ) );
	let placeholder;
	const meaningful = optionEls.filter( ( option, idx ) => {
		const value = option.getAttribute( 'value' );
		const text = option.textContent?.trim() || '';
		const isFirstEmpty = idx === 0 && ( value === null || value === '' );
		if ( isFirstEmpty && text ) {
			placeholder = text;
			return false;
		}
		return true;
	} );

	const options = meaningful
		.map( ( option ) => {
			const optionLabel = option.textContent?.trim() || option.getAttribute( 'value' ) || '';
			return optionLabel ? { label: optionLabel, optionTitle: optionLabel } : null;
		} )
		.filter( Boolean );

	return {
		fieldType: 'dropdown',
		label: resolveLabel( element, doc ) || 'Select',
		required: element.hasAttribute( 'required' ),
		fieldOptions: options,
		placeholder,
		confidence: options.length ? 'high' : 'low',
	};
}

/**
 * Collapse parsed radio entries by their HTML `name` attribute — the HTML
 * form spec models a radio group as N inputs sharing a name, but SureForms
 * expects a single multi-choice field with N options. Without this step the
 * generated form would end up with one field per radio option.
 *
 * @param {Array} fields Mixed list of parsed field descriptors.
 * @return {Array} List with radio groups merged into single multi-choice fields.
 */
function collapseRadioGroups( fields ) {
	const merged = [];
	const groups = new Map();

	for ( const field of fields ) {
		if ( field.fieldType !== 'multi-choice' || ! field._groupName ) {
			merged.push( field );
			continue;
		}

		if ( ! groups.has( field._groupName ) ) {
			// Prefer the legend text picked up in `parseInput` over the
			// raw `name` attribute. The legend usually reads as a proper
			// question ("Dietary Preference"); falling back to the name
			// gives the user something humanized rather than a slug.
			const placeholder = {
				fieldType: 'multi-choice',
				label:
					field._groupLabel ||
					field._groupName.replace( /[_-]+/g, ' ' ),
				required: field.required,
				singleSelection: true,
				fieldOptions: [],
				confidence: 'medium',
			};
			groups.set( field._groupName, placeholder );
			merged.push( placeholder );
		}

		const target = groups.get( field._groupName );
		const optionLabel = field.label || field._optionValue;
		if ( optionLabel ) {
			target.fieldOptions.push( {
				label: optionLabel,
				optionTitle: optionLabel,
			} );
		}
	}

	return merged;
}

/**
 * Parse the HTML inside a `core/html` block, returning the first
 * `<form>` found and a structured descriptor of its fields.
 *
 * Returns `null` when no `<form>` is present so callers can short-circuit
 * cheaply on the common case of HTML blocks that hold non-form markup.
 *
 * @param {string} html Raw HTML string from the block.
 * @return {object|null} Parsed form descriptor or null.
 */
export function parseFormHtml( html ) {
	if ( typeof html !== 'string' || ! /<form\b/i.test( html ) ) {
		return null;
	}

	const doc = new window.DOMParser().parseFromString( html, 'text/html' );
	const formEl = doc.querySelector( 'form' );
	if ( ! formEl ) {
		return null;
	}

	const formInputs = formEl.querySelectorAll( 'input, textarea, select' );

	// Bail out of the deep parse for pathologically large forms. Every
	// field walk runs `closest('fieldset')` (O(depth)) and a per-field
	// `label[for="..."]` document query (O(N) within the doc); on a
	// form with hundreds of inputs that is O(N^2) on the editor's main
	// thread, and the parser runs on every keystroke inside the source
	// `core/html` block. Return a single-field sentinel with
	// `confidence: 'low'` so the HOC still surfaces the Convert button
	// (it shows when `fields.length > 0`) AND the server routes the
	// conversion through the AI middleware, which has the raw HTML and
	// can take its time off the editor's main thread.
	if ( formInputs.length > FORM_INPUT_SOFT_LIMIT ) {
		return {
			fields: [
				{
					fieldType: 'input',
					label: 'Field',
					required: false,
					confidence: 'low',
				},
			],
			submitText: '',
			styling: {},
			confidence: 'low',
		};
	}

	const rawFields = [];

	formInputs.forEach( ( element ) => {
		if ( element.tagName === 'INPUT' ) {
			const parsed = parseInput( element, doc );
			if ( parsed ) {
				rawFields.push( parsed );
			}
		} else if ( element.tagName === 'TEXTAREA' ) {
			rawFields.push( parseTextarea( element, doc ) );
		} else if ( element.tagName === 'SELECT' ) {
			rawFields.push( parseSelect( element, doc ) );
		}
	} );

	const fields = collapseRadioGroups( rawFields );

	const submitEl =
		formEl.querySelector( 'button[type="submit"], input[type="submit"], button:not([type])' ) ||
		formEl.querySelector( 'input[type="submit"]' );
	const submitText =
		submitEl?.textContent?.trim() ||
		submitEl?.getAttribute( 'value' )?.trim() ||
		'';

	const styling = extractInlineStyling( formEl, submitEl );

	// Majority-low (rather than any-low) so a single uncommon field type
	// — e.g. one `<input type="color">` in an otherwise standard contact
	// form — does not flip the whole form to low and burn an AI roundtrip.
	// The AI fallback is expensive (network + tokens + middleware cost);
	// reserve it for forms where the local parser genuinely can't carry
	// most of the load.
	const lowCount = fields.filter( ( f ) => f.confidence === 'low' ).length;
	const overallConfidence =
		fields.length === 0
			? 'low'
			: lowCount * 2 > fields.length
				? 'low'
				: 'high';

	return {
		fields,
		submitText,
		styling,
		confidence: overallConfidence,
	};
}

/**
 * Extract whatever styling we can glean from inline `style="..."` attributes
 * on the form / submit button / first input. We deliberately do not touch
 * external stylesheets: the editor iframe rarely has the live theme CSS
 * applied to the `<form>` markup as it sits inside a `core/html` block, so
 * reading computed styles would surface defaults that misrepresent the
 * published page anyway. Inline styles are the only signal we can trust to
 * round-trip through the conversion.
 *
 * The returned object follows the `formMetaData` schema expected by the
 * Create_Form ability (`formStyling.*`, `instantForm.formBackgroundColor`).
 * Keys are omitted when the source form did not supply them — the endpoint
 * applies safe defaults on top.
 *
 * @param {HTMLFormElement|null} formEl   The source `<form>` element.
 * @param {Element|null}         submitEl The submit button / input, if any.
 * @return {Object} Best-effort styling descriptor.
 */
function extractInlineStyling( formEl, submitEl ) {
	const styling = {};
	if ( ! formEl ) {
		return styling;
	}

	const formStyle = formEl.style || {};
	if ( formStyle.backgroundColor ) {
		const hex = normalizeColor( formStyle.backgroundColor );
		if ( hex ) {
			styling.formBackgroundColor = hex;
		}
	}
	if ( formStyle.color ) {
		const hex = normalizeColor( formStyle.color );
		if ( hex ) {
			styling.textColor = hex;
		}
	}

	// Box-model hints — these only matter when the form has a distinct
	// "card" look in the source. We pass them through verbatim so the
	// endpoint can decide whether to emit them as custom CSS.
	if ( formStyle.padding ) {
		styling.formPadding = formStyle.padding;
	}
	if ( formStyle.borderRadius ) {
		styling.formBorderRadius = formStyle.borderRadius;
	}
	if ( formStyle.border ) {
		styling.formBorder = formStyle.border;
	}

	if ( submitEl ) {
		const submitStyle = submitEl.style || {};
		if ( submitStyle.backgroundColor ) {
			const hex = normalizeColor( submitStyle.backgroundColor );
			if ( hex ) {
				styling.primaryColor = hex;
			}
		}
		if ( submitStyle.color ) {
			const hex = normalizeColor( submitStyle.color );
			if ( hex ) {
				styling.textColorOnPrimary = hex;
			}
		}
	}

	return styling;
}

/**
 * Convert any CSS color value the browser accepts (`rgb(...)`, `#abc`,
 * `red`, etc.) into a 6-digit hex. SureForms' `sanitize_hex_color()`
 * rejects anything else, so any color we cannot resolve is dropped.
 *
 * Uses the browser's own color parser by round-tripping through a temp
 * element's `style.color` (which normalizes any valid color to `rgb(...)`),
 * then converts the rgb to hex. Returns null on failure.
 *
 * @param {string} value Source color string.
 * @return {string|null} `#RRGGBB` hex, or null when the value cannot be parsed.
 */
function normalizeColor( value ) {
	if ( typeof value !== 'string' || ! value.trim() ) {
		return null;
	}

	const probe = window.document.createElement( 'div' );
	probe.style.color = '';
	probe.style.color = value;
	if ( ! probe.style.color ) {
		return null;
	}

	const match = probe.style.color.match(
		/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/
	);
	if ( ! match ) {
		return null;
	}

	const toHex = ( c ) => {
		const h = Number( c ).toString( 16 );
		return h.length === 1 ? '0' + h : h;
	};
	return '#' + toHex( match[ 1 ] ) + toHex( match[ 2 ] ) + toHex( match[ 3 ] );
}
