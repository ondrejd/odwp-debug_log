<?php
/**
 * Plugin Name: Debug Log Viewer
 * Plugin URI: https://github.com/ondrejd/odwp-debug_log
 * Description: Small plugin aimed for developers that want easilly see their debug.log file.
 * Version: 1.0.0
 * Author: Ondřej Doněk
 * Author URI: https://ondrejd.com/
 * License: GPLv3
 * Requires at least: 4.7
 * Tested up to: 4.9.5
 * Tags: debug,log,development
 * Donate link: https://www.paypal.me/ondrejd
 * Text Domain: odwpdl
 * Domain Path: /languages/
 *
 * @author Ondřej Doněk <ondrejd@gmail.com>
 * @link https://github.com/ondrejd/odwp-debug_log for the canonical source repository
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @package odwp-debug_log
 * @since 1.0.0
 */

/**
 * This file is just a bootstrap. It checks if requirements of plugins
 * are met and accordingly either allow activating the plugin or stops
 * the activation process.
 *
 * Requirements can be specified either for PHP interperter or for
 * the WordPress self. In both cases you can specify minimal required
 * version and required extensions/plugins.
 *
 * If you are using copy of original file in your plugin you should change
 * prefix "odwpdl" and name "odwp-debug_log" to your own values.
 *
 * To set the requirements go down to line 200 and define array that
 * is used as a parameter for `odwpdl_check_requirements` function.
 */

if( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Some constants
defined( 'DL_SLUG' ) || define( 'DL_SLUG', 'odwpdl' );
defined( 'DL_NAME' ) || define( 'DL_NAME', 'odwp-debug_log' );
defined( 'DL_PATH' ) || define( 'DL_PATH', dirname( __FILE__ ) . '/' );
defined( 'DL_FILE' ) || define( 'DL_FILE', __FILE__ );
defined( 'DL_LOG' )  || define( 'DL_LOG', WP_CONTENT_DIR . '/debug.log' );


if( ! function_exists( 'odwpdl_check_requirements' ) ) :
    /**
     * Checks requirements of our plugin.
     * @global string $wp_version
     * @param array $requirements
     * @return array
     * @since 1.0.0
     */
    function odwpdl_check_requirements( array $requirements ) {
        global $wp_version;

        // Initialize locales
        load_plugin_textdomain( DL_SLUG, false, DL_NAME . '/languages' );

        /**
         * @var array Hold requirement errors
         */
        $errors = [];

        // Check PHP version
        if( ! empty( $requirements['php']['version'] ) ) {
            if( version_compare( phpversion(), $requirements['php']['version'], '<' ) ) {
                $errors[] = sprintf(
                        __( 'Used PHP interpreter doesn\'t meet requirements of this plugin (is required version <b>%1$s</b> at least)!'),
                        $requirements['php']['version']
                );
            }
        }

        // Check PHP extensions
        if( count( $requirements['php']['extensions'] ) > 0 ) {
            foreach( $requirements['php']['extensions'] as $req_ext ) {
                if( ! extension_loaded( $req_ext ) ) {
                    $errors[] = sprintf(
                            __( 'PHP extension <b>%1$s</b> is required but not installed!', DL_SLUG ),
                            $req_ext
                    );
                }
            }
        }

        // Check WP version
        if( ! empty( $requirements['wp']['version'] ) ) {
            if( version_compare( $wp_version, $requirements['wp']['version'], '<' ) ) {
                $errors[] = sprintf(
                        __( 'This plugin requires higher version of <b>WordPress</b> (at least version <b>%1$s</b>)!', DL_SLUG ),
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
                            __( 'The plugin <b>%1$s</b> is required but not installed!', DL_SLUG ),
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
     * Deactivate plugin by the raw way (it updates directly WP options).
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


if( ! function_exists( 'odwpdl_write_log' ) ) :
    /**
     * Write record to the `wp-content/debug.log` file.
     * @param mixed $log
     * @return void
     * @since 1.0.0
     */
    function odwpdl_write_log( $log ) {
        if( ! file_exists( DL_LOG ) || ! is_writable( DL_LOG ) ) {
            return;
        }

        if( is_null( $log ) ) {
            $message = 'NULL';
        }
        elseif( is_array( $log ) || is_object( $log ) ) {
            $message = print_r( $log, true );
        }
        else {
            $message = $log;
        }

        $datetime = date( 'd-M-Y H:i:s', time() );
        $record = '[' . $datetime . ' UTC] ' . trim( $message ) . PHP_EOL;

        file_put_contents( DL_LOG, $record, FILE_APPEND );
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
    function odwpdl_error_log( string $message, int $message_type = null, string $destination = null, string $extra_headers = null ) {
        _deprecated_function( __FUNCTION__, '1.0.0', __( 'Use function `odwpdl_write_log` instead!', DL_SLUG ) );

        odwpdl_write_log( $message );
    }
endif;


if( ! function_exists( 'readonly' ) ) :
    /**
     * Prints HTML readonly attribute. It's an addition to WP original
     * functions {@see disabled()} and {@see checked()}.
     * @param mixed $value
     * @param mixed $current (Optional.) Defaultly TRUE.
     * @return string
     * @since 1.0.0
     */
    function readonly( $current, $value = true ) {
        if( $current == $value ) {
            echo ' readonly';
        }
    }
endif;


/**
 * Errors from the requirements check
 * @var array
 */
$odwpdl_errs = odwpdl_check_requirements( [
    'php' => [
        // Enter minimum PHP version you needs
        'version' => '7.0',
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
        add_action( 'admin_notices', function() use ( $odwpdl_errs ) {
            $err_head = __( '<b>Debug Log Viewer</b>: ', DL_SLUG );

            foreach( $odwpdl_errs as $err ) {
                printf( '<div class="error"><p>%1$s</p></div>', $err_head . $err );
            }
        } );
    }
} else {
    // Requirements are met so initialize the plugin.
    include( DL_PATH . 'src/DL_Screen_Prototype.php' );
    include( DL_PATH . 'src/DL_Plugin.php' );
    DL_Plugin::initialize();
}
