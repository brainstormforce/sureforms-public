<?php
/**
 * Class Test_Form_Templates_Accounts
 *
 * The Accounts & Submissions templates, the long applications and the bulk
 * order form: their plans, and Pro declarations that name fields of the
 * right block types, so SureForms Pro can build every one of them.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Templates;

class Test_Form_Templates_Accounts extends TestCase {

	/**
	 * The templates of catalog/accounts.php, with the plan each needs.
	 *
	 * @var array<string, string>
	 */
	private const TEMPLATES = [
		'member-registration'        => 'business',
		'guest-post-submission'      => 'business',
		'business-directory-listing' => 'business',
		'bulk-order-form'            => 'business',
		'rental-application'         => 'pro',
		'scholarship-application'    => 'pro',
	];

	/**
	 * Template keys that need SureForms Business to be built at all.
	 *
	 * @var array<int, string>
	 */
	private const BUSINESS_KEYS = [ 'registration', 'cpt', 'entry_limit', 'pdf', 'quiz' ];

	/**
	 * Field types only the Business build of SureForms Pro renders.
	 *
	 * @var array<int, string>
	 */
	private const BUSINESS_FIELDS = [ 'password', 'repeater' ];

	public function set_up() {
		parent::set_up();
		// Emit the Pro and Business blocks, as a Business site would.
		add_filter( 'srfm_ai_field_mapping_has_pro', '__return_true' );
		add_filter( 'srfm_ai_field_mapping_has_business', '__return_true' );
		add_filter( 'srfm_pro_tier', static fn() => 'business' );
	}

	public function tear_down() {
		remove_all_filters( 'srfm_ai_field_mapping_has_pro' );
		remove_all_filters( 'srfm_ai_field_mapping_has_business' );
		remove_all_filters( 'srfm_pro_tier' );
		parent::tear_down();
	}

	/**
	 * Each field slug of a template's built markup with its block name and the
	 * block it sits in ('' at the top level), as SureForms Pro maps them.
	 *
	 * @param array<string, mixed> $template Template.
	 * @return array<string, array{block: string, parent: string}>
	 */
	private function slug_map( $template ) {
		$content = Form_Templates::build_content( $template );
		$this->assertIsString( $content, "Template {$template['id']} failed to build." );

		$map  = [];
		$walk = static function ( $blocks, $parent ) use ( &$walk, &$map ) {
			foreach ( $blocks as $block ) {
				$slug = $block['attrs']['slug'] ?? '';
				if ( '' !== $slug && ! isset( $map[ $slug ] ) && ! empty( $block['attrs']['block_id'] ) ) {
					$map[ $slug ] = [
						'block'  => $block['blockName'],
						'parent' => $parent,
					];
				}
				$walk( $block['innerBlocks'], (string) $block['blockName'] );
			}
		};
		$walk( parse_blocks( $content ), '' );

		return $map;
	}

	/**
	 * Assert a declared slug is a top-level field of one of the given blocks.
	 *
	 * @param array<string, array{block: string, parent: string}> $map    From slug_map().
	 * @param string                                              $slug   Declared slug.
	 * @param array<int, string>                                  $blocks Allowed block names; empty for any.
	 * @param string                                              $where  Message prefix.
	 */
	private function assert_field( $map, $slug, $blocks, $where ) {
		$this->assertArrayHasKey( $slug, $map, "{$where}: {$slug} is not a field of the template." );
		$this->assertSame( '', $map[ $slug ]['parent'], "{$where}: {$slug} is not a top-level field." );
		if ( ! empty( $blocks ) ) {
			$this->assertContains( $map[ $slug ]['block'], $blocks, "{$where}: {$slug} is a {$map[ $slug ]['block']}." );
		}
	}

	public function test_templates_ship_with_the_plan_they_need() {
		foreach ( self::TEMPLATES as $id => $tier ) {
			$template = Form_Templates::get_template( $id );
			$this->assertIsArray( $template, "Template {$id} is missing." );
			$this->assertSame( $tier, Form_Templates::get_required_tier( $template ), "Template {$id} needs the wrong plan." );

			$types    = wp_list_pluck( Form_Templates::resolve_fields( $template, 'business' ), 'fieldType' );
			$business = array_intersect_key( $template, array_flip( self::BUSINESS_KEYS ) ) || array_intersect( self::BUSINESS_FIELDS, $types );
			// Business-only blocks and features need Business; nothing else does, so the rest ask for less.
			$this->assertSame( $business, 'business' === $tier, "Template {$id} asks for more or less than its features need." );
		}
	}

	public function test_every_template_builds_all_its_fields_on_business() {
		foreach ( array_keys( self::TEMPLATES ) as $id ) {
			$template = Form_Templates::get_template( $id );
			$content  = Form_Templates::build_content( $template );
			$fields   = Form_Templates::resolve_fields( $template, 'business' );

			$top = array_filter( parse_blocks( $content ), static fn( $block ) => null !== $block['blockName'] );
			$this->assertCount( count( $fields ), $top, "Template {$id} lost a field while building." );
		}
	}

	public function test_registration_creates_a_subscriber_from_the_form_fields() {
		$template = Form_Templates::get_template( 'member-registration' );
		$map      = $this->slug_map( $template );
		$options  = $template['registration'];

		$this->assertArrayNotHasKey( 'role', $options, 'Templates never choose the role.' );
		$this->assert_field( $map, $options['email'], [ 'srfm/email' ], 'registration email' );
		$this->assert_field( $map, $options['username'], [ 'srfm/input', 'srfm/email' ], 'registration username' );
		$this->assert_field( $map, $options['password'], [ 'srfm/password' ], 'registration password' );
		$this->assert_field( $map, $options['first_name'], [], 'registration first name' );
		$this->assert_field( $map, $options['last_name'], [], 'registration last name' );

		$password = wp_list_filter( Form_Templates::resolve_fields( $template, 'business' ), [ 'fieldType' => 'password' ] );
		$password = reset( $password );
		$this->assertTrue( $password['required'] );
		// One box with show/hide instead of a confirm box.
		$this->assertFalse( $password['isConfirmPassword'] );
		$this->assertTrue( $password['togglePasswordVisibility'] );
		$this->assertTrue( $password['checkPasswordStrength'] );
	}

	public function test_every_registration_template_has_a_password_field() {
		foreach ( Form_Templates::get_templates() as $id => $template ) {
			if ( empty( $template['registration'] ) ) {
				continue;
			}
			$types = wp_list_pluck( Form_Templates::resolve_fields( $template, 'business' ), 'fieldType' );
			$this->assertContains( 'password', $types, "Template {$id} registers users without a password field." );
		}
	}

	public function test_submissions_become_posts_waiting_for_review() {
		foreach ( [ 'guest-post-submission', 'business-directory-listing' ] as $id ) {
			$template = Form_Templates::get_template( $id );
			$map      = $this->slug_map( $template );
			$cpt      = $template['cpt'];

			$this->assertContains( $cpt['status'] ?? 'pending', [ 'pending', 'draft' ], "Template {$id} would publish posts." );
			$this->assertSame( 'post', $cpt['post_type'] ?? 'post' );
			$this->assertNotEmpty( $cpt['title'], "Template {$id} has no post title." );

			foreach ( [ 'title', 'content', 'excerpt' ] as $part ) {
				if ( ! isset( $cpt[ $part ] ) ) {
					continue;
				}
				if ( isset( $map[ $cpt[ $part ] ] ) ) {
					continue; // A field slug.
				}
				// Text with tags: every tag names a field.
				$this->assertGreaterThan( 0, preg_match_all( '/\{form:([a-z0-9-]+)\}/', $cpt[ $part ], $tags ), "Template {$id}: the post {$part} is neither a field nor uses one." );
				foreach ( $tags[1] as $slug ) {
					$this->assertArrayHasKey( $slug, $map, "Template {$id}: the post {$part} uses {$slug}, which is not a field." );
				}
			}
			if ( isset( $cpt['featured_image'] ) ) {
				$this->assert_field( $map, $cpt['featured_image'], [ 'srfm/upload' ], "{$id} featured image" );
			}
		}
	}

	public function test_applications_save_progress_and_attach_their_documents() {
		foreach ( [ 'rental-application', 'scholarship-application' ] as $id ) {
			$template = Form_Templates::get_template( $id );
			$map      = $this->slug_map( $template );
			$fields   = Form_Templates::resolve_fields( $template, 'business' );
			$types    = wp_list_pluck( $fields, 'fieldType' );

			$this->assertTrue( $template['save_resume'], "Template {$id} cannot be saved and resumed." );
			$this->assertContains( 'signature', $types, "Template {$id} is not signed." );
			// Signatures are a Pro block, not Starter; signing comes with a required acknowledgement.
			$this->assertNotContains( 'signature', wp_list_pluck( Form_Templates::resolve_fields( $template, 'starter' ), 'fieldType' ), "Template {$id}: the signature needs the Pro plan." );
			$this->assertNotEmpty( wp_list_filter( $fields, [ 'fieldType' => 'gdpr', 'required' => true ] ), "Template {$id} has no required acknowledgement." );
			$this->assertNotEmpty( Form_Templates::get_preview_image_url( $template ), "Template {$id} has no Premium preview image." );
			$breaks = count( array_filter( $types, static fn( $type ) => 'page-break' === $type ) );
			$this->assertCount( $breaks + 1, $template['steps'], "Template {$id} needs a title for each step." );

			foreach ( $template['attach_uploads'] as $slug ) {
				$this->assert_field( $map, $slug, [ 'srfm/upload' ], "{$id} attach_uploads" );
			}

			// Business adds reminders to finish an abandoned application.
			$partial = $template['partial_entries'];
			$this->assertTrue( $partial['reminder'] );
			$this->assert_field( $map, $partial['email'], [ 'srfm/email' ], "{$id} partial entries" );
		}
	}

	public function test_applications_lose_only_the_business_extras_on_pro() {
		remove_all_filters( 'srfm_pro_tier' );
		add_filter( 'srfm_pro_tier', static fn() => 'pro' );

		foreach ( [ 'rental-application', 'scholarship-application' ] as $id ) {
			$item = wp_list_filter( Form_Templates::get_templates_for_js(), [ 'id' => $id ] );
			$item = reset( $item );

			// Pro's plan is enough; only a Pro build without the template builder (as in this run) asks for an update.
			$this->assertNotSame( 'tier', $item['lock_reason'], "Template {$id} should work on Pro." );
			$this->assertContains( 'save_resume', $item['features'] );
			$this->assertContains( 'signature', $item['features'] );
			$this->assertSame( [ 'partial_entries' ], $item['pro_features'], "Template {$id} should only gain partial entries with Business." );
		}
	}

	public function test_bulk_order_collects_rows_of_items() {
		$template = Form_Templates::get_template( 'bulk-order-form' );
		$map      = $this->slug_map( $template );

		$this->assert_field( $map, 'items', [ 'srfm/repeater' ], 'bulk order items' );
		$this->assertSame( 'srfm/repeater', $map['item']['parent'] );
		$this->assertSame( 'srfm/repeater', $map['quantity']['parent'] );
		$this->assertSame( 'srfm/number', $map['quantity']['block'] );

		// The delivery address is only asked for deliveries.
		foreach ( $template['conditions'] as $target => $rule ) {
			$this->assert_field( $map, $target, [], 'bulk order condition target' );
			foreach ( $rule['logic'] as $group ) {
				foreach ( $group as $condition ) {
					$this->assert_field( $map, $condition[0], [ 'srfm/multi-choice', 'srfm/dropdown' ], 'bulk order condition source' );
				}
			}
		}
	}

	public function test_templates_use_only_known_pro_keys() {
		$known = array_merge( array_keys( Form_Templates::PRO_FEATURE_KEYS ), [ 'id', 'category', 'theme', 'icon', 'requires', 'setup', 'preview_image', 'title', 'description', 'submit_text', 'steps', 'fields', 'confirmation', 'email_subject', 'email_intro', 'autoresponder', 'features' ] );
		foreach ( array_keys( self::TEMPLATES ) as $id ) {
			$this->assertSame( [], array_diff( array_keys( Form_Templates::get_template( $id ) ), $known ), "Template {$id} has an unknown key." );
		}
	}
}
