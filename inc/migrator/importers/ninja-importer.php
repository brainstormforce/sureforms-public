<?php
/**
 * Ninja Forms importer — translates the form definitions stored across
 * Ninja's `nf3_forms` + `nf3_fields` + `nf3_field_meta` custom tables
 * (one row per setting key, with `maybe_unserialize` on each value) into
 * SureForms block markup.
 *
 * Field settings are NOT stored as a single blob — each setting key lives
 * as its own row in `nf3_field_meta`, so we have to assemble settings via
 * a JOIN-then-collapse pass per field.
 *
 * Conditional Logic is a paid Ninja add-on (`NF_ConditionalLogic` class)
 * — when present, rules live as a form-level `conditions` setting in
 * `nf3_form_meta`. Each rule targets fields by their `key` (slug), not
 * by id; we keep a slug→block_id map during emission and rewrite on the
 * way out.
 *
 * @package sureforms
 * @since   x.x.x
 */

namespace SRFM\Inc\Migrator\Importers;

use SRFM\Inc\Migrator\Base_Migrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ninja_Importer
 *
 * @since x.x.x
 */
class Ninja_Importer extends Base_Migrator {
	/**
	 * Ninja Forms conditional-logic comparator → SureForms operator slug.
	 * Mirrors the add-on's runtime; we ship the seven Pro-supported
	 * operators and drop the rest with a warning.
	 */
	private const OPERATOR_MAP = [
		'equal'        => '==',
		'=='           => '==',
		'not_equal'    => '!=',
		'!='           => '!=',
		'greater_than' => '>',
		'>'            => '>',
		'less_than'    => '<',
		'<'            => '<',
		'contains'     => 'includes',
		'starts_with'  => 'startWith',
		'ends_with'    => 'endWith',
	];

	/**
	 * Bucket-specific operator maps for date / time sources (SureForms'
	 * datepicker / timepicker expose their own operator set). Operators with
	 * no equivalent are absent and the rule is dropped by the validity gate.
	 */
	private const DATE_OPERATOR_MAP = [
		'equal'        => 'datePickerIs',
		'=='           => 'datePickerIs',
		'greater_than' => 'isAfter',
		'>'            => 'isAfter',
		'less_than'    => 'isBefore',
		'<'            => 'isBefore',
	];

	private const TIME_OPERATOR_MAP = [
		'equal'        => 'timePickerIs',
		'=='           => 'timePickerIs',
		'greater_than' => 'isAfter',
		'>'            => 'isAfter',
		'less_than'    => 'isBefore',
		'<'            => 'isBefore',
	];

	/**
	 * Map: source Ninja Forms field key (slug) → SureForms block_id.
	 *
	 * @var array<string,string>
	 */
	private $field_key_to_block_id = [];

	/**
	 * Per-field block-type bucket for the SureForms CL editor.
	 *
	 * @var array<string,string>
	 */
	private $field_key_to_block_type = [];

	/**
	 * Form-level submit-button text discovered during parsing.
	 *
	 * @var string
	 */
	private $submit_label = '';

	/**
	 * Form-level conditions (paid add-on output) lifted from form_meta.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $form_conditions = [];

	/**
	 * Form-level meta captured for get_form_metas() — title settings,
	 * actions (notifications), etc.
	 *
	 * @var array<string,mixed>
	 */
	private $form_meta = [];

	/**
	 * Form id currently being processed.
	 *
	 * @var int
	 */
	private $current_form_id = 0;

	/**
	 * Per-form memo of fetch_actions() — notifications and confirmations both
	 * read it, so cache to avoid double-querying nf3_objects/meta per form.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	private $actions_cache = null;

	/**
	 * Set source identifiers.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->key   = 'ninja';
		$this->title = __( 'Ninja Forms', 'sureforms' );
	}

	/**
	 * Whether Ninja Forms is currently active.
	 *
	 * @since x.x.x
	 *
	 * @return bool
	 */
	public function exist() {
		return class_exists( 'Ninja_Forms' ) || defined( 'NF_PLUGIN_VERSION' );
	}

