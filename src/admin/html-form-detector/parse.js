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

/**
 * Resolve the label text for a given input element.
 *
 * Prefers an explicit `<label for="id">` association, then a wrapping
 * `<label>` ancestor, then `aria-label` / `placeholder` / `name` as
 * progressively weaker signals. Returns an empty string when nothing
 * usable can be derived — callers treat that as a low-confidence field.
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
		const legend = fieldset?.querySelector( ':scope > legend' );
		const groupLabel = legend?.textContent?.trim() || '';

		return {
			fieldType: 'multi-choice',
			label:
				resolveLabel( element, doc ) ||
				element.getAttribute( 'value' ) ||
				element.getAttribute( 'name' ) ||
				'Choice',
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

	const rawFields = [];

	formEl.querySelectorAll( 'input, textarea, select' ).forEach( ( element ) => {
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

	const overallConfidence =
		fields.length === 0
			? 'low'
			: fields.some( ( f ) => f.confidence === 'low' )
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
