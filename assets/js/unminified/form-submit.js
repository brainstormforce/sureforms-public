/* eslint-disable no-undef */
import {
	fieldValidation,
	initializeInlineFieldValidation,
	handleScrollAndFocusOnError,
	handleCaptchaValidation,
	srfmFields,
} from './validation';
import { handleFormPayment } from './payment';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

// Tracks the form that initiated the reCAPTCHA v3 verification.
// This prevents multiple forms on the same page from all submitting
// when only one form's submit button was clicked.
let pendingRecaptchaForm = null;

/**
 * Event listener for external validation initialization.
 *
 * Allows external code to trigger form validation initialization by dispatching
 * the 'srfm_initialize_validation' custom event. When triggered, this will
 * initialize inline field validation for specified fields or all fields if none specified.
 *
 * @param {CustomEvent} event                 - The custom event object
 * @param {Object}      event.detail          - Event details
 * @param {Array}       [event.detail.fields] - Optional array of field types to initialize validation for
 */
document.addEventListener( 'srfm_initialize_validation', ( event ) => {
	const fields = event?.detail?.fields;

	if ( ! fields || ! Array.isArray( fields ) ) {
		// If no fields specified, initialize validation for all field types
		initializeInlineFieldValidation();
		return;
	}

	// Filter the fields array to only include valid field types
	const validFields = fields.filter( ( field ) =>
		srfmFields().includes( field )
	);

	// Initialize validation with the filtered fields
	initializeInlineFieldValidation( validFields );
} );

// Counter for assigning unique instance IDs to forms.
// Declared at module scope so it persists across multiple initializeFormHandlers() calls.
let srfmFormInstanceCounter = 0;

/**
 * Initializes form handlers for all forms with the class `.srfm-form`.
 */
function initializeFormHandlers() {
	initializeInlineFieldValidation();

	const forms = Array.from( document.querySelectorAll( '.srfm-form' ) );

	for ( const form of forms ) {
		// Assign a unique instance ID to each form to support multiple embeds of the same form on one page.
		if ( ! form.hasAttribute( 'data-srfm-instance' ) ) {
			form.setAttribute(
				'data-srfm-instance',
				String( ++srfmFormInstanceCounter )
			);
		}

		// Add the event before the form initialization to ensure that the all third party libraries are loaded and initialized.
		// Dispatch a custom event *before* the form is submitted.
		document.dispatchEvent(
			new CustomEvent( 'srfm_form_before_submission', {
				detail: { form },
			} )
		);

		const {
			formId,
			submitType,
			successUrl,
			ajaxUrl,
			submitToken,
			loader,
			successContainer,
			successElement,
			recaptchaType,
			afterSubmission,
			captchaErrorElement,
			hCaptchaDiv,
			turnstileDiv,
			hasLoginBlock,
		} = extractFormAttributesAndElements( form );

		const hasCaptcha =
			'v2-checkbox' === recaptchaType ||
			!! hCaptchaDiv ||
			!! turnstileDiv;

		// Add click listener for v3-reCAPTCHA form tracking.
		// Uses capture phase to run before Google's reCAPTCHA click handler.
		// Only add once per form to prevent duplicate handlers when initializeFormHandlers is called multiple times.
		if (
			recaptchaType === 'v3-reCAPTCHA' &&
			! form.hasAttribute( 'data-srfm-v3-click-handler' )
		) {
			form.setAttribute( 'data-srfm-v3-click-handler', 'true' );
			form.addEventListener(
				'click',
				function ( e ) {
					const submitBtn = e.target.closest(
						'#srfm-submit-btn, .srfm-custom-button'
					);
					if (
						submitBtn &&
						submitBtn.classList.contains( 'g-recaptcha' )
					) {
						pendingRecaptchaForm = form;
					}
				},
				true
			);
		}

		form.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();

			const formTarget = e.target;
			if ( formTarget?.tagName === 'FORM' ) {
				// If the submit button is hidden, prevent form submission.
				const getTheContainer = formTarget?.closest(
					'.srfm-form-container'
				);
				const hasHiddenClass = getTheContainer?.classList.contains(
					'srfm-submit-button-hidden'
				);

				const isCustomButton = formTarget?.querySelector(
					'button.srfm-custom-button'
				);

				// Check if the custom button is hidden by conditional logic.
				const isCustomButtonHidden = isCustomButton
					?.closest( '.srfm-custom-button-ctn' )
					?.classList.contains( 'hide-element' );

				if ( hasHiddenClass && ( ! isCustomButton || isCustomButtonHidden ) ) {
					console.warn(
						'Form submission is disabled because the submit button is hidden.'
					);
					return;
				}
			}

			/**
			 * Check for the form login completion status.
			 * If the login is not completed, dispatch a custom event to handle the login request.
			 * This allows for a two-step process where the login is handled separately before form submission.
			 */
			if ( hasLoginBlock && ! form.__loginSuccess ) {
				const loginEvent = new CustomEvent( 'srfm_login_request', {
					cancelable: true,
					detail: { form },
				} );
				form.dispatchEvent( loginEvent );
				return;
			}

			/**
			 * Filter to handle custom form submission interceptors (e.g., lost password, reset password).
			 * Pro plugins can use this filter to intercept form submission and handle custom logic.
			 *
			 * @since 2.5.0
			 * hook srfm.handleCustomFormSubmission
			 *
			 * @param {boolean} shouldContinue Whether to continue with normal form submission.
			 * @param {Object}  args           Arguments object.
			 * @param {Element} args.form      The form DOM element.
			 * @return {boolean} Return false to stop form submission.
			 */
			const shouldContinueSubmission = applyFilters(
				'srfm.handleCustomFormSubmission',
				true,
				{ form }
			);

			if ( ! shouldContinueSubmission ) {
				return;
			}

			handleFormSubmission(
				form,
				formId,
				ajaxUrl,
				submitToken,
				loader,
				successUrl,
				successContainer,
				successElement,
				submitType,
				afterSubmission,
				hasCaptcha ? recaptchaType : undefined,
				hasCaptcha ? hCaptchaDiv : undefined,
				hasCaptcha ? turnstileDiv : undefined,
				hasCaptcha ? captchaErrorElement : undefined
			);
			// Set the login completion status to true after form submission.
			form.__loginSuccess = true;
		} );

		// Add the event after the form initialization to ensure that all third party libraries are loaded and initialized.
		// Dispatch a custom event *before* the form is submitted.
		document.dispatchEvent(
			new CustomEvent( 'srfm_form_after_initialization', {
				detail: { form },
			} )
		);
	}

	// Record a cache-safe view (impression) for each form once it becomes visible.
	trackFormViews( forms );
}

