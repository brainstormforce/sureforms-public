<?php
/**
 * WPForms importer — translates the JSON form schema stored in the
 * `wpforms` CPT's `post_content` into SureForms block markup.
 *
 * Storage shape: a wp-slashed JSON blob (see WPForms' `wpforms_encode()`).
 * Each field is keyed by integer id and carries `type`, `label`, `required`,
 * plus type-specific keys. We iterate the field list and dispatch to a
 * `Block_Templates::*` emitter — extending the same four filter seams the
 * CF7 importer added in PR #2789 so SureForms Pro can plug in extra
 * mappings for Pro-only fields (date-time, signature, repeater, …).
 *
 * @package sureforms
 * @since   2.11.0
 */

namespace SRFM\Inc\Migrator\Importers;

use SRFM\Inc\Migrator\Base_Migrator;
use SRFM\Inc\Migrator\Block_Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wpforms_Importer
 *
 * @since 2.11.0
 */
class Wpforms_Importer extends Base_Migrator {
	/**
	 * Operator translation table — WPForms condition operator → SureForms
	 * operator slug (per Pro's `conditional-logic-options.json`). Rules using
	 * operators absent from this map are dropped during migration with a
	 * one-line warning.
	 */
	private const OPERATOR_MAP = [
		'==' => '==',
		'!=' => '!=',
		'c'  => 'includes',
		'!c' => '!includes',
		'^'  => 'startWith',
		'~'  => 'endWith',
		'e'  => 'null',
		'!e' => '!null',
	];

	/**
	 * Maximum layout/repeater nesting depth translated before bailing. Real
	 * WPForms layouts can't nest, so this only guards crafted/corrupt JSON.
	 */
	private const MAX_NESTING_DEPTH = 5;

	/**
	 * Conditional-logic payload accumulated while emitting field blocks for
	 * the form currently being built. Reset per form in `build_form_content`.
	 * Each entry holds the source-side target field id, the show/hide
	 * action, the unconverted rules, and a choice-id map.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $conditional_logic = [];

	/**
	 * Mapping from WPForms field id → assembled SureForms block_id for the
	 * current form. Used to rewrite conditional-logic rule targets (which
	 * reference WPForms ids) onto SureForms block ids in `get_form_metas`.
	 *
	 * @var array<string,string>
	 */
	private $field_id_to_block_id = [];

	/**
	 * Mapping from WPForms field id → block type bucket (`default`, `text`,
	 * `number`, `list`) — required by SureForms Pro's CL schema so its rule
	 * editor knows which operator set to expose.
	 *
	 * @var array<string,string>
	 */
	private $field_id_to_block_type = [];

	/**
	 * Mapping from WPForms choice-field id → [ choice key => SureForms option
	 * label ]. WPForms stores a conditional rule's value as the choice KEY, but
	 * SureForms' CL engine compares against the option label — so convert_rule()
	 * re-keys list-source rule values through this map.
	 *
	 * @var array<string,array<string,string>>
	 */
	private $field_id_to_choices = [];

	/**
	 * Submit-button text discovered while parsing form settings; threaded
	 * through to `get_form_metas` as `_srfm_submit_button_text`.
	 *
	 * @var string
	 */
	private $submit_label = '';

	/**
	 * Confirmation message + recipient discovered while parsing settings.
	 *
	 * @var array<string,mixed>
	 */
	private $form_settings = [];

	/**
	 * Set source identifiers.
	 *
	 * @since 2.11.0
	 */
	public function __construct() {
		$this->key   = 'wpforms';
		$this->title = __( 'WPForms', 'sureforms' );
	}

	/**
	 * Whether WPForms (Lite or Pro) is currently installed/active.
	 *
	 * @since 2.11.0
	 *
	 * @return bool
	 */
	public function exist() {
		return class_exists( 'WPForms' ) || defined( 'WPFORMS_VERSION' );
	}

	/**
	 * Map of WPForms field-type slug → `Block_Templates` method name for the
	 * Lite-only fields. Pro and addon fields are added by Pro's
	 * `Migrator_WPForms` subscriber via the `srfm_migrator_tag_to_template_map`
	 * filter — the same filter the CF7 importer uses.
	 *
	 * Public so tests can read it; Pro subscribers extend it through the
	 * filter, not through inheritance.
	 *
	 * @since 2.11.0
	 *
	 * @return array<string,string>
	 */
	public function default_field_map() {
		return [
			'text'          => 'input',
			'textarea'      => 'textarea',
			'email'         => 'email',
			'number'        => 'number',
			'number-slider' => 'slider',
			'select'        => 'dropdown',
			'radio'         => 'multi_choice',
			'checkbox'      => 'multi_choice',
			'gdpr-checkbox' => 'gdpr',
		];
	}

