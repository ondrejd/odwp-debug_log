<?php
/**
 * @author Ondrej Donek <ondrejd@gmail.com>
 * @link https://github.com/ondrejd/odwp-debug_log for the canonical source repository
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @package odwp-debug_log
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'DL_Plugin' ) ) :

/**
 * Main class.
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @since 1.0.0
 */
class DL_Plugin {

    /**
     * @since 1.0.0
     * @var string
     */
    const SETTINGS_KEY = DL_SLUG . '_settings';

    /**
     * @since 1.0.0
     * @var string
     */
    const TABLE_NAME = DL_SLUG;

    /**
     * @since 1.0.0
     * @var array $admin_screens Array with admin screens.
     */
    public static $admin_screens = [];

    /**
     * @since 1.0.0
     * @var string
     */
    public static $options_page_hook;

    /**
     * Activation hook.
     *
     * @return void
     * @since 1.0.0
     */
    public static function activate() {
        //...
    }

    /**
     * Deactivation hook.
     *
     * @return void
     * @since 1.0.0
     */
    public static function deactivate() {
        //...
    }

    /**
     * Return default options of the plugin.
     * 
     * @return array
     * @since 1.0.0
     */
    public static function get_default_options() : array {
        return [
            'prev_log_count' => 0,
        ];
    }

    /**
     * Return current options of the plugin.
     *
     * @return array
     * @since 1.0.0
     * @uses get_option()
     * @uses update_option()
     */
    public static function get_options() : array {
        $defaults = self::get_default_options();
        $options = get_option( self::SETTINGS_KEY, [] );
        $update = false;

        // Fill defaults for the options that are not set yet
        foreach ( $defaults as $key => $val ) {
            if ( ! array_key_exists( $key, $options ) ) {
                $options[$key] = $val;
                $update = true;
            }
        }

        // Updates options if needed
        if ( $update === true) {
            update_option( self::SETTINGS_KEY, $options );
        }

        return $options;
    }

    /**
     * Return value of option with given key.
     *
     * @param string $key Option's key.
     * @param mixed $default Option's default value.
     * @return mixed Option's value.
     * @since 1.0.0
     */
    public static function get_option( string $key, $default = null ) {
        $options = self::get_options();

        if ( array_key_exists( $key, $options ) ) {
            return $options[$key];
        }

        return $default;
    }