/**
 * Cache-safe form view (impression) tracking.
 *
 * Fires a single beacon per form the first time it scrolls into view, so views are
 * counted even on fully page-cached pages (the beacon runs client-side). Non-JS
 * crawlers never execute this, so they are excluded naturally.
 *
 * `window.srfm_view_beacon.enabled` only reports whether counting has ever started,
 * because that value is printed into cacheable HTML. Per-request exclusions
 * (privileged users, builder/Instant Form previews) are enforced server-side in
 * track_view(), which cannot go stale in a page cache.
 *
 * @param {Array} forms - The `.srfm-form` elements collected during initialization.
 */

/**
 * One observer for the whole document, created lazily on first use.
 *
 * Not one per form: initializeFormHandlers() re-runs on `elementor/popup/show` and on
 * every `srfm_form_initialize` event, and a per-form observer was created on each of
 * those passes for any form that had not yet been seen. Opening a popup twenty times
 * left twenty live observers on a below-the-fold form, each retaining the element and
 * its closure even after the node was removed from the DOM.
 */
let srfmViewObserver = null;

/**
 * Form IDs already counted in this page load.
 *
 * Keyed on the form ID rather than the DOM element because one page can embed the
 * same form more than once (the reason the instance counter above exists). Two
 * elements meant two beacons for a single page view, which inflated views and halved
 * that form's conversion rate. It also survives a node being replaced and re-inited,
 * which the previous `data-srfm-view-tracked` attribute did not.
 */
const srfmCountedFormIds = new Set();

function srfmSendViewBeacon( formId, viewToken ) {
	const beaconUrl = window.srfm_view_beacon?.url;

	// Bail before anything is marked counted, so a missing URL does not silently
	// burn the view the way an unguarded request would.
	if ( ! beaconUrl ) {
		return;
	}

	srfmCountedFormIds.add( formId );

	// Forward the previewed page's own live_mode so the server can exclude form-
	// builder / Instant Form live previews. should_track() runs on this separate
	// beacon request and cannot see the original page's query string, so the signal
	// has to travel on the beacon itself. A visitor spoofing this only under-counts
	// their own view, so it needs no stronger guard.
	const srfmLivePreview = new URLSearchParams( window.location.search ).has(
		'live_mode'
	)
		? '1'
		: '0';

	// Plain fetch(), not wp.apiFetch(): `srfm-form-submit` does not declare
	// `wp-api-fetch` as a dependency, so `window.wp.apiFetch` is undefined and calling
	// it throws a synchronous TypeError that no `.catch()` can intercept. The endpoint
	// authenticates with the HMAC token below, so none of apiFetch's middleware
	// (root-URL resolution, nonce injection) is needed — the same reasoning that moved
	// the two submission calls in this file to fetch().
	//
	// keepalive: the beacon commonly fires as the visitor is navigating away; without
	// it the browser cancels the request in flight and the view is lost.
	fetch( beaconUrl, {
		method: 'POST',
		keepalive: true,
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Submit-Token': viewToken,
		},
		body: JSON.stringify( { form_id: formId, live_preview: srfmLivePreview } ),
	} ).catch( () => {
		// Beacon is best-effort; never disrupt the page on failure.
	} );
}

function trackFormViews( forms ) {
	// Has counting ever been switched on for this site?
	if ( '1' !== String( window.srfm_view_beacon?.enabled ?? '0' ) ) {
		return;
	}

	if ( typeof window.IntersectionObserver === 'undefined' ) {
		return;
	}

	if ( ! srfmViewObserver ) {
		srfmViewObserver = new window.IntersectionObserver(
			( entries ) => {
				for ( const entry of entries ) {
					if ( ! entry.isIntersecting ) {
						continue;
					}

					// threshold 0 alone reports a zero-area target as intersecting, so a
					// form in a collapsed accordion or an inactive tab panel counted as
					// "appeared on screen". Require real painted area instead.
					const rect = entry.intersectionRect;
					if ( ! rect || rect.width <= 0 || rect.height <= 0 ) {
						continue;
					}

					// Stop watching this element regardless of the outcome below.
					srfmViewObserver.unobserve( entry.target );

					const formId = Number(
						entry.target.getAttribute( 'form-id' )
					);
					const viewToken =
						entry.target.getAttribute( 'data-view-token' );

					if ( srfmCountedFormIds.has( formId ) || ! viewToken ) {
						continue;
					}

					srfmSendViewBeacon( formId, viewToken );
				}
			},
			// threshold 0 → count as soon as any part of the form is visible,
			// so tall forms on short viewports are not missed.
			{ threshold: 0 }
		);
	}

	for ( const form of forms ) {
		// Already watched, or already counted via another embed of the same form.
		if ( form.hasAttribute( 'data-srfm-view-observed' ) ) {
			continue;
		}

		// Number(), not parseInt(): parseInt( '42abc' ) is 42, so a malformed
		// attribute passed the guard and was sent as a plausible-looking ID.
		const numericFormId = Number( form.getAttribute( 'form-id' ) );
		if (
			! Number.isInteger( numericFormId ) ||
			numericFormId < 1 ||
			! form.getAttribute( 'data-view-token' )
		) {
			continue;
		}

		form.setAttribute( 'data-srfm-view-observed', '1' );
		srfmViewObserver.observe( form );
	}
}

