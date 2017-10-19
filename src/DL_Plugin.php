<?php
/**
 * @author Ondrej Donek <ondrejd@gmail.com>
 * @link https://github.com/ondrejd/odwp-debug_log for the canonical source repository
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @package odwp-debug_log
 * @since 1.0.0
 */

if( ! defined( 'ABSPATH' ) ) {
    exit;
}

if( ! class_exists( 'DL_Plugin' ) ) :

/**
 * Main class.
 * @since 1.0.0
 */
class DL_Plugin {

    /**
     * @const string
     * @since 1.0.0
     */
    const SETTINGS_KEY = DL_SLUG . '_settings';

    /**
     * @const string
     * @since 1.0.0
     */
    const TABLE_NAME = DL_SLUG;

    /**
     * @var array $admin_screens Array with admin screens.
     * @since 1.0.0
     */
    public static $admin_screens = [];

    /**
     * @var string
     * @since 1.0.0
     */
    public static $options_page_hook;

    /**
     * @internal Activates the plugin.
     * @return void
     * @since 1.0.0
     */
    public static function activate() {
        //...
    }

    /**
     * @internal Deactivates the plugin.
     * @return void
     * @since 1.0.0
     */
    public static function deactivate() {
        //...
    }

    /**
     * @return array Default values for settings of the plugin.
     * @since 1.0.0
     */
    public static function get_default_options() {
        return [
            'debug_mode' => 'disable', // ['enable','disable']
            'prev_log_count' => 0,
        ];
    }

    /**
     * @return array Settings of the plugin.
     * @since 1.0.0
     */
    public static function get_options() {
        $defaults = self::get_default_options();
        $options = get_option( self::SETTINGS_KEY, [] );
        $update = false;

        // Fill defaults for the options that are not set yet
        foreach( $defaults as $key => $val ) {
            if( ! array_key_exists( $key, $options ) ) {
                $options[$key] = $val;
                $update = true;
            }
        }

        // Updates options if needed
        if( $update === true) {
            update_option( self::SETTINGS_KEY, $options );
        }

        return $options;
    }

    /**
     * Returns value of option with given key.
     * @param string $key Option's key.
     * @param mixed $default Option's default value.
     * @return mixed Option's value.
     * @since 1.0.0
     */
    public static function get_option( $key, $default = null ) {
        $options = self::get_options();

        if( array_key_exists( $key, $options ) ) {
            return $options[$key];
        }

        return $default;
    }

