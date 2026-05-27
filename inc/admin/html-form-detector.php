<?php
/**
 * HTML Form Detector.
 *
 * Enqueues the editor-side script that scans `core/html` blocks for raw
 * `<form>` markup and offers a one-click conversion to a SureForms form.
 *
 * This is the free-plugin prototype: detection + UI only. The conversion
 * callback in the JS layer currently parses locally and logs the result;
 * the AI-assisted REST endpoint that actually creates the form lives in a
 * follow-up patch so the detection wiring can be validated independently.
 *
 * Script is enqueued when:
 *  - User can manage SureForms forms (same `manage_options` gate as the
 *    rest of the form-admin surface — there is no point offering a CTA to
 *    users who cannot create forms).
 *  - Current screen is the block editor and not the SureForms form CPT
 *    (we never run on the form editor itself — the source `<form>` only
 *    appears on host posts/pages).
 *
 * @package sureforms.
 */

namespace SRFM\Inc\Admin;

use SRFM\Inc\Abilities\Forms\Create_Form;
use SRFM\Inc\AI_Form_Builder\AI_Helper;
use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HTML form detector handler.
 *
 * @since 2.10.0
 */
class Html_Form_Detector {
	use Get_Instance;

	/**
	 * Confidence level below which we route the raw HTML through the AI
	 * middleware instead of trusting the local parser output.
	 *
	 * @since 2.10.0
	 */
	public const AI_FALLBACK_CONFIDENCE = 'low';

	/**
	 * Hard cap on the size of raw HTML accepted by the conversion endpoint.
	 *
	 * Anything larger is almost certainly the entire page rather than a
	 * single `<form>` and would waste an AI roundtrip on noise. Matches the
	 * upper bound the AI middleware tolerates for `query` payloads.
	 *
	 * @since 2.10.0
	 */
	public const MAX_HTML_BYTES = 32768;

	/**
	 * Constructor.
	 *
	 * Registers the REST route only on admin / REST-dispatch requests
	 * — that is the only context where the route is reachable, and
	 * gating registration narrows the blast radius if the shared
	 * `Helper::get_items_permissions_check` is ever loosened by an
	 * unrelated change. The endpoint also re-checks `manage_options`
	 * inside the handler so authorization survives both contexts.
	 *
	 * @since 2.10.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Register the REST endpoint unconditionally. The constructor runs on
		// the `init` hook (see plugin-loader.php), which fires *before*
		// `parse_request` — the point at which WordPress defines
		// `REST_REQUEST`. Gating on `REST_REQUEST` here meant the filter was
		// never attached for the actual REST dispatch, and the endpoint 404'd.
		// `apply_filters( 'srfm_rest_api_endpoints', ... )` is only invoked
		// from `Rest_Api::register_endpoints()` on `rest_api_init`, so
		// attaching this filter on non-REST requests has no runtime cost.
		add_filter( 'srfm_rest_api_endpoints', [ $this, 'register_rest_endpoint' ] );
	}

	/**
	 * Decide whether the detector script should be loaded for the current request.
	 *
	 * @since 2.10.0
	 * @return bool
	 */
	public function allow_load() {
		if ( ! is_admin() ) {
			return false;
		}

		// Gate on the cap required to actually manage SureForms forms — same
		// rationale as the Editor_Nudge: never surface a "Convert to
		// SureForms" CTA to a user who cannot reach the form-creation flow.
		if ( ! Helper::current_user_can( 'manage_options' ) ) {
			return false;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! method_exists( $screen, 'is_block_editor' ) || ! $screen->is_block_editor() ) {
			return false;
		}

		// Skip on the SureForms form editor itself — the source `<form>`
		// markup we look for only appears on host posts/pages.
		if ( SRFM_FORMS_POST_TYPE === $screen->post_type ) {
			return false;
		}

		return true;
	}

