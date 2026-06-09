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
 * @since   2.11.0
 */

namespace SRFM\Inc\Migrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base_Migrator
 *
 * @since 2.11.0
 */
abstract class Base_Migrator {
	/**
	 * Option key for the source→SureForms import map.
	 */
	public const IMPORTED_MAP_OPTION = 'srfm_imported_forms_map';

	/**
	 * Default cap on entries imported per form (overridable via filter).
	 */
	public const DEFAULT_ENTRY_LIMIT = 1000;

	/**
	 * Operators SureForms' conditional-logic editor exposes per block-type
	 * bucket. Mirrors `src/conditional-logic/conditional-logic-options.json`
	 * in SureForms Pro — keep in sync if that schema changes.
	 */
	protected const CL_BUCKET_OPERATORS = [
		'default'    => [ '==', '!=', 'null', '!null', 'includes', '!includes', 'startWith', 'endWith', 'matchesPattern', 'doesNotMatchPattern' ],
		'text'       => [ '==', '!=', 'null', '!null', 'includes', '!includes', 'startWith', 'endWith', 'matchesPattern', 'doesNotMatchPattern' ],
		'number'     => [ '==', '!=', '>', '>=', '<', '<=', 'between', 'matchesPattern', 'doesNotMatchPattern' ],
		'list'       => [ '==', '!=', 'in', '!in', 'isSelected', '!isSelected', 'matchesPattern', 'doesNotMatchPattern' ],
		'checkbox'   => [ 'isChecked', '!isChecked', 'matchesPattern', 'doesNotMatchPattern' ],
		'datepicker' => [ 'datePickerIs', 'isBefore', 'isOnOrBefore', 'isAfter', 'isOnOrAfter' ],
		'timepicker' => [ 'timePickerIs', 'isBefore', 'isOnOrBefore', 'isAfter', 'isOnOrAfter' ],
	];

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
	 * Slugs already used in the current form, for collision avoidance in
	 * `reserve_slug()`. Reset per form by subclasses before calling
	 * `build_form_content()`.
	 *
	 * @var array<string,int>
	 */
	protected $used_slugs = [];

	/**
	 * Request-scoped cache of the imported-forms map. `null` until first read.
	 *
	 * The map option is `autoload=false`, so reading it once per request (rather
	 * than once per source form) avoids an N+1 DB hit on the listing endpoints.
	 *
	 * @var array<int|string,mixed>|null
	 */
	private $imported_map_cache = null;

	/**
	 * List forms in the source plugin, formatted for the picker UI.
	 *
	 * @since 2.11.0
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
	 * Count the source forms without resolving per-form import mappings.
	 *
	 * The sources listing only needs a count; calling list_forms() there would
	 * run find_existing_srfm_id() for every form purely to discard the result.
	 *
	 * @since 2.11.0
	 * @return int
	 */
	public function count_source_forms() {
		if ( ! $this->exist() ) {
			return 0;
		}
		return count( $this->get_source_forms() );
	}

