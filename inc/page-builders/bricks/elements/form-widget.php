<?php
/**
 * Bricks SureForms form element.
 *
 * @package sureforms.
 * @since 0.0.5
 */

namespace SRFM\Inc\Page_Builders\Bricks\Elements;

use Spec_Gb_Helper;
use SRFM\Inc\Generate_Form_Markup;
use SRFM\Inc\Helper;
use SRFM\Inc\Page_Builders\Page_Builders;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureForms Bricks element.
 */
class Form_Widget extends \Bricks\Element {
	/**
	 * Element category.
	 *
	 * @var string
	 */
	public $category = 'sureforms';

	/**
	 * Element name.
	 *
	 * @var string
	 */
	public $name = 'sureforms';

	/**
	 * Element icon.
	 *
	 * @var string
	 */
	public $icon = 'ti-layout-accordion-separated';

	/**
	 * Constructor.
	 *
	 * @param array<mixed> $element Element data.
	 */
	public function __construct( $element = null ) {

		if ( bricks_is_builder() ) {
			// call the js functions to handle form submission, load page break, phone, dropdown.
			$this->scripts = [ 'handleBricksPreviewFormSubmission', 'srfmLoadPageBreak', 'srfmInitializePhoneField', 'srfmInitializeDropdown' ];
		}

		parent::__construct( $element );
	}

	/**
	 * Get element name.
	 *
	 * @since 0.0.5
	 * @return string element name.
	 */
	public function get_label() {
		return __( 'SureForms', 'sureforms' );
	}

	/**
	 * Get element keywords.
	 *
	 * @since 0.0.5
	 * @return array<string> element keywords.
	 */
	public function get_keywords() {
		return [
			'sureforms',
			'contact form',
			'form',
			'bricks form',
		];
	}

