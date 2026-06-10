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
 * @since   2.11.0
 */

namespace SRFM\Inc\Migrator\Importers;

use SRFM\Inc\Migrator\Base_Migrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cf7_Importer
 *
 * @since 2.11.0
 */
class Cf7_Importer extends Base_Migrator {
	/**
	 * Submit-button label parsed from the source form.
	 *
	 * Captured during `build_form_content()` and written to the
	 * `_srfm_submit_button_text` meta in `get_form_metas()` so each migrated
	 * form keeps the original CTA. SureForms auto-renders the submit button
	 * from this meta — it is never a content block.
	 *
	 * @var string
	 */
	private $submit_label = '';

	/**
	 * Map of CF7 field `name` attribute → final SureForms slug for the current
	 * form. Used by `translate_mail_shortcodes()` to rewrite CF7 mail-template
	 * shortcodes like `[your-name]` into SureForms smart tags `{your-name}`
	 * (or the deduped equivalent).
	 *
	 * Note: slug-collision tracking (`used_slugs` + `reserve_slug()`) lives on
	 * `Base_Migrator` so every importer shares the dedupe logic.
	 *
	 * @var array<string,string>
	 */
	private $field_slug_map = [];

	/**
	 * Constructor — set source identifiers.
	 *
	 * @since 2.11.0
	 */
	public function __construct() {
		$this->key   = 'cf7';
		$this->title = 'Contact Form 7';
	}

	/**
	 * Whether Contact Form 7 is currently active.
	 *
	 * @since 2.11.0
	 * @return bool
	 */
	public function exist() {
		return defined( 'WPCF7_PLUGIN' ) || defined( 'WPCF7_VERSION' );
	}

	/**
	 * Enumerate all CF7 forms as `wpcf7_contact_form` posts.
	 *
	 * @since 2.11.0
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_source_forms() {
		$posts = get_posts(
			[
				'post_type'      => 'wpcf7_contact_form',
				'post_status'    => [ 'publish', 'draft', 'private' ],
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
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $form Form descriptor.
	 * @return array<string,mixed> SureForms meta_input payload.
	 */
	protected function get_form_metas( array $form ) {
		$title   = $this->get_source_form_name( $form );
		$post_id = $this->get_source_form_id( $form );

		$cf7_mail     = $post_id ? get_post_meta( $post_id, '_mail', true ) : [];
		$cf7_mail     = is_array( $cf7_mail ) ? $cf7_mail : [];
		$cf7_messages = $post_id ? get_post_meta( $post_id, '_messages', true ) : [];
		$cf7_messages = is_array( $cf7_messages ) ? $cf7_messages : [];

		$default_subject = sprintf(
			/* translators: %s: form title from the source CF7 form. */
			__( 'New submission: %s', 'sureforms' ),
			$title
		);

		$notification = [
			'id'             => 1,
			'status'         => true,
			'is_raw_format'  => false,
			'name'           => __( 'Admin Notification Email', 'sureforms' ),
			'email_to'       => $this->translate_recipient( $cf7_mail['recipient'] ?? '', '{admin_email}' ),
			'email_reply_to' => '{admin_email}',
			'from_name'      => '{site_title}',
			'from_email'     => '{admin_email}',
			'email_cc'       => '',
			'email_bcc'      => '',
			'subject'        => $this->translate_mail_shortcodes( $cf7_mail['subject'] ?? '', $default_subject ),
			'email_body'     => $this->translate_mail_shortcodes( $cf7_mail['body'] ?? '', '{all_data}' ),
		];

		$mail_sent_ok = isset( $cf7_messages['mail_sent_ok'] ) ? trim( (string) $cf7_messages['mail_sent_ok'] ) : '';
		$confirmation = [
			'id'                => 1,
			'confirmation_type' => 'same page',
			'page_url'          => '',
			'custom_url'        => '',
			'message'           => '' !== $mail_sent_ok ? wp_kses_post( $mail_sent_ok ) : $this->default_confirmation_message(),
			'submission_action' => 'hide form',
		];

		$metas = [
			'_srfm_email_notification' => [ $notification ],
			'_srfm_form_confirmation'  => [ $confirmation ],
		];

		// CF7 [submit "Label"] → SureForms submit-button text. SureForms renders
		// the submit button from this meta, so no button block is added to the
		// post content (see Base_Migrator::import_forms).
		if ( '' !== $this->submit_label ) {
			$metas['_srfm_submit_button_text'] = $this->submit_label;
		}

		return $metas;
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
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $form Form descriptor.
	 * @return string Concatenated field block markup.
	 */
	protected function build_form_content( array $form ) {
		$this->submit_label   = '';
		$this->used_slugs     = [];
		$this->field_slug_map = [];
		$post_id              = $this->get_source_form_id( $form );
		if ( ! $post_id ) {
			return '';
		}
		$raw = get_post_meta( $post_id, '_form', true );
		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return '';
		}
		/**
		 * Filters the raw CF7 template string before parsing.
		 *
		 * Lets add-on importers (e.g. SureForms Pro) rewrite the source
		 * template — for instance to replace `[step]` Multi-Step markers with
		 * synthetic field tags that the rest of the pipeline can map to a
		 * SureForms block.
		 *
		 * @since 2.11.0
		 *
		 * @param string              $raw  Raw CF7 `_form` template.
		 * @param string              $key  Migrator source key (always `cf7` here).
		 * @param array<string,mixed> $form Source form descriptor.
		 */
		$raw       = (string) apply_filters( 'srfm_migrator_preprocess_template', $raw, $this->key, $form );
		$lines     = preg_split( '/\r\n|\r|\n/', $raw );
		$lines     = is_array( $lines ) ? $lines : [];
		$lines     = $this->strip_labels_and_blanks( $lines );
		$tag_blobs = $this->collect_tag_blobs( $lines );
		$content   = '';
		foreach ( $tag_blobs as $blob ) {
			// A single line can carry several form-tags (e.g. two-column layouts
			// like `[text* first-name] [text* last-name]`). Split into one
			// sub-blob per tag so every field is emitted, not just the first.
			foreach ( $this->split_blob_into_tag_blobs( $blob ) as $sub_blob ) {
				$markup = $this->build_field_from_tag_blob( $sub_blob );
				if ( '' !== $markup ) {
					$content .= $markup;
				}
			}
		}
		return $content;
	}

