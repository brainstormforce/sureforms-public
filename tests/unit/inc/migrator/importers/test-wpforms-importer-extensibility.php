<?php
/**
 * Class Test_Wpforms_Importer_Extensibility
 *
 * The four filter seams introduced in PR #2789 are source-agnostic — each one
 * receives `$key` as the discriminator, so SureForms Pro's WPForms
 * subscriber can wire onto them with `$key === 'wpforms'` gating. These
 * tests register tiny in-test subscribers and confirm the WPForms importer
 * routes through each seam exactly the way the CF7 importer does.
 *
 * @package sureforms
 */

use SRFM\Inc\Migrator\Importers\Wpforms_Importer;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Wpforms_Importer_Extensibility extends TestCase {

	/**
	 * Register the WPForms CPT before each test — see notes on
	 * `Test_Wpforms_Importer::set_up()` for why.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( 1 );
		if ( ! post_type_exists( 'wpforms' ) ) {
			register_post_type(
				'wpforms',
				[
					'label'           => 'WPForms',
					'public'          => false,
					'show_ui'         => false,
					'capability_type' => 'post',
				]
			);
		}
	}

	/**
	 * @return Wpforms_Importer
	 */
	private function make_importer() {
		return new class() extends Wpforms_Importer {
			public function exist() {
				return true;
			}

			/**
			 * @param array<string,mixed> $form Source form descriptor.
			 * @return string
			 */
			public function build_form_content_public( array $form ) {
				return $this->build_form_content( $form );
			}
		};
	}

	/**
	 * @param array<string,mixed> $form_data WPForms data.
	 * @param string              $title     Post title.
	 * @return int Post id.
	 */
	private function make_wpforms( array $form_data, $title = 'Ext WF' ) {
		return (int) wp_insert_post(
			[
				'post_type'    => 'wpforms',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => wp_slash( (string) wp_json_encode( $form_data ) ),
			]
		);
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_all_filters( 'srfm_migrator_preprocess_template' );
		remove_all_filters( 'srfm_migrator_tag_to_template_map' );
		remove_all_filters( 'srfm_migrator_unsupported_tags' );
		remove_all_filters( 'srfm_migrator_block_template' );
		foreach ( [ SRFM_FORMS_POST_TYPE, 'wpforms' ] as $pt ) {
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
	 * srfm_migrator_preprocess_template — subscriber rewrites the decoded
	 * form-data array before iteration (e.g. injects a synthetic field).
	 *
	 * @return void
	 */
	public function test_preprocess_template_filter_can_inject_field() {
		add_filter(
			'srfm_migrator_preprocess_template',
			static function ( $data, $key ) {
				if ( 'wpforms' !== $key ) {
					return $data;
				}
				$data['fields'][99] = [ 'id' => 99, 'type' => 'text', 'label' => 'Injected' ];
				return $data;
			},
			10,
			2
		);
		$id     = $this->make_wpforms(
			[ 'id' => 1, 'fields' => [], 'settings' => [] ]
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertStringContainsString( '"label":"Injected"', $markup );
	}

	/**
	 * srfm_migrator_tag_to_template_map — subscriber adds a new
	 * WPForms-type → template-method entry; the unknown type now dispatches
	 * to the named template.
	 *
	 * @return void
	 */
	public function test_tag_to_template_map_filter_adds_new_mapping() {
		add_filter(
			'srfm_migrator_tag_to_template_map',
			static function ( $map, $key ) {
				if ( 'wpforms' === $key ) {
					$map['signature'] = 'input';
				}
				return $map;
			},
			10,
			2
		);
		$id     = $this->make_wpforms(
			[
				'id'       => 1,
				'fields'   => [ 1 => [ 'id' => 1, 'type' => 'signature', 'label' => 'Sign here' ] ],
				'settings' => [],
			]
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertStringContainsString( 'wp:srfm/input', $markup );
		$this->assertStringContainsString( '"label":"Sign here"', $markup );
	}

	/**
	 * srfm_migrator_block_template — for fields with no built-in emitter
	 * and no map entry, a subscriber that returns markup keeps the field
	 * in; without it, the field would be flagged unsupported.
	 *
	 * @return void
	 */
	public function test_block_template_filter_handles_unknown_method() {
		add_filter(
			'srfm_migrator_block_template',
			static function ( $markup, $method, $args, $key ) {
				if ( 'wpforms' === $key && 'rating' === $method ) {
					return '<!-- wp:srfm/rating {"label":"' . esc_attr( (string) ( $args['label'] ?? '' ) ) . '"} /-->';
				}
				return $markup;
			},
			10,
			4
		);
		// Field map doesn't know `rating` in Free — so the filter is the only
		// path that can produce markup for it.
		add_filter(
			'srfm_migrator_tag_to_template_map',
			static function ( $map, $key ) {
				if ( 'wpforms' === $key ) {
					$map['rating'] = 'rating';
				}
				return $map;
			},
			10,
			2
		);
		$id     = $this->make_wpforms(
			[
				'id'       => 1,
				'fields'   => [ 1 => [ 'id' => 1, 'type' => 'rating', 'label' => 'Score' ] ],
				'settings' => [],
			]
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertStringContainsString( 'wp:srfm/rating', $markup );
		$this->assertStringContainsString( 'Score', $markup );
	}

	/**
	 * When the block-template subscriber returns '' for an unknown type,
	 * the importer still flags the field as unsupported (filter is
	 * additive, not silencing).
	 *
	 * @return void
	 */
	public function test_block_template_filter_falls_through_when_subscriber_returns_empty() {
		add_filter( 'srfm_migrator_block_template', static fn( $markup ) => $markup, 10, 4 );
		$post_id = $this->make_wpforms(
			[
				'id'       => 1,
				'fields'   => [ 1 => [ 'id' => 1, 'type' => 'exotic-widget', 'label' => 'Exotic' ] ],
				'settings' => [],
			]
		);
		$result = $this->make_importer()->import_forms( [ $post_id ], true );
		$this->assertContains( 'Exotic', $result['unsupported_fields'] );
	}

	/**
	 * Filter callbacks receive the WPForms source key, not the CF7 one —
	 * subscribers that intentionally only target CF7 must NOT fire here.
	 *
	 * @return void
	 */
	public function test_filters_dispatch_with_wpforms_key() {
		$seen_keys = [];
		add_filter(
			'srfm_migrator_tag_to_template_map',
			static function ( $map, $key ) use ( &$seen_keys ) {
				$seen_keys[] = $key;
				return $map;
			},
			10,
			2
		);
		$id = $this->make_wpforms(
			[
				'id'       => 1,
				'fields'   => [ 1 => [ 'id' => 1, 'type' => 'text', 'label' => 'X' ] ],
				'settings' => [],
			]
		);
		$this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertContains( 'wpforms', $seen_keys );
		$this->assertNotContains( 'cf7', $seen_keys );
	}
}
