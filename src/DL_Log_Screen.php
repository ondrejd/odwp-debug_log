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

if( ! class_exists( 'DL_Log_Screen' ) ) {
    require_once( DL_PATH . 'src/DL_Log_Screen.php' );
}

if( ! class_exists( 'DL_Log_Screen' ) ) :

/**
 * Administration screen for the log viewer.
 * @since 1.0.0
 */
class DL_Log_Screen extends DL_Screen_Prototype {
    /**
     * Constructor.
     * @param WP_Screen $screen Optional.
     * @return void
     * @since 1.0.0
     */
    public function __construct( \WP_Screen $screen = null ) {
        // Main properties
        $this->slug = DL_SLUG . '-log';
        $this->menu_title = __( 'Ladící informace', DL_SLUG );
        $this->page_title = __( 'Prohlížeč ladících informací', DL_SLUG );

        // Specify help tabs
        $this->help_tabs = [];

        // Specify help sidebars
        $this->help_sidebars = [];

        // Specify screen options
        $this->options[$this->slug . '-show_icons'] = [
            'label'   => __( 'Zobrazit typ záznamu jako ikonu?', 'textdomain' ),
            'default' => true,
            'option'  => $this->slug . '-show_icons',
        ];
        $this->enable_screen_options = true;

        // Finish screen constuction
        parent::__construct( $screen );
    }

    /**
     * Action for `admin_menu` hook.
     * @return void
     * @since 1.0.0
     */
    public function admin_menu() {
        $this->hookname = add_management_page(
                $this->page_title,
                $this->menu_title,
                'manage_options',
                $this->slug,
                [$this, 'render']
        );

        add_action( 'load-' . $this->hookname, [$this, 'screen_load'] );
    }

    /**
     * Returns current screen options.
     * @return array
     * @see DL_Screen_Prototype::get_screen_options()
     * @since 1.0.0
     */
    public function get_screen_options() {
        if( $this->enable_screen_options !== true ) {
            return [];
        }

        $screen = $this->get_screen();
        $user   = get_current_user_id();

        // Option for showing icons in record type column
        $show_icons_key = $this->slug . '-show_icons';
        $show_icons = get_user_meta( $user, $show_icons_key, true );
        if( strlen( $show_icons ) == 0 ) {
            $show_icons = $screen->get_option( $show_icons_key, 'default' );
        }

        return [
            'show_icons' => (bool) $show_icons,
        ];
    }

    /**
     * Save screen options.
     * @return void
     * @see DL_Screen_Prototype::get_screen_options()
     * @since 1.0.0
     * @todo It should be done automatically by using {@see DL_Screen_Prototype::$options} without need of writing own code.
     */
    public function save_screen_options() {
        if( $this->enable_screen_options !== true ) {
            return;
        }

        $user = get_current_user_id();

        if(
                filter_input( INPUT_POST, $this->slug . '-submit' ) &&
                (bool) wp_verify_nonce( filter_input( INPUT_POST, $this->slug . '-nonce' ) ) === true
        ) {
            $show_icons = filter_input( INPUT_POST, $this->slug . '-show_icons' );
            update_user_meta( $user, $this->slug . '-show_icons', ( strtolower( $show_icons ) == 'on' ) ? 1 : 0 );
        }
    }
}

endif;
