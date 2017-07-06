<?php
/**
 * @author Ondřej Doněk <ondrejd@gmail.com>
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
     * @const string Plugin's version.
     * @since 1.0.0
     */
    const VERSION = '1.0.0';

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
            'list_style' => 'default', // ['default','short']
            'debug_mode' => 'disable', // ['enable','disable']
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
     * @return mixed Option's value.
     * @since 1.0.0
     * @throws Exception Whenever option with given key doesn't exist.
     */
    public static function get_option( $key ) {
        $options = self::get_options();

        if( ! array_key_exists( $key, $options ) ) {
            throw new Exception( 'Option "'.$key.'" is not set!' );
        }

        return $options[$key];
    }

    /**
     * Initializes the plugin.
     * @return void
     * @since 1.0.0
     */
    public static function initialize() {
        register_activation_hook( __FILE__, [__CLASS__, 'activate'] );
        register_deactivation_hook( __FILE__, [__CLASS__, 'deactivate'] );
        register_uninstall_hook( __FILE__, [__CLASS__, 'uninstall'] );

        add_action( 'init', [__CLASS__, 'init'] );
        add_action( 'admin_init', [__CLASS__, 'admin_init'] );
        add_action( 'admin_menu', [__CLASS__, 'admin_menu'] );
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
                __( 'Obecné nastavení pluginu' ),
                [__CLASS__, 'render_settings_section_1'],
                DL_SLUG
        );

        add_settings_field(
                'list_style',
                __( 'Styl zobrazení', DL_SLUG ),
                [__CLASS__, 'render_setting_list_style'],
                DL_SLUG,
                $section1
        );

        $section2 = self::SETTINGS_KEY . '_section_2';
        add_settings_section(
                $section2,
                __( 'Nastavení debug módu' ),
                [__CLASS__, 'render_settings_section_2'],
                DL_SLUG
        );

        add_settings_field(
                'debug_mode',
                __( 'Povolit debug mód', DL_SLUG ),
                [__CLASS__, 'render_setting_debug_mode'],
                DL_SLUG,
                $section2
        );
    }

    /**
     * Hook for "admin_init" action.
     * @return void
     * @since 1.0.0
     */
    public static function admin_init() {
        register_setting( DL_SLUG, self::SETTINGS_KEY );

        // Check environment
        self::check_environment();

        // Initialize options
        $options = self::get_options();
        self::init_settings();

        // Initialize dashboard widgets
        include( DL_PATH . 'src/DL_Log_Dashboard_Widget.php' );
        add_action( 'wp_dashboard_setup', ['DL_Log_Dashboard_Widget', 'init'] );
    }

    /**
     * Hook for "admin_menu" action.
     * @return void
     * @since 1.0.0
     * @todo We can not initialize screens here because we have more hoooks in there (`admin_init` for example).
     */
    public static function admin_menu() {
        include( DL_PATH . 'src/DL_Log_Record.php' );
        include( DL_PATH . 'src/DL_Screen_Prototype.php' );
        include( DL_PATH . 'src/DL_Options_Screen.php' );
        include( DL_PATH . 'src/DL_Log_Screen.php' );

        /**
         * @var DL_Options_Screen $options_screen
         */
        $options_screen = new DL_Options_Screen();
        self::$admin_screens[] = $options_screen;

        /**
         * @var DL_Log_Screen $log_screen
         */
        $log_screen = new DL_Log_Screen();
        self::$admin_screens[] = $log_screen;

        // Call action for `admin_menu` hook on all screens.
        self::screens_call_method( 'admin_menu' );
    }

    /**
     * Hook for "admin_enqueue_scripts" action.
     * @param string $hook
     * @return void
     * @since 1.0.0
     */
    public static function admin_enqueue_scripts( $hook ) {
        wp_enqueue_script( DL_SLUG, plugins_url( 'js/admin.js', DL_FILE ), ['jquery'] );
        wp_localize_script( DL_SLUG, 'odwpng', [
            //...
        ] );
        wp_enqueue_style( DL_SLUG, plugins_url( 'css/admin.css', DL_FILE ) );
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
            self::print_admin_notice( $err_msg, 'error' );
        }

        if( ! defined( 'WP_DEBUG' ) || ! defined( 'WP_DEBUG_LOG' ) ) {
            self::print_admin_notice( $err_msg, 'error' );
        }
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
        //wp_enqueue_script( DL_SLUG, plugins_url( 'js/public.js', DL_FILE ), ['jquery'] );
        //wp_localize_script( DL_SLUG, 'odwpng', [
        //    //...
        //] );
        //wp_enqueue_style( DL_SLUG, plugins_url( 'css/public.css', DL_FILE ) );
    }

    /**
     * @internal Renders the first settings section.
     * @return void
     * @since 1.0.0
     */
    public static function render_settings_section_1() {
        ob_start( function() {} );
        include( DL_PATH . 'partials/settings-section_1.phtml' );
        echo ob_get_flush();
    }

    /**
     * @internal Renders setting `list_style`.
     * @return void
     * @since 1.0.0
     * @todo In future we should also set who can (user roles) the log.
     */
    public static function render_setting_list_style() {
        $list_style = self::get_option( 'list_style' );
        ob_start( function() {} );
        include( DL_PATH . 'partials/setting-list_style.phtml' );
        echo ob_get_flush();
    }

    /**
     * @internal Renders the second settings section.
     * @return void
     * @since 1.0.0
     */
    public static function render_settings_section_2() {
        ob_start( function() {} );
        include( DL_PATH . 'partials/settings-section_2.phtml' );
        echo ob_get_flush();
    }

    /**
     * @internal Renders setting `notice_borders`.
     * @return void
     * @since 1.0.0
     */
    public static function render_setting_debug_mode() {
        $debug_mode = self::get_option( 'debug_mode' );
        ob_start( function() {} );
        include( DL_PATH . '/partials/setting-debug_mode.phtml' );
        echo ob_get_flush();
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

        // Nothing to do...
    }

    /**
     * @internal Prints error message in correct WP amin style.
     * @param string $msg Error message.
     * @param string $type (Optional.) One of ['error','info','success','warning'].
     * @param boolean $dismissible (Optional.) Is notice dismissible?
     * @return void
     * @since 1.0.0
     */
    protected static function print_admin_notice( $msg, $type = 'info', $dismissible = true ) {
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