document.addEventListener( 'DOMContentLoaded', function () {
	// Initialize the form submission script.
	initializeFormHandlers();
} );

/**
 * Prepares and formats address data from a form for submission.
 *
 * This function extracts address information from blocks within the form that have the class `.srfm-address-block`.
 * It builds a compact address string for each block in the format: "address_1, address_2, city, state, country".
 * If the address is not empty, it is added to the submission data. This is particularly useful for third-party
 * integrations like third-party services, which can utilize compact addresses such as permanent address, temporary address, etc.
 * The address will be structured as field 1, field 2, and so on.
 *
 * @param {HTMLFormElement} form - The form element containing address blocks.
 * @return {Object|null} - An object containing address data, or null if no addresses are found.
 */
function prepareAddressesData( form ) {
	const addressBlocks = form.querySelectorAll( '.srfm-address-block' );
	if ( ! addressBlocks ) {
		return null;
	}

	const addresses = {};

	// Iterate over each address block to extract and format address data.
	addressBlocks.forEach( ( block ) => {
		const addressSlug = block.getAttribute( 'data-slug' );

		// Skip processing if the block does not have a `data-slug` attribute.
		if ( ! addressSlug ) {
			return;
		}

		// Select all input fields within the block (text inputs and dropdowns).
		const addressFields = block.querySelectorAll(
			'.srfm-input-input, .srfm-dropdown-input'
		);

		// Build a compact address string in the format: "address_1, address_2, city, state, country".
		const compactAddress = Array.from( addressFields )
			.map( ( field ) => field?.value?.trim() )
			.filter( Boolean ) // Remove empty or undefined values.
			.join( ', ' );

		// Add the compact address to the addresses object using the slug as the key.
		addresses[ addressSlug ] = compactAddress;
	} );

	// Check if the addresses object is not empty.
	return Object.keys( addresses ).length > 0 ? addresses : null;
}

/**
 * Parse a fetch() Response into the shape the rest of this file expects.
 *
 * SureForms used to call `wp.apiFetch()` for the two REST requests this file
 * makes. That global only exists once a second script, `wp-api-fetch`, has
 * loaded and executed — and JS-combining/deferring "optimizer" plugins can
 * drop or reorder that dependency while still shipping this file, silently
 * breaking every submission with an opaque TypeError. Neither request this
 * file makes actually needs anything `wp.apiFetch` provides beyond URL
 * resolution (see the `submit_form_url` / `after_submit_url` comment in
 * frontend-assets.php): submit-form is authenticated by the `X-WP-Submit-Token`
 * header, not a nonce, and after-submission takes its nonce as an explicit
 * query argument. So a plain fetch() against the PHP-resolved REST URL is a
 * strict simplification, not a re-implementation — and native fetch() has no
 * script dependency of its own for an optimizer to drop.
 *
 * Mirrors wp.apiFetch's own contract: application-level failures (e.g. "form
 * full", a failed CAPTCHA) come back as an HTTP 200 with `success: false` in
 * the body and must be returned as-is for the caller's existing branch to
 * handle; only a non-2xx status (e.g. the 403 an invalid submit token gets)
 * is treated as a rejection, thrown as the parsed WP_Error body itself
 * (`{ code, message, data }`) so callers can read `e.message` / `e.code`
 * exactly as they could from a rejected wp.apiFetch() call.
 *
 * @param {Response} response A fetch() Response.
 * @return {Promise<Object|null>} The parsed JSON body.
 * @throws {Object|Error} The parsed error body on a non-2xx response, or the
 *                         original Error if the body wasn't valid JSON.
 */
/**
 * Client-side debug log for form submission failures.
 *
 * Ships to the server only while the "Enable Logs" setting is on, and only on a
 * failure. The server re-checks that setting on every request, so the flag here
 * is a hint that keeps us from posting needlessly -- it is baked into cached
 * HTML and can be a full cache TTL out of date.
 *
 * Deliberately not a window.fetch wrapper: patching a global is uninstallable
 * once another script patches on top, and captures every request on the page.
 * These are five explicit call sites instead.
 */
const srfmLog = {
	entries: [],

	isOn() {
		return !! window.srfm_submit?.logging_enabled;
	},

	add( entry ) {
		if ( ! this.isOn() ) {
			return;
		}
		// Bound the buffer. A page that fails repeatedly must not grow without limit.
		if ( this.entries.length < 10 ) {
			this.entries.push( entry );
		}
	},

	/**
	 * Send whatever has been buffered, then forget it.
	 *
	 * Uses fetch rather than navigator.sendBeacon: the token travels in a header
	 * and sendBeacon cannot set one. Beacon semantics are not needed anyway --
	 * the page is still alive after a failed submit.
	 *
	 * @param {HTMLFormElement} form Form the failure belongs to.
	 */
	flush( form ) {
		if ( ! this.isOn() || ! this.entries.length ) {
			return;
		}

		const url = window.srfm_submit?.log_error_url;
		// Read both off the form rather than stashing them in submitFormData: the
		// failures caught before the request goes out never run that function.
		const token = form?.getAttribute( 'data-submit-token' ) ?? '';
		const formId = Number( form?.getAttribute( 'form-id' ) ) || 0;

		if ( ! url ) {
			this.entries = [];
			return;
		}

		const body = JSON.stringify( {
			form_id: formId,
			entries: this.entries,
		} );

		this.entries = [];

		// Fire and forget. A logging failure must never affect the submission.
		fetch( url, {
			method: 'POST',
			keepalive: true,
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Submit-Token': token || '',
			},
			body,
		} ).catch( () => {} );
	},
};

