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

if( ! class_exists( 'DL_Options_Screen' ) ):

/**
 * Administration screen for plugin's options.
 * @since 1.0.0
 */
class DL_Options_Screen extends DL_Screen_Prototype {
    /**
     * Constructor.
     * @param WP_Screen $screen Optional.
     * @return void
     * @since 1.0.0
     */
    public function __construct( \WP_Screen $screen = null ) {
        // Main properties
        $this->slug = DL_SLUG . '-plugin_options';
        $this->menu_title = __( 'Ladící informace', DL_SLUG );
        $this->page_title = __( 'Nastavení pro plugin <em>Prohlížeč ladících informací</em>', DL_SLUG );

        // Specify help tabs
        $this->help_tabs = [];

        // Specify help sidebars
        $this->help_sidebars = [];

        // Disable screen options
        $this->enable_screen_options = false;

        // Finish screen constuction
        parent::__construct( $screen );
    }
}

endif;