	/**
	 * Map of Ninja Forms type → `Block_Templates` method for Free-emitable
	 * types. Pro overlays the rest via `srfm_migrator_tag_to_template_map`.
	 *
	 * @since x.x.x
	 *
	 * @return array<string,string>
	 */
	public function default_field_map() {
		return [
			'textbox'         => 'input',
			'firstname'       => 'input',
			'lastname'        => 'input',
			'address'         => 'input',
			'address2'        => 'input',
			'city'            => 'input',
			'zip'             => 'input',
			'phone'           => 'phone',
			'email'           => 'email',
			'textarea'        => 'textarea',
			'number'          => 'number',
			'listselect'      => 'dropdown',
			'listmultiselect' => 'dropdown',
			'listradio'       => 'multi_choice',
			'listcheckbox'    => 'multi_choice',
			'liststate'       => 'dropdown',
			'listcountry'     => 'dropdown',
			'checkbox'        => 'checkbox',
			'terms'           => 'gdpr',
		];
	}

	/**
	 * Fetch all Ninja forms from the custom tables.
	 *
	 * @since x.x.x
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_source_forms() {
		if ( ! $this->exist() ) {
			return [];
		}
		global $wpdb;
		$forms_table = $wpdb->prefix . 'nf3_forms';
		$query       = sprintf(
			'SELECT id, title FROM %s ORDER BY id ASC',
			esc_sql( $forms_table )
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $query, ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return [];
		}
		$out = [];
		foreach ( $rows as $row ) {
			$out[] = [
				'id'   => (int) $row['id'],
				'name' => (string) $row['title'],
			];
		}
		return $out;
	}

	/**
	 * Return the Ninja form id from a source descriptor.
	 *
	 * @since x.x.x
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
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $form Source descriptor.
	 * @return string
	 */
	protected function get_source_form_name( array $form ) {
		$name = $this->str_arg( $form, 'name' );
		return '' !== $name ? $name : __( '(untitled Ninja form)', 'sureforms' );
	}

