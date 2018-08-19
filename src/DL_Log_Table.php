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

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if ( ! class_exists( 'DL_Log_Parser' ) ) {
    require_once( DL_PATH . 'src/DL_Log_Parser.php' );
}

if ( ! class_exists( 'DL_Log_Table' ) ) :

/**
 * Table with log. User options for the table are implemented partially in {@see DL_Log_Screen}.
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @since 1.0.0
 * @todo Default column and direction for sorting should be set via user preferences.
 * @todo Default per page items count should be set via user preferences.
 * @todo Now we get all data at the first place and than we set pagination but it means that we are parsing data we don't need to - so this must be implemented other way.
 * @todo We should parse only rows that going to be displayed not all of them.
 */
class DL_Log_Table extends WP_List_Table {

    /**
     * @since 1.0.0
     * @var string Ascendant direction of the sorting.
     */
    const DEFAULT_SORT_DIR_ASC  = 'asc';

    /**
     * @since 1.0.0
     * @var string Descendant direction of the sorting.
     */
    const DEFAULT_SORT_DIR_DESC = 'desc';

    /**
     * @since 1.0.0
     * @var string Comma-separated list of defaultly shown columns.
     */
    const DEFAULT_SHOWN_COLS = 'id,type';

    /**
     * @since 1.0.0
     * @var string Default per page items count.
     */
    const DEFAULT_PER_PAGE = 10;

    /**
     * @since 1.0.0
     * @var string Column sorted by default.
     */
    const DEFAULT_SORT_COL = 'time';

    /**
     * @since 1.0.0
     * @var string Default sorting direction.
     */
    const DEFAULT_SORT_DIR = self::DEFAULT_SORT_DIR_DESC;

    /**
     * @since 1.0.0
     * @var bool Default settings for displaying icons instead of text in record type column.
     */
    const DEFAULT_SHOW_ICONS = true;

    /**
     * @since 1.0.0
     * @var bool Default settings for displaying file names as HTML links to real source files.
     */
    const DEFAULT_SHOW_LINKS = true;

    /**
     * @since 1.0.0
     * @var bool Default settings for displaying stack trace in the table.
     */
    const DEFAULT_SHOW_TRACE = true;

    /**
     * @since 1.0.0
     * @var bool Default settings for displaying short links to source codes.
     */
    const DEFAULT_SHORT_SRC_LINKS = true;

    /**
     * @since 1.0.0
     * @var int Default width of popup window with source code.
     */
    const DEFAULT_SRC_WIN_WIDTH = 900;

    /**
     * @since 1.0.0
     * @var int Default height of popup window with source code.
     */
    const DEFAULT_SRC_WIN_HEIGHT = 500;

    /**
     * @var string
     * @since 1.0.0
     */
    const DEFAULT_VIEW = self::VIEW_ALL;

    /**
     * @since 1.0.0
     * @var string
     */
    const VIEW_ALL = 'all';

    /**
     * @since 1.0.0
     * @var string
     */
    const VIEW_EARLIER = 'earlier';

    /**
     * @since 1.0.0
     * @var string
     */
    const VIEW_TODAY = 'today';

    /**
     * @since 1.0.0
     * @var string
     */
    const VIEW_YESTERDAY = 'yesterday';

    /**
     * @since 1.0.0
     * @var DL_Log_Parser $parser
     */
    protected $parser = null;

    /**
     * Constructor.
     *
     * @param array $args Optional (not used anyway).
     * @return void
     * @since 1.0.0
     * @todo We use the table in partial so maybe is too late to call `removable_query_args` filter...
     */
    public function __construct( array $args = [] ) {

        parent::__construct( [
            'singular' => __( 'Record', DL_SLUG ),
            'plural'   => __( 'Records', DL_SLUG ),
            'ajax'     => true,
        ] );

        // Register our removable query arguments
        add_filter( 'removable_query_args', [$this, 'register_removable_query_args'] );

        // Initialize parser
        $this->parser = DL_Log_Parser::get_instance( self::get_options() );
    }

    /**
     * Return default options for the table.
     * 
     * @return array
     * @since 1.0.0
     */
    public static function get_default_options() : array {
        return [
            'shown_cols'      => self::DEFAULT_SHOWN_COLS,
            'per_page'        => self::DEFAULT_PER_PAGE,
            'show_icons'      => self::DEFAULT_SHOW_ICONS,
            'show_links'      => self::DEFAULT_SHOW_LINKS,
            'show_trace'      => self::DEFAULT_SHOW_TRACE,
            'sort_col'        => self::DEFAULT_SORT_COL,
            'sort_dir'        => self::DEFAULT_SORT_DIR,
            'short_src_links' => self::DEFAULT_SHORT_SRC_LINKS,
            'src_win_width'   => self::DEFAULT_SRC_WIN_WIDTH,
            'src_win_height'  => self::DEFAULT_SRC_WIN_HEIGHT,
        ];
    }