	/**
	 * Map a CF7 form-tag name (without the trailing `*`) to a SureForms block
	 * template method on Block_Templates.
	 *
	 * @since 2.11.0
	 *
	 * @return array<string,string>
	 */
	private function tag_to_template_map() {
		$map = [
			'text'       => 'input',
			'email'      => 'email',
			'url'        => 'url',
			'tel'        => 'phone',
			'number'     => 'number',
			'range'      => 'number',
			'date'       => 'input',
			'textarea'   => 'textarea',
			'select'     => 'dropdown',
			// CF7 [checkbox] is always a multi-option group, so it maps to
			// srfm/multi-choice in multi-select mode — NOT srfm/checkbox, which
			// is SureForms' single on/off checkbox and carries no options.
			'checkbox'   => 'multi_choice',
			'radio'      => 'multi_choice',
			'acceptance' => 'gdpr',
		];
		/**
		 * Filters the CF7-tag → Block_Templates-method map.
		 *
		 * Lets add-on importers (e.g. SureForms Pro) overlay extra mappings
		 * such as `['date' => 'date_picker', 'file' => 'upload', 'hidden' =>
		 * 'hidden_field', 'range' => 'slider']`. The corresponding method
		 * must be emitted by a `srfm_migrator_block_template` subscriber.
		 *
		 * @since 2.11.0
		 *
		 * @param array<string,string> $map Tag name → Block_Templates method.
		 * @param string               $key Migrator source key (`cf7`).
		 */
		return (array) apply_filters( 'srfm_migrator_tag_to_template_map', $map, $this->key );
	}