	/**
	 * Fetch all `wpforms` posts. The CPT is `public=false, show_ui=false` so we
	 * have to query it explicitly.
	 *
	 * @since 2.11.0
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_source_forms() {
		if ( ! $this->exist() ) {
			return [];
		}
		$posts = get_posts(
			[
				'post_type'      => 'wpforms',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			]
		);
		$out   = [];
		foreach ( $posts as $post ) {
			$out[] = [
				'id'           => (int) $post->ID,
				'name'         => $post->post_title,
				'post_content' => $post->post_content,
			];
		}
		return $out;
	}

	/**
	 * Return the WP post id for a source descriptor.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $form Source descriptor.
	 * @return int
	 */
	protected function get_source_form_id( array $form ) {
		return isset( $form['id'] ) && is_numeric( $form['id'] ) ? (int) $form['id'] : 0;
	}

	/**
	 * Return the WPForms form title for a source descriptor.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $form Source descriptor.
	 * @return string
	 */
	protected function get_source_form_name( array $form ) {
		$name = $this->str_arg( $form, 'name' );
		return '' !== $name ? $name : __( '(untitled WPForms form)', 'sureforms' );
	}

	/**
	 * Decode the JSON form schema and iterate fields, emitting SureForms
	 * block markup. Sub-fields of composite types (Name, Layout, Repeater)
	 * are handled inline rather than via the field map.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $form Source descriptor.
	 * @return string
	 */
	protected function build_form_content( array $form ) {
		// Reset per-form accumulators — used_slugs lives on Base_Migrator.
		$this->used_slugs             = [];
		$this->conditional_logic      = [];
		$this->field_id_to_block_id   = [];
		$this->field_id_to_block_type = [];
		$this->field_id_to_choices    = [];
		$this->submit_label           = '';
		$this->form_settings          = [];

		$data = $this->parse_form_json( $form );
		if ( empty( $data ) ) {
			return '';
		}

		// Allow add-on subscribers to rewrite the form-data array before we
		// iterate it (e.g. expand a Multi-Step addon section into synthetic
		// page-break markers, much like CF7 importer's [step] preprocessing).
		/**
		 * Filter the parsed WPForms form-data array before block emission.
		 *
		 * @since 2.11.0
		 *
		 * @param array<string,mixed> $data Decoded form_data.
		 * @param string              $key  Migrator source key (`wpforms`).
		 * @param array<string,mixed> $form Source descriptor.
		 */
		$data = (array) apply_filters( 'srfm_migrator_preprocess_template', $data, $this->key, $form );

		// Stash form settings so get_form_metas can read them — WPForms keeps
		// notifications/confirmations/submit_text under `settings`.
		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			$this->form_settings = $data['settings'];
			$this->submit_label  = $this->str_arg( $data['settings'], 'submit_text' );
		}

