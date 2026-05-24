<?php
/**
 * Contact Form 7 importer.
 *
 * Parses the shortcode-template stored in `wpcf7_contact_form` post meta
 * `_form` and translates each form-tag into a serialized SureForms block.
 *
 * The shortcode-parsing regexes are adapted from Fluent Forms'
 * `ContactForm7Migrator::formatAsFluentField()` (GPL-2.0+). The block-emit
 * pipeline is SureForms-specific.
 *
 * Source CF7 form-tags supported in v1: text/text*, email/email*, url/url*,
 * tel/tel*, number/number*, range, date, textarea/textarea*, select/select*,
 * checkbox/checkbox*, radio, acceptance, submit. Unsupported tags (file,
 * quiz, captchar, hidden) are flagged via Base_Migrator::note_unsupported().
 *
 * @package sureforms
 * @since   x.x.x
 */

namespace SRFM\Inc\Migrator\Importers;

use SRFM\Inc\Migrator\Base_Migrator;
use SRFM\Inc\Migrator\Block_Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cf7_Importer
 *
 * @since x.x.x
 */
class Cf7_Importer extends Base_Migrator {

	/**
	 * Submit-button label parsed from the source form.
	 *
	 * Captured during `build_form_content()` and applied in
	 * `append_submit_button()` so each migrated form keeps the original CTA.
	 *
	 * @var string
	 */
	private $submit_label = '';

	/**
	 * Constructor — set source identifiers.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->key   = 'cf7';
		$this->title = 'Contact Form 7';
	}

	/**
	 * Whether Contact Form 7 is currently active.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public function exist() {
		return defined( 'WPCF7_PLUGIN' ) || defined( 'WPCF7_VERSION' );
	}

	/**
	 * Map a CF7 form-tag name (without the trailing `*`) to a SureForms block
	 * template method on Block_Templates.
	 *
	 * @since x.x.x
	 *
	 * @return array<string,string>
	 */
	private function tag_to_template_map() {
		return [
			'text'       => 'input',
			'email'      => 'email',
			'url'        => 'url',
			'tel'        => 'phone',
			'number'     => 'number',
			'range'      => 'number',
			'date'       => 'input',
			'textarea'   => 'textarea',
			'select'     => 'dropdown',
			'checkbox'   => 'checkbox',
			'radio'      => 'multi_choice',
			'acceptance' => 'gdpr',
		];
	}

	/**
	 * Enumerate all CF7 forms as `wpcf7_contact_form` posts.
	 *
	 * @since x.x.x
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_source_forms() {
		$posts = get_posts(
			[
				'post_type'      => 'wpcf7_contact_form',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);
		$out   = [];
		foreach ( $posts as $post ) {
			$out[] = [
				'id'   => (int) $post->ID,
				'name' => $post->post_title,
			];
		}
		return $out;
	}

	/**
	 * Get the source form id.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $form Form descriptor from get_source_forms().
	 * @return int
	 */
	protected function get_source_form_id( array $form ) {
		if ( ! isset( $form['id'] ) || ! is_numeric( $form['id'] ) ) {
			return 0;
		}
		return (int) $form['id'];
	}

	/**
	 * Get the source form display name.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $form Form descriptor.
	 * @return string
	 */
	protected function get_source_form_name( array $form ) {
		$name = isset( $form['name'] ) && is_string( $form['name'] ) ? $form['name'] : '';
		return '' === $name ? __( 'Untitled Form', 'sureforms' ) : $name;
	}

