<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://robertdevore.com
 * @since             1.0.0
 * @package           Maintenance_Mode_For_WordPress
 *
 * @wordpress-plugin
 *
 * Plugin Name: Maintenance Mode for WordPress®
 * Description: A maintenance mode plugin with customizable landing pages using the core WordPress® editor, locked down to the domain root for non-logged-in users.
 * Plugin URI:  https://github.com/robertdevore/maintenance-mode-for-wordpress/
 * Version:     1.1.0
 * Author:      Robert DeVore
 * Author URI:  https://robertdevore.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: maintenance-mode-wp
 * Domain Path: /languages
 * Update URI:  https://github.com/robertdevore/maintenance-mode-for-wordpress/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Define the plugin version.
define( 'MAINTENANCE_MODE_VERSION', '1.1.0' );

// Load plugin text domain for translations
function maintenance_mode_wp_load_textdomain() {
    load_plugin_textdomain( 
        'maintenance-mode-wp', 
        false, 
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'maintenance_mode_wp_load_textdomain' );

// Create a Maintenance Mode page on activation.
register_activation_hook( __FILE__, [ 'Maintenance_Mode_WP', 'activate' ] );

// Include the Plugin Update Checker.
require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/robertdevore/maintenance-mode-for-wordpress/',
    __FILE__,
    'maintenance-mode-for-wordpress'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Include the autoload for Composer.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use RobertDevore\WPComCheck\WPComPluginHandler;

// Initialize the WPComPluginHandler with the plugin slug and learn more link.
new WPComPluginHandler( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' );

/**
 * Adds a "Maintenance Mode is Active" notice in the admin toolbar.
 *
 * @since  1.1.0
 * @return void
 */
function maintenance_mode_wp_admin_bar_notice( $wp_admin_bar ) {
    // Check if Maintenance Mode or Coming Soon Mode is enabled.
    $maintenance_mode = get_option( 'maintenance_mode_wp_enabled' );
    $coming_soon_mode = get_option( 'maintenance_mode_wp_coming_soon' );

    if ( $maintenance_mode || $coming_soon_mode ) {
        // Determine the correct message.
        $mode_text = $maintenance_mode ? esc_html__( 'MAINTENANCE MODE IS ACTIVE', 'maintenance-mode-wp' ) : esc_html__( 'COMING SOON MODE IS ACTIVE', 'maintenance-mode-wp' );

        // Settings page URL.
        $settings_url = admin_url( 'edit.php?post_type=maintenance_page&page=maintenance_mode_wp_settings' );

        // Add the notice to the admin bar.
        $wp_admin_bar->add_node( [
            'id'    => 'maintenance-mode-notice',
            'title' => '<span>' . $mode_text . '</span>',
            'href'  => $settings_url,
            'meta'  => [
                'title' => __( 'Click to manage Maintenance Mode settings', 'maintenance-mode-wp' ),
                'class' => 'maintenance-mode-toolbar',
            ],
        ] );
    }
}
add_action( 'admin_bar_menu', 'maintenance_mode_wp_admin_bar_notice', 100 );

/**
 * Enqueue admin styles for Maintenance Mode only when active.
 *
 * @since 1.1.0
 */
function maintenance_mode_wp_enqueue_toolbar_styles() {
    // Check if either Maintenance Mode or Coming Soon Mode is active and if the toolbar is visible.
    if ( ( get_option( 'maintenance_mode_wp_enabled' ) || get_option( 'maintenance_mode_wp_coming_soon' ) ) && is_admin_bar_showing() ) {
        wp_enqueue_style(
            'maintenance-mode-wp-toolbar-styles',
            plugin_dir_url( __FILE__ ) . 'assets/css/toolbar.css',
            [],
            MAINTENANCE_MODE_VERSION
        );
    }
}
add_action( 'admin_enqueue_scripts', 'maintenance_mode_wp_enqueue_toolbar_styles' ); // Load in admin
add_action( 'wp_enqueue_scripts', 'maintenance_mode_wp_enqueue_toolbar_styles' ); // Load on front-end if toolbar is active

/**
 * Main plugin class for Maintenance Mode functionality.
 */
class Maintenance_Mode_WP {
    /**
     * Constructor to initialize hooks and actions.
     * 
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'init', [ $this, 'register_cpt' ] );
        add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'template_redirect', [ $this, 'lock_frontend' ] );
        add_action( 'rest_api_init', [ $this, 'disable_rest_api_for_guests' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_styles' ] );
    }

    /**
     * Enqueue the plugin's admin styles.
     *
     * @since  1.0.0
     * @return void
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'maintenance-mode-wp-styles',
            plugin_dir_url( __FILE__ ) . 'assets/css/style.css',
            [],
            MAINTENANCE_MODE_VERSION
        );
    }

    /**
     * Activation hook callback.
     *
     * @since 1.0.1
     * @return void
     */
    public static function activate() {
        if ( ! get_option( 'maintenance_mode_wp_enabled' ) ) {
            update_option( 'maintenance_mode_wp_enabled', 0 );
        }

        if ( ! get_option( 'maintenance_mode_wp_date' ) ) {
            update_option( 'maintenance_mode_wp_date', '' );
        }

        if ( ! get_option( 'maintenance_mode_wp_cpt_id' ) ) {
            update_option( 'maintenance_mode_wp_cpt_id', 0 );
        }
    }

    /**
     * Enqueue styles for block editor on the frontend to match the backend styles.
     *
     * @since  1.0.0
     * @return void
     */
    public function enqueue_frontend_styles() {
        if ( is_singular( 'maintenance_page' ) ) {
            wp_enqueue_style( 'wp-block-library' );
            wp_enqueue_style(
                'maintenance-mode-wp-styles',
                plugin_dir_url( __FILE__ ) . 'assets/css/style.css',
                [],
                MAINTENANCE_MODE_VERSION
            );
        }
    }

    /**
     * Registers a custom post type for Maintenance Mode landing pages.
     *
     * @since  1.0.0
     * @return void
     */
    public function register_cpt() {
        $args = [
            'labels' => [
                'name'                  => esc_html__( 'Maintenance', 'maintenance-mode-wp' ),
                'singular_name'         => esc_html__( 'Maintenance Page', 'maintenance-mode-wp' ),
                'menu_name'             => esc_html__( 'Maintenance', 'maintenance-mode-wp' ),
                'name_admin_bar'        => esc_html__( 'Maintenance', 'maintenance-mode-wp' ),
                'all_items'             => esc_html__( 'All Pages', 'maintenance-mode-wp' ),
                'add_new_item'          => esc_html__( 'Add New Page', 'maintenance-mode-wp' ),
                'add_new'               => esc_html__( 'Add New', 'maintenance-mode-wp' ),
                'new_item'              => esc_html__( 'New Maintenance Page', 'maintenance-mode-wp' ),
                'edit_item'             => esc_html__( 'Edit Maintenance Page', 'maintenance-mode-wp' ),
                'update_item'           => esc_html__( 'Update Maintenance Page', 'maintenance-mode-wp' ),
                'view_item'             => esc_html__( 'View Maintenance Page', 'maintenance-mode-wp' ),
                'view_items'            => esc_html__( 'View Maintenance Pages', 'maintenance-mode-wp' ),
                'search_items'          => esc_html__( 'Search Maintenance Pages', 'maintenance-mode-wp' ),
                'not_found'             => esc_html__( 'Maintenance Page Not Found', 'maintenance-mode-wp' ),
                'not_found_in_trash'    => esc_html__( 'Maintenance Page Not Found in Trash', 'maintenance-mode-wp' ),
                'insert_into_item'      => esc_html__( 'Insert into Maintenance Page', 'maintenance-mode-wp' ),
                'uploaded_to_this_item' => esc_html__( 'Uploaded to this Maintenance Page', 'maintenance-mode-wp' ),
                'items_list'            => esc_html__( 'Maintenance Pages List', 'maintenance-mode-wp' ),
                'items_list_navigation' => esc_html__( 'Maintenance Pages List Navigation', 'maintenance-mode-wp' ),
                'filter_items_list'     => esc_html__( 'Filter Maintenance Pages List', 'maintenance-mode-wp' ),
            ],
            'public'              => false,
            'show_ui'             => current_user_can( 'administrator' ),
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-welcome-view-site',
            'capability_type'     => 'post',
            'capabilities'        => [
                'edit_post'          => 'manage_options',
                'read_post'          => 'manage_options',
                'delete_post'        => 'manage_options',
                'edit_posts'         => 'manage_options',
                'edit_others_posts'  => 'manage_options',
                'publish_posts'      => 'manage_options',
                'read_private_posts' => 'manage_options',
            ],
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'has_archive'         => false,
            'show_in_rest'        => true,
            'supports'            => [ 'title', 'editor' ],
        ];
        register_post_type( 'maintenance_page', $args );
    }

    /**
     * Register the settings page under the Maintenance Mode CPT menu in WordPress.
     *
     * @since  1.0.0
     * @return void
     */
    public function register_settings_page() {
        add_submenu_page(
            'edit.php?post_type=maintenance_page',
            esc_html__( 'Settings', 'maintenance-mode-wp' ),
            esc_html__( 'Settings', 'maintenance-mode-wp' ),
            'manage_options',
            'maintenance_mode_wp_settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Registers the settings and their respective fields.
     *
     * @since  1.0.0
     * @return void
     */
    public function register_settings() {
        register_setting(
            'maintenance_mode_wp_settings',
            'maintenance_mode_wp_enabled',
            [ 'sanitize_callback' => [ $this, 'sanitize_checkbox' ] ]
        );

        register_setting(
            'maintenance_mode_wp_settings',
            'maintenance_mode_wp_date',
            [ 'sanitize_callback' => 'sanitize_text_field' ]
        );

        register_setting(
            'maintenance_mode_wp_settings',
            'maintenance_mode_wp_cpt_id',
            [ 'sanitize_callback' => 'intval' ]
        );

        register_setting(
            'maintenance_mode_wp_settings',
            'maintenance_mode_wp_coming_soon',
            [ 'sanitize_callback' => [ $this, 'sanitize_checkbox' ] ]
        );        

        add_settings_section(
            'maintenance_mode_wp_main_section',
            esc_html__( 'Maintenance Mode Settings', 'maintenance-mode-wp' ),
            [ $this, 'settings_section_callback' ],
            'maintenance_mode_wp_settings'
        );

        add_settings_field(
            'maintenance_mode_wp_enabled',
            esc_html__( 'Enable Maintenance Mode', 'maintenance-mode-wp' ),
            [ $this, 'checkbox_field_callback' ],
            'maintenance_mode_wp_settings',
            'maintenance_mode_wp_main_section',
            [ 'option_name' => 'maintenance_mode_wp_enabled' ]
        );

        add_settings_field(
            'maintenance_mode_wp_coming_soon',
            esc_html__( 'Enable Coming Soon Mode', 'maintenance-mode-wp' ),
            [ $this, 'checkbox_field_callback' ],
            'maintenance_mode_wp_settings',
            'maintenance_mode_wp_main_section',
            [ 'option_name' => 'maintenance_mode_wp_coming_soon' ]
        );

        add_settings_field(
            'maintenance_mode_wp_date',
            esc_html__( 'Launch Date', 'maintenance-mode-wp' ),
            [ $this, 'text_field_callback' ],
            'maintenance_mode_wp_settings',
            'maintenance_mode_wp_main_section',
            [ 'option_name' => 'maintenance_mode_wp_date', 'type' => 'date' ]
        );

        add_settings_field(
            'maintenance_mode_wp_cpt_id',
            esc_html__( 'Maintenance Mode Page', 'maintenance-mode-wp' ),
            [ $this, 'select_field_callback' ],
            'maintenance_mode_wp_settings',
            'maintenance_mode_wp_main_section',
            [ 'option_name' => 'maintenance_mode_wp_cpt_id' ]
        );
    }

    /**
     * Renders the Maintenance Mode settings page.
     *
     * @since  1.0.0
     * @return void
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e( 'Maintenance Mode Settings', 'maintenance-mode-wp' ); ?>
                <a id="maintenance-mode-support-btn" href="https://robertdevore.com/contact/" target="_blank" class="button button-alt" style="margin-left: 10px;">
                    <span class="dashicons dashicons-format-chat" style="vertical-align: middle;"></span> <?php esc_html_e( 'Support', 'maintenance-mode-wp' ); ?>
                </a>
                <a id="maintenance-mode-docs-btn" href="https://robertdevore.com/articles/maintenance-mode-for-wordpress/" target="_blank" class="button button-alt" style="margin-left: 5px;">
                    <span class="dashicons dashicons-media-document" style="vertical-align: middle;"></span> <?php esc_html_e( 'Documentation', 'maintenance-mode-wp' ); ?>
                </a>
            </h1>
            <hr />

            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'maintenance_mode_wp_settings' );
                do_settings_sections( 'maintenance_mode_wp_settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Disables the REST API for non-logged-in users when Maintenance Mode is enabled.
     *
     * @since  1.0.0
     * @return void
     */
    public function disable_rest_api_for_guests() {
        if ( ! is_user_logged_in() && get_option( 'maintenance_mode_wp_enabled' ) ) {
            // Allow REST API access for the block editor (required for admin screens).
            if ( defined( 'REST_REQUEST' ) && REST_REQUEST && ! is_admin() ) {
                wp_die(
                    esc_html__( 'REST API access is restricted while the site is under maintenance.', 'maintenance-mode-wp' ),
                    esc_html__( 'Maintenance Mode', 'maintenance-mode-wp' ),
                    [ 'response' => apply_filters( 'mmwp_rest_api_response_code', 503 ) ]
                );
            }
        }
    }

    /**
     * Restricts access to the frontend for non-logged-in users when Maintenance Mode is enabled.
     *
     * @since  1.0.0
     * @return void
     */
    public function lock_frontend() {
        // Get the current mode settings
        $maintenance_mode = get_option( 'maintenance_mode_wp_enabled' );
        $coming_soon_mode = get_option( 'maintenance_mode_wp_coming_soon' );

        // If neither mode is enabled, do nothing
        if ( ! $maintenance_mode && ! $coming_soon_mode ) {
            return;
        }

        // Only lock down for non-logged-in users
        if ( ! is_user_logged_in() ) {
            $page_id = get_option( 'maintenance_mode_wp_cpt_id' );

            // Ensure a valid Maintenance Page exists
            if ( $page_id ) {
                $page = get_post( $page_id );

                // Display the "Maintenance" or "Coming Soon" page if published
                if ( $page && 'publish' === $page->post_status ) {
                    // If Maintenance Mode is active, return a 503 status.
                    if ( $maintenance_mode ) {
                        status_header( 503 );
                    }

                    // Output the landing page content.
                    echo '<!DOCTYPE html>';
                    echo '<html ' . get_language_attributes() . '>';
                    echo '<head>';
                    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=' . esc_attr( get_bloginfo( 'charset' ) ) . '">';
                    echo '<title>' . esc_html( get_bloginfo( 'name' ) ) . '</title>';
                    wp_head();
                    echo '</head>';
                    echo '<body>';
                    echo '<div class="maintenance-mode-content">';
                    echo apply_filters( 'the_content', $page->post_content );
                    echo '</div>';
                    wp_footer();
                    echo '</body>';
                    echo '</html>';
                    exit;
                }
            }
    
            // If no valid page exists, show a generic fallback message
            if ( $maintenance_mode ) {
                status_header( 503 );
            }
            wp_die(
                esc_html__( 'Our site is currently unavailable. Please check back later.', 'maintenance-mode-wp' ),
                esc_html__( 'Site Unavailable', 'maintenance-mode-wp' ),
                [ 'response' => $maintenance_mode ? 503 : 200 ]
            );
        }
    }

    /**
     * Checkbox field callback.
     *
     * @param array $args Field arguments.
     * 
     * @since  1.0.0
     * @return void
     */
    public function checkbox_field_callback( $args ) {
        $option = get_option( $args['option_name'], 0 ); // Default to 0 if not set.
        ?>
        <input type="hidden" name="<?php echo esc_attr( $args['option_name'] ); ?>" value="0">
        <input type="checkbox" name="<?php echo esc_attr( $args['option_name'] ); ?>" value="1" <?php checked( $option, 1 ); ?>>
        <?php
    }

    /**
     * Text field callback.
     *
     * @param array $args Field arguments.
     * 
     * @since  1.0.0
     * @return void
     */
    public function text_field_callback( $args ) {
        $option = get_option( $args['option_name'] );
        ?>
        <input type="<?php echo esc_attr( $args['type'] ); ?>" name="<?php echo esc_attr( $args['option_name'] ); ?>" value="<?php echo esc_attr( $option ); ?>">
        <?php
    }

    /**
     * Select field callback.
     *
     * @param array $args Field arguments.
     * 
     * @since  1.0.0
     * @return void
     */
    public function select_field_callback( $args ) {
        $option = get_option( $args['option_name'] );
        $maintenance_pages = get_posts( [
            'post_type'   => 'maintenance_page',
            'post_status' => 'publish',
            'numberposts' => -1,
        ] );
        ?>
        <select name="<?php echo esc_attr( $args['option_name'] ); ?>">
            <?php foreach ( $maintenance_pages as $page ) : ?>
                <option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $option, $page->ID ); ?>>
                    <?php echo esc_html( $page->post_title ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Settings section callback.
     *
     * @since  1.0.0
     * @return void
     */
    public function settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure the maintenance mode settings below.', 'maintenance-mode-wp' ) . '</p>';
    }

    /**
     * Sanitize checkbox input.
     *
     * @param mixed $input Input value.
     * 
     * @since  1.0.0
     * @return int Sanitized value.
     */
    public function sanitize_checkbox( $input ) {
        return ! empty( $input ) ? 1 : 0;
    }
}

// Initialize the plugin.
new Maintenance_Mode_WP();
