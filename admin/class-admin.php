<?php

namespace SmartLink;

/**
 * The code used in the admin.
 */
class Admin {
	private $plugin_slug;
	private $version;
	private $option_name;
	private $settings;
	private $settings_group;
	private $browse;
	private $import;
	private $anchors;

	public function __construct( $plugin_slug, $version, $option_name ) {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-browse.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-import.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-anchors.php';

		$this->plugin_slug    = $plugin_slug;
		$this->version        = $version;
		$this->option_name    = $option_name;
		$this->settings       = get_option( $this->option_name );
		$this->settings_group = $this->option_name . '_group';

		$this->browse  = new Browse( $this->plugin_slug, $this->version, $this->option_name );
		$this->import  = new Import( $this->plugin_slug, $this->version, $this->option_name );
		$this->anchors = new Anchors( $this->plugin_slug, $this->version, $this->option_name );
	}

	public function link_to_plugin_config( $links, $file ) {
		if ( mb_stripos( $file, $this->plugin_slug ) !== false ) {
			$links[] = '<a href="' . admin_url( 'admin.php?page=' . $this->plugin_slug . '_options' ) . '">' . __( 'Settings' ) . '</a>';
		}

		return $links;
	}

	/**
	 * Generate settings fields by passing an array of data (see the render method).
	 *
	 * @param array $field_args The array that helps build the settings fields
	 * @param array $settings The settings array from the options table
	 *
	 * @return string The settings fields' HTML to be output in the view
	 */
	private function custom_settings_fields( $field_args, $settings ) {
		$output = array();

		foreach ( $field_args as $field ) {
			$slug    = $field['slug'];
			$setting = $this->option_name . '[' . $slug . ']';
			$label   = __( $field['label'], $this->plugin_slug );
			$text    = isset( $field['text'] ) ? __( $field['text'], $this->plugin_slug ) : "";
			$class   = $field['class'];

			if ( $field['type'] === 'text' ) {
				$output[] = array(
					'label'   => '<label for="' . $setting . '">' . $label . '</label>',
					'control' => '<input type="text" id="' . $setting . '" class="' . $class . '" name="' . $setting . '" value="' . $settings[ $slug ] . '">'
				);
			} elseif ( $field['type'] === 'number' ) {
				$output[] = array(
					'label'   => '<label for="' . $setting . '">' . $label . '</label>',
					'control' => '<input min="0" step="1" type="number" id="' . $setting . '" class="' . $class . '" name="' . $setting . '" value="' . $settings[ $slug ] . '">'
				);
			} elseif ( $field['type'] === 'textarea' ) {
				$output[] = array(
					'label'   => '<label for="' . $setting . '">' . $label . '</label>',
					'control' => '<textarea id="' . $setting . '" class="' . $class . '" name="' . $setting . '" rows="10">' . $settings[ $slug ] . '</textarea>'
				);
			} elseif ( $field['type'] === 'checkbox' ) {
				$v = '';
				if ( isset( $settings[ $slug ] ) && $settings[ $slug ] == 1 ) {
					$v = ' checked';
				}
				$output[] = array(
					'label'   => '<label for="' . $setting . '">' . $label . '</label>',
					'control' => '<input type="checkbox" class="' . $class . '" id="' . $setting . '" name="' . $setting . '" value="1"' . $v . '> ' . $text
				);
			}
		}

		return $output;
	}