	/**
	 * Set control groups for the Style tab accordion sections.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function set_control_groups() {
		$this->control_groups['srfm_form_styling'] = [
			'title' => __( 'Form Styling', 'sureforms' ),
			'tab'   => 'style',
		];

		$styling_required = [
			[ 'form-id', '!=', '' ],
			[ 'formTheme', '!=', 'inherit' ],
		];

		$this->control_groups['srfm_layout'] = [
			'title'    => __( 'Layout', 'sureforms' ),
			'tab'      => 'style',
			'required' => $styling_required,
		];

		$this->control_groups['srfm_button'] = [
			'title'    => __( 'Button', 'sureforms' ),
			'tab'      => 'style',
			'required' => $styling_required,
		];

		$this->control_groups['srfm_fields'] = [
			'title'    => __( 'Fields', 'sureforms' ),
			'tab'      => 'style',
			'required' => $styling_required,
		];
	}

	/**
	 * Set element controls.
	 *
	 * @since 0.0.5
	 * @return void
	 */
	public function set_controls() {

		// === CONTENT TAB ===

		// Select Form.
		$this->controls['form-id'] = [

			'tab'         => 'content',
			'label'       => __( 'Form', 'sureforms' ),
			'type'        => 'select',
			'options'     => Helper::get_sureforms_title_with_ids(),
			'placeholder' => __( 'Select Form', 'sureforms' ),
		];

		// Show Form Title Toggle.
		$this->controls['form-title'] = [
			'tab'      => 'content',
			'label'    => __( 'Show Form Title', 'sureforms' ),
			'type'     => 'checkbox',
			'info'     => __( 'Enable this to show form title.', 'sureforms' ),
			'required' => [ 'form-id', '!=', '' ],
		];

		$this->controls['srfm_form_submission_info'] = [
			'tab'      => 'content',
			'content'  => __( 'Form submission will be possible on the frontend.', 'sureforms' ),
			'type'     => 'info',
			'required' => [ 'form-id', '!=', '' ],
		];

		// === STYLE TAB — Form Styling Group ===

		$form_required = [
			[ 'form-id', '!=', '' ],
			[ 'formTheme', '!=', 'inherit' ],
		];

		// Form Theme Select.
		$this->controls['formTheme'] = [
			'group'   => 'srfm_form_styling',
			'label'   => __( 'Form Theme', 'sureforms' ),
			'type'    => 'select',
			'options' => [
				'inherit' => __( 'Inherit', 'sureforms' ),
				'default' => __( 'Default', 'sureforms' ),
			],
			'default' => 'inherit',
		];

		/**
		 * Hook for Pro to add form theme control after inherit toggle.
		 *
		 * @param Form_Widget $element The element instance.
		 * @since x.x.x
		 */
		do_action( 'srfm_bricks_after_basic_styling_controls', $this );

		// Primary Color.
		$this->controls['primaryColor'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Primary Color', 'sureforms' ),
			'type'     => 'color',
			'default'  => '#111C44',
			'required' => $form_required,
		];

		// Text Color.
		$this->controls['textColor'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Text Color', 'sureforms' ),
			'type'     => 'color',
			'default'  => '#1E1E1E',
			'required' => $form_required,
		];

		// Text on Primary Color.
		$this->controls['textOnPrimaryColor'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Text on Primary', 'sureforms' ),
			'type'     => 'color',
			'default'  => '#FFFFFF',
			'required' => $form_required,
		];

		// Background Separator.
		$this->controls['bgSeparator'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Background', 'sureforms' ),
			'type'     => 'separator',
			'required' => $form_required,
		];

		// Background Type.
		$this->controls['bgType'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Type', 'sureforms' ),
			'type'     => 'select',
			'options'  => [
				'color'    => __( 'Color', 'sureforms' ),
				'gradient' => __( 'Gradient', 'sureforms' ),
				'image'    => __( 'Image', 'sureforms' ),
			],
			'default'  => 'color',
			'required' => $form_required,
		];

		// Background Color (when bgType=color).
		$this->controls['bgColor'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Background Color', 'sureforms' ),
			'type'     => 'color',
			'default'  => '#FFFFFF',
			'required' => array_merge( $form_required, [ [ 'bgType', '=', 'color' ] ] ),
		];

		// --- Gradient Controls (when bgType=gradient) ---
		$gradient_required = array_merge( $form_required, [ [ 'bgType', '=', 'gradient' ] ] );

		$this->controls['bgGradientColor1'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Gradient Color 1', 'sureforms' ),
			'type'     => 'color',
			'required' => $gradient_required,
		];

		$this->controls['bgGradientColor1Stop'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Color 1 Location (%)', 'sureforms' ),
			'type'     => 'number',
			'default'  => 0,
			'min'      => 0,
			'max'      => 100,
			'unit'     => '%',
			'required' => $gradient_required,
		];

		$this->controls['bgGradientColor2'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Gradient Color 2', 'sureforms' ),
			'type'     => 'color',
			'required' => $gradient_required,
		];

		$this->controls['bgGradientColor2Stop'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Color 2 Location (%)', 'sureforms' ),
			'type'     => 'number',
			'default'  => 100,
			'min'      => 0,
			'max'      => 100,
			'unit'     => '%',
			'required' => $gradient_required,
		];

		$this->controls['bgGradientType'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Gradient Type', 'sureforms' ),
			'type'     => 'select',
			'options'  => [
				'linear' => __( 'Linear', 'sureforms' ),
				'radial' => __( 'Radial', 'sureforms' ),
			],
			'default'  => 'linear',
			'required' => $gradient_required,
		];

		$this->controls['bgGradientAngle'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Angle', 'sureforms' ),
			'type'     => 'number',
			'default'  => 90,
			'min'      => 0,
			'max'      => 360,
			'unit'     => 'deg',
			'required' => array_merge( $gradient_required, [ [ 'bgGradientType', '=', 'linear' ] ] ),
		];

		// --- Background Image Controls (when bgType=image) ---
		$image_required = array_merge( $form_required, [ [ 'bgType', '=', 'image' ] ] );

		$this->controls['bgImage'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Image', 'sureforms' ),
			'type'     => 'image',
			'required' => $image_required,
		];

		$this->controls['bgImageSize'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Size', 'sureforms' ),
			'type'     => 'select',
			'options'  => [
				'cover'   => __( 'Cover', 'sureforms' ),
				'contain' => __( 'Contain', 'sureforms' ),
				'auto'    => __( 'Auto', 'sureforms' ),
			],
			'default'  => 'cover',
			'required' => $image_required,
		];

		$this->controls['bgImagePosition'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Position', 'sureforms' ),
			'type'     => 'select',
			'options'  => [
				'left top'      => __( 'Left Top', 'sureforms' ),
				'left center'   => __( 'Left Center', 'sureforms' ),
				'left bottom'   => __( 'Left Bottom', 'sureforms' ),
				'center top'    => __( 'Center Top', 'sureforms' ),
				'center center' => __( 'Center Center', 'sureforms' ),
				'center bottom' => __( 'Center Bottom', 'sureforms' ),
				'right top'     => __( 'Right Top', 'sureforms' ),
				'right center'  => __( 'Right Center', 'sureforms' ),
				'right bottom'  => __( 'Right Bottom', 'sureforms' ),
			],
			'default'  => 'center center',
			'required' => $image_required,
		];

		$this->controls['bgImageRepeat'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Repeat', 'sureforms' ),
			'type'     => 'select',
			'options'  => [
				'no-repeat' => __( 'No Repeat', 'sureforms' ),
				'repeat'    => __( 'Repeat', 'sureforms' ),
				'repeat-x'  => __( 'Repeat X', 'sureforms' ),
				'repeat-y'  => __( 'Repeat Y', 'sureforms' ),
			],
			'default'  => 'no-repeat',
			'required' => $image_required,
		];

		$this->controls['bgImageAttachment'] = [
			'group'    => 'srfm_form_styling',
			'label'    => __( 'Attachment', 'sureforms' ),
			'type'     => 'select',
			'options'  => [
				'scroll' => __( 'Scroll', 'sureforms' ),
				'fixed'  => __( 'Fixed', 'sureforms' ),
			],
			'default'  => 'scroll',
			'required' => $image_required,
		];

		// === STYLE TAB — Layout Group ===

		// Form Padding.
		$this->controls['formPadding'] = [
			'group'    => 'srfm_layout',
			'label'    => __( 'Form Padding', 'sureforms' ),
			'type'     => 'spacing',
			'css'      => [],
			'default'  => [
				'top'    => '0',
				'right'  => '0',
				'bottom' => '0',
				'left'   => '0',
				'unit'   => 'px',
			],
			'required' => $form_required,
		];

		// Form Border Radius.
		$this->controls['formBorderRadius'] = [
			'group'    => 'srfm_layout',
			'label'    => __( 'Form Border Radius', 'sureforms' ),
			'type'     => 'spacing',
			'css'      => [],
			'default'  => [
				'top'    => '0',
				'right'  => '0',
				'bottom' => '0',
				'left'   => '0',
				'unit'   => 'px',
			],
			'required' => $form_required,
		];

		/**
		 * Hook for Pro to add additional layout controls (e.g., row/column gap).
		 *
		 * @param Form_Widget $element The element instance.
		 * @since x.x.x
		 */
		do_action( 'srfm_bricks_layout_controls', $this );

		// === STYLE TAB — Button Group ===

		// Button Alignment.
		$this->controls['buttonAlignment'] = [
			'group'    => 'srfm_button',
			'label'    => __( 'Alignment', 'sureforms' ),
			'type'     => 'select',
			'options'  => [
				'left'    => __( 'Left', 'sureforms' ),
				'center'  => __( 'Center', 'sureforms' ),
				'right'   => __( 'Right', 'sureforms' ),
				'justify' => __( 'Full Width', 'sureforms' ),
			],
			'default'  => 'left',
			'required' => $form_required,
		];

		/**
		 * Hook for Pro to add additional button controls.
		 *
		 * @param Form_Widget $element The element instance.
		 * @since x.x.x
		 */
		do_action( 'srfm_bricks_button_controls', $this );

		// === STYLE TAB — Fields Group ===

		// Field Spacing.
		// Hidden when Pro's custom theme is selected (replaced by Row/Column Gap).
		$this->controls['fieldSpacing'] = [
			'group'    => 'srfm_fields',
			'label'    => __( 'Field Spacing', 'sureforms' ),
			'type'     => 'select',
			'options'  => [
				'small'  => __( 'Small', 'sureforms' ),
				'medium' => __( 'Medium', 'sureforms' ),
				'large'  => __( 'Large', 'sureforms' ),
			],
			'default'  => 'medium',
			'required' => array_merge( $form_required, [ [ 'formTheme', '!=', 'custom' ] ] ),
		];

		/**
		 * Hook for Pro to add additional field controls.
		 *
		 * @param Form_Widget $element The element instance.
		 * @since x.x.x
		 */
		do_action( 'srfm_bricks_field_controls', $this );

		/**
		 * Hook for Pro to add additional style sections (e.g., Messages).
		 *
		 * @param Form_Widget $element The element instance.
		 * @since x.x.x
		 */
		do_action( 'srfm_bricks_after_styling_section', $this );
	}

