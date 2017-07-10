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

if( ! class_exists( 'DL_Log_Record' ) ) {
    require_once( DL_PATH . 'src/DL_Log_Record.php' );
}

if( ! class_exists( 'DL_Log_Table' ) ) :

/**
 * Table with log. User options for the table are implemented partially in {@see DL_Log_Screen}.
 * @return string
 * @since 1.0.0
 * @todo Default column and direction for sorting should be set via user preferences.
 * @todo Default per page items count should be set via user preferences.
 * @todo Now we get all data at the first place and than we set pagination but it means that we are parsing data we don't need to - so this must be implemented other way.
 */
class DL_Log_Table extends WP_List_Table {
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
    const DEFAULT_SORT_DIR = 'desc';

    /**
     * @var boolean Default settings for displaying icons instead of text in record type column.
     * @since 1.0.0
     */
    const DEFAULT_SHOW_ICONS = true;

    /**
     * Returns default options for the table.
     * @return array
     * @since 1.0.0
     */
    public static function get_default_options() {
        return [
            'per_page'   => self::DEFAULT_PER_PAGE,
            'show_icons' => self::DEFAULT_SHOW_ICONS,
            'sort_col'   => self::DEFAULT_SORT_COL,
            'sort_dir'   => self::DEFAULT_SORT_DIR,
        ];
    }

    /**
     * Returns options for the table.
     * @return array
     * @since 1.0.0
     * @todo We should load actual options from the user metas and then merge them with the defaults options.
     * @todo Finish this!
     */
    public static function get_options() {
        $defaults = self::get_default_options();

        //...

        return $defaults;
    }

    /**
     * Renders checkbox column.
     * @param DL_Log_Record $item
     * @return string
     * @since 1.0.0
     */
    function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="log_item[]" value="%s">', $item->getId()
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
                return $item->getId();

            case 'time':
                return $item->getTime( true );

            case 'text':
                return $item->getMessage();