async function parseRestResponse( response ) {
	let body = null;
	try {
		// Clone before reading: a Response body can only be consumed once, and on a
		// parse failure the raw text is the whole diagnosis -- a WAF block page, a
		// PHP fatal, or a stray warning printed ahead of the JSON. Without this the
		// log says "submission failed", which the admin already knew.
		const rawForLog = srfmLog.isOn() ? response.clone() : null;

		try {
			body = await response.json();
		} catch ( parseError ) {
			if ( rawForLog ) {
				const text = await rawForLog.text().catch( () => '' );
				srfmLog.add( {
					type: 'response',
					status: response.status,
					message: `Response was not JSON: ${ parseError?.message ?? '' }`,
					body: String( text ).slice( 0, 500 ),
				} );
			}
			throw parseError;
		}
	} catch ( parseError ) {
		if ( response.ok ) {
			return null;
		}
		throw parseError;
	}

	if ( ! response.ok ) {
		throw body;
	}

	return body;
}

async function submitFormData( form ) {
	const submitFormUrl = window.srfm_submit?.submit_form_url;
	if ( ! submitFormUrl ) {
		return {
			success: false,
			data: {
				message: __(
					'This form could not be submitted. Please refresh the page and try again.',
					'sureforms'
				),
				log_message:
					'SureForms: srfm_submit.submit_form_url is unavailable, so the submission was never sent.',
			},
		};
	}

	const formData = new FormData( form );
	const filteredFormData = new FormData();
	const submitToken = form.getAttribute( 'data-submit-token' );

	// Define keys to exclude from filtered form data
	const blockTheseKeys = [ 'srfm-email-confirm', 'srfm-password-confirm' ];

	// Iterate over each entry in formData
	for ( let [ key, value ] of formData.entries() ) {
		// Skip keys listed in blockTheseKeys array
		if ( blockTheseKeys.includes( key ) ) {
			continue;
		}

		if ( value !== '' ) {
			// Retrieve input element by key name and find closest `.srfm-block-single` parent
			const inputElement = form.querySelector( `[name="${ key }"]` );
			const parentBlock = inputElement?.closest( '.srfm-block-single' );

			// If parent has `.hide-element` class, reset value to empty string
			if ( parentBlock?.classList.contains( 'hide-element' ) ) {
				value = '';
			}

			/**
			 * Filters the field value during form submission.
			 *
			 * Allows modifying or clearing field values before they are submitted.
			 * Similar to how hidden fields are cleared, this filter enables custom handling
			 * for specific field types like repeater fields.
			 *
			 * @since 1.11.0
			 * hook srfm.filterFieldValue
			 *
			 * @param {*}       value           The current field value.
			 * @param {Object}  filterArgs      Arguments passed to the filter.
			 * @param {string}  filterArgs.key  The field key/name.
			 * @param {Element} filterArgs.form The form DOM element.
			 * @return {*} The filtered field value.
			 */
			value = applyFilters( 'srfm.filterFieldValue', value, {
				key,
				form,
			} );
		}

		/**
		 * Filters whether a field should be skipped during form submission.
		 *
		 * @since 1.11.0
		 * hook srfm.shouldSkipField
		 *
		 * @param {boolean} false            Default value indicating field should not be skipped.
		 * @param {Object}  filterArgs       Arguments passed to the filter.
		 * @param {string}  filterArgs.key   The field key/name.
		 * @param {*}       filterArgs.value The field value.
		 * @param {Element} filterArgs.form  The form DOM element.
		 * @return {boolean} Whether to skip the field.
		 */
		const shouldSkipField = applyFilters( 'srfm.shouldSkipField', false, {
			key,
			value,
			form,
		} );

		if ( shouldSkipField ) {
			continue;
		}

		// Append the (possibly modified) key-value pair to filteredFormData
		filteredFormData.append( key, value );
	}

	const addresses = prepareAddressesData( form );
	if ( addresses ) {
		filteredFormData.append(
			'srfm_addresses',
			JSON.stringify( addresses )
		);
	}

	// Allow pro plugins to add additional form data
	const additionalData = applyFilters(
		'srfm.prepareAdditionalFormData',
		{},
		form
	);
	Object.keys( additionalData ).forEach( ( key ) => {
		if (
			additionalData[ key ] !== null &&
			additionalData[ key ] !== undefined
		) {
			filteredFormData.append(
				key,
				JSON.stringify( additionalData[ key ] )
			);
		}
	} );

	const startedAt = performance.now();

	try {
		const response = await fetch( submitFormUrl, {
			method: 'POST',
			body: filteredFormData,
			headers: {
				'X-WP-Submit-Token': submitToken,
			},
		} );

		// Read status here, before parseRestResponse consumes the body. Once it
		// throws, the Response is gone and this is the only place the status,
		// content type and round-trip time still exist.
		const status = response.status;
		const contentType = response.headers.get( 'content-type' ) ?? '';
		const durationMs = Math.round( performance.now() - startedAt );

		const parsed = await parseRestResponse( response );

		if ( ! response.ok || parsed?.success === false ) {
			// Which fields the server rejected, not just that something was
			// rejected. Keys only, never values -- the log must not become a data
			// export.
			const rejected = Object.keys( parsed?.data?.field_errors ?? {} );

			// What the endpoint actually said. Without this the entry reads
			// "responded 200" and tells you nothing -- every server-side rejection
			// returns 200 with success:false, so the status alone never explains a
			// failure.
			//
			// log_message first: it is the detailed variant the server builds for
			// exactly this purpose. `parsed.message` is checked because the
			// entry-insert failure returns its message at the top level rather than
			// inside `data`, unlike every other branch.
			const reason =
				parsed?.data?.log_message ??
				parsed?.data?.message ??
				parsed?.message ??
				'';

			// CAPTCHA rejections carry the provider's own codes, which is the
			// difference between "verification failed" and a bad secret key,
			// a duplicated token, or a domain mismatch.
			const codes = parsed?.data?.api_response?.[ 'error-codes' ];
			const codeText = Array.isArray( codes ) ? ` [${ codes.join( ', ' ) }]` : '';

			const errorCode = parsed?.data?.code ? ` (${ parsed.data.code })` : '';

			// A rejection naming specific fields is the server asking the visitor
			// to correct something. Not logged: the server drops the 'blocked' type,
			// so sending it only spends a request.
			if ( rejected.length ) {
				return parsed;
			}

			srfmLog.add( {
				type: 'network',
				status,
				duration_ms: durationMs,
				message: `Submission responded ${ status } (${ contentType })${ errorCode }: ${ reason }${ codeText }`,
				// The whole payload, not only the line picked out of it above. The
				// interesting part of a rejection is often a key we did not think to
				// read -- api_response detail, a nested code, an unexpected shape.
				body: ( () => {
					try {
						return JSON.stringify( parsed ).slice( 0, 500 );
					} catch ( e ) {
						return '';
					}
				} )(),
				field_keys: [ ...filteredFormData.keys() ],
			} );
		}

		return parsed;
	} catch ( e ) {
		// Intentional: Log form submission errors to aid debugging in production.
		console.error( e );

		// "TypeError: Failed to fetch" with a short duration is itself the
		// diagnosis: the request never reached PHP -- blocked, offline or CORS.
		srfmLog.add( {
			type: 'error',
			duration_ms: Math.round( performance.now() - startedAt ),
			message: `${ e?.name ?? 'Error' }: ${
				typeof e?.message === 'string' ? e.message : ''
			}${ e?.code ? ` (${ e.code })` : '' }`,
		} );

		/**
		 * Surface the server's own message (e.g. an expired submission token, a
		 * failed CAPTCHA, a REST route blocked by a firewall) instead of returning
		 * undefined and letting the caller fall through to the generic error.
		 * `message` is left empty when the failure carries no readable reason, in
		 * which case the generic string is still used as the final fallback.
		 */
		return {
			success: false,
			data: {
				message: typeof e?.message === 'string' ? e.message : '',
				log_message: e?.code
					? `SureForms submission failed (${ e.code }).`
					: null,
			},
		};
	}
}