	/**
	 * Import (or dry-run) the selected source forms into SureForms.
	 *
	 * Re-imports honour an optional per-source behavior map:
	 *  - `update` (default) — overwrite the existing SureForms post.
	 *  - `skip`             — leave the existing post untouched; report under `skipped`.
	 *  - `create`           — insert a fresh SureForms post even if one already exists.
	 *
	 * @since 2.11.0
	 *
	 * @param array<int,int|string>    $selected_ids  List of source form ids. Empty = all.
	 * @param bool                     $dry_run       If true, no posts are inserted; a preview is returned.
	 * @param array<int|string,string> $behavior      Per-source-id behavior. Keyed by source id (any cast).
	 * @param string                   $post_status   Status for newly inserted forms; one of `draft`/`publish`.
	 * @param bool                     $skip_existing Force `skip` for any source form already mapped to a
	 *                                                SureForms post — the onboarding "Import all" uses this so it
	 *                                                cannot silently overwrite forms the user already imported +
	 *                                                hand-edited. Per-form $behavior entries still win.
	 * @return array{imported: array<int,array<string,mixed>>, failed: array<int,string>, skipped: array<int,array<string,mixed>>, unsupported_fields: array<int,string>, preview?: array<string,string>}
	 */
	public function import_forms( array $selected_ids = [], $dry_run = false, array $behavior = [], $post_status = 'publish', $skip_existing = false ) {
		$post_status = in_array( $post_status, [ 'draft', 'publish' ], true ) ? $post_status : 'publish';

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

			// Resolve the per-form action. Order of precedence:
			// 1. explicit per-id entry from $behavior (user choice)
			// 2. `$skip_existing` shortcut when there IS an existing import
			// 3. default `update` (re-import overwrites — matches Settings UI default).
			$explicit_action = $behavior[ (string) $source_id ] ?? ( $behavior[ (int) $source_id ] ?? null );
			if ( is_string( $explicit_action ) && in_array( $explicit_action, $allowed_actions, true ) ) {
				$action = $explicit_action;
			} elseif ( $skip_existing && $existing_id ) {
				$action = 'skip';
			} else {
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
				$post_id = $this->insert_form_post( $form, $markup, $metas, $post_status );
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
	 * Source key accessor.
	 *
	 * @since 2.11.0
	 * @return string
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Display title accessor.
	 *
	 * @since 2.11.0
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Whether the source plugin is currently installed/active.
	 *
	 * @since 2.11.0
	 * @return bool
	 */
	abstract public function exist();

	/**
	 * Insert a new sureforms_form post.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $form        Source form descriptor (for title).
	 * @param string              $markup      Block markup for post_content.
	 * @param array<string,mixed> $metas       Optional meta_input payload (key => value).
	 * @param string              $post_status Status for the new post; one of `draft`/`publish`.
	 * @return int Inserted post id, or 0 on failure.
	 */
	protected function insert_form_post( array $form, $markup, array $metas = [], $post_status = 'publish' ) {
		$post_status = in_array( $post_status, [ 'draft', 'publish' ], true ) ? $post_status : 'publish';
		$args        = [
			'post_type'    => SRFM_FORMS_POST_TYPE,
			'post_status'  => $post_status,
			'post_title'   => $this->get_source_form_name( $form ),
			// wp_insert_post applies wp_unslash to post_content; pre-slash so the
			// JSON unicode escapes (e.g. <) survive the round-trip.
			'post_content' => wp_slash( $markup ),
		];
		if ( ! empty( $metas ) ) {
			$args['meta_input'] = $metas;
		}
		$post_id = wp_insert_post( $args, true );
		if ( is_wp_error( $post_id ) ) {
			return 0;
		}
		$post_id = (int) $post_id;

		// Blocks carry the form's post id in their `formId` attribute, which
		// SureForms uses at render to resolve per-block conditional-logic classes
		// (`conditional-trigger`/`conditional-logic`). The id isn't known until
		// the post exists, so stamp it now and re-save the content.
		$stamped = $this->apply_form_id_to_blocks( $markup, $post_id );
		if ( $stamped !== $markup ) {
			wp_update_post(
				[
					'ID'           => $post_id,
					'post_content' => wp_slash( $stamped ),
				]
			);
		}
		return $post_id;
	}

	/**
	 * Update an existing sureforms_form post with re-imported markup.
	 *
	 * @since 2.11.0
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
			// JSON unicode escapes (e.g. <) survive the round-trip. The form id is
			// stamped into each block's `formId` so SureForms can resolve per-block
			// conditional-logic classes at render.
			'post_content' => wp_slash( $this->apply_form_id_to_blocks( $markup, (int) $post_id ) ),
		];
		if ( ! empty( $metas ) ) {
			$args['meta_input'] = $metas;
		}
		$updated = wp_update_post( $args, true );
		return is_wp_error( $updated ) ? 0 : (int) $updated;
	}

	/**
	 * Stamp the form's post id into every SureForms block's `formId` attribute.
	 *
	 * SureForms resolves per-block conditional-logic classes at render from each
	 * block's `formId` (see `Base::$conditional_class`). Migrated markup is built
	 * before the post exists, so the id is injected once it's known.
	 *
	 * Uses a targeted string insert rather than parse_blocks()/serialize_blocks()
	 * on purpose: re-serialising would drop the JSON_HEX escaping the
	 * Block_Templates emitters apply to neutralise hostile labels. Every srfm
	 * block carries at least a `block_id`, so the opening `{` is always followed
	 * by a quoted key — `formId` is inserted ahead of it without touching the
	 * existing (already-escaped) attribute payload.
	 *
	 * @since 2.11.0
	 *
	 * @param string $markup  Serialized block markup.
	 * @param int    $form_id Target form post id.
	 * @return string
	 */
	protected function apply_form_id_to_blocks( $markup, $form_id ) {
		$form_id = (int) $form_id;
		if ( $form_id <= 0 || '' === trim( (string) $markup ) ) {
			return (string) $markup;
		}
		return (string) preg_replace(
			'/(<!--\s+wp:srfm\/[A-Za-z0-9-]+\s+\{)(")/',
			'${1}"formId":"' . $form_id . '",$2',
			(string) $markup
		);
	}

	/**
	 * Find the existing SureForms post id (if any) that was imported from a
	 * given source identifier.
	 *
	 * @since 2.11.0
	 *
	 * @param int|string $source_id Source form id.
	 * @return int 0 if not previously imported.
	 */
	protected function find_existing_srfm_id( $source_id ) {
		$map = $this->get_imported_map();
		foreach ( $map as $srfm_id => $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$entry_source_id = $entry['source_id'] ?? '';
			if ( ! is_scalar( $entry_source_id ) || (string) $entry_source_id !== (string) $source_id ) {
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
			$this->save_imported_map( $map );
			return 0;
		}
		return 0;
	}

	/**
	 * Read the imported-forms map once per request (memoized).
	 *
	 * @since 2.11.0
	 * @return array<int|string,mixed>
	 */
	protected function get_imported_map() {
		if ( null === $this->imported_map_cache ) {
			$map                      = get_option( self::IMPORTED_MAP_OPTION, [] );
			$this->imported_map_cache = is_array( $map ) ? $map : [];
		}
		return $this->imported_map_cache;
	}

	/**
	 * Persist the imported-forms map and refresh the request cache.
	 *
	 * @since 2.11.0
	 *
	 * @param array<int|string,mixed> $map Map to store.
	 * @return void
	 */
	protected function save_imported_map( array $map ) {
		$this->imported_map_cache = $map;
		update_option( self::IMPORTED_MAP_OPTION, $map, false );
	}

	/**
	 * Record an import mapping for future idempotency checks.
	 *
	 * @since 2.11.0
	 *
	 * @param int        $srfm_id   Newly inserted/updated SureForms post id.
	 * @param int|string $source_id Source-plugin form id.
	 * @return void
	 */
	protected function record_import_mapping( $srfm_id, $source_id ) {
		$map                   = $this->get_imported_map();
		$map[ (int) $srfm_id ] = [
			'source_id'     => $source_id,
			'source_key'    => $this->key,
			'last_imported' => current_time( 'mysql' ),
		];
		$this->save_imported_map( $map );
	}

	/**
	 * Push a label onto the unsupported-fields list.
	 *
	 * @since 2.11.0
	 *
	 * @param string $label Field label.
	 * @return void
	 */
	protected function note_unsupported( $label ) {
		$label                      = trim( (string) $label );
		$this->unsupported_fields[] = '' === $label ? __( '(unnamed field)', 'sureforms' ) : $label;
	}

	/**
	 * Reconcile a converted CL operator against a field's block-type bucket.
	 *
	 * SureForms' CL editor only evaluates a restricted operator set per bucket
	 * (e.g. a `list` field supports `==`/`!=`/`in`, not `includes`/`startWith`).
	 * A source rule whose operator doesn't fit its bucket would import but never
	 * evaluate. This returns a usable bucket — the original when valid, or
	 * `default` when the operator is a text-style comparator the default bucket
	 * accepts — or `''` when no bucket supports the operator (caller drops it).
	 *
	 * @since 2.11.0
	 *
	 * @param string $operator SureForms operator (already mapped from source).
	 * @param string $bucket   Field's block-type bucket.
	 * @return string Usable bucket, or '' if the rule should be dropped.
	 */
	protected function resolve_cl_bucket( $operator, $bucket ) {
		$ops = self::CL_BUCKET_OPERATORS;
		if ( isset( $ops[ $bucket ] ) && in_array( $operator, $ops[ $bucket ], true ) ) {
			return $bucket;
		}
		if ( in_array( $operator, $ops['default'], true ) ) {
			return 'default';
		}
		return '';
	}

	/**
	 * Reserve a unique slug for the current form. Generates a slug from the
	 * seed via `sanitize_title()`. If the slug is already taken in this form,
	 * appends `-2`, `-3`, … until a free slot is found. Tracks the slug in
	 * `$this->used_slugs` so subsequent calls within the same form see the
	 * collision.
	 *
	 * Subclasses must reset `$this->used_slugs = []` at the start of each
	 * `build_form_content()` call.
	 *
	 * @since 2.11.0
	 *
	 * @param string $seed Slug seed (source field name or label).
	 * @return string Deduped slug.
	 */
	protected function reserve_slug( $seed ) {
		$base = sanitize_title( (string) $seed );
		if ( '' === $base ) {
			$base = 'field';
		}
		$slug = $base;
		$n    = 2;
		while ( isset( $this->used_slugs[ $slug ] ) ) {
			$slug = $base . '-' . $n;
			++$n;
		}
		$this->used_slugs[ $slug ] = 1;
		return $slug;
	}

	/**
	 * Default confirmation HTML used when the source plugin doesn't ship a
	 * usable thank-you message. Centered heading + body, neutral copy.
	 *
	 * @since 2.11.0
	 *
	 * @return string Confirmation HTML.
	 */
	protected function default_confirmation_message() {
		$heading = esc_html__( 'Thank you', 'sureforms' );
		$body    = esc_html__(
			"Your form has been submitted successfully. We'll review your details and get back to you soon.",
			'sureforms'
		);
		return sprintf(
			'<h2 style="text-align: center;">%1$s</h2><p style="text-align: center;">%2$s</p>',
			$heading,
			$body
		);
	}

	/**
	 * Static dispatch from a template-method name to a `Block_Templates`
	 * emitter. Returns an empty string when the method is unknown — the
	 * caller (after applying the `srfm_migrator_block_template` filter) is
	 * responsible for flagging unsupported fields.
	 *
	 * Shared by every importer so add-on subscribers (e.g. SureForms Pro)
	 * can register new emitter names without each importer re-implementing
	 * the dispatch switch.
	 *
	 * @since 2.11.0
	 *
	 * @param string              $method Template method name.
	 * @param array<string,mixed> $args   Block args.
	 * @return string Block markup, or `''` when unknown.
	 */
	protected function dispatch_template( $method, array $args ) {
		switch ( $method ) {
			case 'input':
				return Block_Templates::input( $args );
			case 'email':
				return Block_Templates::email( $args );
			case 'url':
				return Block_Templates::url( $args );
			case 'phone':
				return Block_Templates::phone( $args );
			case 'number':
				return Block_Templates::number( $args );
			case 'textarea':
				return Block_Templates::textarea( $args );
			case 'dropdown':
				return Block_Templates::dropdown( $args );
			case 'multi_choice':
				return Block_Templates::multi_choice( $args );
			case 'checkbox':
				return Block_Templates::checkbox( $args );
			case 'gdpr':
				return Block_Templates::gdpr( $args );
			default:
				return '';
		}
	}

	/**
	 * Return the list of source forms, normalized to ['id', 'name', ...].
	 *
	 * Each element is treated opaquely by the base class and passed back to
	 * the subclass in `get_source_form_id`, `get_source_form_name`,
	 * `build_form_content`.
	 *
	 * @since 2.11.0
	 * @return array<int,array<string,mixed>>
	 */
	abstract protected function get_source_forms();

	/**
	 * Return the source-side identifier for a given form item.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $form Source form descriptor.
	 * @return int|string
	 */
	abstract protected function get_source_form_id( array $form );

	/**
	 * Return the source-side display name for a given form item.
	 *
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $form Source form descriptor.
	 * @return array<string,mixed> Meta key → meta value.
	 */
	abstract protected function get_form_metas( array $form );
}