	/**
	 * Translate a CF7 mail-template string into a SureForms smart-tag string.
	 *
	 * Rewrites two flavors of CF7 shortcodes:
	 *  - Field references — `[your-name]` becomes `{your-name}` (or the
	 *    deduped slug from `$this->field_slug_map`).
	 *  - CF7 system tags — `[_post_title]` → `{post_title}`, `[_user_email]`
	 *    → `{email_address}`, and friends. Unknown system tags are dropped.
	 *
	 * Empty input returns the supplied fallback so callers get a sane default.
	 *
	 * @since 2.11.0
	 *
	 * @param string $body     Raw CF7 mail template.
	 * @param string $fallback Returned verbatim when `$body` is blank.
	 * @return string Translated body, or fallback.
	 */
	private function translate_mail_shortcodes( $body, $fallback = '' ) {
		$body = (string) $body;
		if ( '' === trim( $body ) ) {
			return $fallback;
		}

		// CF7 system tags → SureForms smart tags. Only tags with a direct
		// counterpart are mapped; unknown tags are stripped at the end.
		$system_map = [
			'_post_title'  => '{post_title}',
			'_post_url'    => '{post_url}',
			'_post_id'     => '{post_id}',
			'_post_author' => '{author_name}',
			'_user_email'  => '{email_address}',
			'_user_login'  => '{username}',
			'_user_agent'  => '{user_agent}',
			'_remote_ip'   => '{ip_address}',
			'_url'         => '{current_url}',
			'_date'        => '{date}',
			'_time'        => '{time}',
			'_site_title'  => '{site_title}',
			'_site_url'    => '{site_url}',
		];
		foreach ( $system_map as $cf7 => $srfm ) {
			$body = str_replace( '[' . $cf7 . ']', $srfm, $body );
		}

		// Field references — only translate tokens that match a known CF7 name.
		// The callback returns the matched literal when there's no mapping so
		// unrelated bracket text (e.g. "[Reserved]") survives.
		if ( ! empty( $this->field_slug_map ) ) {
			$body = preg_replace_callback(
				'/\[([a-zA-Z0-9_\-]+)\]/',
				function ( $m ) {
					$name = $m[1];
					if ( isset( $this->field_slug_map[ $name ] ) ) {
						return '{' . $this->field_slug_map[ $name ] . '}';
					}
					return $m[0];
				},
				$body
			);
		}

		return is_string( $body ) ? $body : $fallback;
	}

	/**
	 * Translate the CF7 mail recipient string into a SureForms `email_to`
	 * value. Falls back to `{admin_email}` when the recipient is empty or
	 * uses unrecognised tokens (so the imported form still routes to a real
	 * inbox even when the source template referenced `[_user_email]` for an
	 * auto-reply mail that didn't get ported).
	 *
	 * @since 2.11.0
	 *
	 * @param string $recipient CF7 `_mail.recipient` value.
	 * @param string $fallback  Returned when recipient is unusable.
	 * @return string
	 */
	private function translate_recipient( $recipient, $fallback ) {
		$recipient = trim( (string) $recipient );
		if ( '' === $recipient ) {
			return $fallback;
		}
		$translated = $this->translate_mail_shortcodes( $recipient, $fallback );
		// Sanity check: if translation produced empty or only smart tags without
		// a concrete address, keep the fallback so the notification is deliverable.
		if ( '' === trim( $translated ) ) {
			return $fallback;
		}
		return $translated;
	}