    /**
     * Return options for the table.
     *
     * @return array
     * @since 1.0.0
     * @uses get_current_user_id()
     * @uses get_user_meta()
     */
    public static function get_options() : array {
        $user = get_current_user_id();

        $shown_cols = get_user_meta( $user, DL_Log_Screen::SLUG . '-shown_cols', true );
        $per_page = get_user_meta( $user, DL_Log_Screen::SLUG . '-per_page', true );
        $show_icons = get_user_meta( $user, DL_Log_Screen::SLUG . '-show_icons', true );
        $show_links = get_user_meta( $user, DL_Log_Screen::SLUG . '-show_links', true );
        $show_trace = get_user_meta( $user, DL_Log_Screen::SLUG . '-show_trace', true );
        $sort_col = get_user_meta( $user, DL_Log_Screen::SLUG . '-sort_col', true );
        $sort_dir = get_user_meta( $user, DL_Log_Screen::SLUG . '-sort_dir', true );
        $short_src_links = get_user_meta( $user, DL_Log_Screen::SLUG . '-short_src_links', true );
        $src_win_width = get_user_meta( $user, DL_Log_Screen::SLUG . '-src_win_width', true );
        $src_win_height = get_user_meta( $user, DL_Log_Screen::SLUG . '-src_win_height', true );

        $defaults = self::get_default_options();
        $currents = [];

        if ( strlen( $shown_cols ) > 0 && filter_var( $shown_cols, FILTER_SANITIZE_STRING ) ) {
            $currents['shown_cols'] = $shown_cols;
        }

        if ( filter_var( $per_page, FILTER_SANITIZE_NUMBER_INT ) ) {
            $currents['per_page'] = (int) $per_page;
        }

        $currents['show_icons'] = ( filter_var( $show_icons, FILTER_SANITIZE_NUMBER_INT ) == '1' );
        $currents['show_links'] = ( filter_var( $show_links, FILTER_SANITIZE_NUMBER_INT ) == '1' );
        $currents['show_trace'] = ( filter_var( $show_trace, FILTER_SANITIZE_NUMBER_INT ) == '1' );
        $currents['short_src_links'] = ( filter_var( $short_src_links, FILTER_SANITIZE_NUMBER_INT ) == '1' );

        if ( strlen( $sort_col ) > 0 && filter_var( $shown_cols, FILTER_SANITIZE_STRING ) ) {
            $currents['sort_col'] = $sort_col;
        }

        if ( strlen( $sort_dir ) > 0 && filter_var( $shown_cols, FILTER_SANITIZE_STRING ) ) {
            $currents['sort_dir'] = $sort_dir;
        }

        if ( filter_var( $src_win_width, FILTER_SANITIZE_NUMBER_INT ) ) {
            $currents['src_win_width'] = (int) $src_win_width;
        }

        if ( filter_var( $src_win_height, FILTER_SANITIZE_NUMBER_INT ) ) {
            $currents['src_win_height'] = (int) $src_win_height;
        }

        return array_merge( $defaults, $currents );
    }

