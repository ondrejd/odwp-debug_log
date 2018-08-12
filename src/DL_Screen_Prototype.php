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

if ( ! class_exists( 'DL_Screen_Prototype' ) ) :

/**
 * Prototype class for administration screens.
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @since 1.0.0
 */
abstract class DL_Screen_Prototype {

    /**
     * @since 1.0.0
     * @var string $slug
     */
    protected $slug;

    /**
     * @since 1.0.0
     * @var string $page_title
     */
    protected $page_title;

    /**
     * @since 1.0.0
     * @var string $menu_title
     */
    protected $menu_title;

    /**
     * @since 1.0.0
     * @var \WP_Screen $screen
     */
    protected $screen;

    /**
     * Array with tabs for screen help. Single tab can be defined by code like this:
     *
     * <pre>
     * $this->help_tabs[] = [
     *     'id'      => $this->slug . '-help_tab',
     *     'title'   => __( 'Screen help', 'textdomain' ),
     *     'content' => sprintf( __( '<h4>Screen help</h4><p>Some help provided by your plugin...</p>', 'textdomain' ) ),
     * ];
     * </pre>
     *
     * @since 1.0.0
     * @var array $help_tabs
     */
    protected $help_tabs = [];

    /**
     * Array with sidebars for screen help. Sidebar can be defined by code like this:
     *
     * <pre>
     * $this->help_sidebars[] = sprintf(
     *     _( '<b>Useful links</b>' .
     *        '<p><a href="%1$s" target="blank">Link 1</a> is the first link.</p>' .
     *        '<p><a href="%2$s" target="blank">Link 2</a> is the second link.</p>' .
     *        '<p><a href="%3$s" target="blank">Link 3</a> is the third link.</p>',
     *        'textdomain' ),
     *     '#',
     *     '#',
     *     '#'
     * );</pre>
     *
     * @since 1.0.0
     * @var array $help_sidebars
     */
    protected $help_sidebars = [];

    /**
     * Array with screen options - they are saved as user meta values. Don't forget that you can use screen options 
     * only when {@see DevHelper_Screen_Prototype::$enable_screen_options} is set on <code>TRUE</code>.
     * 
     * You can define them like this:
     * 
     * <pre>
     * $this->options[$this->slug . '-option1'] = [
     *     'default' => 'default',
     *     'label'   => __( 'The first option', 'textdomain' ),
     *     'option'  => $this->slug . '-option1',
     *     'type'    => 'string', // ['boolean', 'integer', 'real', 'string']
     * ];
     * </pre>
     *
     * @since 1.0.0
     * @var array $options
     */
    protected $options = [];

    /**
     * If this is set to <code>FALSE</code> these methods will be omitted:
     * 
     * <ul>
     *   <li>{@see DL_Screen_Prototype::get_screen_options()}</li>
     *   <li>{@see DL_Screen_Prototype::save_screen_options()}</li>
     *   <li>{@see DL_Screen_Prototype::screen_options()}</li>
     * </ul>
     *
     * @since 1.0.0
     * @var bool $enable_screen_options
     */
    protected $enable_screen_options = false;

    /**
     * @since 1.0.0
     * @var string $hookname Name of the admin menu page hook.
     */
    protected $hookname;

    /**
     * Constructor.
     * 
     * @param \WP_Screen $screen Optional.
     * @return void
     * @since 1.0.0
     */
    public function __construct( \WP_Screen $screen = null ) {
        $this->screen = $screen;
    }

    /**
     * Return screen's slug.
     * 
     * @return string
     * @since 1.0.0
     */
    public function get_slug() {
        return $this->slug;
    }

    /**
     * Return screen's page title.
     * 
     * @return string
     * @since 1.0.0
     */
    public function get_page_title() {
        return $this->page_title;
    }

    /**
     * Return screen's menu title.
     * 
     * @return string 
     * @since 1.0.0
     */
    public function get_menu_title() {
        return $this->menu_title;
    }

    /**
     * Return instance of screen self.
     *
     * @return \WP_Screen
     * @since 1.0.0
     * @uses get_current_screen()
     */
    public function get_screen() {

        if ( ! ( $this->screen instanceof \WP_Screen )) {
            $this->screen = get_current_screen();
        }

        return $this->screen;
    }

    /**
     * Returns current screen options.
     *
     * @return array
     * @since 1.0.0
     * @uses get_current_user_id()
     * @uses get_user_meta()
     */
    public function get_screen_options() : array {

        if ( $this->enable_screen_options !== true ) {
            return [];
        }

        $screen = $this->get_screen();
        $user = get_current_user_id();
        $opts = [];

        // Go through all pre-defined screen options and collect them including values
        foreach ( $this->options as $option_key => $option_props ) {
            $full_option_key = $this->slug . '-' . $option_key;
            $option_val = get_user_meta( $user, $full_option_key, true );

            // If option's value is not defined get the default value
            if ( strlen( $option_val ) == 0 ) {
                $option_val = $screen->get_option( $full_option_key, 'default' );
            }

            $opts[$option_key] = $option_val;
        }

        return $opts;
    }

    /**
     * Action for `init` hook.
     * 
     * @return void
     * @since 1.0.0
     */
    public function init() {
        // ...
    }

    /**
     * Action for `admin_init` hook.
     * 
     * @return void
     * @since 1.0.0
     */
    public function admin_init() {
        $this->save_screen_options();
    }