    /**
     * Initializes the plugin.
     * @return void
     * @since 1.0.0
     */
    public static function initialize() {
        register_activation_hook( DL_FILE, [__CLASS__, 'activate'] );
        register_deactivation_hook( DL_FILE, [__CLASS__, 'deactivate'] );
        register_uninstall_hook( DL_FILE, [__CLASS__, 'uninstall'] );

        add_action( 'init', [__CLASS__, 'init'] );
        add_action( 'admin_init', [__CLASS__, 'admin_init'] );
        add_action( 'admin_menu', [__CLASS__, 'admin_menu'] );
        add_action( 'admin_bar_menu', [__CLASS__, 'admin_menu_bar'], 100 );
        add_action( 'plugins_loaded', [__CLASS__, 'plugins_loaded'] );
        add_action( 'wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts'] );
        add_action( 'admin_enqueue_scripts', [__CLASS__, 'admin_enqueue_scripts'] );
    }

    /**
     * Hook for "init" action.
     * @return void
     * @since 1.0.0
     */
    public static function init() {
        // Initialize locales
        $path = DL_PATH . 'languages';
        load_plugin_textdomain( DL_SLUG, false, $path );

        // Initialize options
        $options = self::get_options();

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
     * @return void
     * @since 1.0.0
     */
    public static function init_custom_post_types() {
        //...
    }

    /**
     * Registers our shortcodes.
     * @return void
     * @since 1.0.O
     */
    public static function init_shortcodes() {
        //...
    }

    /**
     * Initialize settings using <b>WordPress Settings API</b>.
     * @link https://developer.wordpress.org/plugins/settings/settings-api/
     * @return void
     * @since 1.0.0
     */
    protected static function init_settings() {
        $section1 = self::SETTINGS_KEY . '_section_1';
        add_settings_section(
                $section1,
                __( 'Nastavení ladícího módu', DL_SLUG ),
                [__CLASS__, 'render_settings_section_1'],
                DL_SLUG
        );

        add_settings_field(
                'debug_mode',
                __( 'Povolit ladící mód', DL_SLUG ),
                [__CLASS__, 'render_setting_debug_mode'],
                DL_SLUG,
                $section1
        );
    }

    /**
     * Initialize admin screens.
     * @return void
     * @since 1.0.0
     */
    protected static function init_screens() {
        include( DL_PATH . 'src/DL_Screen_Prototype.php' );
        include( DL_PATH . 'src/DL_Options_Screen.php' );
        include( DL_PATH . 'src/DL_Log_Screen.php' );

        /**
         * @var DL_Options_Screen $options_screen
         */
        $options_screen = new DL_Options_Screen();
        self::$admin_screens[$options_screen->get_slug()] = $options_screen;

        /**
         * @var DL_Log_Screen $log_screen
         */
        $log_screen = new DL_Log_Screen();
        self::$admin_screens[$log_screen->get_slug()] = $log_screen;
    }

    /**
     * Hook for "admin_init" action.
     * @return void
     * @since 1.0.0
     */
    public static function admin_init() {
        register_setting( DL_SLUG, self::SETTINGS_KEY );

        self::check_environment();
        self::init_settings();
        self::screens_call_method( 'admin_init' );
        self::admin_init_widgets();
    }

    /**
     * @internal Initializes WP admin dashboard widgets.
     * @return void
     * @since 1.0.0
     */
    public static function admin_init_widgets() {
        include( DL_PATH . 'src/DL_Log_Dashboard_Widget.php' );
        add_action( 'wp_dashboard_setup', ['DL_Log_Dashboard_Widget', 'init'] );
    }

    /**
     * Hook for "admin_menu" action.
     * @return void
     * @since 1.0.0
     */
    public static function admin_menu() {
        // Call action for `admin_menu` hook on all screens.
        self::screens_call_method( 'admin_menu' );
    }

    /**
     * Hook for "admin_menu_bar" action.
     * @link https://codex.wordpress.org/Class_Reference/WP_Admin_Bar/add_menu
     * @param \WP_Admin_Bar $bar
     * @return void
     * @since 1.0.0
     */
    public static function admin_menu_bar( \WP_Admin_Bar $bar ) {
        $bar->add_node( [
            'id'     => 'odwpdl-adminbar_item',
            'href'   => admin_url( 'tools.php?page=' . DL_SLUG . '-log' ),
            'parent' => 'top-secondary',
            'title'  => '<span class="ab-icon"></span>',
            'meta'   => [
                'title' => __( 'Přejít na zobrazení ladících informací.', DL_LOG ),
            ],
        ] );
    }

    /**
     * Hook for "admin_enqueue_scripts" action.
     * @param string $hook
     * @return void
     * @since 1.0.0
     */
    public static function admin_enqueue_scripts( $hook ) {
        $js_file = 'assets/js/admin.js';
        $js_path = DL_PATH . $js_file;

        if( file_exists( $js_path ) && is_readable( $js_path ) ) {
	    wp_enqueue_script( DL_SLUG, plugins_url( $js_file, DL_FILE ), ['jquery'] );
            wp_localize_script( DL_SLUG, 'odwpdl', [
                // Put variables you want to pass into JS here...
            ] );
        }

        $css_file = 'assets/css/admin.css';
        $css_path = DL_PATH . $css_file;

        if( file_exists( $css_path ) && is_readable( $css_path ) ) {
            wp_enqueue_style( DL_SLUG, plugins_url( $css_file, DL_FILE ) );
        }

        self::screens_call_method( 'admin_enqueue_scripts' );
    }

    /**
     * Checks environment we're running and prints admin messages if needed.
     * @return void
     * @since 1.0.0
     */
    public static function check_environment() {
        if( ! file_exists( DL_LOG ) || ! is_writable( DL_LOG ) ) {
            add_action( 'admin_notices', function() {
                $msg = sprintf(
                        __( '<strong>Debug Log Viewer</strong>: Soubor (<code>%s</code>) k zápisu ladících informací není vytvořen nebo není zapisovatelný. Pro více informací přejděte na <a href="%s">nastavení tohoto pluginu</a>.', DL_SLUG ),
                        DL_LOG,
                        admin_url( 'options-general.php?page=' . DL_SLUG . '-plugin_options' )
                );
                DL_Plugin::print_admin_notice( $msg );
            } );
        }

        /**
         * @var string $err_msg Error message about setting WP_DEBUG and WP_DEBUG_LOG constants.
         */
        $err_msg = sprintf(
                __( 'Pro umožnění zápisu ladících informací do logovacího souboru (<code>%s</code>) musí být konstanty <code>%s</code> a <code>%s</code> nastaveny na hodnotu <code>TRUE</code>. Pro více informací přejděte na <a href="%s">nastavení tohoto pluginu</a>.', DL_SLUG ),
                DL_LOG,
                'WP_DEBUG',
                'WP_DEBUG_LOG',
                admin_url( 'options-general.php?page=' . DL_SLUG . '-plugin_options' )
        );

        if( ! defined( 'WP_DEBUG' ) || ! defined( 'WP_DEBUG_LOG' ) ) {
            add_action( 'admin_notices', function() use ( $err_msg ) {
                self::print_admin_notice( $err_msg, 'error' );
            } );
        }

        if( WP_DEBUG !== true || WP_DEBUG_LOG !== true ) {
            add_action( 'admin_notices', function() use ( $err_msg ) {
                self::print_admin_notice( $err_msg, 'error' );
            } );
        }
    }

    /**
     * Loads specified template with given arguments.
     * @param string $template
     * @param array  $args (Optional.)
     * @return string Output created by rendering template.
     * @since 1.0.0
     */
    public static function load_template( $template, array $args = [] ) {
        extract( $args );
        $path = sprintf( '%spartials/%s.phtml', DL_PATH, $template );
        ob_start( function() {} );
        include( $path );
        return ob_get_flush();
    }

    /**
     * Hook for "plugins_loaded" action.
     * @return void
     * @since 1.0.0
     */
    public static function plugins_loaded() {
        //...
    }

    /**
     * Hook for "wp_enqueue_scripts" action.
     * @return void
     * @since 1.0.0
     */
    public static function enqueue_scripts() {
        $js_file = 'assets/js/public.js';
        $js_path = DL_FILE . $js_file;

        if( file_exists( $js_path ) && is_readable( $js_path ) ) {
            wp_enqueue_script( DL_SLUG, plugins_url( $js_file, DL_FILE ), ['jquery'] );
            wp_localize_script( DL_SLUG, 'odwpwcchp', [
                // Put variables you want to pass into JS here...
            ] );
        }

        $css_file = 'assets/css/public.css';
        $css_path = DL_FILE . $css_file;

        if( file_exists( $css_path ) && is_readable( $css_path ) ) {
            wp_enqueue_style( DL_SLUG, plugins_url( $css_file, DL_FILE ) );
        }
    }

    /**
     * @internal Renders the first settings section.
     * @return void
     * @since 1.0.0
     */
    public static function render_settings_section_1() {
        echo self::load_template( 'setting-section_1' );
    }

    /**
     * @internal Renders setting `debug_mode`.
     * @return void
     * @since 1.0.0
     */
    public static function render_setting_debug_mode() {
        echo self::load_template( 'setting-debug_mode', [
            'debug_mode' => self::get_option( 'debug_mode' ),
        ] );
    }

    /**
     * @internal Uninstalls the plugin.
     * @return void
     * @since 1.0.0
     */
    public static function uninstall() {
        if( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            return;
        }

        //...
    }

    /**
     * @internal Prints error message in correct WP amin style.
     * @param string $msg Error message.
     * @param string $type (Optional.) One of ['error','info','success','warning'].
     * @param boolean $dismissible (Optional.) Is notice dismissible?
     * @return void
     * @since 1.0.0
     */
    public static function print_admin_notice( $msg, $type = 'info', $dismissible = true ) {
        $class = 'notice';

        if( in_array( $type, ['error','info','success','warning'] ) ) {
            $class .= ' notice-' . $type;
        } else {
            $class .= ' notice-info';
        }

        if( $dismissible === true) {
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
     * @access private
     * @param  string  $method
     * @return void
     * @since 1.0.0
     */
    private static function screens_call_method( $method ) {
        foreach ( self::$admin_screens as $slug => $screen ) {
            if( method_exists( $screen, $method ) ) {
                call_user_func( [ $screen, $method ] );
            }
        }
    }
}

endif;