async function afterSubmit( formStatus, form ) {
	// Supplied by the server, already carrying the submission id and nonce.
	// Assembling it here meant reimplementing two things WordPress already does:
	// rest_url() knows whether the route is a path or a `?rest_route=` query arg,
	// and add_query_arg() knows whether the nonce needs `?` or `&`. Concatenation
	// got both wrong on plain-permalink sites and the request never routed.
	const afterSubmitUrl = formStatus?.data?.after_submit_url;

	if ( ! afterSubmitUrl ) {
		return;
	}

	const startedAt = performance.now();

	try {
		// keepalive lets the request complete after the document starts
		// unloading, which is exactly what a redirect confirmation does. The
		// request is a GET with no body, so the 64KB keepalive cap does not apply.
		const response = await fetch( afterSubmitUrl, {
			method: 'GET',
			keepalive: true,
		} );

		const status = response.status;
		const durationMs = Math.round( performance.now() - startedAt );

		await parseRestResponse( response );

		if ( ! response.ok ) {
			// Its own type, not 'network'. The entry is already saved by the time
			// this runs, so a failure here is the after-submission process --
			// integrations and webhooks -- not the submission. Recorded server-side
			// against the integration category for that reason.
			srfmLog.add( {
				type: 'after_submission',
				status,
				duration_ms: durationMs,
				message: `After-submission step responded ${ status }`,
				body: await response
					.clone()
					.text()
					.then( ( text ) => String( text ).slice( 0, 500 ) )
					.catch( () => '' ),
			} );
			srfmLog.flush( form );
		}
	} catch ( error ) {
		console.error( error );

		// The submission itself succeeded, so nothing is shown to the visitor and
		// this would otherwise be invisible outside the console.
		//
		// Logged without a status, which is what keeps it out of is_fault(). We
		// caught the browser abandoning the request -- the page unloading behind a
		// redirect confirmation is the usual cause, and it is what keepalive above
		// exists to survive -- and that says nothing about whether the server ran
		// the work. It is guarded by is_after_submission_process_triggered and
		// usually has. Safari spells this abort "TypeError: Load failed", and
		// raising a non-dismissible "their entries were not saved" notice off it
		// reported healthy sites as broken.
		srfmLog.add( {
			type: 'after_submission',
			duration_ms: Math.round( performance.now() - startedAt ),
			message: `After-submission step failed: ${
				error?.name ?? 'Error'
			}: ${ typeof error?.message === 'string' ? error.message : '' }`,
		} );
		srfmLog.flush( form );
	}
}

function showSuccessMessage(
	container,
	element,
	message,
	form,
	afterSubmission,
	submitType,
	loader
) {
	// Create and dispatch a custom event
	const event = new CustomEvent( 'srfm_on_show_success_message', {
		cancelable: true,
		detail: {
			form,
			element,
			message,
			submitType,
			container,
			loader,
		},
	} );

	if ( ! document.dispatchEvent( event ) ) {
		return; // Stop further execution if event.preventDefault() was called.
	}

	// Re-enable submit button after showing success message.
	enableSubmitButton( form );

	if ( afterSubmission === 'hide form' ) {
		form.style.opacity = 1;
		form.style.display = 'none';
		setTimeout( () => {
			element.style.opacity = 1;
		}, 500 );
	} else if ( afterSubmission === 'reset form' ) {
		form.reset();
		// Dispatch event so custom field implementations (TomSelect dropdowns,
		// date/time pickers, signature pads, etc.) can reset their own state,
		// since the native form.reset() only resets standard HTML elements.
		document.dispatchEvent(
			new CustomEvent( 'srfm_form_reset', {
				detail: { form },
			} )
		);
	}
	element.innerHTML = message;
	container.classList.add( 'srfm-active' );
	window?.srfm?.handleInstantFormWrapperHeight();

	// Scroll to the success message container, if enabled.
	if ( applyFilters( 'srfm.enableScrollOnSuccess', true ) ) {
		form.parentElement.scrollIntoView( { behavior: 'smooth' } );
	}
}