	/**
	 * Build SureForms post-meta payload for an imported CF7 form.
	 *
	 * CF7 stores its mail templates in `_mail` / `_mail_2` post meta using
	 * `[field-name]` shortcodes that don't map cleanly to SureForms smart
	 * tags. Rather than ship a half-working translation, we match Fluent
	 * Forms' approach: return a sane default admin notification and a
	 * generic confirmation message. The imported form is fully usable —
	 * users can refine the email body in SureForms' Single Form Settings.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $form Form descriptor.
	 * @return array<string,mixed> SureForms meta_input payload.
	 */
	protected function get_form_metas( array $form ) {
		$title = $this->get_source_form_name( $form );

		$notification = [
			'id'             => 1,
			'status'         => true,
			'is_raw_format'  => false,
			'name'           => __( 'Admin Notification Email', 'sureforms' ),
			'email_to'       => '{admin_email}',
			'email_reply_to' => '{admin_email}',
			'from_name'      => '{site_title}',
			'from_email'     => '{admin_email}',
			'email_cc'       => '',
			'email_bcc'      => '',
			'subject'        => sprintf(
				/* translators: %s: form title from the source CF7 form. */
				__( 'New submission: %s', 'sureforms' ),
				$title
			),
			'email_body'     => '{all_data}',
		];

		$confirmation = [
			'id'                => 1,
			'confirmation_type' => 'same page',
			'page_url'          => '',
			'custom_url'        => '',
			'message'           => $this->default_confirmation_message(),
			'submission_action' => 'hide form',
		];

		return [
			'_srfm_email_notification' => [ $notification ],
			'_srfm_form_confirmation'  => [ $confirmation ],
		];
	}

	/**
	 * Default success message used for imported CF7 forms. Mirrors the
	 * canonical SureForms confirmation HTML registered in
	 * `inc/post-types.php` (centered heading + paragraph).
	 *
	 * @since x.x.x
	 *
	 * @return string Confirmation HTML.
	 */
	private function default_confirmation_message() {
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
	 * Parse a CF7 form's shortcode template into SureForms block markup.
	 *
	 * Pipeline:
	 *   1. Read `_form` post meta.
	 *   2. Split into per-line tokens.
	 *   3. Strip `<label>` wrappers, extract labels, drop quiz tags.
	 *   4. Per form-tag: regex out attributes, map to srfm/* block.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,mixed> $form Form descriptor.
	 * @return string Concatenated field block markup.
	 */
	protected function build_form_content( array $form ) {
		$this->submit_label = '';
		$post_id            = $this->get_source_form_id( $form );
		if ( ! $post_id ) {
			return '';
		}
		$raw = get_post_meta( $post_id, '_form', true );
		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return '';
		}
		$lines     = preg_split( '/\r\n|\r|\n/', $raw );
		$lines     = is_array( $lines ) ? $lines : [];
		$lines     = $this->strip_labels_and_blanks( $lines );
		$tag_blobs = $this->collect_tag_blobs( $lines );
		$content   = '';
		foreach ( $tag_blobs as $blob ) {
			$markup = $this->build_field_from_tag_blob( $blob );
			if ( '' !== $markup ) {
				$content .= $markup;
			}
		}
		return $content;
	}

	/**
	 * Override the submit button to use the CF7-derived label.
	 *
	 * @since x.x.x
	 *
	 * @param string $content Concatenated field markup.
	 * @return string
	 */
	protected function append_submit_button( $content ) {
		$label = '' !== $this->submit_label ? $this->submit_label : __( 'Submit', 'sureforms' );
		return $content . Block_Templates::submit_button( [ 'text' => $label ] );
	}

