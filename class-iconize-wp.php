<?php
/**
 * Iconize WordPress Plugin.
 *
 * @package   Iconize_WP
 * @author    THATplugin <admin@thatplugin.com>
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://thatplugin.com/
 * @copyright 2021 THATplugin
 */

/**
 * Iconize_WP class.
 *
 * @package Iconize_WP
 * @author  THATplugin <admin@thatplugin.com>
 */
class Iconize_WP {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $version = ICONIZE_PLUGIN_VERSION;

	/**
	 * Unique identifier for this plugin.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'iconize';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;


	/**
	 * Check if TinyMCE Bootstrap Modal plugin is enabled.
	 *
	 * @since    1.0.0
	 *
	 * @var      boolean
	 */
	private $enqueue_wpbs = false;

	/**
	 * Check if Iconize plugin is enabled on visual editor mode.
	 *
	 * @since    1.0.0
	 *
	 * @var      boolean
	 */
	private $tinymce_iconize_plugin_enabled = false;

	/**
	 * Check if Iconize plugin is enabled on HTML editor mode.
	 *
	 * @since    1.0.0
	 *
	 * @var      boolean
	 */
	private $quicktags_iconize_plugin_enabled = false;

	/**
	 * Check if there is CMB2 field on page.
	 *
	 * @since    1.0.0
	 *
	 * @var      boolean
	 */
	private $cmb_field_available = false;

	/**
	 * Visible default options.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	private $shown_options = array();

	/**
	 * Siteorigin builder widget settings for iconize.
	 *
	 * @since    1.2.0
	 *
	 * @var      array
	 */
	private $siteorigin_widget_title_settings = array();

	/**
	 * Initialize the plugin.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_iconize_plugin_textdomain' ) );

		// Load the main functionality on 'init' action so that the plugin can be easily customized via custom filters from themes "functions.php" file.
		add_action( 'init', array( $this, 'iconize_wp_systems' ) );

		// Add taxonomy systems support if nedded ( priority 99, must be called after registration of custom taxonomies ).
		add_action( 'init', array( $this, 'iconize_taxonomies' ), 99 );
		add_action( 'widgets_init', array( $this, 'iconize_widgets' ) );

		// Plugin options.
		add_action( 'admin_init', array( $this, 'init_iconize_plugin_options' ) );

		// Load stylesheets with fonts and icons defined on admin screens and front end.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_iconize_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_iconize_styles' ) );

		// Load styles to TinyMCE editor content ( priority 11 to prevent overrides ).
		add_filter( 'mce_css', array( $this, 'iconize_mce_css' ), 11 );

		// Load needed styles & scripts and add iconize dialog to footer on admin screens.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_iconize_admin_scripts_and_styles' ) );
		add_action( 'admin_footer', array( $this, 'iconize_admin_dialog' ) );

		// Settings update.
		if ( version_compare( get_option( 'iconize_plugin_version', '1.0.1' ), '1.2.0', '<' ) ) {
			add_action( 'init', array( $this, 'upgrade_to_1_2_0' ), 100 );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( ! self::$instance ) {

			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0.0
	 */
	public function load_iconize_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * Add iconize plugin components to WordPress systems.
	 *
	 * @since 1.0.0
	 *
	 * @uses Iconize_WP::show_iconize_options()
	 * @uses Iconize_WP::get_iconize_support_for()
	 */
	public function iconize_wp_systems() {

		// Check if plugin is enabled on editor/widgets/nav menus.
		$mce_plugin_support   = $this->get_iconize_support_for( 'editor' );
		$widget_icons_support = $this->get_iconize_support_for( 'widgets' );
		$menu_icons_support   = $this->get_iconize_support_for( 'nav_menus' );
		$elementor_support    = $this->get_iconize_support_for( 'elementor' );
		$beaver_support       = $this->get_iconize_support_for( 'beaver_builder' );
		$siteorigin_support   = $this->get_iconize_support_for( 'siteorigin' );

		// Options page.
		if ( $this->show_iconize_options() ) { // Check if options page is enabled.

			// Add the options page and dashboard menu item.
			add_action( 'admin_menu', array( $this, 'iconize_plugin_admin_menu' ) );
			// Add an action link pointing to the options page.
			$plugin_basename = plugin_basename( plugin_dir_path( __FILE__ ) . 'iconize.php' );
			add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_iconize_plugin_action_links' ) );
		}

		// TinyMCE editor integration if enabled.
		if ( $mce_plugin_support['enabled'] ) {

			// Enable contenteditable attr in visual editor.
			add_filter( 'tiny_mce_before_init', array( $this, 'iconize_mce_allow_contenteditable_attr' ) );
			// Add editor plugins.
			add_filter( 'mce_external_plugins', array( $this, 'iconize_mce_plugins' ) );
			// TinyMCE plugin localization.
			add_filter( 'mce_external_languages', ( array( $this, 'iconize_mce_lang' ) ), 10, 1 );
			add_filter( 'wp_mce_translation', ( array( $this, 'iconize_mce_translation' ) ) );
			// Add four buttons to TinyMCE editor.
			add_filter( 'mce_buttons_2', array( $this, 'iconize_mce_buttons' ) );

			// Check if our TinyMCE plugins are included on wp_editor instance.
			add_action( 'wp_tiny_mce_init', array( $this, 'tinymce_iconize_plugin_check' ) );

			// Check if our quicktag plugin is included on editor instance.
			add_filter( 'quicktags_settings', ( array( $this, 'quicktags_iconize_plugin_check' ) ), 10, 2 );

			// Add dialog to footer and enqueue scripts and styles for it if needed.
			add_action( 'admin_footer', ( array( $this, 'iconize_editor_dialog_scripts' ) ), 999999999 );
			add_action( 'wp_footer', ( array( $this, 'iconize_editor_dialog_scripts' ) ), 999999999 );
		}

		// Customizer support.
		if ( $widget_icons_support['enabled'] || $menu_icons_support['enabled'] ) {
			add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_iconize_styles' ) );
			add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_iconize_admin_scripts_and_styles' ) );
			add_action( 'customize_controls_print_footer_scripts', array( $this, 'iconize_admin_dialog' ) );
		}

		// Widget system integration if enabled.
		if ( $widget_icons_support['enabled'] ) {

			// Add icon button and input fields to widget form.
			add_action( 'in_widget_form', array( $this, 'iconize_in_widget_form' ), 5, 3 );
			// Handle widget form update.
			add_filter( 'widget_update_callback', ( array( $this, 'iconize_in_widget_form_update' ) ), 5, 3 );
			// Add icon to output, before widget title.
			add_filter( 'dynamic_sidebar_params', array( $this, 'iconize_dynamic_sidebar_params' ) );

			// Page Builder by SiteOrigin Support.
			add_action( 'siteorigin_panel_enqueue_admin_scripts', array( $this, 'enqueue_iconize_admin_scripts_and_styles' ) );
			add_filter( 'siteorigin_panels_widget_instance', array( $this, 'siteorigin_panels_widget_instance' ) );
			add_filter( 'siteorigin_panels_widget_args', array( $this, 'siteorigin_panels_widget_args' ) );
		}

		// Custom Menus system integration if enabled.
		if ( $menu_icons_support['enabled'] ) {

			// Add icon button and input fields to nav menu item form.
			add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'iconize_nav_menu_item_custom_fields' ), 10, 4 );
			// Handle nav menu item form form update.
			add_action( 'wp_update_nav_menu_item', array( $this, 'iconize_update_nav_menu_item' ), 10, 3 );
			// Add icon properties to nav menu item object.
			add_filter( 'wp_setup_nav_menu_item', array( $this, 'iconize_setup_nav_menu_item' ) );
			// Call our custom edit walker on edit nav menu screen.
			add_filter( 'wp_edit_nav_menu_walker', array( $this, 'iconize_edit_nav_menu_walker' ) );

			// Add menu icon selector to customizer.
			add_action( 'wp_nav_menu_item_custom_fields_customize_template', array( $this, 'customizer_nav_menu_item_custom_fields' ) );

			/**
			 * Allow users to choose how the plugin will attach itself to menus on front end and easaly fix conflicts with other menu walkers.
			 *
			 * Possible returned strings - title_link, title, walker
			 */
			$menu_hook = (string) apply_filters( 'iconize_menu_items_with', 'title' );

			if ( 'title_link' === $menu_hook ) {

				// The best one, but most of custom walkers out there were omitting "nav_menu_link_attributes" filter in past...
				add_filter( 'nav_menu_link_attributes', ( array( $this, 'iconize_nav_menu_link_attributes' ) ), 10, 3 );
				add_filter( 'the_title', ( array( $this, 'iconize_menu_item_title' ) ), 10, 2 );

			} elseif ( 'title' === $menu_hook ) {

				// The best chance to work - most of custom walkers out there have "the_title" filter.
				add_filter( 'the_title', ( array( $this, 'iconize_menu_item_title_all' ) ), 10, 2 );

			} elseif ( 'walker' === $menu_hook ) {

				// Call our custom output walker on all nav menus.
				add_filter( 'wp_nav_menu_args', array( $this, 'iconize_nav_menu_args' ) );
			}
			// else - find another way :) .