	/**
	 * Enqueue the detector script when allowed.
	 *
	 * @since 2.10.0
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! $this->allow_load() ) {
			return;
		}

		$handle     = SRFM_SLUG . '-html-form-detector';
		$asset_path = SRFM_DIR . 'assets/build/htmlFormDetector.asset.php';
		$asset      = file_exists( $asset_path )
			? include $asset_path
			: [
				'dependencies' => [ 'wp-api-fetch', 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-compose', 'wp-data', 'wp-element', 'wp-hooks', 'wp-i18n' ],
				'version'      => SRFM_VER,
			];

		wp_enqueue_script(
			$handle,
			SRFM_URL . 'assets/build/htmlFormDetector.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_localize_script(
			$handle,
			'srfm_html_form_detector',
			[
				'rest_nonce' => wp_create_nonce( 'wp_rest' ),
			]
		);

		Helper::register_script_translations( $handle );
	}

	/**
	 * Register the conversion REST endpoint on the existing SureForms route map.
	 *
	 * Hooked into `srfm_rest_api_endpoints` so we land alongside the other
	 * `sureforms/v1/*` routes without touching `Rest_Api::get_endpoints()` —
	 * keeping every concern of the detector co-located in this class.
	 *
	 * @since 2.10.0
	 * @param array<string,array<string,mixed>> $endpoints Existing endpoints map.
	 * @return array<string,array<string,mixed>>
	 */
	public function register_rest_endpoint( $endpoints ) {
		if ( ! is_array( $endpoints ) ) {
			$endpoints = [];
		}

		$endpoints['convert-html-form'] = [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'handle_convert_html_form' ],
			'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
			'args'                => [
				'parsed_fields' => [
					'required'    => false,
					'type'        => 'array',
					'description' => __( 'Array of fields produced by the editor-side parser.', 'sureforms' ),
				],
				'submit_text'   => [
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'default'           => '',
				],
				'confidence'    => [
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'default'           => 'high',
				],
				'html'          => [
					'required'    => false,
					'type'        => 'string',
					'description' => __( 'Raw HTML of the source <form>. Required when parser confidence is low so we can hand the markup to the AI middleware.', 'sureforms' ),
				],
				'form_title'    => [
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'default'           => '',
				],
				'styling'       => [
					'required'    => false,
					'type'        => 'object',
					'description' => __( 'Best-effort styling descriptor (hex colors) extracted from inline styles on the source <form>.', 'sureforms' ),
				],
			],
		];

