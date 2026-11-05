import { isValidElement } from '@wordpress/element';
import createDOMPurify from 'dompurify';

/**
 * Sanitizer policy for entry content that legitimately contains markup —
 * rich-text textarea values and Pro change-log messages shown in the entries
 * admin view.
 *
 * DOMPurify's DEFAULT allowlist is deliberately broad — verified against 3.3.3,
 * it permits `style`, `form`, `input`, `button`, the media elements and the
 * SVG/MathML namespaces. That is wrong for this screen for three reasons:
 *
 *  1. None of that is script execution, but an unauthenticated form submitter
 *     could still land document-wide CSS or a cross-origin `<form action>` in
 *     wp-admin (UI redressing, defacement, click-jacked POSTs). An ATTACKER
 *     payload reaches this sink with no server-side filtering at all: submitted
 *     as entity-encoded text it contains no literal `<`, so PHP treats it as
 *     inert text and every kses pass leaves it alone — then
 *     decodeHTMLEntities() turns it back into live markup on the client, just
 *     before this call. DOMPurify is the ONLY guard standing here.
 *  2. Dropping the SVG/MathML namespaces removes `<foreignObject>` — the exact
 *     namespace CVE-2026-18406 used — and with it the `<svg><style>` shape,
 *     which is the one construct able to slip past DOMPurify's own anti-mXSS
 *     guard (that guard only fires on elements with no element children).
 *  3. The media elements are a zero-click outbound request from the reviewing
 *     admin's session — see the comment on FORBID_TAGS below.
 *
 * Note the asymmetry with LEGITIMATE rich text, which travels a different route:
 * it arrives as real markup and is filtered by Helper::sanitize_textarea(). That
 * function does NOT strip style attributes — disable_style_attr_parsing() returns
 * an empty allowlist, and WordPress's safecss_filter_attr() early-returns the CSS
 * unchanged when the allowlist is empty (kses.php), a behaviour pinned by
 * tests/unit/inc/test-helper.php. Stored rich text therefore legitimately carries
 * `style="color:…"`, `text-align` and `direction`, so `style` must be filtered
 * per-property here rather than forbidden outright.
 *
 * @since 2.12.3
 */
export const RICH_TEXT_SANITIZE_CONFIG = {
	USE_PROFILES: { html: true },
	FORBID_TAGS: [
		'style',
		'form',
		'input',
		'button',
		'select',
		'textarea',
		// Allowing media would let an UNAUTHENTICATED submitter force the
		// reviewing admin's browser into an attacker-controlled GET on entry
		// view: IP / UA / Accept-Language fingerprint exfiltration, read-receipt
		// confirmation, and same-origin GET CSRF against any nonce-less
		// admin.php?action= handler.
		//
		// The cost is not quite zero, so it is a deliberate trade rather than a
		// free win. The Quill toolbar (assets/js/unminified/blocks/textarea.js)
		// has no image/video/audio button, but the editor registers no `formats`
		// allowlist either, so the image blot stays live and PASTED image markup
		// can be stored — `wp_kses_post()` in Helper::sanitize_textarea() keeps
		// `<img src="https://…">`. An existing entry with a pasted image now
		// renders without it. Accepted: a submitter-triggered outbound request
		// from an admin session outweighs displaying pasted images.
		'img',
		'picture',
		'source',
		'video',
		'audio',
		'track',
		'object',
		'embed',
	],
	// `class` is forbidden for the same reason the CSS allowlist below exists:
	// this is a Tailwind admin app whose content glob covers all of src/, so every
	// utility used anywhere is compiled into this bundle. Without this, a stored
	// `class="fixed inset-0 z-[99999999]"` reproduces the exact full-viewport
	// overlay the style filtering is there to prevent. Rich text does not need it.
	// `srcset`/`poster`/`background` are belt-and-braces alongside the media tags
	// above, so a future widening of FORBID_TAGS cannot silently reopen the fetch.
	// `id`/`name` follow the same reasoning as `class`: the editor emits neither,
	// and DOMPurify's SANITIZE_DOM only blocks values that clobber a document or
	// form property — it happily keeps `id="wpbody"`, which duplicates a wp-admin
	// id and can hijack an aria-labelledby/label target or a getElementById lookup.
	FORBID_ATTR: [
		'action',
		'formaction',
		'class',
		'srcset',
		'poster',
		'background',
		'id',
		'name',
	],
	ALLOW_DATA_ATTR: false,
};