    /**
     * Initialize the plugin.
     *
     * @return void
     * @since 1.0.0
     * @uses add_action()
     * @uses is_admin()
     * @uses register_activation_hook()
     * @uses register_deactivation_hook()
     * @uses register_uninstall_hook()
     */
    public static function initialize() {
        register_activation_hook( DL_FILE, [__CLASS__, 'activate'] );
        register_deactivation_hook( DL_FILE, [__CLASS__, 'deactivate'] );
        register_uninstall_hook( DL_FILE, [__CLASS__, 'uninstall'] );

        add_action( 'init', [__CLASS__, 'init'] );

        if ( is_admin() === true ) {
            add_action( 'admin_init', [__CLASS__, 'admin_init'] );
            add_action( 'admin_menu', [__CLASS__, 'admin_menu'] );
            add_action( 'admin_bar_menu', [__CLASS__, 'admin_menu_bar'], 100 );
            add_action( 'plugins_loaded', [__CLASS__, 'plugins_loaded'] );
            add_action( 'wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts'] );
            add_action( 'admin_enqueue_scripts', [__CLASS__, 'admin_enqueue_scripts'] );
        }
    }

    /**
     * Hook for "init" action.
     *
     * @return void
     * @since 1.0.0
     * @uses load_plugin_textdomain()
     */
    public static function init() {

        // Initialize locales
        load_plugin_textdomain( DL_SLUG, false, DL_NAME . '/languages' );

        // Initialize options
        self::get_options();

        // Initialize custom post types
        self::init_custom_post_types();

        // Initialize shortcodes
        self::init_shortcodes();

        // Initialize admin screens
        self::init_screens();
        self::screens_call_method( 'init' );
    }

    /**
     * Initialize custom post types.
     *
     * @return void
     * @since 1.0.0
     */
    public static function init_custom_post_types() {
        //...
    }

    /**
     * Register our shortcodes.
     *
     * @return void
     * @since 1.0.O
     */
    public static function init_shortcodes() {
        //...
    }

    /**
     * Initialize settings using <b>WordPress Settings API</b>.
     *
     * @link https://developer.wordpress.org/plugins/settings/settings-api/
     * @return void
     * @since 1.0.0
     */
    protected static function init_settings() {
        //...
    }

    /**
     * Initialize admin screens.
     *
     * @return void
     * @since 1.0.0
     */
    protected static function init_screens() {

    	// Include required source files
        include( DL_PATH . 'src/DL_Screen_Prototype.php' );
        include( DL_PATH . 'src/DL_Log_Screen.php' );

        // Initialize all screens one by one

        /**
         * @var DL_Log_Screen $log_screen
         */
        $log_screen = new DL_Log_Screen();
        self::$admin_screens[$log_screen->get_slug()] = $log_screen;
    }

    /**
     * Hook for "admin_init" action.
     *
     * @return void
     * @since 1.0.0
     * @uses register_setting()
     */
    public static function admin_init() {
        register_setting( DL_SLUG, self::SETTINGS_KEY );

        self::check_environment();
        self::init_settings();
        self::screens_call_method( 'admin_init' );
        self::admin_init_widgets();
    }

    /**
     * Initializes WP admin dashboard widgets.
     *
     * @return void
     * @since 1.0.0
     * @uses add_action()
     */
    public static function admin_init_widgets() {

    	// Include required source files
        include( DL_PATH . 'src/DL_Log_Dashboard_Widget.php' );

        // Register widgets
        add_action( 'wp_dashboard_setup', ['DL_Log_Dashboard_Widget', 'init'] );
    }

    /**
     * Hook for "admin_menu" action.
     *
     * @return void
     * @since 1.0.0
     */
    public static function admin_menu() {

        // Call action for `admin_menu` hook on all screens.
        self::screens_call_method( 'admin_menu' );
    }

    /**
     * Hook for "admin_menu_bar" action.
     *
     * @link https://codex.wordpress.org/Class_Reference/WP_Admin_Bar/add_menu
     * @param \WP_Admin_Bar $bar
     * @return void
     * @since 1.0.0
     * @uses admin_url()
     */
    public static function admin_menu_bar( \WP_Admin_Bar $bar ) {
        $bar->add_node( [
            'id'     => 'odwpdl-adminbar_item',
            'href'   => admin_url( 'tools.php?page=' . DL_SLUG . '-log' ),
            'parent' => 'top-secondary',
            'title'  => '<span class="ab-icon"></span>',
            'meta'   => [
                'title' => __( 'Show Debug Log Viewer', DL_SLUG ),
            ],
        ] );
    }

    /**
     * Hook for "admin_enqueue_scripts" action.
     *
     * @param string $hook
     * @return void
     * @since 1.0.0
     * @uses plugins_url()
     * @uses wp_enqueue_script()
     * @uses wp_enqueue_style()
     * @uses wp_localize_script()
     */
    public static function admin_enqueue_scripts( string $hook ) {

        // Include JavaScript
        wp_enqueue_script( DL_SLUG, plugins_url( 'assets/js/admin.js', DL_FILE ), ['jquery'] );
        wp_localize_script( DL_SLUG, 'odwpdl', [
            // Put variables you want to pass into JS here...
        ] );

        // Include stylesheet
        wp_enqueue_style( DL_SLUG, plugins_url( 'assets/css/admin.css', DL_FILE ) );

        // Call this hook for all screens of the plugin
        self::screens_call_method( 'admin_enqueue_scripts', $hook );
    }

    /**
     * Create (or erase existing) `debug.log` file.
     *
     * @return bool
     * @since 1.0.0
     */
    public static function create_log_file() : bool {
        return copy( DL_LOG, DL_LOG . '.bak' ) &&
               unlink( DL_LOG ) &&
               ( file_put_contents( DL_LOG, '' ) !== false );
    }

    /**
     * Check environment we're running and prints admin messages if needed.
     *
     * @return void
     * @since 1.0.0
     * @uses add_action()
     */
    public static function check_environment() {

        // Firstly we check if `debug.log` file exists
        if ( ! file_exists( DL_LOG ) ) {

            // File doesn't exist. We will try create it.
            if ( self::create_log_file() === true ) {

            	// Print admin notice
                add_action( 'admin_notices', function() {
                    $msg = sprintf( __( '<strong>Debug Log Viewer</strong>: File (<code>%s</code>) was successfully created.', DL_SLUG ), DL_LOG );
                    DL_Plugin::print_admin_notice( $msg, 'success' );
                } );
            } else {

	            // Print admin notice
                add_action( 'admin_notices', function() {
                    $msg = sprintf( __( '<strong>Debug Log Viewer</strong>: File (<code>%s</code>) doesn\'t exist and can\'t be created. Create it on your own.', DL_SLUG ), DL_LOG );
                    DL_Plugin::print_admin_notice( $msg, 'warning', true );
                } );
            }
        } else if ( ! is_writable( DL_LOG ) ) {

            // File exists but is not writable
            add_action( 'admin_notices', function() {
                $msg = sprintf( __( '<strong>Debug Log Viewer</strong>: File (<code>%s</code>) exists but is not writeable.', DL_SLUG ), DL_LOG );
                DL_Plugin::print_admin_notice( $msg );
            } );
        }

        // Setting of WP_DEBUG|WP_DEBUG_LOG constants is wrong
        $err_msg = sprintf( __( 'If you want to write log records into (<code>%s</code>) file you need to set PHP constants <code>WP_DEBUG</code> and <code>WP_DEBUG_LOG</code> on value <code>TRUE</code>.', DL_SLUG ), DL_LOG );

        if ( ! defined( 'WP_DEBUG' ) || ! defined( 'WP_DEBUG_LOG' ) ) {
            add_action( 'admin_notices', function() use ( $err_msg ) {
                self::print_admin_notice( $err_msg, 'error' );
            } );
        }

        if ( WP_DEBUG != true || WP_DEBUG_LOG != true ) {
            add_action( 'admin_notices', function() use ( $err_msg ) {
                self::print_admin_notice( $err_msg, 'error' );
            } );
        }
    }

    /**
     * Load specified template with given arguments.
     *
     * @param string $template
     * @param array  $args (Optional.)
     * @return string Output created by rendering template.
     * @since 1.0.0
     */
    public static function load_template( string $template, array $args = [] ) : string {

    	// Extract all arguments as variables for the template
        extract( $args );

        // Get template's path
        $path = sprintf( '%spartials/%s.phtml', DL_PATH, $template );

        // Check if path is exist
        if ( ! file_exists( $path ) ) {
            return '';
        }

        // Render and return template
        ob_start( function() {} );
        include( $path );

        return ob_get_flush();
    }

    /**
     * Hook for "plugins_loaded" action.
     *
     * @return void
     * @since 1.0.0
     */
    public static function plugins_loaded() {
        //...
    }

    /**
     * Hook for "wp_enqueue_scripts" action.
     *
     * @return void
     * @since 1.0.0
     * @uses plugins_url()
     * @uses wp_enqueue_script()
     * @uses wp_enqueue_style()
     * @uses wp_localize_script()
     */
    public static function enqueue_scripts() {
        $js_file = 'assets/js/public.js';
        $js_path = DL_FILE . $js_file;

        if ( file_exists( $js_path ) && is_readable( $js_path ) ) {
            wp_enqueue_script( DL_SLUG, plugins_url( $js_file, DL_FILE ), ['jquery'] );
            wp_localize_script( DL_SLUG, 'odwpdl', [
                // Put variables you want to pass into JS here...
            ] );
        }

        $css_file = 'assets/css/public.css';
        $css_path = DL_FILE . $css_file;

        if ( file_exists( $css_path ) && is_readable( $css_path ) ) {
            wp_enqueue_style( DL_SLUG, plugins_url( $css_file, DL_FILE ) );
        }
    }

    /**
     * Render the first settings section.
     *
     * @return void
     * @since 1.0.0
     */
    public static function render_settings_section_1() {
        echo self::load_template( 'setting-section_1' );
    }

    /**
     * Render setting `debug_mode`.
     *
     * @return void
     * @since 1.0.0
     */
    public static function render_setting_debug_mode() {
        echo self::load_template( 'setting-debug_mode', [
            'debug_mode' => self::get_option( 'debug_mode' ),
        ] );
    }

    /**
     * Uninstall hook.
     *
     * @return void
     * @since 1.0.0
     */
    public static function uninstall() {

        if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            return;
        }

        //...
    }

    /**
     * Print error message in correct WP admin style.
     *
     * @param string $msg Error message.
     * @param string $type (Optional.) One of ['error','info','success','warning'].
     * @param bool $dismissible (Optional.) Is notice dismissible?
     * @return void
     * @since 1.0.0
     */
    public static function print_admin_notice( $msg, $type = 'info', bool $dismissible = true ) {
        $class = 'notice';

        if ( in_array( $type, ['error','info','success','warning'] ) ) {
            $class .= ' notice-' . $type;
        } else {
            $class .= ' notice-info';
        }

        if ( $dismissible === true) {
            $class .= ' s-dismissible';
        }

        printf( '<div class="%s"><p>%s</p></div>', $class, $msg );
    }

    /**
     * On all screens call method with given name.
     *
     * Used for calling hook's actions of the existing screens.
     * See {@see DL_Plugin::admin_menu} for an example how is used.
     *
     * If method doesn't exist in the screen object it means that screen
     * do not provide action for the hook.
     *
     * @param string $method
     * @param mixed $args
     * @return void
     * @since 1.0.0
     */
    private static function screens_call_method( string $method, $args = null ) {

        // Go through all screens and call specified method if it exists.
        foreach ( self::$admin_screens as $slug => $screen ) {
            if ( method_exists( $screen, $method ) ) {
                call_user_func( [ $screen, $method ], $args );
            }
        }
    }
}

endif;
