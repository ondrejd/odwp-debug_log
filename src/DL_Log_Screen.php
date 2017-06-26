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

if( ! class_exists( 'DL_Log_Screen' ) ):

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
        $this->slug = DL_SLUG . '-log_viewer';
        $this->menu_title = __( 'Log Viewer', DL_SLUG );
        $this->page_title = __( 'Debug Log Viewer', DL_SLUG );

        // Specify help tabs
        $this->help_tabs[] = array(
            'id'      => $this->slug . '-help_tab',
            'title'   => __( 'Tables', DL_SLUG ),
            'content' => __( '<p style="color: #f30;"><code>XXX</code> Fill this screen help!<p>', DL_SLUG ),
        );

        // Specify help sidebars
        $this->help_sidebars[] = sprintf(
            __( '<b>Usefull links</b><p><a href="%1$s" target="blank"><code>WP_List_Table</code></a> on <b>WordPress Codex</b>.</p><p><a href="%2$s" target="blank"><code>WP_List_Table</code></a> on <b>WordPress Code Reference</b>.</a></p><!-- <p><a href="%3$s" target="blank">Link 3</a> is the third link.</p> -->', DL_SLUG ),
            'http://codex.wordpress.org/Class_Reference/WP_List_Table',
            'https://developer.wordpress.org/reference/classes/wp_list_table/',
            '#'
        );

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
}

endif;