    /**
     * Action for `init` hook.
     * 
     * @return void
     * @since 1.0.0
     */
    public function admin_enqueue_scripts() {
        // ...
    }

    /**
     * Action for `admin_head` hook.
     * 
     * @return void
     * @since 1.0.0
     */
    public function admin_head() {
        // ...
    }

    /**
     * Action for `admin_menu` hook.
     * 
     * @return void
     * @since 1.0.0
     */
    abstract public function admin_menu();

    /**
     * Creates screen help and add filter for screen options. Action for `load-{$hookname}` hook.
     * 
     * @return void
     * @since 1.0.0
     */
    public function screen_load() {
        $screen = $this->get_screen();

        // Screen help

        // Help tabs
        foreach ( $this->help_tabs as $tab ) {
            $screen->add_help_tab( $tab );
        }

        // Help sidebars
        foreach ( $this->help_sidebars as $sidebar ) {
            $screen->set_help_sidebar( $sidebar );
        }

        // Screen options
        if ( $this->enable_screen_options === true ) {
            add_filter( 'screen_layout_columns', array( $this, 'screen_options' ) );

            foreach ( $this->options as $option_key => $option_props ) {
                if ( ! empty( $option_key ) && is_array( $option_props ) ) {
                    $screen->add_option( $option_key, $option_props );
                }
            }
        }
    }

    /**
     * Render screen options form. Handler for `screen_layout_columns` filter (see {@see DevHelper_Screen_Prototype::screen_load}).
     * 
     * @param array $additional_template_args Optional.
     * @return void
     * @since 1.0.0
     * @todo In WordPress Dashboard screen options there is no apply button and all is done by AJAX - it would be nice to have this the same.
     * @uses apply_filters()
     */
    public function screen_options( array $additional_template_args = [] ) {
        if ( $this->enable_screen_options !== true ) {
            return;
        }

        // These are used in the template
	    $slug = $this->slug;
	    $screen = $this->get_screen();
        $args = array_merge( $this->get_screen_options(), $additional_template_args );
        extract( $args );

        ob_start( function() {} );
        $template = str_replace( DL_SLUG . '-', '', "screen-{$this->slug}_options.phtml" );
        include( DL_PATH . "partials/{$template}" );
        $output = ob_get_clean();

        /**
         * Filter for screen options form.
         *
         * @param string $output Rendered HTML.
         * @since 1.0.0
         */
        $output = apply_filters( DL_SLUG . "_{$this->slug}_screen_options_form", $output );
        echo $output;
    }

    /**
     * Save screen options. Action for `admin_init` hook (see {@see DL_Screen_Prototype::init} for more details).
     * Here is an example code how to save a screen option:
     *
     * <pre>
     * $user = get_current_user_id();
     *
     * if (
     *         filter_input( INPUT_POST, $this->slug . '-submit' ) &&
     *         (bool) wp_verify_nonce( filter_input( INPUT_POST, $this->slug . '-nonce' ) ) === true
     * ) {
     *     $option1 = filter_input( INPUT_POST, $this->slug . '-option1' );
     *     update_user_meta( $user, $this->slug . '-option1', $option1 );
     * }
     * </pre>
     *
     * @return void
     * @since 1.0.0
     * @uses get_current_user_id()
     * @uses wp_verify_nonce()
     * @uses update_user_meta()
     */
    public function save_screen_options() {

        if ( $this->enable_screen_options !== true ) {
            return;
        }

        // Check if screen options are saved and NONCE
        $submit = filter_input( INPUT_POST, $this->slug . '-screen_options_submit' );
        $nonce = filter_input( INPUT_POST, $this->slug . '-screen_options_nonce' );

		if ( ! ( $submit && ( bool ) wp_verify_nonce( $nonce ) === true ) ) {
            return;
        }

        // Get current user's ID
        $user = get_current_user_id();

		// User was not found!!!
        if ( empty( $user ) ) {
            return;
        }

        // Update all screen options
        foreach ( $this->options as $option_key => $option_props ) {
            if ( ! empty( $option_key ) && is_array( $option_props ) ) {
                $full_option_key = $this->slug . '-' . $option_key;

                if ( $option_props['type'] == 'boolean' ) { // e.g. checkbox in HTML
                    $val = ( string ) filter_input( INPUT_POST, $full_option_key );
                    $val = ( strtolower( $val ) == 'on' ) ? 1 : 0;
                } else { // e.g. other inputs
                    $val = ( string ) filter_input( INPUT_POST, $full_option_key );
                }

                update_user_meta( $user, $full_option_key, $val );
            }
        }
    }

    /**
     * Render page self.
     * 
     * @param mixed $args (Optional.) Array with arguments for rendered template.
     * @return void
     * @since 1.0.0
     * @uses apply_filters()
     */
    public function render( $args = [] ) {

        // Check arguments
        if ( ! is_array( $args ) ) {
            $args = [];
        }

        // These are used in the template:
        $slug = $this->slug;
        $screen = $this->get_screen();
        extract( array_merge( $this->get_screen_options(), $args ) );

        ob_start( function() {} );
        include( DL_PATH . 'partials/screen-' . str_replace( DL_SLUG . '-', '', $this->slug ) . '.phtml' );
        $output = ob_get_clean();

        /**
         * Filter for whole rendered screen.
         *
         * @param string $output Rendered HTML.
         * @since 1.0.0
         */
        $output = apply_filters( DL_SLUG . "_{$this->slug}", $output );
        echo $output;
    }
}

endif;
