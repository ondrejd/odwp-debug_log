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

if ( ! class_exists( 'DL_Log_Dashboard_Widget' ) ) :

/**
 * Log dashboard widget.
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @since 1.0.0
 */
class DL_Log_Dashboard_Widget {

    /**
     * @since 1.0.0
     * @var string ID of the widget.
     */
    const WID = DL_SLUG . '-log_dashboard_widget';

    /**
     * @since 1.0.0
     * @var string Options key.
     */
    const OPTIONS_KEY = self::WID . '-options';

    /**
     * Hook to `wp_dashboard_setup` to add the widget.
     *
     * @return void
     * @see DL_Plugin::admin_init()
     * @since 1.0.0
     * @uses wp_add_dashboard_widget()
     */
    public static function init() {
        // Register widget settings
        self::update_dashboard_widget_options(
            self::WID, self::get_default_options(), true
        );

        // Register the widget
        wp_add_dashboard_widget(
            self::WID,
            __( 'Debug Log', DL_SLUG ),
            [__CLASS__, 'widget'],
            [__CLASS__, 'config']
        );
    }

    /**
     * Return default options for the widget.
     *
     * @return array
     * @since 1.0.0
     */
    public static function get_default_options() : array {
        return [
            'visible_lines' => 20,
            'table_style'   => 'default',
            'enable_clear'  => true
        ];
    }

    /**
     * Load the widget code.
     *
     * @return void
     * @see DL_Log_Dashboard_Widget::init()
     * @since 1.0.0
     */
    public static function widget() {
        echo DL_Plugin::load_template( 'dashboard_widget-log', [] );
    }

    /**
     * Load widget config code.
     *
     * @return void
     * @see DL_Log_Dashboard_Widget::init()
     * @since 1.0.0
     */
    public static function config() {
        echo DL_Plugin::load_template( 'dashboard_widget-log_config', [] );
    }

    /**
     * Get the options for a widget of the specified name.
     *
     * @param string $widget_id Optional. If provided, will only get options for the specified widget.
     * @return array An associative array containing the widget's options and values. Empty array if no opts.
     * @since 1.0.0
     */
    public static function get_dashboard_widget_options( string $widget_id = '' ) : array {
        $opts = get_option( self::OPTIONS_KEY );

        // If no widget is specified, return everything
        if ( empty( $widget_id ) ) {
            return $opts;
        }

        // If we request a widget and it exists, return it
        if ( isset( $opts[$widget_id] ) ) {
            return $opts[$widget_id];
        }

        // Something went wrong...
        return [];
    }

    /**
     * Get one specific option for the specified widget.
     *
     * @param string $widget_id
     * @param string $option
     * @param mixed $default
     * @return mixed Return value if found, default value if real value is not found and default is set. Return FALSE in all other cases.
     * @since 1.0.0
     */
    public static function get_dashboard_widget_option( string $widget_id, string  $option, $default = null ) {
        $opts = self::get_dashboard_widget_options( $widget_id );

        // If widget opts dont exist, return false
        if ( count( $opts ) === 0 ) {
            return false;
        }

        // Otherwise fetch the option or use default
        if ( isset( $opts[$option] ) && ! empty( $opts[$option] ) ) {
            return $opts[$option];
        } else {
            return ( isset( $default ) ) ? $default : false;
        }
    }

    /**
     * Save an array of options for a single dashboard widget to the database.
     * Can also be used to define default values for a widget.
     *
     * @param string $widget_id The name of the widget being updated
     * @param array $args An associative array of options being saved.
     * @param bool $add_only If true, options will not be added if widget options already exist
     * @return bool
     * @since 1.0.0
     */
    public static function update_dashboard_widget_options( string $widget_id , array $args = [], bool $add_only = false ) : bool {
        // Fetch ALL dashboard widget options from the db...
        $opts = get_option( DL_SLUG . '-dashboard_widget_options' );

        // Get just our widget's options, or set empty array
        $w_opts = ( isset( $opts[$widget_id] ) ) ? $opts[$widget_id] : [];

        if ( $add_only ) {
            // Flesh out any missing options (existing ones overwrite new ones)
            $opts[$widget_id] = array_merge( $args,$w_opts );
        }
        else {
            // Merge new options with existing ones, and add it back to the widgets array
            $opts[$widget_id] = array_merge( $w_opts,$args );
        }

        // Save the entire widgets array back to the db
        return update_option( DL_SLUG . '-dashboard_widget_options', $opts );
    }
}

endif;
