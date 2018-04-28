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

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if( ! class_exists( 'DL_Log_Parser' ) ) {
    require_once( DL_PATH . 'src/DL_Log_Parser.php' );
}

if( ! class_exists( 'DL_Log_Table' ) ) :

/**
 * Table with log. User options for the table are implemented partially in {@see DL_Log_Screen}.
 * @since 1.0.0
 * @todo Default column and direction for sorting should be set via user preferences.
 * @todo Default per page items count should be set via user preferences.
 * @todo Now we get all data at the first place and than we set pagination but it means that we are parsing data we don't need to - so this must be implemented other way.
 * @todo We should parse only rows that going to be displayed not all of them.
 */
class DL_Log_Table extends WP_List_Table {

    /**
     * @var string Ascendant direction of the sorting.
     * @since 1.0.0
     */
    const DEFAULT_SORT_DIR_ASC  = 'asc';

    /**
     * @var string Descendant direction of the sorting.
     * @since 1.0.0
     */
    const DEFAULT_SORT_DIR_DESC = 'desc';

    /**
     * @var string Comma-separated list of defaultly shown columns.
     * @since 1.0.0
     */
    const DEFAULT_SHOWN_COLS = 'id,type';

    /**
     * @var string Default per page items count.
     * @since 1.0.0
     */
    const DEFAULT_PER_PAGE = 10;

    /**
     * @var string Defaultly sorted column.
     * @since 1.0.0
     */
    const DEFAULT_SORT_COL = 'time';

    /**
     * @var string Default sorting direction.
     * @since 1.0.0
     */
    const DEFAULT_SORT_DIR = self::DEFAULT_SORT_DIR_DESC;

    /**
     * @var boolean Default settings for displaying icons instead of text in record type column.
     * @since 1.0.0
     */
    const DEFAULT_SHOW_ICONS = true;

    /**
     * @var boolean Default settings for displaying file names as HTML links to real source files.
     * @since 1.0.0
     */
    const DEFAULT_SHOW_LINKS = true;

    /**
     * @var boolean Default settings for displaying stack trace in the table.
     * @since 1.0.0
     */
    const DEFAULT_SHOW_TRACE = true;

    /**
     * @var boolean Default settings for displaying short links to source codes.
     * @since 1.0.0
     */
    const DEFAULT_SHORT_SRC_LINKS = true;

    /**
     * @const integer Default width of popup window with source code.
     * @since 1.0.0
     */
    const DEFAULT_SRC_WIN_WIDTH = 900;

    /**
     * @const integer Default height of popup window with source code.
     * @since 1.0.0
     */
    const DEFAULT_SRC_WIN_HEIGHT = 500;

    /**
     * const string
     * @since 1.0.0
     */
    const DEFAULT_VIEW = 'all';

    /**
     * @var DL_Log_Parser $parser
     * @since 1.0.0
     */
    protected $parser = null;

    /**
     * Constructor.
     * @param array $args (Optional.)
     * @return void
     * @since 1.0.0
     */
    public function __construct( $args = [] ) {
        parent::__construct( [
            'singular' => __( 'Record', DL_SLUG ),
            'plural'   => __( 'Records', DL_SLUG ),
            'ajax'     => true,
        ] );

        $this->parser = new DL_Log_Parser( null, self::get_options() );
    }

