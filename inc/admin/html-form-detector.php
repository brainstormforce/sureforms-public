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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HTML form detector handler.
 *
 * @since x.x.x
 */
class Html_Form_Detector {
	use Get_Instance;

	/**
	 * Confidence level below which we route the raw HTML through the AI
	 * middleware instead of trusting the local parser output.
	 *
	 * @since x.x.x
	 */
	public const AI_FALLBACK_CONFIDENCE = 'low';

	/**
	 * Hard cap on the size of raw HTML accepted by the conversion endpoint.
	 *
	 * Anything larger is almost certainly the entire page rather than a
	 * single `<form>` and would waste an AI roundtrip on noise. Matches the
	 * upper bound the AI middleware tolerates for `query` payloads.
	 *
	 * @since x.x.x
	 */
	public const MAX_HTML_BYTES = 32768;

	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_filter( 'srfm_rest_api_endpoints', [ $this, 'register_rest_endpoint' ] );
	}

	/**
	 * Decide whether the detector script should be loaded for the current request.
	 *
	 * @since x.x.x
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
	 * @since x.x.x
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
				'dependencies' => [ 'wp-api-fetch', 'wp-blocks', 'wp-data', 'wp-dom-ready', 'wp-i18n' ],
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
	 * @since x.x.x
	 * @param array<string,array<string,mixed>> $endpoints Existing endpoints map.
	 * @return array<string,array<string,mixed>>
	 */
	public function register_rest_endpoint( $endpoints ) {
		if ( ! is_array( $endpoints ) ) {
			$endpoints = [];
		}

		$endpoints['convert-html-form'] = [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_convert_html_form' ],
			'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
			'args'                => [
				'parsed_fields' => [
					'required'    => false,
					'description' => __( 'Array of fields produced by the editor-side parser.', 'sureforms' ),
				],
				'submit_text'   => [
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
					'default'           => '',
				],
				'confidence'    => [
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
					'default'           => 'high',
				],
				'html'          => [
					'required'    => false,
					'description' => __( 'Raw HTML of the source <form>. Required when parser confidence is low so we can hand the markup to the AI middleware.', 'sureforms' ),
				],
				'form_title'    => [
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
					'default'           => '',
				],
				'styling'       => [
					'required'    => false,
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
	 * @since x.x.x
	 * @param WP_REST_Request $request REST request.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function handle_convert_html_form( $request ) {
		$nonce = Helper::get_string_value( $request->get_header( 'X-WP-Nonce' ) );
		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return new WP_Error(
				'srfm_html_convert_nonce_failed',
				__( 'Security verification failed. Please refresh the page and try again.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		// Cap is enforced again inside Create_Form via the `manage_options`
		// capability — this is an explicit second check so a malformed
		// permission_callback override never reaches the endpoint.
		if ( ! Helper::current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'srfm_html_convert_forbidden',
				__( 'You are not allowed to convert HTML forms.', 'sureforms' ),
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
		 * @since x.x.x
		 * @param array<int,array<string,mixed>> $clean_fields Sanitized field list ready for Create_Form.
		 * @param string                         $raw_html     Original HTML of the source `<form>` block.
		 * @param string                         $confidence   Parser confidence (`high`/`medium`/`low`).
		 */
		$clean_fields = apply_filters( 'srfm_html_form_detector_refine_fields', $clean_fields, $raw_html, $confidence );

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
			 * @since x.x.x
			 * @param int                 $form_id  Newly-created SureForms form ID.
			 * @param array<string,mixed> $styling  Parser styling descriptor (inline-style hints).
			 * @param string              $raw_html Original HTML of the source `<form>` block.
			 */
			do_action( 'srfm_html_form_detector_after_styling', $form_id, $styling_in, $raw_html );
		}

		$result['used_ai'] = $used_ai;
		return $result;
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
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
	 * @param string $html Raw HTML containing the source `<form>`.
	 * @return array<int,array<string,mixed>>|\WP_Error
	 */
	protected function extract_fields_via_ai( $html ) {
		$query = sprintf(
			// translators: %s = raw HTML markup of a <form> element.
			__( 'Convert the following raw HTML form into a SureForms field schema. Preserve field types, labels, the required attribute, and any select/radio/checkbox options. Do not invent fields that are not present in the markup. HTML: %s', 'sureforms' ),
			$html
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

		return $response['form']['formFields'];
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
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
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
}