function redirectToUrl( url ) {
	window.location.assign( url );
}

/**
 * Handles the display of an error message on the form at a specified position.
 *
 * This function is triggered by the `srfm_show_common_form_error` event and is responsible
 * for showing an error message on the form. The error message can be displayed either
 * at the header or footer of the form, based on the provided position.
 *
 * @param {Event}       event                            - The custom event containing details for displaying the error message.
 * @param {Object}      event.detail                     - The event detail object.
 * @param {HTMLElement} event.detail.form                - The form element where the error message will be displayed.
 * @param {string}      [event.detail.message]           - The error message to display. Defaults to a generic error message.
 * @param {string}      [event.detail.position='footer'] - The position to display the error message ('header' or 'footer').
 */
function dispatchErrorEvent( event ) {
	const {
		form,
		message = '',
		position = 'footer',
		log_message = null,
	} = event.detail || {};

	if ( ! form ) {
		return;
	}

	// Log the error message to the console if provided.
	if ( log_message ) {
		console.warn( log_message );
	}

	const errorMessage =
		message ||
		__(
			'An error occurred while submitting your form. Please try again.',
			'sureforms'
		);

	const errorClass =
		position === 'header' ? 'srfm-head-error' : 'srfm-footer-error';
	const errorElement = form.querySelector(
		`.srfm-common-error-message.${ errorClass }`
	);

	if ( errorElement ) {
		// errorElement.innerHTML = errorMessage;
		errorElement.querySelector( '.srfm-error-content' ).innerHTML =
			errorMessage;
		errorElement.removeAttribute( 'hidden' );

		// Scroll to and focus on the error message container, if enabled.
		handleScrollAndFocusOnError( {
			firstErrorInput: errorElement,
			scrollElement: errorElement,
		} );
	}
}

// Listen for the custom event
document.addEventListener( 'srfm_show_common_form_error', dispatchErrorEvent );

function showErrorMessage( args ) {
	const {
		form,
		message = '',
		position = 'footer',
		log_message = null,
	} = args;

	const errorEvent = new CustomEvent( 'srfm_show_common_form_error', {
		detail: {
			form,
			message,
			position,
			log_message,
		},
	} );

	document.dispatchEvent( errorEvent );
}

function hideErrorMessage( form ) {
	const getErrorMessages = form.querySelectorAll(
		'.srfm-common-error-message'
	);
	getErrorMessages.forEach( ( errorMessage ) => {
		errorMessage.setAttribute( 'hidden', '' );
	} );
}

/**
 * Re-enables the submit button and restores pointer events.
 * @since 1.13.0
 *
 * @param {HTMLFormElement} form - The form element containing the submit button.
 */
function enableSubmitButton( form ) {
	const submitBtn = form.querySelector(
		'#srfm-submit-btn, button[type="submit"], .srfm-custom-button'
	);
	if ( submitBtn ) {
		submitBtn.disabled = false;
		submitBtn.style.pointerEvents = '';
	}

	// Dispatch a custom event stop form submission.
	const stopSubmissionEvent = new CustomEvent( 'srfm_form_submission_stop', {
		cancelable: true,
		detail: {
			form,
		},
	} );
	document.dispatchEvent( stopSubmissionEvent );
}

