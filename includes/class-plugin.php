<?php

namespace SmartLink;

/**
 * The main plugin class.
 */
class Plugin
{
    private $loader;
    private $plugin_slug;
    private $version;
    private $option_name;

    public function __construct()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'widgets/widget.php';

        $this->plugin_slug = Info::SLUG;
        $this->version = Info::VERSION;
        $this->option_name = Info::OPTION_NAME;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_frontend_hooks();
        $this->update();
    }

    private function load_dependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'frontend/class-frontend.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-admin.php';
        $this->loader = new Loader();
    }

    private function define_admin_hooks()
    {
        $plugin_admin = new Admin($this->plugin_slug, $this->version, $this->option_name);
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'assets');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        $this->loader->add_action('init', $plugin_admin, 'register_meta');
        $this->loader->add_action('rest_api_init', $plugin_admin, 'register_api');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_menus');
        $this->loader->add_filter('plugin_action_links', $plugin_admin, 'link_to_plugin_config', 10, 2);
        $this->loader->add_action('plugins_loaded', $this, 'load_languages');
        $this->loader->add_action('plugins_loaded', $this, 'xsmartlink_update_db_check');
        $this->loader->add_action('widgets_init', $this, 'load_widgets');
    }

    private function define_frontend_hooks()
    {
//      $plugin_frontend = new Frontend( $this->plugin_slug, $this->version, $this->option_name );
//      $this->loader->add_action( 'wp_enqueue_scripts', $plugin_frontend, 'assets' );
//      $this->loader->add_action( 'wp_footer', $plugin_frontend, 'render' );
        $this->loader->add_action('plugins_loaded', $this, 'load_languages');
        $this->loader->add_action('widgets_init', $this, 'load_widgets');
    }

    private function update()
    {
        global $wpdb;
        // удаляем старье
        // можно закомментировать через апдейт.
        $existing_columns = $wpdb->get_col("DESC `{$wpdb->prefix}posts`", 0);
        if (in_array('post_link_type', $existing_columns)) {
            $sql = "ALTER TABLE `{$wpdb->prefix}posts` DROP `post_link_type`;";
            $wpdb->query($sql);
        }
        $existing_columns = $wpdb->get_col("DESC `{$wpdb->prefix}xanchors`", 0);
        if (!in_array('link_state', $existing_columns)) {
            $sql = "ALTER TABLE `{$wpdb->prefix}xanchors` ";
            $sql .= "ADD `link_state` INT NOT NULL DEFAULT 0;";
            $wpdb->query($sql);
        }
    }

    function load_widgets()
    {
        register_widget('SmartLink\xsmartlink_widget');
    }

    public function load_languages()
    {
        load_plugin_textdomain($this->plugin_slug, false, $this->plugin_slug . '/languages/');
    }

    function xsmartlink_update_db_check()
    {
        if (get_option(INFO::OPTION_NAME . '_db_version') != INFO::DB_VERSION) {
            require_once plugin_dir_path(__FILE__) . 'class-activator.php';
            Activator::activate();
        }
    }

    public function run()
    {
        $this->loader->run();
    }
}