/**
 * CSS properties the rich-text editor legitimately emits.
 *
 * `style` is deliberately NOT in FORBID_ATTR: the editor registers Quill's
 * *style* attributors for alignment and direction (see
 * assets/js/unminified/blocks/textarea.js) and its colour/highlight formats are
 * style-based too, and `Helper::sanitize_textarea()` preserves them —
 * `disable_style_attr_parsing()` returns an empty allowlist, which makes
 * WordPress's safecss_filter_attr() SKIP filtering rather than strip it (pinned
 * by tests/unit/inc/test-helper.php). Forbidding `style` outright would silently
 * de-colour and un-align every rich-text entry already in the database.
 *
 * INVARIANT — only add a property here whose value grammar cannot reference a
 * `<url>` or an `<image>`. These four accept only `<color>` values and keywords,
 * which is what makes the value blocklist unnecessary. Adding `background`,
 * `background-image`, `list-style`, `content`, `border-image`, `cursor`, `mask`
 * or `filter` would turn this into a live external-fetch vector.
 *
 * That invariant is enforced by the USER AGENT's CSS value parser, not by this
 * file: a value the parser rejects never enters the CSSOM, so it cannot be
 * re-emitted. Verified in Chrome 150 — all four properties reject `url(…)`.
 * jsdom's `cssstyle` does NOT implement value validation for `text-align` or
 * `direction` and will happily store `text-align: url(…)`, so the unit tests
 * below pin PROPERTY filtering only. Widening this list is therefore a change
 * the test suite cannot catch; review it by hand.
 *
 * @since 2.12.3
 */
const ALLOWED_CSS_PROPERTIES = [
	'color',
	'background-color',
	'text-align',
	'direction',
];

/**
 * Keep only allowlisted CSS declarations on a node's `style` attribute.
 *
 * The attribute is rebuilt from the parsed CSSOM rather than filtered as a
 * string, so the browser's own CSS parser does the parsing. That removes three
 * classes of bug a hand-rolled `split( ';' )` filter has: declarations with no
 * colon are dropped instead of retained, quoted values and comments containing
 * `;` cannot truncate or smuggle, and CSS escapes are already resolved. Because
 * the attribute is always rewritten from what the parser understood, anything it
 * did not understand disappears.
 *
 * Runs as a DOMPurify hook so it operates on the live node during sanitization —
 * never by re-parsing the serialized output, which is the mistake that caused
 * CVE-2026-18406 in the first place.
 *
 * @since 2.12.3
 *
 * @param {Element} node Node being sanitized.
 * @return {void}
 */
const filterStyleAttribute = ( node ) => {
	if ( ! node?.style || ! node.hasAttribute?.( 'style' ) ) {
		return;
	}

	const declarations = Array.from( node.style )
		.filter( ( property ) => ALLOWED_CSS_PROPERTIES.includes( property ) )
		.map(
			( property ) =>
				`${ property }: ${ node.style.getPropertyValue( property ) }`
		);

	if ( declarations.length ) {
		node.setAttribute( 'style', declarations.join( '; ' ) );
	} else {
		node.removeAttribute( 'style' );
	}
};

/**
 * Private DOMPurify instance.
 *
 * `import domPurify from 'dompurify'` yields a module-scoped singleton, so
 * calling addHook() on it would silently apply this entry-specific CSS policy to
 * every other dompurify consumer sharing the instance. Bracketing with
 * removeHook() is not an alternative: it pops the LAST registered hook, so it
 * would clobber a hook registered after ours.
 */
const purifier = createDOMPurify( window );

purifier.addHook( 'afterSanitizeAttributes', filterStyleAttribute );

/**
 * Blocks whose value can legitimately contain markup.
 *
 * `srfm-textarea` holds SUBMITTER-authored rich text (Quill). `srfm-payment`
 * holds PLUGIN-authored markup: the `srfm_entry_value` filter in
 * `inc/payments/stripe/payments-settings.php` replaces the stored value — a
 * numeric payment row ID, `intval()`-gated — with a "View Payment" anchor. The
 * two are different trust levels and get different policies below.
 */