async function handleFormSubmission(
	form,
	formId,
	ajaxUrl,
	submitToken,
	loader,
	_successUrl, // Unused but required positionally to access subsequent params.
	successContainer,
	successElement,
	submitType,
	afterSubmission,
	recaptchaType,
	hCaptchaDiv,
	turnstileDiv,
	captchaErrorElement
) {
	try {
		loader.classList.add( 'srfm-active' );

		// Dispatch a custom event before submission to indicate form submission is starting.
		const startSubmissionEvent = new CustomEvent(
			'srfm_form_submission_start',
			{
				cancelable: true,
				detail: {
					form,
					loader,
					formId,
					submitType,
					successElement,
					successContainer,
				},
			}
		);
		document.dispatchEvent( startSubmissionEvent );

		// Disable submit button to prevent multiple submissions.
		const submitBtn = form.querySelector(
			'#srfm-submit-btn, button[type="submit"], .srfm-custom-button'
		);
		if ( submitBtn ) {
			submitBtn.disabled = true;
			submitBtn.style.pointerEvents = 'none';
		}

		// Hide any previous error messages.
		hideErrorMessage( form );

		const isValidate = await fieldValidation(
			formId,
			ajaxUrl,
			submitToken,
			form
		);

		// Handle captcha validation, returns true if captcha is valid or not present.
		const isCaptchaValid = handleCaptchaValidation(
			recaptchaType,
			hCaptchaDiv,
			turnstileDiv,
			captchaErrorElement
		);

		/**
		 * if captcha or field validation fails, show error message and scroll to the first input or captcha container.
		 */
		if ( isValidate?.validateResult || ! isCaptchaValid ) {
			loader.classList.remove( 'srfm-active' );

			// Not logged: a visitor-correctable stop is not a failure of the form,
			// and the server drops the 'blocked' type anyway.

			// Re-enable submit button after validation fails.
			enableSubmitButton( form );

			if ( isValidate?.validateResult ) {
				// Handle scroll and focus on error field when validation fails.
				handleScrollAndFocusOnError( isValidate );
			} else if ( ! isCaptchaValid ) {
				// Handle scroll and focus on error field when captcha validation fails.
				handleScrollAndFocusOnError( {
					firstErrorInput: captchaErrorElement,
					scrollElement: captchaErrorElement,
				} );
			}
			return;
		}

		// Create and dispatch a custom event
		const event = new CustomEvent( 'srfm_on_trigger_form_submission', {
			cancelable: true,
			detail: {
				form,
				loader,
				formId,
				submitType,
				successElement,
				successContainer,
			},
		} );

		if ( ! document.dispatchEvent( event ) ) {
			loader.classList.remove( 'srfm-active' );

			// Re-enable submit button if submission is cancelled.
			enableSubmitButton( form );

			return; // Stop further execution if event.preventDefault() was called.
		}

		// Process payment submission
		const paymentResult = await handleFormPayment( form );

		if ( ! paymentResult?.valid ) {
			showErrorMessage( { form, message: paymentResult?.message } );
			// Remove loading.
			loader.classList.remove( 'srfm-active' );
			enableSubmitButton( form );
			return;
		}

		const formStatus = await submitFormData( form );
		if ( formStatus?.success ) {
			// Dispatched first, before anything below can navigate away. The
			// 'different page' and 'custom url' branches call redirectToUrl(), and a
			// third-party listener on srfm_form_submission_success may navigate too;
			// either unload cancels an in-flight fetch, and issuing this afterwards
			// meant the request aborted so the after-submission process never ran.
			// afterSubmit() reads only after_submit_url, so it depends on neither the
			// event nor the submission-mode resolution below, and gating it here on
			// after_submit alone keeps every submission mode covered.
			if ( formStatus?.data?.after_submit ) {
				afterSubmit( formStatus, form );
			}

			/**
			 * Emit a function to signal the successful submission of a form.
			 */
			emitFormSubmitSuccess( { ...formStatus, formId } );

			/**
			 * If there is a submit mode or after submission defined in the form submit response,
			 * use that instead of the one defined in the form attribute.
			 */
			const submitTypeFormAttribute = submitType;
			submitType =
				formStatus?.data?.submission_settings?.submission_mode ||
				submitTypeFormAttribute;

			const oldAfterSubmission = afterSubmission;
			afterSubmission =
				formStatus?.data?.submission_settings?.after_submission ||
				oldAfterSubmission;

			if ( submitType === 'same page' ) {
				showSuccessMessage(
					successContainer,
					successElement,
					formStatus?.message ?? '',
					form,
					afterSubmission,
					submitType
				);
				loader.classList.remove( 'srfm-active' );

				// Re-enable submit button after successful submission.
				enableSubmitButton( form );
			} else if (
				/**
				 * This condition is similar to above one but we are using this for custom-app
				 * here we are not removing 'srfm-active' class from loader
				 * and sending loader as an extra parameter
				 */
				! [ 'different page', 'custom url' ].includes( submitType )
			) {
				showSuccessMessage(
					successContainer,
					successElement,
					formStatus?.message ?? '',
					form,
					afterSubmission,
					submitType,
					loader
				);
			} else {
				if ( formStatus?.redirect_url ) {
					redirectToUrl( formStatus?.redirect_url );
				}
				loader.classList.remove( 'srfm-active' );

				// Re-enable submit button after redirect.
				enableSubmitButton( form );
			}
		} else {
			const errorData = formStatus?.data || {};
			showErrorMessage( { form, ...errorData } );
			loader.classList.remove( 'srfm-active' );

			// Ship everything buffered for this attempt. The message the visitor
			// saw is already in the response entry submitFormData recorded.
			srfmLog.flush( form );

			// Re-enable submit button after error.
			enableSubmitButton( form );
		}
	} catch ( error ) {
		// Anything thrown anywhere in the submission path lands here -- including a
		// throw inside field or captcha validation, before the request is ever made.
		// That is the case where a third-party script has broken a field's own
		// validation code, which is invisible to every other capture point.
		srfmLog.add( {
			type: 'error',
			message: `Submission aborted: ${ error?.name ?? 'Error' }: ${
				typeof error?.message === 'string' ? error.message : ''
			}`,
			source: String( error?.stack ?? '' )
				.split( '\n' )[ 1 ]
				?.trim()
				?.slice( 0, 200 ),
		} );
		srfmLog.flush( form );

		// Create and dispatch a custom event
		const event = new CustomEvent(
			'srfm_on_trigger_form_submission_failure',
			{
				detail: {
					form,
					error,
					loader,
					formId,
					submitType,
					successElement,
					successContainer,
				},
			}
		);

		document.dispatchEvent( event );

		loader.classList.remove( 'srfm-active' );

		// Re-enable submit button after exception.
		enableSubmitButton( form );

		showErrorMessage( { form } );
	}
}

function extractFormAttributesAndElements( form ) {
	const formId = form.getAttribute( 'form-id' );
	const submitType = form.getAttribute( 'message-type' );
	const successUrl = form.getAttribute( 'success-url' );
	const ajaxUrl = form.getAttribute( 'ajaxurl' );
	const submitToken = form.getAttribute( 'data-submit-token' );
	const loader = form.querySelector( '.srfm-loader' );
	const successContainer = form.parentElement.querySelector(
		'.srfm-single-form.srfm-success-box'
	);
	const successElement = successContainer?.querySelector(
		'.srfm-success-box-description'
	);
	const submitBtn = form.querySelector( '#srfm-submit-btn' );
	const afterSubmission = form.getAttribute( 'after-submission' );
	const gcaptchaDiv = form.querySelector( '.g-recaptcha' );
	const siteKey = gcaptchaDiv?.getAttribute( 'data-sitekey' );
	const recaptchaType = gcaptchaDiv?.getAttribute( 'recaptcha-type' );
	const captchaErrorElement = form.querySelector( '#captcha-error' );
	const hCaptchaDiv = form.querySelector( '.h-captcha' );
	const turnstileDiv = form.querySelector( '.cf-turnstile' );
	const hasLoginBlock = form.querySelector( '.srfm-login-block' );

	return {
		formId,
		submitType,
		successUrl,
		ajaxUrl,
		submitToken,
		loader,
		successContainer,
		successElement,
		submitBtn,
		siteKey,
		recaptchaType,
		afterSubmission,
		captchaErrorElement,
		hCaptchaDiv,
		turnstileDiv,
		hasLoginBlock,
	};
}

