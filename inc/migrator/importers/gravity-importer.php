<?php
/**
 * Gravity Forms importer — translates the JSON form definition stored in
 * `wp_gf_form_meta.display_meta` into SureForms block markup. Falls back to
 * the legacy `wp_rg_form*` tables for installs predating Gravity Forms 2.3.
 *
 * Gravity Forms uses no CPT — forms live in custom DB tables. Field
 * definitions are stored as a JSON-encoded (or legacy PHP-serialized)
 * blob alongside the form row. Each field is a flat associative array
 * with `type`, `label`, `isRequired`, `placeholder`, `defaultValue`, plus
 * type-specific keys; composite fields (Name, Address, Email-with-confirm,
 * Time, Checkbox, Consent) carry sub-inputs via an `inputs[]` array with
 * dotted IDs (`parent.1`, `parent.2`, …).
 *
 * Conditional logic is a per-field `conditionalLogic` block with
 * `actionType`, `logicType`, and an array of `rules[]` referencing target
 * field IDs (string form, can include dotted sub-IDs for sub-inputs).
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
 * Gravity_Importer
 *
 * @since 2.11.0
 */
class Gravity_Importer extends Base_Migrator {
	/**
	 * Gravity Forms operator → SureForms operator slug. Sourced from
	 * Pro's `conditional-logic-options.json`; aliases (`greater_than` for
	 * `>`, `less_than` for `<`) collapse to the symbol form during port.
	 */
	private const OPERATOR_MAP = [
		'is'           => '==',
		'isnot'        => '!=',
		'>'            => '>',
		'greater_than' => '>',
		'<'            => '<',
		'less_than'    => '<',
		'contains'     => 'includes',
		'starts_with'  => 'startWith',
		'ends_with'    => 'endWith',
	];

	/**
	 * Bucket-specific operator maps for date / time sources. SureForms'
	 * datepicker / timepicker block types expose their own operator set
	 * (`datePickerIs`, `isBefore`, `isAfter`, …) — the generic OPERATOR_MAP
	 * (`==`, `>`, …) is invalid for them, so date/time rules translate
	 * through these instead. Operators with no equivalent (e.g. `isnot` on a
	 * date — there is no `!=` for datepicker) are absent and the rule is
	 * dropped by the validity gate in `convert_rule()`.
	 */
	private const DATE_OPERATOR_MAP = [
		'is'           => 'datePickerIs',
		'>'            => 'isAfter',
		'greater_than' => 'isAfter',
		'<'            => 'isBefore',
		'less_than'    => 'isBefore',
	];

	private const TIME_OPERATOR_MAP = [
		'is'           => 'timePickerIs',
		'>'            => 'isAfter',
		'greater_than' => 'isAfter',
		'<'            => 'isBefore',
		'less_than'    => 'isBefore',
	];

	/**
	 * Accumulator: conditional-logic targets discovered during emission.
	 * Each entry: { source_field_id, action, rules: [...] }.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $conditional_logic = [];

	/**
	 * Map: source Gravity Forms field id (or dotted sub-id like `1.3`) →
	 * SureForms block_id assembled during translation. Used by
	 * `assemble_conditional_logic_meta()` to rewrite rule targets.
	 *
	 * @var array<string,string>
	 */
	private $field_id_to_block_id = [];

	/**
	 * Per-field block-type bucket for the SureForms CL editor.
	 *
	 * @var array<string,string>
	 */
	private $field_id_to_block_type = [];

	/**
	 * Form-level button text, captured while parsing.
	 *
	 * @var string
	 */
	private $submit_label = '';

	/**
	 * Form-level confirmations + notifications collected during parsing.
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
		$this->key   = 'gravity';
		$this->title = __( 'Gravity Forms', 'sureforms' );
	}

	/**
	 * Whether Gravity Forms (any version since 2.0) is currently active.
	 *
	 * @since 2.11.0
	 *
	 * @return bool
	 */
	public function exist() {
		return class_exists( 'GFForms' ) || class_exists( 'GFFormsModel' ) || defined( 'GF_MIN_WP_VERSION' );
	}

