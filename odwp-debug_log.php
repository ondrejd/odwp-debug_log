<?php
/**
 * Plugin Name: Debug Log Viewer
 * Plugin URI: https://github.com/ondrejd/odwp-debug_log
 * Description: Small <a href="https://wordpress.org/" target="blank">WordPress</a> plugin especially for developers that allows better work with debug.log file. 
 * Version: 1.0.0
 * Author: Ondřej Doněk
 * Author URI: https://ondrejd.com/
 * License: GPLv3
 * Requires at least: 4.7
 * Tested up to: 4.8
 * Tags: debug,log,development
 * Donate link: https://www.paypal.me/ondrejd
 *
 * Text Domain: odwp-debug_log
 * Domain Path: /languages/
 *
 * @author Ondřej Doněk <ondrejd@gmail.com>
 * @link https://github.com/ondrejd/odwp-debug_log for the canonical source repository
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @package odwp-debug_log
 * @since 1.0.0
 */

/**
 * This file is just a bootstrap. It checks if requirements of plugins are met
 * and accordingly either initializes the plugin or halts the process.
 *
 * Requirements can be specified for PHP and the WordPress self - version
 * for both, required extensions for PHP and requireds plugins for WP.
 *
 * If you are using copy of original file in your plugin you shoud change
 * prefix "odwpdl" and name "odwp-debug_log" to your own values.
 *
 * To set the requirements go down to line 133 and define array that
 * is used as a parameter for `odwpdl_check_requirements` function.
 */

if( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Some widely used constants
defined( 'DL_SLUG' ) || define( 'DL_SLUG', 'odwpdl' );
defined( 'DL_NAME' ) || define( 'DL_NAME', 'odwp-debug_log' );
defined( 'DL_PATH' ) || define( 'DL_PATH', dirname( __FILE__ ) . '/' );
defined( 'DL_FILE' ) || define( 'DL_FILE', __FILE__ );
defined( 'DL_LOG' )  || define( 'DL_LOG', WP_CONTENT_DIR . '/debug.log' );

if( ! function_exists( 'odwpdl_check_requirements' ) ) :
    /**
     * Checks requirements of our plugin.
     * @param array $requirements
     * @return array
     * @since 1.0.0
     */
    function odwpdl_check_requirements( array $requirements ) {
        global $wp_version;

        // Initialize locales
        load_plugin_textdomain( DL_SLUG, false, dirname( __FILE__ ) . '/languages' );

        /**
         * @var array Hold requirement errors
         */
        $errors = [];

        // Check PHP version
        if( ! empty( $requirements['php']['version'] ) ) {
            if( version_compare( phpversion(), $requirements['php']['version'], '<' ) ) {
                $errors[] = sprintf(
                        __( 'PHP nesplňuje nároky pluginu na minimální verzi (vyžadována nejméně <b>%s</b>)!', DL_SLUG ),
                        $requirements['php']['version']
                );
            }
        }

        // Check PHP extensions
        if( count( $requirements['php']['extensions'] ) > 0 ) {
            foreach( $requirements['php']['extensions'] as $req_ext ) {
                if( ! extension_loaded( $req_ext ) ) {
                    $errors[] = sprintf(
                            __( 'Je vyžadováno rozšíření PHP <b>%s</b>, to ale není nainstalováno!', DL_SLUG ),
                            $req_ext
                    );
                }
            }
        }

        // Check WP version
        if( ! empty( $requirements['wp']['version'] ) ) {
            if( version_compare( $wp_version, $requirements['wp']['version'], '<' ) ) {
                $errors[] = sprintf(
                        __( 'Plugin vyžaduje vyšší verzi platformy <b>WordPress</b> (minimálně <b>%s</b>)!', DL_SLUG ),
                        $requirements['wp']['version']
                );
            }
        }

        // Check WP plugins
        if( count( $requirements['wp']['plugins'] ) > 0 ) {
            $active_plugins = (array) get_option( 'active_plugins', [] );
            foreach( $requirements['wp']['plugins'] as $req_plugin ) {
                if( ! in_array( $req_plugin, $active_plugins ) ) {
                    $errors[] = sprintf(
                            __( 'Je vyžadován plugin <b>%s</b>, ten ale není nainstalován!', DL_SLUG ),
                            $req_plugin
                    );
                }
            }
        }

        return $errors;
    }
endif;

if( ! function_exists( 'odwpdl_deactivate_raw' ) ) :
    /**
     * Deactivate plugin by the raw way.
     * @return void
     * @since 1.0.0
     */
    function odwpdl_deactivate_raw() {
        $active_plugins = get_option( 'active_plugins' );
        $out = [];
        foreach( $active_plugins as $key => $val ) {
            if( $val != DL_NAME . '/' . DL_NAME . '.php' ) {
                $out[$key] = $val;
            }
        }
        update_option( 'active_plugins', $out );
    }
endif;

if( ! function_exists( 'odwpdl_error_log' ) ) :
    /**
     * @internal Write message to the `wp-content/debug.log` file.
     * @param string $message
     * @param integer $message_type (Optional.)
     * @param string $destination (Optional.)
     * @param string $extra_headers (Optional.)
     * @return void
     * @since 1.0.0
     */
    function odwpdl_error_log( string $message, int $message_type = 0, string $destination = null, string $extra_headers = '' ) {
        if( ! file_exists( DL_LOG ) || ! is_writable( DL_LOG ) ) {
            return;
        }

        $record = '[' . date( 'd-M-Y H:i:s', time() ) . ' UTC] ' . $message;
        file_put_contents( DL_LOG, PHP_EOL . $record, FILE_APPEND );
    }
endif;

if( ! function_exists( 'odwpdl_write_log' ) ) :
    /**
     * Write record to the `wp-content/debug.log` file.
     * @param mixed $log
     * @return void
     * @since 1.0.0
     */
    function odwpdl_write_log( $log ) {
        if( is_array( $log ) || is_object( $log ) ) {
            odwpdl_error_log( print_r( $log, true ) );
        } else {
            odwpdl_error_log( $log );
        }
    }
endif;

/**
 * Errors from the requirements check
 * @var array
 */
$odwpdl_errs = odwpdl_check_requirements( [
    'php' => [
        // Enter minimum PHP version you needs.
        'version' => '5.6',
        // Enter extensions that your plugin needs
        'extensions' => [
            //'gd',
        ],
    ],
    'wp' => [
        // Enter minimum WP version you need
        'version' => '4.7',
        // Enter WP plugins that your plugin needs
        'plugins' => [
            //'woocommerce/woocommerce.php',
        ],
    ],
] );

// Check if requirements are met or not
if( count( $odwpdl_errs ) > 0 ) {
    // Requirements are not met
    odwpdl_deactivate_raw();

    // In administration print errors
    if( is_admin() ) {
        $err_head = __( '<b>Debug Log Viewer</b>: ', DL_SLUG );
        foreach( $odwpdl_errs as $err ) {
            printf( '<div class="error"><p>%s</p></div>', $err_head . $err );
        }
    }
} else {
    // Requirements are met so initialize the plugin.
    include( DL_PATH . 'src/DL_Screen_Prototype.php' );
    include( DL_PATH . 'src/DL_Plugin.php' );
    DL_Plugin::initialize();
}