	/**
	 * Enqueue scripts for phone and dropdown field in the Bricks editor.
	 *
	 * @since 0.0.10
	 * @return void
	 */
	public function enqueue_scripts() {
		// enqueue common fields assets for the dropdown and phone fields.
		Page_Builders::enqueue_common_fields_assets();
	}

	/**
	 * Render element.
	 *
	 * @since 0.0.5
	 * @return void
	 */
	public function render() {
		$settings = $this->settings;
		$form_id  = absint( $settings['form-id'] ?? 0 );

		if ( $form_id > 0 ) {
			$form = get_post( $form_id );
			// 'protected' is a custom SureForms post status for password-protected forms.
			if ( ! $form || ! in_array( $form->post_status, [ 'publish', 'protected' ], true ) ) {
				echo esc_html__( 'This form has been deleted or is unavailable.', 'sureforms' );
				return;
			}

			$form_title  = isset( $settings['form-title'] );
			$block_attrs = $this->get_block_attrs( $settings );

			// Get spectra blocks CSS/JS.
			$blocks = parse_blocks( get_post_field( 'post_content', $form_id ) );
			$styles = Spec_Gb_Helper::get_instance()->get_assets( $blocks );
			?>
			<div <?php echo $this->render_attributes( '_root' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php
				// Bypass shortcode — call Generate_Form_Markup directly with block_attrs.
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Generate_Form_Markup.
				echo Generate_Form_Markup::get_form_markup(
					$form_id,
					! $form_title,
					'',
					'post',
					true,
					$block_attrs
				);
				?>
				<style><?php echo $styles['css']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
				<script><?php echo $styles['js']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
			</div>
			<?php
		} else {
			// Show placeholder when no form is selected.
			// phpcs:ignore -- WordPress.Security.EscapeOutput.OutputNotEscaped - Escaping not required.
			echo $this->render_element_placeholder(
				[
					'icon-class'  => $this->icon,
					'description' => esc_html__( 'Select the form that you wish to add here.', 'sureforms' ),
				]
			);
			// phpcs:ignoreEnd
		}
	}

	/**
	 * Resolve a Bricks color value to a hex string.
	 * Bricks color controls may return a string or an array with 'hex'/'raw' keys.
	 *
	 * @param mixed $color_value Raw value from Bricks color control.
	 * @return string|null Hex color string or null.
	 * @since x.x.x
	 */
	public static function resolve_bricks_color( $color_value ) {
		if ( empty( $color_value ) ) {
			return null;
		}

		$raw = null;

		if ( is_string( $color_value ) ) {
			$raw = $color_value;
		} elseif ( is_array( $color_value ) ) {
			$raw = $color_value['hex'] ?? $color_value['raw'] ?? null;
		}

		if ( null === $raw || '' === $raw ) {
			return null;
		}

		// Validate as a CSS-safe color value.
		$hex = sanitize_hex_color( $raw );
		if ( $hex ) {
			return $hex;
		}

		// Allow rgb/rgba/hsl/hsla functional notation.
		if ( preg_match( '/^(rgb|rgba|hsl|hsla)\s*\([^)]+\)$/i', $raw ) ) {
			return $raw;
		}

		return null;
	}

	/**
	 * Map Bricks 'spacing' control to individual camelCase block_attrs keys.
	 * Bricks spacing returns: ['top' => '10', 'right' => '10', ..., 'unit' => 'px']
	 *
	 * @param array<string, mixed> $settings    Bricks settings.
	 * @param string               $setting_key Key in settings for the spacing control.
	 * @param string               $attr_prefix Prefix for output block_attrs keys.
	 * @return array<string, string> Mapped attributes.
	 * @since x.x.x
	 */
	public static function map_bricks_spacing( $settings, $setting_key, $attr_prefix ) {
		$attrs = [];

		if ( empty( $settings[ $setting_key ] ) || ! is_array( $settings[ $setting_key ] ) ) {
			return $attrs;
		}

		$dims          = $settings[ $setting_key ];
		$allowed_units = [ 'px', 'em', 'rem', '%', 'vw', 'vh' ];
		$unit          = in_array( $dims['unit'] ?? 'px', $allowed_units, true ) ? $dims['unit'] : 'px';

		foreach ( [ 'top', 'right', 'bottom', 'left' ] as $side ) {
			if ( isset( $dims[ $side ] ) && '' !== (string) $dims[ $side ] ) {
				$attrs[ $attr_prefix . ucfirst( $side ) ] = intval( $dims[ $side ] ) . $unit;
			}
		}

		return $attrs;
	}

	/**
	 * Build CSS gradient string from individual Bricks controls.
	 * Since Bricks has no built-in gradient group control, we use individual controls.
	 *
	 * @param array<string, mixed> $settings Bricks settings.
	 * @param string               $prefix   Control name prefix (e.g., 'bg' for bgGradientColor1).
	 * @return string|null CSS gradient string or null.
	 * @since x.x.x
	 */
	public static function build_bricks_gradient_css( $settings, $prefix ) {
		$color_1 = self::resolve_bricks_color( $settings[ $prefix . 'GradientColor1' ] ?? null );
		$color_2 = self::resolve_bricks_color( $settings[ $prefix . 'GradientColor2' ] ?? null );

		// Both colors are required.
		if ( ! $color_1 || ! $color_2 ) {
			return null;
		}

		$type        = 'radial' === ( $settings[ $prefix . 'GradientType' ] ?? 'linear' ) ? 'radial' : 'linear';
		$angle       = absint( Helper::get_string_value( $settings[ $prefix . 'GradientAngle' ] ?? 90 ) );
		$color1_stop = absint( Helper::get_string_value( $settings[ $prefix . 'GradientColor1Stop' ] ?? 0 ) );
		$color2_stop = absint( Helper::get_string_value( $settings[ $prefix . 'GradientColor2Stop' ] ?? 100 ) );

		if ( 'radial' === $type ) {
			return sprintf(
				'radial-gradient(at center center, %s %s%%, %s %s%%)',
				$color_1,
				$color1_stop,
				$color_2,
				$color2_stop
			);
		}

		return sprintf(
			'linear-gradient(%sdeg, %s %s%%, %s %s%%)',
			$angle,
			$color_1,
			$color1_stop,
			$color_2,
			$color2_stop
		);
	}

	/**
	 * Convert Bricks settings to block_attrs array.
	 * Uses same camelCase keys as Gutenberg for code reuse with Form_Styling.
	 *
	 * @param array<string, mixed> $settings Bricks element settings.
	 * @return array<string, mixed> Block attributes.
	 * @since x.x.x
	 */
	protected function get_block_attrs( $settings ) {
		$block_attrs = [
			'blockId' => 'bricks-' . ( $this->id ?? uniqid() ),
		];

		// Check form theme — if inheriting, don't pass any custom styling attributes.
		$form_theme               = $settings['formTheme'] ?? 'inherit';
		$block_attrs['formTheme'] = $form_theme;

		if ( 'inherit' === $form_theme ) {
			return $block_attrs;
		}

		// Color controls.
		$color_keys = [
			'primaryColor',
			'textColor',
			'textOnPrimaryColor',
			'bgColor',
		];

		foreach ( $color_keys as $key ) {
			$color = self::resolve_bricks_color( $settings[ $key ] ?? null );
			if ( $color ) {
				$block_attrs[ $key ] = $color;
			}
		}

		// Pass-through keys with allowed values for server-side validation.
		$passthrough_keys = [
			'fieldSpacing'      => [ 'small', 'medium', 'large' ],
			'buttonAlignment'   => [ 'left', 'center', 'right', 'justify' ],
			'bgType'            => [ 'color', 'gradient', 'image' ],
			'bgImageSize'       => [ 'cover', 'contain', 'auto' ],
			'bgImagePosition'   => [ 'left top', 'left center', 'left bottom', 'center top', 'center center', 'center bottom', 'right top', 'right center', 'right bottom' ],
			'bgImageRepeat'     => [ 'no-repeat', 'repeat', 'repeat-x', 'repeat-y' ],
			'bgImageAttachment' => [ 'scroll', 'fixed' ],
		];

		foreach ( $passthrough_keys as $key => $allowed ) {
			if ( isset( $settings[ $key ] ) && in_array( $settings[ $key ], $allowed, true ) ) {
				$block_attrs[ $key ] = $settings[ $key ];
			}
		}

		// Build gradient CSS string from individual Bricks controls.
		if ( 'gradient' === ( $settings['bgType'] ?? '' ) ) {
			$gradient_css = self::build_bricks_gradient_css( $settings, 'bg' );
			if ( $gradient_css ) {
				$block_attrs['bgGradient'] = $gradient_css;
			}
		}

		// Handle spacing controls (4-sided) → individual camelCase keys with unit appended.
		$block_attrs = array_merge( $block_attrs, self::map_bricks_spacing( $settings, 'formPadding', 'formPadding' ) );
		$block_attrs = array_merge( $block_attrs, self::map_bricks_spacing( $settings, 'formBorderRadius', 'formBorderRadius' ) );

		// Handle bgImage — Bricks image control returns ['url', 'id', ...].
		if ( ! empty( $settings['bgImage'] ) && is_array( $settings['bgImage'] ) ) {
			if ( ! empty( $settings['bgImage']['url'] ) ) {
				$block_attrs['bgImage'] = esc_url_raw( $settings['bgImage']['url'] );
			}
		}

		/**
		 * Filter the block attributes for Bricks widget.
		 * Pro uses this to add additional styling attributes.
		 *
		 * @param array<string, mixed> $block_attrs Block attributes.
		 * @param array<string, mixed> $settings    Bricks element settings.
		 * @since x.x.x
		 */
		return apply_filters( 'srfm_bricks_block_attrs', $block_attrs, $settings );
	}
}
