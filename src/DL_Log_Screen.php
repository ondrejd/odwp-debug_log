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

if ( ! class_exists( 'DL_Log_Table' ) ) {
    require_once( DL_PATH . 'src/DL_Log_Table.php' );
}

if ( ! class_exists( 'DL_Log_Screen' ) ) :

/**
 * Administration screen for the log viewer.
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @since 1.0.0
 */
class DL_Log_Screen extends DL_Screen_Prototype {

    /**
     * @since 1.0.0
     * @var string
     */
    const SLUG = DL_SLUG . '-log';

    /**
     * Constructor.
     * 
     * @param WP_Screen $screen Optional.
     * @return void
     * @since 1.0.0
     * @todo Refactor this!
     */
    public function __construct( \WP_Screen $screen = null ) {
        // Main properties
        $this->slug = self::SLUG;
        $this->menu_title = __( 'Debug Log', DL_SLUG );
        $this->page_title = __( 'Debug Log Viewer', DL_SLUG );

        // Specify help tabs

        // General help tab
        $this->help_tabs[] = [
            'id' => self::SLUG . '-info-helptab-01',
            'title' => __( 'General', DL_SLUG ),
            'content' => sprintf(
                    '<h3 class="help-title">%1$s</h3><p>%2$s</p>',
                    __( 'Debug Log Viewer', DL_SLUG ),
                    __( 'Debug log table contains all records from <code>debug.log</code> file.', DL_SLUG )
            ),
        ];

        // Column types help tab
        $this->help_tabs[] = [
            'id' => self::SLUG . '-info-helptab-02',
            'title' => __( 'Table columns', DL_SLUG ),
            'content' => sprintf(
                '<h3 class="help-title">%1$s</h3>' .
                '<ul class="help-list">' .
                '<li><strong>%2$s</strong>&nbsp;&ndash;&nbsp;%3$s</li>' .
                '<li><strong>%4$s</strong>&nbsp;&ndash;&nbsp;%5$s</li>' .
                '<li><strong>%6$s</strong>&nbsp;&ndash;&nbsp;%7$s</li>' .
                '<li><strong>%8$s</strong>&nbsp;&ndash;&nbsp;%9$s</li>' .
                '</ul>',
                __( 'Table columns', DL_SLUG ),
                __( 'Record number', DL_SLUG ),
                __( 'Record (line) number of the record', DL_SLUG ),
                __( 'Date and time', DL_SLUG ),
                __( 'Date and time when was record added', DL_SLUG ),
                __( 'Record type', DL_SLUG ),
                __( 'Type of record (see table below)', DL_SLUG ),
                __( 'Record', DL_SLUG ),
                __( 'Text of record (including <em>stack trace</em>)', DL_SLUG )
            ),
        ];

        // Error types help tab
        $this->help_tabs[] = [
            'id' => self::SLUG . '-info-helptab-03',
            'title' => __( 'Record types', DL_SLUG ),
            'content' => sprintf(
                '<h3 class="help-title">%1$s</h3>' .
                '<ul class="help-list">' .
                '<li><span class="dashicons dashicons-warning"></span><em>%2$s</em>&nbsp;&ndash;&nbsp;%3$s</li>' .
                '<li><span class="dashicons dashicons-format-status"></span><em>%4$s</em>&nbsp;&ndash;&nbsp;%5$s</li>' .
                '<li><span class="dashicons dashicons-editor-help"></span><em>%6$s</em>&nbsp;&ndash;&nbsp;%7$s</li>' .
                '<li><span class="dashicons dashicons-thumbs-down"></span><em>%8$s</em>&nbsp;&ndash;&nbsp;%9$s</li>' .
                '<li><span class="dashicons dashicons-flag"></span><em>%10$s</em>&nbsp;&ndash;&nbsp;%11$s</li>' .
                '<li><span class="dashicons dashicons-marker"></span><em>%12$s</em>&nbsp;&ndash;&nbsp;%13$s</li>' .
                '</ul>',
                __( 'Types of records', DL_SLUG ),
                DL_Log_Record::TYPE_ERROR,
                __( 'Fatal error', DL_SLUG ),
                DL_Log_Record::TYPE_NOTICE,
                __( 'Error notice', DL_SLUG ),
                DL_Log_Record::TYPE_OTHER,
                __( 'Other errors', DL_SLUG ),
                DL_Log_Record::TYPE_PARSER,
                __( 'PHP parser error', DL_SLUG ),
                DL_Log_Record::TYPE_WARNING,
                __( 'Warning notice', DL_SLUG ),
                DL_Log_Record::TYPE_DLPARSER,
                __( 'Debug log parser error', DL_SLUG )
            ),
        ];

        // Specify help sidebars
        $this->help_sidebars[] = sprintf(
                '<b>%s</b>' .
                '<p><a href="%s" target="blank">%s</a></p>' .
                '<p><a href="%s" target="blank">%s</a></p>' .
                '<p><a href="%s" target="blank">%s</a></p>',
                __( 'Useful links', DL_SLUG ),
                'https://ondrejd.com',
                __( 'Author\'s homepage', DL_SLUG ),
                'https://github.com/ondrejd/odwp-debug_log',
                __( 'GitHub - source codes', DL_SLUG ),
                'https://github.com/ondrejd/odwp-debug_log/issues',
                __( 'GitHub - issues', DL_SLUG )
        );

        // Enable screen options
        $this->enable_screen_options = true;

        // Specify screen options
        $this->options[self::SLUG . '-shown_cols'] = [
            'label'   => __( 'Displayed columns', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_SHOWN_COLS,
            'option'  => self::SLUG . '-shown_cols',
        ];
        $this->options[self::SLUG . '-per_page'] = [
            'label'   => __( 'Count of records per page', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_PER_PAGE,
            'option'  => self::SLUG . '-per_page',
        ];
        $this->options[self::SLUG . '-short_src_links'] = [
            'label'   => __( 'Make links to source files shorter?', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_SHORT_SRC_LINKS,
            'option'  => self::SLUG . '-short_src_links',
        ];
        $this->options[self::SLUG . '-show_icons'] = [
            'label'   => __( 'Show record type as an icon?', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_SHOW_ICONS,
            'option'  => self::SLUG . '-show_icons',
        ];
        $this->options[self::SLUG . '-show_links'] = [
            'label'   => __( 'Show links to source files?', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_SHOW_LINKS,
            'option'  => self::SLUG . '-show_links',
        ];
        $this->options[self::SLUG . '-show_trace'] = [
            'label'   => __( 'Show <em>stack trace</em> defaultly unfolded?', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_SHOW_TRACE,
            'option'  => self::SLUG . '-show_trace',
        ];
        $this->options[self::SLUG . '-sort_col'] = [
            'label'   => __( 'Defaultly sorted column', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_SORT_COL,
            'option'  => self::SLUG . '-sort_col',
        ];
        $this->options[self::SLUG . '-sort_dir'] = [
            'label'   => __( 'Default sort direction', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_SORT_DIR,
            'option'  => self::SLUG . '-sort_dir',
        ];
        $this->options[self::SLUG . '-src_win_width' ] = [
            'label'   => __( 'Width of modal window with source codes', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_SORT_DIR,
            'option'  => self::SLUG . '-sort_win_width',
        ];
        $this->options[self::SLUG . '-src_win_height' ] = [
            'label'   => __( 'Height of modal window with source codes', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_SORT_DIR,
            'option'  => self::SLUG . '-src_win_height',
        ];

        // Finish screen construction
        parent::__construct( $screen );
    }

    /**
     * Initializes hooks for AJAX requests.
     *
     * @return void
     * @see DL_Plugin::init_ajax()
     * @since 1.0.0
     * @uses add_action()
     */
    public function init_ajax() {
        add_action( 'wp_ajax_odwpdl_delete_action', [$this, 'ajax_delete_action'] );
    }

    /**
     * Action for `admin_menu` hook.
     * 
     * @return void
     * @since 1.0.0
     * @uses add_management_page()
     * @uses add_action()
     */
    public function admin_menu() {
        $this->hookname = add_management_page(
                $this->page_title,
                $this->menu_title,
                'manage_options',
                self::SLUG,
                [$this, 'render']
        );

        add_action( 'load-' . $this->hookname, [$this, 'screen_load'] );
    }

    /**
     * Action for `admin_enqueue_scripts` hook.
     *
     * @param string $hook
     * @return void
     * @since 1.0.0
     * @uses admin_url()
     * @uses plugins_url()
     * @uses wp_enqueue_script()
     * @uses wp_localize_script()
     */
    public function admin_enqueue_scripts( string $hook ) {

        // We need js/css resources only on log screen
        if ( $hook !== 'tools_page_odwpdl-log' ) {
            return;
        }

        // Include JavaScript
        wp_enqueue_script( self::SLUG, plugins_url( 'assets/js/screen-log.js', DL_FILE ), ['jquery'] );
        wp_localize_script( self::SLUG, 'odwpdl', [
            'ajax'     => [
                'actions'  => [
                    'delete_log'    => DL_Plugin::AJAX_DELETE_LOG_ACTION,
                    'delete_record' => DL_Plugin::AJAX_DELETE_RECORD_ACTION,
                ],
                'url'      => admin_url( 'admin-ajax.php' ),
            ],
            'options'  => [
                'current'  => DL_Log_Table::get_options(),
                'default'  => DL_Log_Table::get_default_options(),
            ],
            'i18n'     => [
                'confirm_delete_log_msg' => __( 'Do you really want to delete whole <code>debug.log</code> file?', DL_SLUG ),
                'confirm_delete_record_msg' => __( 'Do you really want to delete selected record?', DL_SLUG ),
            ],
        ] );
    }

    /**
     * Return current screen options.
     * 
     * @return array
     * @see DL_Screen_Prototype::get_screen_options()
     * @see DL_Log_Table::get_options();
     * @since 1.0.0
     */
    public function get_screen_options() : array {

        // Screen options are not enabled
        if ( $this->enable_screen_options !== true ) {
            return [];
        }

        return DL_Log_Table::get_options();
    }

    /**
     * Save screen options.
     * 
     * @return void
     * @see DL_Screen_Prototype::get_screen_options()
     * @since 1.0.0
     * @todo It should be done automatically by using {@see DL_Screen_Prototype::$options} without need of writing own code.
     * @uses get_current_user_id()
     * @uses update_user_meta()
     * @uses wp_verify_nonce()
     */
    public function save_screen_options() {

        if ( $this->enable_screen_options !== true ) {
            return;
        }

        $user = get_current_user_id();

        if (
            filter_input( INPUT_POST, self::SLUG . '-submit' ) &&
            (bool) wp_verify_nonce( filter_input( INPUT_POST, self::SLUG . '-nonce' ) ) === true
        ) {
            // Shown columns
            $_shown_cols_raw = filter_input( INPUT_POST, self::SLUG . '-show_cols', FILTER_DEFAULT , FILTER_REQUIRE_ARRAY );

            if ( is_null( $_shown_cols_raw ) || empty( $_shown_cols_raw ) ) {
                // No cols are set
                update_user_meta( $user, self::SLUG . '-shown_cols', '' );
            }
            else if ( is_array( $_shown_cols_raw ) ) {
                // Get IDs of the cols
                $shown_cols_arr = array_keys( $_shown_cols_raw );
                $shown_cols_str = implode( ',', $shown_cols_arr );

                // Save meta value
                update_user_meta( $user, self::SLUG . '-shown_cols', $shown_cols_str );
            }

            // Rows per page
            $per_page = filter_input( INPUT_POST, self::SLUG . '-per_page' );
            update_user_meta( $user, self::SLUG . '-per_page', ( int ) $per_page );

            // Show icons
            $show_icons = filter_input( INPUT_POST, self::SLUG . '-show_icons' );
            update_user_meta( $user, self::SLUG . '-show_icons', ( strtolower( $show_icons ) == 'on' ) ? 1 : 0 );

            // Show links
            $show_links = filter_input( INPUT_POST, self::SLUG . '-show_links' );
            update_user_meta( $user, self::SLUG . '-show_links', ( strtolower( $show_links ) == 'on' ) ? 1 : 0 );

            // Show short links
            $short_src_links = filter_input( INPUT_POST, self::SLUG . '-short_src_links' );
            update_user_meta( $user, self::SLUG . '-short_src_links', ( strtolower( $short_src_links ) == 'on' ) ? 1 : 0 );

            // Show trace
            $show_trace = filter_input( INPUT_POST, self::SLUG . '-show_trace' );
            update_user_meta( $user, self::SLUG . '-show_trace', ( strtolower( $show_trace ) == 'on' ) ? 1 : 0 );

            // Sorting column
            $sort_col = filter_input( INPUT_POST, self::SLUG . '-sort_col' );
            update_user_meta( $user, self::SLUG . '-sort_col', $sort_col );

            // Sorting direction
            $sort_dir = filter_input( INPUT_POST, self::SLUG . '-sort_dir' );
            update_user_meta( $user, self::SLUG . '-sort_dir', $sort_dir );

            // Source code window width
            $src_win_width = filter_input( INPUT_POST, self::SLUG . '-src_win_width' );
            update_user_meta( $user, self::SLUG . '-src_win_width', $src_win_width );

            // Source code window height
            $src_win_height = filter_input( INPUT_POST, self::SLUG . '-src_win_height' );
            update_user_meta( $user, self::SLUG . '-src_win_height', $src_win_height );
        }
    }
}

endif;