	public function assets() {
		wp_enqueue_style( $this->plugin_slug, plugin_dir_url( __FILE__ ) . 'css/xsmartlink-admin.css', [], $this->version );
		wp_enqueue_script( $this->plugin_slug, plugin_dir_url( __FILE__ ) . 'js/xsmartlink-admin.js', [ 'jquery' ], $this->version, false );
		wp_enqueue_script( $this->plugin_slug.'-uploader', plugin_dir_url( __FILE__ ) . 'js/xsmartlink-uploader.js', [ 'jquery' ], $this->version, false );
		wp_enqueue_script( 'charts', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.min.js', [ 'jquery' ], $this->version, false );
		wp_localize_script( $this->plugin_slug, 'wma', array(
				'all_links_checked' => __( 'All links checked!', $this->plugin_slug ),
				'all_linked'        => __( 'All links linked!', $this->plugin_slug ),
                'delete_question'        => __( 'Delete this image?', $this->plugin_slug ),
			)
		);
	}

	public function register_settings() {
		register_setting( $this->settings_group, $this->option_name );
	}

	public function add_menus() {
		//$translations = get_translations_for_domain( $this->plugin_slug );
		//print_r($translations->entries);
		add_menu_page(
			__( 'Acceptors', $this->plugin_slug ),
			__( 'Acceptors', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug . '_list',
			[ $this->browse, 'render' ],
			plugins_url( 'img/icon.png', __FILE__ ),
			6
		);
		add_submenu_page(
			$this->plugin_slug . '_list',
			__( 'Import', $this->plugin_slug ),
			__( 'Import', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug . '_import',
			[ $this->import, 'render' ]
		);
		add_submenu_page(
			$this->plugin_slug . '_list',
			__( 'Export', $this->plugin_slug ),
			__( 'Export', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug . '_export',
			[ $this->import, 'export' ]
		);
		add_submenu_page(
			$this->plugin_slug . '_list',
			__( 'Stat', $this->plugin_slug ),
			__( 'Stat', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug . '_stat',
			[ $this->anchors, 'render' ]
		);
		add_submenu_page(
			$this->plugin_slug . '_list',
			__( 'Options', $this->plugin_slug ),
			__( 'Options', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug . '_options',
			[ $this, 'render_options' ]
		);
	}

	/**
	 * Render the view using MVC pattern.
	 */
	public function render_options() {

		// Generate the settings fields
		$field_args = [
			[
				'label' => __( 'Links in posts', $this->plugin_slug ),
				'slug'  => 'insert_in_pages',
				'type'  => 'checkbox',
				'text'  => __( 'Show', $this->plugin_slug ) . '<p class="description" id="tagline-description">' . __( 'can be disabled and used to display links - a widget', $this->plugin_slug ) . '</p>',
				'class' => '',
			],
			[
				'label' => __( 'All links', $this->plugin_slug ),
				'slug'  => 'global_req',
				'type'  => 'number',
				'class' => 'small-text',
			],
			[
				'label' => __( 'Local links', $this->plugin_slug ),
				'slug'  => 'local_req',
				'type'  => 'number',
				'class' => 'small-text',
			],
			[
				'label' => __( 'Local url', $this->plugin_slug ),
				'slug'  => 'local_domain',
				'type'  => 'text',
				'class' => 'regular-text code',
			],
			[
				'label' => __( 'For new posts', $this->plugin_slug ),
				'slug'  => 'new_post_to_anchors',
				'type'  => 'checkbox',
				'text'  => __( 'Add to anchors', $this->plugin_slug ),
				'class' => '',
			],
			[
				'label' => __( 'Default count for new anchor (default)', $this->plugin_slug ),
				'slug'  => 'new_req',
				'type'  => 'number',
				'class' => 'small-text',
			],
            [
                'label' => __( 'Show images in links', $this->plugin_slug ),
                'slug'  => 'image_enabled',
                'type'  => 'checkbox',
                'text'  => __( 'Show', $this->plugin_slug ),
                'class' => '',
            ],
            [
                'label' => __( 'Image height', $this->plugin_slug ),
                'slug'  => 'image_height',
                'type'  => 'number',
                'class' => 'small-text',
            ],
		];

		// Model
		$settings = $this->settings;

		// Controller
		$fields         = $this->custom_settings_fields( $field_args, $settings );
		$settings_group = $this->settings_group;
		$heading        = __( 'Options', $this->plugin_slug );
		$submit_text    = __( 'Save Changes' );

		// View
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/view_options.php';
	}
}
