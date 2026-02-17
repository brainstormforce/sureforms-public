<?php
/**
 * Elementor SureForms form widget.
 *
 * @package sureforms.
 * @since 0.0.5
 */

namespace SRFM\Inc\Page_Builders\Elementor;

use Elementor\Plugin;
use Elementor\Widget_Base;
use Spec_Gb_Helper;
use SRFM\Inc\Helper;
use SRFM\Inc\Page_Builders\Page_Builders;
use SRFM\Inc\Generate_Form_Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureForms widget that displays a form.
 */
class Form_Widget extends Widget_Base {
	/**
	 * Whether we are in the preview mode.
	 *
	 * @var bool
	 */
	public $is_preview_mode;

	/**
	 * Constructor.
	 *
	 * @param array<mixed> $data Widget data.
	 * @param array<mixed> $args Widget arguments.
	 */
	public function __construct( $data = [], $args = null ) {

		parent::__construct( $data, $args );

		// (Preview iframe)
		$this->is_preview_mode = \Elementor\Plugin::$instance->preview->is_preview_mode();

		if ( $this->is_preview_mode ) {

			// enqueue common fields assets for the dropdown and phone fields.
			Page_Builders::enqueue_common_fields_assets();

			wp_register_script( 'srfm-elementor-preview', SRFM_URL . 'inc/page-builders/elementor/assets/elementor-editor-preview.js', [ 'elementor-frontend' ], SRFM_VER, true );
			wp_localize_script(
				'srfm-elementor-preview',
				'srfmElementorData',
				[
					'isProActive' => Helper::has_pro(),
				]
			);

			// Register styling preview script for live preview.
			wp_register_script(
				'srfm-elementor-styling-preview',
				SRFM_URL . 'inc/page-builders/elementor/assets/elementor-styling-preview.js',
				[ 'elementor-frontend' ],
				SRFM_VER,
				true
			);
			wp_localize_script(
				'srfm-elementor-styling-preview',
				'srfmElementorStyling',
				[
					'fieldSpacingVars' => Helper::get_css_vars(),
				]
			);

			/**
			 * Hook for Pro to register additional preview assets (scripts and styles).
			 *
			 * @since x.x.x
			 */
			do_action( 'srfm_elementor_register_preview_assets' );
		}
	}

	/**
	 * Get script depends.
	 *
	 * @since 0.0.5
	 * @return array<string> Script dependencies.
	 */
	public function get_script_depends() {
		if ( $this->is_preview_mode ) {
			$scripts = [ 'srfm-elementor-preview', 'srfm-elementor-styling-preview' ];

			/**
			 * Filter the widget's script dependencies.
			 * Pro uses this to add its preview scripts.
			 *
			 * @param array<string> $scripts Script handles.
			 * @since x.x.x
			 */
			return apply_filters( 'srfm_elementor_widget_script_depends', $scripts );
		}

		return [];
	}

	/**
	 * Get style depends.
	 *
	 * @since x.x.x
	 * @return array<string> Style dependencies.
	 */
	public function get_style_depends() {
		$styles = [];

		/**
		 * Filter the widget's style dependencies.
		 * Pro uses this to add its custom-styles CSS.
		 *
		 * @param array<string> $styles Style handles.
		 * @since x.x.x
		 */
		return apply_filters( 'srfm_elementor_widget_style_depends', $styles );
	}

	/**
	 * Get widget name.
	 *
	 * @since 0.0.5
	 * @return string Widget name.
	 */
	public function get_name() {
		return SRFM_FORMS_POST_TYPE;
	}

	/**
	 * Get widget title.
	 *
	 * @since 0.0.5
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'SureForms', 'sureforms' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since 0.0.5
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-form-horizontal srfm-elementor-widget-icon';
	}

	/**
	 * Get widget categories. Used to determine where to display the widget in the editor.
	 *
	 * @since 0.0.5
	 * @return array<string> Widget categories.
	 */
	public function get_categories() {
		return [ 'sureforms-elementor' ];
	}

