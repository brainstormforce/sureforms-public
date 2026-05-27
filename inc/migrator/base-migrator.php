<?php
/**
 * Base Migrator — abstract template-method parent for every per-source importer.
 *
 * Holds the import loop, idempotency map, and unsupported-field tracking shared
 * across every form-plugin importer. Subclasses implement source-specific
 * detection (`exist`), enumeration (`get_source_forms`), and field translation
 * (`build_form_content`).
 *
 * Architecture mirrors Fluent Forms' `BaseMigrator` (GPL-2.0+), adapted for
 * SureForms' block-markup output and WP REST API patterns.
 *
 * @package sureforms
 * @since   x.x.x
 */

namespace SRFM\Inc\Migrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base_Migrator
 *
 * @since x.x.x
 */
abstract class Base_Migrator {

	/**
	 * Option key for the source→SureForms import map.
	 *
	 * @var string
	 */
	const IMPORTED_MAP_OPTION = 'srfm_imported_forms_map';

	/**
	 * Default cap on entries imported per form (overridable via filter).
	 *
	 * @var int
	 */
	const DEFAULT_ENTRY_LIMIT = 1000;

	/**
	 * Source key — one of: cf7, wpforms, gravity, ninja, caldera.
	 *
	 * Subclasses MUST override.
	 *
	 * @var string
	 */
	protected $key = '';

	/**
	 * Human-readable source name (shown in admin UI).
	 *
	 * Subclasses MUST override.
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Field labels that did not have a SureForms equivalent during the last import.
	 *
	 * @var array<int,string>
	 */
	protected $unsupported_fields = [];

	/**
	 * Whether the source plugin is currently installed/active.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	abstract public function exist();

	/**
	 * Return the list of source forms, normalized to ['id', 'name', ...].
	 *
	 * Each element is treated opaquely by the base class and passed back to
	 * the subclass in `get_source_form_id`, `get_source_form_name`,
	 * `build_form_content`.
	 *
	 * @since x.x.x
	 * @return array<int,array<string,mixed>>
	 */
	abstract protected function get_source_forms();

	/**
	 * Return the source-side identifier for a given form item.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $form Source form descriptor.
	 * @return int|string
	 */
	abstract protected function get_source_form_id( array $form );

	/**
	 * Return the source-side display name for a given form item.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $form Source form descriptor.
	 * @return string
	 */
	abstract protected function get_source_form_name( array $form );

	/**
	 * Build the inner block markup for one source form.
	 *
	 * Implementations should append field labels that fail to map onto
	 * SureForms blocks to `$this->unsupported_fields` so the admin UI can
	 * warn the user.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $form Source form descriptor.
	 * @return string Concatenated field-block markup (without form wrapper).
	 */
	abstract protected function build_form_content( array $form );

	/**
	 * Build the SureForms post-meta payload for one source form.
	 *
	 * Returns a map of `meta_key => meta_value` that will be passed to
	 * `wp_insert_post()` / `wp_update_post()` via `meta_input`. Keys should
	 * be SureForms' canonical meta keys (e.g. `_srfm_email_notification`,
	 * `_srfm_form_confirmation`). Values must already match the schemas
	 * registered in `inc/post-types.php` — the sanitize_callback there will
	 * still run on import.
	 *
	 * Subclasses without source-side metadata (or where the source format
	 * is incompatible — e.g. CF7) should return a sensible default that
	 * leaves the imported form usable.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $form Source form descriptor.
	 * @return array<string,mixed> Meta key → meta value.
	 */
	abstract protected function get_form_metas( array $form );

	/**
	 * List forms in the source plugin, formatted for the picker UI.
	 *
	 * @since x.x.x
	 * @return array<int,array<string,mixed>>
	 */
	public function list_forms() {
		if ( ! $this->exist() ) {
			return [];
		}
		$out = [];
		foreach ( $this->get_source_forms() as $form ) {
			$source_id   = $this->get_source_form_id( $form );
			$existing_id = $this->find_existing_srfm_id( $source_id );
			$out[]       = [
				'id'                     => $source_id,
				'name'                   => $this->get_source_form_name( $form ),
				'imported_srfm_id'       => $existing_id,
				'imported_srfm_edit_url' => $existing_id
					? admin_url( 'post.php?post=' . $existing_id . '&action=edit' )
					: '',
			];
		}
		return $out;
	}