	/**
	 * Remove `<label>...</label>` wrappers and blank lines, keep the label text
	 * inline so the next pass can pair it with the form-tag.
	 *
	 * @since 2.11.0
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
	 * @since 2.11.0
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
			// CF7 Multi-Step Forms addon — `[step ...]` wraps groups of fields.
			// Without explicit support, the step boundaries silently flatten into
			// a single SureForms form, hiding navigation logic from the user.
			if ( 0 === strpos( $line, '[step' ) || 0 === strpos( $line, '[/step' ) ) {
				$this->note_unsupported( __( 'Multi-Step Forms addon', 'sureforms' ) );
				continue;
			}
			// CF7 Conditional Fields addon — `[group ...]` wraps conditional groups.
			// As with [step], silently dropping these would produce a form whose
			// behaviour differs from the source. Flag it once per form.
			if ( 0 === strpos( $line, '[group' ) || 0 === strpos( $line, '[/group' ) ) {
				$this->note_unsupported( __( 'Conditional Fields addon', 'sureforms' ) );
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
	 * Split a blob that may hold several form-tags into one sub-blob per field.
	 *
	 * Two-column CF7 templates put multiple tags on one line
	 * (`[text* first-name] [text* last-name]`); resolving only the first tag
	 * silently dropped the rest. Each sub-blob keeps the label text that
	 * precedes its tag. Block-syntax tags with a matching closing tag
	 * (e.g. `[acceptance id] I agree [/acceptance]`) are kept whole, and bare
	 * closing tags are absorbed rather than treated as new fields.
	 *
	 * @since 2.11.0
	 *
	 * @param string $blob One cleaned template blob.
	 * @return array<int,string> One sub-blob per opening form-tag (>= 1 entry).
	 */
	private function split_blob_into_tag_blobs( $blob ) {
		if ( ! preg_match_all( '/\[[^\]]+\]/', $blob, $m, PREG_OFFSET_CAPTURE ) ) {
			return [ $blob ];
		}
		$tokens = $m[0];
		// Only one bracket token: nothing to split, preserve original behaviour.
		if ( count( $tokens ) < 2 ) {
			return [ $blob ];
		}

		$out    = [];
		$cursor = 0; // Start of the current field's label region.
		foreach ( $tokens as $token ) {
			$token_str = (string) $token[0];
			$token_off = (int) $token[1];
			$token_end = $token_off + strlen( $token_str );

			// Bare closing tag (`[/acceptance]`) — absorbed by its opener below.
			if ( 0 === strpos( $token_str, '[/' ) ) {
				continue;
			}

			// If a matching closing tag follows, extend the field through it so
			// block-syntax tags stay intact as a single field.
			$field_end = $token_end;
			if ( preg_match( '/^\[\s*([A-Za-z0-9_-]+)/', $token_str, $nm ) ) {
				$close    = '[/' . strtolower( $nm[1] ) . ']';
				$close_at = stripos( $blob, $close, $token_end );
				if ( false !== $close_at ) {
					$field_end = $close_at + strlen( $close );
				}
			}

			$sub = trim( substr( $blob, $cursor, $field_end - $cursor ) );
			if ( '' !== $sub ) {
				$out[] = $sub;
			}
			$cursor = $field_end;
		}

		return ! empty( $out ) ? $out : [ $blob ];
	}