	/**
	 * Map of Gravity field-type → `Block_Templates` method for the field
	 * types Free can render without Pro. Pro overlays the rest via the
	 * `srfm_migrator_tag_to_template_map` filter.
	 *
	 * @since 2.11.0
	 *
	 * @return array<string,string>
	 */
	public function default_field_map() {
		return [
			'text'     => 'input',
			'textarea' => 'textarea',
			'email'    => 'email',
			'number'   => 'number',
			'select'   => 'dropdown',
			'radio'    => 'multi_choice',
			'checkbox' => 'multi_choice',
			'website'  => 'url',
			'phone'    => 'phone',
			'consent'  => 'gdpr',
		];
	}

	/**
	 * Fetch all Gravity forms from the custom tables, gating on the
	 * legacy / modern table-name split (pre-2.3 vs 2.3+).
	 *
	 * @since 2.11.0
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_source_forms() {
		if ( ! $this->exist() ) {
			return [];
		}
		global $wpdb;
		[ $form_table, $meta_table ] = $this->resolve_table_names();
		// Table names are derived from $wpdb->prefix + a hard-coded literal —
		// no user input — so the placeholder-vs-prepare warning is informational
		// only. Both `$wpdb->prepare` placeholders (%s/%i) would over-escape an
		// identifier here.
		$query = sprintf(
			'SELECT f.id, f.title, m.display_meta, m.confirmations, m.notifications FROM %1$s f LEFT JOIN %2$s m ON m.form_id = f.id WHERE f.is_trash = 0 ORDER BY f.id ASC',
			esc_sql( $form_table ),
			esc_sql( $meta_table )
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $query, ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return [];
		}
		$out = [];
		foreach ( $rows as $row ) {
			$out[] = [
				'id'            => (int) $row['id'],
				'name'          => (string) $row['title'],
				'display_meta'  => (string) $row['display_meta'],
				'confirmations' => (string) ( $row['confirmations'] ?? '' ),
				'notifications' => (string) ( $row['notifications'] ?? '' ),
			];
		}
		return $out;
	}

	/**
	 * Return the Gravity form id from a source descriptor.
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
	 * Return the form title for a source descriptor.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $form Source descriptor.
	 * @return string
	 */
	protected function get_source_form_name( array $form ) {
		$name = $this->str_arg( $form, 'name' );
		return '' !== $name ? $name : __( '(untitled Gravity form)', 'sureforms' );
	}

	/**
	 * Parse `display_meta` and emit SureForms block markup for the form.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $form Source descriptor.
	 * @return string
	 */
	protected function build_form_content( array $form ) {
		$this->used_slugs             = [];
		$this->conditional_logic      = [];
		$this->field_id_to_block_id   = [];
		$this->field_id_to_block_type = [];
		$this->submit_label           = '';
		$this->form_settings          = [];

		$display_meta = $this->parse_display_meta( $form );
		if ( empty( $display_meta ) ) {
			return '';
		}

		/**
		 * Filter parsed Gravity Forms `display_meta` before iteration.
		 *
		 * @since 2.11.0
		 *
		 * @param array<string,mixed> $display_meta Decoded form definition.
		 * @param string              $key          Migrator source key (`gravity`).
		 * @param array<string,mixed> $form         Source descriptor.
		 */
		$display_meta = (array) apply_filters( 'srfm_migrator_preprocess_template', $display_meta, $this->key, $form );

		// Capture form-level state for get_form_metas().
		$this->form_settings = $display_meta;
		$button              = isset( $display_meta['button'] ) && is_array( $display_meta['button'] ) ? $display_meta['button'] : [];
		$this->submit_label  = $this->str_arg( $button, 'text' );

		$fields = isset( $display_meta['fields'] ) && is_array( $display_meta['fields'] ) ? $display_meta['fields'] : [];
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
	 * Build SureForms post-meta payload — submit text, confirmations,
	 * notifications, and the assembled conditional-logic blob.
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

		$cl_meta = $this->assemble_conditional_logic_meta();
		if ( ! empty( $cl_meta ) ) {
			$metas['_srfm_conditional_logic'] = $cl_meta;
		}

		return $metas;
	}

	/**
	 * Resolve the form + meta table names. Gravity Forms ≥ 2.3 uses
	 * `gf_form*`; earlier installs use `rg_form*`. We detect the schema
	 * version via the `gf_database_version` option.
	 *
	 * @since 2.11.0
	 *
	 * @return array{0:string,1:string} `[form_table, meta_table]`.
	 */
	private function resolve_table_names() {
		global $wpdb;
		$option_value = get_option( 'gf_database_version', '2.3' );
		$version      = is_string( $option_value ) ? $option_value : '2.3';
		if ( version_compare( $version, '2.3-dev-1', '<' ) ) {
			return [ $wpdb->prefix . 'rg_form', $wpdb->prefix . 'rg_form_meta' ];
		}
		return [ $wpdb->prefix . 'gf_form', $wpdb->prefix . 'gf_form_meta' ];
	}

