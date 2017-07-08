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
     * @var array
     * @since 1.0.0
     */
    protected $help_tabs = [];

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
     * @var array
     * @since 1.0.0
     */
    protected $help_sidebars = [];

    /**
     * <p>Array with screen options. Don't forget that you can use screen options only when {@see DL_Screen_Prototype::$enable_screen_options} is set on <code>TRUE</code>. You can define them like this:</p>
     * <pre>
     * $this->options[$this->slug . '-option1'] = [
     *     'label'   => __( 'The first option', 'textdomain' ),
     *     'default' => 'default',
     *     'option'  => $this->slug . '-option1',
     * ];
     * </pre>
     */
    protected $options = [];

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
     * <p>Returns current screen options. Here is an example code how to retrieve existing screen options:</p>
     * <pre>
     * $screen  = $this->get_screen();
     * $user    = get_current_user_id();
     * $option1 = get_user_meta( $user, $this->slug . '-option1', true );
     *
     * if( strlen( $option1 ) == 0 ) {
     *     $option1 = $screen->get_option( $this->slug . '-option1', 'default' );
     * }
     *
     * return [
     *     'option1' => $option1,
     * ];
     * </pre>
     *
     * @return array
     * @since 1.0.0
     * @todo Make this automatic without need of writing own code - just use {@see DL_Screen_Prototype::$options}.
     */
    public function get_screen_options() {
        if( $this->enable_screen_options !== true ) {
            return [];
        }

        // ...
    }

    /**
     * @internal Updates option with given value.
     * @param string $key Option's key.
     * @param mixed $value Option's value.
     * @return void
     * @since 1.0.0
     */
    protected function update_option( $key, $value ) {
        if( $this->enable_screen_options !== true ) {
            return;
        }

        $options = DL_Plugin::get_options();
        $need_update = false;

        if( ! array_key_exists( $key, $options ) ) {
            $need_update = true;
        }

        if( ! $need_update && $options[$key] != $value ) {
            $need_update = true;
        }

        if( $need_update === true) {
            $options[$key] = $value;
            update_option( $key, $value );
        }
    }

    /**
     * Action for `init` hook.
     * @return void
     * @since 1.0.0
     */
    public function init() {
        add_action( 'admin_init', [$this, 'save_screen_options'] );
    }

    /**
     * Action for `admin_init` hook.
     * @return void
     * @since 1.0.0
     */
    public function admin_init() {
        // ...
    }

    /**
     * Action for `init` hook.
     * @return void
     */
    public function admin_enqueue_scripts() {
        // ...
    }

    /**
     * Action for `admin_head` hook.
     * @return void
     * @since 1.0.0
     */
    public function admin_head() {
        // ...
    }

    /**
     * Action for `admin_menu` hook.
     * @return void
     * @since 1.0.0
     */
    abstract public function admin_menu();

    /**
     * Creates screen help and add filter for screen options. Action 
     * for `load-{$hookname}` hook (see {@see DL_Screen_Prototype::admin_menu} 
     * for more details).
     * @return void
     * @since 1.0.0
     */
    public function screen_load() {
        $screen = $this->get_screen();

        // Screen help
        foreach( $this->help_tabs as $tab ) {
            $screen->add_help_tab( $tab );
        }

        foreach( $this->help_sidebars as $sidebar ) {
            $screen->set_help_sidebar( $sidebar );
        }

        // Screen options
        if( $this->enable_screen_options === true ) {
            add_filter( 'screen_layout_columns', [$this, 'screen_options'] );

            foreach( $this->options as $option_key => $option_props ) {
                if( ! empty( $option_key ) && is_array( $option_props ) ) {
                    $screen->add_option( $option_key, $option_props );
                }
            }
        }
    }

    /**
     * <p>Renders screen options form. Handler for `screen_layout_columns` filter (see {@see DL_Screen_Prototype::screen_load}).</p>
     * @return void
     * @since 1.0.0
     * @todo It should be rendered automatically by using {@see DL_Screen_Prototype::$options}.
     */
    public function screen_options() {
        if( $this->enable_screen_options !== true ) {
            return;
        }

        // These are used in the template:
        $slug = $this->slug;
        $screen = $this->get_screen();
        extract( $this->get_screen_options() );

        ob_start();
        include( DL_PATH . "partials/screen-{$this->slug}_options.phtml" );
        $output = ob_get_clean();

        /**
         * Filter for screen options form.
         *
         * @param string $output Rendered HTML.
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
     * @todo It should be done automatically by using {@see DL_Screen_Prototype::$options} without need of writing own code.
     */
    public function save_screen_options() {
        if( $this->enable_screen_options !== true ) {
            return;
        }

        // ...
    }

    /**
     * Render page self.
     * @param array $args (Optional.) Arguments for rendered template.
     * @return void
     * @since 1.0.0
     */
    public function render( $args = [] ) {
        // These are used in the template:
        $slug = $this->slug;
        $screen = $this->get_screen();
        extract( $this->get_screen_options() );
        extract( is_array( $args ) ? $args : [] );

        ob_start();
        include( DL_PATH . 'partials/screen-' . str_replace( DL_SLUG . '-', '', $this->slug ) . '.phtml' );
        $output = ob_get_clean();

        /**
         * Filter for whole rendered screen.
         *
         * @param string $output Rendered HTML.
         */
        $output = apply_filters( DL_SLUG . "_{$this->slug}", $output );
        echo $output;
    }
}

endif;