	/**
	 * Build SureForms block markup for a Ninja form. Queries `nf3_fields`
	 * and `nf3_field_meta` to assemble each field's settings, then
	 * dispatches via the shared `srfm_migrator_*` filter pipeline.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $form Source descriptor.
	 * @return string
	 */
	protected function build_form_content( array $form ) {
		$this->used_slugs              = [];
		$this->field_key_to_block_id   = [];
		$this->field_key_to_block_type = [];
		$this->submit_label            = '';
		$this->form_conditions         = [];
		$this->form_meta               = [];
		$this->actions_cache           = null;
		$this->current_form_id         = $this->get_source_form_id( $form );

		$fields = $this->fetch_fields( $this->current_form_id );
		if ( empty( $fields ) ) {
			return '';
		}
		$this->form_meta = $this->fetch_form_meta( $this->current_form_id );

		// Extract paid-add-on conditional logic blob, if present.
		if ( isset( $this->form_meta['conditions'] ) && is_array( $this->form_meta['conditions'] ) ) {
			$this->form_conditions = $this->form_meta['conditions'];
		}

		/**
		 * Filter the Ninja field list before iteration.
		 *
		 * @since x.x.x
		 *
		 * @param array<int,array<string,mixed>> $fields Assembled Ninja fields.
		 * @param string                         $key    Migrator source key (`ninja`).
		 * @param array<string,mixed>            $form   Source descriptor.
		 */
		$fields = (array) apply_filters( 'srfm_migrator_preprocess_template', $fields, $this->key, $form );

		$markup = '';
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$type = $this->str_arg( $field, 'type' );
			if ( in_array( $type, [ 'submit', 'spam', 'timedsubmit', 'recaptcha_v3', 'recaptcha', 'hcaptcha', 'turnstile' ], true ) ) {
				if ( 'submit' === $type ) {
					$this->submit_label = $this->str_arg( $field, 'label', 'Submit' );
				}
				continue; // Submit + captcha + spam → form-level, not block-level.
			}
			$markup .= $this->translate_field( $field );
		}
		return $markup;
	}

	/**
	 * Build SureForms post-meta payload.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $form Source descriptor.
	 * @return array<string,mixed>
	 */
	protected function get_form_metas( array $form ) {
		unset( $form );
		$metas = [
			'_srfm_submit_button_text' => '' !== $this->submit_label ? $this->submit_label : __( 'Submit', 'sureforms' ),
		];

		$confirmation = $this->translate_confirmation_from_actions();
		if ( ! empty( $confirmation ) ) {
			$metas['_srfm_form_confirmation'] = $confirmation;
		}

		$email = $this->translate_email_notifications_from_actions();
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
	 * Query `nf3_fields` + `nf3_field_meta` and collapse the meta rows
	 * into a flat associative array per field. Mirrors what Ninja's
	 * `NF_Abstracts_Model::__construct` does on read.
	 *
	 * @since x.x.x
	 *
	 * @param int $form_id Ninja form id.
	 * @return array<int,array<string,mixed>>
	 */
	protected function fetch_fields( $form_id ) {
		global $wpdb;
		$fields_table     = $wpdb->prefix . 'nf3_fields';
		$field_meta_table = $wpdb->prefix . 'nf3_field_meta';

		$fields_query = sprintf(
			'SELECT id, `label`, `key`, `type`, `order` FROM %s WHERE parent_id = %d ORDER BY `order` ASC',
			esc_sql( $fields_table ),
			(int) $form_id
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $fields_query, ARRAY_A );
		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return [];
		}

		$ids = array_map( static fn( $r ) => (int) $r['id'], $rows );
		// Fetch all meta rows for these field ids in one query. Each id is
		// cast to int above, so the IN-list is safe to interpolate directly
		// — `prepare()` doesn't support variadic IN lists cleanly.
		$ids_sql    = implode( ',', $ids );
		$meta_query = sprintf(
			'SELECT parent_id, COALESCE(NULLIF(meta_key, \'\'), `key`) AS k, COALESCE(NULLIF(meta_value, \'\'), `value`) AS v FROM %s WHERE parent_id IN (%s)',
			esc_sql( $field_meta_table ),
			$ids_sql
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$meta_rows = $wpdb->get_results( $meta_query, ARRAY_A );

		$meta_by_field = [];
		if ( is_array( $meta_rows ) ) {
			foreach ( $meta_rows as $m ) {
				$pid                         = (int) $m['parent_id'];
				$k                           = (string) $m['k'];
				$v                           = $m['v'];
				$v                           = $this->safe_unserialize( $v );
				$meta_by_field[ $pid ][ $k ] = $v;
			}
		}

		$out = [];
		foreach ( $rows as $row ) {
			$id    = (int) $row['id'];
			$field = [
				'id'    => $id,
				'label' => (string) $row['label'],
				'key'   => (string) $row['key'],
				'type'  => (string) $row['type'],
				'order' => (int) $row['order'],
			];
			$out[] = array_merge( $field, $meta_by_field[ $id ] ?? [] );
		}
		return $out;
	}

	/**
	 * Query `nf3_form_meta` and collapse into a flat assoc array.
	 *
	 * @since x.x.x
	 *
	 * @param int $form_id Ninja form id.
	 * @return array<string,mixed>
	 */
	protected function fetch_form_meta( $form_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nf3_form_meta';
		$query = sprintf(
			'SELECT COALESCE(NULLIF(meta_key, \'\'), `key`) AS k, COALESCE(NULLIF(meta_value, \'\'), `value`) AS v FROM %s WHERE parent_id = %d',
			esc_sql( $table ),
			(int) $form_id
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $query, ARRAY_A );
		$out  = [];
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$k         = (string) $row['k'];
				$v         = $row['v'];
				$v         = $this->safe_unserialize( $v );
				$out[ $k ] = $v;
			}
		}
		return $out;
	}

	/**
	 * Translate a single Ninja field (with meta merged in) into block
	 * markup.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $field Ninja field with merged meta.
	 * @return string
	 */
	private function translate_field( array $field ) {
		$type = $this->str_arg( $field, 'type' );
		if ( '' === $type ) {
			return '';
		}
		if ( in_array( $type, $this->hard_unsupported_types(), true ) ) {
			$this->note_unsupported( $this->str_arg( $field, 'label', $type ) );
			return '';
		}
		if ( 'hr' === $type ) {
			return $this->dispatch_via_filter( 'divider', $this->build_block_args( $field ), $field );
		}
		if ( 'note' === $type || 'html' === $type ) {
			return $this->dispatch_via_filter( 'html_block', $this->build_block_args( $field ), $field );
		}
		if ( 'date' === $type ) {
			return $this->dispatch_via_filter( 'date_time_picker', $this->build_block_args( $field ), $field );
		}
		if ( 'file_upload' === $type ) {
			return $this->dispatch_via_filter( 'upload', $this->build_block_args( $field ), $field );
		}
		if ( 'starrating' === $type ) {
			return $this->dispatch_via_filter( 'rating', $this->build_block_args( $field ), $field );
		}
		if ( 'hidden' === $type ) {
			return $this->dispatch_via_filter( 'hidden_field', $this->build_block_args( $field ), $field );
		}
		if ( 'password' === $type ) {
			return $this->dispatch_via_filter( 'password_input', $this->build_block_args( $field ), $field );
		}
		if ( 'signature' === $type ) {
			return $this->dispatch_via_filter( 'signature', $this->build_block_args( $field ), $field );
		}

		/**
		 * Filter the Ninja-type → template-method map.
		 *
		 * @since x.x.x
		 *
		 * @param array<string,string> $map Type → method.
		 * @param string               $key Migrator source key (`ninja`).
		 */
		$map    = (array) apply_filters( 'srfm_migrator_tag_to_template_map', $this->default_field_map(), $this->key );
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
	 * Build SureForms block args from a Ninja field with merged meta.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $field Ninja field.
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
			'default_value' => $this->str_arg( $field, 'default' ),
			'required'      => ! empty( $field['required'] ),
			'help'          => $this->str_arg( $field, 'help_text', $this->str_arg( $field, 'desc_text' ) ),
			'slug'          => $slug,
		];

		switch ( $type ) {
			case 'textbox':
			case 'firstname':
			case 'lastname':
				if ( isset( $field['input_limit'] ) && is_numeric( $field['input_limit'] ) && (int) $field['input_limit'] > 0 ) {
					$args['max_length'] = (int) $field['input_limit'];
				}
				break;
			case 'textarea':
				if ( isset( $field['input_limit'] ) && is_numeric( $field['input_limit'] ) && (int) $field['input_limit'] > 0 ) {
					$args['max_length'] = (int) $field['input_limit'];
				}
				break;
			case 'number':
				$num = isset( $field['number'] ) && is_array( $field['number'] ) ? $field['number'] : [];
				if ( isset( $num['num_min'] ) && is_numeric( $num['num_min'] ) ) {
					$args['min'] = (int) $num['num_min'];
				}
				if ( isset( $num['num_max'] ) && is_numeric( $num['num_max'] ) ) {
					$args['max'] = (int) $num['num_max'];
				}
				break;
			case 'listselect':
			case 'listmultiselect':
			case 'listradio':
			case 'listcheckbox':
				$options             = $this->translate_options( $field );
				$args['options']     = $options['options'];
				$args['preselected'] = $options['preselected'];
				$args['multiple']    = in_array( $type, [ 'listmultiselect', 'listcheckbox' ], true );
				break;
			case 'checkbox':
				$args['checked'] = ! empty( $field['checkbox_default_value'] );
				break;
			case 'date':
				$args['format']      = 'date';
				$args['date_format'] = $this->str_arg( $field, 'date_format', 'm/d/Y' );
				break;
			case 'file_upload':
				$exts = isset( $field['upload_types'] ) && is_array( $field['upload_types'] ) ? $field['upload_types'] : [];
				if ( ! empty( $exts ) ) {
					$args['allowed_formats'] = array_values( array_filter( $exts, 'is_string' ) );
				}
				if ( isset( $field['max_filesize'] ) && is_numeric( $field['max_filesize'] ) ) {
					$args['file_size_limit'] = (int) $field['max_filesize'];
				}
				if ( isset( $field['max_files'] ) && is_numeric( $field['max_files'] ) ) {
					$args['max_files'] = (int) $field['max_files'];
					$args['multiple']  = (int) $field['max_files'] > 1;
				}
				break;
			case 'starrating':
				if ( isset( $field['number_of_stars'] ) && is_numeric( $field['number_of_stars'] ) ) {
					$args['icon'] = 'star';
				}
				break;
			case 'hidden':
				$args['default_value'] = $this->str_arg( $field, 'default' );
				break;
			case 'html':
			case 'note':
				$args['content'] = $this->str_arg( $field, 'default' );
				break;
		}

		return $args;
	}

	/**
	 * Translate Ninja's `options[]` (assoc rows with label/value/selected)
	 * into SureForms options + a preselected-INDEX list. The option title is
	 * the Ninja label (falling back to value) — the same string Ninja CL
	 * rules compare against — so `convert_rule()` matches it directly without
	 * a re-key map.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $field Ninja field.
	 * @return array<string,mixed>
	 */
	private function translate_options( array $field ) {
		$raw         = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : [];
		$options     = [];
		$preselected = [];
		$i           = 0;
		foreach ( $raw as $opt ) {
			if ( ! is_array( $opt ) ) {
				continue;
			}
			// Ninja stores the visible text in `label` and the submitted
			// value in `value` — surface the label as the SureForms option
			// title, falling back to `value` when the editor left label empty.
			$label     = $this->str_arg( $opt, 'label' );
			$display   = '' !== $label ? $label : $this->str_arg( $opt, 'value' );
			$options[] = [ 'label' => $display ];
			if ( ! empty( $opt['selected'] ) ) {
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
	 * Dispatch a method through `srfm_migrator_block_template`, flag
	 * unsupported if no subscriber answers.
	 *
	 * @since x.x.x
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
	 * After emitting a block, capture its block_id under the source
	 * field's `key` (slug) so conditional logic can rewrite targets.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $field    Source field.
	 * @param array<string,mixed> $args     Block args.
	 * @param string              $markup   Assembled markup.
	 * @param string              $type_key Method / type for the type bucket.
	 * @return string
	 */
	private function capture_field_metadata( array $field, array $args, $markup, $type_key ) {
		unset( $args );
		$key = $this->str_arg( $field, 'key' );
		if ( '' !== $key && preg_match( '/"block_id":"([a-f0-9]{8})"/', $markup, $m ) ) {
			$this->field_key_to_block_id[ $key ]   = $m[1];
			$this->field_key_to_block_type[ $key ] = $this->block_type_bucket( $type_key );
		}
		return $markup;
	}

	/**
	 * Block-type bucket for the SureForms CL editor.
	 *
	 * @since x.x.x
	 *
	 * @param string $type Source type or method name.
	 * @return string
	 */
	private function block_type_bucket( $type ) {
		if ( in_array( $type, [ 'number' ], true ) ) {
			return 'number';
		}
		if ( in_array( $type, [ 'listselect', 'listmultiselect', 'listradio', 'listcheckbox', 'multi_choice', 'dropdown' ], true ) ) {
			return 'list';
		}
		if ( in_array( $type, [ 'date', 'date_picker' ], true ) ) {
			return 'datepicker';
		}
		if ( in_array( $type, [ 'time', 'time_picker' ], true ) ) {
			return 'timepicker';
		}
		return 'default';
	}

	/**
	 * Map a Ninja comparator to the SureForms operator slug, or null when the
	 * comparator has no equivalent. Date/time buckets use their dedicated maps;
	 * all others go through OPERATOR_MAP. Validity against the bucket's allowed
	 * set is reconciled by `resolve_cl_bucket()` in the caller.
	 *
	 * @since x.x.x
	 *
	 * @param string $comparator Ninja comparator (e.g. `equal`, `contains`).
	 * @param string $bucket     Resolved block-type bucket.
	 * @return string|null
	 */
	private function map_operator( $comparator, $bucket ) {
		if ( 'datepicker' === $bucket ) {
			return self::DATE_OPERATOR_MAP[ $comparator ] ?? null;
		}
		if ( 'timepicker' === $bucket ) {
			return self::TIME_OPERATOR_MAP[ $comparator ] ?? null;
		}
		return self::OPERATOR_MAP[ $comparator ] ?? null;
	}

	/**
	 * Unserialize a Ninja meta value without instantiating objects.
	 *
	 * `maybe_unserialize()` delegates to bare `unserialize()` with no class
	 * allow-list, so a serialized object in `nf3_*_meta` would be woken
	 * (`__wakeup`/`__destruct` gadget surface). Gate on `is_serialized()` and
	 * forbid classes — matches the Gravity importer's hardening. Non-string
	 * and non-serialized values pass through unchanged.
	 *
	 * @since x.x.x
	 *
	 * @param mixed $value Raw meta value.
	 * @return mixed
	 */
	private function safe_unserialize( $value ) {
		if ( ! is_string( $value ) || ! is_serialized( $value ) ) {
			return $value;
		}
		// allowed_classes => false forbids object instantiation (no gadget surface).
		return unserialize( $value, [ 'allowed_classes' => false ] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
	}

	/**
	 * Assemble the `_srfm_conditional_logic` post-meta from Ninja's
	 * form-level `conditions` array (paid add-on only).
	 *
	 * @since x.x.x
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function assemble_conditional_logic_meta() {
		if ( empty( $this->form_conditions ) ) {
			return [];
		}
		$out = [];
		foreach ( $this->form_conditions as $condition ) {
			if ( ! is_array( $condition ) ) {
				continue;
			}
			$when      = isset( $condition['when'] ) && is_array( $condition['when'] ) ? $condition['when'] : [];
			$connector = $this->str_arg( $condition, 'connector', 'and' );
			$then      = isset( $condition['then'] ) && is_array( $condition['then'] ) ? $condition['then'] : [];

			// Convert the `when` rules.
			$rules = [];
			foreach ( $when as $w ) {
				if ( ! is_array( $w ) ) {
					continue;
				}
				$converted = $this->convert_rule( $w );
				if ( null !== $converted ) {
					$rules[] = $converted;
				}
			}
			if ( empty( $rules ) ) {
				continue;
			}
			// `or` connector → each rule its own subgroup. `and` → one group.
			$logic = 'or' === strtolower( $connector )
				? array_map( static fn( $r ) => [ $r ], $rules )
				: [ $rules ];

			// Attach to each target named in `then`.
			foreach ( $then as $act ) {
				if ( ! is_array( $act ) ) {
					continue;
				}
				$target_key   = $this->str_arg( $act, 'key' );
				$trigger      = $this->str_arg( $act, 'trigger', 'show' );
				$target_block = $this->field_key_to_block_id[ $target_key ] ?? '';
				if ( '' === $target_block || ! in_array( $trigger, [ 'show', 'hide' ], true ) ) {
					continue;
				}
				$out[] = [
					$target_block => [
						'action' => $trigger,
						'logic'  => $logic,
					],
				];
			}
		}
		return $out;
	}

	/**
	 * Convert one Ninja conditional rule into the SureForms shape.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $rule Ninja `when` rule.
	 * @return array<string,string>|null
	 */
	private function convert_rule( array $rule ) {
		$src   = $this->str_arg( $rule, 'key' );
		$cmp   = $this->str_arg( $rule, 'comparator', 'equal' );
		$block = $this->field_key_to_block_id[ $src ] ?? '';
		if ( '' === $block ) {
			return null;
		}
		$bucket   = $this->field_key_to_block_type[ $src ] ?? 'default';
		$operator = $this->map_operator( $cmp, $bucket );
		if ( null === $operator ) {
			// Comparator has no SureForms equivalent — drop the rule.
			return null;
		}
		// Reconcile the operator against the bucket via the shared Base_Migrator
		// allowlist: down-buckets a text-style operator to `default`, or drops
		// the rule when no bucket supports it.
		$bucket = $this->resolve_cl_bucket( $operator, $bucket );
		if ( '' === $bucket ) {
			return null;
		}
		// The option title we emit is the Ninja option label (translate_options),
		// which is the same string Ninja CL rules compare against — pass the
		// value through directly.
		return [
			'field'    => $block,
			'operator' => $operator,
			'value'    => $this->str_arg( $rule, 'value' ),
			'type'     => $bucket,
		];
	}

	/**
	 * Translate Ninja `email` actions (in `wp_nf3_objects` joined via
	 * `nf3_relationships`) into SureForms email notification meta.
	 *
	 * For simplicity we read the same data through the WordPress option
	 * cache that Ninja exposes via its REST API; if unavailable, fall
	 * back to scanning form_meta for an `email_*` key prefix.
	 *
	 * @since x.x.x
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function translate_email_notifications_from_actions() {
		$actions = $this->fetch_actions( $this->current_form_id );
		foreach ( $actions as $action ) {
			if ( 'email' !== ( $action['type'] ?? '' ) ) {
				continue;
			}
			$settings = isset( $action['settings'] ) && is_array( $action['settings'] ) ? $action['settings'] : [];
			$to       = $this->str_arg( $settings, 'to' );
			if ( '' === $to ) {
				$admin = get_option( 'admin_email' );
				$to    = is_string( $admin ) ? $admin : '';
			}
			return [
				[
					'status'         => true,
					'name'           => $this->str_arg( $action, 'label', __( 'Admin Notification', 'sureforms' ) ),
					'email_to'       => $to,
					'subject'        => $this->str_arg( $settings, 'email_subject', __( 'New form submission', 'sureforms' ) ),
					'email_reply_to' => $this->str_arg( $settings, 'reply_to', '{admin_email}' ),
					'email_body'     => $this->str_arg( $settings, 'email_message', '{all_fields}' ),
				],
			];
		}
		return [];
	}

	/**
	 * Translate Ninja `successmessage` / `redirect` actions into the
	 * SureForms confirmation shape.
	 *
	 * @since x.x.x
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function translate_confirmation_from_actions() {
		$actions = $this->fetch_actions( $this->current_form_id );
		$entry   = [
			'confirmation_type' => 'same page',
			'message'           => $this->default_confirmation_message(),
			'page_url'          => '',
		];
		foreach ( $actions as $action ) {
			$type     = $this->str_arg( $action, 'type' );
			$settings = isset( $action['settings'] ) && is_array( $action['settings'] ) ? $action['settings'] : [];
			if ( 'successmessage' === $type && ! empty( $settings['success_msg'] ) ) {
				$entry['message'] = wp_kses_post( $this->str_arg( $settings, 'success_msg' ) );
				return [ $entry ];
			}
			if ( 'redirect' === $type && ! empty( $settings['redirect_url'] ) ) {
				$entry['confirmation_type'] = 'different page';
				$entry['page_url']          = esc_url_raw( $this->str_arg( $settings, 'redirect_url' ) );
				return [ $entry ];
			}
		}
		return [ $entry ];
	}

	/**
	 * Fetch the form's actions (success message, email, redirect, …) via
	 * the `nf3_objects` + `nf3_object_meta` + `nf3_relationships` tables.
	 *
	 * @since x.x.x
	 *
	 * @param int $form_id Ninja form id.
	 * @return array<int,array<string,mixed>>
	 */
	protected function fetch_actions( $form_id ) {
		if ( null !== $this->actions_cache ) {
			return $this->actions_cache;
		}
		global $wpdb;
		$objects_table = $wpdb->prefix . 'nf3_objects';
		$rels_table    = $wpdb->prefix . 'nf3_relationships';
		$meta_table    = $wpdb->prefix . 'nf3_object_meta';

		// Table names are derived from $wpdb->prefix + a hard-coded literal;
		// child_type is the hard-coded string 'action'; only $form_id needs
		// prepared-statement protection.
		$query = sprintf(
			"SELECT o.id, o.type, o.title AS label FROM %1\$s o JOIN %2\$s r ON r.child_id = o.id WHERE r.parent_id = %3\$d AND r.child_type = 'action'",
			esc_sql( $objects_table ),
			esc_sql( $rels_table ),
			(int) $form_id
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $query, ARRAY_A );
		if ( ! is_array( $rows ) || empty( $rows ) ) {
			$this->actions_cache = [];
			return $this->actions_cache;
		}

		$ids = array_map( static fn( $r ) => (int) $r['id'], $rows );
		// Each id is cast to int above; safe to interpolate the IN-list
		// directly.
		$ids_sql    = implode( ',', $ids );
		$meta_query = sprintf(
			'SELECT parent_id, COALESCE(NULLIF(meta_key, \'\'), `key`) AS k, COALESCE(NULLIF(meta_value, \'\'), `value`) AS v FROM %s WHERE parent_id IN (%s)',
			esc_sql( $meta_table ),
			$ids_sql
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$meta_rows = $wpdb->get_results( $meta_query, ARRAY_A );
		$by_id     = [];
		if ( is_array( $meta_rows ) ) {
			foreach ( $meta_rows as $m ) {
				$pid                 = (int) $m['parent_id'];
				$k                   = (string) $m['k'];
				$v                   = $m['v'];
				$v                   = $this->safe_unserialize( $v );
				$by_id[ $pid ][ $k ] = $v;
			}
		}

		$out = [];
		foreach ( $rows as $r ) {
			$id    = (int) $r['id'];
			$out[] = [
				'id'       => $id,
				'type'     => (string) $r['type'],
				'label'    => (string) $r['label'],
				'settings' => $by_id[ $id ] ?? [],
			];
		}
		$this->actions_cache = $out;
		return $this->actions_cache;
	}

	/**
	 * Source types Ninja Forms ships but SureForms has no peer for.
	 *
	 * @since x.x.x
	 *
	 * @return array<int,string>
	 */
	private function hard_unsupported_types() {
		return [
			'creditcard',
			'creditcardnumber',
			'creditcardcvc',
			'creditcardexpiration',
			'creditcardfullname',
			'creditcardzip',
			'total',
			'product',
			'quantity',
			'shipping',
			'tax',
			'listmodifier',
			'stripeshipping',
			'unknown',
		];
	}

	/**
	 * Coerce a mixed array entry to string.
	 *
	 * @since x.x.x
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