	/**
	 * Get widget keywords.
	 *
	 * @since 0.0.5
	 * @return array<string> Widget keywords.
	 */
	public function get_keywords() {
		return [
			'sureforms',
			'contact form',
			'form',
			'elementor form',
		];
	}

	/**
	 * Register form widget controls.
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 0.0.5
	 * @return void
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'section_form',
			[
				'label' => __( 'SureForms', 'sureforms' ),
			]
		);

		$this->add_control(
			'srfm_form_block',
			[
				'label'   => __( 'Select Form', 'sureforms' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => Helper::get_sureforms_title_with_ids(),
				'default' => '',
			]
		);

		$this->add_control(
			'srfm_show_form_title',
			[
				'label'        => __( 'Form Title', 'sureforms' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'sureforms' ),
				'label_off'    => __( 'Hide', 'sureforms' ),
				'return_value' => 'true',
				'condition'    => [
					'srfm_form_block!' => [ '' ],
				],
			]
		);

		$this->add_control(
			'srfm_edit_form',
			[
				'label'     => __( 'Edit Form', 'sureforms' ),
				'separator' => 'before',
				'type'      => \Elementor\Controls_Manager::BUTTON,
				'text'      => __( 'Edit', 'sureforms' ),
				'event'     => 'sureforms:form:edit',
				'condition' => [
					'srfm_form_block!' => [ '' ],
				],
			]
		);

		$this->add_control(
			'srfm_create_form',
			[
				'label' => __( 'Create New Form', 'sureforms' ),
				'type'  => \Elementor\Controls_Manager::BUTTON,
				'text'  => __( 'Create', 'sureforms' ),
				'event' => 'sureforms:form:create',
			]
		);

		$this->add_control(
			'srfm_form_submission_info',
			[
				'content'   => __( 'Form submission will be possible on the frontend.', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::ALERT,
				'condition' => [
					'srfm_form_block!' => [ '' ],
				],
			]
		);

		$this->end_controls_section();

		// Style Tab - Form Styling Section.
		$this->start_controls_section(
			'srfm_style_section',
			[
				'label'     => __( 'Form Styling', 'sureforms' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'srfm_form_block!' => '',
				],
			]
		);

		// Inherit Styling Toggle.
		$this->add_control(
			'inheritStyling',
			[
				'label'        => __( 'Inherit Styling from Instant Form', 'sureforms' ),
				'description'  => __( 'When enabled, this form uses Instant Form styling. Disable to customize styling for this embed.', 'sureforms' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'sureforms' ),
				'label_off'    => __( 'No', 'sureforms' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'after',
			]
		);

		// Primary Color.
		$this->add_control(
			'primaryColor',
			[
				'label'     => __( 'Primary Color', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => [
					'inheritStyling!' => 'yes',
				],
			]
		);

		// Text Color.
		$this->add_control(
			'textColor',
			[
				'label'     => __( 'Text Color', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => [
					'inheritStyling!' => 'yes',
				],
			]
		);

		// Text on Primary (button text color).
		$this->add_control(
			'textOnPrimaryColor',
			[
				'label'     => __( 'Text on Primary', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => [
					'inheritStyling!' => 'yes',
				],
			]
		);

		// Background Type Heading.
		$this->add_control(
			'bgHeading',
			[
				'label'     => __( 'Background', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'condition' => [
					'inheritStyling!' => 'yes',
				],
			]
		);

		// Background Type.
		$this->add_control(
			'bgType',
			[
				'label'     => __( 'Type', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'color',
				'options'   => [
					'color'    => __( 'Color', 'sureforms' ),
					'gradient' => __( 'Gradient', 'sureforms' ),
					'image'    => __( 'Image', 'sureforms' ),
				],
				'condition' => [
					'inheritStyling!' => 'yes',
				],
			]
		);

		// Background Color.
		$this->add_control(
			'bgColor',
			[
				'label'     => __( 'Color', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'condition' => [
					'inheritStyling!' => 'yes',
					'bgType'          => 'color',
				],
			]
		);

		// Background Gradient.
		$this->add_control(
			'bgGradient',
			[
				'label'       => __( 'Gradient', 'sureforms' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'linear-gradient(90deg, #FFC9B2 0%, #C7CBFF 100%)',
				'placeholder' => 'linear-gradient(90deg, #color1, #color2)',
				'label_block' => true,
				'condition'   => [
					'inheritStyling!' => 'yes',
					'bgType'          => 'gradient',
				],
			]
		);

		// Background Image.
		$this->add_control(
			'bgImage',
			[
				'label'     => __( 'Image', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::MEDIA,
				'default'   => [
					'url' => '',
				],
				'condition' => [
					'inheritStyling!' => 'yes',
					'bgType'          => 'image',
				],
			]
		);

		// Background Image Size.
		$this->add_control(
			'bgImageSize',
			[
				'label'     => __( 'Size', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => [
					'cover'   => __( 'Cover', 'sureforms' ),
					'contain' => __( 'Contain', 'sureforms' ),
					'auto'    => __( 'Auto', 'sureforms' ),
				],
				'condition' => [
					'inheritStyling!' => 'yes',
					'bgType'          => 'image',
				],
			]
		);

		// Background Image Position.
		$this->add_control(
			'bgImagePosition',
			[
				'label'     => __( 'Position', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'center center',
				'options'   => [
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
				'condition' => [
					'inheritStyling!' => 'yes',
					'bgType'          => 'image',
				],
			]
		);

		// Background Image Repeat.
		$this->add_control(
			'bgImageRepeat',
			[
				'label'     => __( 'Repeat', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'no-repeat',
				'options'   => [
					'no-repeat' => __( 'No Repeat', 'sureforms' ),
					'repeat'    => __( 'Repeat', 'sureforms' ),
					'repeat-x'  => __( 'Repeat X', 'sureforms' ),
					'repeat-y'  => __( 'Repeat Y', 'sureforms' ),
				],
				'condition' => [
					'inheritStyling!' => 'yes',
					'bgType'          => 'image',
				],
			]
		);

		// Background Image Attachment.
		$this->add_control(
			'bgImageAttachment',
			[
				'label'     => __( 'Attachment', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'scroll',
				'options'   => [
					'scroll' => __( 'Scroll', 'sureforms' ),
					'fixed'  => __( 'Fixed', 'sureforms' ),
				],
				'condition' => [
					'inheritStyling!' => 'yes',
					'bgType'          => 'image',
				],
			]
		);

		/**
		 * Hook for Pro to add advanced styling controls.
		 *
		 * @param \Elementor\Widget_Base $widget The widget instance.
		 * @since x.x.x
		 */
		do_action( 'srfm_elementor_after_basic_styling_controls', $this );

