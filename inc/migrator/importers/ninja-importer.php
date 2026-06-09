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
 * @since   2.11.0
 */

namespace SRFM\Inc\Migrator\Importers;

use SRFM\Inc\Migrator\Base_Migrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ninja_Importer
 *
 * @since 2.11.0
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
	 * @since 2.11.0
	 */
	public function __construct() {
		$this->key   = 'ninja';
		$this->title = __( 'Ninja Forms', 'sureforms' );
	}

	/**
	 * Whether Ninja Forms is currently active.
	 *
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
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
		return '' !== $name ? $name : __( '(untitled Ninja form)', 'sureforms' );
	}

	/**
	 * Build SureForms block markup for a Ninja form. Queries `nf3_fields`
	 * and `nf3_field_meta` to assemble each field's settings, then
	 * dispatches via the shared `srfm_migrator_*` filter pipeline.
	 *
	 * @since 2.11.0
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
		 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * Fetch the form's actions (success message, email, redirect, …) via
	 * the `nf3_objects` + `nf3_object_meta` + `nf3_relationships` tables.
	 *
	 * @since 2.11.0
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
	 * Translate a single Ninja field (with meta merged in) into block
	 * markup.
	 *
	 * @since 2.11.0
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
			return $this->translate_date_field( $field );
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
		 * @since 2.11.0
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
	 * @since 2.11.0
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
			case 'listcountry':
			case 'liststate':
				// Ninja auto-populates Country / State lists at render time, so the
				// stored field has no `options[]` — translate_options() would yield
				// only the "Option 1" placeholder. Seed the canonical list instead.
				$options             = 'listcountry' === $type ? $this->country_options() : $this->state_options();
				$args['options']     = $options;
				$args['preselected'] = [];
				$args['multiple']    = false;
				break;
			case 'checkbox':
				$args['checked'] = $this->checkbox_is_checked( $field );
				break;
			case 'date':
				// Ninja's Date/Time field carries a `date_mode` setting whose
				// value is one of `date` / `time` / `date and time`. Map that to
				// SureForms' date-picker `format` enum (`date`|`time`|`date-time`)
				// so a time-only or date+time field isn't flattened to date-only.
				// `date_time_picker` emits a time-picker for `time` and a
				// date-picker for `date`/`date-time`; we additionally emit a
				// companion time block for `date-time` (see translate_field()),
				// because SureForms' date-picker has no on-field time component.
				$args['format']      = $this->date_format_from_mode( $field );
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
	 * @since 2.11.0
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
	 * Translate a Ninja Date/Time field. Ninja's single field can be a date
	 * picker, a time picker, or both (its `date_mode` setting). SureForms'
	 * date-picker block has no on-field time component, so a `date and time`
	 * Ninja field is emitted as TWO blocks — a date-picker plus a companion
	 * time-picker — rather than being flattened to date-only.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field Ninja date field with merged meta.
	 * @return string
	 */
	private function translate_date_field( array $field ) {
		$args   = $this->build_block_args( $field );
		$format = $this->str_arg( $args, 'format', 'date' );

		if ( 'date-time' !== $format ) {
			// Pure date or pure time → one date_time_picker block; the Pro
			// emitter routes on args['format'] ('date'|'time').
			return $this->dispatch_via_filter( 'date_time_picker', $args, $field );
		}

		// Date + time → emit a date block plus a companion time block so the
		// time component survives. Only the date block is registered for
		// conditional logic (via dispatch_via_filter → capture_field_metadata):
		// the Ninja field has a single key, so it anchors CL to the primary
		// date block. The companion time block is supplementary and emitted
		// directly without re-registering the same key (which would clobber it).
		$date_args           = $args;
		$date_args['format'] = 'date';
		$markup              = $this->dispatch_via_filter( 'date_time_picker', $date_args, $field );

		$time_args           = $args;
		$time_args['format'] = 'time';
		$time_label          = '' !== $this->str_arg( $args, 'label' )
			? $this->str_arg( $args, 'label' ) . ' ' . __( '(Time)', 'sureforms' )
			: __( 'Time', 'sureforms' );
		$time_args['label']  = $time_label;
		$time_args['slug']   = $this->reserve_slug( $time_label );
		return $markup . (string) apply_filters( 'srfm_migrator_block_template', '', 'date_time_picker', $time_args, $this->key );
	}

	/**
	 * Resolve the SureForms date-picker `format` enum (`date`|`time`|`date-time`)
	 * from a Ninja date field's `date_mode` setting. Ninja stores one of
	 * `date` / `time` / `date and time`; older builds may use `datetime`.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field Ninja date field.
	 * @return string `date`, `time`, or `date-time`.
	 */
	private function date_format_from_mode( array $field ) {
		$mode = strtolower( trim( $this->str_arg( $field, 'date_mode', 'date' ) ) );
		if ( 'time' === $mode ) {
			return 'time';
		}
		if ( in_array( $mode, [ 'date and time', 'datetime', 'date-time', 'date_and_time' ], true ) ) {
			return 'date-time';
		}
		return 'date';
	}

	/**
	 * Resolve whether a Ninja single-checkbox field defaults to checked.
	 *
	 * Ninja stores the default in the `default_value` setting as the string
	 * `checked` / `unchecked` (the generic `default` key can also carry it).
	 * Treat `checked` (case-insensitive) or a truthy `1`/`true`/`yes` as on.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $field Ninja checkbox field.
	 * @return bool
	 */
	private function checkbox_is_checked( array $field ) {
		$raw = strtolower( trim( $this->str_arg( $field, 'default_value', $this->str_arg( $field, 'default' ) ) ) );
		if ( in_array( $raw, [ 'checked', '1', 'true', 'yes' ], true ) ) {
			return true;
		}
		// Ninja checkboxes can define a custom "checked" value; a default that
		// equals it (and isn't blank) means the box starts checked.
		$checked_value = strtolower( trim( $this->str_arg( $field, 'checked_value' ) ) );
		return '' !== $checked_value && $raw === $checked_value;
	}

	/**
	 * Build SureForms dropdown options for a Ninja `listcountry` field from the
	 * bundled, server-readable country list at `inc/fields/countries.json`
	 * (Ninja auto-populates its country list at render, so the source field
	 * carries no options of its own).
	 *
	 * @since 2.11.0
	 *
	 * @return array<int,array<string,string>>
	 */
	private function country_options() {
		$path = SRFM_DIR . 'inc/fields/countries.json';
		// Local bundled catalogue — wp_remote_get() is for remote URLs only.
		$raw  = is_readable( $path ) ? file_get_contents( $path ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$list = is_string( $raw ) ? json_decode( $raw, true ) : null;
		$out  = [];
		if ( is_array( $list ) ) {
			foreach ( $list as $entry ) {
				if ( is_array( $entry ) ) {
					$name = $this->str_arg( $entry, 'name' );
					if ( '' !== $name ) {
						$out[] = [ 'label' => $name ];
					}
				}
			}
		}
		return ! empty( $out ) ? $out : [ [ 'label' => 'Option 1' ] ];
	}

	/**
	 * Built-in US state list for a Ninja `liststate` field. Ninja's State
	 * dropdown auto-populates the 50 US states + DC at render time, so the
	 * stored field carries no options to read; this mirrors that catalogue.
	 *
	 * @since 2.11.0
	 *
	 * @return array<int,array<string,string>>
	 */
	private function state_options() {
		$states = [
			'Alabama',
			'Alaska',
			'Arizona',
			'Arkansas',
			'California',
			'Colorado',
			'Connecticut',
			'Delaware',
			'District of Columbia',
			'Florida',
			'Georgia',
			'Hawaii',
			'Idaho',
			'Illinois',
			'Indiana',
			'Iowa',
			'Kansas',
			'Kentucky',
			'Louisiana',
			'Maine',
			'Maryland',
			'Massachusetts',
			'Michigan',
			'Minnesota',
			'Mississippi',
			'Missouri',
			'Montana',
			'Nebraska',
			'Nevada',
			'New Hampshire',
			'New Jersey',
			'New Mexico',
			'New York',
			'North Carolina',
			'North Dakota',
			'Ohio',
			'Oklahoma',
			'Oregon',
			'Pennsylvania',
			'Rhode Island',
			'South Carolina',
			'South Dakota',
			'Tennessee',
			'Texas',
			'Utah',
			'Vermont',
			'Virginia',
			'Washington',
			'West Virginia',
			'Wisconsin',
			'Wyoming',
		];
		$out    = [];
		foreach ( $states as $state ) {
			$out[] = [ 'label' => $state ];
		}
		return $out;
	}

	/**
	 * Dispatch a method through `srfm_migrator_block_template`, flag
	 * unsupported if no subscriber answers.
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
	 * After emitting a block, capture its block_id under the source
	 * field's `key` (slug) so conditional logic can rewrite targets.
	 *
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function translate_email_notifications_from_actions() {
		$actions       = $this->fetch_actions( $this->current_form_id );
		$notifications = [];
		$id            = 1;
		foreach ( $actions as $action ) {
			if ( 'email' !== ( $action['type'] ?? '' ) ) {
				continue;
			}
			$settings = isset( $action['settings'] ) && is_array( $action['settings'] ) ? $action['settings'] : [];
			$to       = $this->str_arg( $settings, 'to' );
			if ( '' === $to ) {
				$admin = get_option( 'admin_email' );
				$to    = is_string( $admin ) ? $admin : '{admin_email}';
			}
			$from_name  = $this->str_arg( $settings, 'from_name', '{site_title}' );
			$from_email = $this->str_arg( $settings, 'from_address', '{admin_email}' );
			// SureForms reads the full notification shape (id/from_*/cc/bcc) at
			// both render (form-submit.php) and editor (form-metadata.php) time;
			// mirror Migrator_CF7's canonical keys so the editor doesn't fall back
			// to the global-default notification.
			$notifications[] = [
				'id'             => $id,
				'status'         => true,
				'is_raw_format'  => false,
				'name'           => $this->str_arg( $action, 'label', __( 'Admin Notification Email', 'sureforms' ) ),
				'email_to'       => $to,
				'email_reply_to' => $this->str_arg( $settings, 'reply_to', '{admin_email}' ),
				'from_name'      => '' !== $from_name ? $from_name : '{site_title}',
				'from_email'     => '' !== $from_email ? $from_email : '{admin_email}',
				'email_cc'       => $this->str_arg( $settings, 'cc' ),
				'email_bcc'      => $this->str_arg( $settings, 'bcc' ),
				'subject'        => $this->str_arg( $settings, 'email_subject', __( 'New form submission', 'sureforms' ) ),
				'email_body'     => $this->str_arg( $settings, 'email_message', '{all_data}' ),
			];
			++$id;
		}
		return $notifications;
	}

	/**
	 * Translate Ninja `successmessage` / `redirect` actions into the
	 * SureForms confirmation shape.
	 *
	 * @since 2.11.0
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
	 * Source types Ninja Forms ships but SureForms has no peer for.
	 *
	 * @since 2.11.0
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
