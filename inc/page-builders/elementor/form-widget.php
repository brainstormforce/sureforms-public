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
use SRFM\Inc\Generate_Form_Markup;
use SRFM\Inc\Helper;
use SRFM\Inc\Page_Builders\Page_Builders;

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
				'srfm-elementor-preview-styling',
				SRFM_URL . 'assets/build/elementorPreviewStyling.js',
				[ 'elementor-frontend' ],
				SRFM_VER,
				true
			);
			wp_localize_script(
				'srfm-elementor-preview-styling',
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
			$scripts = [ 'srfm-elementor-preview', 'srfm-elementor-preview-styling' ];

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

	/**
	 * Build CSS gradient string from Group_Control_Background settings.
	 *
	 * Constructs a CSS gradient value from Elementor's gradient control settings.
	 * Supports both linear and radial gradients. Radial gradients use 'center center' position.
	 *
	 * @param array<string, mixed> $settings Widget settings containing gradient values.
	 * @return string|null CSS gradient string or null if colors not set.
	 * @since x.x.x
	 */
	public static function build_gradient_css( $settings ) {
		// Get gradient colors - resolve global colors if used.
		$color_1 = self::get_resolved_color( $settings, 'bgGradient_color' );
		$color_2 = self::get_resolved_color( $settings, 'bgGradient_color_b' );

		// Both colors are required.
		if ( ! $color_1 || ! $color_2 ) {
			return null;
		}

		// Get gradient settings with defaults - use Helper::get_string_value for type safety.
		$type = isset( $settings['bgGradient_gradient_type'] ) && '' !== $settings['bgGradient_gradient_type']
			? Helper::get_string_value( $settings['bgGradient_gradient_type'] )
			: 'linear';

		// Get angle settings.
		$angle_settings = isset( $settings['bgGradient_gradient_angle'] ) && is_array( $settings['bgGradient_gradient_angle'] )
			? $settings['bgGradient_gradient_angle']
			: [];
		$angle          = isset( $angle_settings['size'] ) ? Helper::get_string_value( $angle_settings['size'] ) : '180';
		$angle_unit     = isset( $angle_settings['unit'] ) ? Helper::get_string_value( $angle_settings['unit'] ) : 'deg';

		// Get color 1 stop settings.
		$color_1_settings = isset( $settings['bgGradient_color_stop'] ) && is_array( $settings['bgGradient_color_stop'] )
			? $settings['bgGradient_color_stop']
			: [];
		$color_1_stop     = isset( $color_1_settings['size'] ) ? Helper::get_string_value( $color_1_settings['size'] ) : '0';
		$color_1_unit     = isset( $color_1_settings['unit'] ) ? Helper::get_string_value( $color_1_settings['unit'] ) : '%';

		// Get color 2 stop settings.
		$color_2_settings = isset( $settings['bgGradient_color_b_stop'] ) && is_array( $settings['bgGradient_color_b_stop'] )
			? $settings['bgGradient_color_b_stop']
			: [];
		$color_2_stop     = isset( $color_2_settings['size'] ) ? Helper::get_string_value( $color_2_settings['size'] ) : '100';
		$color_2_unit     = isset( $color_2_settings['unit'] ) ? Helper::get_string_value( $color_2_settings['unit'] ) : '%';

		// Build radial gradient: radial-gradient(at center center, color1 stop1, color2 stop2).
		if ( 'radial' === $type ) {
			return sprintf(
				'radial-gradient(at center center, %s %s%s, %s %s%s)',
				esc_attr( $color_1 ),
				esc_attr( $color_1_stop ),
				esc_attr( $color_1_unit ),
				esc_attr( $color_2 ),
				esc_attr( $color_2_stop ),
				esc_attr( $color_2_unit )
			);
		}

		// Build linear gradient: linear-gradient(angle, color1 stop1, color2 stop2).
		return sprintf(
			'linear-gradient(%s%s, %s %s%s, %s %s%s)',
			esc_attr( $angle ),
			esc_attr( $angle_unit ),
			esc_attr( $color_1 ),
			esc_attr( $color_1_stop ),
			esc_attr( $color_1_unit ),
			esc_attr( $color_2 ),
			esc_attr( $color_2_stop ),
			esc_attr( $color_2_unit )
		);
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

		// Form Theme Select.
		$this->add_control(
			'formTheme',
			[
				'label'     => __( 'Form Theme', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'inherit',
				'options'   => [
					'inherit' => __( "Inherit Form's Original Style", 'sureforms' ),
					'default' => __( 'Default', 'sureforms' ),
				],
				'separator' => 'after',
			]
		);

		// Primary Color.
		$this->add_control(
			'primaryColor',
			[
				'label'     => __( 'Primary Color', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => [
					'formTheme!' => 'inherit',
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
					'formTheme!' => 'inherit',
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
					'formTheme!' => 'inherit',
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
					'formTheme!' => 'inherit',
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
					'formTheme!' => 'inherit',
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
					'formTheme!' => 'inherit',
					'bgType'     => 'color',
				],
			]
		);

		// Background Gradient - using Group_Control_Background for visual gradient picker.
		// Position control is hidden - radial gradients use 'center center' position by default.
		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name'           => 'bgGradient',
				'types'          => [ 'gradient' ],
				'exclude'        => [ 'image' ],
				'fields_options' => [
					'background'        => [
						'label'   => __( 'Gradient', 'sureforms' ),
						'default' => 'gradient',
					],
					'color'             => [
						'label'   => __( 'Color 1', 'sureforms' ),
						'default' => '#FFC9B2',
					],
					'color_stop'        => [
						'label'       => __( 'Location 1', 'sureforms' ),
						'size_units'  => [ '%' ],
						'responsive'  => false,
						'description' => '',
					],
					'color_b'           => [
						'label'   => __( 'Color 2', 'sureforms' ),
						'default' => '#C7CBFF',
					],
					'color_b_stop'      => [
						'label'       => __( 'Location 2', 'sureforms' ),
						'size_units'  => [ '%' ],
						'responsive'  => false,
						'description' => '',
					],
					'gradient_type'     => [],
					'gradient_angle'    => [
						'label'       => __( 'Angle', 'sureforms' ),
						'size_units'  => [ 'deg' ],
						'responsive'  => false,
						'description' => '',
					],
					// Hide position control - use 'center center' as default for radial gradients.
					'gradient_position' => [
						'default' => 'center center',
						'type'    => \Elementor\Controls_Manager::HIDDEN,
					],
				],
				'selector'       => '{{WRAPPER}} .srfm-gradient-dummy-selector', // Dummy selector - we handle styling via CSS variables.
				'condition'      => [
					'formTheme!' => 'inherit',
					'bgType'     => 'gradient',
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
					'formTheme!' => 'inherit',
					'bgType'     => 'image',
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
					'formTheme!' => 'inherit',
					'bgType'     => 'image',
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
					'formTheme!' => 'inherit',
					'bgType'     => 'image',
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
					'formTheme!' => 'inherit',
					'bgType'     => 'image',
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
					'formTheme!' => 'inherit',
					'bgType'     => 'image',
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
					'formTheme!'       => 'inherit',
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
					'formTheme!'       => 'inherit',
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
					'formTheme!'       => 'inherit',
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

		// Check form theme — if inheriting, don't pass any custom styling attributes.
		$form_theme               = $settings['formTheme'] ?? 'inherit';
		$block_attrs['formTheme'] = $form_theme;

		if ( 'inherit' === $form_theme ) {
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
			$color_value = self::get_resolved_color( $settings, $key );
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

		// Build gradient CSS string from Group_Control_Background settings.
		if ( 'gradient' === ( $settings['bgType'] ?? '' ) ) {
			$gradient_css = self::build_gradient_css( $settings );
			if ( $gradient_css ) {
				$block_attrs['bgGradient'] = $gradient_css;
			}
		}

		// Handle DIMENSIONS controls - map to individual camelCase keys for Gutenberg compatibility.
		$block_attrs = self::map_elementor_dimensions( $block_attrs, $settings, 'formPadding' );
		$block_attrs = self::map_elementor_dimensions( $block_attrs, $settings, 'formBorderRadius' );

		// Handle bgImage separately as it returns an object from Elementor.
		$bg_image = isset( $settings['bgImage'] ) && is_array( $settings['bgImage'] ) ? $settings['bgImage'] : [];
		if ( ! empty( $bg_image['url'] ) ) {
			$block_attrs['bgImage'] = esc_url_raw( $bg_image['url'] );
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
	 * Map Elementor dimensions control to individual camelCase block attributes.
	 *
	 * Elementor returns dimensions as an array with top/right/bottom/left/unit keys.
	 * This maps them to individual keys like formPaddingTop, formPaddingRight, etc.
	 *
	 * @param array<string, mixed> $block_attrs Block attributes.
	 * @param array<string, mixed> $settings    Widget settings.
	 * @param string               $control_key The Elementor dimensions control key (e.g., 'formPadding').
	 * @return array<string, mixed> Updated block attributes.
	 * @since x.x.x
	 */
	private static function map_elementor_dimensions( $block_attrs, $settings, $control_key ) {
		if ( empty( $settings[ $control_key ] ) || ! is_array( $settings[ $control_key ] ) ) {
			return $block_attrs;
		}

		$dimensions    = $settings[ $control_key ];
		$allowed_units = [ 'px', '%', 'em' ];
		$unit          = in_array( $dimensions['unit'] ?? 'px', $allowed_units, true ) ? ( $dimensions['unit'] ?? 'px' ) : 'px';
		$sides         = [ 'top', 'right', 'bottom', 'left' ];

		foreach ( $sides as $side ) {
			if ( ! empty( $dimensions[ $side ] ) || '0' === $dimensions[ $side ] ) {
				$block_attrs[ $control_key . ucfirst( $side ) ] = $dimensions[ $side ] . $unit;
			}
		}

		return $block_attrs;
	}

}