		return $endpoints;
	}

	/**
	 * Convert a raw HTML form into a SureForms form.
	 *
	 * Flow:
	 *  - If the editor-side parser returned `confidence === 'low'` AND raw
	 *    HTML is supplied, send the HTML to the AI middleware and use the
	 *    structured schema it returns (hybrid path — AI handles markup the
	 *    deterministic parser could not confidently classify).
	 *  - Otherwise trust the parsed fields and pass them straight to the
	 *    existing `Create_Form` ability so the same code that creates AI- /
	 *    MCP-generated forms also handles this conversion. Means a single
	 *    code path produces the final `sureforms_form` CPT; no parallel
	 *    insert logic to maintain.
	 *
	 * @since 2.10.0
	 * @param WP_REST_Request $request REST request.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function handle_convert_html_form( $request ) {
		// Capability check runs first — cheaper than `wp_verify_nonce`,
		// and the REST framework's `permission_callback` already passed
		// at this point, so a failure here means a `current_user_can`
		// filter or capability-mapping shim was loosened between the
		// permission callback and the handler. Bail early to keep
		// the expensive AI middleware path off the table for users who
		// could not legitimately complete the action.
		if ( ! Helper::current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'srfm_html_convert_forbidden',
				__( 'You are not allowed to convert HTML forms.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		// Nonce check is CSRF defense for the legitimate
		// `manage_options` user we just confirmed. Running it after the
		// cap check means cap-less requests never pay the
		// `hash_hmac`/session-lookup cost of nonce verification, and
		// the error-code precedence is also more honest: a subscriber
		// without `manage_options` gets `forbidden`, not the misleading
		// `nonce_failed`.
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );
		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new WP_Error(
				'srfm_html_convert_nonce_failed',
				__( 'Security verification failed. Please refresh the page and try again.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		$raw_fields  = $request->get_param( 'parsed_fields' );
		$confidence  = Helper::get_string_value( $request->get_param( 'confidence' ) );
		$raw_html    = Helper::get_string_value( $request->get_param( 'html' ) );
		$form_title  = Helper::get_string_value( $request->get_param( 'form_title' ) );
		$submit_text = Helper::get_string_value( $request->get_param( 'submit_text' ) );
		$styling_in  = $request->get_param( 'styling' );
		$styling_in  = is_array( $styling_in ) ? $styling_in : [];
		$used_ai     = false;

		if ( '' === $form_title ) {
			$form_title = __( 'Converted form', 'sureforms' );
		}

		// AI fallback path. We only invoke the middleware when the local
		// parser flagged the input as ambiguous AND the caller actually
		// supplied raw HTML — otherwise there is nothing useful to send.
		if ( self::AI_FALLBACK_CONFIDENCE === $confidence && '' !== $raw_html ) {
			if ( strlen( $raw_html ) > self::MAX_HTML_BYTES ) {
				return new WP_Error(
					'srfm_html_convert_too_large',
					__( 'The HTML form is too large to convert. Please simplify the markup or build the form manually.', 'sureforms' ),
					[ 'status' => 413 ]
				);
			}

			$ai_fields = $this->extract_fields_via_ai( $raw_html );
			if ( is_wp_error( $ai_fields ) ) {
				return $ai_fields;
			}
			$raw_fields = $ai_fields;
			$used_ai    = true;
		}

		if ( ! is_array( $raw_fields ) || empty( $raw_fields ) ) {
			return new WP_Error(
				'srfm_html_convert_no_fields',
				__( 'No fields could be derived from the supplied form.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		// Strip the parser-internal hints (`_groupName`, `_optionValue`,
		// `confidence`) before handing fields to Create_Form — the schema
		// for that ability rejects unknown keys via additionalProperties.
		$clean_fields = $this->strip_internal_hints( $raw_fields );

		/**
		 * Filter the field list before handing it to `Create_Form`.
		 *
		 * Lets extensions (notably SureForms Pro) re-inspect the raw
		 * source HTML and refine the parsed fields — e.g. promote a
		 * `<input type="date">` from a plain `input` to a `date-picker`
		 * block when the pro field type is registered. The JS parser
		 * cannot do this on its own because the pro field types are
		 * only valid when the pro plugin is active; gating that on the
		 * server is simpler and avoids leaking pro-specific behavior
		 * into the public block-editor bundle.
		 *
		 * @since 2.10.0
		 * @param array<int,array<string,mixed>> $clean_fields Sanitized field list ready for Create_Form.
		 * @param string                         $raw_html     Original HTML of the source `<form>` block.
		 * @param string                         $confidence   Parser confidence (`high`/`medium`/`low`).
		 *
		 * SECURITY CONTRACT: callbacks MUST return values already
		 * sanitized for storage as block attributes. `Create_Form`
		 * re-sanitizes a hardcoded list of properties (label,
		 * placeholder, helpText, defaultValue, fieldOptions), but any
		 * property a callback introduces beyond that set — a pro
		 * field's `allowedFormats`, `dateFormat`, `step`, etc. — is NOT
		 * covered by the downstream sanitization pass. Strings should
		 * pass through `sanitize_text_field` / `wp_kses_post`, scalars
		 * through `absint` / `floatval`, arrays should have each leaf
		 * sanitized. The defensive `strip_unsafe_html_in_fields` pass
		 * below catches obvious raw-tag injection but is not a
		 * substitute for proper per-property sanitization.
		 */
		$clean_fields = apply_filters( 'srfm_html_form_detector_refine_fields', $clean_fields, $raw_html, $confidence );

		// Defensive post-filter sweep: strip raw HTML tags from every
		// string leaf in every field property. The filter contract
		// above documents that callbacks must sanitize, but a sloppy
		// callback could re-introduce attacker markup in properties
		// `Create_Form` does not know to clean — at which point a
		// later block renderer that emits the attribute as inner HTML
		// becomes a stored-XSS sink. Stripping tags here is a narrow
		// safety net that loses no legitimate value: form-field
		// attributes are not HTML containers.
		$clean_fields = $this->strip_unsafe_html_in_fields( $clean_fields );

		$create_form = new Create_Form();
		$result      = $create_form->execute(
			[
				'formTitle'    => $form_title,
				'formFields'   => $clean_fields,
				'formStatus'   => 'publish',
				'formMetaData' => $this->build_form_metadata( $submit_text, $styling_in ),
			]
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Layer in the native form-card styling (background, padding,
		// border radius). These live in `_srfm_forms_styling` and are
		// exposed in the per-form Styling sidebar — the same UI users get
		// when they build a form by hand — so populating them keeps the
		// converted form fully editable post-creation instead of locking
		// the look behind opaque custom CSS.
		$form_id = isset( $result['form_id'] ) ? Helper::get_integer_value( $result['form_id'] ) : 0;
		if ( $form_id > 0 ) {
			$this->apply_native_card_styling( $form_id, $styling_in );

			/**
			 * Fires after the converter writes its baseline form
			 * metadata, giving extensions a chance to layer in
			 * additional `_srfm_forms_styling` keys — e.g. a pro
			 * `form_theme` preset chosen from inline-style hints.
			 *
			 * @since 2.10.0
			 * @param int                 $form_id  Newly-created SureForms form ID.
			 * @param array<string,mixed> $styling  Parser styling descriptor (inline-style hints).
			 * @param string              $raw_html Original HTML of the source `<form>` block.
			 */
			do_action( 'srfm_html_form_detector_after_styling', $form_id, $styling_in, $raw_html );
		}

		// Compute the markup that survives once the source `<form>` is
		// removed from the original block — wrapping `<div>`s, a
		// heading above the form, a post-submit paragraph below it,
		// inline `<script>`, etc. The client also computes this (so
		// the conversion is responsive even when the response is in
		// flight), but the client output is treated as untrusted: we
		// return the server-computed value here and the editor
		// prefers it when present. This lets us KSES-filter the
		// remnant for users without `unfiltered_html` so a converter
		// click cannot surface previously-hidden attacker markup that
		// the original `<form>` was masking visually.
		$result['preserved_html'] = $this->strip_form_for_preservation( $raw_html );

		$result['used_ai'] = $used_ai;
		return $result;
	}

	/**
	 * Strip the first `<form>` element from the supplied HTML and
	 * return whatever non-empty markup remains, optionally KSES-filtered
	 * when the current user lacks `unfiltered_html`.
	 *
	 * The editor would otherwise drop the source `core/html` block's
	 * non-form content silently when the user clicks Convert. Routing
	 * that decision through the server lets us apply the same
	 * `unfiltered_html` capability gate WordPress applies to every
	 * other path that writes user-supplied HTML into post_content —
	 * site admins on multisite (who have `manage_options` but not
	 * `unfiltered_html`) get the `wp_kses_post` treatment, super
	 * admins on single-site / multisite get the raw markup through.
	 *
	 * @since 2.10.0
	 * @param string $html Original `core/html` block contents.
	 * @return string Stripped (and optionally filtered) markup, or '' when nothing survives.
	 */
	protected function strip_form_for_preservation( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return '';
		}

		// DOMDocument copes with malformed HTML, wrappers around the
		// `<form>`, and embedded `<script>`/`<style>` blocks without
		// us hand-rolling a regex. `libxml_use_internal_errors()` is the
		// idiomatic way to suppress malformed-HTML warnings without the
		// `@` operator (mirrors `Helper::strip_js_attributes()`).
		$dom = new \DOMDocument();
		libxml_use_internal_errors( true );
		// Defense-in-depth flags mirror the pro extension's loadHTML
		// call. `LIBXML_NONET` refuses any outbound DTD fetch even if a
		// `<!DOCTYPE … SYSTEM …>` ever slipped past `LIBXML_HTML_NODEFDTD`;
		// the latter suppresses the default HTML4 DTD, which libxml
		// would otherwise pull in. Single-site admins reach this path
		// with `unfiltered_html` so the attack surface is mostly
		// theoretical, but multisite site-admins (manage_options
		// without unfiltered_html) do not — closing this gives them
		// the same hardening their pro counterparts get.
		$loaded = $dom->loadHTML(
			'<?xml encoding="UTF-8"><div id="srfm-preserve-root">' . $html . '</div>',
			LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		if ( ! $loaded ) {
			return '';
		}

		$root = $dom->getElementById( 'srfm-preserve-root' );
		if ( ! $root instanceof \DOMElement ) {
			return '';
		}

		$forms = $root->getElementsByTagName( 'form' );
		if ( $forms->length > 0 ) {
			$first = $forms->item( 0 );
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM property.
			if ( $first instanceof \DOMNode && $first->parentNode instanceof \DOMNode ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM property.
				$first->parentNode->removeChild( $first );
			}
		}

		$preserved = '';
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM property.
		foreach ( iterator_to_array( $root->childNodes ) as $child ) {
			$preserved .= $dom->saveHTML( $child );
		}

		$preserved = trim( $preserved );
		if ( '' === $preserved ) {
			return '';
		}

		// Gate `<script>` / `<iframe>` / similar survival on the same
		// capability WordPress applies to every other unsanitized HTML
		// sink. On multisite, this means a site admin (who can paste
		// `<script>` into a `core/html` block only by virtue of
		// per-site rules WP already enforces) gets the same treatment
		// here that they would get if the post were saved through the
		// REST API directly.
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$preserved = wp_kses_post( $preserved );
		}

		return $preserved;
	}

	/**
	 * Merge background / padding / border-radius into the form's
	 * `_srfm_forms_styling` meta — the same array the Styling sidebar
	 * writes to in the form editor.
	 *
	 * Why this is a post-create step instead of going through the
	 * Form_Metadata trait: the trait only exposes the colors +
	 * field-spacing slice of the styling array (primary, text, text on
	 * primary, field_spacing). Padding, border radius, and the
	 * embedded-form background (`bg_type` / `bg_color`) are not in its
	 * input schema. Rather than expand the shared trait — which is also
	 * used by the MCP `update-form` ability and would broaden the
	 * blast radius of any schema mistake — we write the extra keys
	 * directly here, keeping the change scoped to the conversion flow.
	 *
	 * All keys touched (`bg_type`, `bg_color`, `form_padding_*`,
	 * `form_border_radius_*`) exist in the FREE plugin (see
	 * `Form_Styling::map_block_attrs_to_styling` and the Styling tab in
	 * `src/admin/single-form-settings/tabs/StyleSettings.js` — neither
	 * gates these behind `SRFM_PRO_VER`). Pro is not required.
	 *
	 * @since 2.10.0
	 * @param int                 $form_id Newly-created form ID.
	 * @param array<string,mixed> $styling Parser styling descriptor.
	 * @return void
	 */
	protected function apply_native_card_styling( $form_id, $styling ) {
		$existing = get_post_meta( $form_id, '_srfm_forms_styling', true );
		$existing = is_array( $existing ) ? $existing : [];

		$updates = [];

		if ( ! empty( $styling['formBackgroundColor'] ) ) {
			$hex = sanitize_hex_color( Helper::get_string_value( $styling['formBackgroundColor'] ) );
			if ( $hex ) {
				// `bg_type` must accompany `bg_color` — `Generate_Form_Markup`
				// only emits `--srfm-bg-color` when `bg_type === 'color'`,
				// so setting the color without the type is silently dropped.
				$updates['bg_type']  = 'color';
				$updates['bg_color'] = $hex;
			}
		}

		$padding = $this->shorthand_to_sides( $styling['formPadding'] ?? '' );
		if ( null !== $padding ) {
			$updates['form_padding_top']    = $padding['top'];
			$updates['form_padding_right']  = $padding['right'];
			$updates['form_padding_bottom'] = $padding['bottom'];
			$updates['form_padding_left']   = $padding['left'];
			$updates['form_padding_unit']   = $padding['unit'];
			$updates['form_padding_link']   = $padding['link'];
		}

		$radius = $this->shorthand_to_sides( $styling['formBorderRadius'] ?? '' );
		if ( null !== $radius ) {
			$updates['form_border_radius_top']    = $radius['top'];
			$updates['form_border_radius_right']  = $radius['right'];
			$updates['form_border_radius_bottom'] = $radius['bottom'];
			$updates['form_border_radius_left']   = $radius['left'];
			$updates['form_border_radius_unit']   = $radius['unit'];
			$updates['form_border_radius_link']   = $radius['link'];
		}

		if ( empty( $updates ) ) {
			return;
		}

		update_post_meta( $form_id, '_srfm_forms_styling', array_merge( $existing, $updates ) );
	}

	/**
	 * Parse a CSS box-model shorthand string (e.g. `24px`, `12px 16px`,
	 * `1rem 2rem 1rem 2rem`) into the 4-side structure that
	 * `_srfm_forms_styling` expects.
	 *
	 * Returns `null` when the value is unusable so callers can skip the
	 * meta write entirely — better than writing zeros that would
	 * silently override defaults set elsewhere. Only px / rem / em / %
	 * units are accepted; anything else falls back to px to match the
	 * SureForms admin's allowed values.
	 *
	 * Security-critical input boundary — DO NOT relax without auditing
	 * `Generate_Form_Markup`: values from this function flow verbatim
	 * into the form-container `<style>` block. The strict numeric +
	 * whitelisted-unit regex below is what prevents a crafted source
	 * `style="padding: }</style><script>…"` from escaping the
	 * declaration and producing stored XSS. If you ever extend the
	 * accepted units (e.g. `calc()`, `vh`, `vw`) you MUST also confirm
	 * the downstream consumer still wraps the value in a context where
	 * those tokens cannot escape into the surrounding markup.
	 *
	 * @since 2.10.0
	 * @param mixed $value Raw shorthand string from the parser.
	 * @return array{top:float,right:float,bottom:float,left:float,unit:string,link:bool}|null
	 */
	protected function shorthand_to_sides( $value ) {
		if ( ! is_string( $value ) ) {
			return null;
		}
		$value = trim( $value );
		if ( '' === $value ) {
			return null;
		}

		$parts = preg_split( '/\s+/', $value );
		if ( ! is_array( $parts ) || empty( $parts ) ) {
			return null;
		}

		$nums  = [];
		$units = [];
		foreach ( $parts as $part ) {
			// Security-critical regex — see method docblock. The unit
			// alternation MUST stay a fixed whitelist.
			if ( ! preg_match( '/^(-?\d+(?:\.\d+)?)(px|rem|em|%)?$/', $part, $m ) ) {
				return null;
			}
			$nums[]  = (float) $m[1];
			$units[] = $m[2] ?? 'px';
		}

		// Normalize the unit: SureForms admin stores ONE unit shared by
		// all four sides. When the source mixed units (rare) we keep the
		// first one — the alternative of converting between units would
		// be lossy and surprising.
		$unit = in_array( $units[0], [ 'px', 'rem', 'em', '%' ], true ) ? $units[0] : 'px';

		// Expand CSS shorthand semantics: 1 → all sides, 2 → vert/horiz,
		// 3 → top, horiz, bottom, 4 → top, right, bottom, left.
		switch ( count( $nums ) ) {
			case 1:
				$sides = [ $nums[0], $nums[0], $nums[0], $nums[0] ];
				break;
			case 2:
				$sides = [ $nums[0], $nums[1], $nums[0], $nums[1] ];
				break;
			case 3:
				$sides = [ $nums[0], $nums[1], $nums[2], $nums[1] ];
				break;
			case 4:
				$sides = [ $nums[0], $nums[1], $nums[2], $nums[3] ];
				break;
			default:
				return null;
		}

		return [
			'top'    => $sides[0],
			'right'  => $sides[1],
			'bottom' => $sides[2],
			'left'   => $sides[3],
			'unit'   => $unit,
			// `link` is the admin's "all sides linked" toggle — when the
			// shorthand collapsed to one value, the user clearly meant
			// every side to match, so link them.
			'link'   => 1 === count( $nums ),
		];
	}

	/**
	 * Send raw HTML to the AI middleware and return the structured field list.
	 *
	 * The middleware was originally designed for natural-language prompts
	 * ("a contact form with name, email, message"), but its system prompt
	 * always produces `form.formFields` — feeding the HTML as the prompt
	 * with an explicit instruction reliably yields a usable schema. If the
	 * middleware errors or returns a malformed payload we surface a single
	 * WP_Error so the caller can fall back to a manual create-form CTA.
	 *
	 * Privacy hardening: the source `<form>` markup may contain values
	 * the admin did not author — prefilled hidden inputs from a previous
	 * form library, CSRF tokens, server-rendered email addresses, etc.
	 * We strip `<input type="hidden">` and `value="..."` attributes
	 * before forwarding so the middleware only sees the structural
	 * shape (which is all it needs to infer field types). The endpoint
	 * is admin-trusted, so this is defense-in-depth rather than a
	 * trust-boundary check, but it meaningfully reduces what leaves
	 * the site.
	 *
	 * Defensive filtering on the response: even though the middleware
	 * is trusted, we drop any non-array entries from `formFields`
	 * before returning so a malformed payload cannot reach the
	 * `Create_Form` schema validator as a surprise scalar.
	 *
	 * @since 2.10.0
	 * @param string $html Raw HTML containing the source `<form>`.
	 * @return array<int,array<string,mixed>>|\WP_Error
	 */
	protected function extract_fields_via_ai( $html ) {
		$sanitized_html = $this->scrub_html_for_ai( $html );

		// English-only by design — this is the instruction sent to the
		// AI middleware, not user-facing copy. Wrapping it in `__()`
		// would invite translators to localize a machine prompt (and
		// the model is not multilingual on the conversion task today).
		$query = sprintf(
			'Convert the following raw HTML form into a SureForms field schema. Preserve field types, labels, the required attribute, and any select/radio/checkbox options. Do not invent fields that are not present in the markup. HTML: %s',
			$sanitized_html
		);

		$response = AI_Helper::get_chat_completions_response( [ 'query' => $query ] );

		if ( ! is_array( $response ) || ! empty( $response['error'] ) ) {
			return new WP_Error(
				'srfm_html_convert_ai_failed',
				__( 'The SureForms AI service could not process this form. Try again or build the form manually.', 'sureforms' ),
				[ 'status' => 502 ]
			);
		}

		if (
			empty( $response['form'] ) ||
			! is_array( $response['form'] ) ||
			empty( $response['form']['formFields'] ) ||
			! is_array( $response['form']['formFields'] )
		) {
			return new WP_Error(
				'srfm_html_convert_ai_empty',
				__( 'The SureForms AI service returned an unusable response. Try again or build the form manually.', 'sureforms' ),
				[ 'status' => 502 ]
			);
		}

		// Belt-and-braces: trust the middleware to return well-formed
		// fields but never let a non-array slip through to the
		// downstream schema validator.
		$fields = array_values( array_filter( $response['form']['formFields'], 'is_array' ) );

		if ( empty( $fields ) ) {
			return new WP_Error(
				'srfm_html_convert_ai_empty',
				__( 'The SureForms AI service returned an unusable response. Try again or build the form manually.', 'sureforms' ),
				[ 'status' => 502 ]
			);
		}

		return $fields;
	}

	/**
	 * Remove pre-filled values + hidden inputs from the source HTML
	 * before handing it to the AI middleware. The structural shape of
	 * a form (`<input type="email" name="..." required>`) is enough
	 * for the model to infer the correct SureForms field type; the
	 * concrete `value="..."` payload, hidden CSRF tokens, and
	 * action-URL attributes only widen the data we send out.
	 *
	 * Implementation note: we deliberately do this as a regex pass
	 * rather than round-tripping through `DOMDocument` to avoid an
	 * encoding renormalization step on the JS-supplied bytes (each
	 * `loadHTML` / `saveHTML` pair can mutate whitespace and entity
	 * encoding in ways the AI prompt is sensitive to).
	 *
	 * @since 2.10.0
	 * @param string $html Source HTML markup.
	 * @return string Sanitized markup safe to forward.
	 */
	protected function scrub_html_for_ai( $html ) {
		// Normalize Unicode whitespace separators to plain ASCII space
		// before the regex pass. Without this, a payload such as
		// `<input type=<NBSP>"hidden">` (U+00A0 between attr name and
		// the `=`) sails through every `\s`-based pattern below because
		// PCRE's default `\s` is ASCII-only. Defense-in-depth — the
		// trust boundary is the `manage_options` cap on the route — but
		// the cost is one `str_replace` and the regex patterns stay
		// readable.
		$html = str_replace( [ "\xC2\xA0", "\xE2\x80\x83", "\xE2\x80\x82" ], ' ', $html );

		// Drop hidden inputs entirely.
		$html = preg_replace(
			'/<input\b[^>]*\btype\s*=\s*["\']?hidden["\']?[^>]*>/i',
			'',
			$html
		);
		if ( ! is_string( $html ) ) {
			return '';
		}

		// Strip any remaining `value="..."` / `value='...'` so
		// pre-filled defaults do not leave the site. Attribute names
		// without a value (boolean attrs like `required`) are
		// untouched.
		$html = preg_replace(
			'/\svalue\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i',
			'',
			$html
		);
		if ( ! is_string( $html ) ) {
			return '';
		}

		// Strip the form's `action` attribute — the middleware does
		// not need the host site's internal endpoint URL.
		$html = preg_replace(
			'/(<form\b[^>]*?)\saction\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i',
			'$1',
			$html
		);
		if ( ! is_string( $html ) ) {
			return '';
		}

		// Strip HTML comments. Source authors sometimes leave server-
		// side staging notes, build hashes, or even API keys inside
		// `<!-- ... -->`. The model has no use for them and the regex
		// scrubber's whole purpose is to keep that surface out of the
		// outbound request.
		$html = preg_replace( '/<!--.*?-->/s', '', $html );
		if ( ! is_string( $html ) ) {
			return '';
		}

		// Empty out `<script>` bodies — the structural fact that a
		// `<script>` block exists may be useful for the model to
		// recognize multi-step / handler-driven forms, but the
		// contents (often containing tokens, endpoint URLs, or
		// inline configuration) must not leave the site. Keep the
		// opening/closing tags so the model still sees the shape.
		$html = preg_replace(
			'/(<script\b[^>]*>).*?(<\/script>)/is',
			'$1$2',
			$html
		);
		if ( ! is_string( $html ) ) {
			return '';
		}

		// Empty out `<textarea>` bodies for the same reason as the
		// hidden-input scrub above: pre-filled defaults can be CSRF
		// tokens, server-rendered email addresses, or the user's
		// stored draft. The model only needs to know a textarea is
		// present at this position; the existing content adds nothing
		// to the conversion and is exactly the data class we are
		// trying not to forward.
		$html = preg_replace(
			'/(<textarea\b[^>]*>).*?(<\/textarea>)/is',
			'$1$2',
			$html
		);

		return is_string( $html ) ? $html : '';
	}

	/**
	 * Build the `formMetaData` payload for the Create_Form ability from the
	 * conversion inputs.
	 *
	 * Why every conversion sets an explicit `textColor`:
	 * `Generate_Form_Markup` emits the CSS variables that drive input
	 * borders (`--srfm-color-input-border`, `--srfm-color-input-background`,
	 * etc.) as `hsl( from <textColor> ... )`. Unlike `primaryColor`, the
	 * upstream code does not substitute a fallback when `textColor` is
	 * empty — the generated CSS becomes `hsl( from  h s l / 0.25 )` which
	 * is invalid, so the browser drops the rule and every input renders
	 * with `border: 0`. The user-visible symptom is "the form looks
	 * invisible after conversion". We sidestep the bug by always passing a
	 * non-empty `text_color` for converted forms, falling back to a neutral
	 * near-black when the source form had no inline color we could read.
	 *
	 * Why `showTitle` is forced to false:
	 * The converted form is embedded on a host page via shortcode, where
	 * the user typically already has a heading above the embed. Letting
	 * SureForms render its own form title there produces the duplicate
	 * "Contact Us" header we saw in QA.
	 *
	 * @since 2.10.0
	 * @param string              $submit_text Submit button label extracted from the source form.
	 * @param array<string,mixed> $styling     Inline-style colors the parser was able to read.
	 * @return array<string,mixed>
	 */
	protected function build_form_metadata( $submit_text, $styling ) {
		$form_styling = [
			// Always non-empty — see method docblock for why we cannot let
			// this fall through to the upstream empty-string default.
			'textColor'    => $this->pick_hex( $styling['textColor'] ?? null, '#1E1E1E' ),
			'fieldSpacing' => 'medium',
		];

		// Pass through any inline colors the parser surfaced. We
		// intentionally do NOT inject opinionated defaults for these —
		// SureForms' own defaults (`#046bd2` primary, `#111827` text on
		// primary) are exactly what users get when they create a form via
		// the admin UI, so omitting these keys keeps converted forms
		// visually consistent with hand-built ones.
		if ( ! empty( $styling['primaryColor'] ) ) {
			$hex = sanitize_hex_color( Helper::get_string_value( $styling['primaryColor'] ) );
			if ( $hex ) {
				$form_styling['primaryColor'] = $hex;
			}
		}
		if ( ! empty( $styling['textColorOnPrimary'] ) ) {
			$hex = sanitize_hex_color( Helper::get_string_value( $styling['textColorOnPrimary'] ) );
			if ( $hex ) {
				$form_styling['textColorOnPrimary'] = $hex;
			}
		}

		$meta = [
			'formStyling' => $form_styling,
			'instantForm' => [
				// See method docblock for why this is unconditional.
				'showTitle' => false,
			],
		];

		if ( '' !== $submit_text ) {
			$meta['general'] = [ 'submitText' => $submit_text ];
		}

		// Form background / padding / border-radius are NOT applied through
		// `formMetaData` here — the `Form_Metadata` trait only exposes the
		// colors + field-spacing slice of styling. The full card-look
		// settings are written directly to `_srfm_forms_styling` via
		// `apply_native_card_styling()` after `Create_Form` runs. See the
		// docblock there for why we bypass the trait.

		return $meta;
	}

	/**
	 * Return the first sanitized hex color from a candidate value, falling
	 * back to a default. Centralizes the `sanitize_hex_color() || default`
	 * pattern used in several places in `build_form_metadata()`.
	 *
	 * @since 2.10.0
	 * @param mixed  $value   Candidate hex color.
	 * @param string $default Default to use when the candidate is unusable.
	 * @return string
	 */
	protected function pick_hex( $value, $default ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return $default;
		}
		$sanitized = sanitize_hex_color( $value );
		return $sanitized ? $sanitized : $default;
	}

	/**
	 * Remove keys that exist only to ferry parser context across the HTTP
	 * boundary. `Create_Form::get_input_schema()` sets
	 * `additionalProperties: false`, so passing through `_groupName`,
	 * `_optionValue`, or `confidence` would reject the entire request.
	 *
	 * @since 2.10.0
	 * @param array<int,mixed> $fields Raw parser fields.
	 * @return array<int,array<string,mixed>>
	 */
	protected function strip_internal_hints( $fields ) {
		$internal = [ '_groupName', '_optionValue', '_groupLabel', 'confidence' ];
		$cleaned  = [];

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			foreach ( $internal as $key ) {
				unset( $field[ $key ] );
			}
			$cleaned[] = $field;
		}

		return $cleaned;
	}

	/**
	 * Defensive sweep over post-filter fields. Walks every string leaf
	 * inside every field — including arbitrary-depth `fieldOptions`
	 * arrays — and runs each one through `wp_strip_all_tags` so a
	 * sloppy `srfm_html_form_detector_refine_fields` callback cannot
	 * smuggle raw HTML into a downstream block attribute.
	 *
	 * Form-field attributes are not HTML containers — labels,
	 * placeholders, helptext, and option labels render as text-nodes
	 * (escaped via the block renderer's `esc_html`) or as attribute
	 * values (escaped via `esc_attr`). Stripping tags here loses no
	 * legitimate value and short-circuits the stored-XSS path for
	 * any pro-added property that `Create_Form`'s hardcoded sanitize
	 * list does not cover.
	 *
	 * @since 2.10.0
	 * @param array<int,array<string,mixed>> $fields Filter output.
	 * @return array<int,array<string,mixed>>
	 */
	protected function strip_unsafe_html_in_fields( $fields ) {
		if ( ! is_array( $fields ) ) {
			return [];
		}

		$walker = static function ( $value ) use ( &$walker ) {
			if ( is_string( $value ) ) {
				return wp_strip_all_tags( $value );
			}
			if ( is_array( $value ) ) {
				return array_map( $walker, $value );
			}
			return $value;
		};

		$cleaned = [];
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$cleaned[] = array_map( $walker, $field );
		}
		return $cleaned;
	}
}