/**
 * Callback function to handle form submission.
 * Incase of v2-invisible reCAPTCHA, it will render the reCAPTCHA and handle form submission.
 * Incase of v3-reCAPTCHA, it will handle form submission directly for the specific form that was clicked.
 *
 * @param {string} token v3-reCAPTCHA token.
 */
function recaptchaCallback( token = '' ) {
	// For v3-reCAPTCHA: Only submit the form that was clicked, not all forms on the page
	if ( token && pendingRecaptchaForm ) {
		const form = pendingRecaptchaForm;
		pendingRecaptchaForm = null; // Clear the reference immediately

		const {
			formId,
			submitType,
			ajaxUrl,
			submitToken,
			loader,
			successUrl,
			successContainer,
			successElement,
			recaptchaType,
			afterSubmission,
		} = extractFormAttributesAndElements( form );

		if ( recaptchaType === 'v3-reCAPTCHA' ) {
			loader.classList.add( 'srfm-active' );
			handleFormSubmission(
				form,
				formId,
				ajaxUrl,
				submitToken,
				loader,
				successUrl,
				successContainer,
				successElement,
				submitType,
				afterSubmission
			);
			return;
		}
	}

	// Handle v2-invisible reCAPTCHA (original behavior for initial render)
	const forms = Array.from( document.querySelectorAll( '.srfm-form' ) );
	forms.forEach( ( form ) => {
		const {
			formId,
			submitType,
			successUrl,
			ajaxUrl,
			submitToken,
			loader,
			successContainer,
			successElement,
			submitBtn,
			siteKey,
			recaptchaType,
			afterSubmission,
		} = extractFormAttributesAndElements( form );
		let isRecaptchaRender = false;
		if ( recaptchaType === 'v2-invisible' ) {
			grecaptcha.render( submitBtn, {
				sitekey: siteKey,
				callback: () => {
					handleFormSubmission(
						form,
						formId,
						ajaxUrl,
						submitToken,
						loader,
						successUrl,
						successContainer,
						successElement,
						submitType,
						afterSubmission
					);
					isRecaptchaRender = true;
				},
				'error-callback': () => {
					showErrorMessageOnRecaptchaError( {
						containerSelector:
							'.g-recaptcha[recaptcha-type="v2-invisible"]:not(.captcha-error-added)',
						message:
							srfm_submit?.messages
								?.srfm_google_captcha_error_message,
					} );
				},
			} );

			submitBtn.addEventListener( 'click', () => {
				loader.classList.add( 'srfm-active' );
				if ( isRecaptchaRender ) {
					handleFormSubmission(
						form,
						formId,
						ajaxUrl,
						submitToken,
						loader,
						successUrl,
						successContainer,
						successElement,
						submitType,
						afterSubmission
					);
				}
			} );
		}
	} );
}

function showErrorMessageOnRecaptchaError( args ) {
	const { containerSelector, message = '' } = args;

	const getCaptchaContainer = document.querySelectorAll( containerSelector );
	if ( ! getCaptchaContainer ) {
		return;
	}

	getCaptchaContainer.forEach( ( element ) => {
		const getTheForm = element.closest( '.srfm-form' );
		if ( getTheForm ) {
			showErrorMessage( { form: getTheForm, message } );

			// Add class
			element.classList.add( 'captcha-error-added' );
		}
	} );
}

/**
 * Emits a custom event to signal the successful submission of a form.
 *
 * This function creates and dispatches a custom event, `srfm_form_submission_success`,
 * to notify other parts of the application that a form has been successfully submitted.
 * It includes form-specific details, such as the form data.
 *
 * Custom Event: `srfm_form_submission_success`
 * - Dispatched event signaling a form submission success.
 * - Event payload (`detail`) includes: form data.
 *
 * @param {Object} formStatus - An object representing the status of the form submission.
 */
function emitFormSubmitSuccess( formStatus ) {
	// Create a custom event with form details.
	const srfmFormSubmissionSuccessEvent = new CustomEvent(
		'srfm_form_submission_success',
		{
			detail: {
				formId: `srfm-form-${ formStatus.formId }`,
			},
		}
	);

	// Dispatch the custom event.
	document.dispatchEvent( srfmFormSubmissionSuccessEvent );
}

// directly assign recaptchaCallback into the global space:
window.recaptchaCallback = recaptchaCallback;

// Export showErrorMessage for use in payment integrations (Stripe, PayPal, etc.)
window.showErrorMessage = showErrorMessage;

// Bricks Builder compatibility to disable form submission in the preview mode
window.handleBricksPreviewFormSubmission = function () {
	const forms = Array.from( document.querySelectorAll( '.srfm-form' ) );
	for ( const form of forms ) {
		form.addEventListener( 'submit', async function ( e ) {
			e.preventDefault();
		} );
	}
};

// Listen for the Elementor popup show event
window.addEventListener( 'elementor/popup/show', function ( e ) {
	// Check if the popup contains a SureForms form container
	const formContainer = e?.detail?.instance?.$element?.[ 0 ]?.querySelector(
		'.srfm-form-container'
	);

	// If a form container is found, initialize form handlers
	if ( formContainer ) {
		initializeFormHandlers();
	}
} );

// Listen for a custom event named 'srfm_form_initialize'
// This event should be dispatched whenever a form is dynamically initialized
document.addEventListener( 'srfm_form_initialize', function () {
	// Call a function to attach event listeners, validation, or other custom logic
	initializeFormHandlers();
} );