			// Customizer.
			// Set up previewing.
			add_action( 'customize_register', array( $this, 'customize_register' ), 1000 );
			// Set up saving.
			add_action( 'customize_save_after', array( $this, 'customize_save_after' ) );
		}

		if ( $elementor_support['enabled'] ) {
			add_filter( 'elementor/icons_manager/additional_tabs', array( $this, 'elementor_icon_tabs' ), 9999999, 1 );
			add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_iconize_styles' ) );
		}

		if ( $beaver_support['enabled'] ) {
			add_filter( 'fl_builder_icon_sets', array( $this, 'beaver_builder_fonts' ) );
		}

		if ( $siteorigin_support['enabled'] ) {
			add_filter( 'siteorigin_widgets_icon_families', array( $this, 'so_builder_fonts' ) );
		}

		add_filter( 'cmb2_render_iconize', array( $this, 'render_iconize_field' ), 10, 5 );

		require_once ICONIZE_PLUGIN_PATH . 'includes/uploader/class-iconize-font-uploader.php';
		new Iconize_Font_Uploader();
	}

	/**
	 * Function to apply Iconize plugin support to taxonomies
	 *
	 * @since    1.0.0
	 */
	public function iconize_taxonomies() {

		// Taxonomies.
		$supported_taxonomies = array();

		$taxonomies = get_taxonomies(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'names',
			'and'
		);

		if ( $taxonomies ) {

			foreach ( $taxonomies as $taxonomy ) {

				$tax_support       = $this->get_iconize_support_for( 'taxonomy_' . $taxonomy );
				$tax_icons_enabled = $tax_support['enabled'];

				if ( taxonomy_exists( $taxonomy ) && $tax_icons_enabled ) {
					$supported_taxonomies[] = $taxonomy;
				}
			}
		}

		if ( ! empty( $supported_taxonomies ) ) {

			foreach ( $supported_taxonomies as $taxonomy ) {

				// Add icon option to taxonomy terms settings.
				add_action( $taxonomy . '_add_form_fields', array( $this, 'iconize_taxonomy_add_form_fields' ) );
				add_action( $taxonomy . '_edit_form_fields', ( array( $this, 'iconize_taxonomy_edit_form_fields' ) ), 10, 2 );
				// Add icon column to taxonomy terms tables.
				add_filter( 'manage_edit-' . $taxonomy . '_columns', array( $this, 'iconize_term_columns_head' ) );
				add_filter( 'manage_' . $taxonomy . '_custom_column', array( $this, 'iconize_term_column_content' ), 10, 3 );
			}

			// Handle creating/editing/deleting of term icons.
			add_action( 'created_term', ( array( $this, 'iconize_create_update_taxonomy_icon' ) ), 10, 3 );
			add_action( 'edited_term', ( array( $this, 'iconize_create_update_taxonomy_icon' ) ), 10, 3 );
			add_action( 'delete_term', ( array( $this, 'iconize_delete_taxonomy_icon' ) ), 10, 3 );
			// Iconize wp_list_categories and wp_generate_tag_cloud.
			add_filter( 'wp_list_categories', ( array( $this, 'iconize_list_taxonomies' ) ), 99, 2 );
			add_filter( 'wp_generate_tag_cloud', ( array( $this, 'iconize_wp_generate_tag_cloud' ) ), 99, 3 );

			// Check if there is dialog on widgets screen, because we need it there.
			$wid_support = $this->get_iconize_support_for( 'widgets' );
			if ( ! $wid_support['enabled'] ) {
				// Add dialog with scripts and styles for it.
				add_filter(
					'add_iconize_dialog_to_screens',
					function( $array ) {
						$array[] = 'widgets';
						return $array;
					}
				);
			}
		}
	}

	/**
	 * Function to register our widgets ( only one widget for now ).
	 *
	 * @since    1.0.0
	 */
	public function iconize_widgets() {

		// Taxonomies.
		$supported_taxonomies = array();

		$taxonomies = get_taxonomies(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'names',
			'and'
		);

		if ( $taxonomies ) {

			foreach ( $taxonomies as $taxonomy ) {

				$tax_support       = $this->get_iconize_support_for( 'taxonomy_' . $taxonomy );
				$tax_icons_enabled = $tax_support['enabled'];

				if ( taxonomy_exists( $taxonomy ) && $tax_icons_enabled ) {

					$supported_taxonomies[] = $taxonomy;
				}
			}
		}

		if ( ! empty( $supported_taxonomies ) ) {
			register_widget( 'Iconize_Widget_Taxonomies' );
		}
	}


	/**
	 * Settings
	 */

	/**
	 * Register options.
	 *
	 * @since 1.0.0
	 *
	 * @uses Iconize_WP::get_iconize_support_for()
	 * @uses Iconize_WP::iconize_options_checkbox_callback()
	 */
	public function init_iconize_plugin_options() {

		$settings = array(
			'iconize_fonts'        => array(
				'name'    => __( 'Choose fonts', 'iconize' ),
				'desc'    => __( 'Here you can enable/disable default fonts.', 'iconize' ),
				'options' => array(
					'iconize_font_dashicons'  => array(
						'type' => 'checkbox',
						'name' => __( 'Dashicons', 'iconize' ),
						'args' => array(
							'id'    => 'font_dashicons',
							'label' => __( 'Enable Dashicons?', 'iconize' ),
						),
					),
					'iconize_font_awesome'    => array(
						'type' => 'checkbox',
						'name' => __( 'Font Awesome 4.7', 'iconize' ),
						'args' => array(
							'id'    => 'font_awesome',
							'label' => __( 'Enable Font Awesome 4.7?', 'iconize' ),
						),
					),
					'iconize_fa_solid'        => array(
						'type' => 'checkbox',
						'name' => __( 'Font Awesome 5 Solid', 'iconize' ),
						'args' => array(
							'id'    => 'fa_solid',
							'label' => __( 'Enable Font Awesome 5 Solid?', 'iconize' ),
						),
					),
					'iconize_fa_regular'      => array(
						'type' => 'checkbox',
						'name' => __( 'Font Awesome 5 Regular', 'iconize' ),
						'args' => array(
							'id'    => 'fa_regular',
							'label' => __( 'Enable Font Awesome 5 Regular?', 'iconize' ),
						),
					),
					'iconize_fa_brands'       => array(
						'type' => 'checkbox',
						'name' => __( 'Font Awesome 5 Brands', 'iconize' ),
						'args' => array(
							'id'    => 'fa_brands',
							'label' => __( 'Enable Font Awesome 5 Brands?', 'iconize' ),
						),
					),
					'iconize_font_foundation' => array(
						'type' => 'checkbox',
						'name' => __( 'Foundation Icons', 'iconize' ),
						'args' => array(
							'id'    => 'font_foundation',
							'label' => __( 'Enable Foundation Icons?', 'iconize' ),
						),
					),
					'iconize_font_bootstrap'  => array(
						'type' => 'checkbox',
						'name' => __( 'Bootstrap Icons', 'iconize' ),
						'args' => array(
							'id'    => 'font_bootstrap',
							'label' => __( 'Enable Bootstrap Icons?', 'iconize' ),
						),
					),
					'iconize_font_iconoir'    => array(
						'type' => 'checkbox',
						'name' => __( 'Iconoir', 'iconize' ),
						'args' => array(
							'id'    => 'font_iconoir',
							'label' => __( 'Enable Iconoir?', 'iconize' ),
						),
					),
				),
			),
			'iconize_custom_fonts' => array(
				'name'    => __( 'Manage custom fonts', 'iconize' ),
				'desc'    => __( 'Here you can upload .zip file downloaded from Fontello and enable/disable custom fonts (uploaded or added programmatically).', 'iconize' ),
				'options' => array(),
			),
			'iconize_integrations' => array(
				'name'    => __( 'Choose integrations', 'iconize' ),
				'desc'    => __( 'Here you can enable/disable Iconize plugin integration on specific WordPress system.', 'iconize' ),
				'options' => array(
					'iconize_nav_menus'      => array(
						'type' => 'checkbox',
						'name' => __( 'Menus', 'iconize' ),
						'args' => array(
							'id'    => 'nav_menus',
							'label' => __( 'Enable custom menus system integration?', 'iconize' ),
						),
					),
					'iconize_widgets'        => array(
						'type' => 'checkbox',
						'name' => __( 'Widgets', 'iconize' ),
						'args' => array(
							'id'    => 'widgets',
							'label' => __( 'Enable widget system integration?', 'iconize' ),
						),
					),
					'iconize_editor'         => array(
						'type' => 'checkbox',
						'name' => __( 'Editor', 'iconize' ),
						'args' => array(
							'id'    => 'editor',
							'label' => __( 'Enable plugin integration on editors?', 'iconize' ),
						),
					),
					'iconize_elementor'      => array(
						'type' => 'checkbox',
						'name' => __( 'Elementor', 'iconize' ),
						'args' => array(
							'id'    => 'elementor',
							'label' => __( 'Use Iconize fonts inside Elementor?', 'iconize' ),
						),
					),
					'iconize_beaver_builder' => array(
						'type' => 'checkbox',
						'name' => __( 'Beaver Builder', 'iconize' ),
						'args' => array(
							'id'    => 'beaver_builder',
							'label' => __( 'Use Iconize fonts inside Beaver Builder?', 'iconize' ),
						),
					),
					'iconize_siteorigin'     => array(
						'type' => 'checkbox',
						'name' => __( 'Page Builder by SiteOrigin', 'iconize' ),
						'args' => array(
							'id'    => 'siteorigin',
							'label' => __( 'Use Iconize fonts inside Page Builder by SiteOrigin?', 'iconize' ),
						),
					),
				),
			),
		);

		// Add Custom Font options.
		$uploaded_fonts = get_option( 'iconize_uploaded_fonts_data', array() );
		foreach ( $uploaded_fonts as $key => $data ) {
			$name = ucwords( $key );
			$settings['iconize_custom_fonts']['options'][ 'uploaded_font_' . $key ] = array(
				'type' => 'checkbox',
				'name' => $name,
				'args' => array(
					'id'            => 'uploaded_font_' . $key,
					'label'         => __( 'Enable ', 'iconize' ) . $name,
					'remove_button' => $key,
				),
			);
		}

		$custom_fonts = apply_filters( 'iconize_fonts_styles', array() );
		if ( ! empty( $custom_fonts ) && is_array( $custom_fonts ) ) {
			foreach ( $custom_fonts as $key => $data ) {
				$name = ucwords( $key );
				$settings['iconize_custom_fonts']['options'][ 'custom_font_' . $key ] = array(
					'type' => 'checkbox',
					'name' => $name,
					'args' => array(
						'id'    => 'custom_font_' . $key,
						'label' => __( 'Enable ', 'iconize' ) . $name,
					),
				);
			}
		}

		// Add Taxonomy options.
		$taxonomies = get_taxonomies(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'objects',
			'and'
		);
		if ( $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				$settings['iconize_integrations']['options'][ 'iconize_taxonomy_' . $taxonomy->name ] = array(
					'type' => 'checkbox',
					'name' => $taxonomy->labels->name,
					'args' => array(
						'id'    => 'taxonomy_' . $taxonomy->name,
						/* translators: 1: taxonomy label, 2: post type */
						'label' => sprintf( __( 'Enable plugin integration on %1$s ( post type: %2$s )?', 'iconize' ), $taxonomy->label, $taxonomy->object_type[0] ),
					),
				);
			}
		}

		foreach ( $settings as $tab => $options ) {
			register_setting( $tab, $tab );
			add_settings_section(
				$tab,
				$options['name'],
				function() use ( $options ) {
					echo esc_html( $options['desc'] );
				},
				$tab
			);
			foreach ( $options['options'] as $id => $args ) {
				$support = $this->get_iconize_support_for( $args['args']['id'], $tab );
				if ( $support['show_in_options'] ) {
					add_settings_field(
						$id,
						$args['name'],
						array( $this, 'iconize_options_' . $args['type'] . '_callback' ),
						$tab,
						$tab,
						array_merge(
							array(
								'tab'     => $tab,
								'default' => $support['enabled'],
							),
							$args['args']
						)
					);
					$this->shown_options[ str_replace( 'iconize_', '', $tab ) ][] = $id;
				}
			}
		}
	}

	/**
	 * Checkbox option callback function.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Args.
	 */
	public function iconize_options_checkbox_callback( $args ) {
		$tab_options = get_option( $args['tab'], array() );
		$checked     = ! empty( $tab_options ) ? isset( $tab_options[ $args['id'] ] ) : $args['default'];

		$html  = '<input type="checkbox" id="' . esc_attr( $args['id'] ) . '" name="' . $args['tab'] . '[' . $args['id'] . ']" value="1" ' . checked( 1, $checked, false ) . '/>';
		$html .= '<label for="' . esc_attr( $args['id'] ) . '"> ' . esc_html( $args['label'] ) . '</label>';
		if ( ! empty( $args['remove_button'] ) ) {
			$html .= '<button class="button iconize-option-remove-button" data-remove="' . esc_attr( $args['remove_button'] ) . '">' . esc_html__( 'Delete', 'iconize' ) . '</button>';
		}

		echo $html; // @codingStandardsIgnoreLine.
	}

	/**
	 * Register the administration menu for Iconize plugin.
	 *
	 * @since 1.0.0
	 *
	 * @uses Iconize_WP::display_iconize_plugin_admin_page()
	 */
	public function iconize_plugin_admin_menu() {

		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Iconize Settings', 'iconize' ),
			__( 'Iconize', 'iconize' ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_iconize_plugin_admin_page' )
		);
	}

	/**
	 * Render the settings page for Iconize plugin.
	 *
	 * @since 1.0.0
	 */
	public function display_iconize_plugin_admin_page() {
		$default_tabs = array(
			'fonts'        => __( 'Default Fonts', 'iconize' ),
			'custom_fonts' => __( 'Custom Fonts', 'iconize' ),
			'integrations' => __( 'Integrations', 'iconize' ),
		);

		if ( ! array_key_exists( 'fonts', $this->shown_options ) && ! has_action( 'iconize_after_fonts_options' ) ) {
			unset( $default_tabs['fonts'] );
		}

		if ( ! array_key_exists( 'integrations', $this->shown_options ) && ! has_action( 'iconize_after_integrations_options' ) ) {
			unset( $default_tabs['integrations'] );
		}

		$tabs    = apply_filters( 'iconize_settings_tabs', $default_tabs );
		$current = ! empty( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : false;// phpcs:ignore

		$active_tab = $current && array_key_exists( $current, $tabs ) ? $current : key( $tabs );
		?>
		<div class="wrap">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php
			if ( is_null( $active_tab ) ) {
				?>
				<p><?php esc_html_e( 'Oops! Nothing to show here.', 'iconize' ); ?></p>
				<?php
			} else {
				?>
				<h2 class="nav-tab-wrapper">
					<?php
					foreach ( $tabs as $tab => $label ) {
						?>
						<a href="?page=iconize&tab=<?php echo esc_attr( $tab ); ?>" class="nav-tab <?php echo $tab === $active_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
						<?php
					}
					?>
				</h2>
				<?php
				do_action( 'iconize_before_options_form' );
				do_action( 'iconize_before_' . $active_tab . '_form' );
				?>
				<form method="post" action="options.php">
					<?php
					settings_fields( 'iconize_' . $active_tab );
					do_settings_sections( 'iconize_' . $active_tab );
					do_action( 'iconize_after_' . $active_tab . '_options' );
					if (
						'custom_fonts' !== $active_tab
						|| ( 'custom_fonts' === $active_tab && ( array_key_exists( 'custom_fonts', $this->shown_options ) || has_action( 'iconize_after_custom_fonts_options' ) ) )
					) {
						submit_button();
					}
					?>
				</form>
				<?php
				do_action( 'iconize_after_' . $active_tab . '_form' );
				do_action( 'iconize_after_options_form' );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Add settings action link to plugin on plugins screen.
	 *
	 * @since 1.0.0
	 *
	 * @param array $links Array of links.
	 */
	public function add_iconize_plugin_action_links( $links ) {

		return array_merge(
			array( 'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', 'iconize' ) . '</a>' ),
			$links
		);
	}

	/**
	 * Basic integration
	 */

	/**
	 * Enqueue main iconize plugin styles and styles with fonts and icons defined.
	 *
	 * @since 1.0.0
	 *
	 * @uses Iconize_WP::get_iconize_fonts_styles()
	 */
	public function enqueue_iconize_styles() {

		// Use the .min suffix if SCRIPT_DEBUG is turned off.
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Main styles.
		wp_enqueue_style(
			'iconize-styles',
			plugins_url( "css/iconize$suffix.css", __FILE__ ),
			array(),
			$this->version
		);

		// Styles with fonts and icons defined.
		$iconize_fonts_stylesheets = $this->get_iconize_fonts_styles();

		foreach ( $iconize_fonts_stylesheets as $handle => $array ) {

			$handle = 'iconize-' . $handle . '-font-styles';

			if ( is_array( $array ) && array_key_exists( 'url', $array ) && ! empty( $array['url'] ) ) {

				// if dashicons are enabled set 'dashicons' as dependency for our custom dashicons CSS.
				$deps = ( 'iconize-dashicons-font-styles' === $handle ) ? array( 'dashicons' ) : array();

				wp_enqueue_style( $handle, $array['url'], $deps, $this->version );
			}
		}
	}

	/**
	 * Add styles to visual editor.
	 *
	 * @since 1.0.0
	 *
	 * @uses Iconize_WP::get_iconize_fonts_styles()
	 *
	 * @param string $mce_css Styles.
	 */
	public function iconize_mce_css( $mce_css ) {

		if ( ! empty( $mce_css ) ) {
			$mce_css .= ',';
		}

		// Use the .min suffix if SCRIPT_DEBUG is turned off.
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Main styles.
		$mce_css .= plugins_url( "css/iconize$suffix.css", __FILE__ );

		// Styles with fonts and icons defined.
		$iconize_fonts_stylesheets = $this->get_iconize_fonts_styles();

		foreach ( $iconize_fonts_stylesheets as $handle => $array ) {

			// if Dashicons are enabled we must add default dashicons styles too.
			if ( 'dashicons' === $handle ) {

				$mce_css .= ',';
				$mce_css .= includes_url() . 'css/dashicons.min.css';
			}

			$mce_css .= ',';
			$mce_css .= $array['url'];
		}

		return $mce_css;
	}

	/**
	 * Enqueue css and js for admin dialog.
	 *
	 * @since 1.0.0
	 *
	 * @uses Iconize_WP::get_iconize_support_for()
	 * @uses Iconize_WP::get_extra_iconize_dialog_support()
	 * @uses Iconize_WP::get_iconize_dialog_strings()
	 * @uses Iconize_WP::get_icons_array()
	 * @uses Iconize_WP::get_iconize_dialog_inline_styles()
	 */
	public function enqueue_iconize_admin_scripts_and_styles() {

		// Check if plugin is enabled on widgets/nav menus.
		$widget_icons_support = $this->get_iconize_support_for( 'widgets' );
		$menu_icons_support   = $this->get_iconize_support_for( 'nav_menus' );

		// Get screens ids of supported taxonomies.
		$supported_taxonomy_screens = $this->iconize_get_supported_taxonomy_screens_ids();

		// Check if user enabled dialog on other admin pages.
		$extra_admin_screens_array = $this->get_extra_iconize_dialog_support();

		// Get current screen id.
		$screen    = get_current_screen();
		$screen_id = $screen->id;

		$add_to_nav_menus = ( 'nav-menus' === $screen_id && $menu_icons_support['enabled'] );
		$add_to_widgets   = ( 'widgets' === $screen_id && $widget_icons_support['enabled'] );
		$add_to_tax       = ( in_array( $screen_id, $supported_taxonomy_screens, true ) );
		$add_to_other     = ( in_array( $screen_id, $extra_admin_screens_array, true ) );

		$current_filter = current_filter();

		if ( $add_to_nav_menus || $add_to_widgets || $add_to_tax || $add_to_other || 'customize_controls_enqueue_scripts' === $current_filter || 'siteorigin_panel_enqueue_admin_scripts' === $current_filter ) {

			$this->enqueue_admin_dialog_scripts();
		}
	}

	/**
	 * Enqueue css and js for admin dialog.
	 *
	 * @since 1.0.0
	 *
	 * @uses Iconize_WP::get_iconize_support_for()
	 * @uses Iconize_WP::get_extra_iconize_dialog_support()
	 * @uses Iconize_WP::get_iconize_dialog_strings()
	 * @uses Iconize_WP::get_icons_array()
	 * @uses Iconize_WP::get_iconize_dialog_inline_styles()
	 */
	public function enqueue_admin_dialog_scripts() {
		// Use the .min suffix if SCRIPT_DEBUG is turned off.
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Styles.
		wp_enqueue_style(
			'iconize-bootstrap-modal',
			plugins_url( "css/bootstrap-modal$suffix.css", __FILE__ ),
			array(),
			$this->version
		);

		wp_enqueue_style(
			'iconize-dialog',
			plugins_url( "css/iconize-dialog$suffix.css", __FILE__ ),
			array(),
			$this->version
		);

		$dialog_inline_styles = $this->get_iconize_dialog_inline_styles();
		if ( $dialog_inline_styles ) {

			wp_add_inline_style( 'iconize-dialog', $dialog_inline_styles );
		}

		wp_enqueue_style( 'wp-color-picker' );

		// Scripts.
		wp_enqueue_script(
			'iconize-bootstrap-modal',
			plugins_url( "js/bootstrap-modal$suffix.js", __FILE__ ),
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_enqueue_script(
			'iconize-helpers',
			plugins_url( "js/iconize-helpers$suffix.js", __FILE__ ),
			array(
				'jquery-ui-autocomplete',
				'jquery-effects-blind',
				'jquery-effects-highlight',
			),
			$this->version,
			true
		);

		$iconize_dialog_l10n = $this->get_iconize_dialog_strings();
		$icons_arr           = $this->get_icons_array();

		$iconize_dialog_params          = array( 'l10n' => $iconize_dialog_l10n );
		$iconize_dialog_params['icons'] = empty( $icons_arr ) ? false : $icons_arr;

		wp_localize_script( 'iconize-helpers', 'iconizeDialogParams', $iconize_dialog_params );

		wp_enqueue_script(
			'iconize-admin-dialog',
			plugins_url( "js/iconize-admin-dialog$suffix.js", __FILE__ ),
			array(
				'iconize-bootstrap-modal',
				'iconize-helpers',
				'wp-color-picker',
			),
			$this->version,
			true
		);
	}

	/**
	 * Render the dialog for admin screens.
	 *
	 * @since 1.0.0
	 *
	 * @uses Iconize_WP::get_iconize_support_for()
	 * @uses Iconize_WP::get_extra_iconize_dialog_support()
	 * @uses Iconize_WP::iconize_dialog()
	 */
	public function iconize_admin_dialog() {

		// Check if plugin is enabled on widgets or nav menus.
		$widget_icons_support = $this->get_iconize_support_for( 'widgets' );
		$menu_icons_support   = $this->get_iconize_support_for( 'nav_menus' );

		// Get screens ids of supported taxonomies.
		$supported_taxonomy_screens = $this->iconize_get_supported_taxonomy_screens_ids();

		// Check if dialog is enabled on other admin pages by user.
		$extra_admin_screens_array = $this->get_extra_iconize_dialog_support();

		// Get current screen id.
		$screen    = get_current_screen();
		$screen_id = $screen->id;

		$add_to_nav_menus = ( 'nav-menus' === $screen_id && $menu_icons_support['enabled'] );
		$add_to_widgets   = ( 'widgets' === $screen_id && $widget_icons_support['enabled'] );
		$add_to_tax       = ( in_array( $screen_id, $supported_taxonomy_screens, true ) );
		$add_to_other     = ( in_array( $screen_id, $extra_admin_screens_array, true ) );

		$current_filter = current_filter();
		if ( $add_to_nav_menus || $add_to_widgets || $add_to_tax || $add_to_other || 'customize_controls_print_footer_scripts' === $current_filter || $this->cmb_field_available || did_action( 'siteorigin_panel_enqueue_admin_scripts' ) ) {

			/**
			 * Allow users to customize dialog options for specific admin screen.
			 *
			 * Example case:
			 * array( 'transform', 'color' ) is an array returned by users function attached to "iconize_dialog_options_for_nav-menu" filter,
			 * the plugin will display only icon transform dropdown option and icon color option on nav-menu screen.
			 * To disable all options function must return boolean false or empty array ( empty string wont work, it is reserved for default options ).
			 *
			 * Possible array values - transform, animate, hover, color, size, align, custom_classes
			 */
			$dialog_opts = apply_filters( "iconize_dialog_options_for_{$screen_id}", array( 'transform', 'animate', 'hover', 'color', 'custom_classes' ) );
			$dialog_btns = array( 'remove' );

			$this->iconize_dialog( 'admin', $dialog_opts, $dialog_btns );
		}
	}

	/**
	 * Editors integration
	 */

	/**
	 * Allow contenteditable attribute on <i> and <span> tags.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings_array Array of settings.
	 */
	public function iconize_mce_allow_contenteditable_attr( $settings_array ) {

		$ext = 'i[*|contenteditable],span[*|contenteditable]';

		if ( isset( $settings_array['extended_valid_elements'] ) ) {

			$settings_array['extended_valid_elements'] .= ',' . $ext;

		} else {

			$settings_array['extended_valid_elements'] = $ext;
		}

		return $settings_array;
	}

	/**
	 * Add plugins to TinyMCE editor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $plugins Array of plugins.
	 */
	public function iconize_mce_plugins( $plugins ) {

		// Use the .min suffix if SCRIPT_DEBUG is turned off.
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$plugins['iconize_mce'] = plugins_url( "/tinymce/editor_plugin$suffix.js", __FILE__ );

		return $plugins;
	}

	/**
	 * TinyMCE Plugin Localization file.
	 *
	 * @since 1.0.0
	 *
	 * @param array $array Array of plugins.
	 */
	public function iconize_mce_lang( $array ) {

		$array['iconize_mce'] = plugin_dir_path( __FILE__ ) . '/tinymce/langs/iconize-langs.php';

		return $array;
	}

	/**
	 * TinyMCE Plugin Translation.
	 *
	 * @since 1.0.0
	 *
	 * @param array $array Array of translated strings.
	 */
	public function iconize_mce_translation( $array ) {

		$array['insert_icon_title'] = __( 'Insert/Edit icon', 'iconize' );
		$array['swap_pos_title']    = __( 'Swap positions of stacked icons', 'iconize' );
		$array['swap_size_title']   = __( 'Swap sizes of stacked icons', 'iconize' );
		$array['remove_icon_title'] = __( 'Remove icon', 'iconize' );

		return $array;
	}

	/**
	 * Register TinyMCE editor buttons.
	 *
	 * @since 1.0.0
	 *
	 * @param array $buttons Buttons.
	 */
	public function iconize_mce_buttons( $buttons ) {

		array_push( $buttons, 'insert_icon', 'swap_icon_positions', 'swap_icon_sizes', 'remove_icon' );

		return $buttons;
	}

	/**
	 * Check if our TinyMCE plugin is included on wp_editor instance.
	 *
	 * Function is attached to 'wp_tiny_mce_init' filter.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Array of settings.
	 */
	public function tinymce_iconize_plugin_check( $settings ) {

		$arr = $settings;
		reset( $arr );
		$first_arr        = current( $arr );
		$external_plugins = isset( $first_arr['external_plugins'] ) ? $first_arr['external_plugins'] : '';

		$this->enqueue_wpbs                   = false;
		$this->tinymce_iconize_plugin_enabled = false;
		if ( false !== strpos( $external_plugins, 'iconize_mce' ) ) {

			$this->enqueue_wpbs                   = true;
			$this->tinymce_iconize_plugin_enabled = true;
		}

		return $settings;
	}

	/**
	 * Add "iconize_quicktags" parameter to "quicktags" options in settings of wp_editor(),
	 * and check if our quicktags plugin is enabled on wp_editor instance.
	 *
	 * Function is attached to 'quicktags_settings' filter.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $qt_init Array of settings.
	 * @param string $editor_id Editor ID.
	 */
	public function quicktags_iconize_plugin_check( $qt_init, $editor_id ) {

		if ( ! isset( $qt_init['iconize_quicktags'] ) ) {

			$screen_id = '';

			if ( is_admin() ) {

				// Get current screen id.
				$screen = get_current_screen();
				if ( $screen ) {

					$screen_id = $screen->id;
				}
			}

			/**
			 * Apply "iconize_quicktags" filter to allow users to change default value of "iconize_quicktags" parameter
			 * on specific editor instance ( passed editor id and current admin screen id to target the editor instance ).
			 */
			$qt_init['iconize_quicktags'] = (bool) apply_filters( 'iconize_quicktags', true, $editor_id, $screen_id );
		}

		$this->enqueue_wpbs                     = $qt_init['iconize_quicktags'];
		$this->quicktags_iconize_plugin_enabled = $qt_init['iconize_quicktags'];

		return $qt_init;
	}

	/**
	 * Enqueue scripts and styles for editor dialog and render the dialog only on pages where editor with our plugins included exists.
	 *
	 * @since 1.0.0
	 *
	 * @uses Iconize_WP::enqueue_wpbs
	 * @uses Iconize_WP::tinymce_iconize_plugin_enabled
	 * @uses Iconize_WP::quicktags_iconize_plugin_enabled
	 * @uses Iconize_WP::get_iconize_dialog_inline_styles()
	 * @uses Iconize_WP::get_iconize_dialog_strings()
	 * @uses Iconize_WP::get_icons_array()
	 */
	public function iconize_editor_dialog_scripts() {

		// Check which plugins are active to know what to enqueue.
		$enqueue_wpbs                     = $this->enqueue_wpbs;
		$tinymce_iconize_plugin_enabled   = $this->tinymce_iconize_plugin_enabled;
		$quicktags_iconize_plugin_enabled = $this->quicktags_iconize_plugin_enabled;

		// Use the .min suffix if SCRIPT_DEBUG is turned off.
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Compatibility with Black Studio TinyMCE Widget Plugin ( only on widgets screen ).
		$bs_widget_active = false;
		if ( is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen    = get_current_screen();
			$screen_id = isset( $screen->id ) ? $screen->id : '';
			if ( in_array( 'black-studio-tinymce-widget/black-studio-tinymce-widget.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) && 'widgets' === $screen_id ) {
				$bs_widget_active = true;
			}
		}

		// Allow bootstrapmodal plugin usage for other TinyMCE plugins.
		if ( $enqueue_wpbs || did_action( 'enqueue_block_assets' ) ) {

			// Check if styles and scripts for bootstrap modal are already enqueued before adding them.
			if ( ! wp_style_is( 'iconize-bootstrap-modal', 'enqueued' ) ) {

				wp_enqueue_style(
					'iconize-bootstrap-modal',
					plugins_url( "css/bootstrap-modal$suffix.css", __FILE__ ),
					array(),
					$this->version
				);
			}

			if ( ! wp_script_is( 'iconize-bootstrap-modal', 'enqueued' ) ) {

				wp_enqueue_script(
					'iconize-bootstrap-modal',
					plugins_url( "js/bootstrap-modal$suffix.js", __FILE__ ),
					array( 'jquery' ),
					$this->version,
					true
				);
			}
		}

		// Enqueue iconize dialog scripts and styles only if both of our editor plugins are called or quicktags plugin is called.
		if ( $tinymce_iconize_plugin_enabled || $quicktags_iconize_plugin_enabled || $bs_widget_active || did_action( 'enqueue_block_assets' ) ) {

			// Check if specific styles and scripts are already enqueued before adding them.
			if ( ! wp_style_is( 'iconize-dialog', 'enqueued' ) ) {

				wp_enqueue_style(
					'iconize-dialog',
					plugins_url( "css/iconize-dialog$suffix.css", __FILE__ ),
					array(),
					$this->version
				);

				$dialog_inline_styles = $this->get_iconize_dialog_inline_styles();
				if ( $dialog_inline_styles ) {
					wp_add_inline_style( 'iconize-dialog', $dialog_inline_styles );
				}
			}

			if ( ! wp_style_is( 'wp-color-picker', 'enqueued' ) ) {
				wp_enqueue_style( 'wp-color-picker' );
			}

			if ( ! wp_script_is( 'iconize-helpers', 'enqueued' ) ) {

				wp_enqueue_script(
					'iconize-helpers',
					plugins_url( "js/iconize-helpers$suffix.js", __FILE__ ),
					array(
						'jquery-ui-autocomplete',
						'jquery-effects-blind',
						'jquery-effects-highlight',
					),
					$this->version,
					true
				);

				$iconize_dialog_l10n = $this->get_iconize_dialog_strings();
				$icons_arr           = $this->get_icons_array();

				$iconize_dialog_params          = array( 'l10n' => $iconize_dialog_l10n );
				$iconize_dialog_params['icons'] = empty( $icons_arr ) ? false : $icons_arr;

				wp_localize_script( 'iconize-helpers', 'iconizeDialogParams', $iconize_dialog_params );
			}

			if ( is_admin() ) {

				if ( ! wp_script_is( 'wp-color-picker', 'enqueued' ) ) {

					wp_enqueue_script( 'wp-color-picker' );
				}
			} else {

				/**
				 * If tinymce editor with iconize mce plugins is called on the front end, simple wp_enqueue_script( 'wp-color-picker' ) won't work.
				 * NOTE: if you call iconize editor plugins on front end editor instance you'll need to style the dialog.
				 */
				wp_enqueue_script( // @codingStandardsIgnoreLine.
					'iris',
					admin_url( 'js/iris.min.js' ),
					array(
						'jquery-ui-draggable',
						'jquery-ui-slider',
						'jquery-touch-punch',
					),
					false,
					true
				);

				wp_enqueue_script( // @codingStandardsIgnoreLine.
					'wp-color-picker',
					admin_url( 'js/color-picker.min.js' ),
					array( 'iris' ),
					false,
					true
				);

				$colorpicker_l10n = array(
					'clear'         => __( 'Clear', 'iconize' ),
					'defaultString' => __( 'Default', 'iconize' ),
					'pick'          => __( 'Select Color', 'iconize' ),
				);
				wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', $colorpicker_l10n );
			}

			// Enqueue editor dialog script.
			wp_enqueue_script(
				'iconize-mce-dialog',
				plugins_url( "tinymce/dialog$suffix.js", __FILE__ ),
				array( 'iconize-helpers' ),
				$this->version,
				true
			);

			/**
			 * Pass Iconize_WP::quicktags_iconize_plugin_enabled variable value to dialog script
			 * so that we can add "icon" button to quicktags from there if enabled.
			 */
			$iconize_dialog_settings = array( 'iconizeQuicktags' => $quicktags_iconize_plugin_enabled );

			wp_localize_script( 'iconize-mce-dialog', 'iconizeSettings', $iconize_dialog_settings );

			// Render the dialog.
			$this->iconize_dialog( 'mce' );
		}
	}

	/**
	 * Widget system integration
	 */

	/**
	 * Add Icon option to widgets settings.
	 *
	 * @since 1.0.0
	 *
	 * @param object $t Description.
	 * @param string $return Description.
	 * @param array  $instance Description.
	 */
	public function iconize_in_widget_form( $t, $return, $instance ) {

		$instance = wp_parse_args(
			(array) $instance,
			array(
				'icon_name'           => '',
				'icon_set'            => '',
				'icon_transform'      => '',
				'icon_color'          => '',
				'icon_size'           => '',
				'icon_align'          => '',
				'icon_custom_classes' => '',
			)
		);

		if ( ! isset( $instance['icon_name'] ) ) {
			$instance['icon_name'] = null;
		}

		if ( ! isset( $instance['icon_set'] ) ) {
			$instance['icon_set'] = null;
		}

		if ( ! isset( $instance['icon_transform'] ) ) {
			$instance['icon_transform'] = null;
		}

		if ( ! isset( $instance['icon_color'] ) ) {
			$instance['icon_color'] = null;
		}

		if ( ! isset( $instance['icon_size'] ) ) {
			$instance['icon_size'] = null;
		}

		if ( ! isset( $instance['icon_align'] ) ) {
			$instance['icon_align'] = null;
		}

		if ( ! isset( $instance['icon_custom_classes'] ) ) {
			$instance['icon_custom_classes'] = null;
		}
		?>
		<p>
			<label class="preview-icon-label">
				<?php esc_html_e( 'Title Icon:', 'iconize' ); ?>
				<button type="button" class="preview-icon button iconized-hover-trigger"><span class="iconized <?php echo esc_attr( $instance['icon_name'] ); ?> <?php echo esc_attr( $instance['icon_set'] ); ?> <?php echo esc_attr( $instance['icon_transform'] ); ?>"></span></button>
			</label>
			<span>
				<input type="hidden" id="<?php echo esc_attr( $t->get_field_id( 'icon_name' ) ); ?>" class="iconize-input-name" name="<?php echo esc_attr( $t->get_field_name( 'icon_name' ) ); ?>" value="<?php echo esc_attr( $instance['icon_name'] ); ?>">
				<input type="hidden" id="<?php echo esc_attr( $t->get_field_id( 'icon_set' ) ); ?>" class="iconize-input-set" name="<?php echo esc_attr( $t->get_field_name( 'icon_set' ) ); ?>" value="<?php echo esc_attr( $instance['icon_set'] ); ?>">
				<input type="hidden" id="<?php echo esc_attr( $t->get_field_id( 'icon_transform' ) ); ?>" class="iconize-input-transform" name="<?php echo esc_attr( $t->get_field_name( 'icon_transform' ) ); ?>" value="<?php echo esc_attr( $instance['icon_transform'] ); ?>">
				<input type="hidden" id="<?php echo esc_attr( $t->get_field_id( 'icon_color' ) ); ?>" class="iconize-input-color" name="<?php echo esc_attr( $t->get_field_name( 'icon_color' ) ); ?>" value="<?php echo esc_attr( $instance['icon_color'] ); ?>">
				<input type="hidden" id="<?php echo esc_attr( $t->get_field_id( 'icon_size' ) ); ?>" class="iconize-input-size" name="<?php echo esc_attr( $t->get_field_name( 'icon_size' ) ); ?>" value="<?php echo esc_attr( $instance['icon_size'] ); ?>">
				<input type="hidden" id="<?php echo esc_attr( $t->get_field_id( 'icon_align' ) ); ?>" class="iconize-input-align" name="<?php echo esc_attr( $t->get_field_name( 'icon_align' ) ); ?>" value="<?php echo esc_attr( $instance['icon_align'] ); ?>">
				<input type="hidden" id="<?php echo esc_attr( $t->get_field_id( 'icon_custom_classes' ) ); ?>" class="iconize-input-custom-classes" name="<?php echo esc_attr( $t->get_field_name( 'icon_custom_classes' ) ); ?>" value="<?php echo esc_attr( $instance['icon_custom_classes'] ); ?>">
			</span>
		</p>
		<p>
			<input id="<?php echo esc_attr( $t->get_field_id( 'icon_position' ) ); ?>" name="<?php echo esc_attr( $t->get_field_name( 'icon_position' ) ); ?>" type="checkbox" <?php checked( isset( $instance['icon_position'] ) ? $instance['icon_position'] : 0 ); ?> />&nbsp;<label for="<?php echo esc_attr( $t->get_field_id( 'icon_position' ) ); ?>"><?php esc_html_e( 'Insert icon after the title', 'iconize' ); ?></label>
		</p>
		<?php

		$retrun = null;

		return array( $t, $return, $instance );
	}

	/**
	 * Handle widget settings update.
	 *
	 * @since 1.0.0
	 *
	 * @param array $instance Description.
	 * @param array $new_instance Description.
	 * @param array $old_instance Description.
	 */
	public function iconize_in_widget_form_update( $instance, $new_instance, $old_instance ) {

		$instance['icon_name']           = $new_instance['icon_name'];
		$instance['icon_set']            = $new_instance['icon_set'];
		$instance['icon_transform']      = $new_instance['icon_transform'];
		$instance['icon_color']          = $new_instance['icon_color'];
		$instance['icon_size']           = $new_instance['icon_size'];
		$instance['icon_align']          = $new_instance['icon_align'];
		$instance['icon_custom_classes'] = $new_instance['icon_custom_classes'];
		$instance['icon_position']       = isset( $new_instance['icon_position'] );

		return $instance;
	}

	/**
	 * Add icon to widget title.
	 *
	 * @since 1.0.0
	 *
	 * @uses iconize_get_icon()
	 * @uses Iconize_WP::get_iconize_dialog_dropdown_options_for()
	 *
	 * @param array $params Description.
	 * @return array $params
	 */
	public function iconize_dynamic_sidebar_params( $params ) {

		global $wp_registered_widgets;
		$widget_id  = $params[0]['widget_id'];
		$widget_obj = $wp_registered_widgets[ $widget_id ];
		$widget_opt = get_option( $widget_obj['callback'][0]->option_name );
		$widget_num = $widget_obj['params'][0]['number'];

		$icon_args = array();

		$icon_args['icon_name']           = ( isset( $widget_opt[ $widget_num ]['icon_name'] ) ) ? $widget_opt[ $widget_num ]['icon_name'] : '';
		$icon_args['icon_set']            = ( isset( $widget_opt[ $widget_num ]['icon_set'] ) ) ? $widget_opt[ $widget_num ]['icon_set'] : '';
		$icon_args['icon_transform']      = ( isset( $widget_opt[ $widget_num ]['icon_transform'] ) ) ? $widget_opt[ $widget_num ]['icon_transform'] : '';
		$icon_args['icon_size']           = ( isset( $widget_opt[ $widget_num ]['icon_size'] ) ) ? $widget_opt[ $widget_num ]['icon_size'] : '';
		$icon_args['icon_align']          = ( isset( $widget_opt[ $widget_num ]['icon_align'] ) ) ? $widget_opt[ $widget_num ]['icon_align'] : '';
		$icon_args['icon_custom_classes'] = ( isset( $widget_opt[ $widget_num ]['icon_custom_classes'] ) ) ? $widget_opt[ $widget_num ]['icon_custom_classes'] : '';
		$icon_args['icon_color']          = ( isset( $widget_opt[ $widget_num ]['icon_color'] ) ) ? $widget_opt[ $widget_num ]['icon_color'] : '';
		$icon_args['icon_position']       = ( isset( $widget_opt[ $widget_num ]['icon_position'] ) ) ? $widget_opt[ $widget_num ]['icon_position'] : false;
		$icon_args['icon_position']       = $icon_args['icon_position'] ? 'after' : '';

		// Generate icon html.
		$icon_html = iconize_get_icon( $icon_args, 'widget_title' );

		// Take all hover effects.
		$hovers = array_keys( $this->get_iconize_dialog_dropdown_options_for( 'hover' ) );

		// Check for "hover-color-change" class in custom classes list.
		$hover_color_change = strpos( $icon_args['icon_custom_classes'], 'hover-color-change' );

		// If hover effect is included, wrap icon and title with span.iconized-hover-trigger.
		if ( ( ! empty( $icon_args['icon_transform'] ) && in_array( $icon_args['icon_transform'], $hovers, true ) ) || false !== $hover_color_change ) {

			$params[0]['before_title'] .= '<span class="iconized-hover-trigger">';

			if ( 'after' === $icon_args['icon_position'] ) {

				$after_title              = $params[0]['after_title'];
				$params[0]['after_title'] = $icon_html . '</span>' . $after_title;

			} else {

				$params[0]['before_title'] .= $icon_html;
				$after_title                = '</span>' . $params[0]['after_title'];
			}
		} else {

			// Just insert icon before or after the title.
			if ( 'after' === $icon_args['icon_position'] ) {

				$after_title              = $params[0]['after_title'];
				$params[0]['after_title'] = $icon_html . $after_title;

			} else {

				$params[0]['before_title'] .= $icon_html;
			}
		}

		return $params;
	}

	/**
	 * Grab the settings for widget title icon and use in later filter.
	 *
	 * @since 1.2.0
	 *
	 * @param array $instance Widget Instance.
	 * @return array $instance
	 */
	public function siteorigin_panels_widget_instance( $instance ) {
		if ( ! isset( $instance['icon_name'] ) ) {
			return $instance;
		}

		$this->siteorigin_widget_title_settings = [
			'icon_name'           => $instance['icon_name'],
			'icon_set'            => $instance['icon_set'],
			'icon_transform'      => $instance['icon_transform'],
			'icon_color'          => $instance['icon_color'],
			'icon_size'           => $instance['icon_size'],
			'icon_align'          => $instance['icon_align'],
			'icon_custom_classes' => $instance['icon_custom_classes'],
			'icon_position'       => ( 1 === intval( $instance['icon_position'] ) ? 'after' : '' ),
		];
		
		return $instance;
	}

	/**
	 * Add widget title icon.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Array of args.
	 * @return array $args
	 */
	public function siteorigin_panels_widget_args( $args ) {
		if ( empty( $this->siteorigin_widget_title_settings ) ) {
			return $instance;
		}

		$icon_args = $this->siteorigin_widget_title_settings;

		// Generate icon html.
		$icon_html = iconize_get_icon( $icon_args, 'siteorigin_widget_title' );

		// Take all hover effects.
		$hovers = array_keys( $this->get_iconize_dialog_dropdown_options_for( 'hover' ) );

		// Check for "hover-color-change" class in custom classes list.
		$hover_color_change = strpos( $icon_args['icon_custom_classes'], 'hover-color-change' );

		// If hover effect is included, wrap icon and title with span.iconized-hover-trigger.
		if ( ( ! empty( $icon_args['icon_transform'] ) && in_array( $icon_args['icon_transform'], $hovers, true ) ) || false !== $hover_color_change ) {

			$args['before_title'] .= '<span class="iconized-hover-trigger">';

			if ( 'after' === $icon_args['icon_position'] ) {

				$after_title         = $args['after_title'];
				$args['after_title'] = $icon_html . '</span>' . $after_title;

			} else {

				$args['before_title'] .= $icon_html;
				$after_title           = '</span>' . $args['after_title'];
			}
		} else {

			// Just insert icon before or after the title.
			if ( 'after' === $icon_args['icon_position'] ) {

				$after_title         = $args['after_title'];
				$args['after_title'] = $icon_html . $after_title;

			} else {

				$args['before_title'] .= $icon_html;
			}
		}

		return $args;
	}

	/**
	 * Custom Menus system integration
	 */

	/**
	 * Add icon option to custom menus system.
	 *
	 * @since 1.0.0
	 *
	 * @uses Iconize_WP::get_iconize_support_for()
	 * @uses Iconize_WP::iconize_get_term_icon_by()
	 *
	 * @param string $item_id Description.
	 * @param object $item Description.
	 * @param string $depth Description.
	 * @param array  $args Description.
	 */
	public function iconize_nav_menu_item_custom_fields( $item_id, $item, $depth, $args ) {

		// Icon params.
		$icon_name           = $item->icon_name;
		$icon_set            = $item->icon_set;
		$icon_transform      = $item->icon_transform;
		$icon_color          = $item->icon_color;
		$icon_size           = $item->icon_size;
		$icon_align          = $item->icon_align;
		$icon_custom_classes = $item->icon_custom_classes;
		$icon_position       = $item->icon_position;

		// Display taxonomy term icon when item is added to menu if available.
		$status = $item->post_status;
		$type   = $item->type;

		if ( 'draft' === $status && 'taxonomy' === $type ) {

			$taxonomy = $item->object;
			$term_id  = $item->object_id;

			$tax_support       = $this->get_iconize_support_for( 'taxonomy_' . $taxonomy );
			$tax_icons_enabled = $tax_support['enabled'];

			if ( $tax_icons_enabled ) {

				$term_icon_args = iconize_get_term_icon_by( 'id', $term_id, $taxonomy, 'array' );

				if ( ! empty( $term_icon_args ) ) {

					$icon_name           = $term_icon_args['icon_name'];
					$icon_set            = $term_icon_args['icon_set'];
					$icon_transform      = $term_icon_args['icon_transform'];
					$icon_color          = $term_icon_args['icon_color'];
					$icon_size           = $term_icon_args['icon_size'];
					$icon_align          = $term_icon_args['icon_align'];
					$icon_custom_classes = $term_icon_args['icon_custom_classes'];
				}
			}
		}
		?>
		<p class="field-menu-item-icon description description-thin">
			<label class="preview-icon-label">
				<?php esc_html_e( 'Menu Item Icon: ', 'iconize' ); ?><button type="button" class="preview-icon button iconized-hover-trigger"><span class="iconized <?php echo esc_attr( $icon_name ); ?> <?php echo esc_attr( $icon_set ); ?> <?php echo esc_attr( $icon_transform ); ?>"></span></button>
			</label>
			<span>
				<input type="hidden" id="edit-menu-item-icon-name-<?php echo esc_attr( $item_id ); ?>" class="edit-menu-item-icon-name iconize-input-name" name="menu-item-icon-name[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $icon_name ); ?>">
				<input type="hidden" id="edit-menu-item-icon-set-<?php echo esc_attr( $item_id ); ?>" class="edit-menu-item-icon-set iconize-input-set" name="menu-item-icon-set[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $icon_set ); ?>">
				<input type="hidden" id="edit-menu-item-icon-transform-<?php echo esc_attr( $item_id ); ?>" class="edit-menu-item-icon-transform iconize-input-transform" name="menu-item-icon-transform[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $icon_transform ); ?>">
				<input type="hidden" id="edit-menu-item-icon-color-<?php echo esc_attr( $item_id ); ?>" class="edit-menu-item-icon-color iconize-input-color" name="menu-item-icon-color[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $icon_color ); ?>">
				<input type="hidden" id="edit-menu-item-icon-size-<?php echo esc_attr( $item_id ); ?>" class="edit-menu-item-icon-size iconize-input-size" name="menu-item-icon-size[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $icon_size ); ?>">
				<input type="hidden" id="edit-menu-item-icon-align-<?php echo esc_attr( $item_id ); ?>" class="edit-menu-item-icon-align iconize-input-align" name="menu-item-icon-align[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $icon_align ); ?>">
				<input type="hidden" id="edit-menu-item-icon-custom-classes-<?php echo esc_attr( $item_id ); ?>" class="edit-menu-item-icon-color iconize-input-custom-classes" name="menu-item-icon-custom-classes[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $icon_custom_classes ); ?>">
			</span>
		</p>
		<p class="field-menu-item-icon-position description">
			<label for="edit-menu-item-icon-position-<?php echo esc_attr( $item_id ); ?>">
				<input type="checkbox" id="edit-menu-item-icon-position-<?php echo esc_attr( $item_id ); ?>" value="after" name="menu-item-icon-position[<?php echo esc_attr( $item_id ); ?>]"<?php checked( $item->icon_position, 'after' ); ?> />
				<?php esc_html_e( 'Insert icon after menu item title', 'iconize' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Add icon option to custom menus iside customizer.
	 *
	 * @since 1.0.0
	 */
	public function customizer_nav_menu_item_custom_fields() {
		?>
		<p class="customize-control-field-menu-item-icon description description-thin">
			<label class="preview-icon-label">
				<?php esc_html_e( 'Menu Item Icon: ', 'iconize' ); ?>
				<button type="button" class="preview-icon button iconized-hover-trigger"><span class="iconized"></span></button>
				<button class="customizer-preview-icon-trigger hidden"></button>
			</label>
			<span>
				<input type="hidden" id="edit-menu-item-icon-name-{{ data.menu_item_id }}" class="edit-menu-item-icon-name iconize-input-name" name="menu-item-icon-name[{{ data.menu_item_id }}]" value="">
				<input type="hidden" id="edit-menu-item-icon-set-{{ data.menu_item_id }}" class="edit-menu-item-icon-set iconize-input-set" name="menu-item-icon-set[{{ data.menu_item_id }}]" value="">
				<input type="hidden" id="edit-menu-item-icon-transform-{{ data.menu_item_id }}" class="edit-menu-item-icon-transform iconize-input-transform" name="menu-item-icon-transform[{{ data.menu_item_id }}]" value="">
				<input type="hidden" id="edit-menu-item-icon-color-{{ data.menu_item_id }}" class="edit-menu-item-icon-color iconize-input-color" name="menu-item-icon-color[{{ data.menu_item_id }}]" value="">
				<input type="hidden" id="edit-menu-item-icon-size-{{ data.menu_item_id }}" class="edit-menu-item-icon-size iconize-input-size" name="menu-item-icon-size[{{ data.menu_item_id }}]" value="">
				<input type="hidden" id="edit-menu-item-icon-align-{{ data.menu_item_id }}" class="edit-menu-item-icon-align iconize-input-align" name="menu-item-icon-align[{{ data.menu_item_id }}]" value="">
				<input type="hidden" id="edit-menu-item-icon-custom-classes-{{ data.menu_item_id }}" class="edit-menu-item-icon-color iconize-input-custom-classes" name="menu-item-icon-custom-classes[{{ data.menu_item_id }}]" value="">
			</span>
		</p>
		<p class="customize-control-field-menu-item-icon-position description description-thin">
			<label for="edit-menu-item-icon-position-{{ data.menu_item_id }}">
				<input type="checkbox" id="edit-menu-item-icon-position-{{ data.menu_item_id }}" value="after" name="menu-item-icon-position[{{ data.menu_item_id }}]" />
				<?php esc_html_e( 'Insert icon after menu item title', 'iconize' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Get sanitized posted value for a setting's icon.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Customize_Nav_Menu_Item_Setting $setting Setting.
	 *
	 * @return array Icon value.
	 */
	public function get_sanitized_icon_post_data( WP_Customize_Nav_Menu_Item_Setting $setting ) {
		if ( ! $setting->post_value() ) {
			return null;
		}

		$icon_settings = array(
			'icon_name',
			'icon_set',
			'icon_transform',
			'icon_color',
			'icon_size',
			'icon_align',
			'icon_custom_classes',
			'icon_position',
		);

		$return = array();

		$unsanitized_post_value = $setting->manager->unsanitized_post_values()[ $setting->id ];
		foreach ( $icon_settings as $icon_setting ) {
			if ( isset( $unsanitized_post_value[ $icon_setting ] ) ) {
				$return[ '_menu_item_' . $icon_setting ] = $unsanitized_post_value[ $icon_setting ];
			}
		}
		return $return;
	}

	/**
	 * Preview changes to the nav menu item icon.
	 *
	 * @since 1.0.0
	 *
	 * Note the unimplemented to-do in the doc block for the setting's preview method.
	 *
	 * @see WP_Customize_Nav_Menu_Item_Setting::preview()
	 *
	 * @param WP_Customize_Nav_Menu_Item_Setting $setting Setting.
	 */
	public function preview_nav_menu_setting_postmeta( WP_Customize_Nav_Menu_Item_Setting $setting ) {
		$icon_settings = $this->get_sanitized_icon_post_data( $setting );
		if ( null === $icon_settings ) {
			return;
		}

		add_filter(
			'get_post_metadata',
			static function ( $value, $object_id, $meta_key ) use ( $setting, $icon_settings ) {
				if ( $object_id === $setting->post_id && array_key_exists( $meta_key, $icon_settings ) ) {
					return $icon_settings[ $meta_key ];
				}
				return $value;
			},
			10,
			3
		);
	}

	/**
	 * Save changes to the nav menu item icon.
	 *
	 * @since 1.0.0
	 *
	 * Note the unimplemented to-do in the doc block for the setting's preview method.
	 *
	 * @see WP_Customize_Nav_Menu_Item_Setting::update()
	 *
	 * @param WP_Customize_Nav_Menu_Item_Setting $setting Setting.
	 */
	public function save_nav_menu_setting_postmeta( WP_Customize_Nav_Menu_Item_Setting $setting ) {
		$icon_settings = $this->get_sanitized_icon_post_data( $setting );
		if ( null !== $icon_settings ) {
			foreach ( $icon_settings as $meta_key => $meta_value ) {
				update_post_meta( $setting->post_id, $meta_key, $meta_value );
			}
		}
	}

	/**
	 * Customizer Preview.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Customize_Manager $wp_customize WP_Customize_Manager.
	 */
	public function customize_register( WP_Customize_Manager $wp_customize ) {
		if ( $wp_customize->settings_previewed() ) {
			foreach ( $wp_customize->settings() as $setting ) {
				if ( $setting instanceof WP_Customize_Nav_Menu_Item_Setting ) {
					$this->preview_nav_menu_setting_postmeta( $setting );
				}
			}
		}
	}

	/**
	 * Save changes to the nav menu item icon.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Customize_Manager $wp_customize WP_Customize_Manager.
	 */
	public function customize_save_after( WP_Customize_Manager $wp_customize ) {
		foreach ( $wp_customize->settings() as $setting ) {
			if ( $setting instanceof WP_Customize_Nav_Menu_Item_Setting && $setting->check_capabilities() ) {
				$this->save_nav_menu_setting_postmeta( $setting );
			}
		}
	}

	/**
	 * Save menu item icon option.
	 *
	 * @since 1.0.0
	 *
	 * @param string $menu_id Description.
	 * @param string $menu_item_db_id Description.
	 * @param array  $args Description.
	 */
	public function iconize_update_nav_menu_item( $menu_id, $menu_item_db_id, $args ) {

		$args['menu-item-icon-name']           = isset( $_POST['menu-item-icon-name'][ $menu_item_db_id ] ) ? sanitize_text_field( wp_unslash( $_POST['menu-item-icon-name'][ $menu_item_db_id ] ) ) : ''; // @codingStandardsIgnoreLine.
		$args['menu-item-icon-set']            = isset( $_POST['menu-item-icon-set'][ $menu_item_db_id ] ) ? sanitize_text_field( wp_unslash( $_POST['menu-item-icon-set'][ $menu_item_db_id ] ) ) : ''; // @codingStandardsIgnoreLine.
		$args['menu-item-icon-transform']      = isset( $_POST['menu-item-icon-transform'][ $menu_item_db_id ] ) ? sanitize_text_field( wp_unslash( $_POST['menu-item-icon-transform'][ $menu_item_db_id ] ) ) : ''; // @codingStandardsIgnoreLine.
		$args['menu-item-icon-color']          = isset( $_POST['menu-item-icon-color'][ $menu_item_db_id ] ) ? sanitize_text_field( wp_unslash( $_POST['menu-item-icon-color'][ $menu_item_db_id ] ) ) : ''; // @codingStandardsIgnoreLine.
		$args['menu-item-icon-size']           = isset( $_POST['menu-item-icon-size'][ $menu_item_db_id ] ) ? sanitize_text_field( wp_unslash( $_POST['menu-item-icon-size'][ $menu_item_db_id ] ) ) : ''; // @codingStandardsIgnoreLine.
		$args['menu-item-icon-align']          = isset( $_POST['menu-item-icon-align'][ $menu_item_db_id ] ) ? sanitize_text_field( wp_unslash( $_POST['menu-item-icon-align'][ $menu_item_db_id ] ) ) : ''; // @codingStandardsIgnoreLine.
		$args['menu-item-icon-custom-classes'] = isset( $_POST['menu-item-icon-custom-classes'][ $menu_item_db_id ] ) ? sanitize_text_field( wp_unslash( $_POST['menu-item-icon-custom-classes'][ $menu_item_db_id ] ) ) : ''; // @codingStandardsIgnoreLine.
		$args['menu-item-icon-position']       = isset( $_POST['menu-item-icon-position'][ $menu_item_db_id ] ) ? sanitize_text_field( wp_unslash( $_POST['menu-item-icon-position'][ $menu_item_db_id ] ) ) : ''; // @codingStandardsIgnoreLine.

		update_post_meta( $menu_item_db_id, '_menu_item_icon_name', $args['menu-item-icon-name'] );
		update_post_meta( $menu_item_db_id, '_menu_item_icon_set', $args['menu-item-icon-set'] );
		update_post_meta( $menu_item_db_id, '_menu_item_icon_transform', $args['menu-item-icon-transform'] );
		update_post_meta( $menu_item_db_id, '_menu_item_icon_color', $args['menu-item-icon-color'] );
		update_post_meta( $menu_item_db_id, '_menu_item_icon_size', $args['menu-item-icon-size'] );
		update_post_meta( $menu_item_db_id, '_menu_item_icon_align', $args['menu-item-icon-align'] );
		update_post_meta( $menu_item_db_id, '_menu_item_icon_custom_classes', $args['menu-item-icon-custom-classes'] );
		update_post_meta( $menu_item_db_id, '_menu_item_icon_position', sanitize_key( $args['menu-item-icon-position'] ) );
	}

	/**
	 * Setup the nav menu object to have the additionnal properties.
	 *
	 * @since 1.0.0
	 *
	 * @param object $menu_item Description.
	 */
	public function iconize_setup_nav_menu_item( $menu_item ) {

		$menu_item->icon_name           = empty( $menu_item->icon_name ) ? get_post_meta( $menu_item->ID, '_menu_item_icon_name', true ) : $menu_item->icon_name;
		$menu_item->icon_set            = empty( $menu_item->icon_set ) ? get_post_meta( $menu_item->ID, '_menu_item_icon_set', true ) : $menu_item->icon_set;
		$menu_item->icon_transform      = empty( $menu_item->icon_transform ) ? get_post_meta( $menu_item->ID, '_menu_item_icon_transform', true ) : $menu_item->icon_transform;
		$menu_item->icon_color          = empty( $menu_item->icon_color ) ? get_post_meta( $menu_item->ID, '_menu_item_icon_color', true ) : $menu_item->icon_color;
		$menu_item->icon_size           = empty( $menu_item->icon_size ) ? get_post_meta( $menu_item->ID, '_menu_item_icon_size', true ) : $menu_item->icon_size;
		$menu_item->icon_align          = empty( $menu_item->icon_align ) ? get_post_meta( $menu_item->ID, '_menu_item_icon_align', true ) : $menu_item->icon_align;
		$menu_item->icon_custom_classes = empty( $menu_item->icon_custom_classes ) ? get_post_meta( $menu_item->ID, '_menu_item_icon_custom_classes', true ) : $menu_item->icon_custom_classes;
		$menu_item->icon_position       = empty( $menu_item->icon_position ) ? get_post_meta( $menu_item->ID, '_menu_item_icon_position', true ) : $menu_item->icon_position;

		return $menu_item;
	}

	/**
	 * Custom Walker for menu edit.
	 *
	 * WordPress does not provide any hook to modify the result of menu edit screen.
	 * This function calls our custom edit walker.
	 *
	 * @since 1.0.0
	 *
	 * @param string $a Walker class name.
	 * @return string custom walker
	 */
	public function iconize_edit_nav_menu_walker( $a ) {

		return 'Iconize_Walker_Nav_Menu_Edit';
	}

	/**
	 * Add icon before menu item title ( if function attached to "iconize_menu_items_with" filter is returning "title_link" string ).
	 *
	 * @since 1.0.0
	 *
	 * @param string $title Description.
	 * @param string $id Description.
	 */
	public function iconize_menu_item_title( $title, $id ) {

		if ( ! is_nav_menu_item( $id ) ) {
			return;
		}

		$icon_args['icon_name']           = get_post_meta( $id, '_menu_item_icon_name', true );
		$icon_args['icon_set']            = get_post_meta( $id, '_menu_item_icon_set', true );
		$icon_args['icon_transform']      = get_post_meta( $id, '_menu_item_icon_transform', true );
		$icon_args['icon_color']          = get_post_meta( $id, '_menu_item_icon_color', true );
		$icon_args['icon_size']           = get_post_meta( $id, '_menu_item_icon_size', true );
		$icon_args['icon_align']          = get_post_meta( $id, '_menu_item_icon_align', true );
		$icon_args['icon_custom_classes'] = get_post_meta( $id, '_menu_item_icon_custom_classes', true );
		$icon_args['icon_position']       = get_post_meta( $id, '_menu_item_icon_position', true );

		$icon = iconize_get_icon( $icon_args, 'menu_item' );

		return 'after' === $icon_args['icon_position'] ? $title . $icon : $icon . $title;
	}

	/**
	 * Add iconized-hover-trigger CSS class to menu item link classes if needed ( if function attached to "iconize_menu_items_with" filter is returning "title_link" string ).
	 *
	 * @since 1.0.0
	 *
	 * @param array  $atts Description.
	 * @param object $item Description.
	 * @param array  $args Description.
	 */
	public function iconize_nav_menu_link_attributes( $atts, $item, $args ) {

		$hovers = $this->get_iconize_dialog_dropdown_options_for( 'hover' );
		$hovers = array_keys( $hovers );

		if ( ! empty( $item->icon_transform ) && in_array( $item->icon_transform, $hovers, true ) ) {

			if ( isset( $atts['class'] ) ) {

				$atts['class'] .= ' iconized-hover-trigger';

			} else {

				$atts['class'] = 'iconized-hover-trigger';
			}
		}

		return $atts;
	}

	/**
	 * Add icon before menu item title and wrap them with <span class="iconized-hover-trigger"> tag if needed ( if function attached to "iconize_menu_items_with" filter is returning "title" string ).
	 *
	 * @since 1.0.0
	 *
	 * @param string $title Description.
	 * @param string $id Description.
	 */
	public function iconize_menu_item_title_all( $title, $id ) {

		if ( ! is_nav_menu_item( $id ) ) {
			return $title;
		}

		$icon_args['icon_name']           = get_post_meta( $id, '_menu_item_icon_name', true );
		$icon_args['icon_set']            = get_post_meta( $id, '_menu_item_icon_set', true );
		$icon_args['icon_transform']      = get_post_meta( $id, '_menu_item_icon_transform', true );
		$icon_args['icon_color']          = get_post_meta( $id, '_menu_item_icon_color', true );
		$icon_args['icon_size']           = get_post_meta( $id, '_menu_item_icon_size', true );
		$icon_args['icon_align']          = get_post_meta( $id, '_menu_item_icon_align', true );
		$icon_args['icon_custom_classes'] = get_post_meta( $id, '_menu_item_icon_custom_classes', true );
		$icon_args['icon_position']       = get_post_meta( $id, '_menu_item_icon_position', true );

		$icon = iconize_get_icon( $icon_args, 'menu_item' );

		$hovers = array_keys( $this->get_iconize_dialog_dropdown_options_for( 'hover' ) );

		if ( ! empty( $icon_args['icon_transform'] ) && in_array( $icon_args['icon_transform'], $hovers, true ) ) {

			$title = '<span class="iconized-hover-trigger">' . $icon . $title . '</span>';

		} else {

			$title = 'after' === $icon_args['icon_position'] ? $title . $icon : $icon . $title;
		}

		return $title;
	}

	/**
	 * Filter wp_nav_menu_args to display icons on all menus.
	 *
	 * We will call our walker class only if there is configured menu, because of the known bug where
	 * custom nav menu walkers and wp_nav_menu's 'fallback_cb' argument ( wp_page_menu by default ) are not compatible.
	 * - http://core.trac.wordpress.org/ticket/18232
	 * - http://core.trac.wordpress.org/ticket/24587
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Description.
	 */
	public function iconize_nav_menu_args( $args = array() ) {

		// We will use the same logic found in wp_nav_menu() function.
		// Get the nav menu based on the requested menu.
		$menu = wp_get_nav_menu_object( $args['menu'] );

		// Get the nav menu based on the theme_location.
		$locations = get_nav_menu_locations();
		if ( ! $menu && $args['theme_location'] && $locations && isset( $locations[ $args['theme_location'] ] ) ) {
			$menu = wp_get_nav_menu_object( $locations[ $args['theme_location'] ] );
		}

		// Get the first menu that has items if we still can't find a menu.
		if ( ! $menu && ! $args['theme_location'] ) {

			$menus = wp_get_nav_menus();

			foreach ( $menus as $menu_maybe ) {

				$menu_items = wp_get_nav_menu_items( $menu_maybe->term_id, array( 'update_post_term_cache' => false ) );
				if ( $menu_items ) {

					$menu = $menu_maybe;
					break;
				}
			}
		}

		if ( $menu && empty( $args['walker'] ) ) {
			$args['walker'] = new Iconize_Walker_Nav_Menu();
		}

		return $args;
	}

	/**
	 * Taxonomy system integration
	 */

	/**
	 * Add field to "add taxonomy term" form
	 *
	 * @since    1.0.0
	 */
	public function iconize_taxonomy_add_form_fields() {
		?>
		<div class="form-field">
			<label class="preview-icon-label">
				<?php esc_html_e( 'Icon: ', 'iconize' ); ?><br /><button type="button" class="preview-icon button iconized-hover-trigger"><span class="iconized"></span></button>
			</label>
			<span>
				<input type="hidden" id="icon_name" name="icon_name" class="iconize-input-name" value="">
				<input type="hidden" id="icon_set" name="icon_set" class="iconize-input-set" value="">
				<input type="hidden" id="icon_transform" name="icon_transform" class="iconize-input-transform" value="">
				<input type="hidden" id="icon_color" name="icon_color" class="iconize-input-color" value="">
				<input type="hidden" id="icon_size" name="icon_size" class="iconize-input-size" value="">
				<input type="hidden" id="icon_align" name="icon_align" class="iconize-input-align" value="">
				<input type="hidden" id="icon_custom_classes" name="icon_custom_classes" class="iconize-input-custom-classes" value="">
			</span>
		</div>
		<?php
	}

	/**
	 * Add field to "edit taxonomy term" form
	 *
	 * @since    1.0.0
	 *
	 * @param object $tag Description.
	 * @param string $taxonomy Description.
	 */
	public function iconize_taxonomy_edit_form_fields( $tag, $taxonomy ) {

		// clear values.
		$name      = '';
		$set       = '';
		$transform = '';
		$color     = '';
		$size      = '';
		$align     = '';
		$custom    = '';

		// tag id.
		$id = $tag->term_id;

		$opt_array = get_option( 'iconize_taxonomy_icons' );

		if ( $opt_array && array_key_exists( $taxonomy, $opt_array ) ) {

			if ( array_key_exists( $id, $opt_array[ $taxonomy ] ) ) {

				$name      = $opt_array[ $taxonomy ][ $id ]['icon_name'];
				$set       = $opt_array[ $taxonomy ][ $id ]['icon_set'];
				$transform = $opt_array[ $taxonomy ][ $id ]['icon_transform'];
				$color     = $opt_array[ $taxonomy ][ $id ]['icon_color'];
				$size      = $opt_array[ $taxonomy ][ $id ]['icon_size'];
				$align     = $opt_array[ $taxonomy ][ $id ]['icon_align'];
				$custom    = $opt_array[ $taxonomy ][ $id ]['icon_custom_classes'];
			}
		}
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="taxonomy-icon-button"><?php esc_html_e( 'Icon: ', 'iconize' ); ?></label></th>
			<td>
				<label class="preview-icon-label">
					<button type="button" id="taxonomy-icon-button" name="taxonomy-icon-button" class="preview-icon button iconized-hover-trigger"><span class="iconized <?php echo esc_attr( $name ); ?> <?php echo esc_attr( $set ); ?> <?php echo esc_attr( $transform ); ?>"></span></button>
				</label>
				<span>
					<input type="hidden" id="icon_name" name="icon_name" class="iconize-input-name" value="<?php echo esc_attr( $name ); ?>">
					<input type="hidden" id="icon_set" name="icon_set" class="iconize-input-set" value="<?php echo esc_attr( $set ); ?>">
					<input type="hidden" id="icon_transform" name="icon_transform" class="iconize-input-transform" value="<?php echo esc_attr( $transform ); ?>">
					<input type="hidden" id="icon_color" name="icon_color" class="iconize-input-color" value="<?php echo esc_attr( $color ); ?>">
					<input type="hidden" id="icon_size" name="icon_size" class="iconize-input-size" value="<?php echo esc_attr( $size ); ?>">
					<input type="hidden" id="icon_align" name="icon_align" class="iconize-input-align" value="<?php echo esc_attr( $align ); ?>">
					<input type="hidden" id="icon_custom_classes" name="icon_custom_classes" class="iconize-input-custom-classes" value="<?php echo esc_attr( $custom ); ?>">
				<span>
			</td>
		</tr>
		<?php
	}

	/**
	 * Insert or update taxonomy term
	 *
	 * @since    1.0.0
	 *
	 * @param string $term_id Description.
	 * @param string $tt_id Description.
	 * @param string $taxonomy Description.
	 */
	public function iconize_create_update_taxonomy_icon( $term_id, $tt_id, $taxonomy ) {

		$opt_array = get_option( 'iconize_taxonomy_icons' );

		if ( isset( $_POST['icon_name'] ) ) { // @codingStandardsIgnoreLine.
			$opt_array[ $taxonomy ][ $term_id ]['icon_name'] = sanitize_text_field( wp_unslash( $_POST['icon_name'] ) ); // @codingStandardsIgnoreLine.
		}

		if ( isset( $_POST['icon_set'] ) ) { // @codingStandardsIgnoreLine.
			$opt_array[ $taxonomy ][ $term_id ]['icon_set'] = sanitize_text_field( wp_unslash( $_POST['icon_set'] ) ); // @codingStandardsIgnoreLine.
		}

		if ( isset( $_POST['icon_transform'] ) ) { // @codingStandardsIgnoreLine.
			$opt_array[ $taxonomy ][ $term_id ]['icon_transform'] = sanitize_text_field( wp_unslash( $_POST['icon_transform'] ) ); // @codingStandardsIgnoreLine.
		}

		if ( isset( $_POST['icon_color'] ) ) { // @codingStandardsIgnoreLine.
			$opt_array[ $taxonomy ][ $term_id ]['icon_color'] = sanitize_text_field( wp_unslash( $_POST['icon_color'] ) ); // @codingStandardsIgnoreLine.
		}

		if ( isset( $_POST['icon_size'] ) ) { // @codingStandardsIgnoreLine.
			$opt_array[ $taxonomy ][ $term_id ]['icon_size'] = sanitize_text_field( wp_unslash( $_POST['icon_size'] ) ); // @codingStandardsIgnoreLine.
		}

		if ( isset( $_POST['icon_align'] ) ) { // @codingStandardsIgnoreLine.
			$opt_array[ $taxonomy ][ $term_id ]['icon_align'] = sanitize_text_field( wp_unslash( $_POST['icon_align'] ) ); // @codingStandardsIgnoreLine.
		}

		if ( isset( $_POST['icon_custom_classes'] ) ) { // @codingStandardsIgnoreLine.
			$opt_array[ $taxonomy ][ $term_id ]['icon_custom_classes'] = sanitize_text_field( wp_unslash( $_POST['icon_custom_classes'] ) ); // @codingStandardsIgnoreLine.
		}

		if ( isset( $opt_array ) ) {
			update_option( 'iconize_taxonomy_icons', $opt_array );
		}
	}

	/**
	 * Delete taxonomy term
	 *
	 * @since    1.0.0
	 *
	 * @param string $term_id Description.
	 * @param string $tt_id Description.
	 * @param string $taxonomy Description.
	 */
	public function iconize_delete_taxonomy_icon( $term_id, $tt_id, $taxonomy ) {

		$opt_array = get_option( 'iconize_taxonomy_icons' );
		if ( $opt_array && isset( $opt_array[ $taxonomy ][ $term_id ] ) ) {

			unset( $opt_array[ $taxonomy ][ $term_id ] );
			update_option( 'iconize_taxonomy_icons', $opt_array );
		}
	}

	/**
	 * Add "Icon" column to term table
	 *
	 * @since    1.0.0
	 *
	 * @param array $columns Description.
	 * @return array $columns Description.
	 */
	public function iconize_term_columns_head( $columns ) {

		$columns['term_icon'] = 'Icon';

		return $columns;
	}

	/**
	 * Add term icon to our column content
	 *
	 * @since    1.0.0
	 *
	 * @param string $deprecated Description.
	 * @param string $column_name Description.
	 * @param string $term_id Description.
	 */
	public function iconize_term_column_content( $deprecated, $column_name, $term_id ) {

		if ( 'term_icon' === $column_name ) {

			if ( isset( $_POST['icon_name'] ) ) { // @codingStandardsIgnoreLine.

				$icon_args['icon_name']           = sanitize_text_field( wp_unslash( $_POST['icon_name'] ) ); // @codingStandardsIgnoreLine.
				$icon_args['icon_set']            = sanitize_text_field( wp_unslash( $_POST['icon_set'] ) ); // @codingStandardsIgnoreLine.
				$icon_args['icon_transform']      = sanitize_text_field( wp_unslash( $_POST['icon_transform'] ) ); // @codingStandardsIgnoreLine.
				$icon_args['icon_color']          = sanitize_text_field( wp_unslash( $_POST['icon_color'] ) ); // @codingStandardsIgnoreLine.
				$icon_args['icon_size']           = sanitize_text_field( wp_unslash( $_POST['icon_size'] ) ); // @codingStandardsIgnoreLine.
				$icon_args['icon_align']          = sanitize_text_field( wp_unslash( $_POST['icon_align'] ) ); // @codingStandardsIgnoreLine.
				$icon_args['icon_custom_classes'] = sanitize_text_field( wp_unslash( $_POST['icon_custom_classes'] ) ); // @codingStandardsIgnoreLine.

				$icon = iconize_get_icon( $icon_args, 'term_column_icon', '' );

			} else {

				$screen   = get_current_screen();
				$taxonomy = $screen->taxonomy;

				$icon = iconize_get_term_icon_by( 'id', $term_id, $taxonomy, 'html', '' );
			}

			return $icon;
		}
	}

	/**
	 * List categories/taxonomy terms with icons
	 *
	 * Attached to "wp_list_categories" filter, extends default wp_list_categories() function.
	 *
	 * @since    1.0.0
	 *
	 * @uses Iconize_WP::walk_iconize_category_tree()
	 * @uses Iconize_WP::get_iconize_support_for()
	 *
	 * @param array $output HTML output.
	 * @param array $args Arguments.
	 * @return array $output
	 */
	public function iconize_list_taxonomies( $output, $args ) {

		$defaults = array(
			'child_of'            => 0,
			'current_category'    => 0,
			'depth'               => 0,
			'echo'                => 1,
			'exclude'             => '',
			'exclude_tree'        => '',
			'feed'                => '',
			'feed_image'          => '',
			'feed_type'           => '',
			'hide_empty'          => 1,
			'hide_title_if_empty' => false,
			'hierarchical'        => true,
			'order'               => 'ASC',
			'orderby'             => 'name',
			'separator'           => '<br />',
			'show_count'          => 0,
			'show_option_all'     => '',
			'show_option_none'    => __( 'No categories' ),
			'style'               => 'list',
			'taxonomy'            => 'category',
			'title_li'            => __( 'Categories' ),
			'use_desc_for_title'  => 1,
		);

		// Insert "iconized" arg to defaults.
		if ( ! isset( $defaults['iconized'] ) ) {

			/**
			 * Let users decide wheather to display iconized cat list or default one on each wp_list_categories() usage.
			 * Iconized cat list is disabled by default.
			 */
			$iconized_defaults = array();

			/**
			 * Apply "iconize_tag_cloud_defaults" filter to allow users to change default value of "iconized" parameter.
			 */
			$defaults['iconized'] = apply_filters( 'iconized_list_categories_defaults', $iconized_defaults, $args );
		}

		$r = wp_parse_args( $args, $defaults );

		// Check if iconize is enabled on this taxonomy.
		$tax_support       = $this->get_iconize_support_for( 'taxonomy_' . $r['taxonomy'] );
		$tax_icons_enabled = $tax_support['enabled'];

		// Return output if no iconized arg or iconize is disabled.
		if ( false === (bool) $r['iconized'] || ! $tax_icons_enabled ) {
			return $output;
		}

		if ( ! isset( $r['pad_counts'] ) && $r['show_count'] && $r['hierarchical'] ) {
			$r['pad_counts'] = true;
		}

		// Descendants of exclusions should be excluded too.
		if ( true === (int) $r['hierarchical'] ) {
			$exclude_tree = array();

			if ( $r['exclude_tree'] ) {
				$exclude_tree = array_merge( $exclude_tree, wp_parse_id_list( $r['exclude_tree'] ) );
			}

			if ( $r['exclude'] ) {
				$exclude_tree = array_merge( $exclude_tree, wp_parse_id_list( $r['exclude'] ) );
			}

			$r['exclude_tree'] = $exclude_tree;
			$r['exclude']      = '';
		}

		if ( ! isset( $r['class'] ) ) {
			$r['class'] = ( 'category' === $r['taxonomy'] ) ? 'categories' : $r['taxonomy'];
		}

		if ( ! taxonomy_exists( $r['taxonomy'] ) ) {
			return false;
		}

		$show_option_all  = $r['show_option_all'];
		$show_option_none = $r['show_option_none'];

		$categories = get_categories( $r );

		$output = '';
		if ( $r['title_li'] && 'list' === $r['style'] && ( ! empty( $categories ) || ! $r['hide_title_if_empty'] ) ) {
			$output = '<li class="' . esc_attr( $r['class'] ) . '">' . $r['title_li'] . '<ul>';
		}
		if ( empty( $categories ) ) {
			if ( ! empty( $show_option_none ) ) {
				if ( 'list' === $r['style'] ) {
					$output .= '<li class="cat-item-none">' . $show_option_none . '</li>';
				} else {
					$output .= $show_option_none;
				}
			}
		} else {
			if ( ! empty( $show_option_all ) ) {

				$posts_page = '';

				// For taxonomies that belong only to custom post types, point to a valid archive.
				$taxonomy_object = get_taxonomy( $r['taxonomy'] );
				if ( ! in_array( 'post', $taxonomy_object->object_type, true ) && ! in_array( 'page', $taxonomy_object->object_type, true ) ) {
					foreach ( $taxonomy_object->object_type as $object_type ) {
						$_object_type = get_post_type_object( $object_type );

						// Grab the first one.
						if ( ! empty( $_object_type->has_archive ) ) {
							$posts_page = get_post_type_archive_link( $object_type );
							break;
						}
					}
				}

				// Fallback for the 'All' link is the posts page.
				if ( ! $posts_page ) {
					if ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_for_posts' ) ) {
						$posts_page = get_permalink( get_option( 'page_for_posts' ) );
					} else {
						$posts_page = home_url( '/' );
					}
				}

				$posts_page = esc_url( $posts_page );
				if ( 'list' === $r['style'] ) {
					$output .= "<li class='cat-item-all'><a href='$posts_page'>$show_option_all</a></li>";
				} else {
					$output .= "<a href='$posts_page'>$show_option_all</a>";
				}
			}

			if ( empty( $r['current_category'] ) && ( is_category() || is_tax() || is_tag() ) ) {
				$current_term_object = get_queried_object();
				if ( $current_term_object && $r['taxonomy'] === $current_term_object->taxonomy ) {
					$r['current_category'] = get_queried_object_id();
				}
			}

			if ( $r['hierarchical'] ) {
				$depth = $r['depth'];
			} else {
				$depth = -1; // Flat.
			}

			// Call our helper function instead of "walk_category_tree".
			$output .= $this->walk_iconize_category_tree( $categories, $depth, $r );
		}

		if ( $r['title_li'] && 'list' === $r['style'] && ( ! empty( $categories ) || ! $r['hide_title_if_empty'] ) ) {
			$output .= '</ul></li>';
		}

		// Apply "iconize_list_categories" filter.
		$html = apply_filters( 'iconize_list_categories', $output, $args );

		if ( $r['echo'] ) {
			echo wp_kses_post( $html );
		} else {
			return $html;
		}
	}

	/**
	 * Helper function to call our custom "Iconize_Walker_Category" walker class
	 *
	 * Modified default "walk_category_tree" function...
	 *
	 * @since    1.0.0
	 */
	public function walk_iconize_category_tree() {

		$args = func_get_args();

		// the user's options are the third parameter.
		if ( empty( $args[2]['walker'] ) || ! is_a( $args[2]['walker'], 'Walker' ) ) {

			$walker = new Iconize_Walker_Category();

		} else {

			$walker = $args[2]['walker'];
		}

		return call_user_func_array( array( &$walker, 'walk' ), $args );
	}

	/**
	 * Generate iconized tag cloud
	 *
	 * Attached to "wp_generate_tag_cloud" filter, extends default wp_generate_tag_cloud() function.
	 *
	 * @since 1.0.0
	 *
	 * @uses Iconize_WP::get_iconize_support_for()
	 * @uses Iconize_WP::iconize_get_term_icon_by()
	 * @uses Iconize_WP::iconize_get_icon()
	 *
	 * @param string|array $return Tag cloud as a string or an array, depending on 'format' argument.
	 * @param WP_Term[]    $tags Array of WP_Term objects to generate the tag cloud for.
	 * @param string|array $args Optional. Array or string of arguments for generating a tag cloud.
	 */
	public function iconize_wp_generate_tag_cloud( $return, $tags, $args ) {

		// Modify default functionality.
		$defaults = array(
			'smallest'                   => 8,
			'largest'                    => 22,
			'unit'                       => 'pt',
			'number'                     => 0,
			'format'                     => 'flat',
			'separator'                  => "\n",
			'orderby'                    => 'name',
			'order'                      => 'ASC',
			'topic_count_text'           => null,
			'topic_count_text_callback'  => null,
			'topic_count_scale_callback' => 'default_topic_count_scale',
			'filter'                     => 1,
			'show_count'                 => 0,
		);

		// Insert "iconized" arg to defaults.
		if ( ! isset( $defaults['iconized'] ) ) {

			/**
			 * Let users decide wheather to display iconized tag cloud or default one on each wp_tag_cloud() usage.
			 * Iconized tag cloud disabled by default
			 */
			$iconized_defaults = array();

			// If in admin area, display icons - better for end user.
			if ( is_admin() ) {

				$iconized_defaults = array(
					'hover_effect'         => 'default',
					'color'                => 'default',
					'hover_effect_trigger' => 'link',
					'hover_color_change'   => false,
					'fallback_icon'        => array(),
					'override_icons'       => false,
					'style'                => 'default',
					'after_icon'           => '&nbsp;',
				);
			}

			/**
			 * Apply "iconize_tag_cloud_defaults" filter to allow users to change default value of "iconized" parameter.
			 */
			$defaults['iconized'] = apply_filters( 'iconized_tag_cloud_defaults', $iconized_defaults, $args );
		}

		$args = wp_parse_args( $args, $defaults );

		// Check if iconize is enabled on this taxonomy.
		$tax_icons_enabled = false;
		if ( isset( $args['taxonomy'] ) ) {

			$tax_support       = $this->get_iconize_support_for( 'taxonomy_' . $args['taxonomy'] );
			$tax_icons_enabled = $tax_support['enabled'];
		}

		// Return output if no iconized arg or iconize is disabled.
		if ( false === (bool) $args['iconized'] || ! $tax_icons_enabled ) {
			return $return;
		}

		// Continue with default logic.
		$return = ( 'array' === $args['format'] ) ? array() : '';

		if ( empty( $tags ) ) {
			return $return;
		}

		// Juggle topic counts.
		if ( isset( $args['topic_count_text'] ) ) {
			// First look for nooped plural support via topic_count_text.
			$translate_nooped_plural = $args['topic_count_text'];
		} elseif ( ! empty( $args['topic_count_text_callback'] ) ) {
			// Look for the alternative callback style. Ignore the previous default.
			if ( $args['topic_count_text_callback'] === 'default_topic_count_text' ) {// phpcs:ignore
				$translate_nooped_plural = _n_noop( '%s item', '%s items' );// phpcs:ignore
			} else {
				$translate_nooped_plural = false;
			}
		} elseif ( isset( $args['single_text'] ) && isset( $args['multiple_text'] ) ) {
			// If no callback exists, look for the old-style single_text and multiple_text arguments.
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle,WordPress.WP.I18n.NonSingularStringLiteralPlural
			$translate_nooped_plural = _n_noop( $args['single_text'], $args['multiple_text'] );
		} else {
			// This is the default for when no callback, plural, or argument is passed in.
			$translate_nooped_plural = _n_noop( '%s item', '%s items' );// phpcs:ignore
		}

		/**
		 * Filters how the items in a tag cloud are sorted.
		 *
		 * @since 2.8.0
		 *
		 * @param WP_Term[] $tags Ordered array of terms.
		 * @param array     $args An array of tag cloud arguments.
		 */
		$tags_sorted = apply_filters( 'tag_cloud_sort', $tags, $args );
		if ( empty( $tags_sorted ) ) {
			return $return;
		}

		if ( $tags_sorted !== $tags ) {
			$tags = $tags_sorted;
			unset( $tags_sorted );
		} else {
			if ( 'RAND' === $args['order'] ) {
				shuffle( $tags );
			} else {
				// SQL cannot save you; this is a second (potentially different) sort on a subset of data.
				if ( 'name' === $args['orderby'] ) {
					uasort( $tags, '_wp_object_name_sort_cb' );
				} else {
					uasort( $tags, '_wp_object_count_sort_cb' );
				}

				if ( 'DESC' === $args['order'] ) {
					$tags = array_reverse( $tags, true );
				}
			}
		}

		if ( $args['number'] > 0 ) {
			$tags = array_slice( $tags, 0, $args['number'] );
		}

		$counts      = array();
		$real_counts = array(); // For the alt tag.
		foreach ( (array) $tags as $key => $tag ) {
			$real_counts[ $key ] = $tag->count;
			$counts[ $key ]      = call_user_func( $args['topic_count_scale_callback'], $tag->count );
		}

		$min_count = min( $counts );
		$spread    = max( $counts ) - $min_count;
		if ( $spread <= 0 ) {
			$spread = 1;
		}
		$font_spread = $args['largest'] - $args['smallest'];
		if ( $font_spread < 0 ) {
			$font_spread = 1;
		}
		$font_step = $font_spread / $spread;

		$aria_label = false;

		/*
		 * Determine whether to output an 'aria-label' attribute with the tag name and count.
		 * When tags have a different font size, they visually convey an important information
		 * that should be available to assistive technologies too. On the other hand, sometimes
		 * themes set up the Tag Cloud to display all tags with the same font size (setting
		 * the 'smallest' and 'largest' arguments to the same value).
		 * In order to always serve the same content to all users, the 'aria-label' gets printed out:
		 * - when tags have a different size
		 * - when the tag count is displayed (for example when users check the checkbox in the
		 *   Tag Cloud widget), regardless of the tags font size
		 */
		if ( $args['show_count'] || 0 !== $font_spread ) {
			$aria_label = true;
		}

		// Assemble the data that will be used to generate the tag cloud markup.
		$tags_data = array();
		foreach ( $tags as $key => $tag ) {
			$tag_id = isset( $tag->id ) ? $tag->id : $key;

			$count      = $counts[ $key ];
			$real_count = $real_counts[ $key ];

			if ( $translate_nooped_plural ) {
				$formatted_count = sprintf( translate_nooped_plural( $translate_nooped_plural, $real_count ), number_format_i18n( $real_count ) );
			} else {
				$formatted_count = call_user_func( $args['topic_count_text_callback'], $real_count, $tag, $args );
			}

			$tags_data[] = array(
				'id'              => $tag_id,
				'url'             => '#' !== $tag->link ? $tag->link : '#',
				'role'            => '#' !== $tag->link ? '' : ' role="button"',
				'name'            => $tag->name,
				'formatted_count' => $formatted_count,
				'slug'            => $tag->slug,
				'real_count'      => $real_count,
				'class'           => 'tag-cloud-link tag-link-' . $tag_id,
				'font_size'       => $args['smallest'] + ( $count - $min_count ) * $font_step,
				'aria_label'      => $aria_label ? sprintf( ' aria-label="%1$s (%2$s)"', esc_attr( $tag->name ), esc_attr( $formatted_count ) ) : '',
				'show_count'      => $args['show_count'] ? '<span class="tag-link-count"> (' . $real_count . ')</span>' : '',
			);
		}

		/**
		 * Filters the data used to generate the tag cloud.
		 *
		 * @since 4.3.0
		 *
		 * @param array $tags_data An array of term data for term used to generate the tag cloud.
		 */
		$tags_data = apply_filters( 'wp_generate_tag_cloud_data', $tags_data );

		// Validate custom settings passed with 'iconize' arg to wp_tag_cloud().
		$hover_effect         = ( isset( $args['iconized']['hover_effect'] ) ) ? (string) $args['iconized']['hover_effect'] : 'default';
		$color                = ( isset( $args['iconized']['color'] ) ) ? (string) $args['iconized']['color'] : 'default';
		$hover_effect_trigger = ( isset( $args['iconized']['hover_effect_trigger'] ) ) ? (string) $args['iconized']['hover_effect_trigger'] : 'link';
		$hover_color_change   = ( isset( $args['iconized']['hover_color_change'] ) ) ? (bool) $args['iconized']['hover_color_change'] : false;
		$fallback_icon_args   = ( isset( $args['iconized']['fallback_icon'] ) ) ? (array) $args['iconized']['fallback_icon'] : array();
		$override_icons       = ( isset( $args['iconized']['override_icons'] ) ) ? (bool) $args['iconized']['override_icons'] : false;
		$style                = ( isset( $args['iconized']['style'] ) ) ? (string) $args['iconized']['style'] : 'default';
		$after_icon           = ( isset( $args['iconized']['after_icon'] ) ) ? (string) $args['iconized']['after_icon'] : '&nbsp;';

		$a = array();

		foreach ( $tags_data as $key => $tag_data ) {

			// Retrive an array of settings for term icon configured in term edit screen if there is an icon.
			$icon = iconize_get_term_icon_by( 'name', $tag_data['name'], $args['taxonomy'] );

			$term_icon_args = array();
			if ( ! empty( $icon ) ) {
				$term_icon_args = iconize_get_term_icon_by( 'name', $tag_data['name'], $args['taxonomy'], 'array' );
			}

			// Determine which icon to display.
			if ( true === $override_icons ) {

				$icon_args = $fallback_icon_args;

			} else {

				$icon_args = $term_icon_args;
				if ( empty( $icon_args ) && ! empty( $fallback_icon_args ) ) {
					$icon_args = $fallback_icon_args;
				}
			}

			// Modify icon args if needed.
			if ( ! empty( $icon_args ) ) {

				if ( true === $hover_color_change && false === strpos( $icon_args['icon_custom_classes'], 'hover-color-change' ) ) {
					$icon_args['icon_custom_classes'] .= ( ! empty( $icon_args['icon_custom_classes'] ) ) ? ',hover-color-change' : 'hover-color-change';
				}

				// Override effect and color if needed.
				if ( 'default' !== $hover_effect ) {
					$icon_args['icon_transform'] = $hover_effect;
				}

				if ( 'default' !== $color ) {
					$icon_args['icon_color'] = $color;
				}
			}

			// Generate icon html.
			$icon_html = iconize_get_icon( $icon_args, $args['taxonomy'], $after_icon );

			// Add hover effect class to link if needed.
			$het_link = '';
			if ( 'link' === $hover_effect_trigger && ! empty( $icon_html ) && ! empty( $icon_args['icon_transform'] ) && ! empty( $hover_effect ) ) {
				$het_link = ' iconized-hover-trigger';
			}

			// Generate link.
			$class = $style . '-style iconized-tag-link' . $het_link . ' ' . $tag_data['class'] . ' tag-link-position-' . ( $key + 1 ); // Added few classes.
			$a[]   = sprintf(
				'<a href="%1$s"%2$s class="%3$s" style="font-size: %4$s;"%5$s>%6$s%7$s%8$s</a>', // Added icon here.
				esc_url( $tag_data['url'] ),
				$tag_data['role'],
				esc_attr( $class ),
				esc_attr( str_replace( ',', '.', $tag_data['font_size'] ) . $args['unit'] ),
				$tag_data['aria_label'],
				wp_kses_post( $icon_html ),
				esc_html( $tag_data['name'] ),
				$tag_data['show_count']
			);
		}

		switch ( $args['format'] ) {
			case 'array':
				$return =& $a;
				break;
			case 'list':
				/*
				 * Force role="list", as some browsers (sic: Safari 10) don't expose to assistive
				 * technologies the default role when the list is styled with `list-style: none`.
				 * Note: this is redundant but doesn't harm.
				 */
				$return  = "<ul class='wp-tag-cloud' role='list'>\n\t<li>";
				$return .= join( "</li>\n\t<li>", $a );
				$return .= "</li>\n</ul>\n";
				break;
			default:
				$return = join( $args['separator'], $a );
				break;
		}

		return $return;
	}

	/**
	 * Add our fonts to Elementor.
	 *
	 * @since 1.0.2
	 *
	 * @param array $tabs Array of fonts.
	 */
	public function elementor_icon_tabs( $tabs = array() ) {

		$styles_array = $this->get_iconize_fonts_styles();

		if ( empty( $styles_array ) ) {
			return $tabs;
		}

		$icons_array = $this->get_icons_array();

		$new_icons = array();

		foreach ( $styles_array as $handle => $data ) {

			if ( ! isset( $data['icons'] ) || empty( $data['icons'] ) ) {
				continue;
			}

			if ( is_array( $data ) && array_key_exists( 'url', $data ) && ! empty( $data['url'] ) ) {

				foreach ( $data['icons'] as $font => $icons_js_url ) {
					$new_icons[ 'iconize-' . $font ] = array(
						'name'          => 'iconize-' . $font,
						'label'         => 'Iconize ' . ucwords( str_replace( array( '-', '_' ), array( ' ', ' ' ), $font ) ),
						'url'           => '',
						'enqueue'       => '',
						'prefix'        => 'glyph-',
						'displayPrefix' => 'iconized ' . $font,
						'labelIcon'     => ( isset( $icons_array[ $font ][0] ) ? 'iconized ' . $font . ' glyph-' . $icons_array[ $font ][0] : '' ),
						'ver'           => $this->version,
						'fetchJson'     => $icons_js_url,
					);
				}
			}
		}

		return array_merge( $tabs, $new_icons );
	}

	/**
	 * Add our fonts to Beaver Builder.
	 *
	 * @since 1.0.2
	 *
	 * @param array $sets Array of fonts.
	 */
	public function beaver_builder_fonts( $sets ) {

		$styles_array = $this->get_iconize_fonts_styles();

		if ( empty( $styles_array ) ) {
			return $sets;
		}

		$icons_array = $this->get_icons_array();

		$new_icons = array();

		foreach ( $styles_array as $handle => $data ) {

			if ( ! isset( $data['icons'] ) || empty( $data['icons'] ) ) {
				continue;
			}

			if ( is_array( $data ) && array_key_exists( 'url', $data ) && ! empty( $data['url'] ) ) {

				foreach ( $data['icons'] as $font => $icons_js_url ) {
					$new_icons[ 'iconize-' . $font ] = array(
						'name'       => 'Iconize ' . ucwords( str_replace( array( '-', '_' ), array( ' ', ' ' ), $font ) ),
						'prefix'     => 'iconized ' . $font,
						'type'       => 'iconize',
						'path'       => $data['path'],
						'url'        => '',
						'stylesheet' => $data['url'],
						'icons'		 => preg_filter( '/^/', 'glyph-', $icons_array[ $font ] ),
					);
				}
			}
		}

		return array_merge( $sets, $new_icons );
	}

	/**
	 * Add our fonts to Site Origin Page Builder.
	 *
	 * @since 1.2.0
	 *
	 * @param array $sets Array of fonts.
	 */
	public function so_builder_fonts( $sets ) {

		$styles_array = $this->get_iconize_fonts_styles();

		if ( empty( $styles_array ) ) {
			return $sets;
		}

		$new_icons = array();

		foreach ( $styles_array as $handle => $data ) {

			if ( ! isset( $data['siteorigin'] ) || empty( $data['siteorigin'] ) ) {
				continue;
			}

			if ( is_array( $data['siteorigin'] ) ) {

				foreach ( $data['siteorigin'] as $font => $data1 ) {

					if ( file_exists( $data1[ 'icons' ] ) ) {
						$new_icons[ 'iconize' . str_replace( array( '-', '_' ), array( '', '' ), $font ) ] = array(
							'name'      => 'Iconize ' . ucwords( str_replace( array( '-', '_' ), array( ' ', ' ' ), $font ) ),
							'style_uri' => $data1['url'],
							'icons'     => json_decode( file_get_contents( $data1[ 'icons' ] ), true ),
						);
					}
				}
			}
		}

		return array_merge( $sets, $new_icons );
	}

	/**
	 * CMB2 field.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $field        The passed in `CMB2_Field` object.
	 * @param mixed  $value        The value of this field escaped.
	 * @param int    $object_id    The ID of the current object.
	 * @param string $object_type  The type of object you are working with.
	 * @param object $field_type   This `CMB2_Types` object.
	 */
	public function render_iconize_field( $field, $value, $object_id, $object_type, $field_type ) {

		$this->enqueue_admin_dialog_scripts();

		$this->cmb_field_available = true;

		$value = wp_parse_args(
			$value,
			array(
				'icon_name'           => '',
				'icon_set'            => '',
				'icon_transform'      => '',
				'icon_color'          => '',
				'icon_size'           => '',
				'icon_align'          => '',
				'icon_custom_classes' => '',
			)
		);
		?>
		<div>
			<label class="preview-icon-label">
				<button type="button" id="cmb2-icon-button" name="cmb2-icon-button" class="preview-icon button iconized-hover-trigger"><span class="iconized <?php echo esc_attr( $value['icon_name'] ); ?> <?php echo esc_attr( $value['icon_set'] ); ?> <?php echo esc_attr( $value['icon_transform'] ); ?>"></span></button>
			</label>
			<span>
				<?php
				foreach ( $value as $key => $val ) {
					echo $field_type->input(// phpcs:ignore
						array(
							'name'  => $field_type->_name( '[' . $key . ']' ),
							'id'    => $field_type->_id( '_' . $key ),
							'value' => $val,
							'type'  => 'hidden',
							'desc'  => '',
							'class' => 'cmb2-hidden iconize-input-' . str_replace( array( 'icon_', '_' ), array( '', '-' ), $key ),
						)
					);
				}
				?>
			<span>
		</div>
		<br class="clear">
		<?php
		$field_type->_desc( true, true );
	}

	/**
	 * Helper functions
	 */

	/**
	 * Function to check if options page is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean $show_options_screen
	 */
	public function show_iconize_options() {

		/**
		 * Filter to enable/disable options screen.
		 * Function attached to the 'show_iconize_options_screen' filter must return boolean value true/false.
		 */
		return (bool) apply_filters( 'show_iconize_options_screen', true );
	}

	/**
	 * Function to check if iconize plugin is enabled on specific WP system and whether to show settings for that sistem on plugins options page or not.
	 *
	 * @since 1.0.0
	 *
	 * @uses Iconize_WP::show_iconize_options()
	 *
	 * @param string $system - widgets, nav_menus, editor, taxonomy_(taxonomy name).
	 * @param string $tab - iconize_fonts, iconize_integrations.
	 * @return array
	 */
	public function get_iconize_support_for( $system, $tab = 'iconize_integrations' ) {

		if ( empty( $system ) || ! is_string( $system ) ) {
			return array(
				'enabled'         => true,
				'show_in_options' => true,
			);
		}

		/**
		 * Filter to enable/disable iconize support for widgets system/nav menus system/visual editor etc.
		 * If being used specific option will not show on settings screen.
		 *
		 * Example: add_filter( 'iconize_integrations/widgets/enabled', '__return_true' );
		 */
		$enabled_for_system = boolval( apply_filters( "{$tab}/{$system}/enabled", true ) );

		$options = get_option( $tab, array() );

		return array(
			'enabled'         => ( has_filter( "{$tab}/{$system}/enabled" ) ? $enabled_for_system : isset( $options[ $system ] ) ),
			'show_in_options' => ( ! has_filter( "{$tab}/{$system}/enabled" ) ),
		);
	}


	/**
	 * Function to retrive an array of additional ids of screens where iconize dialog is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return array $extra_screen_ids, empty array by default
	 */
	public function get_extra_iconize_dialog_support() {

		/**
		 * Since iconize dialog is added only on widgets and nav-menus admin screens ( if enabled ),
		 * 'add_iconize_dialog_to_screens' filter allows users to call the dialog on other admin pages if they need to.
		 *
		 * Note that the dialog is useless if you don't have the preview button and inputs to store settings on this pages.
		 *
		 * @see Iconize_WP::iconize_in_widget_form()
		 * @see Iconize_WP::iconize_nav_menu_item_custom_fields()
		 *
		 * Function attached to this filter must return an array of screen ids.
		 */
		$screen_ids       = apply_filters( 'add_iconize_dialog_to_screens', array() );
		$extra_screen_ids = is_array( $screen_ids ) ? $screen_ids : array();

		return $extra_screen_ids;
	}

	/**
	 * Function to get stylesheet file/s with icons defined.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_iconize_fonts_styles() {

		$default_styles = array();

		$dashicons_support    = $this->get_iconize_support_for( 'font_dashicons', 'iconize_fonts' );
		$font_awesome_support = $this->get_iconize_support_for( 'font_awesome', 'iconize_fonts' );
		$fa_solid_support     = $this->get_iconize_support_for( 'fa_solid', 'iconize_fonts' );
		$fa_regular_support   = $this->get_iconize_support_for( 'fa_regular', 'iconize_fonts' );
		$fa_brands_support    = $this->get_iconize_support_for( 'fa_brands', 'iconize_fonts' );
		$foundation_support   = $this->get_iconize_support_for( 'font_foundation', 'iconize_fonts' );
		$bootstrap_support    = $this->get_iconize_support_for( 'font_bootstrap', 'iconize_fonts' );
		$iconoir_support      = $this->get_iconize_support_for( 'font_iconoir', 'iconize_fonts' );

		if ( $dashicons_support['enabled'] ) {
			$default_styles['dashicons'] = array(
				'path'       => plugin_dir_path( __FILE__ ) . 'css/dashicons.min.css',
				'url'        => plugins_url( 'css/dashicons.min.css', __FILE__ ),
				'icons'      => array(
					'font-dashicons' => plugins_url( 'fonts/dashicons/icons.json', __FILE__ ),
				),
				'siteorigin' => array(
					'font-dashicons' => array(
						'url'   => plugins_url( 'css/so/dashicons.min.css', __FILE__ ),
						'icons' => plugins_url( 'fonts/dashicons/so.json', __FILE__ ),
					),
				),
			);
		}

		if ( $font_awesome_support['enabled'] ) {
			$default_styles['default'] = array(
				'path'       => plugin_dir_path( __FILE__ ) . 'css/fa-4-7.min.css',
				'url'        => plugins_url( 'css/fa-4-7.min.css', __FILE__ ),
				'icons'      => array(
					'font-awesome' => plugins_url( 'fonts/fontawesome/icons.json', __FILE__ ),
				),
				'siteorigin' => array(
					'font-awesome' => array(
						'url'   => plugins_url( 'css/so/fa-4-7.min.css', __FILE__ ),
						'icons' => plugins_url( 'fonts/fontawesome/so.json', __FILE__ ),
					),
				),
			);
		}

		if ( $fa_solid_support['enabled'] ) {
			$default_styles['fas'] = array(
				'path'       => plugin_dir_path( __FILE__ ) . 'css/fa-solid.min.css',
				'url'        => plugins_url( 'css/fa-solid.min.css', __FILE__ ),
				'icons'      => array(
					'font-awesome-solid' => plugins_url( 'fonts/fontawesome-5-free/icons-solid.json', __FILE__ ),
				),
				'siteorigin' => array(
					'font-awesome-solid' => array(
						'url'   => plugins_url( 'css/so/fa-solid.min.css', __FILE__ ),
						'icons' => plugins_url( 'fonts/fontawesome-5-free/so-solid.json', __FILE__ ),
					),
				),
			);
		}

		if ( $fa_regular_support['enabled'] ) {
			$default_styles['far'] = array(
				'path'       => plugin_dir_path( __FILE__ ) . 'css/fa-regular.min.css',
				'url'        => plugins_url( 'css/fa-regular.min.css', __FILE__ ),
				'icons'      => array(
					'font-awesome-regular' => plugins_url( 'fonts/fontawesome-5-free/icons-regular.json', __FILE__ ),
				),
				'siteorigin' => array(
					'font-awesome-regular' => array(
						'url'   => plugins_url( 'css/so/fa-regular.min.css', __FILE__ ),
						'icons' => plugins_url( 'fonts/fontawesome-5-free/so-regular.json', __FILE__ ),
					),
				),
			);
		}

		if ( $fa_brands_support['enabled'] ) {
			$default_styles['fab'] = array(
				'path'       => plugin_dir_path( __FILE__ ) . 'css/fa-brands.min.css',
				'url'        => plugins_url( 'css/fa-brands.min.css', __FILE__ ),
				'icons'      => array(
					'font-awesome-brands' => plugins_url( 'fonts/fontawesome-5-free/icons-brands.json', __FILE__ ),
				),
				'siteorigin' => array(
					'font-awesome-brands' => array(
						'url'   => plugins_url( 'css/so/fa-brands.min.css', __FILE__ ),
						'icons' => plugins_url( 'fonts/fontawesome-5-free/so-brands.json', __FILE__ ),
					),
				),
			);
		}

		if ( $foundation_support['enabled'] ) {
			$default_styles['foundation'] = array(
				'path'       => plugin_dir_path( __FILE__ ) . 'css/foundation.min.css',
				'url'        => plugins_url( 'css/foundation.min.css', __FILE__ ),
				'icons'      => array(
					'font-foundation' => plugins_url( 'fonts/foundation/icons.json', __FILE__ ),
				),
				'siteorigin' => array(
					'font-foundation' => array(
						'url'   => plugins_url( 'css/so/foundation.min.css', __FILE__ ),
						'icons' => plugins_url( 'fonts/foundation/so.json', __FILE__ ),
					),
				),
			);
		}

		if ( $bootstrap_support['enabled'] ) {
			$default_styles['bootstrap'] = array(
				'path'       => plugin_dir_path( __FILE__ ) . 'css/bootstrap.min.css',
				'url'        => plugins_url( 'css/bootstrap.min.css', __FILE__ ),
				'icons'      => array(
					'font-bootstrap' => plugins_url( 'fonts/bootstrap/icons.json', __FILE__ ),
				),
				'siteorigin' => array(
					'font-bootstrap' => array(
						'url'   => plugins_url( 'css/so/bootstrap.min.css', __FILE__ ),
						'icons' => plugins_url( 'fonts/bootstrap/so.json', __FILE__ ),
					),
				),
			);
		}

		if ( $iconoir_support['enabled'] ) {
			$default_styles['iconoir'] = array(
				'path'       => plugin_dir_path( __FILE__ ) . 'css/iconoir.min.css',
				'url'        => plugins_url( 'css/iconoir.min.css', __FILE__ ),
				'icons'      => array(
					'font-iconoir' => plugins_url( 'fonts/iconoir/icons.json', __FILE__ ),
				),
				'siteorigin' => array(
					'font-iconoir' => array(
						'url'   => plugins_url( 'css/so/iconoir.min.css', __FILE__ ),
						'icons' => plugins_url( 'fonts/iconoir/so.json', __FILE__ ),
					),
				),
			);
		}

		$uploaded_styles = array();
		$uploaded_fonts  = get_option( 'iconize_uploaded_fonts_data', array() );
		if ( ! empty( $uploaded_fonts ) ) {
			$upload = wp_upload_dir();

			$folder_name = apply_filters( 'iconize_uploads_folder', 'iconize_fonts' );
			$upload_dir  = $upload['basedir'] . '/' . $folder_name;
			$upload_url  = $upload['baseurl'] . '/' . $folder_name;

			// SSL fix because WordPress core function wp_upload_dir() doesn't check protocol.
			if ( is_ssl() ) {
				$upload_url = str_replace( 'http://', 'https://', $upload_url );
			}

			foreach ( $uploaded_fonts as $key => $data ) {
				$support = $this->get_iconize_support_for( 'uploaded_font_' . $key, 'iconize_custom_fonts' );
				if ( $support['enabled'] ) {
					$font_data = json_decode( $data['data'], true );

					$uploaded_styles[ $key ] = array(
						'path'       => $upload_dir . '/' . $font_data['file_name'] . '/' . $key . '.css',
						'url'        => $upload_url . '/' . $font_data['file_name'] . '/' . $key . '.css',
						'icons'      => array(
							'font-' . $key => $upload_url . '/' . $font_data['file_name'] . '/' . $key . '.json',
						),
						'siteorigin' => array(
							'font-' . $key => array(
								'url'   => $upload_url . '/' . $font_data['file_name'] . '/' . $key . '-so.css',
								'icons' => $upload_url . '/' . $font_data['file_name'] . '/' . $key . '-charmap.json',
							),
						),
					);
				}
			}
		}

		/**
		 * Add custom icons stylesheets.
		 * Note: Path and url to css file MUST be provided.
		 * Example array returned:
		 * array(
		 *     'custom1' => array ( 'path'=> 'path to file', 'url' => 'url of file'),
		 *     'custom2' => array ( 'path'=> 'path to file', 'url' => 'url of file'),
		 * )
		 */
		$custom_styles = array();
		$custom_fonts  = apply_filters( 'iconize_fonts_styles', array() );
		if ( ! empty( $custom_fonts ) && is_array( $custom_fonts ) ) {
			foreach ( $custom_fonts as $key => $data ) {
				$support = $this->get_iconize_support_for( 'custom_font_' . $key, 'iconize_custom_fonts' );
				if ( $support['enabled'] ) {
					$custom_styles[ $key ] = $data;
				}
			}
		}

		return (array) $default_styles + (array) $uploaded_styles + (array) $custom_styles;
	}

	/**
	 * Function to read stylesheet file/s and return an array of icon sets.
	 * 
	 * Generated array will be in format:
	 * array(
	 *     'font-1' => array( 'name-1', 'name-2', 'name-3',..., 'name-n' ),
	 *     'font-2' => array( 'name-1', 'name-2', 'name-3',..., 'name-n' ),
	 *     ...
	 *     'font-n' => array( 'name-1', 'name-2', 'name-3',..., 'name-n' )
	 * )
	 *
	 * @since 1.0.0
	 *
	 * @uses Iconize_WP::get_iconize_fonts_styles()
	 *
	 * @return array $icons_array
	 */
	public function get_icons_array() {

		// Get stylesheet file/s.
		$styles_array = $this->get_iconize_fonts_styles();

		$icons_array = array();

		foreach ( $styles_array as $key => $value ) {

			if ( ! is_array( $value ) ) {
				break;
			}

			$failed = true;
			if ( array_key_exists( 'icons', $value ) && ! empty( $value['icons'] ) ) {
				$failed = false;
				foreach ( $value['icons'] as $font => $json_path ) {
					if ( file_exists( $json_path ) ) {
						$array = json_decode( file_get_contents( $json_path  ), true );// phpcs:ignore
						$icons_array[ $font ] = $array['icons'];
					} else {
						$failed = true;
					}
				}
			}

			// Everything was fine.
			if ( ! $failed ) {
				continue;
			}

			// Try to load icons from css, as on some installations file_get_contents and SSL might conflict when reading json file.
			$subject = array_key_exists( 'path', $value ) && ! empty( $value['path'] ) && file_exists( $value['path'] ) ? file_get_contents( $value['path'] ) : false;// phpcs:ignore
			if ( false !== $subject ) {

				// Regex pattern ( see iconize.css ).
				$pattern = '/\.(.+)\.glyph-((?:\w+(?:-)?)+):+before\s*{\s*content:\s*.+;\s*}/';

				// Find matches.
				preg_match_all( $pattern, $subject, $matches, PREG_SET_ORDER );

				if ( $matches ) {
					foreach ( $matches as $match ) {
						$icons_array[ $match[1] ][] = $match[2];
					}
				}
			}
		}

		return $icons_array;
	}

	/**
	 * Generate inline styles for iconize dialog to style it based on selected admin color scheme.
	 *
	 * @since 1.0.0
	 *
	 * @return string $inline_styles
	 */
	public function get_iconize_dialog_inline_styles() {

		if ( ! is_admin() ) {

			return '';
		}

		global $_wp_admin_css_colors;

		$user_admin_color = get_user_option( 'admin_color' );

		if ( empty( $user_admin_color ) || ! isset( $_wp_admin_css_colors[ $user_admin_color ] ) || empty( $_wp_admin_css_colors[ $user_admin_color ] ) ) {

			$user_admin_color = 'fresh';
		}

		// No need for inline styles if admin colors are set to defaults since default dialog colors are the same.
		if ( 'fresh' === $user_admin_color ) {

			return '';
		}

		$inline_styles      = '';
		$header_background  = '';
		$icon_select_border = '';
		$header_color       = '';

		// Take colors for dialog.
		if ( ! empty( $_wp_admin_css_colors[ $user_admin_color ]->colors ) ) {

			$colors = $_wp_admin_css_colors[ $user_admin_color ]->colors;

			$header_background  = isset( $colors[0] ) ? $colors[0] : '';
			$icon_select_border = isset( $colors[2] ) ? $colors[2] : '';
		}

		if ( ! empty( $_wp_admin_css_colors[ $user_admin_color ]->icon_colors ) ) {

			$icon_colors = $_wp_admin_css_colors[ $user_admin_color ]->icon_colors;

			$header_color = isset( $icon_colors['base'] ) ? $icon_colors['base'] : '';
		}

		// Generate styles for dialog.
		if ( ! empty( $header_color ) ) {

			$inline_styles .= '
					.iconize-modal .wpbs-modal-header,
					.iconize-modal .wpbs-modal-close,
					.iconize-modal .wpbs-modal-close:hover,
					.iconize-modal .wpbs-modal-close:focus {
						color: ' . $header_color . ';
					}';
		}

		if ( ! empty( $header_background ) ) {

			$inline_styles .= '
					.iconize-modal .wpbs-modal-header {
						background: ' . $header_background . ';
					}';
		}

		if ( ! empty( $icon_select_border ) ) {

			$inline_styles .= '
					.iconize-modal .icons-list-icon.selected-icon {
						border-color: ' . $icon_select_border . ';
					}';
		}

		return $inline_styles;
	}

	/**
	 * Return array of strings for the dialog input labels, button texts, notifications, etc.
	 * Used for dialog rendering and for javascript localization.
	 *
	 * @since 1.0.0
	 *
	 * @return array $strings
	 */
	public function get_iconize_dialog_strings() {

		$strings = array();

		// Buttons.
		$strings['add']    = __( 'Add icon', 'iconize' );
		$strings['insert'] = __( 'Insert icon', 'iconize' );
		$strings['edit']   = __( 'Edit icon', 'iconize' );
		$strings['update'] = __( 'Update icon', 'iconize' );
		$strings['remove'] = __( 'Remove icon', 'iconize' );
		$strings['cancel'] = __( 'Cancel', 'iconize' );
		$strings['stack']  = __( 'Create icon stack with selected and existing icons', 'iconize' );

		// Labels.
		$strings['icon_set_label']            = __( 'Change set:', 'iconize' );
		$strings['icon_name_label']           = __( 'Search by name:', 'iconize' );
		$strings['icon_effect_label']         = __( 'Icon effect:', 'iconize' );
		$strings['icon_color_label']          = __( 'Icon color:', 'iconize' );
		$strings['icon_size_label']           = __( 'Icon size:', 'iconize' );
		$strings['icon_align_label']          = __( 'Icon align:', 'iconize' );
		$strings['icon_custom_classes_label'] = __( 'Icon custom classes ( type CSS class names without dots, separate them by hitting enter/space/comma key ):', 'iconize' );
		$strings['stack_size_label']          = __( 'Stack size:', 'iconize' );
		$strings['stack_align_label']         = __( 'Stack align:', 'iconize' );

		// Effect Options.
		$strings['option_transform_label'] = __( 'Transformation', 'iconize' );
		$strings['option_animate_label']   = __( 'Animation', 'iconize' );
		$strings['option_hover_label']     = __( 'Hover Effect', 'iconize' );

		// Notifications.
		$strings['no_icons_defined']    = __( "No icons defined.\n\nIf you are trying to define your own icons, you're doing it wrong and you should read the documentation for Iconize plugin.\n\nIf that's not the case you should contact the author.", 'iconize' );
		$strings['no_icon_selected']    = __( "No icon selected.\n\nTo select icon simply click on one from the list or search for it by name using field on upper right corner of the dialog.", 'iconize' );
		$strings['no_icon_found']       = __( "Something is wrong.\n\nIcon is missing font or name CSS class ( or both ). If you want to edit this icon, replace it with another one or add missing classes manually using HTML view.", 'iconize' );
		$strings['no_icon_found_admin'] = __( "The icon font and/or icon name found here is no longer available.\n\nYou can insert new icon here, remove icon, or revert changes you have made to Iconize plugin.", 'iconize' );
		$strings['reserved_class']      = __( "Reserved CSS class name.\n\nYou cannot use this class as custom, please type another class name.", 'iconize' );

		return $strings;
	}

	/**
	 * Function to return an array of options for specified select dropdown available in dialog.
	 *
	 * @since 1.0.0
	 *
	 * @param string $dropdown - effets/transform/hover/size/align.
	 * @return array $options_array
	 */
	public function get_iconize_dialog_dropdown_options_for( $dropdown = '' ) {

		$dropdowns = array( 'transform', 'animate', 'hover', 'size', 'align' );

		if ( empty( $dropdown ) || ! in_array( $dropdown, $dropdowns, true ) ) {

			return array();
		}

		/**
		 * Default options per dropdown
		 */

		$default_transform_options = array(
			'grow'                   => __( 'Grow', 'iconize' ),
			'shrink'                 => __( 'Shrink', 'iconize' ),
			'rotate'                 => __( 'Rotate', 'iconize' ),
			'rotate-90'              => __( 'Rotate 90 deg', 'iconize' ),
			'rotate-180'             => __( 'Rotate 180 deg', 'iconize' ),
			'rotate-270'             => __( 'Rotate 270 deg', 'iconize' ),
			'flip-horizontal'        => __( 'Flip Horizontal', 'iconize' ),
			'flip-vertical'          => __( 'Flip Vertical', 'iconize' ),
			'grow-rotate'            => __( 'Grow Rotate', 'iconize' ),
			'grow-rotate-90'         => __( 'Grow Rotate 90 deg', 'iconize' ),
			'grow-rotate-180'        => __( 'Grow Rotate 180 deg', 'iconize' ),
			'grow-rotate-270'        => __( 'Grow Rotate 270 deg', 'iconize' ),
			'grow-flip-horizontal'   => __( 'Grow Flip Horizontal', 'iconize' ),
			'grow-flip-vertical'     => __( 'Grow Flip Hertical', 'iconize' ),
			'shrink-rotate'          => __( 'Shrink Rotate', 'iconize' ),
			'shrink-rotate-90'       => __( 'Shrink Rotate 90 deg', 'iconize' ),
			'shrink-rotate-180'      => __( 'Shrink Rotate 180 deg', 'iconize' ),
			'shrink-rotate-270'      => __( 'Shrink Rotate 270 deg', 'iconize' ),
			'shrink-flip-horizontal' => __( 'Shrink Flip Horizontal', 'iconize' ),
			'shrink-flip-vertical'   => __( 'Shrink Flip Hertical', 'iconize' ),
			'skew'                   => __( 'Skew', 'iconize' ),
			'skew-forward'           => __( 'Skew Forward', 'iconize' ),
			'skew-backward'          => __( 'Skew Backward', 'iconize' ),
			'float'                  => __( 'Float', 'iconize' ),
			'sink'                   => __( 'Sink', 'iconize' ),
			'float-shadow'           => __( 'Float Shadow', 'iconize' ),
		);

		$default_animate_options = array(
			'animate-pulse'         => __( 'Pulse', 'iconize' ),
			'animate-pulse-grow'    => __( 'Pulse Grow', 'iconize' ),
			'animate-pulse-shrink'  => __( 'Pulse Shrink', 'iconize' ),
			'animate-spin'          => __( 'Spin', 'iconize' ),
			'animate-spin-slow'     => __( 'Spin Slower', 'iconize' ),
			'animate-spin-fast'     => __( 'Spin Faster', 'iconize' ),
			'animate-spin-ccw'      => __( 'Spin CCW', 'iconize' ),
			'animate-spin-slow-ccw' => __( 'Spin Slower CCW', 'iconize' ),
			'animate-spin-fast-ccw' => __( 'Spin Faster CCW', 'iconize' ),
			'animate-buzz'          => __( 'Buzz', 'iconize' ),
			'animate-hover'         => __( 'Hover', 'iconize' ),
			'animate-hang'          => __( 'Hang', 'iconize' ),
			'animate-hover-shadow'  => __( 'Hover Shadow', 'iconize' ),
		);

		$default_hover_options = array(
			'hover-animate-fade-in'                => __( 'Fade In', 'iconize' ),
			'hover-animate-fade-out'               => __( 'Fade Out', 'iconize' ),
			'hover-animate-grow'                   => __( 'Grow', 'iconize' ),
			'hover-animate-shrink'                 => __( 'Shrink', 'iconize' ),
			'hover-animate-pop'                    => __( 'Pop', 'iconize' ),
			'hover-animate-push'                   => __( 'Push', 'iconize' ),
			'hover-animate-pulse'                  => __( 'Pulse', 'iconize' ),
			'hover-animate-pulse-grow'             => __( 'Pulse Grow', 'iconize' ),
			'hover-animate-pulse-shrink'           => __( 'Pulse Shrink', 'iconize' ),
			'hover-animate-rotate'                 => __( 'Rotate', 'iconize' ),
			'hover-animate-rotate-90'              => __( 'Rotate 90', 'iconize' ),
			'hover-animate-rotate-180'             => __( 'Rotate 180', 'iconize' ),
			'hover-animate-rotate-270'             => __( 'Rotate 270', 'iconize' ),
			'hover-animate-rotate-360'             => __( 'Rotate 360', 'iconize' ),
			'hover-animate-flip-horizontal'        => __( 'Flip Horizontally', 'iconize' ),
			'hover-animate-flip-vertical'          => __( 'Flip Vertically', 'iconize' ),
			'hover-animate-grow-rotate'            => __( 'Grow Rotate', 'iconize' ),
			'hover-animate-grow-rotate-90'         => __( 'Grow Rotate 90', 'iconize' ),
			'hover-animate-grow-rotate-180'        => __( 'Grow Rotate 180', 'iconize' ),
			'hover-animate-grow-rotate-270'        => __( 'Grow Rotate 270', 'iconize' ),
			'hover-animate-grow-rotate-360'        => __( 'Grow Rotate 360', 'iconize' ),
			'hover-animate-grow-flip-horizontal'   => __( 'Grow Flip Horizontally', 'iconize' ),
			'hover-animate-grow-flip-vertical'     => __( 'Grow Flip Vertically', 'iconize' ),
			'hover-animate-shrink-rotate'          => __( 'Shrink Rotate', 'iconize' ),
			'hover-animate-shrink-rotate-90'       => __( 'Shrink Rotate 90', 'iconize' ),
			'hover-animate-shrink-rotate-180'      => __( 'Shrink Rotate 180', 'iconize' ),
			'hover-animate-shrink-rotate-270'      => __( 'Shrink Rotate 270', 'iconize' ),
			'hover-animate-shrink-rotate-360'      => __( 'Shrink Rotate 360', 'iconize' ),
			'hover-animate-shrink-flip-horizontal' => __( 'Shrink Flip Horizontally', 'iconize' ),
			'hover-animate-shrink-flip-vertical'   => __( 'Shrink Flip Vertically', 'iconize' ),
			'hover-animate-spin'                   => __( 'Spin', 'iconize' ),
			'hover-animate-spin-slow'              => __( 'Spin Slower', 'iconize' ),
			'hover-animate-spin-fast'              => __( 'Spin Faster', 'iconize' ),
			'hover-animate-spin-ccw'               => __( 'Spin CCW', 'iconize' ),
			'hover-animate-spin-slow-ccw'          => __( 'Spin Slower CCW', 'iconize' ),
			'hover-animate-spin-fast-ccw'          => __( 'Spin Faster CCW', 'iconize' ),
			'hover-animate-buzz'                   => __( 'Buzz', 'iconize' ),
			'hover-animate-buzz-out'               => __( 'Buzz Out', 'iconize' ),
			'hover-animate-wobble-vertical'        => __( 'Wobble Vertical', 'iconize' ),
			'hover-animate-wobble-horizontal'      => __( 'Wobble Horizontal', 'iconize' ),
			'hover-animate-wobble-to-top-right'    => __( 'Wobble To Top Right', 'iconize' ),
			'hover-animate-wobble-to-bottom-right' => __( 'Wobble To Bottom Right', 'iconize' ),
			'hover-animate-wobble-to-bottom-left'  => __( 'Wobble To Bottom Left', 'iconize' ),
			'hover-animate-wobble-to-top-left'     => __( 'Wobble To Top Left', 'iconize' ),
			'hover-animate-wobble-top'             => __( 'Wobble Top', 'iconize' ),
			'hover-animate-wobble-bottom'          => __( 'Wobble Bottom', 'iconize' ),
			'hover-animate-wobble-skew'            => __( 'Wobble Skew', 'iconize' ),
			'hover-animate-skew'                   => __( 'Skew', 'iconize' ),
			'hover-animate-skew-forward'           => __( 'Skew Forward', 'iconize' ),
			'hover-animate-skew-backward'          => __( 'Skew Backward', 'iconize' ),
			'hover-animate-float'                  => __( 'Float', 'iconize' ),
			'hover-animate-sink'                   => __( 'Sink', 'iconize' ),
			'hover-animate-hover'                  => __( 'Hover', 'iconize' ),
			'hover-animate-hang'                   => __( 'Hang', 'iconize' ),
			'hover-animate-float-shadow'           => __( 'Float Shadow', 'iconize' ),
			'hover-animate-hover-shadow'           => __( 'Hover Shadow', 'iconize' ),
		);

		$default_size_options = array(
			'size-2x'        => __( '2x Larger', 'iconize' ),
			'size-3x'        => __( '3x Larger', 'iconize' ),
			'size-4x'        => __( '4x Larger', 'iconize' ),
			'size-5x'        => __( '5x Larger', 'iconize' ),
			'size-6x'        => __( '6x Larger', 'iconize' ),
			'size-7x'        => __( '7x Larger', 'iconize' ),
			'size-8x'        => __( '8x Larger', 'iconize' ),
			'size-9x'        => __( '9x Larger', 'iconize' ),
			'size-10x'       => __( '10x Larger', 'iconize' ),
			'size-sharp'     => __( 'Sharp', 'iconize' ),
			'size-sharp-2x'  => __( 'Sharp 2x Larger', 'iconize' ),
			'size-sharp-3x'  => __( 'Sharp 3x Larger', 'iconize' ),
			'size-sharp-4x'  => __( 'Sharp 4x Larger', 'iconize' ),
			'size-sharp-5x'  => __( 'Sharp 5x Larger', 'iconize' ),
			'size-sharp-6x'  => __( 'Sharp 6x Larger', 'iconize' ),
			'size-sharp-7x'  => __( 'Sharp 7x Larger', 'iconize' ),
			'size-sharp-8x'  => __( 'Sharp 8x Larger', 'iconize' ),
			'size-sharp-9x'  => __( 'Sharp 9x Larger', 'iconize' ),
			'size-sharp-10x' => __( 'Sharp 10x Larger', 'iconize' ),
		);

		$default_align_options = array(
			'align-left'   => __( 'Left', 'iconize' ),
			'align-center' => __( 'Center', 'iconize' ),
			'align-right'  => __( 'Right', 'iconize' ),
		);

		/**
		 * Allow users to customize dialog dropdown options.
		 *
		 * Functions attached to one of the filters below must return an array in format:
		 * array(
		 *     'custom-css-class-1' => 'Custom CSS Class 1 Label',
		 *     'custom-css-class-2' => 'Custom CSS Class 2 Label',
		 *     ...
		 *     'custom-css-class-n' => 'Custom CSS Class n Label'
		 * )
		 */
		$transform_options = apply_filters( 'iconize_dialog_transform_options', $default_transform_options );
		$animate_options   = apply_filters( 'iconize_dialog_animate_options', $default_animate_options );
		$hover_options     = apply_filters( 'iconize_dialog_hover_options', $default_hover_options );
		$size_options      = apply_filters( 'iconize_dialog_size_options', $default_size_options );
		$align_options     = apply_filters( 'iconize_dialog_align_options', $default_align_options );

		$options_array = array();

		switch ( $dropdown ) {
			case 'transform':
				$options_array = is_array( $transform_options ) && ! empty( $transform_options ) ? $transform_options : $default_transform_options;
				break;
			case 'animate':
				$options_array = is_array( $animate_options ) && ! empty( $animate_options ) ? $animate_options : $default_animate_options;
				break;
			case 'hover':
				$options_array = is_array( $hover_options ) && ! empty( $hover_options ) ? $hover_options : $default_hover_options;
				break;
			case 'size':
				$options_array = is_array( $size_options ) && ! empty( $size_options ) ? $size_options : $default_size_options;
				break;
			case 'align':
				$options_array = is_array( $align_options ) && ! empty( $align_options ) ? $align_options : $default_align_options;
				break;
		}

		return $options_array;
	}

	/**
	 * Function to generate HTML for modal dialog.
	 *
	 * @since 1.0.0
	 *
	 * @uses Iconize_WP::get_icons_array()
	 * @uses Iconize_WP::get_iconize_dialog_strings()
	 * @uses Iconize_WP::get_iconize_dialog_dropdown_options_for()
	 *
	 * @param string $prefix - prefix for several dialog CSS ids.
	 * @param array  $options - array of options to include.
	 * @param array  $extra_buttons - array of action buttons to include.
	 */
	public function iconize_dialog( $prefix = '', $options = '', $extra_buttons = '' ) {

		$default_options = array(
			'transform',
			'animate',
			'hover',
			'color',
			'size',
			'align',
			'custom_classes',
		);

		$default_extra_buttons = array( 'stack' );

		$pref = empty( $prefix ) ? 'mce' : $prefix;
		$opts = '' === $options ? $default_options : $options;
		$btns = empty( $extra_buttons ) ? $default_extra_buttons : $extra_buttons;

		$include_opts          = $opts ? $opts : array();
		$include_transform_opt = ! empty( $include_opts ) && in_array( 'transform', $include_opts, true );
		$include_animate_opt   = ! empty( $include_opts ) && in_array( 'animate', $include_opts, true );
		$include_hover_opt     = ! empty( $include_opts ) && in_array( 'hover', $include_opts, true );
		$include_color_opt     = ! empty( $include_opts ) && in_array( 'color', $include_opts, true );
		$include_size_opt      = ! empty( $include_opts ) && in_array( 'size', $include_opts, true );
		$include_align_opt     = ! empty( $include_opts ) && in_array( 'align', $include_opts, true );
		$include_custom_opt    = ! empty( $include_opts ) && in_array( 'custom_classes', $include_opts, true );

		$include_btns       = $btns ? $btns : array();
		$include_stack_btn  = ! empty( $include_btns ) && in_array( 'stack', $include_btns, true );
		$include_remove_btn = ! empty( $include_btns ) && in_array( 'remove', $include_btns, true );

		$icons_arr = $this->get_icons_array();
		$icon_sets = array_keys( $icons_arr );

		$dialog_strings = $this->get_iconize_dialog_strings();

		$effect_type_options              = array();
		$effect_type_options['transform'] = $include_transform_opt ? $dialog_strings['option_transform_label'] : '';
		$effect_type_options['animate']   = $include_animate_opt ? $dialog_strings['option_animate_label'] : '';
		$effect_type_options['hover']     = $include_hover_opt ? $dialog_strings['option_hover_label'] : '';
		?>
		<form style="display: none;" id="iconize-<?php echo esc_attr( $pref ); ?>-modal" class="iconize-modal wpbs-modal fade" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="wpbs-modal-dialog">
				<div class="wpbs-modal-content">
					<div class="wpbs-modal-header">
						<button type="button" class="wpbs-modal-close" data-dismiss="wpbsmodal" aria-hidden="true">&times;</button>
						<h4 id="iconize-<?php echo esc_attr( $pref ); ?>-title" class="wpbs-modal-title"><?php echo esc_html( $dialog_strings['insert'] ); ?></h4>
					</div><!-- /.wpbs-modal-header -->
					<div class="wpbs-modal-body">
						<div class="icons-list-controls">
							<label for="<?php echo esc_attr( $pref ); ?>-icon-set" class="howto"><?php echo esc_html( $dialog_strings['icon_set_label'] ); ?></label>
							<select name="<?php echo esc_attr( $pref ); ?>-icon-set" id="<?php echo esc_attr( $pref ); ?>-icon-set" size="1">
							<?php
							foreach ( $icon_sets as $key => $set ) {

								$selected = 0 === $key ? ' selected="selected"' : '';
								?>
								<option value="<?php echo esc_attr( $set ); ?>"<?php echo wp_kses_post( $selected ); ?>><?php echo esc_html( $set ); ?></option>
								<?php
							}
							?>
							</select>
							<label class="name-label">
								<span class="howto"><?php echo esc_html( $dialog_strings['icon_name_label'] ); ?></span>
								<input type="text" id="<?php echo esc_attr( $pref ); ?>-icon-name" name="<?php echo esc_attr( $pref ); ?>-icon-name" value=''>
							</label>
						</div><!-- /.icons-list-controls -->
						<div class="clear"></div>
						<div id="iconize-<?php echo esc_attr( $pref ); ?>-icons" class="icons-list-wrapper loading-overlay"></div><!-- /.icons-list-wrapper -->
						<div class="clear"></div>
						<?php
						// Check if there is any option enabled.
						if ( ! empty( $include_opts ) ) {

							// Check if any effect option is enabled.
							if ( $include_transform_opt || $include_animate_opt || $include_hover_opt ) {

								$transform_arr = $include_transform_opt ? $this->get_iconize_dialog_dropdown_options_for( 'transform' ) : array();
								$animate_arr   = $include_animate_opt ? $this->get_iconize_dialog_dropdown_options_for( 'animate' ) : array();
								$hover_arr     = $include_hover_opt ? $this->get_iconize_dialog_dropdown_options_for( 'hover' ) : array();
								?>
								<div class="iconize-modal-option">
									<p class="howto"><?php echo esc_html( $dialog_strings['icon_effect_label'] ); ?></p>
									<select name="<?php echo esc_attr( $pref ); ?>-icon-effect" id="<?php echo esc_attr( $pref ); ?>-icon-effect" class="iconize-mother-select" size="1" >
									<?php
									$count = 0;
									foreach ( $effect_type_options as $class => $label ) {
										if ( ! empty( $label ) ) {
											$selected = 0 === $count ? ' selected="selected"' : '';
											?>
											<option value="<?php echo esc_attr( $class ); ?>"<?php echo wp_kses_post( $selected ); ?>><?php echo esc_attr( $label ); ?></option>
											<?php
											$count++;
										}
									}
									?>
									</select>
									<?php

									if ( ! empty( $transform_arr ) ) {
										?>
										<select name="<?php echo esc_attr( $pref ); ?>-icon-transform" id="<?php echo esc_attr( $pref ); ?>-icon-transform" class="mother-opt-<?php echo esc_attr( $pref ); ?>-icon-effect mother-val-transform" size="1" >
											<option value="" selected="selected"><?php esc_html_e( 'None', 'iconize' ); ?></option>
										<?php
										foreach ( $transform_arr as $class => $label ) {
											?>
											<option value="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?></option>
											<?php
										}
										?>
										</select>
										<?php
									}

									if ( ! empty( $animate_arr ) ) {
										?>
										<select name="<?php echo esc_attr( $pref ); ?>-icon-animate" id="<?php echo esc_attr( $pref ); ?>-icon-animate" class="mother-opt-<?php echo esc_attr( $pref ); ?>-icon-effect mother-val-animate" size="1" >
											<option value="" selected="selected"><?php esc_html_e( 'None', 'iconize' ); ?></option>
											<?php
											foreach ( $animate_arr as $class => $label ) {
												?>
												<option value="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?></option>
												<?php
											}
											?>
										</select>
										<?php
									}

									if ( ! empty( $hover_arr ) ) {
										?>
										<select name="<?php echo esc_attr( $pref ); ?>-icon-hover" id="<?php echo esc_attr( $pref ); ?>-icon-hover" class="mother-opt-<?php echo esc_attr( $pref ); ?>-icon-effect mother-val-hover" size="1" >
											<option value="" selected="selected"><?php esc_html_e( 'None', 'iconize' ); ?></option>
											<?php
											foreach ( $hover_arr as $class => $label ) {
												?>
												<option value="<?php echo esc_attr( $class ); ?>"<?php echo wp_kses_post( $selected ); ?>><?php echo esc_html( $label ); ?></option>
												<?php
											}
											?>
										</select>
										<?php
									}
									?>
								</div>
								<?php
							}

							// Check if color option is enabled.
							if ( $include_color_opt ) {
								?>
								<div class="iconize-modal-option">
									<p class="howto"><?php echo esc_html( $dialog_strings['icon_color_label'] ); ?><span id="<?php echo esc_attr( $pref ); ?>-color-hover-checkbox" class="color-hover-checkbox hidden"><input type="checkbox" id="<?php echo esc_attr( $pref ); ?>-icon-color-hover" name="<?php echo esc_attr( $pref ); ?>-icon-color-hover" /><label for="<?php echo esc_attr( $pref ); ?>-icon-color-hover"><?php esc_html_e( 'Change color to parent color on hover', 'iconize' ); ?></label></span></p>
									<input type="text" value="" name="<?php echo esc_attr( $pref ); ?>-icon-color" id="<?php echo esc_attr( $pref ); ?>-icon-color" />
								</div>
								<div class="clear"></div>
								<?php
							}

							// Check if size option is enabled.
							if ( $include_size_opt ) {

								// Get options for size dropdown.
								$size_arr = $this->get_iconize_dialog_dropdown_options_for( 'size' );

								if ( ! empty( $size_arr ) ) {
									?>
									<div class="iconize-modal-option">
										<p id="<?php echo esc_attr( $pref ); ?>-icon-size-howto" class="howto"><?php echo esc_html( $dialog_strings['icon_size_label'] ); ?> </p>
										<select name="<?php echo esc_attr( $pref ); ?>-icon-size" id="<?php echo esc_attr( $pref ); ?>-icon-size" <?php echo wp_kses_post( 'mce' === $pref ? 'class="iconize-mother-select"' : '' ); ?> size="1">
											<option value="" selected="selected"><?php esc_html_e( 'Inherit', 'iconize' ); ?></option>
											<?php
											foreach ( $size_arr as $class => $label ) {
												?>
												<option value="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?></option>
												<?php
											}
											if ( 'mce' === $pref ) {
												echo '<option value="custom-size">' . esc_html__( 'Custom', 'iconize' ) . '</option>';
											}
											?>
										</select>
										<?php
										if ( 'mce' === $pref ) {
											echo '<input type="text" id="mce-icon-custom-size" name="mce-icon-custom-size" value="" class="icon-custom-size mother-opt-mce-icon-size mother-val-custom-size"></option>';
										}
										?>
									</div>
									<?php
								}
							}

							// Check if align option is enabled.
							if ( $include_align_opt ) {

								// Get options for align dropdown.
								$align_arr = $this->get_iconize_dialog_dropdown_options_for( 'align' );

								if ( ! empty( $align_arr ) ) {
									?>
									<div class="iconize-modal-option">
										<p id="<?php echo esc_attr( $pref ); ?>-icon-align-howto" class="howto"><?php echo esc_html( $dialog_strings['icon_align_label'] ); ?></p>
										<select name="<?php echo esc_attr( $pref ); ?>-icon-align" id="<?php echo esc_attr( $pref ); ?>-icon-align" size="1" >
											<option value="" selected="selected"><?php esc_html_e( 'None', 'iconize' ); ?></option>
										<?php
										foreach ( $align_arr as $class => $label ) {
											?>
											<option value="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?></option>
											<?php
										}
										?>
										</select>
									</div>
									<?php
								}
							}

							// Check if custom classes option is enabled.
							if ( $include_custom_opt ) {
								?>
								<div class="iconize-modal-option full-width">
									<p class="howto"><?php echo esc_html( $dialog_strings['icon_custom_classes_label'] ); ?></p>
									<input type="text" id="<?php echo esc_attr( $pref ); ?>-icon-custom-classes" name="<?php echo esc_attr( $pref ); ?>-icon-custom-classes" value=''>
								</div>
								<?php
							}
							?>
							<div class="clear"></div>
							<?php
						}
						?>
					</div><!-- /.wpbs-modal-body -->
					<div class="wpbs-modal-footer">
						<button type="button" id="iconize-<?php echo esc_attr( $pref ); ?>-cancel" class="iconize-cancel button button-large left" data-dismiss="wpbsmodal" aria-hidden="true"><?php echo esc_html( $dialog_strings['cancel'] ); ?></button>
						<button type="button" id="iconize-<?php echo esc_attr( $pref ); ?>-update" class="iconize-update button button-large button-primary right"><?php echo esc_html( $dialog_strings['insert'] ); ?></button>
						<?php
						// Check if stack button is enabled.
						if ( $include_stack_btn ) {
							?>
							<button type="button" id="iconize-<?php echo esc_attr( $pref ); ?>-stack" class="iconize-stack button button-large right"><?php echo esc_html( $dialog_strings['stack'] ); ?></button>
							<?php
						}
						// Check if remove button is enabled.
						if ( $include_remove_btn ) {
							?>
							<button type="button" id="iconize-<?php echo esc_attr( $pref ); ?>-remove" class="iconize-remove button button-large right"><?php echo esc_html( $dialog_strings['remove'] ); ?></button>
							<?php
						}
						?>
					</div><!-- /.wpbs-modal-footer -->
				</div><!-- /.wpbs-modal-content -->
			</div><!-- /.wpbs-modal-dialog -->
		</form><!-- /.wpbs-modal -->
		<?php
	}

	/**
	 * Get an array of all supported taxonomies
	 *
	 * @since 1.0.0
	 *
	 * @return array $screen_ids
	 */
	public function iconize_get_supported_taxonomy_screens_ids() {

		$screen_ids = array();

		// Get all registered taxonomies with backend ui.
		$taxonomies = get_taxonomies(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'names',
			'and'
		);

		if ( $taxonomies ) {

			foreach ( $taxonomies as $taxonomy ) {

				// Check for support.
				$tax_support = $this->get_iconize_support_for( 'taxonomy_' . $taxonomy );
				if ( $tax_support['enabled'] ) {

					$screen_ids[] = 'edit-' . $taxonomy;
				}
			}
		}

		return $screen_ids;
	}

	/**
	 * Update to 1.2.0.
	 *
	 * @since 1.2.0
	 */
	public function upgrade_to_1_2_0() {

		$options_map = array(
			'iconize_fonts'        => array(
				'iconize_font_awesome',
				'iconize_font_dashicons',
			),
			'iconize_integrations' => array(
				'iconize_editor',
				'iconize_widgets',
				'iconize_nav_menus',
			),
		);

		$taxonomies = get_taxonomies(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'names',
			'and'
		);
		if ( $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				$options_map['iconize_integrations'][] = 'iconize_taxonomy_' . $taxonomy;
			}
		}

		foreach ( $options_map as $new_option => $old_options ) {
			$new_value = array();
			$moved     = false;
			$to_remove = array();
			foreach ( $old_options as $old_option ) {
				$old_val = get_option( $old_option, 'not_set' );
				if ( $old_val ) {
					$new_value[ str_replace( 'iconize_', '', $old_option ) ] = 1;
				}
				if ( 'not_set' !== $old_val ) {
					$to_remove[] = $old_option;
				}
			}
			$moved = update_option( $new_option, $new_value );
			if ( $moved ) {
				foreach ( $to_remove as $remove_option ) {
					delete_option( $remove_option );
				}
			}
		}

		update_option( 'iconize_plugin_version', '1.2.0' );
	}

} // End class Iconize_WP
