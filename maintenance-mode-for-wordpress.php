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
 * Version:     1.0.1
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
define( 'MAINTENANCE_MODE_VERSION', '1.0.1' );

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
                'name'          => esc_html__( 'Maintenance', 'maintenance-mode-wp' ),
                'singular_name' => esc_html__( 'Maintenance Page', 'maintenance-mode-wp' ),
                'menu_name' => esc_html__( 'Maintenance Pages', 'maintenance-mode-wp' ),
        		'name_admin_bar' => esc_html__( 'Maintenance Pages', 'maintenance-mode-wp' ),
        		'all_items' => esc_html__( 'All Maintenance Pages', 'maintenance-mode-wp' ),
        		'add_new_item' => esc_html__( 'Add New Maintenance Page', 'maintenance-mode-wp' ),
        		'add_new' => esc_html__( 'Add New', 'maintenance-mode-wp' ),
        		'new_item' => esc_html__( 'New Maintenance Page', 'maintenance-mode-wp' ),
        		'edit_item' => esc_html__( 'Edit Maintenance Page', 'maintenance-mode-wp' ),
        		'update_item' => esc_html__( 'Update Maintenance Page', 'maintenance-mode-wp' ),
        		'view_item' => esc_html__( 'View Maintenance Page', 'maintenance-mode-wp' ),
        		'view_items' => esc_html__( 'View Maintenance Pages', 'maintenance-mode-wp' ),
        		'search_items' => esc_html__( 'Search Maintenance Pages', 'maintenance-mode-wp' ),
        		'not_found' => esc_html__( 'Maintenance Page Not Found', 'maintenance-mode-wp' ),
        		'not_found_in_trash' => esc_html__( 'Maintenance Page Not Found in Trash', 'maintenance-mode-wp' ),
        		'insert_into_item' => esc_html__( 'Insert into Maintenance Page', 'maintenance-mode-wp' ),
        		'uploaded_to_this_item' => esc_html__( 'Uploaded to this Maintenance Page', 'maintenance-mode-wp' ),
        		'items_list' => esc_html__( 'Maintenance Pages List', 'maintenance-mode-wp' ),
        		'items_list_navigation' => esc_html__( 'Maintenance Pages List Navigation', 'maintenance-mode-wp' ),
        		'filter_items_list' => esc_html__( 'Filter Maintenance Pages List', 'maintenance-mode-wp' ),
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
        // Check if maintenance mode is enabled and the user is not logged in.
        if ( ! is_user_logged_in() && get_option( 'maintenance_mode_wp_enabled' ) ) {
            $maintenance_page_id = get_option( 'maintenance_mode_wp_cpt_id' );

            // Ensure we have a valid maintenance page ID.
            if ( $maintenance_page_id ) {
                $maintenance_post = get_post( $maintenance_page_id );

                // Display the maintenance page content if it's published.
                if ( $maintenance_post && 'publish' === $maintenance_post->post_status ) {
                    status_header( 503 );

                    // Output the maintenance page content.
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
                    echo apply_filters( 'the_content', $maintenance_post->post_content );
                    echo '</div>';
                    wp_footer();
                    echo '</body>';
                    echo '</html>';
                    exit;
                }
            }

            // Fallback message if no maintenance page is configured.
            wp_die(
                esc_html__( 'Our site is currently under maintenance. Please check back later.', 'maintenance-mode-wp' ),
                esc_html__( 'Maintenance Mode', 'maintenance-mode-wp' ),
                [ 'response' => apply_filters( 'mmwp_rest_api_response_code', 503 ) ]
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
        $option = get_option( $args['option_name'] );
        ?>
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
        return ( isset( $input ) && '1' === $input ) ? 1 : 0;
    }
}

// Initialize the plugin.
new Maintenance_Mode_WP();

/**
 * Helper function to handle WordPress.com environment checks.
 *
 * @param string $plugin_slug     The plugin slug.
 * @param string $learn_more_link The link to more information.
 * 
 * @since  1.1.0
 * @return bool
 */
function wp_com_plugin_check( $plugin_slug, $learn_more_link ) {
    // Check if the site is hosted on WordPress.com.
    if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
        // Ensure the deactivate_plugins function is available.
        if ( ! function_exists( 'deactivate_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Deactivate the plugin if in the admin area.
        if ( is_admin() ) {
            deactivate_plugins( $plugin_slug );

            // Add a deactivation notice for later display.
            add_option( 'wpcom_deactivation_notice', $learn_more_link );

            // Prevent further execution.
            return true;
        }
    }

    return false;
}

/**
 * Auto-deactivate the plugin if running in an unsupported environment.
 *
 * @since  1.1.0
 * @return void
 */
function wpcom_auto_deactivation() {
    if ( wp_com_plugin_check( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' ) ) {
        return; // Stop execution if deactivated.
    }
}
add_action( 'plugins_loaded', 'wpcom_auto_deactivation' );

/**
 * Display an admin notice if the plugin was deactivated due to hosting restrictions.
 *
 * @since  1.1.0
 * @return void
 */
function wpcom_admin_notice() {
    $notice_link = get_option( 'wpcom_deactivation_notice' );
    if ( $notice_link ) {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                echo wp_kses_post(
                    sprintf(
                        __( 'Maintenance Mode for WordPress® has been deactivated because it cannot be used on WordPress.com-hosted websites. %s', 'maintenance-mode-wp' ),
                        '<a href="' . esc_url( $notice_link ) . '" target="_blank" rel="noopener">' . __( 'Learn more', 'maintenance-mode-wp' ) . '</a>'
                    )
                );
                ?>
            </p>
        </div>
        <?php
        delete_option( 'wpcom_deactivation_notice' );
    }
}
add_action( 'admin_notices', 'wpcom_admin_notice' );

/**
 * Prevent plugin activation on WordPress.com-hosted sites.
 *
 * @since  1.1.0
 * @return void
 */
function wpcom_activation_check() {
    if ( wp_com_plugin_check( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' ) ) {
        // Display an error message and stop activation.
        wp_die(
            wp_kses_post(
                sprintf(
                    '<h1>%s</h1><p>%s</p><p><a href="%s" target="_blank" rel="noopener">%s</a></p>',
                    __( 'Plugin Activation Blocked', 'maintenance-mode-wp' ),
                    __( 'This plugin cannot be activated on WordPress.com-hosted websites. It is restricted due to concerns about WordPress.com policies impacting the community.', 'maintenance-mode-wp' ),
                    esc_url( 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' ),
                    __( 'Learn more', 'maintenance-mode-wp' )
                )
            ),
            esc_html__( 'Plugin Activation Blocked', 'maintenance-mode-wp' ),
            [ 'back_link' => true ]
        );
    }
}
register_activation_hook( __FILE__, 'wpcom_activation_check' );

/**
 * Add a deactivation flag when the plugin is deactivated.
 *
 * @since  1.1.0
 * @return void
 */
function wpcom_deactivation_flag() {
    add_option( 'wpcom_deactivation_notice', 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' );
}
register_deactivation_hook( __FILE__, 'wpcom_deactivation_flag' );
