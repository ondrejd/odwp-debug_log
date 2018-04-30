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

if( ! class_exists( 'DL_Screen_Prototype' ) ):

/**
 * Prototype class for administration screens.
 * @since 1.0.0
 */
abstract class DL_Screen_Prototype {
    /**
     * @var string $slug
     * @since 1.0.0
     */
    protected $slug;

    /**
     * @var string $page_title
     * @since 1.0.0
     */
    protected $page_title;

    /**
     * @var string $menu_title
     * @since 1.0.0
     */
    protected $menu_title;

    /**
     * @var \WP_Screen $screen
     * @since 1.0.0
     */
    protected $screen;

    /**
     * <p>Array with tabs for screen help. Single tab can be defined by code like this:</p>
     * <pre>
     * $this->help_tabs[] = [
     *     'id'      => $this->slug . '-help_tab',
     *     'title'   => __( 'Screen help', 'textdomain' ),
     *     'content' => sprintf(
     *         __( '<h4>Screen help</h4><p>Some help provided by your plugin...</p>', 'textdomain' )
     *     ),
     * ];
     * </pre>
     *
     * @var array $help_tabs
     * @since 1.0.0
     */
    protected $help_tabs = array();

    /**
     * <p>Array with sidebars for screen help. Sidebar can be defined by code like this:</p>
     * <pre>
     * $this->help_sidebars[] = sprintf(
     *     _( '<b>Usefull links</b>' .
     *        '<p><a href="%1$s" target="blank">Link 1</a> is the first link.</p>' .
     *        '<p><a href="%2$s" target="blank">Link 2</a> is the second link.</p>' .
     *        '<p><a href="%3$s" target="blank">Link 3</a> is the third link.</p>',
     *        'textdomain' ),
     *     '#',
     *     '#',
     *     '#'
     * );</pre>
     *
     * @var array $help_sidebars
     * @since 1.0.0
     */
    protected $help_sidebars = array();

    /**
     * <p>Array with screen options - they are saved as user meta values. Don't forget that you can use screen options only when {@see DevHelper_Screen_Prototype::$enable_screen_options} is set on <code>TRUE</code>. You can define them like this:</p>
     * <pre>
     * $this->options[$this->slug . '-option1'] = [
     *     'default' => 'default',
     *     'label'   => __( 'The first option', 'textdomain' ),
     *     'option'  => $this->slug . '-option1',
     *     'type'    => 'string', // ['boolean', 'integer', 'real', 'string']
     * ];
     * </pre>
     *
     * @var array $options
     * @since 1.0.0
     */
    protected $options = array();

    /**
     * <p>If this is set to <code>FALSE</code> these methods will be omitted:</p>
     * <ul>
     *   <li>{@see DL_Screen_Prototype::get_screen_options()}</li>
     *   <li>{@see DL_Screen_Prototype::save_screen_options()}</li>
     *   <li>{@see DL_Screen_Prototype::screen_options()}</li>
     * </ul>
     *
     * @var boolean $enable_screen_options
     * @since 1.0.0
     */
    protected $enable_screen_options = false;

    /**
     * @internal
     * @var string $hookname Name of the admin menu page hook.
     * @since 1.0.0
     */
    protected $hookname;

    /**
     * Constructor.
     * @param \WP_Screen $screen Optional.
     * @return void
     * @since 1.0.0
     */
    public function __construct( \WP_Screen $screen = null ) {
        $this->screen = $screen;
    }

    /**
     * @return string Screen's slug.
     * @since 1.0.0
     */
    public function get_slug() {
        return $this->slug;
    }

    /**
     * @return string Returns screen's page title.
     * @since 1.0.0
     */
    public function get_page_title() {
        return $this->page_title;
    }

    /**
     * @return string Returns screen's menu title.
     * @since 1.0.0
     */
    public function get_menu_title() {
        return $this->menu_title;
    }

    /**
     * @return \WP_Screen Returns screen self.
     * @since 1.0.0
     */
    public function get_screen() {
        if( ! ( $this->screen instanceof \WP_Screen )) {
            $this->screen = get_current_screen();
        }

        return $this->screen;
    }

    /**
     * <p>Returns current screen options.</p>
     *
     * @return array
     * @since 1.0.0
     * @uses get_current_user_id()
     * @uses get_user_meta()
     */
    public function get_screen_options() {
        if( $this->enable_screen_options !== true ) {
            return array();
        }

        $screen = $this->get_screen();
        $user = get_current_user_id();
        $opts = array();

        // Go through all pre-defined screen options and collect them including values
        foreach( $this->options as $option_key => $option_props ) {
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
        foreach( $this->help_tabs as $tab ) {
            $screen->add_help_tab( $tab );
        }

        // Help sidebars
        foreach( $this->help_sidebars as $sidebar ) {
            $screen->set_help_sidebar( $sidebar );
        }

        // Screen options
        if( $this->enable_screen_options === true ) {
            add_filter( 'screen_layout_columns', array( $this, 'screen_options' ) );

            foreach( $this->options as $option_key => $option_props ) {
                if( ! empty( $option_key ) && is_array( $option_props ) ) {
                    $screen->add_option( $option_key, $option_props );
                }
            }
        }
    }

    /**
     * <p>Renders screen options form. Handler for `screen_layout_columns` filter (see {@see DevHelper_Screen_Prototype::screen_load}).</p>
     * 
     * @param array $additional_template_args Optional.
     * @return void
     * @since 1.0.0
     * @uses apply_filters()
     * 
     * @todo In WordPress Dashboard screen options there is no apply button and all is done by AJAX - it would be nice to have this the same.
     */
    public function screen_options( $additional_template_args = array() ) {
        if( $this->enable_screen_options !== true ) {
            return;
        }

        // These are used in the template:
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
     * <p>Save screen options. Action for `admin_init` hook (see {@see DL_Screen_Prototype::init} for more details). Here is an example code how to save a screen option:</p>
     * <pre>
     * $user = get_current_user_id();
     *
     * if(
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
     */
    public function save_screen_options() {
        if( $this->enable_screen_options !== true ) {
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

        if( empty( $user ) ) {
            return;
        }

        // Collect all screen options
        $opts = array();

        foreach( $this->options as $option_key => $option_props ) {
            if( ! empty( $option_key ) && is_array( $option_props ) ) {
                $full_option_key = $this->slug . '-' . $option_key;

                if( $option_props['type'] == 'boolean' ) { // e.g. checkbox in HTML
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
     * @param array $args (Optional.) Arguments for rendered template.
     * @return void
     * @since 1.0.0
     * @uses apply_filters()
     */
    public function render( $args = array() ) {

        // Check arguments
        if( ! is_array( $args ) ) {
            $args = array();
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
