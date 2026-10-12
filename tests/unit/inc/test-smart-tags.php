<?php
/**
 * Class Test_Smart_Tags
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Generate_Form_Markup;
use SRFM\Inc\Helper;
use SRFM\Inc\Smart_Tags;

class Test_Smart_Tags extends TestCase {

	protected $smart_tags;

	/**
	 * Posts created by a test, deleted in tearDown().
	 *
	 * @var int[]
	 */
	private $post_ids = [];

	/**
	 * Request state the request-tag tests change, restored in tearDown().
	 *
	 * @var array<string, mixed>
	 */
	private $saved_request = [];

	protected function setUp(): void {
		$this->smart_tags    = new Smart_Tags();
		$this->saved_request = [
			'query_string' => $_SERVER['QUERY_STRING'] ?? null,
			'cookie'       => $_COOKIE,
			'post'         => $GLOBALS['post'] ?? null,
		];
	}

	protected function tearDown(): void {
		if ( null === $this->saved_request['query_string'] ) {
			unset( $_SERVER['QUERY_STRING'] );
		} else {
			$_SERVER['QUERY_STRING'] = $this->saved_request['query_string'];
		}
		$_COOKIE         = $this->saved_request['cookie'];
		$GLOBALS['post'] = $this->saved_request['post'];
		$this->set_rendering_form_id( 0 );
		foreach ( $this->post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
		parent::tearDown();
	}

	public function test_smart_tag_list_returns_array() {
		$result = Smart_Tags::smart_tag_list();
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( '{site_url}', $result );
		$this->assertArrayHasKey( '{admin_email}', $result );
		$this->assertArrayHasKey( '{site_title}', $result );
		$this->assertArrayHasKey( '{form_title}', $result );
		$this->assertArrayHasKey( '{ip}', $result );
		$this->assertArrayHasKey( '{date_mdy}', $result );
		$this->assertArrayHasKey( '{date_dmy}', $result );
		$this->assertArrayHasKey( '{user_email}', $result );
	}

	public function test_email_smart_tag_list_returns_expected() {
		$result = Smart_Tags::email_smart_tag_list();
		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
		$this->assertArrayHasKey( '{admin_email}', $result );
		$this->assertArrayHasKey( '{user_email}', $result );
	}

	public function test_smart_tags_callback_site_url() {
		$result = Smart_Tags::smart_tags_callback( '{site_url}' );
		$this->assertEquals( site_url(), $result );
	}

	public function test_smart_tags_callback_admin_email() {
		$result = Smart_Tags::smart_tags_callback( '{admin_email}' );
		$this->assertEquals( get_option( 'admin_email' ), $result );
	}

	public function test_smart_tags_callback_site_title() {
		$result = Smart_Tags::smart_tags_callback( '{site_title}' );
		$this->assertEquals( get_option( 'blogname' ), $result );
	}

	public function test_smart_tags_callback_date_mdy() {
		$result = Smart_Tags::smart_tags_callback( '{date_mdy}' );
		$this->assertIsString( $result );
		$this->assertMatchesRegularExpression( '/^\d{2}\/\d{2}\/\d{4}$/', $result );
	}

	public function test_smart_tags_callback_date_dmy() {
		$result = Smart_Tags::smart_tags_callback( '{date_dmy}' );
		$this->assertIsString( $result );
		$this->assertMatchesRegularExpression( '/^\d{2}\/\d{2}\/\d{4}$/', $result );
	}

	public function test_process_smart_tags_no_tags() {
		$content = 'Hello World, no tags here';
		$result = $this->smart_tags->process_smart_tags( $content );
		$this->assertEquals( $content, $result );
	}

	public function test_process_smart_tags_empty_content() {
		$result = $this->smart_tags->process_smart_tags( '' );
		$this->assertEmpty( $result );
	}

	public function test_get_the_user_ip_remote_addr() {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		unset( $_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_FORWARDED'], $_SERVER['HTTP_FORWARDED_FOR'], $_SERVER['HTTP_FORWARDED'] );
		$result = Smart_Tags::get_the_user_ip();
		$this->assertEquals( '127.0.0.1', $result );
	}

	public function test_check_form_by_id_invalid() {
		$result = $this->smart_tags->check_form_by_id( 0 );
		$this->assertFalse( $result );
	}

	/**
	 * Test parse_form_input returns value when no submission data.
	 */
	public function test_parse_form_input_no_data() {
		$result = Smart_Tags::parse_form_input( '{form:name}' );
		$this->assertEquals( '{form:name}', $result );
	}

	/**
	 * Test parse_form_input returns value when no form tag match.
	 */
	public function test_parse_form_input_no_tag() {
		$result = Smart_Tags::parse_form_input( 'plain text', [ 'key' => 'val' ] );
		$this->assertEquals( 'plain text', $result );
	}

	/**
	 * Test parse_form_input resolves a text field smart tag.
	 */
	public function test_parse_form_input_text_field() {
		$submission_data = [
			'srfm-input-abc123-lbl-input-name' => 'John Doe',
		];
		$result = Smart_Tags::parse_form_input( '{form:name}', $submission_data );
		$this->assertStringContainsString( 'John Doe', $result );
	}

	/**
	 * Test parse_form_input decodes rawurlencode'd upload URLs.
	 */
	public function test_parse_form_input_upload_decodes_urls() {
		$submission_data = [
			'srfm-upload-abc123-lbl-upload-resume' => [
				rawurlencode( 'https://example.com/uploads/my file.pdf' ),
				rawurlencode( 'https://example.com/uploads/doc (2).pdf' ),
			],
		];
		// With raw format type, should return comma-separated decoded URLs.
		$form_data = [ 'upload_format_type' => 'raw' ];
		$result    = Smart_Tags::parse_form_input( '{form:resume}', $submission_data, $form_data );
		$this->assertStringContainsString( 'https://example.com/uploads/my file.pdf', $result );
		$this->assertStringContainsString( 'https://example.com/uploads/doc (2).pdf', $result );
		$this->assertStringNotContainsString( '%20', $result );
	}

	/**
	 * Test parse_form_input with single upload file returns anchor tag with decoded URL.
	 */
	public function test_parse_form_input_single_upload_returns_link() {
		$submission_data = [
			'srfm-upload-abc123-lbl-upload-resume' => [
				rawurlencode( 'https://example.com/uploads/my file.pdf' ),
			],
		];
		$result = Smart_Tags::parse_form_input( '{form:resume}', $submission_data );
		$this->assertStringContainsString( '<a ', $result );
		$this->assertStringContainsString( 'my file.pdf', $result );
	}

	public function test_parse_payment_smart_tag_no_submission_data() {
		$result = Smart_Tags::parse_payment_smart_tag( '{form-payment:slug:amount}', null );
		$this->assertEquals( '', $result );
	}

	public function test_parse_payment_smart_tag_invalid_property() {
		$result = Smart_Tags::parse_payment_smart_tag( '{form-payment:slug:invalid}', [ 'test' => 'data' ] );
		$this->assertEquals( '', $result );
	}

	/**
	 * Create a post of the given type and make it the global post.
	 *
	 * @param string $post_type Post type.
	 * @return int Post ID.
	 */
	private function make_current_post( $post_type ) {
		$post_id          = wp_insert_post(
			[
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'post_title'  => 'Smart tag scope ' . $post_type,
			]
		);
		$this->post_ids[] = $post_id;
		$GLOBALS['post']  = get_post( $post_id );
		return $post_id;
	}

	/**
	 * Set the form id that Generate_Form_Markup is rendering.
	 *
	 * @param int $form_id Form ID, or 0 for none.
	 */
	private function set_rendering_form_id( $form_id ) {
		$property = new ReflectionProperty( Generate_Form_Markup::class, 'rendering_form_id' );
		$property->setAccessible( true );
		$property->setValue( null, $form_id );
	}

	/**
	 * A core paragraph from ordinary post content, as render_block passes it.
	 *
	 * @param string $html Block HTML.
	 * @return array<string, mixed>
	 */
	private function paragraph_block( $html ) {
		return [
			'blockName'    => 'core/paragraph',
			'attrs'        => [],
			'innerHTML'    => $html,
			'innerContent' => [ $html ],
			'innerBlocks'  => [],
		];
	}

	public function test_check_form_by_id_false_for_ordinary_post() {
		$post_id = $this->make_current_post( 'post' );
		$this->assertFalse( $this->smart_tags->check_form_by_id( $post_id ) );
	}

	public function test_check_form_by_id_true_for_form_post() {
		$form_id = $this->make_current_post( SRFM_FORMS_POST_TYPE );
		$this->assertTrue( $this->smart_tags->check_form_by_id( $form_id ) );
	}

	/**
	 * Smart tags belong to SureForms forms. Content of an ordinary post is returned as stored.
	 */
	public function test_render_form_leaves_ordinary_post_content_alone() {
		$this->make_current_post( 'post' );
		$_SERVER['QUERY_STRING'] = 'u=hello';
		$html                    = '<p>See <a href="{get_input:u}">docs</a> {site_title}</p>';

		$this->assertSame( $html, $this->smart_tags->render_form( $html, $this->paragraph_block( $html ) ) );
	}

	public function test_render_form_resolves_tags_on_form_post() {
		$this->make_current_post( SRFM_FORMS_POST_TYPE );
		$_SERVER['QUERY_STRING'] = 'u=hello';
		$html                    = '<p>{get_input:u}</p>';

		$this->assertSame( '<p>hello</p>', $this->smart_tags->render_form( $html, $this->paragraph_block( $html ) ) );
	}

	/**
	 * Static blocks of a form embedded in a page (shortcode, Elementor, Bricks) still resolve,
	 * and {form_title} uses the form being rendered, not the host page.
	 */
	public function test_render_form_resolves_tags_while_a_form_renders_in_a_page() {
		$form_id = $this->make_current_post( SRFM_FORMS_POST_TYPE );
		$this->make_current_post( 'page' );
		$this->set_rendering_form_id( $form_id );
		$html = '<h2>{form_title}</h2>';

		$this->assertSame(
			'<h2>' . get_the_title( $form_id ) . '</h2>',
			$this->smart_tags->render_form( $html, $this->paragraph_block( $html ) )
		);
	}

	/**
	 * get_form_markup() marks its content as form content while it renders, and clears the mark after.
	 */
	public function test_get_form_markup_resolves_tags_in_form_content_on_a_page() {
		$form_id = wp_insert_post(
			[
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => 'Scoped Form Title',
				'post_content' => "<!-- wp:paragraph -->\n<p>Title: {form_title}</p>\n<!-- /wp:paragraph -->",
			]
		);
		$this->post_ids[] = $form_id;
		$this->make_current_post( 'page' );

		$markup = Helper::get_string_value( Generate_Form_Markup::get_form_markup( $form_id ) );

		$this->assertStringContainsString( 'Title: Scoped Form Title', $markup );
		$this->assertSame( 0, Generate_Form_Markup::get_rendering_form_id() );
	}

	/**
	 * Blocks rendered from a hook inside get_form_markup(), as Pro's page break and
	 * conversational forms do, are form content too.
	 */
	public function test_get_form_markup_resolves_tags_in_blocks_rendered_from_hooks() {
		$form_id = wp_insert_post(
			[
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => 'Hooked Form Title',
				'post_content' => "<!-- wp:paragraph -->\n<p>Body</p>\n<!-- /wp:paragraph -->",
			]
		);
		$this->post_ids[] = $form_id;
		$this->make_current_post( 'page' );
		$render_from_hook = static function () {
			echo do_blocks( "<!-- wp:paragraph -->\n<p>Hook: {form_title}</p>\n<!-- /wp:paragraph -->" ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		};
		add_action( 'srfm_after_field_content', $render_from_hook );

		try {
			$markup = Helper::get_string_value( Generate_Form_Markup::get_form_markup( $form_id ) );
		} finally {
			remove_action( 'srfm_after_field_content', $render_from_hook );
		}

		$this->assertStringContainsString( 'Hook: Hooked Form Title', $markup );
	}

	/**
	 * get_form_markup() for a post that is not a form does not make its content form content.
	 */
	public function test_get_form_markup_ignores_non_form_post() {
		$post_id = wp_insert_post(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_content' => "<!-- wp:paragraph -->\n<p>{site_title}</p>\n<!-- /wp:paragraph -->",
			]
		);
		$this->post_ids[] = $post_id;
		$this->make_current_post( 'page' );

		$markup = Helper::get_string_value( Generate_Form_Markup::get_form_markup( $post_id ) );

		$this->assertStringContainsString( '{site_title}', $markup );
	}

	/**
	 * A SureForms field block placed directly in ordinary post content is not form content.
	 */
	public function test_render_form_ignores_field_block_outside_a_form() {
		$form_id = $this->make_current_post( SRFM_FORMS_POST_TYPE );
		$this->make_current_post( 'post' );
		$_SERVER['QUERY_STRING'] = 'u=hello';
		$html                    = '<div class="srfm-block"><a href="{get_input:u}">x</a></div>';
		$block                   = [
			'blockName' => 'srfm/input',
			'attrs'     => [ 'formId' => $form_id ],
		];

		$this->assertSame( $html, $this->smart_tags->render_form( $html, $block ) );
	}

	/**
	 * Ordinary post content renders through do_blocks() exactly as stored, whatever the request carries.
	 *
	 * @dataProvider stored_link_provider
	 *
	 * @param string $href Stored link target.
	 */
	public function test_do_blocks_keeps_ordinary_post_links_as_stored( $href ) {
		$this->make_current_post( 'post' );
		$_SERVER['QUERY_STRING'] = 'u=javascript:void(0)';
		$html                    = '<p>See <a href="' . $href . '">docs</a></p>';

		$this->assertStringContainsString(
			'href="' . $href . '"',
			do_blocks( "<!-- wp:paragraph -->\n" . $html . "\n<!-- /wp:paragraph -->" )
		);
	}

	public function stored_link_provider() {
		return [
			'exact tag'    => [ '{get_input:u}' ],
			'prefixed tag' => [ '{x/?get_input:u}' ],
		];
	}

	/**
	 * Only {get_input:name} and {get_cookie:name} are request tags, matched in full.
	 *
	 * @dataProvider loose_request_tag_provider
	 *
	 * @param string $tag Brace-wrapped string that is not a request tag.
	 */
	public function test_process_smart_tags_ignores_loose_request_tags( $tag ) {
		$_SERVER['QUERY_STRING'] = 'u=hello';
		$_COOKIE['u']            = 'hello';
		$content                 = '<a href="' . $tag . '">x</a>';

		$this->assertSame( $content, $this->smart_tags->process_smart_tags( $content ) );
	}

	public function loose_request_tag_provider() {
		return [
			'prefixed input'   => [ '{x/?get_input:u}' ],
			'prefixed cookie'  => [ '{x/?get_cookie:u}' ],
			'leading space'    => [ '{ get_input:u}' ],
			'nested scheme'    => [ '{https://a/?get_input:u}' ],
			'trailing garbage' => [ '{get_input:u x}' ],
		];
	}

	public function test_process_smart_tags_resolves_exact_request_tags() {
		$_SERVER['QUERY_STRING'] = 'utm_source=news-letter';
		$_COOKIE['srfm-ref']     = 'partner_1';

		$this->assertSame(
			'news-letter partner_1',
			$this->smart_tags->process_smart_tags( '{get_input:utm_source} {get_cookie:srfm-ref}' )
		);
	}

	/**
	 * A request value that a browser would run as script is dropped.
	 *
	 * @dataProvider script_scheme_provider
	 *
	 * @param string $raw_query Raw query string.
	 */
	public function test_parse_request_param_drops_script_schemes( $raw_query ) {
		$_SERVER['QUERY_STRING'] = $raw_query;

		$this->assertSame( '', Smart_Tags::parse_request_param( '{get_input:u}' ) );
	}

	public function script_scheme_provider() {
		return [
			'javascript'        => [ 'u=javascript:alert(1)' ],
			'mixed case'        => [ 'u=JaVaScRiPt:alert(1)' ],
			'leading space'     => [ 'u=%20javascript:alert(1)' ],
			'tab inside'        => [ 'u=java%09script:alert(1)' ],
			'entity colon'      => [ 'u=javascript%26%2358%3Balert(1)' ],
			'hex entity colon'  => [ 'u=javascript%26%23x3a%3Balert(1)' ],
			'cr reference'      => [ 'u=java%26%2313%3Bscript:alert(1)' ],
			'cr hex reference'  => [ 'u=java%26%23x0D%3Bscript:alert(1)' ],
			'leading cr'        => [ 'u=%26%2313%3Bjavascript:alert(1)' ],
			'padded tab ref'    => [ 'u=java%26%230000009%3Bscript:alert(1)' ],
			'vbscript'          => [ 'u=vbscript:msgbox(1)' ],
			'data'              => [ 'u=data:text/html,x' ],
		];
	}

	/**
	 * An entity esc_attr() does not know is escaped, so a browser decodes it to text, not a colon.
	 */
	public function test_parse_request_param_unknown_entity_decodes_to_text() {
		$_SERVER['QUERY_STRING'] = 'u=javascript%26colon%3Balert(1)';
		$result                  = Smart_Tags::parse_request_param( '{get_input:u}' );

		$this->assertSame( 'javascript&colon;alert(1)', html_entity_decode( $result, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	public function test_parse_request_param_drops_script_scheme_from_cookie() {
		$_COOKIE['u'] = 'javascript:alert(1)';

		$this->assertSame( '', Smart_Tags::parse_request_param( '{get_cookie:u}' ) );
	}

	/**
	 * Ordinary values, including URLs and text with a colon, are kept.
	 *
	 * @dataProvider safe_value_provider
	 *
	 * @param string $raw_query Raw query string.
	 * @param string $expected  Expected value.
	 */
	public function test_parse_request_param_keeps_safe_values( $raw_query, $expected ) {
		$_SERVER['QUERY_STRING'] = $raw_query;

		$this->assertSame( $expected, Smart_Tags::parse_request_param( '{get_input:u}' ) );
	}

	public function safe_value_provider() {
		return [
			'https url'      => [ 'u=https://example.com/docs', 'https://example.com/docs' ],
			'text with time' => [ 'u=Meet%20at%2010:30', 'Meet at 10:30' ],
			'plain word'     => [ 'u=hello', 'hello' ],
		];
	}

	/**
	 * A resolved request value is inert to later smart tag passes (nested blocks, the form block's own pass).
	 */
	public function test_request_value_cannot_inject_a_tag_for_a_later_pass() {
		$_SERVER['QUERY_STRING'] = 'a=java{get_input:b}&b=script:alert(1)';
		$first                   = $this->smart_tags->process_smart_tags( '<a href="{get_input:a}">x</a>' );

		$this->assertSame( $first, $this->smart_tags->process_smart_tags( $first ) );
		$this->assertStringNotContainsString( '{', $first );
	}

	public function test_request_value_braces_render_as_braces() {
		$_SERVER['QUERY_STRING'] = 'u=a%7Bb%7D';

		$this->assertSame( 'a{b}', html_entity_decode( Smart_Tags::parse_request_param( '{get_input:u}' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	public function test_process_smart_tags_resolves_unicode_param_name() {
		$_SERVER['QUERY_STRING'] = 'pr%C3%A9nom=Ana';

		$this->assertSame( 'Ana', $this->smart_tags->process_smart_tags( '{get_input:prénom}' ) );
	}

	public function test_parse_request_param_rejects_loose_tag() {
		$_SERVER['QUERY_STRING'] = 'u=hello';

		$this->assertSame( '', Smart_Tags::parse_request_param( '{x/?get_input:u}' ) );
	}
}
