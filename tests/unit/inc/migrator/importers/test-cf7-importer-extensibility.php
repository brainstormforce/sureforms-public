<?php
/**
 * Class Test_Cf7_Importer_Extensibility
 *
 * Coverage for the four filter seams the Free CF7 importer exposes so add-on
 * importers (e.g. SureForms Pro) can plug in extra block mappings without
 * forking the migrator:
 *
 *   - srfm_migrator_preprocess_template
 *   - srfm_migrator_tag_to_template_map
 *   - srfm_migrator_unsupported_tags
 *   - srfm_migrator_block_template
 *
 * Each test wires a tiny ad-hoc subscriber, runs an import, asserts the seam
 * altered the outcome, then removes the subscriber.
 *
 * @package sureforms
 */

use SRFM\Inc\Migrator\Importers\Cf7_Importer;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Cf7_Importer_Extensibility extends TestCase {

	/**
	 * Test-only subclass: forces exist() true and exposes build_form_content().
	 *
	 * @return Cf7_Importer
	 */
	private function make_importer() {
		return new class() extends Cf7_Importer {
			public function exist() {
				return true;
			}

			/**
			 * @param array<string,mixed> $form Source descriptor.
			 * @return string Block markup.
			 */
			public function build_form_content_public( array $form ) {
				return $this->build_form_content( $form );
			}
		};
	}

	/**
	 * @param string $template CF7 shortcode template.
	 * @param string $title    Form title.
	 * @return int Post id.
	 */
	private function make_cf7( $template, $title = 'Ext Form' ) {
		$id = wp_insert_post(
			[
				'post_type'   => 'wpcf7_contact_form',
				'post_status' => 'publish',
				'post_title'  => $title,
			]
		);
		update_post_meta( $id, '_form', $template );
		return (int) $id;
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		// Defensive: strip any filter callbacks the tests may have left
		// attached if an assertion threw before the explicit remove.
		remove_all_filters( 'srfm_migrator_preprocess_template' );
		remove_all_filters( 'srfm_migrator_tag_to_template_map' );
		remove_all_filters( 'srfm_migrator_unsupported_tags' );
		remove_all_filters( 'srfm_migrator_block_template' );
		foreach ( [ SRFM_FORMS_POST_TYPE, 'wpcf7_contact_form' ] as $pt ) {
			$ids = get_posts(
				[
					'post_type'      => $pt,
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				]
			);
			foreach ( $ids as $id ) {
				wp_delete_post( (int) $id, true );
			}
		}
		delete_option( 'srfm_imported_forms_map' );
		parent::tear_down();
	}

	/**
	 * srfm_migrator_preprocess_template — rewriting the raw template surfaces
	 * the new tag downstream (here, `[step]` → `[text* step-marker]`).
	 *
	 * @return void
	 */
	public function test_preprocess_template_filter_rewrites_raw_template() {
		add_filter(
			'srfm_migrator_preprocess_template',
			static fn( $raw ) => str_replace( '[step]', "Step\n[text* step-marker]", $raw )
		);
		$post_id  = $this->make_cf7( "Email\n[email* e]\n[step]\nName\n[text* n]" );
		$importer = $this->make_importer();
		$markup   = $importer->build_form_content_public( [ 'id' => $post_id, 'name' => 'Pre' ] );
		$this->assertStringContainsString( '"slug":"step-marker"', $markup );
	}

	/**
	 * srfm_migrator_tag_to_template_map — extra entries are honoured.
	 *
	 * Wire a subscriber that maps the (otherwise-unknown) tag `signature` to
	 * the existing `input` template, plus a block-template emitter that
	 * proves the new entry made it all the way through dispatch.
	 *
	 * @return void
	 */
	public function test_tag_to_template_map_filter_adds_new_mapping() {
		add_filter(
			'srfm_migrator_tag_to_template_map',
			static function ( $map ) {
				$map['signature'] = 'input';
				return $map;
			}
		);
		$post_id  = $this->make_cf7( "Signature\n[signature your-sig]", 'Sig form' );
		$importer = $this->make_importer();
		$markup   = $importer->build_form_content_public( [ 'id' => $post_id, 'name' => 'Sig' ] );
		// `signature` now reaches dispatch and renders via the input template.
		$this->assertStringContainsString( 'wp:srfm/input', $markup );
		$this->assertStringContainsString( '"label":"Signature"', $markup );
	}

	/**
	 * srfm_migrator_unsupported_tags — dropping `file` and `hidden` from the
	 * default list lets them flow into dispatch (where, with a tag-map
	 * subscriber and a block-template subscriber, an add-on can render them).
	 *
	 * @return void
	 */
	public function test_unsupported_tags_filter_can_remove_entries() {
		add_filter(
			'srfm_migrator_unsupported_tags',
			static fn( $tags ) => array_values( array_diff( $tags, [ 'file', 'hidden' ] ) )
		);
		// Map `file` → `input` so the tag has somewhere to land.
		add_filter(
			'srfm_migrator_tag_to_template_map',
			static function ( $map ) {
				$map['file'] = 'input';
				return $map;
			}
		);
		$post_id  = $this->make_cf7( "Resume\n[file* your-file]", 'File form' );
		$importer = $this->make_importer();
		$result   = $importer->import_forms( [ $post_id ], true );

		$preview = is_array( $result['preview'] ?? null ) ? reset( $result['preview'] ) : '';
		$this->assertStringContainsString( 'wp:srfm/input', (string) $preview );
		$this->assertNotContains( 'Resume', $result['unsupported_fields'], 'File tag should no longer be flagged unsupported.' );
	}

	/**
	 * srfm_migrator_block_template — when dispatch_template returns '' for an
	 * unknown method, the filter is consulted before the tag is flagged as
	 * unsupported. A subscriber that returns markup keeps the field in.
	 *
	 * @return void
	 */
	public function test_block_template_filter_handles_unknown_method() {
		add_filter(
			'srfm_migrator_tag_to_template_map',
			static function ( $map ) {
				$map['date'] = 'date_picker';
				return $map;
			}
		);
		add_filter(
			'srfm_migrator_block_template',
			static function ( $markup, $method, $args ) {
				if ( 'date_picker' === $method ) {
					return '<!-- wp:srfm/date-picker {"label":"' . esc_attr( (string) ( $args['label'] ?? '' ) ) . '"} /-->';
				}
				return $markup;
			},
			10,
			3
		);
		$post_id  = $this->make_cf7( "Birthday\n[date your-dob]", 'Date form' );
		$importer = $this->make_importer();
		$result   = $importer->import_forms( [ $post_id ], true );
		$preview  = is_array( $result['preview'] ?? null ) ? reset( $result['preview'] ) : '';
		$this->assertStringContainsString( 'wp:srfm/date-picker', (string) $preview );
		$this->assertStringContainsString( 'Birthday', (string) $preview );
	}

	/**
	 * When the block-template filter returns '' for an unknown method, the
	 * tag still ends up on the unsupported-fields list — proving the filter
	 * is additive, not silencing.
	 *
	 * @return void
	 */
	public function test_block_template_filter_falls_through_when_subscriber_returns_empty() {
		add_filter(
			'srfm_migrator_tag_to_template_map',
			static function ( $map ) {
				$map['exotic'] = 'exotic_template';
				return $map;
			}
		);
		// Deliberately register a no-op subscriber.
		add_filter( 'srfm_migrator_block_template', static fn( $markup ) => $markup, 10, 4 );
		$post_id  = $this->make_cf7( "Exotic field\n[exotic mystery]" );
		$importer = $this->make_importer();
		$result   = $importer->import_forms( [ $post_id ], true );
		$this->assertContains( 'Exotic field', $result['unsupported_fields'] );
	}
}