	/**
	 * Translate one tag blob into a SureForms block. Handles `[submit]` by
	 * stashing the label rather than emitting a block.
	 *
	 * @since 2.11.0
	 *
	 * @param string $blob One tag blob (label + [shortcode]).
	 * @return string Block markup, or '' if not emitted.
	 */
	private function build_field_from_tag_blob( $blob ) {
		// Extract label text (everything before the first `[`).
		$label = '';
		if ( preg_match( '/^(.*?)\[/', $blob, $m ) ) {
			// CF7 templates often wrap field rows in `<p>...</p>` or `<label>...</label>`;
			// strip any HTML so wrapper markup doesn't bleed into the rendered field label.
			$label = trim( wp_strip_all_tags( $m[1] ) );
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

		/**
		 * Filters the list of CF7 tags that have no SureForms equivalent.
		 *
		 * Add-on importers (e.g. SureForms Pro) can drop entries from this
		 * list when they ship a block that covers the tag — Pro removes
		 * `file` and `hidden` because it provides `srfm/upload` and
		 * `srfm/hidden`.
		 *
		 * @since 2.11.0
		 *
		 * @param array<int,string> $tags Lower-cased CF7 tag names.
		 * @param string            $key  Migrator source key (`cf7`).
		 */
		$unsupported_tags = (array) apply_filters(
			'srfm_migrator_unsupported_tags',
			[ 'file', 'captchar', 'hidden' ],
			$this->key
		);
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

		$attrs       = $this->extract_tag_attrs( $body, $tag_name );
		$field_label = '' !== $label ? $label : ucfirst( $tag_name );

		// Acceptance: the quoted text becomes the consent HTML, but reserve the
		// slug from the original `name` attribute so [acceptance accept-this ...]
		// reserves "accept-this", not the long consent sentence.
		$slug_seed = $field_label;
		$cf7_name  = isset( $parts[1] ) ? (string) $parts[1] : '';
		// CF7 field name is the 2nd token unless that token is an option (`size:`, `min:`, …).
		if ( '' !== $cf7_name && false === strpos( $cf7_name, ':' ) && '"' !== $cf7_name[0] && "'" !== $cf7_name[0] ) {
			$slug_seed = $cf7_name;
		}
		$resolved_slug = $this->reserve_slug( $slug_seed );
		if ( '' !== $cf7_name ) {
			$this->field_slug_map[ $cf7_name ] = $resolved_slug;
		}

		$args = [
			'label'         => $field_label,
			'slug'          => $resolved_slug,
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

		// CF7 [date] → srfm/input (plain text). SureForms has no native date
		// field, so this is a best-effort text mapping — the value still
		// imports, it just isn't a date picker.

		// Acceptance consent text becomes the GDPR label. CF7 supports two
		// syntaxes: inline `[acceptance id "I agree"]` (quoted token) and block
		// `[acceptance id] I agree [/acceptance]` (text between the tags).
		if ( 'acceptance' === $tag_name ) {
			if ( ! empty( $attrs['quoted'] ) ) {
				$args['label'] = (string) $attrs['quoted'][0];
			} elseif ( preg_match( '/\[acceptance[^\]]*\](.*?)\[\/acceptance\]/s', $blob, $cm ) ) {
				$consent = trim( wp_strip_all_tags( $cm[1] ) );
				if ( '' !== $consent ) {
					$args['label'] = $consent;
				}
			}
		}

		// CF7 [checkbox] is a multi-option group → render as multi-select
		// (srfm/multi-choice with singleSelection:false).
		if ( 'checkbox' === $tag_name ) {
			$args['multiple'] = true;
		}

		// Multi-select for select tag with `multiple` attribute.
		if ( 'select' === $tag_name && $attrs['multiple'] ) {
			$args['multiple'] = true;
		}

		$fallback_label = '' !== $label ? $label : $tag_name;
		$markup         = $this->dispatch_template( $template_method, $args );
		if ( '' === $markup ) {
			/**
			 * Filters the markup for a template method this importer doesn't
			 * know about, allowing add-ons (e.g. SureForms Pro) to emit blocks
			 * for new template names registered via
			 * `srfm_migrator_tag_to_template_map`.
			 *
			 * Subscribers should return the serialized Gutenberg block string
			 * for the given `$method`+`$args`, or an empty string to fall
			 * through to the unsupported-fields warning.
			 *
			 * @since 2.11.0
			 *
			 * @param string              $markup Default empty string.
			 * @param string              $method Template method name.
			 * @param array<string,mixed> $args   Block args.
			 * @param string              $key    Migrator source key (`cf7`).
			 */
			$markup = (string) apply_filters( 'srfm_migrator_block_template', '', $template_method, $args, $this->key );
		}
		if ( '' === $markup ) {
			$this->note_unsupported( $fallback_label );
		}
		return $markup;
	}

	/**
	 * Match a CF7 `name:value` option, accepting bare, double- or single-quoted
	 * values so multi-word options like `default:"Hello World"` aren't dropped.
	 *
	 * @since 2.11.0
	 *
	 * @param string $name CF7 option name (e.g. `default`, `min`, `max`).
	 * @param string $body Tag body (without the surrounding brackets).
	 * @return string Matched value, or '' if the option is absent.
	 */
	private function match_tag_option( $name, $body ) {
		$pattern = '/\b' . preg_quote( $name, '/' ) . ':(?:"([^"]*)"|\'([^\']*)\'|([^\s"\']+))/';
		if ( ! preg_match( $pattern, $body, $m ) ) {
			return '';
		}
		if ( isset( $m[1] ) && '' !== $m[1] ) {
			return $m[1];
		}
		if ( isset( $m[2] ) && '' !== $m[2] ) {
			return $m[2];
		}
		return $m[3] ?? '';
	}

	/**
	 * Extract attribute tokens from a CF7 form-tag body.
	 *
	 * Mirrors the attribute syntax documented at
	 * https://contactform7.com/tag-syntax/ — single-token attrs (`autocomplete:foo`)
	 * and quoted-list attrs (`"opt 1" "opt 2"`).
	 *
	 * @since 2.11.0
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

		$attrs['min']     = $this->match_tag_option( 'min', $body );
		$attrs['max']     = $this->match_tag_option( 'max', $body );
		$attrs['default'] = $this->match_tag_option( 'default', $body );
		if ( preg_match( '/\bminlength:([0-9]+)/', $body, $m ) ) {
			$attrs['minlength'] = $m[1];
		}
		if ( preg_match( '/\bmaxlength:([0-9]+)/', $body, $m ) ) {
			$attrs['maxlength'] = $m[1];
		}
		if ( preg_match( '/\bstep:([0-9.]+)/', $body, $m ) ) {
			$attrs['step'] = $m[1];
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