	/**
	 * Import (or dry-run) the selected source forms into SureForms.
	 *
	 * Re-imports honour an optional per-source behavior map:
	 *  - `update` (default) — overwrite the existing SureForms post.
	 *  - `skip`             — leave the existing post untouched; report under `skipped`.
	 *  - `create`           — insert a fresh SureForms post even if one already exists.
	 *
	 * @since x.x.x
	 *
	 * @param array<int,int|string>    $selected_ids List of source form ids. Empty = all.
	 * @param bool                     $dry_run      If true, no posts are inserted; a preview is returned.
	 * @param array<int|string,string> $behavior   Per-source-id behavior. Keyed by source id (any cast).
	 * @return array{imported: array<int,array<string,mixed>>, failed: array<int,string>, skipped: array<int,array<string,mixed>>, unsupported_fields: array<int,string>, preview?: array<string,string>}
	 */
	public function import_forms( array $selected_ids = [], $dry_run = false, array $behavior = [] ) {
		$this->unsupported_fields = [];
		$result                   = [
			'imported'           => [],
			'failed'             => [],
			'skipped'            => [],
			'unsupported_fields' => [],
		];

		if ( ! $this->exist() ) {
			return $result;
		}

		$preview         = [];
		$allowed_actions = [ 'update', 'skip', 'create' ];

		foreach ( $this->get_source_forms() as $form ) {
			$source_id = $this->get_source_form_id( $form );
			if ( ! empty( $selected_ids ) && ! in_array( (string) $source_id, array_map( 'strval', $selected_ids ), true ) ) {
				continue;
			}

			$content = $this->build_form_content( $form );
			if ( '' === trim( $content ) ) {
				$result['failed'][] = $this->get_source_form_name( $form );
				continue;
			}

			// SureForms CPT post_content holds only field blocks at top level.
			// The submit button is NOT a content block — SureForms auto-renders
			// it from the `_srfm_submit_button_text` meta (set in get_form_metas),
			// so we must not append a button block here or the form shows two.
			$markup = $content;

			if ( $dry_run ) {
				$preview[ (string) $source_id ] = $markup;
				continue;
			}

			$metas       = $this->get_form_metas( $form );
			$existing_id = $this->find_existing_srfm_id( $source_id );
			$action      = $behavior[ (string) $source_id ] ?? ( $behavior[ (int) $source_id ] ?? 'update' );
			if ( ! in_array( $action, $allowed_actions, true ) ) {
				$action = 'update';
			}

			if ( $existing_id && 'skip' === $action ) {
				$result['skipped'][] = [
					'srfm_id'   => $existing_id,
					'source_id' => $source_id,
					'name'      => $this->get_source_form_name( $form ),
					'edit_url'  => admin_url( 'post.php?post=' . $existing_id . '&action=edit' ),
				];
				continue;
			}

			if ( $existing_id && 'create' !== $action ) {
				$post_id = $this->update_form_post( $existing_id, $form, $markup, $metas );
			} else {
				$post_id = $this->insert_form_post( $form, $markup, $metas );
			}

			if ( $post_id ) {
				$this->record_import_mapping( $post_id, $source_id );
				$result['imported'][] = [
					'srfm_id'   => $post_id,
					'source_id' => $source_id,
					'name'      => $this->get_source_form_name( $form ),
					'edit_url'  => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
				];
			} else {
				$result['failed'][] = $this->get_source_form_name( $form );
			}
		}

		$result['unsupported_fields'] = array_values( array_unique( array_filter( $this->unsupported_fields ) ) );

		if ( $dry_run ) {
			$result['preview'] = $preview;
		}

		return $result;
	}