const RICH_TEXT_BLOCK = 'srfm-textarea';
const SERVER_MARKUP_BLOCKS = [ 'srfm-payment' ];

/**
 * Matches something that plausibly opens an HTML tag, comment or close tag.
 *
 * This is a heuristic, NOT a parser: `if a<b then c>d` still matches it, because
 * distinguishing that from real markup requires actually parsing HTML. It exists
 * only to keep a value without markup off the HTML path. The real discriminators
 * are `block_name` and, for a textarea, `is_rich_text`.
 */
const HTML_TAG_PATTERN = /<[a-z!/][^<>]*>/i;

/**
 * Should this field's value be rendered as HTML rather than as escaped text?
 *
 * The discriminator is the server's `block_name`, not the shape of the value.
 * A textarea is rendered as HTML only when the server reports it uses the
 * rich-text editor (`is_rich_text`, read from the saved form in
 * Rest_Api::get_entry_details()). A plain textarea's answer is text whatever it
 * contains: `<i>italic</i>` typed into one shows as those characters.
 * Content-sniffing every value with a loose `/<[^>]+>/` test silently destroys
 * plain-text answers: `<not a tag>` renders as an empty cell and `x<y>z` as
 * `xz`, because DOMPurify quite correctly removes what looks like an unknown
 * element. Restricting the HTML path to the two blocks that can actually hold
 * markup keeps every other field on the escaping text path.
 *
 * NOTE — `block_name` is a data-integrity discriminator, NOT a trust boundary.
 * It is derived from the submitted POST key (`inc/helper.php`
 * `get_block_name_from_field()` takes the first two dash-segments of any key
 * containing `-lbl-`), so a submitter can choose it. That is fine: everything on
 * the HTML path is sanitized regardless. Do not relax the sanitizer policy on
 * the grounds that "only real textareas reach it".
 *
 * @since 2.12.3
 *
 * @param {Object} field Field object with `block_name` and `value`.
 * @return {boolean} True when the value must be sanitized and inserted as HTML.
 */
export const isRichTextField = ( field ) =>
	( ( field?.block_name === RICH_TEXT_BLOCK && true === field?.is_rich_text ) ||
		SERVER_MARKUP_BLOCKS.includes( field?.block_name ) ) &&
	typeof field.value === 'string' &&
	HTML_TAG_PATTERN.test( field.value );

/**
 * Sanitize a string that may legitimately contain markup.
 *
 * CVE-2026-18406: the result MUST be inserted directly into the DOM (i.e. via
 * `dangerouslySetInnerHTML`). Never pass it to a second HTML parser such as
 * `html-react-parser` — the re-parse decodes entities DOMPurify deliberately
 * left inert and resurrects the payload. Sanitizer-straight-to-DOM is
 * DOMPurify's documented, mXSS-safe contract.
 *
 * Fails CLOSED: DOMPurify returns its input verbatim when `isSupported` is
 * false, and that string would otherwise reach `dangerouslySetInnerHTML`
 * unescaped. An empty cell is the correct degradation.
 *
 * The policy is deliberately NOT a parameter. An overridable config on a function
 * named "sanitize" is a footgun: because hooks are instance-global, passing any
 * config object replaces the whole strict policy while KEEPING the CSS hook, so
 * `sanitizeEntryValue( value, { ADD_ATTR: [ 'target' ] } )` would quietly return
 * DOMPurify's full default allowlist — media, `class` and all. A caller that needs
 * a different policy should change RICH_TEXT_SANITIZE_CONFIG and the tests that
 * pin it.
 *
 * @since 2.12.3
 *
 * @param {string} value Raw value.
 * @return {string} Sanitized HTML, safe for direct DOM insertion only.
 */
export const sanitizeEntryValue = ( value ) =>
	purifier.isSupported
		? purifier.sanitize( value, RICH_TEXT_SANITIZE_CONFIG )
		: '';