            case 'type':
                return $item->getType();

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
    function column_type( DL_Log_Record $item ) {
        $show_icons = self::get_options()['show_icons'];

        switch( $item->getType() ) {
            case DL_Log_Record::TYPE_ERROR:
                $lbl = __( 'Chyba', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-warning" title="' . $lbl .'"></span>' : $lbl;

            case DL_Log_Record::TYPE_NOTICE:
                $lbl = __( 'Upozornění', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-format-status" title="' . $lbl .'"></span>' : $lbl;

            case DL_Log_Record::TYPE_OTHER:
                $lbl = __( 'Ostatní', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-editor-help" title="' . $lbl .'"></span>' : $lbl;

            case DL_Log_Record::TYPE_WARNING:
                $lbl = __( 'Varování', DL_SLUG );
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
        $id      = ( int ) $item->getId();
        $page    = ( int ) filter_input( INPUT_GET, 'page' );
        $text    = $item->getMessage();
        $actions = [
            'view'   => sprintf( __( '<a href="?page=%s&amp;action=%s&amp;record=%s">Zobrazit</a>', DL_SLUG ), $page, 'view', $id ),
            'delete' => sprintf( __( '<a href="?page=%s&amp;action=%s&amp;record=%s">Smazat</a>', DL_SLUG ), $page, 'delete', $id ),
        ];

        return sprintf('%1$s %2$s', $text, $this->row_actions( $actions ) );
    }

    /**
     * Returns array describing bulk actions available for the table.
     * @return array
     * @since 1.0.0
     */
    function get_bulk_actions() {
        $actions = [
            'delete' => __( 'Smaž', DL_SLUG ),
        ];
        return $actions;
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
     * Prepares data items for the table.
     * @return void
     * @since 1.0.0
     */
    public function prepare_items() {
        // Prepare columns
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();

        // Set up column headers
        $this->_column_headers = [$columns, $hidden, $sortable];

        // Prepare data
        $data = $this->get_data();
        usort( $data, [__CLASS__, 'usort_reorder'] );

        // Set up pagination
        $per_page     = self::get_options()['per_page'];
        $current_page = $this->get_pagenum();
        $total_items  = count( $data );
        $found_items  = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ] );

        // Set table items
        $this->items = $found_items;
    }

    /**
     * Returns data from the `debug.log` file.
     * @return array
     * @since 1.0.0
     * @todo We should probably not read debug.log file directly in here but use some sort of cache instead.
     */
    protected function get_data() {
        $log_raw = file( DL_LOG, FILE_SKIP_EMPTY_LINES );
        $log = [];
        $record = null;

        foreach( $log_raw as $log_line ) {
            $matches = preg_split(
                '/(\[[0-9]{2}-[a-zA-Z]{3}-[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2} [a-zA-Z]{0,3}\]) ([a-zA-Z\,\s\d:=\/.\-\_\(\)\'\"$]*)/',
                $log_line,
                -1,
                PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
            );

            if( is_array( $matches ) ) {
                if( count( $matches ) == 2 ) {
                    // This is normal log row (date and details)
                    if( ( $record instanceof DL_Log_Record ) ) {
                        array_push( $log, $record );                        
                    }

                    $record = new DL_Log_Record( 0, '', '' );
                    $record->setId( count( $log ) + 1 );
                    $record->setTime( strtotime( trim( $matches[0], '[]' ) ) );

                    $message = $matches[1];

                    if( strpos( $matches[1], DL_Log_Record::TYPE_ERROR ) === 0 ) {
                        $record->setType( DL_Log_Record::TYPE_ERROR );
                        $message = str_replace( DL_Log_Record::TYPE_ERROR . ': ', '', $message );
                    }
                    else if( strpos( $matches[1], DL_Log_Record::TYPE_NOTICE ) === 0 ) {
                        $record->setType( DL_Log_Record::TYPE_NOTICE );
                        $message = str_replace( DL_Log_Record::TYPE_NOTICE . ': ', '', $message );
                    }
                    else if( strpos( $matches[1], DL_Log_Record::TYPE_WARNING ) === 0 ) {
                        $record->setType( DL_Log_Record::TYPE_WARNING );
                        $message = str_replace( DL_Log_Record::TYPE_WARNING . ': ', '', $message );
                    }
                    else {
                        $record->setType( DL_Log_Record::TYPE_OTHER );
                    }

                    $record->setMessage( $message );
                }
                elseif( count( $matches ) == 1 && ( $record instanceof DL_Log_Record ) ) {
                    if( strpos( $matches[0], '#' ) === 0 ) {
                        // This is just continue of of previous line (debug details)
                        $record->addTrace( $matches[0] );
                    }
                }
                /**
                 * @todo Don't forget to remove this before the first release!!!
                 */
                else {
                    var_dump( $matches );echo '<br>';
                }
            }
            /**
             * @todo Don't forget to remove this before the first release!!!
             */
            else {
                var_dump( $matches );echo '<br>';
            }
        }

        if( ( $record instanceof DL_Log_Record ) ) {
            array_push( $log, $record );
        }

        return $log;
    }

    /**
     * @internal Sorting method for the table data.
     * @param DL_Log_Record $a The first row.
     * @param DL_Log_Record $b The second row.
     * @return integer
     * @since 1.0.0
     */
    protected function usort_reorder( DL_Log_Record $a, DL_Log_Record $b ) {
        $orderby = filter_input( INPUT_GET, 'orderby' );
        if( empty( $orderby ) ) {
            $orderby = self::get_options()['sort_col'];
        }

        $order = filter_input( INPUT_GET, 'order' );
        if( empty( $order ) ) {
            $order = self::get_options()['sort_dir'];
        }

        $val1 = null;
        $val2 = null;
        switch( $orderby ) {
            case 'id':
                $val1 = $a->getId();
                $val2 = $b->getId();
                break;

            case 'time':
                $val1 = $a->getTime();
                $val2 = $b->getTime();
                break;

            case 'text':
                $val1 = $a->getMessage();
                $val2 = $b->getMessage();
                break;

            case 'type':
                $val1 = $a->getType();
                $val2 = $b->getType();
                break;
        }

        $result = strcmp( $val1, $val2 );

        return ( $order === 'asc' ) ? $result : -$result;
    }

}

endif;