	/**
	 * Insert a new sureforms_form post.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $form   Source form descriptor (for title).
	 * @param string              $markup Block markup for post_content.
	 * @param array<string,mixed> $metas  Optional meta_input payload (key => value).
	 * @return int Inserted post id, or 0 on failure.
	 */
	protected function insert_form_post( array $form, $markup, array $metas = [] ) {
		$args = [
			'post_type'    => SRFM_FORMS_POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => $this->get_source_form_name( $form ),
			// wp_insert_post applies wp_unslash to post_content; pre-slash so the
			// JSON unicode escapes (e.g. <) survive the round-trip.
			'post_content' => wp_slash( $markup ),
		];
		if ( ! empty( $metas ) ) {
			$args['meta_input'] = $metas;
		}
		$post_id = wp_insert_post( $args, true );
		return is_wp_error( $post_id ) ? 0 : (int) $post_id;
	}

	/**
	 * Update an existing sureforms_form post with re-imported markup.
	 *
	 * @since x.x.x
	 *
	 * @param int                 $post_id Existing post id.
	 * @param array<string,mixed> $form    Source form descriptor.
	 * @param string              $markup  New post_content.
	 * @param array<string,mixed> $metas   Optional meta_input payload (key => value).
	 * @return int The post id on success, 0 on failure.
	 */
	protected function update_form_post( $post_id, array $form, $markup, array $metas = [] ) {
		$args = [
			'ID'           => $post_id,
			'post_title'   => $this->get_source_form_name( $form ),
			// wp_update_post applies wp_unslash to post_content; pre-slash so the
			// JSON unicode escapes (e.g. <) survive the round-trip.
			'post_content' => wp_slash( $markup ),
		];
		if ( ! empty( $metas ) ) {
			$args['meta_input'] = $metas;
		}
		$updated = wp_update_post( $args, true );
		return is_wp_error( $updated ) ? 0 : (int) $updated;
	}

	/**
	 * Find the existing SureForms post id (if any) that was imported from a
	 * given source identifier.
	 *
	 * @since x.x.x
	 *
	 * @param int|string $source_id Source form id.
	 * @return int 0 if not previously imported.
	 */
	protected function find_existing_srfm_id( $source_id ) {
		$map = get_option( self::IMPORTED_MAP_OPTION, [] );
		if ( ! is_array( $map ) ) {
			return 0;
		}
		foreach ( $map as $srfm_id => $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			if ( (string) ( $entry['source_id'] ?? '' ) !== (string) $source_id ) {
				continue;
			}
			if ( ( $entry['source_key'] ?? '' ) !== $this->key ) {
				continue;
			}
			// Confirm the SureForms post still exists; otherwise prune stale entry.
			$post = get_post( (int) $srfm_id );
			if ( $post && SRFM_FORMS_POST_TYPE === $post->post_type ) {
				return (int) $srfm_id;
			}
			unset( $map[ $srfm_id ] );
			update_option( self::IMPORTED_MAP_OPTION, $map, false );
			return 0;
		}
		return 0;
	}

	/**
	 * Record an import mapping for future idempotency checks.
	 *
	 * @since x.x.x
	 *
	 * @param int        $srfm_id   Newly inserted/updated SureForms post id.
	 * @param int|string $source_id Source-plugin form id.
	 * @return void
	 */
	protected function record_import_mapping( $srfm_id, $source_id ) {
		$map = get_option( self::IMPORTED_MAP_OPTION, [] );
		if ( ! is_array( $map ) ) {
			$map = [];
		}
		$map[ (int) $srfm_id ] = [
			'source_id'     => $source_id,
			'source_key'    => $this->key,
			'last_imported' => current_time( 'mysql' ),
		];
		update_option( self::IMPORTED_MAP_OPTION, $map, false );
	}

	/**
	 * Push a label onto the unsupported-fields list.
	 *
	 * @since x.x.x
	 *
	 * @param string $label Field label.
	 * @return void
	 */
	protected function note_unsupported( $label ) {
		$label                      = trim( (string) $label );
		$this->unsupported_fields[] = '' === $label ? __( '(unnamed field)', 'sureforms' ) : $label;
	}

	/**
	 * Source key accessor.
	 *
	 * @since x.x.x
	 * @return string
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Display title accessor.
	 *
	 * @since x.x.x
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}
}