    /**
     * Returns default options for the table.
     * @return array
     * @since 1.0.0
     */
    public static function get_default_options() {
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
     * Returns options for the table.
     * @return array
     * @since 1.0.0
     * @todo This should be located in {@see DL_Log_Screen}!
     */
    public static function get_options() {
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
        $currents = [
            'shown_cols'      => $shown_cols,
            'per_page'        => ( int ) $per_page,
            'show_icons'      => ( bool ) $show_icons,
            'show_links'      => ( bool ) $show_links,
            'show_trace'      => ( bool ) $show_trace,
            'sort_col'        => $sort_col,
            'sort_dir'        => $sort_dir,
            'short_src_links' => (bool) $short_src_links,
            'src_win_width'   => (int) $src_win_width,
            'src_win_height'  => (int) $src_win_height,
        ];

        return array_merge( $defaults, $currents );
    }

    /**
     * Renders checkbox column.
     * @param DL_Log_Record $item
     * @return string
     * @since 1.0.0
     */
    function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="log_item[]" value="%s">', $item->get_id()
        );
    }

    /**
     * Default function for rendering columns.
     * @param DL_Log_Record $item
     * @param string $column_name
     * @return string
     * @since 1.0.0
     */
    public function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'id':
                return $item->get_id();

            case 'time':
                return $item->get_time( true );

            case 'text':
                return $item->get_message();

            case 'type':
                return $item->get_type();

            default:
                return '';
        }
    }

    /**
     * Renders column with log record type.
     * @param DL_Log_Record $item
     * @return string
     * @since 1.0.0
     */
    public function column_type( DL_Log_Record $item ) {
        $show_icons = self::get_options()['show_icons'];

        switch( $item->get_type() ) {
            case DL_Log_Record::TYPE_ERROR:
                $lbl = __( 'Error', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-warning" title="' . $lbl .'"></span>' : $lbl;

            case DL_Log_Record::TYPE_NOTICE:
                $lbl = __( 'Notice', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-format-status" title="' . $lbl .'"></span>' : $lbl;

            case DL_Log_Record::TYPE_OTHER:
                $lbl = __( 'Other', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-editor-help" title="' . $lbl .'"></span>' : $lbl;

            case DL_Log_Record::TYPE_PARSER:
                $lbl = __( 'Parser error', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons  dashicons-thumbs-down" title="' . $lbl . '"></span>' : $lbl;

            case DL_Log_Record::TYPE_WARNING:
                $lbl = __( 'Warning', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-flag" title="' . $lbl .'"></span>' : $lbl;
        }
    }

    /**
     * Renders `text` column with the `delete` and `view` action.
     * @param DL_Log_Record $item
     * @return string
     * @since 1.0.0
     * @todo Display also stack trace!
     */
    public function column_text( DL_Log_Record $item ) {
        $id = ( int ) $item->get_id();
        $text = $item->get_message();
        $options = $this->get_options();
        $show_links = $options['show_links'];
        $show_trace = $options['show_trace'];
        $show_type  = ( strpos( $options['shown_cols'], 'type' ) !== false );

        if( $show_type !== true ) {
            $text = '<b>' . $item->get_type() . '</b>: ' . $text;
        }

        if( $show_links === true ) {
            $text = $this->parser->make_source_links( $text );
        }

        if( $item->has_trace() === true ) {
            $icon = ( $show_trace === true )
                    ? '<span class="dashicons dashicons-arrow-up-alt2"></span>'
                    : '<span class="dashicons dashicons-arrow-down-alt2"></span>';

            $text .= '<div class="stack-trace ' . ( $show_trace === true ? '' : 'stack-trace--collapsed' ) . '">' . PHP_EOL;
            $text .= '    <b onclick="StackTraceToggler.toggle(this)">' . $icon . ' ' . __( 'Stack trace', DL_SLUG ) . '</b>' . PHP_EOL;
            $text .= '    <ul>' . PHP_EOL;

            foreach( $item->get_trace() as $trace ) {
                if( $show_links === true ) {
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
     * @return void
     * @since 1.0.0
     */
    public function display_rows() {
        foreach( $this->items as $item ) {
            if( ! ( $item instanceof \DL_Log_Record ) ) {
                continue;
            }

            $this->single_row( $item );
        }
    }

    /**
     * Returns array describing bulk actions.
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
     * Returns array describing row actions.
     * @param DL_Log_Record $item
     * @return array
     * @since 1.0.0
     * @todo Base URL of the screen should be taken from method of the screen's class!
     */
    public function get_row_actions( DL_Log_Record $item ) {
        $actions = [
            'delete' => sprintf(
                    '<a href="%s">%s</a>',
                    $this->get_current_table_url( [ 'action' => 'delete', 'record' => $item->get_id() ] ),
                    __( 'Delete', DL_SLUG )
            ),
        ];

        return $actions;
    }

    /**
     * Returns URL of the WP admin page where the table lives on.
     * @param array $args (Optional.) Additional URL arguments as key => value array.
     * @return string
     * @since 1.0.0
     */
    public function get_table_url( $args = [] ) {
        return admin_url( 'tools.php?page=' . DL_Log_Screen::SLUG );
    }

    /**
     * Returns current table URL (with all parameters - filter, paging etc.).
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
        if( $paged > 1 ) {
            $url .= "&amp;paged={$paged}";
        }

        // Default table argument "filter"
        $filter = filter_input( INPUT_GET, 'filter' );
        if( ! empty( $filter ) ) {
            $url .= "&amp;filter={$filter}";
        }

        // Other arguments
        foreach( $args as $key => $val ) {
            $url .= "&amp;{$key}={$val}";
        }

        return $url;
    }

    /**
     * Returns array with table columns.
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
     * Returns array with table columns that can be hidden.
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
     * Returns array with table columns that are hidden.
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
     * Returns array with sortable table columns.
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
     * Returns array with the list of views available on this table.
     *
     * @access protected
     * @return array
     * @since 1.0.0
     * @todo Load correct count of items!
     * @todo Highlight currently selected view!
     */
    protected function get_views() {
        $views = [
            'today'     => __( 'Today', DL_SLUG ),
            'yesterday' => __( 'Yesterday', DL_SLUG ),
            'earlier'   => __( 'Earlier', DL_SLUG ),
            'all'       => __( 'All', DL_SLUG ),
        ];
        $current_view = filter_input( INPUT_GET, 'view' );

        if( empty( $current_view ) ) {
            $current_view = 'all';
        }

        $ret = [];

        foreach( $views as $view => $view_lbl ) {
            $url = add_query_arg( [ 'page' => 'odwpdl-log', 'view' => $view ], admin_url( 'tools.php') );
            $cls = ( $view == $current_view ) ? ' class="current"' : '';
            $cnt = $this->get_view_items_count( $view );
            $ret[$view] = sprintf( '<a href="%s"%s>%s (%s)</a>', $url, $cls, $view_lbl, $cnt );
        }

        return $ret;
    }

    /**
     * Display the list of views available on this table - but only when
     * there are some table items.
     * @access public
     * @return void
     * @see WP_List_Table::views
     * @since 1.0.0
     */
    public function views() {
        if( count( $this->items ) <= 0 ) {
            return;
        }

        parent::views();
    }

    /**
     * @internal Returns count of items for given view.
     * @param string $view
     * @return integer
     * @since 1.0.0
     */
    private function get_view_items_count( $view ) {
        $this->parser->reset();

        if( $view == 'all' ) {
            $this->parser->filter( function( \DL_Log_Record $record ) {
                return true;
            } );
        }
        elseif( $view == 'today' ) {
            $this->parser->filter( function( \DL_Log_Record $record ) {
                return $record->was_today();
            } );
        }
        elseif( $view == 'yesterday' ) {
            $this->parser->filter( function( \DL_Log_Record $record ) {
                return $record->was_yesterday();
            } );
        }
        elseif( $view == 'earlier' ) {
            $this->parser->filter( function( \DL_Log_Record $record ) {
                return ( ! $record->was_today() && ! $record->was_yesterday() );
            } );
        }

        // Get data
        extract( $this->get_order_args() );
        $this->parser->sort( [
            'sort_col'    => $orderby,
            'sort_dir'    => $order,
        ] );
        $data = $this->parser->get_data( ['page' => -1] );

        return count( $data );
    }

    /**
     * Get current filter settings.
     * @return array
     * @since 1.0.0
     */
    public function get_filter() {
        $_time = filter_input( INPUT_GET, 'filter-by-time' );
        $_type = filter_input( INPUT_GET, 'filter-by-type' );

        /**
         * @var array $filter
         */
        $filter = [];
        $filter['time'] = empty( $_time ) ? 0 : ( int ) $_time;
        $filter['type'] = empty( $_type ) ? 0 : ( int ) $_type;

        return $filter;
    }

    /**
     * Extra controls to be displayed between bulk actions and pagination
     * @param string $which
     * @return void
     * @since 1.0.0
     * @todo Remember filtering for the next session (save it as user preferences and renew it).
     */
    protected function extra_tablenav( $which ) {
        if( $which != 'top' ) {
            return;
        }

        if( $this->parser->get_total_count() == 0 ) {
            return;
        }

        /**
         * @var array $filter Contains array with filter settings (e.g. <code>['time' => 0, 'type' => 0]</code>).
        */
        $filter = $this->get_filter();
        $time   = intval( $filter['time'] );
        $type   = intval( $filter['type'] );

        $time_filters = [
            0 => __( '—— All ——', DL_SLUG ),
            1 => __( 'Last hour', DL_SLUG ),
            2 => __( 'Today', DL_SLUG ),
            3 => __( 'Yesterday', DL_SLUG ),
            4 => __( 'Last week', DL_SLUG ),
            5 => __( 'Last month', DL_SLUG ),
        ];

        $type_filters = [
            0 => __( '—— All ——', DL_SLUG ),
            1 => __( 'Error', DL_SLUG ),
            2 => __( 'Notice', DL_SLUG ),
            3 => __( 'Parser error', DL_SLUG ),
            4 => __( 'Warning', DL_SLUG ),
            5 => __( 'Other', DL_SLUG ),
        ];

        // Print the filters
        echo DL_Plugin::load_template(
            'screen-log_extra_tablenav', [
                'filter'       => $filter,
                'time'         => $time,
                'type'         => $type,
                'time_filters' => $time_filters,
                'type_filters' => $type_filters,
            ]
        );
    }

    /**
     * Message to be displayed when there are no items
     *
     * @since 3.1.0
     * @access public
     */
    public function no_items() {
        printf(
            '<p class="odwpdl-no_items">%s</p>',
            __( 'Your file <code>debug.log</code> is empty &ndash; that means no errors <strong class="noitems-smiley">:-)</strong>&hellip;', DL_SLUG )
        );
    }

    /**
     * Prepares data items for the table.
     * @return void
     * @since 1.0.0
     */
    public function prepare_items() {
        $options  = self::get_options();

        // Set up column headers
        $this->_column_headers = [
            $this->get_columns(),
            $this->get_hidden_columns(),
            $this->get_sortable_columns(),
        ];

        // Prepare data
        $this->parser->reset();

        // Process row and bulk actions
        // XXX In this moment is log not parsed so it can't work!
        // XXX $this->process_row_actions();
        // XXX $this->process_bulk_actions();

        // Use view
        $view = filter_input( INPUT_GET, 'view' );
        if( !in_array( $view, [ 'today', 'yesterday', 'earlier', 'all' ] ) ) {
            $view = self::DEFAULT_VIEW;
        }
        $this->parser->set_view( $view );

        // Get order arguments
        extract( $this->get_order_args() );
        // Needed hack (because otherway is arrow indicating sorting
        // in table head not displayed correctly).
        $_GET['orderby'] = $orderby;
        $_GET['order'] = $order;

        // Apply filtering
        $this->apply_filter( $this->get_filter() );

        // Apply sorting
        $this->parser->sort( [
            'sort_col'    => $orderby,
            'sort_dir'    => $order,
        ] );

        // Pagination arguments
        $this->set_pagination_args( [
            'total_items' => $this->parser->get_total_count(),
            'per_page'    => $this->parser->get_options( 'per_page', self::DEFAULT_PER_PAGE ),
        ] );

        // Get data to display
        $this->items = $this->parser->get_data( [
            'page' => $this->get_pagenum()
        ] );
    }

    /**
     * Applies filter on parser data.
     * @param array $filter Array with filter settings (e.g. <code>['time' => 0, 'type' => 0]</code>).
     * @return void
     * @since 1.0.0
     * @todo Finish filtering by time!
     */
    private function apply_filter( array $filter ) {
        // Filter by time
        $time = null;

        switch( ( int ) $filter['time'] ) {
            // TODO Finish filtering by time!
        }

        // ...

        // Filter by type
        $type = null;

        switch( ( int ) $filter['type'] ) {
            case 1 : $type = DL_Log_Record::TYPE_ERROR; break;
            case 2 : $type = DL_Log_Record::TYPE_NOTICE; break;
            case 3 : $type = DL_Log_Record::TYPE_PARSER; break;
            case 4 : $type = DL_Log_Record::TYPE_WARNING; break;
            case 5 : $type = DL_Log_Record::TYPE_OTHER; break;
            case 0 :
            default: $type = null; break;
        }

        if( ! is_null( $type ) ) {
            $this->parser->filter( function( \DL_Log_Record $record ) use( $type ) {
                return ( $record->get_type() == $type );
            } );
        }
    }

    /**
     * @internal Returns array with sorting arguments ['orderby' => 'id', 'order' => 'asc'].
     * @return array
     * @since 1.0.0
     */
    private function get_order_args() {
        $options  = self::get_options();
        $orderby = filter_input( INPUT_GET, DL_Log_Screen::SLUG . '-sort_col' );
        $order = filter_input( INPUT_GET, DL_Log_Screen::SLUG . '-sort_dir' );

        if( empty( $orderby ) ) {
            $orderby = filter_input( INPUT_GET, 'orderby' );
        }

        if( empty( $orderby ) ) {
            $orderby = $options['sort_col'];
        }

        if( empty( $order ) ) {
            $order = filter_input( INPUT_GET, 'order' );
        }

        if( empty( $order ) ) {
            $order = $options['sort_dir'];
        }

        return ['order' => $order, 'orderby' => $orderby];
    }

    /**
     * Process bulk actions.
     * @return void
     * @since 1.0.0
     * @todo Finish this!
     */
    public function process_bulk_actions() {
        /**
         * Name of bulk action we should perform.
         * @var string $action
         */
        $action = filter_input( INPUT_GET, 'action' );

        // There are top and bottom toolbars submit buttons...
        if( empty( $action ) ) {
            $action = filter_input( INPUT_GET, 'action2' );
        }

        // But no one was pressed
        if( empty( $action ) ) {
            return;
        }

        // Validate action, otherwise return
        if( ! in_array( $action, ['delete'] ) ) {
            DL_Plugin::print_admin_notice(
                sprintf( __( 'Requested action "<b>%s</b>" was not recognized!', DL_SLUG ), $action ),
                'warning',
                true
            );
            return;
        }

        /**
         * IDs (or line numbers) of log records to process with action.
         * @var array $log_items
         */
        $log_items = filter_input( INPUT_GET, 'log_item', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

        // There are no items to delete
        if( count( $log_items ) == 0 ) {
            DL_Plugin::print_admin_notice(
                __( 'Nothing deleted - no records were selected.', DL_SLUG ),
                'info',
                true
            );
            return;
        }

        // Delete records
        $res = $this->parser->delete_records( $log_items );

        // Print output message
        if( $res === false ) {
            DL_Plugin::print_admin_notice(
                __( 'Error occured during deleting records from <code>debug.log</code> file.', DL_SLUG ),
                'error'
            );
        }
        else if( $res === 0 ) {
            DL_Plugin::print_admin_notice(
                __( 'No records from <code>debug.log</code> file were deleted.', DL_SLUG ),
                'info'
            );
        }
        else {
            // bylo smazano X polozek
            DL_Plugin::print_admin_notice(
                sprintf(
                    __( 'Deleting were successfull (deleted %1$d records).', DL_SLUG ),
                    $res
                ),
                'success'
            );
        }

        if( $this->parser->is_saved() !== true ) {
            $this->parser->save();
        }
    }

    /**
     * Process row actions. As are defined in {@see DL_Log_Table::column_text()}.
     * @return void
     * @since 1.0.0
     * @todo Use {@see wp_redirect()} at the end?
     */
    public function process_row_actions() {
        // If request's method is not GET return
        if( strtolower( filter_input( INPUT_SERVER, 'method' ) ) !== 'get' ){
            return;
        }

        $action = filter_input( INPUT_GET, 'action' );
        $record = (int) filter_input( INPUT_GET, 'record' );

        // Validate action, otherwise return
        if( ! in_array( $action, ['delete'] ) || empty( $record ) ) {
            DL_Plugin::print_admin_notice(
                __( 'Requested action "<b>%s</b>" was not recognized!', DL_SLUG ),
                'warning',
                true
            );
            return;
        }

        // Perform action
        if( $action == 'delete' ) {
            // Delete selected log record.
            $msg_text = $msg_type = '';

            if( $this->parser->delete_record( $record ) === true ) {
                $msg_text = __( 'Record on line <b>%d</b> was successfully removed from <code>debug.log</code> file.', DL_SLUG );
                $msg_type = 'success';
            } else {
                $msg_text = __( 'Record on line <b>%d</b> from <code>debug.log</code> file was not deleted!', DL_SLUG );
                $msg_type = 'error';
            }

            DL_Plugin::print_admin_notice( sprintf( $msg_text, $record ), $msg_type, true );
        }

        if( $this->parser->is_saved() !== true ) {
            $this->parser->save();
        }
    }

    /**
     * We override default {@see WP_List_Table::pagination()} method because
     * we need to add filter argument into it.
     * @param string $which
     * @return void
     * @since 1.0.0
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
        $removable_query_args = wp_removable_query_args();

        $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

        $current_url = remove_query_arg( $removable_query_args, $current_url );

        // [ondrejd]: added filter
        $filter = $this->get_filter();
        if( $filter > 0 ) {
            $current_url = add_query_arg( 'filter-by-type', $filter, $current_url );
        }

        $page_links = array();

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
                    __( 'First page', DL_SLUG ),
                    '&laquo;'
            );
        }

        if ( $disable_prev ) {
            $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = sprintf( "<a class='prev-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                    esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
                    __( 'Previous page', DL_SLUG ),
                    '&lsaquo;'
            );
        }

        if ( 'bottom' === $which ) {
            $html_current_page  = $current;
            $total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page', DL_SLUG ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
        } else {
            $html_current_page = sprintf( "%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging'><span class='tablenav-paging-text'>",
                    sprintf( '<label for="current-page-selector" class="screen-reader-text">%s</label>', __( 'Current Page', DL_SLUG ) ),
                    $current,
                    strlen( $total_pages )
            );
        }
        $html_total_pages = sprintf( '<span class="total-pages">%s</span>', number_format_i18n( $total_pages ) );
        $page_links[] = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging', DL_SLUG ), $html_current_page, $html_total_pages ) . $total_pages_after;

        if ( $disable_next ) {
            $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span>';
        } else {
            $page_links[] = sprintf( "<a class='next-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                    esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
                    __( 'Next page', DL_SLUG ),
                    '&rsaquo;'
            );
        }

        if ( $disable_last ) {
            $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span>';
        } else {
            $page_links[] = sprintf( "<a class='last-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                    esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
                    __( 'Last page', DL_SLUG ),
                    '&raquo;'
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
     * We override default {@see WP_List_Table::print_column_headers()} method
     * because we need to add filter argument into it.
     * @param boolean $with_id (Optional.)
     * @return void
     * @since 1.0.0
     */
    public function print_column_headers( $with_id = true ) {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
        $current_url = remove_query_arg( 'paged', $current_url );

        // [ondrejd]: added filter
        $filter = $this->get_filter();
        if( $filter > 0 ) {
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

                if ( 'cb' === $column_key )
                        $class[] = 'check-column';
                elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) )
                        $class[] = 'num';

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

                if ( !empty( $class ) )
                        $class = "class='" . join( ' ', $class ) . "'";

                echo "<$tag $scope $id $class>$column_display_name</$tag>";
        }
    }
}

endif;