	/**
	 * Decode `display_meta` — tries `unserialize()` first for legacy
	 * PHP-serialized rows, then falls back to `json_decode()`. Mirrors
	 * Gravity's own `GFFormsModel::unserialize()`.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $form Source descriptor.
	 * @return array<string,mixed>
	 */
	private function parse_display_meta( array $form ) {
		$raw = $this->str_arg( $form, 'display_meta' );
		if ( '' === $raw ) {
			return [];
		}
		if ( is_serialized( $raw ) ) {
			// `unserialize()` is the only path for legacy Gravity rows
			// stored before they switched to JSON. We've already gated with
			// `is_serialized()`, so the input is well-formed; if it ever
			// isn't, an `[ 'allowed_classes' => false ]` second arg keeps
			// it from instantiating unknown class names.
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
			$decoded = unserialize( $raw, [ 'allowed_classes' => false ] );
			return is_array( $decoded ) ? $decoded : [];
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Translate one Gravity field array into SureForms block markup.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field Gravity field array.
	 * @return string
	 */
	private function translate_field( array $field ) {
		$type = $this->str_arg( $field, 'type' );
		if ( '' === $type ) {
			return '';
		}

		if ( 'name' === $type ) {
			return $this->translate_name_field( $field );
		}
		if ( 'address' === $type ) {
			return $this->translate_address_field( $field );
		}
		if ( 'section' === $type ) {
			// Section break — handled via the html_block filter (Pro) or skipped.
			return $this->dispatch_via_filter( 'divider', $this->build_block_args( $field ), $field );
		}
		if ( 'html' === $type ) {
			return $this->dispatch_via_filter( 'html_block', $this->build_block_args( $field ), $field );
		}
		if ( 'page' === $type ) {
			$args  = $this->build_block_args( $field );
			$next  = $this->str_arg( isset( $field['nextButton'] ) && is_array( $field['nextButton'] ) ? $field['nextButton'] : [], 'text', 'Next' );
			$prev  = $this->str_arg( isset( $field['previousButton'] ) && is_array( $field['previousButton'] ) ? $field['previousButton'] : [], 'text', 'Back' );
			$args += [
				'next_label' => $next,
				'prev_label' => $prev,
			];
			return $this->dispatch_via_filter( 'page_break', $args, $field );
		}
		if ( 'captcha' === $type ) {
			return ''; // form-level CAPTCHA; no block.
		}
		if ( in_array( $type, $this->hard_unsupported_types(), true ) ) {
			$this->note_unsupported( $this->str_arg( $field, 'label', $type ) );
			return '';
		}

		/**
		 * Filter the Gravity-field-type → template-method map.
		 *
		 * @since 2.11.0
		 *
		 * @param array<string,string> $map Gravity field type → method name.
		 * @param string               $key Migrator source key (`gravity`).
		 */
		$map = (array) apply_filters( 'srfm_migrator_tag_to_template_map', $this->default_field_map(), $this->key );

		$args   = $this->build_block_args( $field );
		$method = $map[ $type ] ?? '';

		if ( '' === $method ) {
			$markup = (string) apply_filters( 'srfm_migrator_block_template', '', $type, $args, $this->key );
			if ( '' === $markup ) {
				$this->note_unsupported( $this->str_arg( $field, 'label', $type ) );
				return '';
			}
			return $this->capture_field_metadata( $field, $args, $markup, $type );
		}

		// Gravity's "confirm email" is a second input on the same field;
		// srfm/email models that natively via isConfirmEmail, so enable the
		// option on the single block rather than emitting a duplicate email
		// field. The GF confirm sub-input label (inputs[1]) carries over.
		if ( 'email' === $type && ! empty( $field['emailConfirmEnabled'] ) ) {
			$args['confirm_email'] = true;
			$inputs                = isset( $field['inputs'] ) && is_array( $field['inputs'] ) ? $field['inputs'] : [];
			if ( isset( $inputs[1] ) && is_array( $inputs[1] ) ) {
				$confirm_label = $this->str_arg( $inputs[1], 'label' );
				if ( '' !== $confirm_label ) {
					$args['confirm_label'] = $confirm_label;
				}
			}
		}

		$markup = $this->dispatch_template( $method, $args );
		if ( '' === $markup ) {
			$markup = (string) apply_filters( 'srfm_migrator_block_template', '', $method, $args, $this->key );
		}
		if ( '' === $markup ) {
			$this->note_unsupported( $this->str_arg( $field, 'label', $type ) );
			return '';
		}

		return $this->capture_field_metadata( $field, $args, $markup, $method );
	}

	/**
	 * Build a SureForms block-args array from a Gravity field array.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field Gravity field.
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
			'default_value' => $this->str_arg( $field, 'defaultValue' ),
			'required'      => ! empty( $field['isRequired'] ),
			'help'          => $this->str_arg( $field, 'description' ),
			'error_message' => $this->str_arg( $field, 'errorMessage' ),
			'slug'          => $slug,
		];

		switch ( $type ) {
			case 'textarea':
				if ( isset( $field['maxLength'] ) && is_numeric( $field['maxLength'] ) ) {
					$args['max_length'] = (int) $field['maxLength'];
				}
				break;
			case 'text':
				if ( isset( $field['maxLength'] ) && is_numeric( $field['maxLength'] ) ) {
					$args['max_length'] = (int) $field['maxLength'];
				}
				break;
			case 'number':
				if ( isset( $field['rangeMin'] ) && is_numeric( $field['rangeMin'] ) ) {
					$args['min'] = (int) $field['rangeMin'];
				}
				if ( isset( $field['rangeMax'] ) && is_numeric( $field['rangeMax'] ) ) {
					$args['max'] = (int) $field['rangeMax'];
				}
				break;
			case 'select':
			case 'multiselect':
			case 'radio':
			case 'checkbox':
				$options             = $this->translate_choices( $field );
				$args['options']     = $options['options'];
				$args['preselected'] = $options['preselected'];
				$args['multiple']    = 'multiselect' === $type || 'checkbox' === $type;
				break;
			case 'date':
				$args['date_format'] = $this->normalize_date_format( $this->str_arg( $field, 'dateFormat', 'mdy' ) );
				break;
			case 'time':
				$args['format']      = 'time';
				$args['time_format'] = $this->str_arg( $field, 'timeFormat', '12' );
				break;
			case 'phone':
				$args['format'] = $this->str_arg( $field, 'phoneFormat', 'standard' );
				break;
			case 'fileupload':
				$exts = $this->str_arg( $field, 'allowedExtensions' );
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
				if ( isset( $field['maxFileSize'] ) && is_numeric( $field['maxFileSize'] ) ) {
					$args['file_size_limit'] = (int) $field['maxFileSize'];
				}
				if ( isset( $field['maxFiles'] ) && is_numeric( $field['maxFiles'] ) ) {
					$args['max_files'] = (int) $field['maxFiles'];
				}
				$args['multiple'] = ! empty( $field['multipleFiles'] );
				break;
			case 'hidden':
				$args['default_value'] = $this->str_arg( $field, 'defaultValue' );
				break;
			case 'consent':
				$args['label'] = $this->str_arg( $field, 'checkboxLabel', $args['label'] );
				break;
			case 'html':
				$args['content'] = $this->str_arg( $field, 'content' );
				break;
			case 'section':
				$args['help'] = $this->str_arg( $field, 'description' );
				break;
		}
		return $args;
	}

	/**
	 * Translate Gravity's Name composite. Sub-input visibility is per-
	 * `inputs[].isHidden`; we emit one `srfm/input` per visible sub.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field Name field.
	 * @return string
	 */
	private function translate_name_field( array $field ) {
		$base     = $this->str_arg( $field, 'label', 'Name' );
		$req      = ! empty( $field['isRequired'] );
		$field_id = $this->str_arg( $field, 'id' );
		$inputs   = isset( $field['inputs'] ) && is_array( $field['inputs'] ) ? $field['inputs'] : [];
		$markup   = '';
		$first    = '';
		foreach ( $inputs as $sub ) {
			if ( ! is_array( $sub ) || ! empty( $sub['isHidden'] ) ) {
				continue;
			}
			$sub_label  = $this->str_arg( $sub, 'label' );
			$sub_markup = Block_Templates::input(
				[
					'label'    => $base . ( '' !== $sub_label ? ' (' . $sub_label . ')' : '' ),
					'required' => $req,
					'slug'     => $this->reserve_slug( $base . '-' . $sub_label ),
				]
			);
			$markup    .= $sub_markup;
			// Register each visible sub-input's dotted id (e.g. "1.3") so CL
			// rules that reference a specific name part resolve to its block.
			$sub_block = $this->extract_block_id( $sub_markup );
			if ( '' === $sub_block ) {
				continue;
			}
			if ( '' === $first ) {
				$first = $sub_block;
			}
			$sub_id = $this->str_arg( $sub, 'id' );
			if ( '' !== $sub_id ) {
				$this->field_id_to_block_id[ $sub_id ]   = $sub_block;
				$this->field_id_to_block_type[ $sub_id ] = 'default';
			}
		}
		if ( '' === $markup ) {
			// `nameFormat=simple` or no inputs[] — emit one input.
			$markup = Block_Templates::input( $this->build_block_args( $field ) );
			$first  = $this->extract_block_id( $markup );
		}
		// Register the whole-field id → first sub-block so the Name field can
		// act as a CL source/target by its top-level id, then capture its own
		// CL rules (translate_name_field bypasses capture_field_metadata).
		if ( '' !== $field_id && '' !== $first ) {
			$this->field_id_to_block_id[ $field_id ]   = $first;
			$this->field_id_to_block_type[ $field_id ] = 'default';
		}
		$this->record_conditional_logic( $field );
		return $markup;
	}

	/**
	 * Translate Gravity's Address composite. Routes to the Pro
	 * `srfm/address` emitter via the `address` template-method.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field Address field.
	 * @return string
	 */
	private function translate_address_field( array $field ) {
		$args   = $this->build_block_args( $field );
		$markup = (string) apply_filters( 'srfm_migrator_block_template', '', 'address', $args, $this->key );
		if ( '' === $markup ) {
			$this->note_unsupported( $this->str_arg( $field, 'label', 'Address' ) );
			return '';
		}
		return $this->capture_field_metadata( $field, $args, $markup, 'address' );
	}

	/**
	 * Translate Gravity's `choices[]` (`{text,value,isSelected,price}`)
	 * into SureForms options + a preselected-index list. The option title is
	 * the choice value (when `enableChoiceValue`) else its text — the same
	 * string Gravity CL rules reference, so `convert_rule()` matches against
	 * it directly without a re-key map.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field Source field.
	 * @return array<string,mixed>
	 */
	private function translate_choices( array $field ) {
		$raw         = isset( $field['choices'] ) && is_array( $field['choices'] ) ? $field['choices'] : [];
		$use_values  = ! empty( $field['enableChoiceValue'] );
		$options     = [];
		$preselected = [];
		$i           = 0;
		foreach ( $raw as $choice ) {
			if ( ! is_array( $choice ) ) {
				continue;
			}
			$text      = $this->str_arg( $choice, 'text' );
			$value     = $use_values && '' !== $this->str_arg( $choice, 'value' )
				? $this->str_arg( $choice, 'value' )
				: $text;
			$options[] = [ 'label' => $value ];
			if ( ! empty( $choice['isSelected'] ) ) {
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
		];
	}

	/**
	 * Dispatch a single template-method through the
	 * `srfm_migrator_block_template` filter, flag unsupported if no
	 * subscriber answers.
	 *
	 * @since 2.11.0
	 *
	 * @param string              $method Template method name.
	 * @param array<string,mixed> $args   Block args.
	 * @param array<string,mixed> $field  Source field (for unsupported label).
	 * @return string
	 */
	private function dispatch_via_filter( $method, array $args, array $field ) {
		$markup = (string) apply_filters( 'srfm_migrator_block_template', '', $method, $args, $this->key );
		if ( '' === $markup ) {
			$this->note_unsupported( $this->str_arg( $field, 'label', $method ) );
			return '';
		}
		return $this->capture_field_metadata( $field, $args, $markup, $method );
	}

	/**
	 * After emitting a block, capture its block_id + CL rules for later
	 * meta-assembly.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field    Source field.
	 * @param array<string,mixed> $args     Final block args.
	 * @param string              $markup   Assembled block markup.
	 * @param string              $type_key WPForms/method key for block-type bucket.
	 * @return string
	 */
	private function capture_field_metadata( array $field, array $args, $markup, $type_key ) {
		unset( $args );
		$field_id = $this->str_arg( $field, 'id' );
		$block_id = $this->extract_block_id( $markup );
		if ( '' !== $field_id && '' !== $block_id ) {
			$this->field_id_to_block_id[ $field_id ]   = $block_id;
			$this->field_id_to_block_type[ $field_id ] = $this->block_type_bucket( $type_key );
		}
		$this->record_conditional_logic( $field );
		return $markup;
	}

	/**
	 * Extract the first `block_id` from a block markup string, or '' if none.
	 *
	 * @since 2.11.0
	 *
	 * @param string $markup Serialized block markup.
	 * @return string
	 */
	private function extract_block_id( $markup ) {
		return preg_match( '/"block_id":"([a-f0-9]{8})"/', (string) $markup, $m ) ? $m[1] : '';
	}

	/**
	 * Record a field's `conditionalLogic` block (if any) for later assembly.
	 * Split out of `capture_field_metadata()` so composite emitters (Name)
	 * that register their own sub-input ids can still capture CL rules.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field Source field.
	 * @return void
	 */
	private function record_conditional_logic( array $field ) {
		if ( empty( $field['conditionalLogic'] ) || ! is_array( $field['conditionalLogic'] ) ) {
			return;
		}
		$cl                        = $field['conditionalLogic'];
		$this->conditional_logic[] = [
			'target_field_id' => $this->str_arg( $field, 'id' ),
			'action'          => isset( $cl['actionType'] ) ? (string) $cl['actionType'] : 'show',
			'logic_type'      => isset( $cl['logicType'] ) ? (string) $cl['logicType'] : 'all',
			'rules'           => isset( $cl['rules'] ) && is_array( $cl['rules'] ) ? $cl['rules'] : [],
		];
	}

	/**
	 * Resolve a Gravity field id (whole or dotted sub-input like `1.3`) to the
	 * SureForms block_id captured during translation. Gravity CL rules
	 * frequently reference a sub-input id — a Name part, an Address line, a
	 * single Checkbox choice. When the exact id isn't registered, fall back to
	 * the parent field id (truncate at the first `.`) so the rule maps to the
	 * composite block instead of being silently dropped.
	 *
	 * @since 2.11.0
	 *
	 * @param string $field_id Gravity field id (e.g. `1` or `1.3`).
	 * @return string SureForms block_id, or '' if neither resolves.
	 */
	private function resolve_block_id( $field_id ) {
		if ( isset( $this->field_id_to_block_id[ $field_id ] ) ) {
			return $this->field_id_to_block_id[ $field_id ];
		}
		$parent = $this->parent_field_id( $field_id );
		return '' !== $parent && isset( $this->field_id_to_block_id[ $parent ] )
			? $this->field_id_to_block_id[ $parent ]
			: '';
	}

	/**
	 * Resolve a Gravity field id to its CL block-type bucket, with the same
	 * dotted-sub-input → parent fallback as `resolve_block_id()`.
	 *
	 * @since 2.11.0
	 *
	 * @param string $field_id Gravity field id.
	 * @return string Block-type bucket (defaults to `default`).
	 */
	private function resolve_block_type( $field_id ) {
		if ( isset( $this->field_id_to_block_type[ $field_id ] ) ) {
			return $this->field_id_to_block_type[ $field_id ];
		}
		$parent = $this->parent_field_id( $field_id );
		return '' !== $parent && isset( $this->field_id_to_block_type[ $parent ] )
			? $this->field_id_to_block_type[ $parent ]
			: 'default';
	}

	/**
	 * Return the parent field id of a dotted sub-input id (`1.3` → `1`), or ''
	 * when the id has no dot.
	 *
	 * @since 2.11.0
	 *
	 * @param string $field_id Gravity field id.
	 * @return string
	 */
	private function parent_field_id( $field_id ) {
		$dot = strpos( (string) $field_id, '.' );
		return false === $dot ? '' : substr( (string) $field_id, 0, $dot );
	}

	/**
	 * Coarse block-type bucket for SureForms' CL editor.
	 *
	 * @since 2.11.0
	 *
	 * @param string $type Gravity Forms type or template-method name.
	 * @return string
	 */
	private function block_type_bucket( $type ) {
		if ( in_array( $type, [ 'number' ], true ) ) {
			return 'number';
		}
		if ( in_array( $type, [ 'select', 'multiselect', 'radio', 'checkbox', 'multi_choice', 'dropdown' ], true ) ) {
			return 'list';
		}
		// Pro makes date/time importable (#1258); their CL block types expose a
		// dedicated operator set, so bucket them accordingly instead of letting
		// them fall to `default` (which can't evaluate date/time operators).
		if ( in_array( $type, [ 'date', 'date_picker' ], true ) ) {
			return 'datepicker';
		}
		if ( in_array( $type, [ 'time', 'time_picker' ], true ) ) {
			return 'timepicker';
		}
		return 'default';
	}

	/**
	 * Build the `_srfm_conditional_logic` payload. `logicType=all` is the
	 * SureForms AND group; `logicType=any` becomes a SureForms group of
	 * one-rule subgroups (mirrors WPForms' rule shape).
	 *
	 * @since 2.11.0
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function assemble_conditional_logic_meta() {
		$out = [];
		foreach ( $this->conditional_logic as $entry ) {
			$target_field_id = $this->str_arg( $entry, 'target_field_id' );
			$target_block_id = $this->resolve_block_id( $target_field_id );
			if ( '' === $target_block_id ) {
				continue;
			}
			$rules           = isset( $entry['rules'] ) && is_array( $entry['rules'] ) ? $entry['rules'] : [];
			$converted_rules = [];
			foreach ( $rules as $rule ) {
				if ( ! is_array( $rule ) ) {
					continue;
				}
				$converted = $this->convert_rule( $rule );
				if ( null !== $converted ) {
					$converted_rules[] = $converted;
				}
			}
			if ( empty( $converted_rules ) ) {
				continue;
			}
			$logic = 'any' === $entry['logic_type']
				? array_map( static fn( $r ) => [ $r ], $converted_rules ) // each rule its own OR-group.
				: [ $converted_rules ];                                      // one AND-group.
			$out[] = [
				$target_block_id => [
					'action' => 'hide' === $entry['action'] ? 'hide' : 'show',
					'logic'  => $logic,
				],
			];
		}
		return $out;
	}

	/**
	 * Convert one Gravity CL rule into the SureForms rule shape.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $rule Gravity rule (`{fieldId, operator, value}`).
	 * @return array<string,string>|null
	 */
	private function convert_rule( array $rule ) {
		$src   = $this->str_arg( $rule, 'fieldId' );
		$op    = $this->str_arg( $rule, 'operator', 'is' );
		$block = $this->resolve_block_id( $src );
		if ( '' === $block ) {
			return null;
		}
		$bucket   = $this->resolve_block_type( $src );
		$operator = $this->map_operator( $op, $bucket );
		if ( null === $operator ) {
			// Source operator has no SureForms equivalent (e.g. `isnot` on a
			// date) — drop the rule.
			return null;
		}
		// Reconcile the operator against the bucket via the shared Base_Migrator
		// allowlist: down-buckets a text-style operator to `default`, or drops
		// the rule when no bucket supports it.
		$bucket = $this->resolve_cl_bucket( $operator, $bucket );
		if ( '' === $bucket ) {
			return null;
		}
		// Gravity stores the rule value as the choice `value`; we emit list
		// options keyed by that same value (translate_choices), so the raw
		// value matches the SureForms option title — pass it through as-is.
		return [
			'field'    => $block,
			'operator' => $operator,
			'value'    => $this->str_arg( $rule, 'value' ),
			'type'     => $bucket,
		];
	}

	/**
	 * Map a Gravity operator to the SureForms operator slug, or null when the
	 * source operator has no equivalent. Date/time buckets use their dedicated
	 * operator maps; all others go through OPERATOR_MAP. Validity against the
	 * bucket's allowed set is reconciled by `resolve_cl_bucket()` in the caller.
	 *
	 * @since 2.11.0
	 *
	 * @param string $gf_operator Gravity operator (e.g. `is`, `contains`).
	 * @param string $bucket      Resolved block-type bucket.
	 * @return string|null
	 */
	private function map_operator( $gf_operator, $bucket ) {
		if ( 'datepicker' === $bucket ) {
			return self::DATE_OPERATOR_MAP[ $gf_operator ] ?? null;
		}
		if ( 'timepicker' === $bucket ) {
			return self::TIME_OPERATOR_MAP[ $gf_operator ] ?? null;
		}
		return self::OPERATOR_MAP[ $gf_operator ] ?? null;
	}

	/**
	 * Translate Gravity's notifications JSON column into SureForms'
	 * `_srfm_email_notification` shape — first notification wins.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $settings Captured form_settings.
	 * @return array<int,array<string,mixed>>
	 */
	private function translate_email_notifications( array $settings ) {
		$notifs = isset( $settings['notifications'] ) && is_array( $settings['notifications'] ) ? $settings['notifications'] : [];
		if ( empty( $notifs ) ) {
			return [];
		}
		$first = reset( $notifs );
		if ( ! is_array( $first ) ) {
			return [];
		}
		$to = $this->str_arg( $first, 'to' );
		if ( '' === $to ) {
			$admin = get_option( 'admin_email' );
			$to    = is_string( $admin ) ? $admin : '';
		}
		return [
			[
				'status'         => true,
				'name'           => $this->str_arg( $first, 'name', __( 'Admin Notification', 'sureforms' ) ),
				'email_to'       => $to,
				'subject'        => $this->str_arg( $first, 'subject', __( 'New form submission', 'sureforms' ) ),
				'email_reply_to' => $this->str_arg( $first, 'replyTo', '{admin_email}' ),
				'email_body'     => $this->str_arg( $first, 'message', '{all_fields}' ),
			],
		];
	}

	/**
	 * Translate Gravity's confirmations JSON column — first wins.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $settings Captured form_settings.
	 * @return array<int,array<string,mixed>>
	 */
	private function translate_confirmation( array $settings ) {
		$confs = isset( $settings['confirmations'] ) && is_array( $settings['confirmations'] ) ? $settings['confirmations'] : [];
		$first = is_array( reset( $confs ) ) ? reset( $confs ) : [];
		$type  = $this->str_arg( $first, 'type', 'message' );
		$entry = [
			'confirmation_type' => 'same page',
			'message'           => $this->default_confirmation_message(),
			'page_url'          => '',
		];
		if ( 'message' === $type && ! empty( $first['message'] ) ) {
			$entry['message'] = wp_kses_post( $this->str_arg( $first, 'message' ) );
		}
		if ( 'redirect' === $type && ! empty( $first['url'] ) ) {
			$entry['confirmation_type'] = 'different page';
			$entry['page_url']          = esc_url_raw( $this->str_arg( $first, 'url' ) );
		}
		return [ $entry ];
	}

	/**
	 * Field types Gravity stores but SureForms has no peer for.
	 *
	 * @since 2.11.0
	 *
	 * @return array<int,string>
	 */
	private function hard_unsupported_types() {
		return [
			'creditcard',
			'product',
			'singleproduct',
			'hiddenproduct',
			'option',
			'quantity',
			'shipping',
			'singleshipping',
			'price',
			'total',
			'donation',
			'post_title',
			'post_content',
			'post_excerpt',
			'post_tags',
			'post_category',
			'post_custom_field',
			'post_image',
			'calculation',
			'survey',
			'quiz',
			'poll',
			'chainedselect',
		];
	}

	/**
	 * Map a Gravity Forms `dateFormat` slug onto the format string the
	 * SureForms date-picker block expects (it ships a fixed enum of
	 * `mm/dd/yyyy`, `dd/mm/yyyy`, `yyyy-mm-dd` etc.).
	 *
	 * Gravity formats: `mdy` / `dmy` / `dmy_dash` / `dmy_dot` /
	 * `ymd_slash` / `ymd_dash` / `ymd_dot`.
	 *
	 * @since 2.11.0
	 *
	 * @param string $gf_format Gravity Forms date format slug.
	 * @return string SureForms date format string.
	 */
	private function normalize_date_format( $gf_format ) {
		$map = [
			'mdy'       => 'mm/dd/yyyy',
			'dmy'       => 'dd/mm/yyyy',
			'dmy_dash'  => 'dd-mm-yyyy',
			'dmy_dot'   => 'dd.mm.yyyy',
			'ymd_slash' => 'yyyy/mm/dd',
			'ymd_dash'  => 'yyyy-mm-dd',
			'ymd_dot'   => 'yyyy.mm.dd',
		];
		return $map[ $gf_format ] ?? 'mm/dd/yyyy';
	}

	/**
	 * Coerce a mixed array entry to string. PHPStan level 9 friendly.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $arr     Source array.
	 * @param string              $key     Key.
	 * @param string              $default Default.
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