		$this->end_controls_section();

		// Style Tab - Layout Section.
		$this->start_controls_section(
			'srfm_layout_section',
			[
				'label'     => __( 'Layout', 'sureforms' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'srfm_form_block!' => '',
					'inheritStyling!'  => 'yes',
				],
			]
		);

		// Form Padding.
		$this->add_control(
			'formPadding',
			[
				'label'      => __( 'Form Padding', 'sureforms' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'default'    => [
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '0',
					'left'     => '0',
					'unit'     => 'px',
					'isLinked' => true,
				],
				'separator'  => 'after',
			]
		);

		// Form Border Radius.
		$this->add_control(
			'formBorderRadius',
			[
				'label'      => __( 'Form Border Radius', 'sureforms' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'default'    => [
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '0',
					'left'     => '0',
					'unit'     => 'px',
					'isLinked' => true,
				],
			]
		);

		/**
		 * Hook for Pro to add additional layout controls.
		 *
		 * @param \Elementor\Widget_Base $widget The widget instance.
		 * @since x.x.x
		 */
		do_action( 'srfm_elementor_layout_controls', $this );

		$this->end_controls_section();

		// Style Tab - Button Section.
		$this->start_controls_section(
			'srfm_button_section',
			[
				'label'     => __( 'Button', 'sureforms' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'srfm_form_block!' => '',
					'inheritStyling!'  => 'yes',
				],
			]
		);

		// Button Alignment.
		$this->add_control(
			'buttonAlignment',
			[
				'label'   => __( 'Alignment', 'sureforms' ),
				'type'    => \Elementor\Controls_Manager::CHOOSE,
				'options' => [
					'left'    => [
						'title' => __( 'Left', 'sureforms' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'  => [
						'title' => __( 'Center', 'sureforms' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'   => [
						'title' => __( 'Right', 'sureforms' ),
						'icon'  => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => __( 'Full Width', 'sureforms' ),
						'icon'  => 'eicon-text-align-justify',
					],
				],
				'default' => '',
				'toggle'  => true,
			]
		);

		/**
		 * Hook for Pro to add additional button controls.
		 *
		 * @param \Elementor\Widget_Base $widget The widget instance.
		 * @since x.x.x
		 */
		do_action( 'srfm_elementor_button_controls', $this );

		$this->end_controls_section();

		// Style Tab - Fields Section.
		$this->start_controls_section(
			'srfm_field_section',
			[
				'label'     => __( 'Fields', 'sureforms' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'srfm_form_block!' => '',
					'inheritStyling!'  => 'yes',
				],
			]
		);

		// Field Spacing.
		// Hidden when custom theme is selected (Pro provides Row Gap/Column Gap controls).
		$this->add_control(
			'fieldSpacing',
			[
				'label'     => __( 'Field Spacing', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'medium',
				'options'   => [
					'small'  => __( 'Small', 'sureforms' ),
					'medium' => __( 'Medium', 'sureforms' ),
					'large'  => __( 'Large', 'sureforms' ),
				],
				'condition' => [
					'formTheme!' => 'custom',
				],
			]
		);

		/**
		 * Hook for Pro to add additional field controls.
		 *
		 * @param \Elementor\Widget_Base $widget The widget instance.
		 * @since x.x.x
		 */
		do_action( 'srfm_elementor_field_controls', $this );

		$this->end_controls_section();

		/**
		 * Hook for Pro to add additional style sections.
		 *
		 * @param \Elementor\Widget_Base $widget The widget instance.
		 * @since x.x.x
		 */
		do_action( 'srfm_elementor_after_styling_section', $this );
	}

	/**
	 * Render form widget output on the frontend.
	 *
	 * @since 0.0.5
	 * @return void|string
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( ! is_array( $settings ) ) {
			return;
		}

		$is_editor = Plugin::instance()->editor->is_edit_mode();
		$form_id   = intval( $settings['srfm_form_block'] ?? 0 );

		// Show placeholder in editor when no form selected.
		if ( $is_editor && empty( $form_id ) ) {
			?>
			<div style="background: #D9DEE1; color: #9DA5AE; padding: 10px; font-family: Roboto, sans-serif">
				<?php echo esc_html__( 'Select the form that you wish to add here.', 'sureforms' ); ?>
			</div>
			<?php
			return;
		}

		// Validation: Same as shortcode for backward compatibility.
		$form = get_post( $form_id );
		if ( empty( $form_id ) || ! $form || ! in_array( $form->post_status, [ 'publish', 'protected' ], true ) ) {
			echo esc_html__( 'This form has been deleted or is unavailable.', 'sureforms' );
			return;
		}

		$show_title = 'true' === ( $settings['srfm_show_form_title'] ?? '' );

		// Build block_attrs from widget settings.
		$block_attrs = $this->get_block_attrs( $settings );

		// Bypass shortcode - call get_form_markup() directly.
		// $do_blocks = true to match current shortcode behavior.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Generate_Form_Markup.
		echo Generate_Form_Markup::get_form_markup(
			$form_id,
			! $show_title,
			'',
			'post',
			true,
			$block_attrs
		);

		// Get spectra blocks and add css and js.
		$blocks = parse_blocks( get_post_field( 'post_content', $form_id ) );
		$styles = Spec_Gb_Helper::get_instance()->get_assets( $blocks );
		?>
		<style><?php echo $styles['css']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
		<script><?php echo $styles['js']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
		<?php
	}

	/**
	 * Convert widget settings to block_attrs array.
	 * Uses same camelCase keys as Gutenberg for code reuse.
	 *
	 * @param array<string, mixed> $settings Widget settings.
	 * @return array<string, mixed> Block attributes.
	 * @since x.x.x
	 */
	protected function get_block_attrs( $settings ) {
		$block_attrs = [
			'blockId' => 'elementor-' . $this->get_id(),
		];

		// Check if inheriting styling from Instant Form.
		$inherit_styling               = 'yes' === ( $settings['inheritStyling'] ?? 'yes' );
		$block_attrs['inheritStyling'] = $inherit_styling;

		// If inheriting styling, don't pass any custom styling attributes.
		if ( $inherit_styling ) {
			return $block_attrs;
		}

		// Color controls that support Elementor Global Colors.
		$color_keys = [
			'primaryColor',
			'textColor',
			'textOnPrimaryColor',
			'bgColor',
		];

		foreach ( $color_keys as $key ) {
			$color_value = $this->get_color_value( $settings, $key );
			if ( $color_value ) {
				$block_attrs[ $key ] = $color_value;
			}
		}

		// Non-color styling keys to pass through (camelCase).
		$styling_keys = [
			'fieldSpacing',
			'buttonAlignment',
			// Background (non-color).
			'bgType',
			'bgGradient',
			'bgImageSize',
			'bgImagePosition',
			'bgImageRepeat',
			'bgImageAttachment',
		];

		foreach ( $styling_keys as $key ) {
			if ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] && 'default' !== $settings[ $key ] ) {
				$block_attrs[ $key ] = $settings[ $key ];
			}
		}

		// Handle DIMENSIONS controls - map to individual camelCase keys for Gutenberg compatibility.
		// Form Padding.
		if ( ! empty( $settings['formPadding'] ) && is_array( $settings['formPadding'] ) ) {
			$padding = $settings['formPadding'];
			$unit    = $padding['unit'] ?? 'px';
			if ( ! empty( $padding['top'] ) || '0' === $padding['top'] ) {
				$block_attrs['formPaddingTop'] = $padding['top'] . $unit;
			}
			if ( ! empty( $padding['right'] ) || '0' === $padding['right'] ) {
				$block_attrs['formPaddingRight'] = $padding['right'] . $unit;
			}
			if ( ! empty( $padding['bottom'] ) || '0' === $padding['bottom'] ) {
				$block_attrs['formPaddingBottom'] = $padding['bottom'] . $unit;
			}
			if ( ! empty( $padding['left'] ) || '0' === $padding['left'] ) {
				$block_attrs['formPaddingLeft'] = $padding['left'] . $unit;
			}
		}

		// Form Border Radius.
		if ( ! empty( $settings['formBorderRadius'] ) && is_array( $settings['formBorderRadius'] ) ) {
			$radius = $settings['formBorderRadius'];
			$unit   = $radius['unit'] ?? 'px';
			if ( ! empty( $radius['top'] ) || '0' === $radius['top'] ) {
				$block_attrs['formBorderRadiusTop'] = $radius['top'] . $unit;
			}
			if ( ! empty( $radius['right'] ) || '0' === $radius['right'] ) {
				$block_attrs['formBorderRadiusRight'] = $radius['right'] . $unit;
			}
			if ( ! empty( $radius['bottom'] ) || '0' === $radius['bottom'] ) {
				$block_attrs['formBorderRadiusBottom'] = $radius['bottom'] . $unit;
			}
			if ( ! empty( $radius['left'] ) || '0' === $radius['left'] ) {
				$block_attrs['formBorderRadiusLeft'] = $radius['left'] . $unit;
			}
		}

		// Handle bgImage separately as it returns an object from Elementor.
		$bg_image = isset( $settings['bgImage'] ) && is_array( $settings['bgImage'] ) ? $settings['bgImage'] : [];
		if ( ! empty( $bg_image['url'] ) ) {
			$block_attrs['bgImage'] = $bg_image['url'];
		}
		if ( ! empty( $bg_image['id'] ) ) {
			$block_attrs['bgImageId'] = $bg_image['id'];
		}

		/**
		 * Filter the block attributes for Elementor widget.
		 * Pro uses this to add additional styling attributes.
		 *
		 * @param array<string, mixed> $block_attrs Block attributes.
		 * @param array<string, mixed> $settings    Widget settings.
		 * @since x.x.x
		 */
		return apply_filters( 'srfm_elementor_block_attrs', $block_attrs, $settings );
	}

	/**
	 * Resolve a global color to its actual hex value.
	 *
	 * When a user selects a global color in Elementor, the value is stored in
	 * __globals__ array as 'globals/colors?id=primary'. This method resolves
	 * that reference to the actual color value.
	 *
	 * @param array<string, mixed> $settings    Widget settings.
	 * @param string               $control_key The control key to resolve.
	 * @return string|null The resolved color value or null if not found.
	 * @since x.x.x
	 */
	protected function resolve_global_color( $settings, $control_key ) {
		// Check if there's a global color reference.
		$globals = isset( $settings['__globals__'] ) && is_array( $settings['__globals__'] ) ? $settings['__globals__'] : [];
		if ( empty( $globals[ $control_key ] ) ) {
			return null;
		}

		$global_key = is_string( $globals[ $control_key ] ) ? $globals[ $control_key ] : '';
		if ( empty( $global_key ) ) {
			return null;
		}

		// Use Elementor's data manager to resolve the global value.
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( Plugin::$instance->data_manager_v2 ) ) {
			return null;
		}

		$data = Plugin::$instance->data_manager_v2->run( $global_key );

		if ( ! is_array( $data ) || empty( $data['value'] ) ) {
			return null;
		}

		return is_string( $data['value'] ) ? $data['value'] : null;
	}

	/**
	 * Get color value from settings, checking both direct value and global color.
	 *
	 * @param array<string, mixed> $settings    Widget settings.
	 * @param string               $control_key The control key.
	 * @return string|null The color value or null if not set.
	 * @since x.x.x
	 */
	protected function get_color_value( $settings, $control_key ) {
		// First check for a global color reference.
		$global_color = $this->resolve_global_color( $settings, $control_key );
		if ( $global_color ) {
			return $global_color;
		}

		// Fall back to direct value.
		if ( isset( $settings[ $control_key ] ) && '' !== $settings[ $control_key ] && 'default' !== $settings[ $control_key ] ) {
			$value = $settings[ $control_key ];
			return is_string( $value ) ? $value : null;
		}

		return null;
	}

	/**
	 * Static method to get color value from settings.
	 * Can be used by Pro plugin to resolve global colors.
	 *
	 * @param array<string, mixed> $settings    Widget settings.
	 * @param string               $control_key The control key.
	 * @return string|null The color value or null if not set.
	 * @since x.x.x
	 */
	public static function get_resolved_color( $settings, $control_key ) {
		// First check for a global color reference.
		$globals = isset( $settings['__globals__'] ) && is_array( $settings['__globals__'] ) ? $settings['__globals__'] : [];
		if ( ! empty( $globals[ $control_key ] ) ) {
			$global_key = is_string( $globals[ $control_key ] ) ? $globals[ $control_key ] : '';

			// Use Elementor's data manager to resolve the global value.
			if ( $global_key && class_exists( '\Elementor\Plugin' ) && isset( Plugin::$instance->data_manager_v2 ) ) {
				$data = Plugin::$instance->data_manager_v2->run( $global_key );

				if ( is_array( $data ) && ! empty( $data['value'] ) && is_string( $data['value'] ) ) {
					return $data['value'];
				}
			}
		}

		// Fall back to direct value.
		if ( isset( $settings[ $control_key ] ) && '' !== $settings[ $control_key ] && 'default' !== $settings[ $control_key ] ) {
			$value = $settings[ $control_key ];
			return is_string( $value ) ? $value : null;
		}

		return null;
	}

}