	/**
	 * Remove `<label>...</label>` wrappers and blank lines, keep the label text
	 * inline so the next pass can pair it with the form-tag.
	 *
	 * @since x.x.x
	 *
	 * @param array<int,string> $lines Raw template lines.
	 * @return array<int,string>
	 */
	private function strip_labels_and_blanks( array $lines ) {
		$out = [];
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			if ( false !== strpos( $line, '<label>' ) || false !== strpos( $line, '</label>' ) ) {
				$line = trim( str_replace( [ '<label>', '</label>' ], '', $line ) );
			}
			if ( '' === $line ) {
				continue;
			}
			$out[] = $line;
		}
		return $out;
	}

	/**
	 * Walk the cleaned lines and produce per-tag blob strings of the form
	 * `Label text [text* name "default" placeholder "foo"]`.
	 *
	 * @since x.x.x
	 *
	 * @param array<int,string> $lines Cleaned template lines.
	 * @return array<int,string>
	 */
	private function collect_tag_blobs( array $lines ) {
		$out   = [];
		$count = count( $lines );
		for ( $i = 0; $i < $count; $i++ ) {
			$line = $lines[ $i ];
			// Skip CF7 quiz tags — no SureForms equivalent.
			if ( 0 === strpos( $line, '[quiz' ) ) {
				$this->note_unsupported( 'Quiz' );
				continue;
			}
			$bracket_pos = strpos( $line, '[' );
			if ( false === $bracket_pos ) {
				// Line has only label text; pair with next line if it holds a tag.
				if ( isset( $lines[ $i + 1 ] ) && false !== strpos( $lines[ $i + 1 ], '[' ) ) {
					$out[] = $line . ' ' . $lines[ $i + 1 ];
					++$i;
				}
				continue;
			}
			// Inline label + tag on the same line.
			$out[] = $line;
		}
		return $out;
	}

	/**
	 * Translate one tag blob into a SureForms block. Handles `[submit]` by
	 * stashing the label rather than emitting a block.
	 *
	 * @since x.x.x
	 *
	 * @param string $blob One tag blob (label + [shortcode]).
	 * @return string Block markup, or '' if not emitted.
	 */
	private function build_field_from_tag_blob( $blob ) {
		// Extract label text (everything before the first `[`).
		$label = '';
		if ( preg_match( '/^(.*?)\[/', $blob, $m ) ) {
			$label = trim( $m[1] );
		}

		// Extract the tag body (between the brackets).
		if ( ! preg_match( '/\[([^\]]+)\]/', $blob, $m ) ) {
			return '';
		}
		$body  = trim( $m[1] );
		$parts = preg_split( '/\s+/', $body );
		if ( ! is_array( $parts ) || empty( $parts ) ) {
			return '';
		}

		$head     = (string) $parts[0];
		$required = '*' === substr( $head, -1 );
		$tag_name = rtrim( $head, '*' );
		$tag_name = strtolower( $tag_name );

		// Handle `[submit "Send"]` — capture label, no block emitted.
		if ( 'submit' === $tag_name ) {
			if ( preg_match_all( '/(["\'])(.*?)\1/', $body, $matches ) && ! empty( $matches[2] ) ) {
				$this->submit_label = (string) $matches[2][0];
			}
			return '';
		}

		// Unsupported tags are flagged explicitly.
		$unsupported_tags = [ 'file', 'captchar', 'hidden' ];
		if ( in_array( $tag_name, $unsupported_tags, true ) ) {
			$this->note_unsupported( '' !== $label ? $label : $tag_name );
			return '';
		}

		$template_map = $this->tag_to_template_map();
		if ( ! isset( $template_map[ $tag_name ] ) ) {
			$this->note_unsupported( '' !== $label ? $label : $tag_name );
			return '';
		}
		$template_method = $template_map[ $tag_name ];

		$attrs = $this->extract_tag_attrs( $body, $tag_name );
		$args  = [
			'label'         => '' !== $label ? $label : ucfirst( $tag_name ),
			'required'      => $required,
			'placeholder'   => $attrs['placeholder'],
			'default_value' => $attrs['default'],
			'min'           => $attrs['min'],
			'max'           => $attrs['max'],
			'min_length'    => $attrs['minlength'],
			'max_length'    => $attrs['maxlength'],
			'options'       => $attrs['choices'],
			'multiple'      => $attrs['multiple'],
		];

		// Date tag → srfm/input with date field type.
		if ( 'date' === $tag_name ) {
			$args['field_type'] = 'date';
		}

		// Acceptance: the quoted text becomes the consent HTML.
		if ( 'acceptance' === $tag_name && ! empty( $attrs['quoted'] ) ) {
			$args['label'] = (string) $attrs['quoted'][0];
		}

		// Multi-select for select tag with `multiple` attribute.
		if ( 'select' === $tag_name && $attrs['multiple'] ) {
			$args['multiple'] = true;
		}

		return $this->dispatch_template( $template_method, $args, '' !== $label ? $label : $tag_name );
	}

	/**
	 * Invoke a Block_Templates method by name with safe static dispatch.
	 *
	 * Avoids `call_user_func` (which PHPStan can't type-check) by enumerating
	 * each supported template; unknown methods are flagged as unsupported.
	 *
	 * @since x.x.x
	 *
	 * @param string              $method   Block_Templates method name.
	 * @param array<string,mixed> $args     Block args.
	 * @param string              $label    Field label (for unsupported notes).
	 * @return string Block markup, or '' if dispatch failed.
	 */
	private function dispatch_template( $method, array $args, $label ) {
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
				$this->note_unsupported( $label );
				return '';
		}
	}

	/**
	 * Extract attribute tokens from a CF7 form-tag body.
	 *
	 * Mirrors the attribute syntax documented at
	 * https://contactform7.com/tag-syntax/ — single-token attrs (`autocomplete:foo`)
	 * and quoted-list attrs (`"opt 1" "opt 2"`).
	 *
	 * @since x.x.x
	 *
	 * @param string $body     Tag body (without the surrounding brackets).
	 * @param string $tag_name Lower-cased tag name (without trailing `*`).
	 * @return array{placeholder:string,default:string,min:string,max:string,minlength:string,maxlength:string,step:string,choices:array<int,string>,quoted:array<int,string>,multiple:bool,autocomplete:string}
	 */
	private function extract_tag_attrs( $body, $tag_name ) {
		$attrs = [
			'placeholder'  => '',
			'default'      => '',
			'min'          => '',
			'max'          => '',
			'minlength'    => '',
			'maxlength'    => '',
			'step'         => '',
			'choices'      => [],
			'quoted'       => [],
			'multiple'     => false,
			'autocomplete' => '',
		];

		if ( preg_match( '/\bmin:([A-Za-z0-9._-]+)/', $body, $m ) ) {
			$attrs['min'] = $m[1];
		}
		if ( preg_match( '/\bmax:([A-Za-z0-9._-]+)/', $body, $m ) ) {
			$attrs['max'] = $m[1];
		}
		if ( preg_match( '/\bminlength:([0-9]+)/', $body, $m ) ) {
			$attrs['minlength'] = $m[1];
		}
		if ( preg_match( '/\bmaxlength:([0-9]+)/', $body, $m ) ) {
			$attrs['maxlength'] = $m[1];
		}
		if ( preg_match( '/\bstep:([0-9.]+)/', $body, $m ) ) {
			$attrs['step'] = $m[1];
		}
		if ( preg_match( '/\bdefault:([A-Za-z0-9_-]+)/', $body, $m ) ) {
			$attrs['default'] = $m[1];
		}
		if ( preg_match( '/(?:placeholder|watermark)\s+"([^"]+)"/', $body, $m ) ) {
			$attrs['placeholder'] = $m[1];
		}
		if ( preg_match( '/\bautocomplete:([A-Za-z0-9_-]+)/', $body, $m ) ) {
			$attrs['autocomplete'] = $m[1];
		}
		if ( false !== strpos( $body, ' multiple' ) || preg_match( '/\bmultiple\b/', $body ) ) {
			$attrs['multiple'] = true;
		}

		// Capture every quoted string in the body for radio/checkbox/select/acceptance.
		if ( preg_match_all( '/(["\'])(.*?)\1/', $body, $matches ) && ! empty( $matches[2] ) ) {
			$attrs['quoted'] = array_values( $matches[2] );
		}

		// Choice-bearing tags (select / radio / checkbox) — the trailing
		// quoted strings are the option labels.
		$choice_tags = [ 'select', 'radio', 'checkbox' ];
		if ( in_array( $tag_name, $choice_tags, true ) ) {
			// `placeholder "foo"` is captured separately; remove it from choices.
			$choices = $attrs['quoted'];
			if ( '' !== $attrs['placeholder'] ) {
				$choices = array_values(
					array_filter(
						$choices,
						static function ( $c ) use ( $attrs ) {
							return $c !== $attrs['placeholder'];
						}
					)
				);
			}
			$attrs['choices'] = $choices;
		}

		return $attrs;
	}
}