		$fields = isset( $data['fields'] ) && is_array( $data['fields'] ) ? $data['fields'] : [];
		// WPForms keys fields by integer id but the JSON is associative —
		// preserve source order by iterating with foreach (PHP keeps insertion
		// order for assoc arrays).
		$markup = '';
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$markup .= $this->translate_field( $field );
		}
		return $markup;
	}

	/**
	 * Build the SureForms meta payload — email notifications, confirmation,
	 * submit text, and the conditional-logic blob assembled during emission.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $form Source descriptor.
	 * @return array<string,mixed>
	 */
	protected function get_form_metas( array $form ) {
		unset( $form );

		$metas = [
			'_srfm_submit_button_text' => '' !== $this->submit_label ? $this->submit_label : __( 'Submit', 'sureforms' ),
		];

		$confirmation = $this->translate_confirmation( $this->form_settings );
		if ( ! empty( $confirmation ) ) {
			$metas['_srfm_form_confirmation'] = $confirmation;
		}

		$email = $this->translate_email_notifications( $this->form_settings );
		if ( ! empty( $email ) ) {
			$metas['_srfm_email_notification'] = $email;
		}

		// `_srfm_conditional_logic` is registered by SureForms Pro
		// (inc/extensions/conditional-logic.php). When Pro is inactive the meta
		// is still written but simply sits unused until Pro is enabled — harmless.
		$cl_meta = $this->assemble_conditional_logic_meta();
		if ( ! empty( $cl_meta ) ) {
			$metas['_srfm_conditional_logic'] = $cl_meta;
		}

		return $metas;
	}

	/**
	 * Decode the JSON form schema from `post_content`. Mirrors WPForms' own
	 * `wpforms_decode()` which calls `json_decode()` directly — `post_content`
	 * arrives from `get_post_field()` already unslashed, so a second pass
	 * would corrupt the escaped quotes inside string values (e.g.
	 * `replyto:"{field_id=\"1\"}"`).
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $form Source descriptor.
	 * @return array<string,mixed>
	 */
	private function parse_form_json( array $form ) {
		$raw = $this->str_arg( $form, 'post_content' );
		if ( '' === $raw ) {
			return [];
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Translate a single WPForms field array into block markup.
	 *
	 * Branches:
	 *  - `name` — composite, emits 1–3 `srfm/input` blocks based on `format`.
	 *  - `layout` — emits `core/columns` and recurses children.
	 *  - everything else — looked up in the field map (extended via filter),
	 *    dispatched via `Base_Migrator::dispatch_template()`, with the
	 *    `srfm_migrator_block_template` filter giving Pro subscribers a
	 *    chance for unmapped types before we flag them unsupported.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field WPForms field array.
	 * @param int                 $depth Current nesting depth (layout/repeater recursion).
	 * @return string
	 */
	private function translate_field( array $field, $depth = 0 ) {
		// Guard against a crafted/corrupt self-nesting layout/repeater chain that
		// would otherwise recurse unbounded during an authenticated import.
		if ( $depth > self::MAX_NESTING_DEPTH ) {
			$this->note_unsupported( $this->str_arg( $field, 'label', $this->str_arg( $field, 'type', 'nested field' ) ) );
			return '';
		}

		$type = $this->str_arg( $field, 'type' );
		if ( '' === $type ) {
			return '';
		}

		if ( 'name' === $type ) {
			return $this->translate_name_field( $field );
		}

		if ( 'layout' === $type ) {
			return $this->translate_layout_field( $field, $depth );
		}

		if ( 'repeater' === $type ) {
			return $this->translate_repeater_field( $field, $depth );
		}

		// captcha is rendered form-level in SureForms, never as a block.
		if ( 0 === strpos( $type, 'captcha_' ) || 'captcha' === $type ) {
			return '';
		}

		/**
		 * Filter the WPForms-field-type → template-method map.
		 *
		 * Pro subscribers (Migrator_WPForms) overlay extra entries for fields
		 * Free can't render on its own (date-time, file-upload, signature,
		 * repeater, …). The filter is the same one CF7 uses — discriminate by
		 * the second parameter `$key`.
		 *
		 * @since 2.11.0
		 *
		 * @param array<string,string> $map WPForms field type → method name.
		 * @param string               $key Migrator source key (`wpforms`).
		 */
		$map = (array) apply_filters( 'srfm_migrator_tag_to_template_map', $this->default_field_map(), $this->key );

		// Pre-build the SureForms block args so the dispatch_template + filter
		// path both receive the same shape (label, required, choices, …).
		$args   = $this->build_block_args( $field );
		$method = $map[ $type ] ?? '';

		if ( '' === $method ) {
			// No mapping for this type — give subscribers a chance, otherwise
			// flag it as unsupported.
			$markup = (string) apply_filters( 'srfm_migrator_block_template', '', $type, $args, $this->key );
			if ( '' === $markup ) {
				$this->note_unsupported( $this->str_arg( $field, 'label', $type ) );
				return '';
			}
			return $this->capture_field_metadata( $field, $args, $markup, $type );
		}

		$markup = $this->dispatch_template( $method, $args );
		if ( '' === $markup ) {
			// Subscriber-only emitter (e.g. Pro `date_picker`).
			$markup = (string) apply_filters( 'srfm_migrator_block_template', '', $method, $args, $this->key );
		}
		if ( '' === $markup ) {
			$this->note_unsupported( $this->str_arg( $field, 'label', $type ) );
			return '';
		}

		return $this->capture_field_metadata( $field, $args, $markup, $method );
	}

	/**
	 * Build SureForms block args from a WPForms field array. Common keys are
	 * mapped 1:1; type-specific keys (choices, min/max, format, …) are added
	 * conditionally.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field WPForms field.
	 * @return array<string,mixed>
	 */
	private function build_block_args( array $field ) {
		$type      = $this->str_arg( $field, 'type' );
		$label     = $this->str_arg( $field, 'label' );
		$slug_seed = '' !== $label ? $label : $type;
		$slug      = $this->reserve_slug( $slug_seed );

		$args = [
			'label'         => $label,
			'placeholder'   => $this->str_arg( $field, 'placeholder' ),
			'default_value' => $this->str_arg( $field, 'default_value' ),
			'required'      => ! empty( $field['required'] ),
			'help'          => $this->str_arg( $field, 'description' ),
			'slug'          => $slug,
		];

		switch ( $type ) {
			case 'textarea':
				$args['rows']       = $this->size_to_rows( $this->str_arg( $field, 'size' ) );
				$args['max_length'] = $this->resolve_limit( $field );
				break;
			case 'text':
				$args['max_length'] = $this->resolve_limit( $field );
				break;
			case 'number':
			case 'number-slider':
				// Preserve decimals — a WPForms slider step of 0.5 must not be
				// truncated to 0. `+ 0` yields an int for whole numbers, float otherwise.
				if ( isset( $field['min'] ) && is_numeric( $field['min'] ) ) {
					$args['min'] = $field['min'] + 0;
				}
				if ( isset( $field['max'] ) && is_numeric( $field['max'] ) ) {
					$args['max'] = $field['max'] + 0;
				}
				if ( isset( $field['step'] ) && is_numeric( $field['step'] ) ) {
					$args['step'] = $field['step'] + 0;
				}
				if ( '' !== $this->str_arg( $field, 'default_value' ) ) {
					$args['default_value'] = $this->str_arg( $field, 'default_value' );
				}
				break;
			case 'select':
			case 'radio':
			case 'checkbox':
				$options                = $this->translate_choices( $field );
				$args['options']        = $options['options'];
				$args['preselected']    = $options['preselected'];
				$args['_choice_id_map'] = $options['id_map']; // Internal — capture_field_metadata reads + strips it.
				$args['multiple']       = 'checkbox' === $type
					? empty( $field['disclaimer_format'] ) // Disclaimer = single-checkbox semantics.
					: ! empty( $field['multiple'] );
				break;
			case 'email':
				// WPForms' "Enable Email Confirmation" (confirmation=1) is a
				// second input on the same field; srfm/email models that
				// natively via isConfirmEmail, so enable the option on the one
				// block instead of dropping it or emitting a duplicate field.
				if ( ! empty( $field['confirmation'] ) ) {
					$args['confirm_email'] = true;
				}
				break;
			case 'date-time':
				$args['format']      = $this->str_arg( $field, 'format', 'date' );
				$args['date_format'] = $this->str_arg( $field, 'date_format', 'mm/dd/yyyy' );
				$args['time_format'] = $this->str_arg( $field, 'time_format' );
				break;
			case 'rating':
				$args['icon']  = $this->str_arg( $field, 'icon', 'star' );
				$args['scale'] = isset( $field['scale'] ) && is_numeric( $field['scale'] ) ? (int) $field['scale'] : 5;
				break;
			case 'net_promoter_score':
				$args['low_label']  = $this->str_arg( $field, 'nps_low_label' );
				$args['high_label'] = $this->str_arg( $field, 'nps_high_label' );
				break;
			case 'signature':
				$args['ink_color'] = $this->str_arg( $field, 'ink_color' );
				break;
			case 'html':
				$args['content'] = $this->str_arg( $field, 'code' );
				break;
			case 'content':
				$args['content'] = $this->str_arg( $field, 'content' );
				break;
			case 'divider':
				// label + help are already in $args; nothing extra to carry.
				break;
			case 'file-upload':
				$exts = $this->str_arg( $field, 'extensions' );
				if ( '' !== $exts ) {
					$args['allowed_formats'] = array_values(
						array_filter(
							array_map( 'trim', explode( ',', $exts ) ),
							static function ( $v ) {
								return '' !== $v;
							}
						)
					);
				}
				if ( isset( $field['max_size'] ) && is_numeric( $field['max_size'] ) ) {
					$args['file_size_limit'] = (int) $field['max_size'];
				}
				if ( isset( $field['max_file_number'] ) && is_numeric( $field['max_file_number'] ) ) {
					$args['max_files'] = (int) $field['max_file_number'];
				}
				$args['multiple'] = isset( $field['max_file_number'] ) && is_numeric( $field['max_file_number'] ) && (int) $field['max_file_number'] > 1;
				break;
			case 'hidden':
			case 'internal-information':
				$args['default_value'] = $this->str_arg( $field, 'default_value' );
				if ( '' === $args['default_value'] ) {
					$args['default_value'] = $this->str_arg( $field, 'code' );
				}
				break;
			case 'phone':
				// WPForms phone format `smart` / `us` / `international` — SureForms
				// phone has its own intl/US toggle; we carry the raw value through
				// and let the Pro emitter (or default) interpret.
				$args['format'] = $this->str_arg( $field, 'format', 'smart' );
				break;
			case 'address':
				$args['format'] = $this->str_arg( $field, 'format', 'us' );
				break;
			case 'pagebreak':
				// Title precedes the page-break block heading; map it onto label.
				$title = $this->str_arg( $field, 'title' );
				if ( '' !== $title ) {
					$args['label'] = $title;
				}
				break;
		}

		return $args;
	}

	/**
	 * Translate WPForms' Name composite (`format=simple|first-last|first-middle-last`)
	 * into 1–3 `srfm/input` blocks. SureForms has no dedicated Name block.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field Name field.
	 * @return string
	 */
	private function translate_name_field( array $field ) {
		$format = $this->str_arg( $field, 'format', 'first-last' );
		$base   = $this->str_arg( $field, 'label', 'Name' );
		$req    = ! empty( $field['required'] );

		if ( 'simple' === $format ) {
			$markup = Block_Templates::input(
				[
					'label'         => $base,
					'placeholder'   => $this->str_arg( $field, 'simple_placeholder' ),
					'default_value' => $this->str_arg( $field, 'simple_default' ),
					'required'      => $req,
					'slug'          => $this->reserve_slug( $base ),
				]
			);
			// Register field id → first sub-block id so a Name field can be a
			// conditional-logic source/target (capture_field_metadata reads the
			// first block_id from the markup and any rules off the field).
			return $this->capture_field_metadata( $field, [], $markup, 'name' );
		}

		$parts = 'first-middle-last' === $format
			? [ 'first', 'middle', 'last' ]
			: [ 'first', 'last' ];

		$markup = '';
		foreach ( $parts as $part ) {
			$markup .= Block_Templates::input(
				[
					'label'         => $base . ' (' . ucfirst( $part ) . ')',
					'placeholder'   => $this->str_arg( $field, $part . '_placeholder' ),
					'default_value' => $this->str_arg( $field, $part . '_default' ),
					'required'      => $req,
					'slug'          => $this->reserve_slug( $base . '-' . $part ),
				]
			);
		}
		// Map the composite field to its first sub-block id so CL rules that
		// target the Name field resolve to the first input.
		return $this->capture_field_metadata( $field, [], $markup, 'name' );
	}

	/**
	 * Translate WPForms' Layout container — emit a `core/columns` block with
	 * width presets from the WPForms `preset` (50-50, 33-33-33, …) and
	 * recurse the nested children into each column.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field Layout field.
	 * @param int                 $depth Current nesting depth.
	 * @return string
	 */
	private function translate_layout_field( array $field, $depth = 0 ) {
		$columns = isset( $field['columns'] ) && is_array( $field['columns'] ) ? $field['columns'] : [];
		if ( empty( $columns ) ) {
			return '';
		}
		$out = "<!-- wp:columns -->\n<div class=\"wp-block-columns\">";
		foreach ( $columns as $column ) {
			if ( ! is_array( $column ) ) {
				continue;
			}
			$children = isset( $column['fields'] ) && is_array( $column['fields'] ) ? $column['fields'] : [];
			$inner    = '';
			foreach ( $children as $child ) {
				if ( is_array( $child ) ) {
					$inner .= $this->translate_field( $child, $depth + 1 );
				}
			}
			$out .= "\n<!-- wp:column -->\n<div class=\"wp-block-column\">{$inner}</div>\n<!-- /wp:column -->";
		}
		$out .= "\n</div>\n<!-- /wp:columns -->\n";
		return $out;
	}

	/**
	 * Translate WPForms' Repeater container — recurse into each column's
	 * child fields, assemble their markup, then hand it to the Pro
	 * `repeater_container` emitter as `children` so it can wrap with
	 * Gutenberg innerBlocks markers.
	 *
	 * When Pro is not active, the dispatch falls through to the
	 * `srfm_migrator_block_template` filter and (since no subscriber
	 * answers) the field is flagged as unsupported — the children are
	 * still tracked so the warning is meaningful.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field WPForms repeater field.
	 * @param int                 $depth Current nesting depth.
	 * @return string
	 */
	private function translate_repeater_field( array $field, $depth = 0 ) {
		$columns  = isset( $field['columns'] ) && is_array( $field['columns'] ) ? $field['columns'] : [];
		$children = '';
		foreach ( $columns as $column ) {
			if ( ! is_array( $column ) ) {
				continue;
			}
			$child_fields = isset( $column['fields'] ) && is_array( $column['fields'] ) ? $column['fields'] : [];
			foreach ( $child_fields as $child ) {
				if ( is_array( $child ) ) {
					$children .= $this->translate_field( $child, $depth + 1 );
				}
			}
		}

		$args = [
			'label'    => $this->str_arg( $field, 'label', 'Repeater Field' ),
			'help'     => $this->str_arg( $field, 'description' ),
			'min_rows' => isset( $field['min_rows'] ) && is_numeric( $field['min_rows'] ) ? (int) $field['min_rows'] : null,
			'max_rows' => isset( $field['max_rows'] ) && is_numeric( $field['max_rows'] ) ? (int) $field['max_rows'] : null,
			'slug'     => $this->reserve_slug( $this->str_arg( $field, 'label', 'repeater' ) ),
			'children' => $children,
		];

		$markup = (string) apply_filters( 'srfm_migrator_block_template', '', 'repeater_container', $args, $this->key );
		if ( '' === $markup ) {
			$this->note_unsupported( $this->str_arg( $field, 'label', 'Repeater' ) );
			return $children; // Fall back to inlining the children at top level so data isn't lost.
		}
		return $markup;
	}

	/**
	 * Translate WPForms' 1-indexed choices array into SureForms options +
	 * `preselected` defaults + an id-map (WPForms choice id → SureForms
	 * option index). The id-map is consumed by `translate_conditional_logic`
	 * to rewrite rule values.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field WPForms field.
	 * @return array{options: array<int,array<string,string>>, preselected: array<int,int>, id_map: array<string,string>}
	 */
	private function translate_choices( array $field ) {
		$choices     = isset( $field['choices'] ) && is_array( $field['choices'] ) ? $field['choices'] : [];
		$show_values = ! empty( $field['show_values'] );
		$options     = [];
		$preselected = [];
		$id_map      = [];
		$i           = 0;

		foreach ( $choices as $cid => $choice ) {
			if ( ! is_array( $choice ) ) {
				continue;
			}
			$label     = $this->str_arg( $choice, 'label' );
			$value     = $show_values && ! empty( $choice['value'] ) ? (string) $choice['value'] : $label;
			$entry     = [ 'label' => $value ];
			$options[] = $entry;
			// Map the WPForms choice KEY to the SureForms option label. WPForms
			// conditional rules reference a choice by its key; SureForms' CL
			// engine compares against the option label (= $value here), so this
			// lets convert_rule() rewrite the rule value to one that matches.
			$id_map[ (string) $cid ] = $value;
			// SureForms' dropdown / multi-choice render preselected entries by
			// matching the option *index*, not the label — see
			// inc/fields/dropdown-markup.php:162. Push the integer index here.
			if ( ! empty( $choice['default'] ) ) {
				$preselected[] = $i;
			}
			++$i;
		}

		if ( empty( $options ) ) {
			$options = [ [ 'label' => 'Option 1' ] ];
		}

		return [
			'options'     => $options,
			'preselected' => $preselected,
			'id_map'      => $id_map,
		];
	}

	/**
	 * Resolve the `limit_count` for a text/textarea field — only the
	 * `characters` mode maps cleanly; `words` mode is noted as a partial
	 * loss and converted to a generous character heuristic (count × 7).
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field WPForms field.
	 * @return int|null
	 */
	private function resolve_limit( array $field ) {
		if ( empty( $field['limit_enabled'] ) ) {
			return null;
		}
		$count = isset( $field['limit_count'] ) && is_numeric( $field['limit_count'] ) ? (int) $field['limit_count'] : 0;
		if ( $count <= 0 ) {
			return null;
		}
		$mode = $this->str_arg( $field, 'limit_mode', 'characters' );
		if ( 'words' === $mode ) {
			$this->note_unsupported( $this->str_arg( $field, 'label' ) . ' (' . __( 'word limit converted to character heuristic', 'sureforms' ) . ')' );
			return $count * 7;
		}
		return $count;
	}

	/**
	 * Map WPForms field `size` → SureForms textarea row count.
	 *
	 * @since 2.11.0
	 *
	 * @param string $size 'small', 'medium', 'large'.
	 * @return int
	 */
	private function size_to_rows( $size ) {
		switch ( $size ) {
			case 'small':
				return 3;
			case 'large':
				return 8;
			case 'medium':
			default:
				return 5;
		}
	}

	/**
	 * After a field's block markup is built, extract its block_id from the
	 * markup and stash the WPForms-id → block_id mapping. Also stages the
	 * field's conditional logic for the get_form_metas payload.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field    Source field.
	 * @param array<string,mixed> $args     Final block args (used to resolve choice id-map).
	 * @param string              $markup   Assembled block markup.
	 * @param string              $type_key WPForms type or method name (used as block-type bucket).
	 * @return string Markup (unchanged).
	 */
	private function capture_field_metadata( array $field, array $args, $markup, $type_key ) {
		$field_id = $this->str_arg( $field, 'id' );
		if ( '' !== $field_id && preg_match( '/"block_id":"([a-f0-9]{8})"/', $markup, $m ) ) {
			$this->field_id_to_block_id[ $field_id ]   = $m[1];
			$this->field_id_to_block_type[ $field_id ] = $this->block_type_bucket( $type_key );
			// Remember a choice field's [ key => option label ] map so a rule that
			// uses this field as a CL source can have its value re-keyed.
			if ( ! empty( $args['_choice_id_map'] ) && is_array( $args['_choice_id_map'] ) ) {
				$this->field_id_to_choices[ $field_id ] = $args['_choice_id_map'];
			}
		}

		if ( ! empty( $field['conditional_logic'] ) && ! empty( $field['conditionals'] ) && is_array( $field['conditionals'] ) ) {
			$this->conditional_logic[] = [
				'target_field_id' => $field_id,
				'action'          => $this->str_arg( $field, 'conditional_type', 'show' ),
				'rules'           => $field['conditionals'],
			];
		}

		return $markup;
	}

	/**
	 * Coarse bucket for SureForms' CL editor — `default` for text-like fields,
	 * `number` for numeric, `list` for choice fields.
	 *
	 * @since 2.11.0
	 *
	 * @param string $type WPForms type or template-method name.
	 * @return string
	 */
	private function block_type_bucket( $type ) {
		if ( in_array( $type, [ 'number', 'number-slider', 'slider' ], true ) ) {
			return 'number';
		}
		if ( in_array( $type, [ 'select', 'radio', 'checkbox', 'multi_choice', 'dropdown' ], true ) ) {
			return 'list';
		}
		return 'default';
	}

	/**
	 * Build the `_srfm_conditional_logic` post-meta payload from the
	 * accumulated rules. Source-side field ids are rewritten to
	 * SureForms block ids; unsupported operators are dropped with a
	 * single warning per rule.
	 *
	 * @since 2.11.0
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function assemble_conditional_logic_meta() {
		$out = [];
		foreach ( $this->conditional_logic as $entry ) {
			$target_field_id = $this->str_arg( $entry, 'target_field_id' );
			$target_block_id = $this->field_id_to_block_id[ $target_field_id ] ?? '';
			if ( '' === $target_block_id ) {
				continue;
			}
			$rules = isset( $entry['rules'] ) && is_array( $entry['rules'] ) ? $entry['rules'] : [];
			$logic = [];
			foreach ( $rules as $group ) {
				if ( ! is_array( $group ) ) {
					continue;
				}
				$converted_group = [];
				foreach ( $group as $rule ) {
					if ( ! is_array( $rule ) ) {
						continue;
					}
					$converted = $this->convert_rule( $rule );
					if ( null !== $converted ) {
						$converted_group[] = $converted;
					}
				}
				if ( ! empty( $converted_group ) ) {
					$logic[] = $converted_group;
				}
			}
			if ( empty( $logic ) ) {
				continue;
			}
			$action = $this->str_arg( $entry, 'action', 'show' );
			$out[]  = [
				$target_block_id => [
					'action' => '' !== $action ? $action : 'show',
					'logic'  => $logic,
				],
			];
		}
		return $out;
	}

	/**
	 * Convert one WPForms conditional-logic rule into the SureForms shape.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $rule WPForms rule (`{ field, operator, value }`).
	 * @return array<string,string>|null Null when the rule references an
	 *                                   unknown source field or uses an
	 *                                   unsupported operator.
	 */
	private function convert_rule( array $rule ) {
		$src_field_id = $this->str_arg( $rule, 'field', '' );
		$source_block = $this->field_id_to_block_id[ $src_field_id ] ?? '';
		if ( '' === $source_block ) {
			return null;
		}
		$op = $this->str_arg( $rule, 'operator', '==' );
		if ( ! isset( self::OPERATOR_MAP[ $op ] ) ) {
			return null;
		}
		$operator = self::OPERATOR_MAP[ $op ];
		$bucket   = $this->field_id_to_block_type[ $src_field_id ] ?? 'default';
		// Reconcile the operator with the field's bucket — SureForms' CL editor
		// only evaluates a restricted operator set per bucket, so a list/number
		// field with a text-style operator is down-bucketed to `default` (or the
		// rule is dropped when no bucket supports it).
		$bucket = $this->resolve_cl_bucket( $operator, $bucket );
		if ( '' === $bucket ) {
			return null;
		}

		// WPForms stores a choice source's rule value as the choice KEY (e.g.
		// "2"); SureForms' CL engine compares against the option label, so
		// re-key it through the source field's [ key => label ] map. Text/number
		// sources have no choice map and pass through unchanged.
		$value      = $this->str_arg( $rule, 'value', '' );
		$choice_map = $this->field_id_to_choices[ $src_field_id ] ?? [];
		if ( ! empty( $choice_map ) && array_key_exists( $value, $choice_map ) ) {
			$value = (string) $choice_map[ $value ];
		}

		return [
			'field'    => $source_block,
			'operator' => $operator,
			'value'    => $value,
			'type'     => $bucket,
		];
	}

	/**
	 * Translate WPForms' first email notification (the only one SureForms
	 * supports) into the `_srfm_email_notification` shape.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $settings WPForms settings array.
	 * @return array<int,array<string,mixed>>
	 */
	private function translate_email_notifications( array $settings ) {
		$notifications = isset( $settings['notifications'] ) && is_array( $settings['notifications'] ) ? $settings['notifications'] : [];
		if ( empty( $notifications ) ) {
			return [];
		}
		// SureForms supports a single notification; surface the dropped extras.
		if ( count( $notifications ) > 1 ) {
			$this->note_unsupported( __( 'Additional email notifications (only the first was imported)', 'sureforms' ) );
		}
		$first = reset( $notifications );
		if ( ! is_array( $first ) ) {
			return [];
		}
		$to_email = $this->str_arg( $first, 'email' );
		if ( '' === $to_email ) {
			$admin    = get_option( 'admin_email' );
			$to_email = is_string( $admin ) ? $admin : '';
		}

		return [
			[
				'status'         => true,
				'name'           => $this->str_arg( $first, 'notification_name', __( 'Admin Notification', 'sureforms' ) ),
				'email_to'       => $to_email,
				'subject'        => $this->str_arg( $first, 'subject', __( 'New form submission', 'sureforms' ) ),
				'email_reply_to' => $this->str_arg( $first, 'replyto', '{admin_email}' ),
				'email_body'     => $this->str_arg( $first, 'message', '{all_fields}' ),
			],
		];
	}

	/**
	 * Translate WPForms' first confirmation entry into the SureForms
	 * `_srfm_form_confirmation` shape. Falls back to the canonical default
	 * confirmation when WPForms didn't customize it.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $settings WPForms settings array.
	 * @return array<int,array<string,mixed>>
	 */
	private function translate_confirmation( array $settings ) {
		$confirmations = isset( $settings['confirmations'] ) && is_array( $settings['confirmations'] ) ? $settings['confirmations'] : [];
		// SureForms supports a single confirmation; surface the dropped extras.
		if ( count( $confirmations ) > 1 ) {
			$this->note_unsupported( __( 'Additional confirmations (only the first was imported)', 'sureforms' ) );
		}
		$first = is_array( reset( $confirmations ) ) ? reset( $confirmations ) : [];
		$type  = $this->str_arg( $first, 'type', 'message' );

		$entry = [
			'confirmation_type' => 'same page',
			'message'           => $this->default_confirmation_message(),
			'page_url'          => '',
		];

		if ( 'message' === $type && ! empty( $first['message'] ) ) {
			$entry['message'] = wp_kses_post( (string) $first['message'] );
		}
		if ( 'redirect' === $type && ! empty( $first['redirect'] ) ) {
			$entry['confirmation_type'] = 'different page';
			$entry['page_url']          = esc_url_raw( $this->str_arg( $first, 'redirect' ) );
		}

		return [ $entry ];
	}

	/**
	 * Coerce a mixed array entry to a string. PHPStan level 9 rejects a
	 * direct `(string) $mixed` cast; this helper centralises the
	 * `is_string` / `is_scalar` guards so the per-field translators above
	 * stay readable.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $arr     Source array.
	 * @param string              $key     Key to read.
	 * @param string              $default Default when missing or non-scalar.
	 * @return string
	 */
	private function str_arg( array $arr, $key, $default = '' ) {
		if ( ! isset( $arr[ $key ] ) ) {
			return $default;
		}
		$value = $arr[ $key ];
		if ( is_string( $value ) ) {
			return $value;
		}
		if ( is_scalar( $value ) ) {
			return (string) $value;
		}
		return $default;
	}
}