    /**
     * Renders checkbox column.
     * 
     * @param DL_Log_Record $item
     * @return string
     * @since 1.0.0
     */
    function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="log_item[]" value="%s">', $item->get_id() );
    }

    /**
     * Default function for rendering columns.
     * 
     * @param DL_Log_Record $item
     * @param string $column_name
     * @return string
     * @since 1.0.0
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'id'   : return $item->get_id();
            case 'time' : return $item->get_time( true );
            case 'text' : return $item->get_message();
            case 'type' : return $item->get_type();
            default     : return '';
        }
    }

    /**
     * Render column with log record type.
     * 
     * @param DL_Log_Record $item
     * @return string
     * @since 1.0.0
     */
    public function column_type( DL_Log_Record $item ) {
        $show_icons = self::get_options()['show_icons'];
        $lbl = $cls = '';

        // Set up label and class according to record's type
        switch ( $item->get_type() ) {
            case DL_Log_Record::TYPE_ERROR:
                $lbl = __( 'Error', DL_SLUG );
                $cls = 'warning';
                break;

            case DL_Log_Record::TYPE_NOTICE:
                $lbl = __( 'Notice', DL_SLUG );
                $cls = 'format-status';
                break;

            case DL_Log_Record::TYPE_OTHER:
                $lbl = __( 'Other', DL_SLUG );
                $cls = 'editor-help';
                break;

            case DL_Log_Record::TYPE_PARSER:
                $lbl = __( 'Parser error', DL_SLUG );
                $cls = 'thumbs-down';
                break;

            case DL_Log_Record::TYPE_WARNING:
                $lbl = __( 'Warning', DL_SLUG );
                $cls = 'flag';
                break;

            case DL_Log_Record::TYPE_DLPARSER:
                $lbl = __( 'Debug Log Parser Error', DL_SLUG );
                $cls = 'marker';
                break;
        }

        // Prepare the output
        $ret = $lbl;
        if ( $show_icons === true ) {
            return sprintf( '<span class="dashicons dashicons-%2$s" title="%1$s"></span>', $lbl, $cls );
        }

        return $ret;
    }

    /**
     * Render `text` column with the `delete` and `view` action.
     * @param DL_Log_Record $item
     * @return string
     * @since 1.0.0
     * @todo Improve this!
     */
    public function column_text( DL_Log_Record $item ) {
        $id = ( int ) $item->get_id();
        $text = $item->get_message();
        $options = $this->get_options();
        $show_links = $options['show_links'];
        $show_trace = $options['show_trace'];
        $show_type  = ( strpos( $options['shown_cols'], 'type' ) !== false );

        if ( $show_type !== true ) {
            $text = '<b>' . $item->get_type() . '</b>: ' . $text;
        }

        if ( $show_links === true ) {
            $text = $this->parser->make_source_links( $text );
        }

        if ( $item->has_trace() === true ) {
            $icon = ( $show_trace === true )
                    ? '<span class="dashicons dashicons-arrow-up-alt2"></span>'
                    : '<span class="dashicons dashicons-arrow-down-alt2"></span>';

            $text .= '<div class="stack-trace ' . ( $show_trace === true ? '' : 'stack-trace--collapsed' ) . '">' . PHP_EOL;
            $text .= '    <b class="stack-trace-toggling">' . $icon . ' ' . __( 'Stack trace', DL_SLUG ) . '</b>' . PHP_EOL;
            $text .= '    <ul>' . PHP_EOL;

            foreach ( $item->get_trace() as $trace ) {
                if ( $show_links === true ) {
                    $trace = $this->parser->make_source_links( $trace );
                }

                $text .= '        <li>' . $trace . '</li>';
            }

            $text .= '    </ul>' . PHP_EOL;
            $text .= '</div>' . PHP_EOL;
        }

        $actions = $this->get_row_actions( $item );

        return sprintf('%1$s %2$s', $text, $this->row_actions( $actions ) );
    }

    /**
     * Custom method for displaying rows.
     * 
     * @return void
     * @since 1.0.0
     */
    public function display_rows() {
        foreach ( $this->items as $item ) {
            if ( ! ( $item instanceof \DL_Log_Record ) ) {
                continue;
            }

            $this->single_row( $item );
        }
    }

    /**
     * Return array describing bulk actions.
     *
     * @return array
     * @since 1.0.0
     */
    public function get_bulk_actions() {
        $actions = [
            'delete' => __( 'Delete', DL_SLUG ),
        ];
        return $actions;
    }

    /**
     * Return array describing row actions.
     *
     * @param DL_Log_Record $item
     * @return array
     * @see DL_Log_Table::process_row_actions()
     * @since 1.0.0
     * @uses wp_nonce_url()
     */
    public function get_row_actions( DL_Log_Record $item ) {
        $action_url = add_query_arg( ['action' => 'delete_single', 'record' => $item->get_id()], $this->get_current_url() );
        $nonce_url = wp_nonce_url( $action_url, 'delete_log_item_'.$item->get_id() );

        $actions = [
            'delete_single' => sprintf('<a href="%1$s">%2$s</a>', $nonce_url, __( 'Delete', DL_SLUG ) ),
        ];

        return $actions;
    }

    /**
     * Return URL of the WP admin page where the table lives on.
     *
     * @deprecated Use {@see DL_Log_Table::get_current_url()} instead!
     * @param array $args (Optional.) Additional URL arguments as key => value array.
     * @return string
     * @since 1.0.0
     */
    public function get_table_url( $args = [] ) {
        return admin_url( 'tools.php?page=' . DL_Log_Screen::SLUG );
    }

    /**
     * Return current table URL (with all parameters - filter, paging etc.).
     *
     * @deprecated Use {@see DL_Log_Table::get_current_url()} instead!
     * @param array $args (Optional.) Additional URL arguments as key => value array.
     * @return string
     * @since 1.0.0
     * @todo Use {@see add_query_arg()} - {@link https://developer.wordpress.org/reference/functions/add_query_arg/}
     * @uses DL_Log_Table::get_table_url()
     */
    public function get_current_table_url( $args = [] ) {
        $url = $this->get_table_url();

        // Default table argument "paged"
        $paged = (int) filter_input( INPUT_GET, 'paged' );
        if ( $paged > 1 ) {
            $url .= "&amp;paged={$paged}";
        }

        // Default table argument "filter"
        $filter = filter_input( INPUT_GET, 'filter' );
        if ( ! empty( $filter ) ) {
            $url .= "&amp;filter={$filter}";
        }

        // Other arguments
        foreach ( $args as $key => $val ) {
            $url .= "&amp;{$key}={$val}";
        }

        return $url;
    }

    /**
     * Return array with table columns.
     *
     * @return array
     * @since 1.0.0
     */
    public function get_columns() {
        $columns = [
            'cb'   => '<input type="checkbox">',
            'id'   => __( 'ID', DL_SLUG ),
            'time' => __( 'Date and time', DL_SLUG ),
            'type' => __( 'Type', DL_SLUG ),
            'text' => __( 'Record', DL_SLUG ),
        ];

        return $columns;
    }

    /**
     * Return array with table columns that can be hidden.
     *
     * @return array
     * @since 1.0.0
     */
    public function get_hideable_columns() {
        $columns = [
            'id'   => ['id', true],
            'type' => ['type', false],
        ];

        return $columns;
    }

    /**
     * Return array with table columns that are hidden.
     *
     * @return array
     * @since 1.0.0
     * @todo Get really hidden columns from user meta!
     */
    public function get_hidden_columns() {
        $all_cols        = array_keys( $this->get_columns() );
        $hideable_cols   = array_keys( $this->get_hideable_columns() );
        $unhideable_cols = array_diff( $all_cols, $hideable_cols );
        $_shown_cols     = explode( ',', $this->get_options()['shown_cols'] );
        $shown_cols      = array_merge( $unhideable_cols, $_shown_cols );
        $hidden_cols     = array_diff( $all_cols, $shown_cols );

        return $hidden_cols;
    }

    /**
     * Return array with sortable table columns.
     *
     * @return array
     * @since 1.0.0
     */
    public function get_sortable_columns() {
        $columns = [
            'id'   => ['id', false],
            'time' => ['time', false],
            'text' => ['text', false],
            'type' => ['type', false],
        ];

        return $columns;
    }

    /**
     * Return array with the list of views available on this table.
     *
     * @return array
     * @since 1.0.0
     * @uses add_query_arg()
     * @uses admin_url()
     */
    protected function get_views() {
        $views = [
            self::VIEW_TODAY     => __( 'Today', DL_SLUG ),
            self::VIEW_YESTERDAY => __( 'Yesterday', DL_SLUG ),
            self::VIEW_EARLIER   => __( 'Earlier', DL_SLUG ),
            self::VIEW_ALL       => __( 'All', DL_SLUG ),
        ];
        $current_view = filter_input( INPUT_GET, 'view' );

        if ( empty( $current_view ) ) {
            $current_view = 'all';
        }

        $ret = [];

        foreach ( $views as $view => $view_lbl ) {
            $url = add_query_arg( [ 'page' => 'odwpdl-log', 'view' => $view ], admin_url( 'tools.php') );
            $cls = ( $view == $current_view ) ? ' class="current"' : '';

            // Get count of records for selected view (period).
            $cnt = $this->parser->get_stats()->get_count_by_period( $view );

            $ret[$view] = sprintf( '<a href="%s"%s>%s (%s)</a>', $url, $cls, $view_lbl, $cnt );
        }

        return $ret;
    }

    /**
     * Display the list of views available on this table - but only when there are some table items.
     *
     * @return void
     * @see WP_List_Table::views
     * @since 1.0.0
     */
    public function views() {

        if ( count( $this->items ) <= 0 && !$this->is_any_filter_used() && !$this->is_any_view_used() ) {
            return;
        }

        parent::views();
    }

    /**
     * Get current filter settings.
     *
     * @return array
     * @since 1.0.0
     */
    public function get_filter() {
        $_type = filter_input( INPUT_GET, 'filter-by-type', FILTER_SANITIZE_NUMBER_INT );

        /**
         * @var array $filter
         */
        $filter = [];
        $filter['type'] = empty( $_type ) ? 0 : ( int ) $_type;
        $filter['is_filter'] = $filter['type'] > 0;

        return $filter;
    }

    /**
     * Extra controls to be displayed between bulk actions and pagination
     *
     * @param string $which
     * @return void
     * @since 1.0.0
     * @todo Remember filtering for the next session (save it as user preferences and renew it).
     */
    protected function extra_tablenav( $which ) {

        // We don't have extra navigation below the table
        if ( $which != 'top' ) {
            return;
        }

        // If there are no items no table navigation is needed
        if ( $this->parser->get_stats()->get_total_count() == 0 ) {
            return;
        }

        $filter = $this->get_filter();
        $type = intval( $filter['type'] );
        $type_filters = [];

        // Set up type filters (with count of records)
        $type_filters[] = __( '—— All ——', DL_SLUG );

        // Fatal errors
        $cnt_error = $this->parser->get_stats()->get_count_by_type( DL_Log_Record::TYPE_ERROR );
        if ( $cnt_error > 0 ) {
            $type_filters[] = sprintf( __( 'Error (%d)', DL_SLUG ), $cnt_error );
        }

        // Notices
        $cnt_notice = $this->parser->get_stats()->get_count_by_type( DL_Log_Record::TYPE_NOTICE );
        if ( $cnt_notice > 0 ) {
            $type_filters[] = sprintf( __( 'Notice (%d)', DL_SLUG ), $cnt_notice );
        }

        // PHP parser error
        $cnt_parser = $this->parser->get_stats()->get_count_by_type( DL_Log_Record::TYPE_PARSER );
        if ( $cnt_parser > 0 ) {
            $type_filters[] = sprintf( __( 'Parser error (%d)', DL_SLUG ), $cnt_parser );
        }

        // Warnings
        $cnt_warning = $this->parser->get_stats()->get_count_by_type( DL_Log_Record::TYPE_WARNING );
        if ( $cnt_warning > 0 ) {
            $type_filters[] = sprintf( __( 'Warning (%d)', DL_SLUG ), $cnt_warning );
        }

        // Other errors
        $cnt_other = $this->parser->get_stats()->get_count_by_type( DL_Log_Record::TYPE_OTHER );
        if ( $cnt_other > 0 ) {
            $type_filters[] = sprintf( __( 'Other (%d)', DL_SLUG ), $cnt_other );
        }

        // Debug Log parser error
        $cnt_dlparser = $this->parser->get_stats()->get_count_by_type( DL_Log_Record::TYPE_DLPARSER );
        if ( $cnt_dlparser > 0 ) {
            $type_filters[] = sprintf( __( 'Debug Log parser error (%d)', DL_SLUG ), $cnt_dlparser );
        }

        // Print the filters
        echo DL_Plugin::load_template(
            'screen-log_extra_tablenav', [
                'filter'       => $filter,
                'type'         => $type,
                'type_filters' => $type_filters,
            ]
        );
    }

    /**
     * Message to be displayed when there are no items
     *
     * @return void
     * @since 1.0.0
     */
    public function no_items() {
        $msg = ( $this->is_any_filter_used() || $this->is_any_view_used() )
            ? __( 'There are no items with filter or view you have selected&hellip;', DL_SLUG )
            : __( 'Your file <code>debug.log</code> is empty &ndash; that means no errors <strong class="noitems-smiley">:-)</strong>&hellip;', DL_SLUG );

        printf( '<p class="odwpdl-no_items">%s</p>', $msg );
    }

    /**
     * Prepares data items for the table.
     *
     * @return void
     * @since 1.0.0
     */
    public function prepare_items() {

        // Set up column headers
        $this->_column_headers = [
            $this->get_columns(),
            $this->get_hidden_columns(),
            $this->get_sortable_columns(),
        ];

        // Prepare data
        $this->parser->reset();

        // Process row and bulk actions
        $this->process_row_actions();
        $this->process_bulk_actions();

        // Use view
        $view = filter_input( INPUT_GET, 'view' );
        if ( !in_array( $view, [ self::VIEW_TODAY, self::VIEW_YESTERDAY, self::VIEW_EARLIER, self::VIEW_ALL ] ) ) {
            $view = self::DEFAULT_VIEW;
        }
        $this->parser->set_view( $view );

        // Get order arguments
        $order_args = $this->get_order_args();

        // Needed hack (because other way is arrow indicating sorting
        // in table head not displayed correctly).
        $_GET['orderby'] = $order_args['orderby'];
        $_GET['order'] = $order_args['order'];

        // Apply filtering
        $this->apply_filter( $this->get_filter() );

        // Apply sorting
        $this->parser->sort( [
            'sort_col' => $order_args['orderby'],
            'sort_dir' => $order_args['order'],
        ] );

        // Pagination arguments
        $this->set_pagination_args( [
            'total_items' => $this->parser->get_stats()->get_total_count(),
            'per_page'    => $this->parser->get_options( 'per_page', self::DEFAULT_PER_PAGE ),
        ] );

        // Get data to display
        $this->items = $this->parser->get_data( [
            'page' => $this->get_pagenum()
        ] );
    }

    /**
     * Apply filter on parser data.
     *
     * @param array $filter Array with filter settings (e.g. <code>['is_filter' => <bool>, 'type' => <int>]</code>).
     * @return void
     * @since 1.0.0
     */
    private function apply_filter( array $filter ) {

        if ( $filter['is_filter'] === false ) {
            return;
        }

        $type = null;
        switch ( ( int ) $filter['type'] ) {
            case 1 : $type = DL_Log_Record::TYPE_ERROR; break;
            case 2 : $type = DL_Log_Record::TYPE_NOTICE; break;
            case 3 : $type = DL_Log_Record::TYPE_PARSER; break;
            case 4 : $type = DL_Log_Record::TYPE_WARNING; break;
            case 5 : $type = DL_Log_Record::TYPE_OTHER; break;
            case 0 :
            default: $type = null; break;
        }

        if ( ! is_null( $type ) ) {
            $this->parser->filter( function( \DL_Log_Record $record ) use( $type ) {
                return ( $record->get_type() == $type );
            } );
        }
    }

    /**
     * Return array with sorting arguments ['orderby' => 'id', 'order' => 'asc'].
     *
     * @return array
     * @since 1.0.0
     */
    private function get_order_args() {
        $options  = self::get_options();
        $orderby = filter_input( INPUT_GET, DL_Log_Screen::SLUG . '-sort_col' );
        $order = filter_input( INPUT_GET, DL_Log_Screen::SLUG . '-sort_dir' );

        if ( empty( $orderby ) ) {
            $orderby = filter_input( INPUT_GET, 'orderby' );
        }

        if ( empty( $orderby ) ) {
            $orderby = $options['sort_col'];
        }

        if ( empty( $order ) ) {
            $order = filter_input( INPUT_GET, 'order' );
        }

        if ( empty( $order ) ) {
            $order = $options['sort_dir'];
        }

        return ['order' => $order, 'orderby' => $orderby];
    }

    /**
     * Process bulk actions.
     *
     * @return void
     * @since 1.0.0
     * @todo Check NONCE!
     */
    public function process_bulk_actions() {

        /**
         * @var string $action Name of bulk action we should perform.
         */
        $action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );

        // There are top and bottom toolbars submit buttons...
        if ( empty( $action ) ) {
            $action = filter_input( INPUT_GET, 'action2', FILTER_SANITIZE_STRING );
        }

        // Validate action - if not valid return
        if ( ! in_array( $action, ['delete'] ) ) {
            return;
        }

        // Validate NONCE - if not valid return
        if ( ! check_admin_referer( 'log_table_form' ) ) {
            // TODO ...
        }

        /**
         * @var array $records Array with records IDs.
         */
        $records = filter_input( INPUT_GET, 'log_item', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

        // There are no items to delete
        if ( count( $records ) == 0 ) {
            DL_Plugin::print_admin_notice( __( 'Nothing deleted - no records were selected.', DL_SLUG ), 'info', true );
            return;
        }

        // Delete records
        $res = $this->parser->delete_records( $records );

        // Print output message
        if ( $res['saved'] !== true ) {
            DL_Plugin::print_admin_notice( __( 'Error occurs during deleting records from <code>debug.log</code> file.', DL_SLUG ), 'error' );
        } else {

            if ( $res['failed'] === 0 && $res['done'] > 0 ) {
                DL_Plugin::print_admin_notice( sprintf( __( 'Deleting was successful (deleted records: <strong>%1$d</strong>).', DL_SLUG ), $res['done'] ), 'success' );
            }
            elseif ( $res['failed'] === 0 && $res['done'] === 0 ) {
                DL_Plugin::print_admin_notice( __( 'No records from <code>debug.log</code> were deleted.', DL_SLUG ), 'info' );
            }
            else {
                DL_Plugin::print_admin_notice( sprintf( __( 'Some records were deleted but other not (deleted/not deleted records: <strong>%1$d/%2$d</strong>).', DL_SLUG ), $res['done'], $res['failed'] ), 'warning' );
            }
        }
    }

    /**
     * Process row actions. As are defined in {@see DL_Log_Table::column_text()}.
     *
     * @return void
     * @see DL_Log_Table::get_row_actions()
     * @since 1.0.0
     * @uses check_admin_referer()
     */
    public function process_row_actions() {

        $action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
        $record = (int) filter_input( INPUT_GET, 'record', FILTER_SANITIZE_NUMBER_INT );

        // Validate action, otherwise return
        if ( ! in_array( $action, ['delete_single'] ) || empty( $record ) ) {
            return;
        }

        // Perform action
        if ( $action == 'delete_single' ) {

            // Check NONCE
            if (  check_admin_referer( 'delete_log_item_'.$record ) !== 1 ) {
                DL_Plugin::print_admin_notice( sprintf( __( 'Nonce is not valid. Please try your request again.', DL_SLUG ), $record ), 'info', true );
                return;
            }

            // Check instance of record's object
            $record_obj = $this->parser->get_data( ['record' => $record] );
            if ( ! ( $record_obj instanceof DL_Log_Record ) ) {
                DL_Plugin::print_admin_notice( sprintf( __( 'Something went wrong so log record with ID <strong>%d</strong> was not deleted!', DL_SLUG ), $record ), 'error', true );
                return;
            }

            // Delete selected log record
            if ( $this->parser->delete_record( $record_obj ) === true ) {
                $msg_text = __( 'Record on line <strong>%d</strong> was successfully removed from <code>debug.log</code> file.', DL_SLUG );
                $msg_type = 'success';
            } else {
                $msg_text = __( 'Record on line <strong>%d</strong> from <code>debug.log</code> file was not deleted!', DL_SLUG );
                $msg_type = 'error';
            }

            DL_Plugin::print_admin_notice( sprintf( $msg_text, $record_obj->get_start_line() ), $msg_type, true );
        }
    }

    /**
     * We override default {@see WP_List_Table::pagination()} method because we need to add filter argument into it.
     *
     * @param string $which
     * @return void
     * @since 1.0.0
     * @uses add_query_arg()
     * @uses esc_url()
     * @uses number_format_i18n()
     * @uses remove_query_arg()
     */
    protected function pagination( $which ) {

        if ( empty( $this->_pagination_args ) ) {
            return;
        }

        $total_items = $this->_pagination_args['total_items'];
        $total_pages = $this->_pagination_args['total_pages'];
        $infinite_scroll = false;

        if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
            $infinite_scroll = $this->_pagination_args['infinite_scroll'];
        }

        if ( 'top' === $which && $total_pages > 1 ) {
            $this->screen->render_screen_reader_content( 'heading_pagination' );
        }

        $output = '<span class="displaying-num">'
            . sprintf( _n( '%s item', '%s items', $total_items, DL_SLUG ), number_format_i18n( $total_items ) )
            . '</span>';

        $current = $this->get_pagenum();
        $current_url = self::get_current_url();

        // Added filter
        $filter = $this->get_filter();
        if ( $filter > 0 ) {
            $current_url = add_query_arg( 'filter-by-type', $filter, $current_url );
        }

        $page_links = [];

        $total_pages_before = '<span class="paging-input">';
        $total_pages_after  = '</span></span>';

        $disable_first = $disable_last = $disable_prev = $disable_next = false;

        if ( $current == 1 ) {
            $disable_first = true;
            $disable_prev = true;
        }
        if ( $current == 2 ) {
            $disable_first = true;
        }
        if ( $current == $total_pages ) {
            $disable_last = true;
            $disable_next = true;
        }
        if ( $current == $total_pages - 1 ) {
            $disable_last = true;
        }

        if ( $disable_first ) {
            $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>';
        } else {
            $page_links[] = sprintf( "<a class='first-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                    esc_url( remove_query_arg( 'paged', $current_url ) ),
                    __( 'First page', DL_SLUG ), '&laquo;'
            );
        }

        if ( $disable_prev ) {
            $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = sprintf( "<a class='prev-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                    esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
                    __( 'Previous page', DL_SLUG ), '&lsaquo;'
            );
        }

        if ( 'bottom' === $which ) {
            $html_current_page  = $current;
            $total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page', DL_SLUG ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
        } else {
            $html_current_page = sprintf( "%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging'><span class='tablenav-paging-text'>",
                    sprintf( '<label for="current-page-selector" class="screen-reader-text">%s</label>', __( 'Current Page', DL_SLUG ) ),
                    $current, strlen( $total_pages )
            );
        }

        $html_total_pages = sprintf( '<span class="total-pages">%s</span>', number_format_i18n( $total_pages ) );
        $page_links[] = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging', DL_SLUG ), $html_current_page, $html_total_pages ) . $total_pages_after;

        if ( $disable_next ) {
            $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span>';
        } else {
            $page_links[] = sprintf( "<a class='next-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                    esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
                    __( 'Next page', DL_SLUG ), '&rsaquo;'
            );
        }

        if ( $disable_last ) {
            $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span>';
        } else {
            $page_links[] = sprintf( "<a class='last-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                    esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
                    __( 'Last page', DL_SLUG ), '&raquo;'
            );
        }

        $pagination_links_class = 'pagination-links';
        if ( ! empty( $infinite_scroll ) ) {
            $pagination_links_class = ' hide-if-js';
        }
        $output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

        if ( $total_pages ) {
            $page_class = $total_pages < 2 ? ' one-page' : '';
        } else {
            $page_class = ' no-pages';
        }
        $this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

        echo $this->_pagination;
    }

    /**
     * We override default {@see WP_List_Table::print_column_headers()} method because we need to add filter argument into it.
     *
     * @param bool $with_id (Optional.)
     * @return void
     * @since 1.0.0
     * @uses add_query_arg()
     * @uses esc_url()
     * @uses remove_query_arg()
     * @todo Refactor this method!
     */
    public function print_column_headers( $with_id = true ) {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        $current_url = remove_query_arg( 'paged', self::get_current_url() );

        // [ondrejd]: added filter
        $filter = $this->get_filter();
        if ( $filter > 0 ) {
            $current_url = add_query_arg( 'filter-by-type', $filter, $current_url );
        }

        if ( isset( $_GET['orderby'] ) ) {
            $current_orderby = $_GET['orderby'];
        } else {
            $current_orderby = '';
        }

        if ( isset( $_GET['order'] ) && 'desc' === $_GET['order'] ) {
            $current_order = 'desc';
        } else {
            $current_order = 'asc';
        }

        if ( ! empty( $columns['cb'] ) ) {
            static $cb_counter = 1;
            $columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">'
                    . __( 'Select All', DL_SLUG ) . '</label>'
                    . '<input id="cb-select-all-' . $cb_counter . '" type="checkbox">';
            $cb_counter++;
        }

        foreach ( $columns as $column_key => $column_display_name ) {
            $class = array( 'manage-column', "column-$column_key" );

            if ( in_array( $column_key, $hidden ) ) {
                    $class[] = 'hidden';
            }

            if ( 'cb' === $column_key ) {
                $class[] = 'check-column';
            } elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) ) {
                $class[] = 'num';
            }

            if ( $column_key === $primary ) {
                $class[] = 'column-primary';
            }

            if ( isset( $sortable[$column_key] ) ) {
                list( $orderby, $desc_first ) = $sortable[$column_key];

                if ( $current_orderby === $orderby ) {
                    $order = 'asc' === $current_order ? 'desc' : 'asc';
                    $class[] = 'sorted';
                    $class[] = $current_order;
                } else {
                    $order = $desc_first ? 'desc' : 'asc';
                    $class[] = 'sortable';
                    $class[] = $desc_first ? 'asc' : 'desc';
                }

                $column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) )
                    . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
            }

            $tag = ( 'cb' === $column_key ) ? 'td' : 'th';
            $scope = ( 'th' === $tag ) ? 'scope="col"' : '';
            $id = $with_id ? "id='$column_key'" : '';

            if ( !empty( $class ) ) {
                $class = "class='" . join( ' ', $class ) . "'";
            }

            echo "<$tag $scope $id $class>$column_display_name</$tag>";
        }
    }

    /**
     * Return TRUE if any filter is selected/used.
     *
     * @return bool
     * @since 1.0.0
     */
    public function is_any_filter_used() : bool {
        return $this->get_filter()['is_filter'];
    }

    /**
     * Return TRUE if any view is selected/used.
     *
     * @return bool
     * @since 1.0.0
     */
    public function is_any_view_used() : bool {
        $view = filter_input( INPUT_GET, 'view', FILTER_SANITIZE_STRING );

        if ( empty( $view ) ) {
            $view = filter_input( INPUT_GET, 'view', FILTER_SANITIZE_STRING );

            if ( empty( $view ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return current URL.
     *
     * @return string
     * @since 1.0.0
     * @uses remove_query_arg()
     * @uses set_url_scheme()
     * @uses wp_removable_query_args()
     * @todo Allow to pass array of arguments (as an addition to the query).
     */
    public static function get_current_url() : string {
        $r_query_args = wp_removable_query_args();
        $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

        return remove_query_arg( $r_query_args, $current_url );
    }

    /**
     * Filter for removable query arguments.
     *
     * @param array $args
     * @return array
     * @see wp_removable_query_args()
     * @since 1.0.0
     */
    public static function register_removable_query_args( array $args ) : array {
        $args[] = 'action';
        $args[] = 'action2';
        $args[] = 'record';
        $args[] = '_wpnonce';
        $args[] = '_wp_http_referer';
        $args[] = 'odwpdl-filter_submit';
        $args[] = 'delete_log';
        $args[] = 'show_raw_log';

        return $args;
    }
}

endif;
