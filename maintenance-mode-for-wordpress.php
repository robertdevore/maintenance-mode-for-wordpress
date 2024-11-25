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
 * Version:     1.0.0
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
define( 'MAINTENANCE_MODE_VERSION', '1.0.0' );

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
     */
    public function __construct() {
        add_action( 'init', [ $this, 'register_cpt' ] );
        add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'template_redirect', [ $this, 'lock_frontend' ] );
        add_action( 'rest_api_init', [ $this, 'disable_rest_api_for_guests' ] );
        add_filter( 'pre_option_rss_use_excerpt', '__return_false' );
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
            'maintenance-mode-wp',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Registers settings fields for enabling Maintenance Mode and configuring the landing page.
     *
     * @since  1.0.0
     * @return void
     */
    public function register_settings() {
        register_setting( 'maintenance_mode_wp_settings', 'maintenance_mode_wp_enabled' );
        register_setting( 'maintenance_mode_wp_settings', 'maintenance_mode_wp_date' );
        register_setting( 'maintenance_mode_wp_settings', 'maintenance_mode_wp_cpt_id' );
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
                    <span class="dashicons dashicons-format-chat" style="vertical-align: middle;"></span> <?php esc_html_e( 'Support', 'markdown-editor' ); ?>
                </a>
                <a id="maintenance-mode-docs-btn" href="https://robertdevore.com/articles/maintenance-mode-for-wordpress/" target="_blank" class="button button-alt" style="margin-left: 5px;">
                    <span class="dashicons dashicons-media-document" style="vertical-align: middle;"></span> <?php esc_html_e( 'Documentation', 'markdown-editor' ); ?>
                </a>
            </h1>
            <hr />
            <form method="post" action="options.php">
                <?php
                settings_fields( 'maintenance_mode_wp_settings' );
                do_settings_sections( 'maintenance_mode_wp_settings' );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Enable Maintenance Mode', 'maintenance-mode-wp' ); ?></th>
                        <td>
                            <input type="checkbox" name="maintenance_mode_wp_enabled" value="1" <?php checked( get_option( 'maintenance_mode_wp_enabled' ), 1 ); ?>>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Launch Date', 'maintenance-mode-wp' ); ?></th>
                        <td>
                            <input type="date" name="maintenance_mode_wp_date" value="<?php echo esc_attr( get_option( 'maintenance_mode_wp_date' ) ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Maintenance Mode Page', 'maintenance-mode-wp' ); ?></th>
                        <td>
                            <select name="maintenance_mode_wp_cpt_id">
                                <?php
                                $maintenance_pages = get_posts( [
                                    'post_type'   => 'maintenance_page',
                                    'post_status' => 'publish',
                                    'numberposts' => -1,
                                ] );
                                foreach ( $maintenance_pages as $page ) {
                                    echo '<option value="' . esc_attr( $page->ID ) . '" ' . selected( get_option( 'maintenance_mode_wp_cpt_id' ), $page->ID, false ) . '>' . esc_html( $page->post_title ) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Restricts access to the frontend for non-logged-in users when Maintenance Mode is enabled.
     *
     * @since  1.0.0
     * @return void
     */
    public function lock_frontend() {
        if ( is_user_logged_in() || ! get_option( 'maintenance_mode_wp_enabled' ) ) {
            return;
        }

        $maintenance_page_id = get_option( 'maintenance_mode_wp_cpt_id' );
        if ( ! $maintenance_page_id || get_queried_object_id() == $maintenance_page_id ) {
            return;
        }

        $launch_date = get_option( 'maintenance_mode_wp_date' );
        if ( $launch_date && strtotime( $launch_date ) <= current_time( 'timestamp' ) ) {
            return;
        }

        $maintenance_post = get_post( $maintenance_page_id );
        if ( $maintenance_post && 'publish' === $maintenance_post->post_status ) {
            status_header( 200 );

            // Open HTML structure.
            echo '<!DOCTYPE html><html ' . get_language_attributes() . '><head>';
            wp_head();
            echo '</head><body class="maintenance-mode">';

            // Output the maintenance page content.
            echo '<div class="maintenance-content">';
            echo apply_filters( 'the_content', $maintenance_post->post_content );
            echo '</div>';

            wp_footer();
            echo '</body></html>';
            exit;
        } else {
            wp_die( esc_html__( 'Our site is under maintenance. Please check back later.', 'maintenance-mode-wp' ) );
        }
    }

    /**
     * Disables the REST API for non-logged-in users when Maintenance Mode is enabled.
     *
     * @since  1.0.0
     * @return void
     */
    public function disable_rest_api_for_guests() {
        if ( ! is_user_logged_in() && get_option( 'maintenance_mode_wp_enabled' ) ) {
            wp_die( esc_html__( 'REST API access is restricted while the site is under maintenance.', 'maintenance-mode-wp' ), 403 );
        }
    }

    /**
     * Creates a default Maintenance Mode page upon plugin activation.
     *
     * @since  1.0.0
     * @return void
     */
    public static function activate() {
        if ( ! get_option( 'maintenance_mode_wp_cpt_id' ) ) {
            $page_id = wp_insert_post( [
                'post_type'    => 'maintenance_page',
                'post_title'   => esc_html__( 'Default Maintenance Page', 'maintenance-mode-wp' ),
                'post_content' => esc_html__( 'Our site is currently under maintenance. Please check back soon.', 'maintenance-mode-wp' ),
                'post_status'  => 'publish',
            ] );
            if ( ! is_wp_error( $page_id ) ) {
                update_option( 'maintenance_mode_wp_cpt_id', $page_id );
            }
        }
    }
}

// Initialize the plugin.
new Maintenance_Mode_WP();
