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
     * @var string Comma-separated list of defaultly hidden columns.
     * @since 1.0.0
     */
    const DEFAULT_HIDDEN_COLS = 'id';

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
            'singular' => __( 'Záznam', DL_SLUG ),
            'plural'   => __( 'Záznamy', DL_SLUG ),
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
            'hidden_cols' => self::DEFAULT_HIDDEN_COLS,
            'per_page'    => self::DEFAULT_PER_PAGE,
            'show_icons'  => self::DEFAULT_SHOW_ICONS,
            'show_links'  => self::DEFAULT_SHOW_LINKS,
            'show_trace'  => self::DEFAULT_SHOW_TRACE,
            'sort_col'    => self::DEFAULT_SORT_COL,
            'sort_dir'    => self::DEFAULT_SORT_DIR,
        ];
    }

    /**
     * Returns options for the table.
     * @return array
     * @since 1.0.0
     */
    public static function get_options() {
        $user = get_current_user_id();

        $hidden_cols = get_user_meta( $user, DL_Log_Screen::SLUG . '-hidden_cols', true );
        if( strlen( $hidden_cols ) == 0 ) {
            $hidden_cols = self::DEFAULT_HIDDEN_COLS;
        }

        $per_page = get_user_meta( $user, DL_Log_Screen::SLUG . '-per_page', true );
        if( strlen( $per_page ) == 0 ) {
            $per_page = self::DEFAULT_PER_PAGE;
        }
;
        $show_icons = get_user_meta( $user, DL_Log_Screen::SLUG . '-show_icons', true );
        if( strlen( $show_icons ) == 0 ) {
            $show_icons = self::DEFAULT_SHOW_ICONS;
        }

        $show_links = get_user_meta( $user, DL_Log_Screen::SLUG . '-show_links', true );
        if( strlen( $show_links ) == 0 ) {
            $show_links = self::DEFAULT_SHOW_LINKS;
        }

        $show_trace = get_user_meta( $user, DL_Log_Screen::SLUG . '-show_trace', true );
        if( strlen( $show_trace ) == 0 ) {
            $show_trace = self::DEFAULT_SHOW_TRACE;
        }

        $sort_col = get_user_meta( $user, DL_Log_Screen::SLUG . '-sort_col', true );
        if( strlen( $sort_col ) == 0 ) {
            $sort_col = self::DEFAULT_SORT_COL;
        }

        $sort_dir = get_user_meta( $user, DL_Log_Screen::SLUG . '-sort_dir', true );
        if( strlen( $sort_dir ) == 0 ) {
            $sort_dir = self::DEFAULT_SORT_DIR;
        }

        $defaults = self::get_default_options();
        $currents = [
            'hidden_cols' => $hidden_cols,
            'per_page'    => ( int ) $per_page,
            'show_icons'  => ( bool ) $show_icons,
            'show_links'  => ( bool ) $show_links,
            'show_trace'  => ( bool ) $show_trace,
            'sort_col'    => $sort_col,
            'sort_dir'    => $sort_dir,
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
                $lbl = __( 'Chyba', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-warning" title="' . $lbl .'"></span>' : $lbl;

            case DL_Log_Record::TYPE_NOTICE:
                $lbl = __( 'Upozornění', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-format-status" title="' . $lbl .'"></span>' : $lbl;

            case DL_Log_Record::TYPE_OTHER:
                $lbl = __( 'Ostatní', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-editor-help" title="' . $lbl .'"></span>' : $lbl;

            case DL_Log_Record::TYPE_PARSER:
                $lbl = __( 'Chyba kódu', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons  dashicons-thumbs-down" title="' . $lbl . '"></span>' : $lbl;

            case DL_Log_Record::TYPE_WARNING:
                $lbl = __( 'Varování', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-flag" title="' . $lbl .'"></span>' : $lbl;

            case DL_Log_Record::TYPE_ODWPDL:
                $lbl = __( 'Chyba log parseru', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-no" title="' . $lbl .'"></span>' : $lbl;
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
            'delete' => __( 'Smaž', DL_SLUG ),
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
                    __( 'Smazat', DL_SLUG )
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
            'id'   => __( '<abbr title="Pořadové číslo">P.č.</abbr>', DL_LOG ),
            'time' => __( 'Datum a čas', DL_LOG ),
            'type' => __( 'Typ', DL_LOG ),
            'text' => __( 'Záznam', DL_LOG ),
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
        // Prepare defaultly hidden columns
        $defaults = [];
        foreach( $this->get_hideable_columns() as $key => $spec ) {
            if( ( bool ) $spec[1] === true ) {
                $defaults[] = $key;
            }
        }

        // Get hidden columns by user
        $hidden   = []; // TODO Get it from user_meta!

        // Returns it
        return array_merge( $defaults, $hidden );
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
            'today'     => __( 'Dnešní', DL_SLUG ),
            'yesterday' => __( 'Včerejší', DL_SLUG ),
            'earlier'   => __( 'Dřívější', DL_SLUG ),
            'all'       => __( 'Všechny', DL_SLUG ),
        ];
        $current_view = filter_input( INPUT_GET, 'view' );

        if( empty( $current_view ) ) {
            $current_view = 'all';
        }

        $ret = [];

        foreach( $views as $view => $view_lbl ) {
            $url = add_query_arg( 'view', $view, plugins_url() );
            $cls = ( $view == $current_view ) ? ' class="current"' : '';
            $cnt = $this->get_view_items_count( $view );
            $ret[$view] = sprintf( '<a href="%s"%s>%s (%s)</a>', $url, $cls, $view_lbl, $cnt );
        }

        return $ret;
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
                $date_today = new DateTime();
                $date_real = new DateTime( date( 'Y-m-d H:i:s', $record->get_time() ) );
                return ( $date_today == $date_real );
            } );
        }
        elseif( $view == 'yesterday' ) {
            $this->parser->filter( function( \DL_Log_Record $record ) {
                $date_yesterday = new DateTime();
                $date_yesterday->sub(new DateInterval( 'P1D' ) );
                $date_real = new DateTime( date( 'Y-m-d H:i:s', $record->get_time() ) );
                return ( $date_yesterday == $date_real );
            } );
        }
        elseif( $view == 'earlier' ) {
            $this->parser->filter( function( \DL_Log_Record $record ) {
                $date_yesterday = new DateTime();
                $date_yesterday->sub(new DateInterval( 'P1D' ) );
                $date_real = new DateTime( date( 'Y-m-d H:i:s', $record->get_time() ) );
                return ( $date_yesterday > $date_real );
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
     * 
     * @return integer
     */
    public function get_filter() {
        $filter = filter_input( INPUT_POST, 'filter-by-type' );

        if( empty( $filter ) ) {
            $filter = filter_input( INPUT_GET, 'filter-by-type' );
        }

        if( empty( $filter ) ) {
            return 0;
        }

        return ( int ) $filter;
    }

    /**
     * Extra controls to be displayed between bulk actions and pagination
     * @param string $which
     * @return void
     * @since 1.0.0
     * @todo That HTML source block should not be here!
     */
    protected function extra_tablenav( $which ) {
        if( $which != 'top' ) {
            return;
        }

        if( $this->parser->get_total_count() == 0 ) {
            return;
        }

        $filter = $this->get_filter();
?>
<div class="alignleft actions">
    <label for="filter_by_type" class="screen-reader-text"><?php _e( 'Typ záznamu', DL_SLUG ) ?></label>
    <select name="filter-by-type" id="filter_by_type">
        <option<?php selected( $filter, 0 ) ?> value="0"><?php _e( '— Typ záznamu —', DL_SLUG ) ?></option>
        <option<?php selected( $filter, 1 ) ?> value="1"><?php _e( 'Chyba', DL_SLUG ) ?></option>
        <option<?php selected( $filter, 2 ) ?> value="2"><?php _e( 'Oznámení', DL_SLUG ) ?></option>
        <option<?php selected( $filter, 3 ) ?> value="3"><?php _e( 'Chyba PHP parseru', DL_SLUG ) ?></option>
        <option<?php selected( $filter, 4 ) ?> value="4"><?php _e( 'Varování', DL_SLUG ) ?></option>
        <option<?php selected( $filter, 5 ) ?> value="5"><?php _e( 'Jiný', DL_SLUG ) ?></option>
    </select>
    <input name="<?php echo DL_SLUG . '-filter_submit' ?>" id="<?php echo DL_SLUG . '-filter_submit' ?>" class="button" value="<?php _e( 'Filtrovat', DL_SLUG ) ?>" type="submit">
</div>
<?php
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

        // Process row and bulk actions
        $this->process_row_actions();
        $this->process_bulk_actions();

        // Get order arguments
        extract( $this->get_order_args() );
        // Needed hack (because otherway is arrow indicating sorting
        // in table head not displayed correctly).
        $_GET['orderby'] = $orderby;
        $_GET['order'] = $order;

        // Prepare data
        $this->parser->reset();

        // Apply filters
        $filter = $this->get_filter();
        if( ! empty( $filter ) ) {
            $this->apply_filter( $filter );
        }

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
     * @param string $filter
     * @return void
     * @since 1.0.0
     */
    private function apply_filter( $filter ) {
        $_filter = null;

        switch( $filter ) {
            case 1 : $_filter = DL_Log_Record::TYPE_ERROR; break;
            case 2 : $_filter = DL_Log_Record::TYPE_NOTICE; break;
            case 3 : $_filter = DL_Log_Record::TYPE_PARSER; break;
            case 4 : $_filter = DL_Log_Record::TYPE_WARNING; break;
            case 5 : $_filter = DL_Log_Record::TYPE_OTHER; break;
        }

        if( ! is_null( $_filter ) ) {
            $this->parser->filter( function( \DL_Log_Record $record ) use( $_filter ) {
                return ( $record->get_type() == $_filter );
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
        $orderby = filter_input( INPUT_POST, DL_Log_Screen::SLUG . '-sort_col' );
        $order = filter_input( INPUT_POST, DL_Log_Screen::SLUG . '-sort_dir' );

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
        $action = filter_input( INPUT_POST, 'action' );
        if( empty( $action ) ) {
            $action = filter_input( INPUT_POST, 'action2' );
        }

        // Validate action, otherwise return
        if( in_array( $action, ['delete'] ) ) {
            return;
        }

        // ...
    }

    /**
     * Process row actions. As are defined in {@see DL_Log_Table::column_text()}.
     * @return void
     * @since 1.0.0
     * @todo Finish this!
     */
    public function process_row_actions() {
        $action = filter_input( INPUT_GET, 'action' );
        $record = (int) filter_input( INPUT_GET, 'record' );

        // Validate action, otherwise return
        if( ! in_array( $action, ['delete'] ) || empty( $record ) ) {
            return;
        }

        // Perform action
        if( $action == 'delete' ) {
            // Delete selected log record.
            $msg_text = $msg_type = '';

            if( $this->parser->delete_record( $record ) === true ) {
                $msg_text = __( 'Záznam z řádku <b>%d</b> byl úspěšně odstraněn ze souboru <code>debug.log</code>.', DL_SLUG );
                $msg_type = 'success';
            } else {
                $msg_text = __( 'Záznam z řádku <b>%d</b> souboru <code>debug.log</code> se nepodařilo smazat!', DL_SLUG );
                $msg_type = 'error';
            }

            DL_Plugin::print_admin_notice( sprintf( $msg_text, $record ), $msg_type, true );
        }
    }

    /**
     * Removes log record from the debug.log file.
     * @param integer $record_id
     * @return boolean
     * @since 1.0.0
     * @todo Finish this!
     */
    public function delete_log_record( $record_id ) {
        // ...
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

        $output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

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
                    __( 'First page' ),
                    '&laquo;'
            );
        }

        if ( $disable_prev ) {
            $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = sprintf( "<a class='prev-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                    esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
                    __( 'Previous page' ),
                    '&lsaquo;'
            );
        }

        if ( 'bottom' === $which ) {
            $html_current_page  = $current;
            $total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
        } else {
            $html_current_page = sprintf( "%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                    '<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
                    $current,
                    strlen( $total_pages )
            );
        }
        $html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
        $page_links[] = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;

        if ( $disable_next ) {
            $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span>';
        } else {
            $page_links[] = sprintf( "<a class='next-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                    esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
                    __( 'Next page' ),
                    '&rsaquo;'
            );
        }

        if ( $disable_last ) {
            $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span>';
        } else {
            $page_links[] = sprintf( "<a class='last-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                    esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
                    __( 'Last page' ),
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
                $columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All' ) . '</label>'
                        . '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
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

                        $column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
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