/**
 * Sanitize a field value for rendering as HTML.
 *
 * There is deliberately ONE policy, applied to every block. It is tempting to
 * relax it for SERVER_MARKUP_BLOCKS on the grounds that the payment anchor is
 * plugin-authored — do not. `block_name` is derived from the submitted POST key
 * and `process_form_fields()` (`inc/form-submit.php`) accepts any key containing
 * `-lbl-` without checking it against the form's real fields, so an
 * unauthenticated submitter can post `srfm-payment-1-lbl-x` with an arbitrary
 * value. The payment filter leaves non-numeric values alone, so that payload
 * arrives here labelled `srfm-payment`. Keying a weaker policy off the label
 * would hand a submitter whatever that policy allows.
 *
 * The visible cost is that the payment link loses its `class` and `target`, so
 * it renders as a default-styled link that opens in the same tab. Restoring
 * those needs the PHP filter to return structured data instead of markup.
 *
 * @since 2.12.3
 *
 * @param {Object} field Field object with `value`.
 * @return {string} Sanitized HTML, safe for direct DOM insertion only.
 */
export const sanitizeFieldValue = ( field ) =>
	typeof field?.value === 'string' ? sanitizeEntryValue( field.value ) : '';

/**
 * Re-attach `block_name` across the Pro `render-pro-fields` filter boundary.
 *
 * Pro's formatter (`sureforms-pro/src/admin/entries/components/EntryDataSection.js`)
 * returns a bare `{ label, value }` and drops `block_name`, which is the only
 * discriminator isRichTextField() has. Without this graft `field.block_name` is
 * undefined on every Pro site and all rich text falls through to the escaping text
 * branch, rendering as literal tags.
 *
 * `formatted` wins on conflict, so Pro can never be widened into a block_name it
 * did not itself return. Arrays (repeater rows), React elements and null pass
 * through untouched — repeater rows deliberately do NOT inherit the parent's
 * `srfm-repeater`, since each row's cells are separate fields whose own block
 * names Pro does not currently carry.
 *
 * `is_rich_text` is taken from the original field only, never from `formatted`:
 * it is the server's reading of the saved form, and nothing a formatter returns
 * can turn a plain textarea's value into HTML.
 *
 * Long term Pro should spread `...field` in its formatter and this can go away.
 *
 * @since 2.12.3
 * @since 2.13.1 Also carries `is_rich_text`.
 *
 * @param {Object} field     The original field, which still has `block_name`.
 * @param {*}      formatted Whatever the Pro filter returned.
 * @return {*} `formatted`, with `block_name` and `is_rich_text` restored when it is a plain object.
 */
export const withBlockName = ( field, formatted ) =>
	formatted &&
	typeof formatted === 'object' &&
	! Array.isArray( formatted ) &&
	! isValidElement( formatted )
		? {
			block_name: field?.block_name,
			...formatted,
			is_rich_text: true === field?.is_rich_text,
		  }
		: formatted;

/**
 * Sanitize an entry log message.
 *
 * Log messages are NOT plain text. Most are status strings, but the Pro
 * change-log writers deliberately embed markup: `inc/extensions/hooks.php` emits
 * `<strong>Label: </strong> "" &#8594; new` when a field is added and
 * `<strong>Label: </strong> <del>old</del> &#8594; new` when one is modified.
 * Rendering those as text would show the raw tags to the user.
 *
 * The message is NOT entity-decoded first, deliberately. Messages are written as
 * a markup template around `esc_html()`-escaped values, so decoding would turn
 * an escaped value back into live markup that the sanitizer then removes —
 * silently blanking it. A logged old value of `&lt;img src=x&gt;` renders as the
 * literal text `<img src=x>` without a decode, and as nothing at all with one,
 * which misreports what changed. `innerHTML` performs the single correct decode
 * on insertion, so `&#039;` and `&#8594;` still display as `'` and `→`.
 *
 * NOTE — because the sanitizer is the only control here, `esc_html()` in the log
 * writers is now belt-and-braces rather than load-bearing. Two Pro writers embed
 * submitter-influenced values with no escaping at all
 * (`inc/extensions/hooks.php` and `inc/business/repeater/init.php`); they are
 * inert under this policy but should be escaped regardless.
 *
 * Non-strings return '' rather than rendering `"null"` or a JSON blob whose `<`
 * characters would be parsed as markup.
 *
 * @since 2.12.3
 *
 * @param {*} message Raw log message from the API.
 * @return {string} Sanitized HTML, safe for direct DOM insertion only.
 */
export const sanitizeLogMessage = ( message ) =>
	typeof message === 'string' ? sanitizeEntryValue( message ) : '';
